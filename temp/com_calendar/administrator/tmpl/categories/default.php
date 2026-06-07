<?php
/**
 * @license GNU/GPL v2 or later
 * @copyright (c) 2026 huberlabs.ch
 */


defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Jewe\Component\Calendar\Administrator\View\Categories\HtmlView $this */
?>
<form action="<?php echo Route::_('index.php?option=com_calendar&view=categories'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table" id="categoryList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_CALENDAR_CATEGORIES_TABLE_CAPTION'); ?>
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
                                <th scope="col" class="w-10 text-center">
                                    <?php echo Text::_('COM_CALENDAR_FIELD_COLOR'); ?>
                                </th>
                                <th scope="col" class="w-10 text-center">
                                    <?php echo Text::_('COM_CALENDAR_EVENT_COUNT'); ?>
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
                                        <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'categories.'); ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo Route::_('index.php?option=com_calendar&task=category.edit&id=' . $item->id); ?>">
                                            <?php echo $this->escape($item->title); ?>
                                        </a>
                                        <?php if ($item->description) : ?>
                                            <div class="small text-muted"><?php echo $this->escape($item->description); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php $safeColor = preg_match('/^#[0-9a-fA-F]{3,6}$/', $item->color) ? $item->color : '#000000'; ?>
                                        <span style="display:inline-block;width:24px;height:24px;border-radius:4px;background:<?php echo htmlspecialchars($safeColor, ENT_QUOTES, 'UTF-8'); ?>;border:1px solid rgba(0,0,0,.1);"></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?php echo (int) $item->event_count; ?></span>
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
