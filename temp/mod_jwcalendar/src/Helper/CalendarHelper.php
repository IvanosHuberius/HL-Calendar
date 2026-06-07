<?php
/**
 * @license GNU/GPL v2 or later
 * @copyright (c) 2026 huberlabs.ch
 */


namespace Jewe\Module\JwCalendar\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

class CalendarHelper
{
    public function getCategories(Registry $params): array
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__calendar_categories'))
            ->where('state = 1')
            ->order('ordering, title');

        // Apply category filter from params
        $catFilter = $params->get('category_filter', '');
        if (!empty($catFilter)) {
            if (is_array($catFilter)) {
                $catFilter = array_filter($catFilter);
                if (!empty($catFilter)) {
                    $ids = implode(',', array_map('intval', $catFilter));
                    $query->where('id IN (' . $ids . ')');
                }
            }
        }

        $db->setQuery($query);
        return $db->loadObjectList() ?: [];
    }

    public function canEdit(Registry $params): bool
    {
        if (!(int) $params->get('allow_frontend_edit', 1)) {
            return false;
        }

        $user = Factory::getApplication()->getIdentity();
        return !$user->guest && (
            $user->authorise('core.create', 'com_calendar') ||
            $user->authorise('core.edit', 'com_calendar') ||
            $user->authorise('core.edit.own', 'com_calendar')
        );
    }
}
