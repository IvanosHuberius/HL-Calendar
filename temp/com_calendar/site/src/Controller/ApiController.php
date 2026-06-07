<?php
/**
 * @license GNU/GPL v2 or later
 * @copyright (c) 2026 huberlabs.ch
 */


namespace Jewe\Component\Calendar\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;

class ApiController extends BaseController
{
    /**
     * Get events as JSON for FullCalendar
     */
    public function getEvents()
    {
        $app = Factory::getApplication();
        $input = $app->getInput();

        $start = $input->getString('start', '');
        $end = $input->getString('end', '');
        $categoryId = $input->getInt('category_id', 0);

        // FullCalendar sends ISO dates with timezone (e.g. 2026-01-26T00:00:00+01:00)
        // The + becomes a space in URL params → PHP DateTime crashes
        // Fix: extract only the YYYY-MM-DD portion
        if ($start && preg_match('/^(\d{4}-\d{2}-\d{2})/', $start, $m)) {
            $start = $m[1];
        }
        if ($end && preg_match('/^(\d{4}-\d{2}-\d{2})/', $end, $m)) {
            $end = $m[1];
        }

        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true);
        $user = $app->getIdentity();

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
                    'created_by' => (int) $event->created_by,
                    'editable' => ($user->id > 0 && ($user->id == $event->created_by || $user->authorise('core.edit', 'com_calendar'))),
                ],
            ];
            $result[] = $eventData;

            // Generate recurring events
            if ($event->recurrence_type !== 'none' && $event->recurrence_type) {
                $recurrences = $this->generateRecurrences($event, $start, $end, $user);
                $result = array_merge($result, $recurrences);
            }
        }

        echo json_encode($result);
        $app->close();
    }

    /**
     * Save an event (create or update)
     */
    public function saveEvent()
    {
        if (!Session::checkToken('get') && !Session::checkToken()) {
            $app = Factory::getApplication();
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $app->close();
            return;
        }

        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $input = $app->getInput();

        if ($user->guest) {
            echo new JsonResponse(null, Text::_('COM_CALENDAR_ERROR_NOT_AUTHORIZED'), true);
            $app->close();
            return;
        }

        $data = $input->getArray([
            'id' => 'int',
            'title' => 'string',
            'description' => 'raw',
            'start_date' => 'string',
            'end_date' => 'string',
            'all_day' => 'int',
            'category_id' => 'int',
            'recurrence_type' => 'string',
            'recurrence_interval' => 'int',
            'recurrence_end' => 'string',
        ]);

        $db = Factory::getContainer()->get('DatabaseDriver');

        // Check permissions for editing existing event
        if (!empty($data['id'])) {
            $query = $db->getQuery(true)
                ->select('created_by')
                ->from('#__calendar_events')
                ->where('id = ' . (int) $data['id']);
            $db->setQuery($query);
            $createdBy = $db->loadResult();

            if ($user->id != $createdBy && !$user->authorise('core.edit', 'com_calendar')) {
                echo new JsonResponse(null, Text::_('COM_CALENDAR_ERROR_NOT_AUTHORIZED'), true);
                $app->close();
                return;
            }
        }

        // Clean up empty datetime values (MySQL strict mode rejects '' for datetime)
        if (empty($data['recurrence_end'])) {
            $data['recurrence_end'] = null;
        }

        $table = Factory::getApplication()->bootComponent('com_calendar')
            ->getMVCFactory()->createTable('Event', 'Administrator');

        if (!empty($data['id'])) {
            $table->load($data['id']);
        }

        $table->bind($data);

        if (empty($table->created_by)) {
            $table->created_by = $user->id;
        }
        if (empty($table->created)) {
            $table->created = Factory::getDate()->toSql();
        }

        $table->state = 1;
        if (empty($table->access)) {
            $table->access = 1;
        }

        try {
            if (!$table->check() || !$table->store()) {
                Factory::getApplication()->enqueueMessage($table->getError() ?: 'Unknown error', 'error');
                echo new JsonResponse(null, Text::_('COM_CALENDAR_ERROR_SAVING'), true);
                $app->close();
                return;
            }
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            echo new JsonResponse(null, Text::_('COM_CALENDAR_ERROR_SAVING'), true);
            $app->close();
            return;
        }

        echo new JsonResponse(['id' => $table->id], Text::_('COM_CALENDAR_EVENT_SAVED'));
        $app->close();
    }

    /**
     * Delete an event
     */
    public function deleteEvent()
    {
        if (!Session::checkToken('get') && !Session::checkToken()) {
            $app = Factory::getApplication();
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $app->close();
            return;
        }

        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $id = $app->getInput()->getInt('id', 0);

        if ($user->guest || !$id) {
            echo new JsonResponse(null, Text::_('COM_CALENDAR_ERROR_NOT_AUTHORIZED'), true);
            $app->close();
            return;
        }

        $db = Factory::getContainer()->get('DatabaseDriver');

        // Check ownership
        $query = $db->getQuery(true)
            ->select('created_by')
            ->from('#__calendar_events')
            ->where('id = ' . $id);
        $db->setQuery($query);
        $createdBy = $db->loadResult();

        if ($user->id != $createdBy && !$user->authorise('core.delete', 'com_calendar')) {
            echo new JsonResponse(null, Text::_('COM_CALENDAR_ERROR_NOT_AUTHORIZED'), true);
            $app->close();
            return;
        }

        $query = $db->getQuery(true)
            ->delete('#__calendar_events')
            ->where('id = ' . $id);
        $db->setQuery($query);
        $db->execute();

        echo new JsonResponse(null, Text::_('COM_CALENDAR_EVENT_DELETED'));
        $app->close();
    }

    /**
     * Move/resize an event (drag & drop)
     */
    public function moveEvent()
    {
        if (!Session::checkToken('get') && !Session::checkToken()) {
            $app = Factory::getApplication();
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $app->close();
            return;
        }

        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $input = $app->getInput();

        $id = $input->getInt('id', 0);
        $startDate = $input->getString('start_date', '');
        $endDate = $input->getString('end_date', '');
        $allDay = $input->getInt('all_day', -1);

        if ($user->guest || !$id) {
            echo new JsonResponse(null, Text::_('COM_CALENDAR_ERROR_NOT_AUTHORIZED'), true);
            $app->close();
            return;
        }

        $db = Factory::getContainer()->get('DatabaseDriver');

        // Check ownership
        $query = $db->getQuery(true)
            ->select('created_by')
            ->from('#__calendar_events')
            ->where('id = ' . $id);
        $db->setQuery($query);
        $createdBy = $db->loadResult();

        if ($user->id != $createdBy && !$user->authorise('core.edit', 'com_calendar')) {
            echo new JsonResponse(null, Text::_('COM_CALENDAR_ERROR_NOT_AUTHORIZED'), true);
            $app->close();
            return;
        }

        $fields = [
            $db->quoteName('start_date') . ' = ' . $db->quote($startDate),
            $db->quoteName('modified') . ' = ' . $db->quote(Factory::getDate()->toSql()),
            $db->quoteName('modified_by') . ' = ' . $user->id,
        ];

        if ($endDate) {
            $fields[] = $db->quoteName('end_date') . ' = ' . $db->quote($endDate);
        }
        if ($allDay >= 0) {
            $fields[] = $db->quoteName('all_day') . ' = ' . $allDay;
        }

        $query = $db->getQuery(true)
            ->update('#__calendar_events')
            ->set($fields)
            ->where('id = ' . $id);
        $db->setQuery($query);
        $db->execute();

        echo new JsonResponse(null, Text::_('COM_CALENDAR_EVENT_UPDATED'));
        $app->close();
    }

    /**
     * Get categories
     */
    public function getCategories()
    {
        $app = Factory::getApplication();
        $db = Factory::getContainer()->get('DatabaseDriver');

        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__calendar_categories')
            ->where('state = 1')
            ->order('ordering, title');
        $db->setQuery($query);
        $categories = $db->loadObjectList();

        echo json_encode($categories);
        $app->close();
    }

    /**
     * Save a category (create or update) - Super User only
     */
    public function saveCategory()
    {
        if (!Session::checkToken('get') && !Session::checkToken()) {
            $app = Factory::getApplication();
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $app->close();
            return;
        }

        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.admin')) {
            echo new JsonResponse(null, Text::_('COM_CALENDAR_ERROR_NOT_AUTHORIZED'), true);
            $app->close();
            return;
        }

        $input = $app->getInput();
        $id = $input->getInt('id', 0);
        $title = trim($input->getString('title', ''));
        $color = $input->getString('color', '#3788d8');
        if (!preg_match('/^#[0-9a-fA-F]{3,6}$/', $color)) {
            $color = '#3788d8';
        }
        $description = $input->getString('description', '');

        if (empty($title)) {
            echo new JsonResponse(null, Text::_('COM_CALENDAR_ERROR_TITLE_REQUIRED'), true);
            $app->close();
            return;
        }

        $db = Factory::getContainer()->get('DatabaseDriver');

        if ($id) {
            // Update existing
            $fields = [
                $db->quoteName('title') . ' = ' . $db->quote($title),
                $db->quoteName('color') . ' = ' . $db->quote($color),
                $db->quoteName('description') . ' = ' . $db->quote($description),
            ];

            $query = $db->getQuery(true)
                ->update('#__calendar_categories')
                ->set($fields)
                ->where('id = ' . $id);
            $db->setQuery($query);
            $db->execute();
        } else {
            // Get next ordering value
            $query = $db->getQuery(true)
                ->select('COALESCE(MAX(ordering), 0) + 1')
                ->from('#__calendar_categories');
            $db->setQuery($query);
            $nextOrder = (int) $db->loadResult();

            // Insert new
            $columns = ['title', 'color', 'description', 'state', 'access', 'created_by', 'created', 'ordering'];
            $values = [
                $db->quote($title),
                $db->quote($color),
                $db->quote($description),
                1,
                1,
                (int) $user->id,
                $db->quote(Factory::getDate()->toSql()),
                $nextOrder,
            ];

            $query = $db->getQuery(true)
                ->insert('#__calendar_categories')
                ->columns($columns)
                ->values(implode(',', $values));
            $db->setQuery($query);
            $db->execute();

            $id = $db->insertid();
        }

        // Return the saved category
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__calendar_categories')
            ->where('id = ' . (int) $id);
        $db->setQuery($query);
        $category = $db->loadObject();

        echo new JsonResponse($category, Text::_('COM_CALENDAR_CATEGORY_SAVED'));
        $app->close();
    }

    /**
     * Delete a category - Super User only
     */
    public function deleteCategory()
    {
        if (!Session::checkToken('get') && !Session::checkToken()) {
            $app = Factory::getApplication();
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $app->close();
            return;
        }

        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.admin')) {
            echo new JsonResponse(null, Text::_('COM_CALENDAR_ERROR_NOT_AUTHORIZED'), true);
            $app->close();
            return;
        }

        $id = $app->getInput()->getInt('id', 0);

        if (!$id) {
            echo new JsonResponse(null, 'Invalid ID', true);
            $app->close();
            return;
        }

        $db = Factory::getContainer()->get('DatabaseDriver');

        // Unassign events from this category
        $query = $db->getQuery(true)
            ->update('#__calendar_events')
            ->set($db->quoteName('category_id') . ' = 0')
            ->where('category_id = ' . $id);
        $db->setQuery($query);
        $db->execute();

        // Delete category
        $query = $db->getQuery(true)
            ->delete('#__calendar_categories')
            ->where('id = ' . $id);
        $db->setQuery($query);
        $db->execute();

        echo new JsonResponse(null, Text::_('COM_CALENDAR_CATEGORY_DELETED'));
        $app->close();
    }

    /**
     * Generate recurring event instances
     */
    private function generateRecurrences($event, $rangeStart, $rangeEnd, $user): array
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
                        'created_by' => (int) $event->created_by,
                        'editable' => ($user->id > 0 && ($user->id == $event->created_by || $user->authorise('core.edit', 'com_calendar'))),
                    ],
                ];
            }
        }

        return $result;
    }
}
