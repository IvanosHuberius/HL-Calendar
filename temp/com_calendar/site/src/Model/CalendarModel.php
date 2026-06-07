<?php
/**
 * @license GNU/GPL v2 or later
 * @copyright (c) 2026 huberlabs.ch
 */


namespace Jewe\Component\Calendar\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;

class CalendarModel extends BaseDatabaseModel
{
    public function getCategories(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__calendar_categories'))
            ->where('state = 1')
            ->order('ordering, title');
        $db->setQuery($query);
        return $db->loadObjectList() ?: [];
    }

    public function getCanEdit(): bool
    {
        $user = Factory::getApplication()->getIdentity();
        return !$user->guest && ($user->authorise('core.create', 'com_calendar') || $user->authorise('core.edit', 'com_calendar') || $user->authorise('core.edit.own', 'com_calendar'));
    }
}
