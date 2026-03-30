<?php

/**
 * @version    1.0.2
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

namespace Snippets\Component\Snippets\Site\View\Categories;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * View class for a list of snippet categories.
 *
 * @since  1.0.0
 */
class HtmlView extends BaseHtmlView
{
	/**
	 * An array of items.
	 *
	 * @var  array
	 */
	protected $items;

	/**
	 * The pagination object.
	 *
	 * @var  \Joomla\CMS\Pagination\Pagination
	 */
	protected $pagination;

	/**
	 * The model state.
	 *
	 * @var  \Joomla\CMS\Object\CMSObject
	 */
	protected $state;

	/**
	 * The component parameters.
	 *
	 * @var  \Joomla\Registry\Registry
	 */
	protected $params;

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
	public function display($tpl = null): void
	{
		$app = Factory::getApplication();

		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->params = $app->getParams('com_snippets');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			throw new \Exception(implode("\n", $errors));
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
	protected function _prepareDocument(): void
	{
		$app = Factory::getApplication();
		$menus = $app->getMenu();
		$title = null;

		$menu = $menus->getActive();

		if ($menu) {
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		} else {
			$this->params->def('page_heading', Text::_('SNIPPETS_CATEGORIES_PAGE_TITLE'));
		}

		$title = $this->params->get('page_title', '');

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

	/**
	 * Check if state is set.
	 *
	 * @param   mixed  $state  State key.
	 *
	 * @return  mixed  The state value or false.
	 *
	 * @since   1.0.0
	 */
	public function getState($state)
	{
		return isset($this->state->{$state}) ? $this->state->{$state} : false;
	}
}
