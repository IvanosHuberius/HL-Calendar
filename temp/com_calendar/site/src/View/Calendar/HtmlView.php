<?php
/**
 * @license GNU/GPL v2 or later
 * @copyright (c) 2026 huberlabs.ch
 */


namespace Jewe\Component\Calendar\Site\View\Calendar;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;

class HtmlView extends BaseHtmlView
{
    protected $categories;
    protected $canEdit;

    public function display($tpl = null): void
    {
        $this->categories = $this->get('Categories');
        $this->canEdit = $this->get('CanEdit');

        parent::display($tpl);
    }
}
