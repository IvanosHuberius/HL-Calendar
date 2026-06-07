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

class CategoryTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__calendar_categories', 'id', $db);

        $this->setColumnAlias('published', 'state');
    }

    public function check(): bool
    {
        if (empty($this->title)) {
            $this->setError('COM_CALENDAR_ERROR_TITLE_REQUIRED');
            return false;
        }
        if (empty($this->created)) {
            $this->created = Factory::getDate()->toSql();
        }
        return true;
    }
}
