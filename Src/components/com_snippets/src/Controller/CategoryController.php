<?php
/**
 * @version    1.0.8
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

namespace Snippets\Component\Snippets\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/**
 * Category detail/action controller.
 *
 * @since  1.0.0
 */
class CategoryController extends BaseController
{
	/**
	 * Method to check out an item for editing and redirect to the edit form.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 *
	 * @throws  \Exception
	 */
	public function edit() : void
	{
		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $this->app->getUserState('com_snippets.edit.category.id');
		$editId     = $this->input->getInt('id', 0);

		// Set the user id for the user to edit in the session.
		$this->app->setUserState('com_snippets.edit.category.id', $editId);

		// Get the model.
		$model = $this->getModel('Category', 'Site');

		// Check out the item.
		if ($editId) {
			$model->checkout($editId);
		}

		// Check in the previous user.
		if ($previousId && $previousId !== $editId) {
			$model->checkin($previousId);
		}

		// Redirect to the edit screen.
		$this->setRedirect(Route::_('index.php?option=com_snippets&view=categoryform&layout=edit', false));
	}

	/**
	 * Method to publish or unpublish a category.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 *
	 * @since   1.0.0
	 */
	public function publish() : void
	{
		$user = $this->app->getIdentity();

		if ($user->authorise('core.edit', 'com_snippets') || $user->authorise('core.edit.state', 'com_snippets')) {
			$model = $this->getModel('Category', 'Site');

			$id    = $this->input->getInt('id');
			$state = $this->input->getInt('state');

			$return = $model->publish($id, $state);

			if ($return === false) {
				$this->setMessage(Text::sprintf('Save failed: %s', $model->getError()), 'warning');
			}

			// Clear the profile id from the session.
			$this->app->setUserState('com_snippets.edit.category.id', null);

			// Flush the data from the session.
			$this->app->setUserState('com_snippets.edit.category.data', null);

			// Redirect to the list screen.
			$this->setMessage(Text::_('SNIPPETS_CATEGORY_SAVED_SUCCESSFULLY'));
			$menu = Factory::getApplication()->getMenu();
			$item = $menu->getActive();

			if (!$item) {
				$this->setRedirect(Route::_('index.php?option=com_snippets&view=categories', false));
			}
			else {
				$this->setRedirect(Route::_('index.php?Itemid=' . $item->id, false));
			}
		}
		else {
			throw new \Exception(500);
		}
	}

	/**
	 * Check in record.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0.0
	 */
	public function checkin() : bool
	{
		// Check for request forgeries.
		$this->checkToken('GET');

		$id    = $this->input->getInt('id', 0);
		$model = $this->getModel();
		$item  = $model->getItem($id);

		$user = $this->app->getIdentity();

		if ($user->authorise('core.manage', 'com_snippets') || $item->checked_out == $user->id) {
			$return = $model->checkin($id);

			if ($return === false) {
				$message = Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError());
				$this->setRedirect(Route::_('index.php?option=com_snippets&view=category' . '&id=' . $id, false), $message, 'error');

				return false;
			}
			else {
				$message = Text::_('SNIPPETS_CHECKEDIN_SUCCESSFULLY');
				$this->setRedirect(Route::_('index.php?option=com_snippets&view=category' . '&id=' . $id, false), $message);

				return true;
			}
		}
		else {
			throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	/**
	 * Remove data.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 *
	 * @since   1.0.0
	 */
	public function remove() : void
	{
		$user = $this->app->getIdentity();

		if ($user->authorise('core.delete', 'com_snippets')) {
			$model = $this->getModel('Category', 'Site');

			$id = $this->input->getInt('id', 0);

			$return = $model->delete($id);

			if ($return === false) {
				$this->setMessage(Text::sprintf('Delete failed', $model->getError()), 'warning');
			}
			else {
				if ($return) {
					$model->checkin($return);
				}

				$this->app->setUserState('com_snippets.edit.category.id', null);
				$this->app->setUserState('com_snippets.edit.category.data', null);

				$this->app->enqueueMessage(Text::_('SNIPPETS_CATEGORY_DELETED_SUCCESSFULLY'), 'success');
				$this->app->redirect(Route::_('index.php?option=com_snippets&view=categories', false));
			}

			$menu = Factory::getApplication()->getMenu();
			$item = $menu->getActive();
			$this->setRedirect(Route::_($item->link, false));
		}
		else {
			throw new \Exception(500);
		}
	}

}
