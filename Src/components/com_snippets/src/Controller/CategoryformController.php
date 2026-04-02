<?php
/**
 * @version    1.0.4
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

namespace Snippets\Component\Snippets\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Category form controller.
 *
 * @since  1.0.0
 */
class CategoryformController extends FormController
{
	/**
	 * Method to check out an item for editing and redirect to the edit form.
	 *
	 * @param   string|null  $key     The name of the primary key of the URL variable. Optional.
	 * @param   string|null  $urlVar  The name of the URL variable. Optional.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 *
	 * @throws  \Exception
	 */
	public function edit($key = null, $urlVar = null): void
	{
		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $this->app->getUserState('com_snippets.edit.category.id');
		$editId = $this->input->getInt('id', 0);

		// Set the user id for the user to edit in the session.
		$this->app->setUserState('com_snippets.edit.category.id', $editId);

		// Get the model.
		$model = $this->getModel('Categoryform', 'Site');

		// Check out the item.
		if ($editId) {
			$model->checkout($editId);
		}

		// Check in the previous user.
		if ($previousId) {
			$model->checkin($previousId);
		}

		// Redirect to the edit screen.
		$this->setRedirect(Route::_('index.php?option=com_snippets&view=categoryform&layout=edit', false));
	}

	/**
	 * Method to save data.
	 *
	 * @param   string|null  $key     The name of the primary key of the URL variable. Optional.
	 * @param   string|null  $urlVar  The name of the URL variable. Optional.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 *
	 * @since   1.0.0
	 */
	public function save($key = null, $urlVar = null): void
	{
		// Check for request forgeries.
		$this->checkToken();

		// Initialise variables.
		$model = $this->getModel('Categoryform', 'Site');

		// Get the user data.
		$data = $this->input->get('jform', array(), 'array');

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form) {
			throw new \Exception($model->getError(), 500);
		}

		// Send an object which can be modified through the plugin event.
		$objData = (object) $data;
		$this->app->triggerEvent(
			'onContentNormaliseRequestData',
			array($this->option . '.' . $this->context, $objData, $form)
		);

		$data = (array) $objData;

		// Validate the posted data.
		$data = $model->validate($form, $data);

		// Check for errors.
		if ($data === false) {
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
				if ($errors[$i] instanceof \Exception) {
					$this->app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				} else {
					$this->app->enqueueMessage($errors[$i], 'warning');
				}
			}

			$jform = $this->input->get('jform', array(), 'ARRAY');

			// Save the data in the session.
			$this->app->setUserState('com_snippets.edit.category.data', $jform);

			// Redirect back to the edit screen.
			$id = (int) $this->app->getUserState('com_snippets.edit.category.id');
			$this->setRedirect(Route::_('index.php?option=com_snippets&view=categoryform&layout=edit&id=' . $id, false));

			$this->redirect();
		}

		// Attempt to save the data.
		$return = $model->save($data);

		// Check for errors.
		if ($return === false) {
			// Save the data in the session.
			$this->app->setUserState('com_snippets.edit.category.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $this->app->getUserState('com_snippets.edit.category.id');
			$this->setMessage(Text::sprintf('Save failed', $model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_snippets&view=categoryform&layout=edit&id=' . $id, false));
			$this->redirect();
		}

		// Check in the profile.
		if ($return) {
			$model->checkin($return);
		}

		// Clear the profile id from the session.
		$this->app->setUserState('com_snippets.edit.category.id', null);

		// Redirect to the list screen.
		if (!empty($return)) {
			$this->setMessage(Text::_('SNIPPETS_CATEGORY_SAVED_SUCCESSFULLY'));
		}

		$menu = Factory::getApplication()->getMenu();
		$item = $menu->getActive();
		$url = (empty($item->link) ? 'index.php?option=com_snippets&view=categories' : $item->link);
		$this->setRedirect(Route::_($url, false));

		// Flush the data from the session.
		$this->app->setUserState('com_snippets.edit.category.data', null);

		// Invoke the postSave method to allow for the child class to access the model.
		$this->postSaveHook($model, $data);
	}

	/**
	 * Method to abort current operation.
	 *
	 * @param   string|null  $key  The name of the primary key of the URL variable. Optional.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 *
	 * @since   1.0.0
	 */
	public function cancel($key = null): void
	{
		// Get the current edit id.
		$editId = (int) $this->app->getUserState('com_snippets.edit.category.id');

		// Get the model.
		$model = $this->getModel('Categoryform', 'Site');

		// Check in the item.
		if ($editId) {
			$model->checkin($editId);
		}

		$this->setRedirect(Route::_('index.php?option=com_snippets&view=categories', false));
	}

	/**
	 * Method to remove data.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 *
	 * @since   1.0.0
	 */
	public function remove(): void
	{
		$model = $this->getModel('Categoryform', 'Site');
		$pk = $this->input->getInt('id');

		try {
			// Check in before delete.
			$return = $model->checkin($pk);
			// Clear id from the session.
			$this->app->setUserState('com_snippets.edit.category.id', null);

			$menu = $this->app->getMenu();
			$item = $menu->getActive();
			$url = (empty($item->link) ? 'index.php?option=com_snippets&view=categories' : $item->link);

			if ($return) {
				$model->delete($pk);
				$this->setMessage(Text::_('SNIPPETS_CATEGORY_DELETED_SUCCESSFULLY'));
			} else {
				$this->setMessage(Text::_('SNIPPETS_CATEGORY_DELETED_UNSUCCESSFULLY'), 'warning');
			}

			$this->setRedirect(Route::_($url, false));
			// Flush the data from the session.
			$this->app->setUserState('com_snippets.edit.category.data', null);
		} catch (\Exception $e) {
			$errorType = ($e->getCode() == '404') ? 'error' : 'warning';
			$this->setMessage($e->getMessage(), $errorType);
			$this->setRedirect('index.php?option=com_snippets&view=categories');
		}
	}

	/**
	 * Function that allows child controller access to model data
	 * after the data has been saved.
	 *
	 * @param   BaseDatabaseModel  $model      The data model object.
	 * @param   array              $validData  The validated data.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function postSaveHook(BaseDatabaseModel $model, $validData = array()): void
	{
	}
}
