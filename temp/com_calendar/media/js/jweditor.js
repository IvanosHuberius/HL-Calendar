/**
 * HL Calendar – minimal rich text editor for the event description.
 *
 * Shared by the component and the module: the toolbar is built here, so both
 * stay in sync automatically. Deliberately dependency-free and small – it only
 * produces the tags the server-side filter (EventService::cleanDescription)
 * keeps, so nothing a user formats can silently disappear on save.
 *
 * Usage:
 *   JwEditor.init(editableEl, labels);
 *   JwEditor.setHtml(editableEl, '<p>…</p>');
 *   var html = JwEditor.getHtml(editableEl);
 *
 * @license GNU/GPL v2 or later
 * @copyright (c) 2026 huberlabs.ch
 */
(function (window, document) {
    'use strict';

    var SVG = {
        ul: '<svg viewBox="0 0 24 24"><circle cx="4" cy="6" r="1.6"/><circle cx="4" cy="12" r="1.6"/><circle cx="4" cy="18" r="1.6"/><path d="M9 6h11M9 12h11M9 18h11"/></svg>',
        ol: '<svg viewBox="0 0 24 24"><text x="1" y="8" font-size="7">1</text><text x="1" y="15" font-size="7">2</text><text x="1" y="22" font-size="7">3</text><path d="M9 6h11M9 13h11M9 20h11"/></svg>',
        left: '<svg viewBox="0 0 24 24"><path d="M4 6h16M4 11h10M4 16h16M4 21h10"/></svg>',
        center: '<svg viewBox="0 0 24 24"><path d="M4 6h16M7 11h10M4 16h16M7 21h10"/></svg>',
        right: '<svg viewBox="0 0 24 24"><path d="M4 6h16M10 11h10M4 16h16M10 21h10"/></svg>',
        link: '<svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>',
        unlink: '<svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.5.5l1-1"/><path d="M14 11a5 5 0 0 0-7.5-.5l-1 1"/><path d="M4 4l16 16"/></svg>',
        clear: '<svg viewBox="0 0 24 24"><path d="M5 5h14M9 5l-2 14M15 5l2 14"/><path d="M4 19h16"/></svg>',
        source: '<svg viewBox="0 0 24 24"><path d="M9 7l-5 5 5 5M15 7l5 5-5 5"/></svg>'
    };

    /** Tags the paste cleanup keeps – mirrors what the toolbar itself produces. */
    var ALLOWED = ['B', 'STRONG', 'I', 'EM', 'U', 'S', 'STRIKE', 'DEL', 'P', 'BR',
        'DIV', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'UL', 'OL', 'LI', 'A', 'BLOCKQUOTE'];

    var savedRange = null;

    /** Labels come from Joomla translations and land in HTML attributes. */
    function esc(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function saveSelection(editable) {
        var sel = window.getSelection();
        if (!sel || !sel.rangeCount) {
            return;
        }
        var range = sel.getRangeAt(0);
        if (editable.contains(range.commonAncestorContainer)) {
            // Snapshot, not the live range: a live one keeps mutating with the
            // DOM and can end up pointing somewhere unrelated.
            savedRange = range.cloneRange();
        }
    }

    /**
     * The live selection always wins. `savedRange` is only a fallback for when
     * focus really left the editor (e.g. the link bar). Restoring a remembered
     * range on top of a perfectly good live one is how commands end up running
     * against a stale, detached range and silently doing nothing – execCommand
     * rewrites the DOM, which can invalidate a range we stored earlier.
     */
    function restoreSelection(editable) {
        var sel = window.getSelection();

        if (sel && sel.rangeCount
            && editable.contains(sel.getRangeAt(0).commonAncestorContainer)) {
            if (document.activeElement !== editable) {
                editable.focus();
            }
            return;
        }

        if (savedRange && editable.contains(savedRange.commonAncestorContainer)) {
            try {
                sel.removeAllRanges();
                sel.addRange(savedRange);
            } catch (e) {
                savedRange = null; // range went stale – better none than wrong
            }
        }

        if (document.activeElement !== editable) {
            editable.focus();
        }
    }

    function exec(editable, command, value) {
        restoreSelection(editable);
        try {
            document.execCommand(command, false, value || null);
        } catch (e) {
            /* command not supported – ignore rather than break the dialog */
        }
        saveSelection(editable);
        syncFormat(editable);
    }

    function anchorFromSelection(editable) {
        var sel = window.getSelection();
        if (!sel || !sel.focusNode) {
            return null;
        }
        var node = sel.focusNode;
        if (node.nodeType === 3) {
            node = node.parentNode;
        }
        while (node && node !== editable) {
            if (node.tagName === 'A') {
                return node;
            }
            node = node.parentNode;
        }
        return null;
    }

    /** Block tag the caret currently sits in ('p' when it is ordinary text). */
    function currentBlock(editable) {
        var sel = window.getSelection();
        if (!sel || !sel.focusNode) {
            return 'p';
        }
        var node = sel.focusNode;
        if (node.nodeType === 3) {
            node = node.parentNode;
        }
        while (node && node !== editable) {
            var tag = node.tagName;
            if (tag && /^H[1-6]$/.test(tag)) {
                return tag.toLowerCase();
            }
            if (tag === 'P' || tag === 'DIV' || tag === 'LI') {
                return 'p';
            }
            node = node.parentNode;
        }
        return 'p';
    }

    /** Every block element the current selection touches. */
    function blocksInSelection(editable) {
        var sel = window.getSelection();
        var blocks = [];

        if (!sel || !sel.rangeCount) {
            return blocks;
        }

        var range = sel.getRangeAt(0);
        Array.prototype.forEach.call(
            editable.querySelectorAll('p, div, h1, h2, h3, h4, h5, h6, li, blockquote'),
            function (el) {
                if (range.intersectsNode(el)) {
                    blocks.push(el);
                }
            }
        );

        return blocks;
    }

    var STATE_COMMANDS = ['bold', 'italic', 'underline', 'strikeThrough',
        'insertUnorderedList', 'insertOrderedList', 'justifyLeft', 'justifyCenter', 'justifyRight'];

    /**
     * Mirror the toolbar against the caret: the dropdown shows the real block
     * format, and active buttons light up. Without the highlight, applying bold
     * inside a heading (already bold) looks like nothing happened at all.
     */
    function syncFormat(editable) {
        var toolbar = editable._jwToolbar;
        if (!toolbar) {
            return;
        }

        var select = toolbar.querySelector('.jw-ed-format');
        if (select) {
            select.value = currentBlock(editable);
        }

        STATE_COMMANDS.forEach(function (command) {
            var button = toolbar.querySelector('.jw-ed-btn[data-cmd="' + command + '"]');
            if (!button) {
                return;
            }
            var active = false;
            try {
                active = document.queryCommandState(command);
            } catch (e) {
                active = false;
            }
            button.classList.toggle('jw-ed-on', active);
        });
    }

    function decorateAnchor(anchor, newTab) {
        if (!anchor) {
            return;
        }
        if (newTab) {
            anchor.setAttribute('target', '_blank');
            anchor.setAttribute('rel', 'noopener noreferrer');
        } else {
            anchor.removeAttribute('target');
            anchor.removeAttribute('rel');
        }
    }

    function normaliseUrl(url) {
        url = (url || '').trim();
        if (!url) {
            return '';
        }
        // Block anything that could execute; only real navigation targets pass.
        if (/^\s*(javascript|data|vbscript)\s*:/i.test(url)) {
            return '';
        }
        if (!/^([a-z][a-z0-9+.-]*:|\/|#|mailto:|tel:)/i.test(url)) {
            url = 'https://' + url;
        }
        return url;
    }

    /** Strip everything the toolbar could not have produced (Word/Web paste). */
    function cleanPastedHtml(html) {
        var holder = document.createElement('div');
        holder.innerHTML = html;

        holder.querySelectorAll('script,style,meta,link,title,head').forEach(function (el) {
            el.remove();
        });

        holder.querySelectorAll('*').forEach(function (el) {
            if (ALLOWED.indexOf(el.tagName) === -1) {
                // keep the text, drop the wrapper
                while (el.firstChild) {
                    el.parentNode.insertBefore(el.firstChild, el);
                }
                el.remove();
                return;
            }

            var align = (el.style && el.style.textAlign) || '';
            Array.prototype.slice.call(el.attributes).forEach(function (attr) {
                el.removeAttribute(attr.name);
            });

            if (el.tagName === 'A') {
                return; // href is re-added below only when it was safe
            }
            if (align === 'center' || align === 'right' || align === 'left') {
                el.style.textAlign = align;
            }
        });

        // Re-apply safe hrefs
        var source = document.createElement('div');
        source.innerHTML = html;
        var srcLinks = source.querySelectorAll('a');
        var dstLinks = holder.querySelectorAll('a');
        for (var i = 0; i < dstLinks.length && i < srcLinks.length; i++) {
            var href = normaliseUrl(srcLinks[i].getAttribute('href'));
            if (href) {
                dstLinks[i].setAttribute('href', href);
                if (srcLinks[i].getAttribute('target') === '_blank') {
                    decorateAnchor(dstLinks[i], true);
                }
            }
        }

        return holder.innerHTML;
    }

    function button(cmd, title, content, extraClass) {
        var t = esc(title);
        return '<button type="button" class="jw-ed-btn ' + (extraClass || '') + '" data-cmd="' + cmd +
            '" title="' + t + '" aria-label="' + t + '">' + content + '</button>';
    }

    function buildToolbar(labels) {
        var bar = document.createElement('div');
        bar.className = 'jw-ed-toolbar';

        var formatOptions =
            '<option value="p">' + esc(labels.normal) + '</option>' +
            '<option value="h1">H1</option>' +
            '<option value="h2">H2</option>' +
            '<option value="h3">H3</option>' +
            '<option value="h4">H4</option>' +
            '<option value="h5">H5</option>' +
            '<option value="h6">H6</option>';

        bar.innerHTML =
            '<select class="jw-ed-format" title="' + esc(labels.format) + '" aria-label="' + esc(labels.format) + '">' +
                formatOptions +
            '</select>' +
            '<span class="jw-ed-sep"></span>' +
            button('bold', labels.bold, 'B', 'jw-ed-b') +
            button('italic', labels.italic, 'I', 'jw-ed-i') +
            button('underline', labels.underline, 'U', 'jw-ed-u') +
            button('strikeThrough', labels.strike, 'S', 'jw-ed-s') +
            '<span class="jw-ed-sep"></span>' +
            button('insertUnorderedList', labels.bulletList, SVG.ul) +
            button('insertOrderedList', labels.numberList, SVG.ol) +
            '<span class="jw-ed-sep"></span>' +
            button('justifyLeft', labels.alignLeft, SVG.left) +
            button('justifyCenter', labels.alignCenter, SVG.center) +
            button('justifyRight', labels.alignRight, SVG.right) +
            '<span class="jw-ed-sep"></span>' +
            button('jwLink', labels.link, SVG.link) +
            button('unlink', labels.unlink, SVG.unlink) +
            button('jwClear', labels.clearFormat, SVG.clear) +
            '<span class="jw-ed-sep"></span>' +
            button('jwSource', labels.source, SVG.source, 'jw-ed-srcbtn');

        return bar;
    }

    function buildLinkBar(labels) {
        var bar = document.createElement('div');
        bar.className = 'jw-ed-linkbar';
        bar.style.display = 'none';
        bar.innerHTML =
            '<input type="text" class="jw-ed-linkurl" placeholder="' + esc(labels.linkUrl) + '">' +
            '<label class="jw-ed-linktab"><input type="checkbox" class="jw-ed-linkblank"> ' + esc(labels.linkNewTab) + '</label>' +
            '<button type="button" class="jw-ed-linkok">' + esc(labels.insert) + '</button>' +
            '<button type="button" class="jw-ed-linkcancel">' + esc(labels.cancel) + '</button>';
        return bar;
    }

    var JwEditor = {
        init: function (editable, labels) {
            if (!editable || editable.dataset.jwEditorReady === '1') {
                return;
            }
            editable.dataset.jwEditorReady = '1';
            editable.setAttribute('contenteditable', 'true');
            editable.setAttribute('role', 'textbox');
            editable.setAttribute('aria-multiline', 'true');

            var toolbar = buildToolbar(labels);
            var linkbar = buildLinkBar(labels);
            editable.parentNode.insertBefore(toolbar, editable);
            editable.parentNode.insertBefore(linkbar, editable.nextSibling);

            // Source view: lets people write HTML by hand instead of clicking,
            // and keeps hand-written descriptions from older versions editable.
            var source = document.createElement('textarea');
            source.className = 'jw-ed-source';
            source.setAttribute('spellcheck', 'false');
            source.style.display = 'none';
            editable.parentNode.insertBefore(source, editable.nextSibling);
            editable._jwSource = source;
            editable._jwToolbar = toolbar;

            try {
                document.execCommand('styleWithCSS', false, false);
            } catch (e) { /* older browsers */ }

            ['keyup', 'mouseup', 'blur'].forEach(function (evt) {
                editable.addEventListener(evt, function () {
                    saveSelection(editable);
                    syncFormat(editable);
                });
            });

            // mousedown default would move focus out of the editable and drop the selection
            toolbar.addEventListener('mousedown', function (e) {
                if (e.target.closest('.jw-ed-btn')) {
                    e.preventDefault();
                }
            });

            toolbar.addEventListener('click', function (e) {
                var btn = e.target.closest('.jw-ed-btn');
                if (!btn) {
                    return;
                }
                e.preventDefault();
                var cmd = btn.dataset.cmd;

                if (cmd === 'jwSource') {
                    JwEditor.toggleSource(editable);
                    return;
                }

                if (source.style.display !== 'none') {
                    return; // other buttons do nothing while editing raw HTML
                }

                if (cmd === 'jwClear') {
                    // execCommand('removeFormat') räumt NUR Inline-Formate weg –
                    // Überschriften und Ausrichtung bleiben stehen. Das erwartet
                    // aber niemand, also machen wir hier ganz sauber.
                    restoreSelection(editable);
                    try { document.execCommand('removeFormat', false, null); } catch (e) {}
                    try { document.execCommand('unlink', false, null); } catch (e) {}
                    try { document.execCommand('formatBlock', false, '<p>'); } catch (e) {}
                    // AFTER the block conversion – formatBlock carries the old
                    // style attribute over to the new element.
                    blocksInSelection(editable).forEach(function (el) {
                        if (el.style) {
                            el.style.textAlign = '';
                            if (!el.getAttribute('style')) {
                                el.removeAttribute('style');
                            }
                        }
                        el.removeAttribute('align');
                    });
                    saveSelection(editable);
                    syncFormat(editable);
                    return;
                }

                if (cmd === 'jwLink') {
                    saveSelection(editable);
                    var existing = anchorFromSelection(editable);
                    linkbar.querySelector('.jw-ed-linkurl').value = existing ? existing.getAttribute('href') || '' : '';
                    linkbar.querySelector('.jw-ed-linkblank').checked = existing
                        ? existing.getAttribute('target') === '_blank'
                        : true;
                    linkbar.style.display = 'flex';
                    linkbar.querySelector('.jw-ed-linkurl').focus();
                    return;
                }

                exec(editable, cmd);
            });

            toolbar.querySelector('.jw-ed-format').addEventListener('change', function () {
                exec(editable, 'formatBlock', '<' + this.value + '>');
            });

            linkbar.querySelector('.jw-ed-linkcancel').addEventListener('click', function () {
                linkbar.style.display = 'none';
                restoreSelection(editable);
            });

            linkbar.querySelector('.jw-ed-linkurl').addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    linkbar.querySelector('.jw-ed-linkok').click();
                }
            });

            linkbar.querySelector('.jw-ed-linkok').addEventListener('click', function () {
                var url = normaliseUrl(linkbar.querySelector('.jw-ed-linkurl').value);
                var newTab = linkbar.querySelector('.jw-ed-linkblank').checked;
                linkbar.style.display = 'none';

                if (!url) {
                    restoreSelection(editable);
                    return;
                }

                restoreSelection(editable);
                var sel = window.getSelection();

                if (sel && sel.isCollapsed) {
                    // nothing selected → insert the address itself as link text
                    var a = document.createElement('a');
                    a.href = url;
                    a.textContent = url;
                    decorateAnchor(a, newTab);
                    document.execCommand('insertHTML', false, a.outerHTML);
                } else {
                    document.execCommand('createLink', false, url);
                    var anchor = anchorFromSelection(editable);
                    if (anchor) {
                        decorateAnchor(anchor, newTab);
                    } else {
                        editable.querySelectorAll('a[href="' + url.replace(/"/g, '\\"') + '"]').forEach(function (el) {
                            decorateAnchor(el, newTab);
                        });
                    }
                }
                saveSelection(editable);
            });

            editable.addEventListener('paste', function (e) {
                var data = e.clipboardData;
                if (!data) {
                    return;
                }
                var html = data.getData('text/html');
                e.preventDefault();
                if (html) {
                    document.execCommand('insertHTML', false, cleanPastedHtml(html));
                } else {
                    document.execCommand('insertText', false, data.getData('text/plain'));
                }
            });
        },

        /** Switch between the visual editor and raw HTML, keeping content in sync. */
        toggleSource: function (editable) {
            var source = editable && editable._jwSource;
            if (!source) {
                return;
            }
            var toSource = source.style.display === 'none';

            if (toSource) {
                source.value = editable.innerHTML;
                source.style.height = Math.max(editable.offsetHeight, 90) + 'px';
                editable.style.display = 'none';
                source.style.display = '';
                source.focus();
            } else {
                editable.innerHTML = source.value;
                source.style.display = 'none';
                editable.style.display = '';
                editable.focus();
            }

            if (editable._jwToolbar) {
                editable._jwToolbar.classList.toggle('jw-ed-sourcemode', toSource);
            }
        },

        /** Editor content, or '' when the user left it visually empty. */
        getHtml: function (editable) {
            if (!editable) {
                return '';
            }
            var source = editable._jwSource;
            var html = (source && source.style.display !== 'none' ? source.value : editable.innerHTML).trim();

            if (!html || /^(<br\s*\/?>|&nbsp;|\s|<p>\s*<\/p>|<div>\s*<\/div>)+$/i.test(html)) {
                return '';
            }
            return html;
        },

        setHtml: function (editable, html) {
            if (!editable) {
                return;
            }
            editable.innerHTML = html || '';
            syncFormat(editable);

            // Always come back in visual mode when a dialog is (re)opened
            var source = editable._jwSource;
            if (source && source.style.display !== 'none') {
                source.value = editable.innerHTML;
                source.style.display = 'none';
                editable.style.display = '';
                if (editable._jwToolbar) {
                    editable._jwToolbar.classList.remove('jw-ed-sourcemode');
                }
            }
        }
    };

    window.JwEditor = JwEditor;
})(window, document);
