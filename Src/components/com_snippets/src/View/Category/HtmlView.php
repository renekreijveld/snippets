<?php

/**
 * @version    1.0.8
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

namespace Snippets\Component\Snippets\Site\View\Category;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Snippets\Component\Snippets\Site\Model\CategoriesModel;
use Snippets\Component\Snippets\Site\Model\CategoryModel;

/**
 * View class for a single snippet category.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * The model state.
	 *
	 * @var  \Joomla\CMS\Object\CMSObject
	 */
	protected $state;

	/**
	 * The item object.
	 *
	 * @var  object
	 */
	protected $item;

	/**
	 * The component parameters.
	 *
	 * @var  \Joomla\Registry\Registry
	 */
	protected $params;

	/**
	 * @var CategoryModel
	 */
	public $categoryModel;

	/**
	 * @var CategoriesModel
	 */
	public       $categoriesModel;
	public array $categories      = [];

	/**
	 * Display the view.
	 *
	 * @param   string  $tpl  Template name.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 *
	 * @since   1.0.0
	 */
	public function display($tpl = null) : void
	{
		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		$this->model           = $this->getModel('Category');
		$this->categoriesModel = $this->getModel('Categories');
		$this->item            = $this->model->getItem();
		$this->categories      = $this->categoriesModel->getItems();
		$this->state           = $this->model->getState();
		$this->params          = $app->getParams('com_snippets');

		// Check for errors.
		if (count($errors = $this->model->getErrors())) {
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
	 * Prepares the document.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 *
	 * @since   1.0.0
	 */
	protected function _prepareDocument() : void
	{
		$app   = Factory::getApplication();
		$menus = $app->getMenu();
		$title = null;

		$menu = $menus->getActive();

		if ($menu) {
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else {
			$this->params->def('page_heading', Text::_('SNIPPETS_CATEGORY_PAGE_TITLE'));
		}

		$title = $this->item->title ?? $this->params->get('page_title', '');

		if (empty($title)) {
			$title = $app->get('sitename');
		}
		elseif ($app->get('sitename_pagetitles', 0) == 1) {
			$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2) {
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
