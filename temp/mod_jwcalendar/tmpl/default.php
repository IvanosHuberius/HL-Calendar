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

/** @var \Joomla\Registry\Registry $params */

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('mod_jwcalendar.fc', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css');
$wa->registerAndUseScript('mod_jwcalendar.fc', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js', [], ['defer' => false]);
$wa->registerAndUseScript('mod_jwcalendar.fclocales', 'https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales-all.global.min.js', [], ['defer' => false], ['mod_jwcalendar.fc']);
$cssVer = @filemtime(JPATH_ROOT . '/media/com_calendar/css/calendar.css') ?: '1';
$wa->registerAndUseStyle('mod_jwcalendar.style', 'media/com_calendar/css/calendar.css', ['version' => (string) $cssVer]);

$baseUrl = Uri::root() . 'index.php?option=com_calendar&task=api.';
$token = Session::getFormToken();
$user = Factory::getApplication()->getIdentity();
$canEdit = $canEdit ?? false;
$categories = $categories ?? [];
$categoriesJson = json_encode($categories);
$locale = Factory::getApplication()->getLanguage()->getTag();
$localeShort = substr($locale, 0, 2);
$fcLocale = strtolower($locale); // full BCP47 tag for FullCalendar (e.g. ka-ge → falls back to ka)
$isRtl = Factory::getApplication()->getLanguage()->isRtl();
$isSuperUser = Factory::getApplication()->getIdentity()->authorise('core.admin');

// Load component params for colors and shared settings
$cparams = ComponentHelper::getParams('com_calendar');

$defaultView = $params->get('default_view', 'dayGridMonth');
$showSidebar = (int) $params->get('show_sidebar', 1);
$calHeight = $params->get('calendar_height', 'auto');
$showWeekNumbers = (int) $cparams->get('show_week_numbers', 1);
$firstDay = (int) $params->get('first_day', 1);
$primaryColor    = $cparams->get('primary_color', '#1a73e8');
$primaryHover    = $cparams->get('primary_hover_color', '#1765cc');
$todayHighlight  = $cparams->get('today_highlight_color', '#fff8e1');
$bgColor         = $cparams->get('background_color', '#ffffff');
$textColor       = $cparams->get('text_color', '#3c4043');
$textLight       = $cparams->get('text_light_color', '#70757a');
$borderColor     = $cparams->get('border_color', '#dadce0');
$hoverBg         = $cparams->get('hover_bg_color', '#f1f3f4');
$sidebarBg       = $cparams->get('sidebar_bg_color', '#ffffff');
$defaultEvtColor = $cparams->get('default_event_color', '#3788d8');
$eventStyle      = $cparams->get('event_display_style', 'dot_medium');
$eventDisplayFc  = ($eventStyle === 'bar') ? 'block' : 'auto';

// Start date: optionally open on the next upcoming event instead of today
// (see MOD_JWCALENDAR_START_DATE_MODE). Never fatal – falls back to today.
$startMode   = $params->get('start_date_mode', 'today');
$initialDate = '';
if ($startMode !== 'today' && class_exists('\\Jewe\\Component\\Calendar\\Site\\Service\\EventService')) {
    try {
        $initialDate = (string) (new \Jewe\Component\Calendar\Site\Service\EventService())
            ->resolveStartDate($user, $startMode, $defaultView, $firstDay);
    } catch (\Throwable $e) {
        $initialDate = '';
    }
}

$pr = hexdec(substr($primaryColor, 1, 2));
$pg = hexdec(substr($primaryColor, 3, 2));
$pb = hexdec(substr($primaryColor, 5, 2));
$primaryLight = sprintf('#%02x%02x%02x', min(255, $pr + round((255 - $pr) * 0.85)), min(255, $pg + round((255 - $pg) * 0.85)), min(255, $pb + round((255 - $pb) * 0.85)));

// Unique ID for multiple module instances
$moduleId = 'jwcal_' . $module->id;
?>

<style>
    #<?php echo $moduleId; ?>_app {
        --jw-primary: <?php echo $primaryColor; ?>;
        --jw-primary-hover: <?php echo $primaryHover; ?>;
        --jw-primary-light: <?php echo $primaryLight; ?>;
        --jw-bg: <?php echo $bgColor; ?>;
        --jw-text: <?php echo $textColor; ?>;
        --jw-text-light: <?php echo $textLight; ?>;
        --jw-border: <?php echo $borderColor; ?>;
        --jw-bg-hover: <?php echo $hoverBg; ?>;
    }
    #<?php echo $moduleId; ?>_app .jw-calendar-sidebar { background: <?php echo $sidebarBg; ?>; }
    #<?php echo $moduleId; ?>_app .jw-calendar-main { background: <?php echo $bgColor; ?>; }
    #<?php echo $moduleId; ?>_app .fc-day-today { background: <?php echo $todayHighlight; ?> !important; }
</style>

<div id="<?php echo $moduleId; ?>_app" class="jw-calendar-wrapper jw-evtstyle-<?php echo $eventStyle; ?>"<?php echo $isRtl ? ' dir="rtl"' : ''; ?>>
    <?php if ($showSidebar) : ?>
    <!-- Sidebar -->
    <div class="jw-calendar-sidebar" id="<?php echo $moduleId; ?>_sidebar">
        <?php if ($canEdit) : ?>
        <button type="button" class="jw-btn-create" id="<?php echo $moduleId; ?>_btnCreate" title="<?php echo Text::_('MOD_JWCALENDAR_CREATE_EVENT'); ?>">
            <svg width="36" height="36" viewBox="0 0 36 36"><path d="M28 17H19V8h-2v9H8v2h9v9h2v-9h9z" fill="currentColor"></path></svg>
            <span><?php echo Text::_('MOD_JWCALENDAR_CREATE_EVENT'); ?></span>
        </button>
        <?php endif; ?>

        <div class="jw-mini-calendar" id="<?php echo $moduleId; ?>_mini"></div>

        <div class="jw-sidebar-section">
            <h3 class="jw-sidebar-title">
                <span class="jw-sidebar-toggle" id="<?php echo $moduleId; ?>_toggleCats">&#9660;</span>
                <?php echo Text::_('MOD_JWCALENDAR_MY_CALENDARS'); ?>
                <?php if ($isSuperUser): ?><button class="jw-btn-add-category" onclick="openCatModal()" title="<?php echo Text::_('MOD_JWCALENDAR_ADD_CATEGORY'); ?>">+</button><?php endif; ?>
            </h3>
            <div id="<?php echo $moduleId; ?>_catList" class="jw-category-list"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Calendar -->
    <div class="jw-calendar-main">
        <div id="<?php echo $moduleId; ?>_cal"></div>
    </div>
</div>

<!-- Event Modal -->
<div class="jw-modal-overlay" id="<?php echo $moduleId; ?>_modal" style="display:none;">
    <div class="jw-modal">
        <div class="jw-modal-header">
            <h2 id="<?php echo $moduleId; ?>_modalTitle"><?php echo Text::_('MOD_JWCALENDAR_NEW_EVENT'); ?></h2>
            <button type="button" class="jw-modal-close" id="<?php echo $moduleId; ?>_modalClose">&times;</button>
        </div>
        <div class="jw-modal-body">
            <input type="hidden" id="<?php echo $moduleId; ?>_evtId" value="">

            <div class="jw-form-group">
                <input type="text" id="<?php echo $moduleId; ?>_evtTitle" class="jw-input jw-input-title" placeholder="<?php echo Text::_('MOD_JWCALENDAR_PLACEHOLDER_TITLE'); ?>" autocomplete="off">
            </div>

            <div class="jw-form-row">
                <div class="jw-form-group jw-form-half">
                    <label for="<?php echo $moduleId; ?>_evtStart"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?php echo Text::_('MOD_JWCALENDAR_START'); ?></label>
                    <input type="datetime-local" id="<?php echo $moduleId; ?>_evtStart" class="jw-input">
                </div>
                <div class="jw-form-group jw-form-half">
                    <label for="<?php echo $moduleId; ?>_evtEnd"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?php echo Text::_('MOD_JWCALENDAR_END'); ?></label>
                    <input type="datetime-local" id="<?php echo $moduleId; ?>_evtEnd" class="jw-input">
                </div>
            </div>

            <div class="jw-form-group" style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="<?php echo $moduleId; ?>_evtAllDay" style="width:18px;height:18px;cursor:pointer;">
                <span><?php echo Text::_('MOD_JWCALENDAR_ALL_DAY'); ?></span>
            </div>

            <div class="jw-form-group">
                <label for="<?php echo $moduleId; ?>_evtDesc"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg> <?php echo Text::_('MOD_JWCALENDAR_DESCRIPTION'); ?></label>
                <textarea id="<?php echo $moduleId; ?>_evtDesc" class="jw-input jw-textarea" rows="3" placeholder="<?php echo Text::_('MOD_JWCALENDAR_PLACEHOLDER_DESC'); ?>"></textarea>
            </div>

            <div class="jw-form-group">
                <label for="<?php echo $moduleId; ?>_evtCat"><?php echo Text::_('MOD_JWCALENDAR_CATEGORY'); ?></label>
                <select id="<?php echo $moduleId; ?>_evtCat" class="jw-input"></select>
            </div>

            <div class="jw-form-group">
                <label for="<?php echo $moduleId; ?>_evtRecur"><?php echo Text::_('MOD_JWCALENDAR_RECURRENCE'); ?></label>
                <select id="<?php echo $moduleId; ?>_evtRecur" class="jw-input">
                    <option value="none"><?php echo Text::_('MOD_JWCALENDAR_RECURRENCE_NONE'); ?></option>
                </select>
            </div>

            <div class="jw-form-group" id="<?php echo $moduleId; ?>_customIntervalGroup" style="display:none">
                <label for="<?php echo $moduleId; ?>_evtCustomInterval"><?php echo Text::_('MOD_JWCALENDAR_RECUR_EVERY'); ?></label>
                <div class="jw-form-row">
                    <input type="number" id="<?php echo $moduleId; ?>_evtCustomInterval" class="jw-input jw-form-half" min="1" max="30" value="1" style="width:70px">
                    <select id="<?php echo $moduleId; ?>_evtCustomUnit" class="jw-input jw-form-half">
                        <option value="days"><?php echo Text::_('MOD_JWCALENDAR_UNIT_DAYS'); ?></option>
                        <option value="weeks"><?php echo Text::_('MOD_JWCALENDAR_UNIT_WEEKS'); ?></option>
                        <option value="months"><?php echo Text::_('MOD_JWCALENDAR_UNIT_MONTHS'); ?></option>
                        <option value="years"><?php echo Text::_('MOD_JWCALENDAR_UNIT_YEARS'); ?></option>
                    </select>
                </div>
            </div>

            <div class="jw-form-group" id="<?php echo $moduleId; ?>_recurEndGroup" style="display:none">
                <label><?php echo Text::_('MOD_JWCALENDAR_RECUR_ENDS'); ?></label>
                <div class="jw-form-row">
                    <select id="<?php echo $moduleId; ?>_recurEndType" class="jw-input" style="width:auto">
                        <option value="never"><?php echo Text::_('MOD_JWCALENDAR_RECUR_NEVER'); ?></option>
                        <option value="on"><?php echo Text::_('MOD_JWCALENDAR_RECUR_ON'); ?></option>
                    </select>
                    <input type="date" id="<?php echo $moduleId; ?>_recurEnd" class="jw-input" style="display:none">
                </div>
            </div>

            <div class="jw-form-group" id="<?php echo $moduleId; ?>_holidaySkipGroup" style="display:none">
                <label class="jw-checkbox-label" style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" id="<?php echo $moduleId; ?>_evtSkipHolidays" style="width:18px;height:18px;cursor:pointer;">
                    <span><?php echo Text::_('MOD_JWCALENDAR_SKIP_HOLIDAYS'); ?></span>
                </label>
            </div>

            <div class="jw-form-group" id="<?php echo $moduleId; ?>_holidayRegionGroup" style="display:none">
                <div class="jw-form-row">
                    <select id="<?php echo $moduleId; ?>_evtHolidayCountry" class="jw-input jw-form-half"></select>
                    <select id="<?php echo $moduleId; ?>_evtHolidaySubdiv" class="jw-input jw-form-half"></select>
                </div>
            </div>

            <div class="jw-form-group" id="<?php echo $moduleId; ?>_exceptionDatesGroup" style="display:none">
                <label for="<?php echo $moduleId; ?>_evtExceptionDates"><?php echo Text::_('MOD_JWCALENDAR_EXCEPTION_DATES_LABEL'); ?></label>
                <input type="text" id="<?php echo $moduleId; ?>_evtExceptionDates" class="jw-input" placeholder="2026-12-25, 2026-12-26">
            </div>

        </div>
        <div class="jw-modal-footer">
            <button type="button" class="jw-btn jw-btn-delete" id="<?php echo $moduleId; ?>_btnDel" style="display:none;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                <?php echo Text::_('MOD_JWCALENDAR_DELETE'); ?>
            </button>
            <div class="jw-btn-group">
                <button type="button" class="jw-btn jw-btn-cancel" id="<?php echo $moduleId; ?>_btnCancel"><?php echo Text::_('JCANCEL'); ?></button>
                <button type="button" class="jw-btn jw-btn-save" id="<?php echo $moduleId; ?>_btnSave"><?php echo Text::_('JSAVE'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Event Detail Popup -->
<div class="jw-popup" id="<?php echo $moduleId; ?>_popup" style="display:none;">
    <div class="jw-popup-header">
        <div class="jw-popup-color" id="<?php echo $moduleId; ?>_popColor"></div>
        <h3 id="<?php echo $moduleId; ?>_popTitle"></h3>
        <button type="button" class="jw-popup-close" id="<?php echo $moduleId; ?>_popClose">&times;</button>
    </div>
    <div class="jw-popup-body">
        <div id="<?php echo $moduleId; ?>_popTime" class="jw-popup-row"></div>
        <div id="<?php echo $moduleId; ?>_popDesc" class="jw-popup-row" style="display:none;"></div>
        <div id="<?php echo $moduleId; ?>_popCat" class="jw-popup-row"></div>
    </div>
    <div class="jw-popup-footer" id="<?php echo $moduleId; ?>_popActions" style="display:none;">
        <button type="button" class="jw-btn jw-btn-sm" id="<?php echo $moduleId; ?>_popEdit">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <?php echo Text::_('MOD_JWCALENDAR_EDIT'); ?>
        </button>
        <button type="button" class="jw-btn jw-btn-sm jw-btn-delete" id="<?php echo $moduleId; ?>_popDel">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
            <?php echo Text::_('MOD_JWCALENDAR_DELETE'); ?>
        </button>
    </div>
</div>

<?php if ($isSuperUser): ?>
<!-- Category Modal (Super User only) -->
<div class="jw-modal-overlay" id="<?php echo $moduleId; ?>_categoryModal" style="display:none;">
    <div class="jw-modal jw-modal-sm">
        <div class="jw-modal-header">
            <h2 id="<?php echo $moduleId; ?>_catModalTitle"><?php echo Text::_('MOD_JWCALENDAR_ADD_CATEGORY'); ?></h2>
            <button type="button" class="jw-modal-close" onclick="closeCatModal()">&times;</button>
        </div>
        <div class="jw-modal-body">
            <input type="hidden" id="catEditId_<?php echo $moduleId; ?>" value="">

            <div class="jw-form-group">
                <label for="catTitle_<?php echo $moduleId; ?>"><?php echo Text::_('MOD_JWCALENDAR_CATEGORY_TITLE'); ?></label>
                <input type="text" id="catTitle_<?php echo $moduleId; ?>" class="jw-input" placeholder="<?php echo Text::_('MOD_JWCALENDAR_CATEGORY_TITLE'); ?>" autocomplete="off">
            </div>

            <div class="jw-form-group">
                <label for="catDesc_<?php echo $moduleId; ?>"><?php echo Text::_('MOD_JWCALENDAR_DESCRIPTION'); ?></label>
                <textarea id="catDesc_<?php echo $moduleId; ?>" class="jw-input jw-textarea" rows="2" placeholder="<?php echo Text::_('MOD_JWCALENDAR_PLACEHOLDER_DESC'); ?>"></textarea>
            </div>

            <div class="jw-form-group">
                <label><?php echo Text::_('MOD_JWCALENDAR_COLOR'); ?></label>
                <div class="jw-cat-color-palette" id="catColorGrid_<?php echo $moduleId; ?>"></div>
                <input type="hidden" id="catColor_<?php echo $moduleId; ?>" value="#3788d8">
            </div>
        </div>
        <div class="jw-modal-footer">
            <div class="jw-btn-group">
                <button type="button" class="jw-btn jw-btn-cancel" onclick="closeCatModal()"><?php echo Text::_('JCANCEL'); ?></button>
                <button type="button" class="jw-btn jw-btn-save" onclick="saveCat()"><?php echo Text::_('JSAVE'); ?></button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    const M = '<?php echo $moduleId; ?>';
    const $ = id => document.getElementById(M + '_' + id);

    // === GLOBAL TRY-CATCH: Alle Fehler sichtbar machen ===
    try {

    const BASE = '<?php echo $baseUrl; ?>';
    const TOKEN = '<?php echo $token; ?>';
    const CAN_EDIT = <?php echo $canEdit ? 'true' : 'false'; ?>;
    const IS_SUPER = <?php echo $isSuperUser ? 'true' : 'false'; ?>;
    const UID = <?php echo (int) $user->id; ?>;
    let CATEGORIES = <?php echo $categoriesJson; ?>;
    const LOC = '<?php echo $localeShort; ?>';
    const DEFAULT_COLOR = '<?php echo $defaultEvtColor; ?>';
    const EVENT_STYLE = '<?php echo $eventStyle; ?>';
    const COMPONENT_URL = '<?php echo Uri::root(); ?>index.php?option=com_calendar&task=api.';
    const LOCALE_INTL = '<?php echo $locale; ?>';

    // Localized UI strings (from Joomla language files; English fallback for untranslated languages)
    const L = <?php echo json_encode([
        'nationwide'   => Text::_('MOD_JWCALENDAR_NATIONWIDE'),
        'recurNone'    => Text::_('MOD_JWCALENDAR_RECUR_NONE_OPT'),
        'daily'        => Text::_('MOD_JWCALENDAR_RECURRENCE_DAILY'),
        'weeklyOn'     => Text::_('MOD_JWCALENDAR_RECUR_WEEKLY_ON'),
        'monthlyOn'    => Text::_('MOD_JWCALENDAR_RECUR_MONTHLY_ON'),
        'yearlyOn'     => Text::_('MOD_JWCALENDAR_RECUR_YEARLY_ON'),
        'custom'       => Text::_('MOD_JWCALENDAR_RECUR_CUSTOM'),
        'ord1'         => Text::_('MOD_JWCALENDAR_ORDINAL_1'),
        'ord2'         => Text::_('MOD_JWCALENDAR_ORDINAL_2'),
        'ord3'         => Text::_('MOD_JWCALENDAR_ORDINAL_3'),
        'ord4'         => Text::_('MOD_JWCALENDAR_ORDINAL_4'),
        'ordLast'      => Text::_('MOD_JWCALENDAR_ORDINAL_LAST'),
        'unitDays'     => Text::_('MOD_JWCALENDAR_UNIT_DAYS'),
        'startMissing' => Text::_('MOD_JWCALENDAR_START_DATE_MISSING'),
        'endMissing'   => Text::_('MOD_JWCALENDAR_END_DATE_MISSING'),
        'serverError'  => Text::_('MOD_JWCALENDAR_SERVER_ERROR'),
        'networkError' => Text::_('MOD_JWCALENDAR_NETWORK_ERROR'),
    ], JSON_UNESCAPED_UNICODE); ?>;

    // Localized month & weekday names from Joomla (site language) — browser-INDEPENDENT.
    // FullCalendar otherwise pulls names from the browser's native Intl, which may lack the
    // site language (e.g. Georgian) and then falls back to the viewer's browser language.
    const CAL_NAMES = <?php echo json_encode([
        'months'      => array_map(fn($k) => Text::_($k), ['JANUARY','FEBRUARY','MARCH','APRIL','MAY','JUNE','JULY','AUGUST','SEPTEMBER','OCTOBER','NOVEMBER','DECEMBER']),
        'monthsShort' => array_map(fn($k) => Text::_($k), ['JANUARY_SHORT','FEBRUARY_SHORT','MARCH_SHORT','APRIL_SHORT','MAY_SHORT','JUNE_SHORT','JULY_SHORT','AUGUST_SHORT','SEPTEMBER_SHORT','OCTOBER_SHORT','NOVEMBER_SHORT','DECEMBER_SHORT']),
        'days'        => array_map(fn($k) => Text::_($k), ['SUNDAY','MONDAY','TUESDAY','WEDNESDAY','THURSDAY','FRIDAY','SATURDAY']),
        'daysShort'   => array_map(fn($k) => Text::_($k), ['SUN','MON','TUE','WED','THU','FRI','SAT']),
    ], JSON_UNESCAPED_UNICODE); ?>;

    function fcDayHeader(arg) {
        const t = arg.view.type, d = arg.date;
        // List view: keep the full date (weekday, day month year) — not just the weekday
        if (t.indexOf('list') === 0) {
            return CAL_NAMES.days[d.getDay()] + ', ' + d.getDate() + '. ' + CAL_NAMES.months[d.getMonth()] + ' ' + d.getFullYear();
        }
        const wd = CAL_NAMES.daysShort[d.getDay()];
        return (t.indexOf('timeGrid') === 0) ? (wd + ' ' + d.getDate()) : wd;
    }
    function fcDayHeaderNarrow(arg) {
        return (CAL_NAMES.daysShort[arg.date.getDay()] || '').charAt(0);
    }
    function fcFixTitle(rootEl, view) {
        const t = rootEl.querySelector('.fc-toolbar-title');
        if (!t) return;
        const s = view.currentStart;
        if (view.type === 'dayGridMonth' || view.type === 'listMonth') {
            t.textContent = CAL_NAMES.months[s.getMonth()] + ' ' + s.getFullYear();
        } else if (view.type === 'timeGridDay') {
            t.textContent = CAL_NAMES.days[s.getDay()] + ', ' + s.getDate() + '. ' + CAL_NAMES.months[s.getMonth()] + ' ' + s.getFullYear();
        } else if (view.type === 'timeGridWeek') {
            const e = new Date(view.currentEnd.getTime() - 86400000);
            t.textContent = s.getDate() + '. ' + CAL_NAMES.monthsShort[s.getMonth()] + ' – ' + e.getDate() + '. ' + CAL_NAMES.monthsShort[e.getMonth()] + ' ' + e.getFullYear();
        }
    }

    // sprintf-lite: replace each %s with the next argument
    function fmt(s) { let i = 1; const a = arguments; return String(s).replace(/%s/g, () => a[i++]); }

    // Pick a readable text color (black/white) for a given background hex
    function pickTextColor(hex) {
        hex = (hex || '').replace('#', '');
        if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
        if (hex.length < 6) return '#ffffff';
        const r = parseInt(hex.substr(0,2),16), g = parseInt(hex.substr(2,2),16), b = parseInt(hex.substr(4,2),16);
        const lum = (0.299*r + 0.587*g + 0.114*b) / 255;
        return lum > 0.6 ? '#202124' : '#ffffff';
    }

    // Countries & regions for the holiday-skip feature (ISO 3166 / 3166-2)
    const HOLIDAY_COUNTRIES = {
        'DE': { name: 'Deutschland', subs: [['DE-BW','Baden-Württemberg'],['DE-BY','Bayern'],['DE-BE','Berlin'],['DE-BB','Brandenburg'],['DE-HB','Bremen'],['DE-HH','Hamburg'],['DE-HE','Hessen'],['DE-MV','Mecklenburg-Vorpommern'],['DE-NI','Niedersachsen'],['DE-NW','Nordrhein-Westfalen'],['DE-RP','Rheinland-Pfalz'],['DE-SL','Saarland'],['DE-SN','Sachsen'],['DE-ST','Sachsen-Anhalt'],['DE-SH','Schleswig-Holstein'],['DE-TH','Thüringen']] },
        'AT': { name: 'Österreich', subs: [['AT-1','Burgenland'],['AT-2','Kärnten'],['AT-3','Niederösterreich'],['AT-4','Oberösterreich'],['AT-5','Salzburg'],['AT-6','Steiermark'],['AT-7','Tirol'],['AT-8','Vorarlberg'],['AT-9','Wien']] },
        'CH': { name: 'Schweiz', subs: [['CH-AG','Aargau'],['CH-AI','Appenzell I.Rh.'],['CH-AR','Appenzell A.Rh.'],['CH-BL','Basel-Landschaft'],['CH-BS','Basel-Stadt'],['CH-BE','Bern'],['CH-FR','Freiburg'],['CH-GE','Genf'],['CH-GL','Glarus'],['CH-GR','Graubünden'],['CH-JU','Jura'],['CH-LU','Luzern'],['CH-NE','Neuenburg'],['CH-NW','Nidwalden'],['CH-OW','Obwalden'],['CH-SG','St. Gallen'],['CH-SH','Schaffhausen'],['CH-SO','Solothurn'],['CH-SZ','Schwyz'],['CH-TG','Thurgau'],['CH-TI','Tessin'],['CH-UR','Uri'],['CH-VD','Waadt'],['CH-VS','Wallis'],['CH-ZG','Zug'],['CH-ZH','Zürich']] },
        'FR': { name: 'France', subs: [] },
        'IT': { name: 'Italia', subs: [] },
        'LU': { name: 'Luxembourg', subs: [] },
        'NL': { name: 'Nederland', subs: [] },
        'BE': { name: 'Belgique', subs: [] },
        'GB': { name: 'United Kingdom', subs: [] },
        'ES': { name: 'España', subs: [] },
        'PL': { name: 'Polska', subs: [] },
        'US': { name: 'United States', subs: [] }
    };

    function fillCountrySelect(sel) {
        sel.innerHTML = '';
        Object.keys(HOLIDAY_COUNTRIES).forEach(code => {
            const o = document.createElement('option');
            o.value = code; o.textContent = HOLIDAY_COUNTRIES[code].name;
            sel.appendChild(o);
        });
    }

    function fillSubdivSelect(sel, country, selected) {
        sel.innerHTML = '';
        const o0 = document.createElement('option');
        o0.value = ''; o0.textContent = L.nationwide;
        sel.appendChild(o0);
        const subs = (HOLIDAY_COUNTRIES[country] || {}).subs || [];
        subs.forEach(pair => {
            const o = document.createElement('option');
            o.value = pair[0]; o.textContent = pair[1];
            sel.appendChild(o);
        });
        sel.value = selected || '';
        sel.disabled = subs.length === 0;
    }

    function toggleHolidayUI() {
        const isRecur = $('evtRecur').value !== 'none';
        const skip = $('evtSkipHolidays').checked;
        $('holidaySkipGroup').style.display = isRecur ? '' : 'none';
        $('exceptionDatesGroup').style.display = isRecur ? '' : 'none';
        $('holidayRegionGroup').style.display = (isRecur && skip) ? '' : 'none';
    }

    const COLORS = [
        '#039be5','#3788d8','#33b679','#0b8043',
        '#e67c73','#d50000','#f6bf26','#f09300',
        '#7986cb','#8e24aa','#616161','#a79b8e',
        '#ad1457','#4285f4','#009688','#795548'
    ];

    let calendar, miniCal, activeCats = {}, popEvt = null;

    // Helper functions
    const pad = n => String(n).padStart(2, '0');
    const toLocal = (d, allDay) => { if (!d) return ''; const x = new Date(d); return allDay ? x.getFullYear()+'-'+pad(x.getMonth()+1)+'-'+pad(x.getDate()) : x.getFullYear()+'-'+pad(x.getMonth()+1)+'-'+pad(x.getDate())+'T'+pad(x.getHours())+':'+pad(x.getMinutes()); };
    const toMySQL = (v, allDay) => { if (!v) return ''; const d = new Date(v); return allDay ? d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+' 00:00:00' : d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+' '+pad(d.getHours())+':'+pad(d.getMinutes())+':00'; };

    function formatRange(s, e, allDay) {
        // Build from Joomla site-language names (CAL_NAMES) \u2014 browser-independent
        const pad = n => String(n).padStart(2, '0');
        const fd = d => CAL_NAMES.days[d.getDay()] + ', ' + d.getDate() + '. ' + CAL_NAMES.months[d.getMonth()] + ' ' + d.getFullYear();
        const ft = d => pad(d.getHours()) + ':' + pad(d.getMinutes());
        let str = fd(s) + (allDay ? '' : ', ' + ft(s));
        if (e && e.getTime() !== s.getTime()) {
            if (s.toDateString() === e.toDateString() && !allDay) {
                str += ' \u2013 ' + ft(e);
            } else {
                str += ' \u2013 ' + fd(e) + (allDay ? '' : ', ' + ft(e));
            }
        }
        return str;
    }

    // Init categories
    function initCats() {
        const list = $('catList');
        const sel = $('evtCat');

        // Preserve active state
        const prevActive = Object.assign({}, activeCats);

        if (list) list.innerHTML = '';
        if (sel) sel.innerHTML = '<option value="0"><?php echo Text::_('MOD_JWCALENDAR_NO_CATEGORY'); ?></option>';

        CATEGORIES.forEach(c => {
            // Preserve previous state or default to true
            if (prevActive.hasOwnProperty(c.id)) {
                activeCats[c.id] = prevActive[c.id];
            } else {
                activeCats[c.id] = true;
            }

            if (list) {
                const item = document.createElement('div');
                item.className = 'jw-category-item';

                let html = '<label class="jw-category-label">' +
                    '<input type="checkbox" '+(activeCats[c.id] ? 'checked' : '')+' data-cat-id="'+c.id+'">' +
                    '<span class="jw-category-color" style="background:'+c.color+'"></span>' +
                    '<span class="jw-category-name">'+c.title+'</span>' +
                    '</label>';

                item.innerHTML = html;

                if (IS_SUPER) {
                    const actSpan = document.createElement('span');
                    actSpan.className = 'jw-category-actions';
                    const eBtn = document.createElement('button');
                    eBtn.className = 'jw-cat-action-btn';
                    eBtn.title = '<?php echo Text::_('MOD_JWCALENDAR_EDIT'); ?>';
                    eBtn.innerHTML = '&#9997;&#65039;';
                    eBtn.addEventListener('click', function(ev) { ev.stopPropagation(); openCatModal(c); });
                    const dBtn = document.createElement('button');
                    dBtn.className = 'jw-cat-action-btn jw-cat-delete';
                    dBtn.title = '<?php echo Text::_('MOD_JWCALENDAR_DELETE'); ?>';
                    dBtn.innerHTML = '&#128465;&#65039;';
                    dBtn.addEventListener('click', function(ev) { ev.stopPropagation(); deleteCat(c.id, c.title); });
                    actSpan.appendChild(eBtn);
                    actSpan.appendChild(dBtn);
                    item.appendChild(actSpan);
                }
                item.querySelector('input').addEventListener('change', function() {
                    activeCats[c.id] = this.checked;
                    calendar.refetchEvents();
                });
                list.appendChild(item);
            }
            if (sel) {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.title;
                sel.appendChild(opt);
            }
        });
    }

    // === Category Modal Functions (Super User) ===
    if (IS_SUPER) {
        window.openCatModal = function(cat) {
            const modal = document.getElementById(M + '_categoryModal');
            const titleEl = document.getElementById(M + '_catModalTitle');
            const idEl = document.getElementById('catEditId_' + M);
            const nameEl = document.getElementById('catTitle_' + M);
            const descEl = document.getElementById('catDesc_' + M);
            const colorEl = document.getElementById('catColor_' + M);

            if (cat && cat.id) {
                titleEl.textContent = '<?php echo Text::_('MOD_JWCALENDAR_EDIT_CATEGORY'); ?>';
                idEl.value = cat.id;
                nameEl.value = cat.title || '';
                descEl.value = cat.description || '';
                colorEl.value = cat.color || '#3788d8';
            } else {
                titleEl.textContent = '<?php echo Text::_('MOD_JWCALENDAR_ADD_CATEGORY'); ?>';
                idEl.value = '';
                nameEl.value = '';
                descEl.value = '';
                colorEl.value = '#3788d8';
            }
            initCatPalette();
            modal.style.display = 'flex';
            nameEl.focus();
        };

        window.closeCatModal = function() {
            document.getElementById(M + '_categoryModal').style.display = 'none';
        };

        window.saveCat = async function() {
            const nameEl = document.getElementById('catTitle_' + M);
            const title = nameEl.value.trim();
            if (!title) { nameEl.classList.add('jw-input-error'); return; }
            nameEl.classList.remove('jw-input-error');

            const fd = new URLSearchParams();
            fd.append('id', document.getElementById('catEditId_' + M).value);
            fd.append('title', title);
            fd.append('description', document.getElementById('catDesc_' + M).value);
            fd.append('color', document.getElementById('catColor_' + M).value);
            fd.append(TOKEN, 1);

            try {
                const r = await fetch(COMPONENT_URL + 'saveCategory', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:fd});
                const d = await r.json();
                if (d.success) { closeCatModal(); reloadCats(); }
                else alert(d.message || 'Error');
            } catch(e) { alert('Error: '+e.message); }
        };

        window.deleteCat = async function(id, title) {
            if (!confirm('<?php echo Text::_('MOD_JWCALENDAR_CONFIRM_DELETE_CATEGORY'); ?>: "' + title + '"?')) return;
            const fd = new URLSearchParams();
            fd.append('id', id);
            fd.append(TOKEN, 1);
            try {
                const r = await fetch(COMPONENT_URL + 'deleteCategory', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:fd});
                const d = await r.json();
                if (d.success) { reloadCats(); }
                else alert(d.message || 'Error');
            } catch(e) { alert('Error: '+e.message); }
        };

        window.reloadCats = async function() {
            try {
                const r = await fetch(COMPONENT_URL + 'getCategories');
                const d = await r.json();
                if (d.success !== false && Array.isArray(d)) {
                    CATEGORIES = d;
                } else if (d.data && Array.isArray(d.data)) {
                    CATEGORIES = d.data;
                }
                initCats();
                calendar.refetchEvents();
            } catch(e) { console.error('Error loading categories:', e); }
        };

        window.initCatPalette = function() {
            const grid = document.getElementById('catColorGrid_' + M);
            const colorInput = document.getElementById('catColor_' + M);
            if (!grid) return;
            grid.innerHTML = '';
            COLORS.forEach(color => {
                const sw = document.createElement('div');
                sw.className = 'jw-color-swatch';
                if (colorInput.value === color) sw.classList.add('active');
                sw.style.background = color;
                sw.addEventListener('click', () => {
                    colorInput.value = color;
                    grid.querySelectorAll('.jw-color-swatch').forEach(s => s.classList.remove('active'));
                    sw.classList.add('active');
                });
                grid.appendChild(sw);
            });
        };

        // Close category modal on overlay click
        document.getElementById(M + '_categoryModal').addEventListener('click', function(e) {
            if (e.target === this) closeCatModal();
        });
    }

    // Popup
    function hidePopup() { $('popup').style.display = 'none'; popEvt = null; }

    function showPopup(info) {
        hidePopup();
        const ev = info.event, p = ev.extendedProps || {};
        popEvt = ev;

        $('popColor').style.background = ev.backgroundColor || DEFAULT_COLOR;
        $('popTitle').textContent = ev.title;
        $('popTime').textContent = formatRange(ev.start, ev.end || ev.start, ev.allDay);

        const desc = $('popDesc');
        if (p.description) { desc.textContent = p.description; desc.style.display=''; }
        else desc.style.display='none';

        $('popCat').textContent = p.category_title || '';
        $('popActions').style.display = CAN_EDIT && p.editable ? '' : 'none';

        const popup = $('popup');
        popup.style.display = 'block';
        const rect = info.el.getBoundingClientRect();
        let top = rect.top;
        let left = rect.right + 10;
        const pr = popup.getBoundingClientRect();
        if (left + pr.width > window.innerWidth) left = rect.left - pr.width - 10;
        if (top + pr.height > window.innerHeight) top = window.innerHeight - pr.height - 20;
        popup.style.top = Math.max(10, top) + 'px';
        popup.style.left = Math.max(10, left) + 'px';
    }

    // Modal
    function closeModal() { $('modal').style.display = 'none'; }

    function updateTimeInputs() {
        const allDay = $('evtAllDay').checked;
        $('evtStart').type = allDay ? 'date' : 'datetime-local';
        $('evtEnd').type = allDay ? 'date' : 'datetime-local';
    }

    function toggleRecurrenceEnd() {
        const recType = $('evtRecur').value;
        const customGroup = $('customIntervalGroup');
        const endGroup = $('recurEndGroup');
        customGroup.style.display = recType === 'custom' ? '' : 'none';
        endGroup.style.display = recType !== 'none' ? '' : 'none';
        if (recType === 'custom') updateCustomUnits();
        toggleHolidayUI();
    }

    function toggleRecurEndDate() {
        const endType = $('recurEndType').value;
        $('recurEnd').style.display = endType === 'on' ? '' : 'none';
    }

    function updateCustomUnits() {
        const startVal = $('evtStart').value;
        const endVal = $('evtEnd').value;
        const isMultiDay = startVal && endVal && startVal.substring(0, 10) !== endVal.substring(0, 10);
        const unit = $('evtCustomUnit');
        const daysOpt = unit.querySelector('option[value="days"]');
        if (isMultiDay) {
            if (daysOpt) daysOpt.remove();
            if (unit.value === 'days') unit.value = 'weeks';
        } else if (!daysOpt) {
            const opt = document.createElement('option');
            opt.value = 'days';
            opt.textContent = L.unitDays;
            unit.insertBefore(opt, unit.firstChild);
        }
    }

    function updateRecurrenceOptions() {
        const startVal = $('evtStart').value;
        if (!startVal) return;
        const endVal = $('evtEnd').value;
        const isMultiDay = startVal && endVal && startVal.substring(0, 10) !== endVal.substring(0, 10);
        const sel = $('evtRecur');
        const currentVal = sel.value;
        const dt = new Date(startVal);
        const dayName = CAL_NAMES.days[dt.getDay()];
        const monthDay = dt.getDate();
        const yearlyDate = monthDay + '. ' + CAL_NAMES.months[dt.getMonth()];
        const weekOfMonth = Math.ceil(monthDay / 7);
        const lastDay = new Date(dt.getFullYear(), dt.getMonth() + 1, 0).getDate();
        const isLast = (monthDay + 7) > lastDay;
        const ordIdx = isLast ? 4 : weekOfMonth - 1;
        const ordinals = [L.ord1, L.ord2, L.ord3, L.ord4, L.ordLast];
        const ordinal = ordinals[ordIdx];

        const options = [
            {value:'none', text: L.recurNone},
        ];
        if (!isMultiDay) {
            options.push({value:'daily', text: L.daily});
        }
        options.push(
            {value:'weekly', text: fmt(L.weeklyOn, dayName)},
            {value:'monthly', text: fmt(L.monthlyOn, ordinal, dayName)},
            {value:'yearly', text: fmt(L.yearlyOn, yearlyDate)},
            {value:'custom', text: L.custom},
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

    function openNew(start, end, allDay) {
        hidePopup();
        $('modalTitle').textContent = '<?php echo Text::_('MOD_JWCALENDAR_NEW_EVENT'); ?>';
        $('evtId').value = '';
        $('evtTitle').value = '';
        $('evtAllDay').checked = allDay || false;
        updateTimeInputs();
        $('evtStart').value = toLocal(start || new Date(), allDay);
        let endDate = end || new Date(Date.now()+3600000);
        if (allDay && end) {
            endDate = new Date(end);
            endDate.setDate(endDate.getDate() - 1);
        }
        $('evtEnd').value = toLocal(endDate, allDay);
        updateRecurrenceOptions();
        $('evtDesc').value = '';
        $('evtCat').value = '0';
        $('evtRecur').value = 'none';
        $('customIntervalGroup').style.display = 'none';
        $('recurEndGroup').style.display = 'none';
        $('recurEndType').value = 'never';
        $('recurEnd').value = '';
        $('recurEnd').style.display = 'none';
        $('evtCustomInterval').value = '1';
        $('evtCustomUnit').value = 'days';
        $('evtSkipHolidays').checked = false;
        $('evtHolidayCountry').value = 'DE';
        fillSubdivSelect($('evtHolidaySubdiv'), 'DE', '');
        $('evtExceptionDates').value = '';
        toggleHolidayUI();
        $('btnDel').style.display = 'none';
        $('modal').style.display = 'flex';
        $('evtTitle').focus();
    }

    function openEdit(ev) {
        hidePopup();
        const p = ev.extendedProps || {};
        $('modalTitle').textContent = '<?php echo Text::_('MOD_JWCALENDAR_EDIT_EVENT'); ?>';
        $('evtId').value = String(ev.id).replace(/_r\d+$/, '');
        $('evtTitle').value = ev.title;
        $('evtAllDay').checked = ev.allDay;
        updateTimeInputs();
        $('evtStart').value = toLocal(ev.start, ev.allDay);
        let editEnd = ev.end || ev.start;
        if (ev.allDay && ev.end) {
            editEnd = new Date(ev.end);
            editEnd.setDate(editEnd.getDate() - 1);
        }
        $('evtEnd').value = toLocal(editEnd, ev.allDay);
        updateRecurrenceOptions();
        $('evtDesc').value = p.description || '';
        $('evtCat').value = p.category_id || 0;
        if (p.recurrence_type === 'custom' || (p.recurrence_type === 'daily' && p.recurrence_interval > 1)) {
            $('evtRecur').value = 'custom';
            $('evtCustomInterval').value = p.recurrence_interval || 1;
            $('evtCustomUnit').value = 'days';
        } else if (p.recurrence_type === 'weekly' && p.recurrence_interval > 1) {
            $('evtRecur').value = 'custom';
            $('evtCustomInterval').value = p.recurrence_interval;
            $('evtCustomUnit').value = 'weeks';
        } else if (p.recurrence_type === 'monthly' && p.recurrence_interval > 1) {
            $('evtRecur').value = 'custom';
            $('evtCustomInterval').value = p.recurrence_interval;
            $('evtCustomUnit').value = 'months';
        } else if (p.recurrence_type === 'yearly' && p.recurrence_interval > 1) {
            $('evtRecur').value = 'custom';
            $('evtCustomInterval').value = p.recurrence_interval;
            $('evtCustomUnit').value = 'years';
        } else {
            $('evtRecur').value = p.recurrence_type || 'none';
        }
        toggleRecurrenceEnd();
        // Load recurrence end date
        if (p.recurrence_end && p.recurrence_type && p.recurrence_type !== 'none') {
            const reDate = p.recurrence_end.substring(0, 10);
            const farFuture = (new Date().getFullYear() + 9) + '-12-31';
            if (reDate > farFuture) {
                $('recurEndType').value = 'never';
                $('recurEnd').style.display = 'none';
            } else {
                $('recurEndType').value = 'on';
                $('recurEnd').value = reDate;
                $('recurEnd').style.display = '';
            }
        } else {
            $('recurEndType').value = 'never';
            $('recurEnd').value = '';
            $('recurEnd').style.display = 'none';
        }
        // Holiday-skip / exception settings
        $('evtSkipHolidays').checked = !!(+p.skip_holidays);
        const editCountry = HOLIDAY_COUNTRIES[p.holiday_country] ? p.holiday_country : 'DE';
        $('evtHolidayCountry').value = editCountry;
        fillSubdivSelect($('evtHolidaySubdiv'), editCountry, p.holiday_subdivision || '');
        $('evtExceptionDates').value = p.exception_dates || '';
        toggleHolidayUI();
        $('btnDel').style.display = '';
        $('modal').style.display = 'flex';
        $('evtTitle').focus();
    }

    // AJAX save
    async function saveEvt() {
        const title = $('evtTitle').value.trim();
        if (!title) { $('evtTitle').classList.add('jw-input-error'); return; }
        $('evtTitle').classList.remove('jw-input-error');

        const startVal = $('evtStart').value;
        const endVal = $('evtEnd').value;

        if (!startVal) { alert(L.startMissing); return; }
        if (!endVal) { alert(L.endMissing); return; }

        const catVal = $('evtCat').value;
        if (!catVal || catVal === '0') { alert('<?php echo Text::_('MOD_JWCALENDAR_SELECT_CATEGORY'); ?>'); $('evtCat').focus(); return; }

        const allDay = $('evtAllDay').checked;
        const recType = $('evtRecur').value;
        const fd = new URLSearchParams();
        fd.append('id', $('evtId').value);
        fd.append('title', title);
        fd.append('description', $('evtDesc').value);
        fd.append('all_day', allDay ? 1 : 0);
        fd.append('category_id', catVal);
        let saveRecType = recType;
        let saveInterval = 1;
        if (recType === 'custom') {
            const unit = $('evtCustomUnit').value;
            saveRecType = unit === 'days' ? 'daily' : unit === 'weeks' ? 'weekly' : unit === 'months' ? 'monthly' : 'yearly';
            saveInterval = parseInt($('evtCustomInterval').value) || 1;
        }
        fd.append('recurrence_type', saveRecType);
        fd.append('recurrence_interval', saveInterval);

        fd.append('start_date', toMySQL(startVal, allDay));
        fd.append('end_date', toMySQL(endVal, allDay));
        if (recType !== 'none') {
            const endType = $('recurEndType').value;
            if (endType === 'on') {
                const recurEnd = $('recurEnd').value;
                fd.append('recurrence_end', recurEnd ? recurEnd + ' 23:59:00' : (new Date().getFullYear() + 10) + '-12-31 23:59:00');
            } else {
                fd.append('recurrence_end', (new Date().getFullYear() + 10) + '-12-31 23:59:00');
            }
        } else {
            fd.append('recurrence_end', '');
        }
        fd.append('skip_holidays', (recType !== 'none' && $('evtSkipHolidays').checked) ? 1 : 0);
        fd.append('holiday_country', $('evtHolidayCountry').value || '');
        fd.append('holiday_subdivision', $('evtHolidaySubdiv').value || '');
        fd.append('exception_dates', $('evtExceptionDates').value || '');
        fd.append(TOKEN, 1);

        try {
            const r = await fetch(BASE + 'saveEvent', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:fd});
            const text = await r.text();
            let d;
            try { d = JSON.parse(text); } catch(pe) { alert(L.serverError + ' ' + text.substring(0, 200)); return; }
            if (d.success) { closeModal(); calendar.refetchEvents(); }
            else alert(d.message || 'Fehler beim Speichern');
        } catch(e) { alert(L.networkError + ' ' + e.message); }
    }

    // AJAX delete
    async function delEvt(id) {
        if (!confirm('<?php echo Text::_('MOD_JWCALENDAR_CONFIRM_DELETE'); ?>')) return;
        const fd = new URLSearchParams();
        fd.append('id', String(id).replace(/_r\d+$/, ''));
        fd.append(TOKEN, 1);
        try {
            const r = await fetch(BASE + 'deleteEvent', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:fd});
            const d = await r.json();
            if (d.success) { hidePopup(); closeModal(); calendar.refetchEvents(); }
            else alert(d.message || 'Error');
        } catch(e) { alert('Error: '+e.message); }
    }

    // FullCalendar init
    const calEl = document.getElementById(M + '_cal');
    calendar = new FullCalendar.Calendar(calEl, {
        locale: '<?php echo $fcLocale; ?>',
        initialView: '<?php echo $defaultView; ?>',
<?php if ($initialDate) : ?>
        initialDate: '<?php echo $initialDate; ?>',
<?php endif; ?>
        eventDisplay: '<?php echo $eventDisplayFc; ?>',
        dayHeaderContent: fcDayHeader,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        height: '<?php echo $calHeight; ?>',
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
        businessHours: { daysOfWeek: [1,2,3,4,5], startTime: '08:00', endTime: '18:00' },

        events: function(info, ok, fail) {
            fetch(BASE + 'getEvents&start=' + info.startStr + '&end=' + info.endStr)
                .then(r => r.json())
                .then(data => {
                    ok(data.filter(e => {
                        const cid = e.extendedProps?.category_id || 0;
                        return cid === 0 || activeCats[cid] !== false;
                    }));
                }).catch(fail);
        },

        select: function(info) {
            if (CAN_EDIT) openNew(info.start, info.end, info.allDay);
            calendar.unselect();
        },
        eventClick: function(info) { info.jsEvent.preventDefault(); showPopup(info); },

        eventDrop: function(info) {
            if (!CAN_EDIT || !info.event.extendedProps?.editable) { info.revert(); return; }
            const fd = new URLSearchParams();
            fd.append('id', String(info.event.id).replace(/_r\d+$/, ''));
            fd.append('start_date', toMySQL(info.event.start));
            fd.append('end_date', toMySQL(info.event.end || info.event.start));
            fd.append('all_day', info.event.allDay ? 1 : 0);
            fd.append(TOKEN, 1);
            fetch(BASE + 'moveEvent', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:fd})
                .then(r=>r.json()).then(d=>{ if(!d.success) info.revert(); }).catch(()=>info.revert());
        },
        eventResize: function(info) {
            if (!CAN_EDIT || !info.event.extendedProps?.editable) { info.revert(); return; }
            const fd = new URLSearchParams();
            fd.append('id', String(info.event.id).replace(/_r\d+$/, ''));
            fd.append('start_date', toMySQL(info.event.start));
            fd.append('end_date', toMySQL(info.event.end || info.event.start));
            fd.append(TOKEN, 1);
            fetch(BASE + 'moveEvent', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:fd})
                .then(r=>r.json()).then(d=>{ if(!d.success) info.revert(); }).catch(()=>info.revert());
        },
        eventDidMount: function(info) {
            info.el.title = info.event.title;
            if (EVENT_STYLE === 'bar' && info.el.classList.contains('fc-daygrid-block-event')) {
                const txt = pickTextColor(info.event.backgroundColor || DEFAULT_COLOR);
                info.el.style.setProperty('--fc-event-text-color', txt);
                info.el.style.color = txt;
            }
        },
        datesSet: function(info) { if (miniCal) miniCal.gotoDate(info.view.currentStart); fcFixTitle(calEl, info.view); const wrap = calEl.closest('.jw-calendar-wrapper'); if (wrap) wrap.classList.toggle('jw-view-list', info.view.type.indexOf('list') === 0); }
    });
    calendar.render();

    // Mini calendar
    const miniEl = $('mini');
    if (miniEl) {
        miniCal = new FullCalendar.Calendar(miniEl, {
            locale: '<?php echo $fcLocale; ?>',
            initialView: 'dayGridMonth',
<?php if ($initialDate) : ?>
            initialDate: '<?php echo $initialDate; ?>',
<?php endif; ?>
            headerToolbar: { left: 'prev', center: 'title', right: 'next' },
            height: 'auto',
            fixedWeekCount: false,
            firstDay: <?php echo $firstDay; ?>,
            dayHeaderFormat: { weekday: 'narrow' },
            dayHeaderContent: fcDayHeaderNarrow,
            datesSet: function(info) { fcFixTitle(miniEl, info.view); },
            dateClick: function(info) { calendar.gotoDate(info.date); calendar.changeView('timeGridDay'); }
        });
        miniCal.render();
    }

    // Events
    initCats();
    fillCountrySelect($('evtHolidayCountry'));
    fillSubdivSelect($('evtHolidaySubdiv'), 'DE', '');

    const btnCreate = $('btnCreate');
    if (btnCreate) btnCreate.addEventListener('click', () => openNew());
    $('modalClose').addEventListener('click', closeModal);
    $('btnCancel').addEventListener('click', closeModal);
    $('btnSave').addEventListener('click', () => saveEvt());
    $('btnDel').addEventListener('click', () => delEvt($('evtId').value));
    $('evtAllDay').addEventListener('change', updateTimeInputs);
    $('evtStart').addEventListener('change', function() { updateRecurrenceOptions(); if ($('evtRecur').value === 'custom') updateCustomUnits(); });
    $('evtEnd').addEventListener('change', function() { updateRecurrenceOptions(); if ($('evtRecur').value === 'custom') updateCustomUnits(); });
    $('evtRecur').addEventListener('change', toggleRecurrenceEnd);
    $('recurEndType').addEventListener('change', toggleRecurEndDate);
    $('evtSkipHolidays').addEventListener('change', toggleHolidayUI);
    $('evtHolidayCountry').addEventListener('change', function() { fillSubdivSelect($('evtHolidaySubdiv'), this.value, ''); });
    $('popClose').addEventListener('click', hidePopup);
    $('popEdit').addEventListener('click', () => { if (popEvt) openEdit(popEvt); });
    $('popDel').addEventListener('click', () => { if (popEvt) delEvt(popEvt.id); });

    if ($('toggleCats')) {
        $('toggleCats').addEventListener('click', function() {
            this.parentElement.nextElementSibling.classList.toggle('collapsed');
            this.textContent = this.textContent === '\u25BC' ? '\u25B6' : '\u25BC';
        });
    }

    document.addEventListener('click', e => {
        if (!e.target.closest('#'+M+'_popup') && !e.target.closest('.fc-event')) hidePopup();
    });
    $('modal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
    document.addEventListener('keydown', e => {
        if (e.key==='Escape') {
            closeModal();
            hidePopup();
            if (IS_SUPER) closeCatModal();
        }
    });


    } catch(globalErr) {
        // Fehler SICHTBAR auf der Seite anzeigen
        console.error('[JWCal] GLOBALER FEHLER:', globalErr);
        document.body.insertAdjacentHTML('afterbegin',
            '<div style="background:#d50000;color:#fff;padding:20px;font-size:16px;font-family:monospace;position:fixed;top:0;left:0;right:0;z-index:99999;">' +
            'JW Calendar JS FEHLER: ' + globalErr.message + '<br>Stack: ' + (globalErr.stack || '').substring(0, 300) + '</div>');
    }
});
</script>
