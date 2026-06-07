<?php
/**
 * @license GNU/GPL v2 or later
 * @copyright (c) 2026 huberlabs.ch
 */


namespace Jewe\Component\Calendar\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

class EventsModel extends ListModel
{
    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('e.*')
            ->from($db->quoteName('#__calendar_events', 'e'))
            ->select($db->quoteName('c.title', 'category_title'))
            ->select($db->quoteName('c.color', 'category_color'))
            ->join('LEFT', $db->quoteName('#__calendar_categories', 'c') . ' ON c.id = e.category_id')
            ->select($db->quoteName('u.name', 'author_name'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = e.created_by');

        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where('(e.title LIKE ' . $search . ' OR e.description LIKE ' . $search . ')');
        }

        $state = $this->getState('filter.state');
        if (is_numeric($state)) {
            $query->where('e.state = ' . (int) $state);
        }

        $categoryId = $this->getState('filter.category_id');
        if (is_numeric($categoryId)) {
            $query->where('e.category_id = ' . (int) $categoryId);
        }

        $query->order('e.start_date DESC');

        return $query;
    }

    protected function populateState($ordering = 'e.start_date', $direction = 'DESC'): void
    {
        parent::populateState($ordering, $direction);
    }
}
