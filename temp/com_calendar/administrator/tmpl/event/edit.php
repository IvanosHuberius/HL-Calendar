<?php
/**
 * @license GNU/GPL v2 or later
 * @copyright (c) 2026 huberlabs.ch
 */


defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Jewe\Component\Calendar\Administrator\View\Event\HtmlView $this */

/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');
?>
<form action="<?php echo Route::_('index.php?option=com_calendar&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="row">
        <div class="col-lg-8">
            <?php echo $this->form->renderField('title'); ?>
            <?php echo $this->form->renderField('description'); ?>
            <?php echo $this->form->renderField('location'); ?>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <?php echo $this->form->renderField('state'); ?>
                    <?php echo $this->form->renderField('access'); ?>
                    <?php echo $this->form->renderField('category_id'); ?>
                    <?php echo $this->form->renderField('color'); ?>
                    <?php echo $this->form->renderField('all_day'); ?>
                    <?php echo $this->form->renderField('start_date'); ?>
                    <?php echo $this->form->renderField('end_date'); ?>
                    <?php echo $this->form->renderField('recurrence_type'); ?>
                    <?php echo $this->form->renderField('recurrence_interval'); ?>
                    <?php echo $this->form->renderField('recurrence_end'); ?>
                    <?php echo $this->form->renderField('reminder_minutes'); ?>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
