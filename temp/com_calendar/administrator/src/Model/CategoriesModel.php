<?php
/**
 * @license GNU/GPL v2 or later
 * @copyright (c) 2026 huberlabs.ch
 */


namespace Jewe\Component\Calendar\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

class CategoriesModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'c.id',
                'title', 'c.title',
                'state', 'c.state',
                'ordering', 'c.ordering',
            ];
        }
        parent::__construct($config);
    }

    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('c.*')
            ->from($db->quoteName('#__calendar_categories', 'c'));

        // Filter by state
        $state = $this->getState('filter.state');
        if (is_numeric($state)) {
            $query->where('c.state = ' . (int) $state);
        }

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where('c.title LIKE ' . $search);
        }

        // Count events per category
        $subQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__calendar_events', 'e'))
            ->where('e.category_id = c.id')
            ->where('e.state = 1');
        $query->select('(' . $subQuery . ') AS event_count');

        $query->order($db->escape($this->getState('list.ordering', 'c.ordering')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

        return $query;
    }

    protected function populateState($ordering = 'c.ordering', $direction = 'ASC'): void
    {
        parent::populateState($ordering, $direction);
    }
}
