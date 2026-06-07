<?php
/**
 * @license GNU/GPL v2 or later
 * @copyright (c) 2026 huberlabs.ch
 */


defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

/** @var \Jewe\Component\Calendar\Administrator\View\Events\HtmlView $this */
?>
<form action="<?php echo Route::_('index.php?option=com_calendar&view=events'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="eventList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_CALENDAR_EVENTS_TABLE_CAPTION'); ?>
                        </caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo Text::_('JSTATUS'); ?>
                                </th>
                                <th scope="col">
                                    <?php echo Text::_('JGLOBAL_TITLE'); ?>
                                </th>
                                <th scope="col" class="w-15">
                                    <?php echo Text::_('COM_CALENDAR_FIELD_START_DATE'); ?>
                                </th>
                                <th scope="col" class="w-15">
                                    <?php echo Text::_('COM_CALENDAR_FIELD_END_DATE'); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo Text::_('COM_CALENDAR_FIELD_CATEGORY'); ?>
                                </th>
                                <th scope="col" class="w-5">
                                    <?php echo Text::_('JGRID_HEADING_ID'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) : ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'events.'); ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo Route::_('index.php?option=com_calendar&task=event.edit&id=' . $item->id); ?>">
                                            <?php echo $this->escape($item->title); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo HTMLHelper::_('date', $item->start_date, Text::_('DATE_FORMAT_LC2')); ?>
                                    </td>
                                    <td>
                                        <?php echo HTMLHelper::_('date', $item->end_date, Text::_('DATE_FORMAT_LC2')); ?>
                                    </td>
                                    <td>
                                        <?php $evColor = $item->category_color ?: $item->color; $safeEvColor = preg_match('/^#[0-9a-fA-F]{3,6}$/', $evColor) ? $evColor : '#000000'; ?>
                                        <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:<?php echo htmlspecialchars($safeEvColor, ENT_QUOTES, 'UTF-8'); ?>;margin-right:5px;"></span>
                                        <?php echo $this->escape($item->category_title ?: '-'); ?>
                                    </td>
                                    <td>
                                        <?php echo $item->id; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>
                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
