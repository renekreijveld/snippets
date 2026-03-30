<?php

/**
 * @version    1.0.3
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

namespace Snippets\Component\Snippets\Site\View\Snippet;
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use Snippets\Component\Snippets\Site\Model\CategoriesModel;
use Snippets\Component\Snippets\Site\Model\CategoryModel;
use Snippets\Component\Snippets\Site\Model\SnippetModel;

/**
 * View class for a list of Snippets.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
    protected $state;

    protected $item;

    protected $form;

    protected $params;

    /**
     * @var CategoryModel
     */
    public $categoryModel;

    /**
     * @var CategoriesModel
     */
    public $categoriesModel;

    /**
     * @var SnippetModel
     */
    public $snippetModel;

    public array $categories = [];
    public object $category;

    /**
     * Display the view
     *
     * @param   string  $tpl  Template name
     *
     * @return void
     *
     * @throws Exception
     */
    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();

        $this->snippetModel = $this->getModel('Snippet');
        $this->categoryModel = $this->getModel('Category');
        $this->categoriesModel = $this->getModel('Categories');
        $this->item = $this->snippetModel->getItem();
        $this->categories = $this->categoriesModel->getItems();
        $this->snippets = $this->categoryModel->getSnippetsForCategory($this->item->cat_id);
        $this->state = $this->get('State');
        $this->params = $app->getParams('com_snippets');

        if (!empty($this->item)) {
            $this->form = $this->get('Form');
        }

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        if ($this->_layout == 'edit') {
            $authorised = $user->authorise('core.create', 'com_snippets');

            if ($authorised !== true) {
                throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'));
            }
        }

        $this->_prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document
     *
     * @return void
     *
     * @throws Exception
     */
    protected function _prepareDocument()
    {
        $app = Factory::getApplication();
        $menus = $app->getMenu();
        $title = null;

        // Because the application sets a default page title,
        // We need to get it from the menu item itself
        $menu = $menus->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', Text::_('SNIPPETS_DEFAULT_PAGE_TITLE'));
        }

        $title = $this->item->title ?? $this->params->get('page_title', '');

        if (empty($title)) {
            $title = $app->get('sitename');
        } elseif ($app->get('sitename_pagetitles', 0) == 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->document->setTitle($title);

        if ($this->params->get('menu-meta_description')) {
            $this->document->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('menu-meta_keywords')) {
            $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots')) {
            $this->document->setMetadata('robots', $this->params->get('robots'));
        }
    }
}
