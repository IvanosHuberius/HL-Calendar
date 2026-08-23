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
use Jewe\Component\Calendar\Site\Service\EventService;

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

        $result = (new EventService())->buildEvents($start, $end, $categoryId, $app->getIdentity());

        echo json_encode($result);
        $app->close();
    }

    /**
     * Date (Y-m-d) of the next upcoming event, or null when there is none.
     * Used by the "start date" setting so the calendar can open on the month
     * of the next event instead of an empty current month.
     */
    public function getNextEventDate()
    {
        $app = Factory::getApplication();
        $categoryId = $app->getInput()->getInt('category_id', 0);

        $date = (new EventService())->getNextEventDate($app->getIdentity(), $categoryId);

        echo json_encode(['date' => $date]);
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
            'skip_holidays' => 'int',
            'holiday_country' => 'string',
            'holiday_subdivision' => 'string',
            'exception_dates' => 'string',
        ]);

        // Sanitise holiday-skip / exception fields
        $data['skip_holidays'] = !empty($data['skip_holidays']) ? 1 : 0;
        $data['holiday_country'] = preg_match('/^[A-Za-z]{2}$/', $data['holiday_country'] ?? '')
            ? strtoupper($data['holiday_country']) : '';
        $data['holiday_subdivision'] = preg_match('/^[A-Za-z]{2}-[A-Za-z0-9]{1,3}$/', $data['holiday_subdivision'] ?? '')
            ? strtoupper($data['holiday_subdivision']) : '';
        if (!empty($data['exception_dates'])) {
            preg_match_all('/\d{4}-\d{2}-\d{2}/', $data['exception_dates'], $exMatches);
            $data['exception_dates'] = implode(',', $exMatches[0]);
        } else {
            $data['exception_dates'] = '';
        }

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
}
