<?php

/**
 * @version    1.0.7
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

namespace Snippets\Component\Snippets\Site\View\Categoryform;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * View class for the category edit form.
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
	 * The form object.
	 *
	 * @var  \Joomla\CMS\Form\Form
	 */
	protected $form;

	/**
	 * The component parameters.
	 *
	 * @var  \Joomla\Registry\Registry
	 */
	protected $params;

	/**
	 * Whether the user can save.
	 *
	 * @var  bool
	 */
	protected $canSave;

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

		$this->state   = $this->get('State');
		$this->item    = $this->get('Item');
		$this->params  = $app->getParams('com_snippets');
		$this->canSave = $this->get('CanSave');
		$this->form    = $this->get('Form');

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

		$title = $this->params->get('page_title', '');

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
