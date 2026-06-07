<?php
/**
 * @license GNU/GPL v2 or later
 * @copyright (c) 2026 huberlabs.ch
 */


defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

/** @var \Jewe\Component\Calendar\Site\View\Calendar\HtmlView $this */

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_calendar.fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css');
$wa->registerAndUseScript('com_calendar.fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js', [], ['defer' => false]);
$wa->registerAndUseStyle('com_calendar.style', 'media/com_calendar/css/calendar.css');

$baseUrl = Uri::root() . 'index.php?option=com_calendar&task=api.';
$token = Session::getFormToken();
$user = Factory::getApplication()->getIdentity();
$canEdit = $this->canEdit;
$categories = json_encode($this->categories);
$locale = Factory::getApplication()->getLanguage()->getTag();
$localeShort = substr($locale, 0, 2);
$isSuperUser = $user->authorise('core.admin');

// Load component params for colors and defaults
$cparams = ComponentHelper::getParams('com_calendar');
$primaryColor     = $cparams->get('primary_color', '#1a73e8');
$primaryHover     = $cparams->get('primary_hover_color', '#1765cc');
$todayHighlight   = $cparams->get('today_highlight_color', '#fff8e1');
$bgColor          = $cparams->get('background_color', '#ffffff');
$textColor        = $cparams->get('text_color', '#3c4043');
$textLight        = $cparams->get('text_light_color', '#70757a');
$borderColor      = $cparams->get('border_color', '#dadce0');
$hoverBg          = $cparams->get('hover_bg_color', '#f1f3f4');
$sidebarBg        = $cparams->get('sidebar_bg_color', '#ffffff');
$defaultView      = $cparams->get('default_view', 'dayGridMonth');
$firstDay         = (int) $cparams->get('first_day', 1);
$showWeekNumbers  = (int) $cparams->get('show_week_numbers', 1);
$showSidebar      = (int) $cparams->get('show_sidebar', 1);
$bhStart          = $cparams->get('business_hours_start', '08:00');
$bhEnd            = $cparams->get('business_hours_end', '18:00');
$defaultEvtColor  = $cparams->get('default_event_color', '#3788d8');

// Compute primary-light from primary color
$pr = hexdec(substr($primaryColor, 1, 2));
$pg = hexdec(substr($primaryColor, 3, 2));
$pb = hexdec(substr($primaryColor, 5, 2));
$primaryLight = sprintf('#%02x%02x%02x', min(255, $pr + round((255 - $pr) * 0.85)), min(255, $pg + round((255 - $pg) * 0.85)), min(255, $pb + round((255 - $pb) * 0.85)));
?>

<style>
    #jw-calendar-app {
        --jw-primary: <?php echo $primaryColor; ?>;
        --jw-primary-hover: <?php echo $primaryHover; ?>;
        --jw-primary-light: <?php echo $primaryLight; ?>;
        --jw-bg: <?php echo $bgColor; ?>;
        --jw-text: <?php echo $textColor; ?>;
        --jw-text-light: <?php echo $textLight; ?>;
        --jw-border: <?php echo $borderColor; ?>;
        --jw-bg-hover: <?php echo $hoverBg; ?>;
    }
    #jw-calendar-app .jw-calendar-sidebar { background: <?php echo $sidebarBg; ?>; }
    #jw-calendar-app .jw-calendar-main { background: <?php echo $bgColor; ?>; }
    #jw-calendar-app .fc-day-today { background: <?php echo $todayHighlight; ?> !important; }
</style>

<div id="jw-calendar-app" class="jw-calendar-wrapper">
    <?php if ($showSidebar) : ?>
    <!-- Sidebar -->
    <div class="jw-calendar-sidebar" id="calSidebar">
        <?php if ($canEdit) : ?>
        <button type="button" class="jw-btn-create" id="btnCreateEvent" title="<?php echo Text::_('COM_CALENDAR_CREATE_EVENT'); ?>">
            <svg width="36" height="36" viewBox="0 0 36 36"><path d="M28 17H19V8h-2v9H8v2h9v9h2v-9h9z" fill="currentColor"></path></svg>
            <span><?php echo Text::_('COM_CALENDAR_CREATE_EVENT'); ?></span>
        </button>
        <?php endif; ?>

        <div class="jw-mini-calendar" id="miniCalendar"></div>

        <div class="jw-sidebar-section">
            <h3 class="jw-sidebar-title">
                <span class="jw-sidebar-toggle" id="toggleCategories">&#9660;</span>
                <?php echo Text::_('COM_CALENDAR_CATEGORIES'); ?>
                <?php if ($isSuperUser): ?><button class="jw-btn-add-category" onclick="openCategoryModal()" title="Kategorie hinzufügen">+</button><?php endif; ?>
            </h3>
            <div id="categoryList" class="jw-category-list"></div>
        </div>

    </div>
    <?php endif; ?>

    <!-- Main Calendar -->
    <div class="jw-calendar-main">
        <div id="calendar"></div>
    </div>
</div>

<!-- Event Modal -->
<div class="jw-modal-overlay" id="eventModal" style="display:none;">
    <div class="jw-modal">
        <div class="jw-modal-header">
            <h2 id="modalTitle"><?php echo Text::_('COM_CALENDAR_NEW_EVENT'); ?></h2>
            <button type="button" class="jw-modal-close" id="modalClose">&times;</button>
        </div>
        <div class="jw-modal-body">
            <input type="hidden" id="eventId" value="">

            <div class="jw-form-group">
                <input type="text" id="eventTitle" class="jw-input jw-input-title" placeholder="<?php echo Text::_('COM_CALENDAR_PLACEHOLDER_TITLE'); ?>" autocomplete="off">
            </div>

            <div class="jw-form-row">
                <div class="jw-form-group jw-form-half">
                    <label for="eventStart"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?php echo Text::_('COM_CALENDAR_FIELD_START_DATE'); ?></label>
                    <input type="datetime-local" id="eventStart" class="jw-input">
                </div>
                <div class="jw-form-group jw-form-half">
                    <label for="eventEnd"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?php echo Text::_('COM_CALENDAR_FIELD_END_DATE'); ?></label>
                    <input type="datetime-local" id="eventEnd" class="jw-input">
                </div>
            </div>

            <div class="jw-form-group">
                <label class="jw-checkbox-label">
                    <input type="checkbox" id="eventAllDay">
                    <span><?php echo Text::_('COM_CALENDAR_FIELD_ALL_DAY'); ?></span>
                </label>
            </div>

            <div class="jw-form-group">
                <label for="eventDescription"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg> <?php echo Text::_('JGLOBAL_DESCRIPTION'); ?></label>
                <textarea id="eventDescription" class="jw-input jw-textarea" rows="3" placeholder="<?php echo Text::_('COM_CALENDAR_PLACEHOLDER_DESCRIPTION'); ?>"></textarea>
            </div>

            <div class="jw-form-group">
                <label for="eventCategory"><?php echo Text::_('COM_CALENDAR_FIELD_CATEGORY'); ?></label>
                <select id="eventCategory" class="jw-input"></select>
            </div>

            <div class="jw-form-group">
                <label for="eventRecurrence"><?php echo Text::_('COM_CALENDAR_FIELD_RECURRENCE'); ?></label>
                <select id="eventRecurrence" class="jw-input"></select>
            </div>

            <div class="jw-form-group" id="customIntervalGroup" style="display:none">
                <label for="eventCustomInterval"><?php echo $localeShort === 'de' ? 'Alle' : 'Every'; ?></label>
                <div class="jw-form-row">
                    <input type="number" id="eventCustomInterval" class="jw-input jw-form-half" min="1" max="30" value="1" style="width:70px">
                    <select id="eventCustomUnit" class="jw-input jw-form-half">
                        <option value="days"><?php echo $localeShort === 'de' ? 'Tage' : 'days'; ?></option>
                        <option value="weeks"><?php echo $localeShort === 'de' ? 'Wochen' : 'weeks'; ?></option>
                        <option value="months"><?php echo $localeShort === 'de' ? 'Monate' : 'months'; ?></option>
                        <option value="years"><?php echo $localeShort === 'de' ? 'Jahre' : 'years'; ?></option>
                    </select>
                </div>
            </div>

            <div class="jw-form-group" id="recurrenceEndGroup" style="display:none">
                <label><?php echo $localeShort === 'de' ? 'Endet' : 'Ends'; ?></label>
                <div class="jw-form-row">
                    <select id="eventRecurrenceEndType" class="jw-input" style="width:auto">
                        <option value="never"><?php echo $localeShort === 'de' ? 'Nie' : 'Never'; ?></option>
                        <option value="on"><?php echo $localeShort === 'de' ? 'Am' : 'On'; ?></option>
                    </select>
                    <input type="date" id="eventRecurrenceEnd" class="jw-input" style="display:none">
                </div>
            </div>

        </div>
        <div class="jw-modal-footer">
            <button type="button" class="jw-btn jw-btn-delete" id="btnDeleteEvent" style="display:none;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                <?php echo Text::_('COM_CALENDAR_DELETE'); ?>
            </button>
            <div class="jw-btn-group">
                <button type="button" class="jw-btn jw-btn-cancel" id="btnCancel"><?php echo Text::_('JCANCEL'); ?></button>
                <button type="button" class="jw-btn jw-btn-save" id="btnSaveEvent"><?php echo Text::_('JSAVE'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Event Detail Popup -->
<div class="jw-popup" id="eventPopup" style="display:none;">
    <div class="jw-popup-header">
        <div class="jw-popup-color" id="popupColor"></div>
        <h3 id="popupTitle"></h3>
        <button type="button" class="jw-popup-close" id="popupClose">&times;</button>
    </div>
    <div class="jw-popup-body">
        <div id="popupTime" class="jw-popup-row"></div>
        <div id="popupDescription" class="jw-popup-row" style="display:none;"></div>
        <div id="popupCategory" class="jw-popup-row"></div>
    </div>
    <div class="jw-popup-footer" id="popupActions" style="display:none;">
        <button type="button" class="jw-btn jw-btn-sm" id="popupEdit">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <?php echo Text::_('COM_CALENDAR_EDIT'); ?>
        </button>
        <button type="button" class="jw-btn jw-btn-sm jw-btn-delete" id="popupDelete">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
            <?php echo Text::_('COM_CALENDAR_DELETE'); ?>
        </button>
    </div>
</div>

<!-- Category Modal -->
<?php if ($isSuperUser): ?>
<div id="categoryModal" class="jw-modal-overlay" style="display:none" onclick="if(event.target===this)closeCategoryModal()">
    <div class="jw-modal jw-modal-sm">
        <div class="jw-modal-header">
            <h2 id="categoryModalTitle">Kategorie hinzufügen</h2>
            <button class="jw-modal-close" onclick="closeCategoryModal()">&times;</button>
        </div>
        <div class="jw-modal-body">
            <input type="hidden" id="catEditId" value="0">
            <div class="jw-form-group">
                <label>Bezeichnung</label>
                <input type="text" id="catEditTitle" class="jw-input" placeholder="Kategorie-Name">
            </div>
            <div class="jw-form-group">
                <label>Farbe</label>
                <div id="catColorPalette" class="jw-cat-color-palette"></div>
                <input type="hidden" id="catEditColor" value="#3788d8">
            </div>
            <div class="jw-form-group">
                <label>Beschreibung</label>
                <textarea id="catEditDesc" class="jw-input jw-textarea" rows="2" placeholder="Optional"></textarea>
            </div>
        </div>
        <div class="jw-modal-footer">
            <div>
                <button id="catDeleteBtn" class="jw-btn jw-btn-delete" style="display:none" onclick="deleteCategory(document.getElementById('catEditId').value, document.getElementById('catEditTitle').value)">Löschen</button>
            </div>
            <div class="jw-btn-group">
                <button class="jw-btn jw-btn-cancel" onclick="closeCategoryModal()">Abbrechen</button>
                <button class="jw-btn jw-btn-save" onclick="saveCategory()">Speichern</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    const BASE_URL = '<?php echo $baseUrl; ?>';
    const TOKEN_NAME = '<?php echo $token; ?>';
    const CAN_EDIT = <?php echo $canEdit ? 'true' : 'false'; ?>;
    const USER_ID = <?php echo (int) $user->id; ?>;
    const IS_SUPERUSER = <?php echo $isSuperUser ? 'true' : 'false'; ?>;
    let CATEGORIES = <?php echo $categories; ?>;
    const LOCALE = '<?php echo $localeShort; ?>';
    const DEFAULT_COLOR = '<?php echo $defaultEvtColor; ?>';

    // Category color palette
    const CAT_COLORS = ['#7986cb','#33b679','#8e24aa','#e67c73','#f6bf26','#f4511e','#039be5','#616161','#3f51b5','#0b8043','#d50000','#f09300','#009688','#795548','#c2185b','#607d8b'];

    let calendar, miniCalendar;
    let activeCategories = {};
    let currentPopupEvent = null;

    // Init categories
    function initCategories() {
        const list = document.getElementById('categoryList');
        const select = document.getElementById('eventCategory');
        list.innerHTML = '';
        select.innerHTML = '<option value="0"><?php echo Text::_('COM_CALENDAR_NO_CATEGORY'); ?></option>';

        // Preserve existing active states
        const prevActive = Object.assign({}, activeCategories);

        CATEGORIES.forEach(cat => {
            // Keep previous state if it exists, otherwise default to true
            if (prevActive.hasOwnProperty(cat.id)) {
                activeCategories[cat.id] = prevActive[cat.id];
            } else {
                activeCategories[cat.id] = true;
            }

            // Sidebar category item
            const item = document.createElement('div');
            item.className = 'jw-category-item';

            const label = document.createElement('label');
            label.className = 'jw-category-label';
            label.innerHTML = `
                <input type="checkbox" ${activeCategories[cat.id] ? 'checked' : ''} data-cat-id="${cat.id}">
                <span class="jw-category-color" style="background:${cat.color}"></span>
                <span>${cat.title}</span>
            `;
            label.querySelector('input').addEventListener('change', function() {
                activeCategories[cat.id] = this.checked;
                calendar.refetchEvents();
            });
            item.appendChild(label);

            <?php if ($isSuperUser): ?>
            const actions = document.createElement('span');
            actions.className = 'jw-category-actions';
            const editBtn = document.createElement('button');
            editBtn.className = 'jw-cat-action-btn';
            editBtn.title = 'Bearbeiten';
            editBtn.innerHTML = '&#9998;';
            editBtn.addEventListener('click', function(e) { e.stopPropagation(); openCategoryModal(cat); });
            const delBtn = document.createElement('button');
            delBtn.className = 'jw-cat-action-btn jw-cat-delete';
            delBtn.title = 'Löschen';
            delBtn.innerHTML = '&#128465;';
            delBtn.addEventListener('click', function(e) { e.stopPropagation(); deleteCategory(cat.id, cat.title); });
            actions.appendChild(editBtn);
            actions.appendChild(delBtn);
            item.appendChild(actions);
            <?php endif; ?>

            list.appendChild(item);

            // Select option
            const opt = document.createElement('option');
            opt.value = cat.id;
            opt.textContent = cat.title;
            select.appendChild(opt);
        });
    }

    // Category color palette
    function initCatColorPalette() {
        const palette = document.getElementById('catColorPalette');
        if (!palette) return;
        palette.innerHTML = '';
        CAT_COLORS.forEach(c => {
            const el = document.createElement('div');
            el.className = 'jw-cat-color-option' + (c === document.getElementById('catEditColor').value ? ' active' : '');
            el.style.background = c;
            el.innerHTML = '&#10003;';
            el.onclick = () => {
                document.getElementById('catEditColor').value = c;
                palette.querySelectorAll('.jw-cat-color-option').forEach(x => x.classList.remove('active'));
                el.classList.add('active');
            };
            palette.appendChild(el);
        });
    }

    // Category modal functions (exposed globally)
    window.openCategoryModal = function(cat) {
        if (!IS_SUPERUSER) return;
        const isEdit = cat && cat.id;
        document.getElementById('categoryModalTitle').textContent = isEdit ? 'Kategorie bearbeiten' : 'Kategorie hinzufügen';
        document.getElementById('catEditId').value = isEdit ? cat.id : 0;
        document.getElementById('catEditTitle').value = isEdit ? cat.title : '';
        document.getElementById('catEditColor').value = isEdit ? cat.color : '#3788d8';
        document.getElementById('catEditDesc').value = isEdit ? (cat.description || '') : '';
        document.getElementById('catDeleteBtn').style.display = isEdit ? '' : 'none';
        initCatColorPalette();
        document.getElementById('categoryModal').style.display = '';
    };

    window.closeCategoryModal = function() {
        document.getElementById('categoryModal').style.display = 'none';
    };

    window.saveCategory = function() {
        const title = document.getElementById('catEditTitle').value.trim();
        if (!title) { document.getElementById('catEditTitle').classList.add('jw-input-error'); return; }
        document.getElementById('catEditTitle').classList.remove('jw-input-error');
        const params = new URLSearchParams({
            id: document.getElementById('catEditId').value,
            title: title,
            color: document.getElementById('catEditColor').value,
            description: document.getElementById('catEditDesc').value
        });
        params.append(TOKEN_NAME, '1');
        fetch(BASE_URL + 'saveCategory', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params })
            .then(r => r.json())
            .then(d => { if (d.success) { closeCategoryModal(); reloadCategories(); } else { alert(d.message); } })
            .catch(e => alert('Error: ' + e));
    };

    window.deleteCategory = function(id, title) {
        if (!confirm('Kategorie "' + title + '" wirklich löschen? Events werden keiner Kategorie mehr zugeordnet.')) return;
        const params = new URLSearchParams({ id: id });
        params.append(TOKEN_NAME, '1');
        fetch(BASE_URL + 'deleteCategory', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params })
            .then(r => r.json())
            .then(d => { if (d.success) { closeCategoryModal(); reloadCategories(); } else { alert(d.message); } })
            .catch(e => alert('Error: ' + e));
    };

    function reloadCategories() {
        fetch(BASE_URL + 'getCategories')
            .then(r => r.json())
            .then(cats => { CATEGORIES = cats; initCategories(); if (typeof calendar !== 'undefined') calendar.refetchEvents(); })
            .catch(e => console.error(e));
    }

    // Format date for display
    function formatDateRange(start, end, allDay) {
        const opts = allDay
            ? { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
            : { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        let str = start.toLocaleDateString(LOCALE, opts);
        if (end && end.getTime() !== start.getTime()) {
            const endStr = end.toLocaleDateString(LOCALE, opts);
            if (start.toDateString() === end.toDateString() && !allDay) {
                str += ' – ' + end.toLocaleTimeString(LOCALE, { hour: '2-digit', minute: '2-digit' });
            } else {
                str += ' – ' + endStr;
            }
        }
        return str;
    }

    // Format datetime-local or date input value
    function toLocalInput(date, allDay) {
        if (!date) return '';
        const d = new Date(date);
        const pad = n => String(n).padStart(2, '0');
        if (allDay) return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate());
        return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    // Format for MySQL
    function toMySQLDate(val) {
        if (!val) return '';
        const d = new Date(val);
        const pad = n => String(n).padStart(2, '0');
        return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':00';
    }

    // Toggle time inputs based on all-day checkbox
    function updateTimeInputs() {
        const allDay = document.getElementById('eventAllDay').checked;
        document.getElementById('eventStart').type = allDay ? 'date' : 'datetime-local';
        document.getElementById('eventEnd').type = allDay ? 'date' : 'datetime-local';
    }

    // Show/hide custom interval based on recurrence type + update unit options
    function toggleRecurrenceEnd() {
        const recType = document.getElementById('eventRecurrence').value;
        const customGroup = document.getElementById('customIntervalGroup');
        const endGroup = document.getElementById('recurrenceEndGroup');
        customGroup.style.display = recType === 'custom' ? '' : 'none';
        endGroup.style.display = recType !== 'none' ? '' : 'none';
        if (recType === 'custom') updateCustomUnits();
    }

    function toggleRecurrenceEndDate() {
        const endType = document.getElementById('eventRecurrenceEndType').value;
        document.getElementById('eventRecurrenceEnd').style.display = endType === 'on' ? '' : 'none';
    }

    function updateCustomUnits() {
        const startVal = document.getElementById('eventStart').value;
        const endVal = document.getElementById('eventEnd').value;
        const isMultiDay = startVal && endVal && startVal.substring(0, 10) !== endVal.substring(0, 10);
        const unit = document.getElementById('eventCustomUnit');
        const daysOpt = unit.querySelector('option[value="days"]');
        if (isMultiDay) {
            if (daysOpt) daysOpt.remove();
            if (unit.value === 'days') unit.value = 'weeks';
        } else if (!daysOpt) {
            const opt = document.createElement('option');
            opt.value = 'days';
            opt.textContent = LOCALE === 'de' ? 'Tage' : 'days';
            unit.insertBefore(opt, unit.firstChild);
        }
    }

    function updateRecurrenceOptions() {
        const startVal = document.getElementById('eventStart').value;
        if (!startVal) return;
        const endVal = document.getElementById('eventEnd').value;
        const isMultiDay = startVal && endVal && startVal.substring(0, 10) !== endVal.substring(0, 10);
        const sel = document.getElementById('eventRecurrence');
        const currentVal = sel.value;
        const dt = new Date(startVal);
        const loc = LOCALE === 'de' ? 'de-DE' : 'en-GB';
        const dayName = dt.toLocaleDateString(loc, {weekday: 'long'});
        const monthDay = dt.getDate();
        const monthName = dt.toLocaleDateString(loc, {month: 'long'});
        const weekOfMonth = Math.ceil(monthDay / 7);
        const lastDay = new Date(dt.getFullYear(), dt.getMonth() + 1, 0).getDate();
        const isLast = (monthDay + 7) > lastDay;
        const ordIdx = isLast ? 4 : weekOfMonth - 1;
        const ordinals_de = ['ersten', 'zweiten', 'dritten', 'vierten', 'letzten'];
        const ordinals_en = ['first', 'second', 'third', 'fourth', 'last'];
        const ordinal = LOCALE === 'de' ? ordinals_de[ordIdx] : ordinals_en[ordIdx];

        const options = [
            {value:'none', text: LOCALE==='de' ? 'Wird nicht wiederholt' : 'Does not repeat'},
        ];
        if (!isMultiDay) {
            options.push({value:'daily', text: LOCALE==='de' ? 'Täglich' : 'Daily'});
        }
        options.push(
            {value:'weekly', text: LOCALE==='de' ? 'Wöchentlich am '+dayName : 'Weekly on '+dayName},
            {value:'monthly', text: LOCALE==='de' ? 'Monatlich am '+ordinal+' '+dayName : 'Monthly on the '+ordinal+' '+dayName},
            {value:'yearly', text: LOCALE==='de' ? 'Jährlich am '+monthDay+'. '+monthName : 'Yearly on '+monthName+' '+monthDay},
            {value:'custom', text: LOCALE==='de' ? 'Benutzerdefiniert...' : 'Custom...'},
        );

        sel.innerHTML = '';
        options.forEach(o => {
            const opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            sel.appendChild(opt);
        });
        if ([...sel.options].some(o => o.value === currentVal)) {
            sel.value = currentVal;
        } else if (currentVal === 'daily' && isMultiDay) {
            sel.value = 'none';
        }
    }

    // Hide popup
    function hidePopup() {
        document.getElementById('eventPopup').style.display = 'none';
        currentPopupEvent = null;
    }

    // Show event popup
    function showEventPopup(info) {
        hidePopup();
        const event = info.event;
        const props = event.extendedProps || {};
        currentPopupEvent = event;

        document.getElementById('popupColor').style.background = event.backgroundColor || DEFAULT_COLOR;
        document.getElementById('popupTitle').textContent = event.title;
        document.getElementById('popupTime').textContent = formatDateRange(event.start, event.end || event.start, event.allDay);

        const descEl = document.getElementById('popupDescription');
        if (props.description) {
            descEl.textContent = props.description;
            descEl.style.display = '';
        } else {
            descEl.style.display = 'none';
        }

        document.getElementById('popupCategory').textContent = props.category_title || '';

        // Show edit/delete if user can edit
        const actionsEl = document.getElementById('popupActions');
        if (CAN_EDIT && props.editable) {
            actionsEl.style.display = '';
        } else {
            actionsEl.style.display = 'none';
        }

        // Position popup near click
        const popup = document.getElementById('eventPopup');
        popup.style.display = 'block';

        const rect = info.el.getBoundingClientRect();
        const popupRect = popup.getBoundingClientRect();

        let top = rect.top + window.scrollY;
        let left = rect.right + 10;

        if (left + popupRect.width > window.innerWidth) {
            left = rect.left - popupRect.width - 10;
        }
        if (top + popupRect.height > window.innerHeight + window.scrollY) {
            top = window.innerHeight + window.scrollY - popupRect.height - 20;
        }

        popup.style.top = Math.max(10, top) + 'px';
        popup.style.left = Math.max(10, left) + 'px';
    }

    // Open event modal for editing
    function openEditModal(event) {
        hidePopup();
        const props = event.extendedProps || {};

        document.getElementById('modalTitle').textContent = '<?php echo Text::_('COM_CALENDAR_EDIT_EVENT'); ?>';
        document.getElementById('eventId').value = String(event.id).replace(/_r\d+$/, '');
        document.getElementById('eventTitle').value = event.title;
        document.getElementById('eventAllDay').checked = event.allDay;
        updateTimeInputs();
        document.getElementById('eventStart').value = toLocalInput(event.start, event.allDay);
        let editEnd = event.end || event.start;
        if (event.allDay && event.end) {
            editEnd = new Date(event.end);
            editEnd.setDate(editEnd.getDate() - 1);
        }
        document.getElementById('eventEnd').value = toLocalInput(editEnd, event.allDay);
        updateRecurrenceOptions();
        document.getElementById('eventDescription').value = props.description || '';
        document.getElementById('eventCategory').value = props.category_id || 0;
        if (props.recurrence_type === 'custom' || (props.recurrence_type === 'daily' && props.recurrence_interval > 1)) {
            document.getElementById('eventRecurrence').value = 'custom';
            document.getElementById('eventCustomInterval').value = props.recurrence_interval || 1;
            document.getElementById('eventCustomUnit').value = 'days';
        } else if (props.recurrence_type === 'weekly' && props.recurrence_interval > 1) {
            document.getElementById('eventRecurrence').value = 'custom';
            document.getElementById('eventCustomInterval').value = props.recurrence_interval;
            document.getElementById('eventCustomUnit').value = 'weeks';
        } else if (props.recurrence_type === 'monthly' && props.recurrence_interval > 1) {
            document.getElementById('eventRecurrence').value = 'custom';
            document.getElementById('eventCustomInterval').value = props.recurrence_interval;
            document.getElementById('eventCustomUnit').value = 'months';
        } else if (props.recurrence_type === 'yearly' && props.recurrence_interval > 1) {
            document.getElementById('eventRecurrence').value = 'custom';
            document.getElementById('eventCustomInterval').value = props.recurrence_interval;
            document.getElementById('eventCustomUnit').value = 'years';
        } else {
            document.getElementById('eventRecurrence').value = props.recurrence_type || 'none';
        }
        toggleRecurrenceEnd();
        // Load recurrence end date
        if (props.recurrence_end && props.recurrence_type && props.recurrence_type !== 'none') {
            const reDate = props.recurrence_end.substring(0, 10);
            const farFuture = (new Date().getFullYear() + 9) + '-12-31';
            if (reDate > farFuture) {
                document.getElementById('eventRecurrenceEndType').value = 'never';
                document.getElementById('eventRecurrenceEnd').style.display = 'none';
            } else {
                document.getElementById('eventRecurrenceEndType').value = 'on';
                document.getElementById('eventRecurrenceEnd').value = reDate;
                document.getElementById('eventRecurrenceEnd').style.display = '';
            }
        } else {
            document.getElementById('eventRecurrenceEndType').value = 'never';
            document.getElementById('eventRecurrenceEnd').value = '';
            document.getElementById('eventRecurrenceEnd').style.display = 'none';
        }
        document.getElementById('btnDeleteEvent').style.display = '';

        document.getElementById('eventModal').style.display = 'flex';
        document.getElementById('eventTitle').focus();
    }

    // Open modal for new event
    function openNewModal(start, end, allDay) {
        hidePopup();
        document.getElementById('modalTitle').textContent = '<?php echo Text::_('COM_CALENDAR_NEW_EVENT'); ?>';
        document.getElementById('eventId').value = '';
        document.getElementById('eventTitle').value = '';
        document.getElementById('eventAllDay').checked = allDay || false;
        updateTimeInputs();
        document.getElementById('eventStart').value = toLocalInput(start || new Date(), allDay);
        let endDate = end || new Date(Date.now() + 3600000);
        if (allDay && end) {
            endDate = new Date(end);
            endDate.setDate(endDate.getDate() - 1);
        }
        document.getElementById('eventEnd').value = toLocalInput(endDate, allDay);
        updateRecurrenceOptions();
        document.getElementById('eventDescription').value = '';
        document.getElementById('eventCategory').value = '0';
        document.getElementById('eventRecurrence').value = 'none';
        document.getElementById('customIntervalGroup').style.display = 'none';
        document.getElementById('recurrenceEndGroup').style.display = 'none';
        document.getElementById('eventRecurrenceEndType').value = 'never';
        document.getElementById('eventRecurrenceEnd').value = '';
        document.getElementById('eventRecurrenceEnd').style.display = 'none';
        document.getElementById('eventCustomInterval').value = '1';
        document.getElementById('eventCustomUnit').value = 'days';
        document.getElementById('btnDeleteEvent').style.display = 'none';

        document.getElementById('eventModal').style.display = 'flex';
        document.getElementById('eventTitle').focus();
    }

    function closeModal() {
        document.getElementById('eventModal').style.display = 'none';
    }

    // Save event via AJAX
    async function saveEvent() {
        const title = document.getElementById('eventTitle').value.trim();
        if (!title) {
            document.getElementById('eventTitle').classList.add('jw-input-error');
            return;
        }
        document.getElementById('eventTitle').classList.remove('jw-input-error');

        const startVal = document.getElementById('eventStart').value;
        const endVal = document.getElementById('eventEnd').value;
        const allDay = document.getElementById('eventAllDay').checked;
        const recType = document.getElementById('eventRecurrence').value;

        if (!startVal) { alert('Start-Datum fehlt'); return; }
        if (!endVal) { alert('End-Datum fehlt'); return; }

        const catVal = document.getElementById('eventCategory').value;
        if (!catVal || catVal === '0') {
            alert(LOCALE === 'de' ? 'Bitte eine Kategorie auswählen' : 'Please select a category');
            document.getElementById('eventCategory').focus();
            return;
        }

        const params = new URLSearchParams();
        params.append('id', document.getElementById('eventId').value);
        params.append('title', title);
        params.append('description', document.getElementById('eventDescription').value);
        params.append('all_day', allDay ? 1 : 0);
        params.append('category_id', catVal);
        let saveRecType = recType;
        let saveInterval = 1;
        if (recType === 'custom') {
            const unit = document.getElementById('eventCustomUnit').value;
            saveRecType = unit === 'days' ? 'daily' : unit === 'weeks' ? 'weekly' : unit === 'months' ? 'monthly' : 'yearly';
            saveInterval = parseInt(document.getElementById('eventCustomInterval').value) || 1;
        }
        params.append('recurrence_type', saveRecType);
        params.append('recurrence_interval', saveInterval);

        params.append('start_date', toMySQLDate(startVal));
        params.append('end_date', toMySQLDate(endVal));
        if (recType !== 'none') {
            const endType = document.getElementById('eventRecurrenceEndType').value;
            if (endType === 'on') {
                const recurEnd = document.getElementById('eventRecurrenceEnd').value;
                params.append('recurrence_end', recurEnd ? recurEnd + ' 23:59:00' : (new Date().getFullYear() + 10) + '-12-31 23:59:00');
            } else {
                params.append('recurrence_end', (new Date().getFullYear() + 10) + '-12-31 23:59:00');
            }
        } else {
            params.append('recurrence_end', '');
        }
        params.append(TOKEN_NAME, 1);

        console.log('[JWCalendar] saveEvent data:', params.toString());
        try {
            const resp = await fetch(BASE_URL + 'saveEvent', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params });
            const text = await resp.text();
            console.log('[JWCalendar] saveEvent response:', resp.status, text);
            let data;
            try { data = JSON.parse(text); } catch(pe) { alert('Server-Fehler: ' + text.substring(0, 200)); return; }
            if (data.success) {
                closeModal();
                calendar.refetchEvents();
            } else {
                alert(data.message || 'Error saving event');
            }
        } catch(e) {
            alert('Error: ' + e.message);
        }
    }

    // Delete event via AJAX
    async function deleteEvent(id) {
        if (!confirm('<?php echo Text::_('COM_CALENDAR_CONFIRM_DELETE'); ?>')) return;

        const cleanId = String(id).replace(/_r\d+$/, '');
        const params = new URLSearchParams();
        params.append('id', cleanId);
        params.append(TOKEN_NAME, 1);

        try {
            const resp = await fetch(BASE_URL + 'deleteEvent', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params });
            const text = await resp.text();
            console.log('[JWCalendar] deleteEvent response:', resp.status, text);
            let data;
            try { data = JSON.parse(text); } catch(pe) { alert('Server-Fehler: ' + text.substring(0, 200)); return; }
            if (data.success) {
                hidePopup();
                closeModal();
                calendar.refetchEvents();
            } else {
                alert(data.message || 'Error');
            }
        } catch(e) {
            alert('Error: ' + e.message);
        }
    }

    // Init FullCalendar
    const calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        locale: LOCALE,
        initialView: '<?php echo $defaultView; ?>',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        buttonText: {
            today: LOCALE === 'de' ? 'Heute' : 'Today',
            month: LOCALE === 'de' ? 'Monat' : 'Month',
            week: LOCALE === 'de' ? 'Woche' : 'Week',
            day: LOCALE === 'de' ? 'Tag' : 'Day',
            list: LOCALE === 'de' ? 'Liste' : 'List'
        },
        height: 'auto',
        contentHeight: 'auto',
        expandRows: true,
        stickyHeaderDates: true,
        dayMaxEvents: 3,
        navLinks: true,
        selectable: CAN_EDIT,
        selectMirror: true,
        editable: CAN_EDIT,
        eventResizableFromStart: true,
        nowIndicator: true,
        weekNumbers: <?php echo $showWeekNumbers ? 'true' : 'false'; ?>,
        weekNumberCalculation: 'ISO',
        firstDay: <?php echo $firstDay; ?>,
        businessHours: {
            daysOfWeek: [1, 2, 3, 4, 5],
            startTime: '<?php echo $bhStart; ?>',
            endTime: '<?php echo $bhEnd; ?>'
        },

        events: function(info, successCallback, failureCallback) {
            const url = BASE_URL + 'getEvents&start=' +
                info.startStr + '&end=' + info.endStr;

            fetch(url)
                .then(resp => resp.json())
                .then(data => {
                    // Filter by active categories
                    const filtered = data.filter(evt => {
                        const catId = evt.extendedProps?.category_id || 0;
                        return catId === 0 || activeCategories[catId] !== false;
                    });
                    successCallback(filtered);
                })
                .catch(err => failureCallback(err));
        },

        select: function(info) {
            if (CAN_EDIT) {
                openNewModal(info.start, info.end, info.allDay);
            }
            calendar.unselect();
        },

        eventClick: function(info) {
            info.jsEvent.preventDefault();
            showEventPopup(info);
        },

        eventDrop: function(info) {
            if (!CAN_EDIT) { info.revert(); return; }

            const props = info.event.extendedProps || {};
            if (!props.editable) { info.revert(); return; }

            const params = new URLSearchParams();
            params.append('id', String(info.event.id).replace(/_r\d+$/, ''));
            params.append('start_date', toMySQLDate(info.event.start));
            params.append('end_date', toMySQLDate(info.event.end || info.event.start));
            params.append('all_day', info.event.allDay ? 1 : 0);
            params.append(TOKEN_NAME, 1);

            fetch(BASE_URL + 'moveEvent', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params })
                .then(r => r.json())
                .then(d => { if (!d.success) info.revert(); })
                .catch(() => info.revert());
        },

        eventResize: function(info) {
            if (!CAN_EDIT) { info.revert(); return; }

            const props = info.event.extendedProps || {};
            if (!props.editable) { info.revert(); return; }

            const params = new URLSearchParams();
            params.append('id', String(info.event.id).replace(/_r\d+$/, ''));
            params.append('start_date', toMySQLDate(info.event.start));
            params.append('end_date', toMySQLDate(info.event.end || info.event.start));
            params.append(TOKEN_NAME, 1);

            fetch(BASE_URL + 'moveEvent', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params })
                .then(r => r.json())
                .then(d => { if (!d.success) info.revert(); })
                .catch(() => info.revert());
        },

        eventDidMount: function(info) {
            // Add tooltip
            info.el.title = info.event.title;
        },

        datesSet: function(info) {
            if (miniCalendar) {
                miniCalendar.gotoDate(info.start);
            }
        }
    });

    calendar.render();

    // Init mini calendar in sidebar
    const miniEl = document.getElementById('miniCalendar');
    miniCalendar = new FullCalendar.Calendar(miniEl, {
        locale: LOCALE,
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev',
            center: 'title',
            right: 'next'
        },
        height: 'auto',
        fixedWeekCount: false,
        firstDay: <?php echo $firstDay; ?>,
        dayHeaderFormat: { weekday: 'narrow' },
        dateClick: function(info) {
            calendar.gotoDate(info.date);
            calendar.changeView('timeGridDay');
        }
    });
    miniCalendar.render();

    // Event handlers
    initCategories();

    if (document.getElementById('btnCreateEvent')) {
        document.getElementById('btnCreateEvent').addEventListener('click', () => openNewModal());
    }
    document.getElementById('modalClose').addEventListener('click', closeModal);
    document.getElementById('btnCancel').addEventListener('click', closeModal);
    document.getElementById('btnSaveEvent').addEventListener('click', saveEvent);
    document.getElementById('btnDeleteEvent').addEventListener('click', () => {
        deleteEvent(document.getElementById('eventId').value);
    });
    document.getElementById('eventAllDay').addEventListener('change', updateTimeInputs);
    document.getElementById('eventStart').addEventListener('change', function() { updateRecurrenceOptions(); if (document.getElementById('eventRecurrence').value === 'custom') updateCustomUnits(); });
    document.getElementById('eventEnd').addEventListener('change', function() { updateRecurrenceOptions(); if (document.getElementById('eventRecurrence').value === 'custom') updateCustomUnits(); });
    document.getElementById('eventRecurrence').addEventListener('change', toggleRecurrenceEnd);
    document.getElementById('eventRecurrenceEndType').addEventListener('change', toggleRecurrenceEndDate);
    document.getElementById('popupClose').addEventListener('click', hidePopup);
    document.getElementById('popupEdit').addEventListener('click', () => {
        if (currentPopupEvent) openEditModal(currentPopupEvent);
    });
    document.getElementById('popupDelete').addEventListener('click', () => {
        if (currentPopupEvent) deleteEvent(currentPopupEvent.id);
    });

    // Close popup on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.jw-popup') && !e.target.closest('.fc-event') && !e.target.closest('.fc-daygrid-event')) {
            hidePopup();
        }
    });

    // Close modal on overlay click
    document.getElementById('eventModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
            closeCategoryModal();
            hidePopup();
        }
    });

    // Toggle sidebar on mobile
    const sidebar = document.getElementById('calSidebar');
    if (window.innerWidth < 768) {
        sidebar.classList.add('collapsed');
    }

    // Sidebar toggle categories
    document.getElementById('toggleCategories').addEventListener('click', function() {
        this.parentElement.nextElementSibling.classList.toggle('collapsed');
        this.textContent = this.textContent === '\u25BC' ? '\u25B6' : '\u25BC';
    });
});
</script>
