<?php
/**
 * @license GNU/GPL v2 or later
 * @copyright (c) 2026 huberlabs.ch
 */

namespace Jewe\Component\Calendar\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/**
 * Central place where events are read from the database and expanded into the
 * FullCalendar payload (including recurrences, holiday skipping and exception dates).
 *
 * Both the AJAX endpoints (ApiController) and the server-side "start date" lookup
 * used by the component and the module template go through this class, so the
 * recurrence logic exists exactly once.
 */
class EventService
{
    /**
     * Build the FullCalendar event payload for a date range.
     *
     * @param string $start      Range start as Y-m-d ('' = no lower bound)
     * @param string $end        Range end as Y-m-d ('' = no upper bound)
     * @param int    $categoryId Restrict to a category (0 = all)
     * @param object $user       Joomla user used for the access check
     */
    public function buildEvents(string $start, string $end, int $categoryId, $user): array
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);

        $query->select('e.*, c.title as category_title, c.color as category_color')
            ->from($db->quoteName('#__calendar_events', 'e'))
            ->join('LEFT', $db->quoteName('#__calendar_categories', 'c') . ' ON c.id = e.category_id')
            ->where('e.state = 1');

        // Access check
        $groups = array_map('intval', $user->getAuthorisedViewLevels());
        $query->where('e.access IN (' . implode(',', $groups) . ')');

        if ($start) {
            // For recurring events, check recurrence_end instead of end_date
            $query->where('(e.end_date >= ' . $db->quote($start)
                . ' OR (e.recurrence_type != ' . $db->quote('none')
                . ' AND (e.recurrence_end IS NULL OR e.recurrence_end >= ' . $db->quote($start) . ')))');
        }
        if ($end) {
            $query->where('e.start_date <= ' . $db->quote($end));
        }
        if ($categoryId) {
            $query->where('e.category_id = ' . $categoryId);
        }

        $db->setQuery($query);
        $events = $db->loadObjectList();

        $result = [];
        foreach ($events as $event) {
            // FullCalendar expects exclusive end for all-day events (+1 day)
            $displayEnd = $event->end_date;
            if ($event->all_day && $displayEnd) {
                $endDt = new \DateTime($displayEnd);
                $endDt->modify('+1 day');
                $displayEnd = $endDt->format('Y-m-d');
            }
            $eventData = [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->start_date,
                'end' => $displayEnd,
                'allDay' => (bool) $event->all_day,
                'backgroundColor' => $event->category_color ?: '#3788d8',
                'borderColor' => $event->category_color ?: '#3788d8',
                'extendedProps' => [
                    'description' => $event->description ?: '',
                    'category_id' => (int) $event->category_id,
                    'category_title' => $event->category_title ?: '',
                    'recurrence_type' => $event->recurrence_type,
                    'recurrence_interval' => (int) $event->recurrence_interval,
                    'recurrence_end' => $event->recurrence_end ?: '',
                    'skip_holidays' => (int) ($event->skip_holidays ?? 0),
                    'holiday_country' => $event->holiday_country ?? '',
                    'holiday_subdivision' => $event->holiday_subdivision ?? '',
                    'exception_dates' => $event->exception_dates ?? '',
                    'created_by' => (int) $event->created_by,
                    'editable' => ($user->id > 0 && ($user->id == $event->created_by || $user->authorise('core.edit', 'com_calendar'))),
                ],
            ];

            // Generate recurring events
            if ($event->recurrence_type !== 'none' && $event->recurrence_type) {
                // The base instance is itself subject to holiday/exception skipping
                $holidayCache = [];
                if (!$this->isExcludedDate($event, new \DateTime($event->start_date), $holidayCache)) {
                    $result[] = $eventData;
                }
                $recurrences = $this->generateRecurrences($event, $start, $end, $user, $holidayCache);
                $result = array_merge($result, $recurrences);
            } else {
                $result[] = $eventData;
            }
        }

        return $result;
    }

    /**
     * Date (Y-m-d) of the next event at or after $from, or null if there is none
     * within $monthsAhead months. An event that is already running on $from
     * yields $from itself.
     */
    public function getNextEventDate($user, int $categoryId = 0, ?string $from = null, int $monthsAhead = 24): ?string
    {
        $fromDt = $from ? new \DateTime($from) : new \DateTime('today');
        $fromYmd = $fromDt->format('Y-m-d');
        $untilYmd = (clone $fromDt)->modify('+' . max(1, $monthsAhead) . ' months')->format('Y-m-d');

        $events = $this->buildEvents($fromYmd, $untilYmd, $categoryId, $user);

        $next = null;
        foreach ($events as $event) {
            $startYmd = substr((string) $event['start'], 0, 10);
            $endYmd = substr((string) ($event['end'] ?: $event['start']), 0, 10);

            if ($startYmd >= $fromYmd) {
                $candidate = $startYmd;
            } elseif ($endYmd >= $fromYmd) {
                // Multi-day event that is already running → nothing to jump to
                $candidate = $fromYmd;
            } else {
                continue;
            }

            if ($next === null || $candidate < $next) {
                $next = $candidate;
            }
        }

        return $next;
    }

    /**
     * Whether at least one event is visible in the given range (past ones included).
     */
    public function hasEventsInRange($user, string $start, string $end, int $categoryId = 0): bool
    {
        return $this->buildEvents($start, $end, $categoryId, $user) !== [];
    }

    /**
     * Resolve the date the calendar should open on, honouring the "start_date_mode"
     * setting. Returns null when the calendar should simply start on today.
     *
     * @param string $mode  today | next_event | next_event_if_empty
     * @param string $view  FullCalendar view name the calendar starts in
     * @param int    $firstDay 0 = Sunday, 1 = Monday
     */
    public function resolveStartDate($user, string $mode, string $view, int $firstDay = 1, int $categoryId = 0): ?string
    {
        if ($mode !== 'next_event' && $mode !== 'next_event_if_empty') {
            return null;
        }

        if ($mode === 'next_event_if_empty') {
            [$periodStart, $periodEnd] = $this->getPeriodRange($view, $firstDay);

            // Something is already visible → leave the calendar where it is
            if ($this->hasEventsInRange($user, $periodStart, $periodEnd, $categoryId)) {
                return null;
            }
        }

        return $this->getNextEventDate($user, $categoryId);
    }

    /**
     * First and last day (Y-m-d) of the period a given view shows when it opens today.
     */
    private function getPeriodRange(string $view, int $firstDay): array
    {
        $today = new \DateTime('today');

        if ($view === 'timeGridDay' || $view === 'listDay') {
            return [$today->format('Y-m-d'), $today->format('Y-m-d')];
        }

        if ($view === 'timeGridWeek' || $view === 'listWeek' || $view === 'dayGridWeek') {
            $dayOfWeek = (int) $today->format('w'); // 0 = Sunday
            $offset = ($dayOfWeek - $firstDay + 7) % 7;
            $weekStart = (clone $today)->modify('-' . $offset . ' days');
            $weekEnd = (clone $weekStart)->modify('+6 days');

            return [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')];
        }

        if (strpos($view, 'Year') !== false) {
            return [$today->format('Y-01-01'), $today->format('Y-12-31')];
        }

        // dayGridMonth, listMonth, …
        return [$today->format('Y-m-01'), $today->format('Y-m-t')];
    }

    /**
     * Generate recurring event instances
     */
    private function generateRecurrences($event, $rangeStart, $rangeEnd, $user, array &$holidayCache = []): array
    {
        $result = [];
        $interval = max(1, (int) $event->recurrence_interval);
        $start = new \DateTime($event->start_date);
        $end = new \DateTime($event->end_date);

        $duration = $start->diff($end);

        $rangeStartDt = new \DateTime($rangeStart ?: 'now');
        $rangeEndDt = new \DateTime($rangeEnd ?: '+3 months');
        $recurrenceEnd = $event->recurrence_end ? new \DateTime($event->recurrence_end) : (clone $rangeEndDt);

        $maxOccurrences = 365;
        $count = 0;

        $current = clone $start;

        while ($count < $maxOccurrences) {
            switch ($event->recurrence_type) {
                case 'daily':
                    $current->modify("+{$interval} days");
                    break;
                case 'weekly':
                    $current->modify("+{$interval} weeks");
                    break;
                case 'monthly':
                    // Find the Nth weekday of the month (e.g., 2nd Tuesday)
                    $origDayOfWeek = (int) $start->format('N'); // 1=Mon, 7=Sun
                    $origDay = (int) $start->format('j');
                    $origWeekOfMonth = (int) ceil($origDay / 7);
                    $isLast = ($origDay + 7) > (int) $start->format('t');

                    $current->modify("+{$interval} months");
                    $current->setDate((int) $current->format('Y'), (int) $current->format('n'), 1);

                    if ($isLast) {
                        // Last occurrence of this weekday in the month
                        $lastDay = (int) $current->format('t');
                        $current->setDate((int) $current->format('Y'), (int) $current->format('n'), $lastDay);
                        while ((int) $current->format('N') !== $origDayOfWeek) {
                            $current->modify('-1 day');
                        }
                    } else {
                        // Nth occurrence of this weekday
                        while ((int) $current->format('N') !== $origDayOfWeek) {
                            $current->modify('+1 day');
                        }
                        if ($origWeekOfMonth > 1) {
                            $current->modify('+' . ($origWeekOfMonth - 1) . ' weeks');
                        }
                    }
                    break;
                case 'yearly':
                    $current->modify("+{$interval} years");
                    break;
                default:
                    return $result;
            }

            if ($current > $recurrenceEnd || $current > $rangeEndDt) {
                break;
            }

            $count++;
            $currentEnd = clone $current;
            $currentEnd->add($duration);

            if ($currentEnd >= $rangeStartDt) {
                // Skip occurrences that fall on a holiday or a manual exception date
                if ($this->isExcludedDate($event, $current, $holidayCache)) {
                    continue;
                }

                // FullCalendar expects exclusive end for all-day events (+1 day)
                $recDisplayEnd = $currentEnd->format('Y-m-d H:i:s');
                if ($event->all_day) {
                    $recDisplayEnd = (clone $currentEnd)->modify('+1 day')->format('Y-m-d');
                }
                $result[] = [
                    'id' => $event->id . '_r' . $count,
                    'title' => $event->title,
                    'start' => $current->format('Y-m-d H:i:s'),
                    'end' => $recDisplayEnd,
                    'allDay' => (bool) $event->all_day,
                    'backgroundColor' => $event->category_color ?: '#3788d8',
                    'borderColor' => $event->category_color ?: '#3788d8',
                    'extendedProps' => [
                        'description' => $event->description ?: '',
                        'category_id' => (int) $event->category_id,
                        'category_title' => $event->category_title ?: '',
                        'is_recurrence' => true,
                        'original_id' => $event->id,
                        'recurrence_type' => $event->recurrence_type,
                        'recurrence_interval' => (int) $event->recurrence_interval,
                        'recurrence_end' => $event->recurrence_end ?: '',
                        'skip_holidays' => (int) ($event->skip_holidays ?? 0),
                        'holiday_country' => $event->holiday_country ?? '',
                        'holiday_subdivision' => $event->holiday_subdivision ?? '',
                        'exception_dates' => $event->exception_dates ?? '',
                        'created_by' => (int) $event->created_by,
                        'editable' => ($user->id > 0 && ($user->id == $event->created_by || $user->authorise('core.edit', 'com_calendar'))),
                    ],
                ];
            }
        }

        return $result;
    }

    /**
     * Decide whether a single occurrence date must be skipped, because it is a
     * manual exception date or (optionally) a public holiday in the configured region.
     * Holiday lookups are cached per year in $holidayCache for the duration of the request.
     */
    private function isExcludedDate($event, \DateTime $date, array &$holidayCache): bool
    {
        $ymd = $date->format('Y-m-d');

        // 1) Manual exception dates (comma/space/semicolon separated YYYY-MM-DD)
        if (!empty($event->exception_dates)) {
            $list = preg_split('/[\s,;]+/', trim($event->exception_dates));
            if (in_array($ymd, $list, true)) {
                return true;
            }
        }

        // 2) Public holidays via the OpenHolidays API (cached)
        if (!empty($event->skip_holidays) && !empty($event->holiday_country)) {
            $year = (int) $date->format('Y');
            $cacheKey = $event->holiday_country . '|' . ($event->holiday_subdivision ?? '') . '|' . $year;

            if (!isset($holidayCache[$cacheKey])) {
                $service = new HolidayService();
                $holidayCache[$cacheKey] = $service->getHolidays(
                    $event->holiday_country,
                    $event->holiday_subdivision ?? '',
                    $year
                );
            }

            if (in_array($ymd, $holidayCache[$cacheKey], true)) {
                return true;
            }
        }

        return false;
    }
}
