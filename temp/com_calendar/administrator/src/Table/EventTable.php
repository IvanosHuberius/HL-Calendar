<?php
/**
 * @license GNU/GPL v2 or later
 * @copyright (c) 2026 huberlabs.ch
 */


namespace Jewe\Component\Calendar\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Factory;

class EventTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__calendar_events', 'id', $db);

        $this->setColumnAlias('published', 'state');
    }

    public function check(): bool
    {
        if (empty($this->title)) {
            $this->setError('COM_CALENDAR_ERROR_TITLE_REQUIRED');
            return false;
        }
        if (empty($this->start_date)) {
            $this->setError('COM_CALENDAR_ERROR_START_DATE_REQUIRED');
            return false;
        }
        if (empty($this->end_date)) {
            $this->end_date = $this->start_date;
        }
        if (empty($this->created)) {
            $this->created = Factory::getDate()->toSql();
        }
        if (empty($this->created_by)) {
            $this->created_by = Factory::getApplication()->getIdentity()->id;
        }

        // Clean up empty datetime fields (MySQL strict mode rejects '' for datetime)
        if ($this->recurrence_end === '' || $this->recurrence_end === '0000-00-00 00:00:00') {
            $this->recurrence_end = null;
        }

        // Ensure valid access level
        if (empty($this->access)) {
            $this->access = 1;
        }

        return true;
    }

    public function store($updateNulls = true): bool
    {
        $this->modified = Factory::getDate()->toSql();
        $this->modified_by = Factory::getApplication()->getIdentity()->id;
        return parent::store($updateNulls);
    }
}
