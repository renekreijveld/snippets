<?php
/**
 * @version    1.0.3
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

namespace Snippets\Component\Snippets\Site\Model;

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Object\CMSObject;

/**
 * Category form model for the frontend.
 *
 * @since  1.0.0
 */
class CategoryformModel extends FormModel
{
	/**
	 * The item object.
	 *
	 * @var    object|null
	 * @since  1.0.0
	 */
	private $item = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 *
	 * @throws  \Exception
	 */
	protected function populateState(): void
	{
		$app = Factory::getApplication('com_snippets');

		// Load state from the request userState on edit or from the passed variable on default.
		if (Factory::getApplication()->input->get('layout') == 'edit') {
			$id = Factory::getApplication()->getUserState('com_snippets.edit.category.id');
		} else {
			$id = Factory::getApplication()->input->get('id');
			Factory::getApplication()->setUserState('com_snippets.edit.category.id', $id);
		}

		$this->setState('category.id', $id);

		// Load the parameters.
		$params = $app->getParams();
		$params_array = $params->toArray();

		if (isset($params_array['item_id'])) {
			$this->setState('category.id', $params_array['item_id']);
		}

		$this->setState('params', $params);
	}

	/**
	 * Method to get an object.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return  object|boolean  Object on success, false on failure.
	 *
	 * @throws  \Exception
	 *
	 * @since   1.0.0
	 */
	public function getItem($id = null): object|bool
	{
		if ($this->item === null) {
			$this->item = false;

			if (empty($id)) {
				$id = $this->getState('category.id');
			}

			// Get a level row instance.
			$table = $this->getTable();
			$properties = $table->getProperties();
			$this->item = ArrayHelper::toObject($properties, CMSObject::class);

			if ($table !== false && $table->load($id) && !empty($table->id)) {
				// Verify this is a category for our extension.
				if ($table->extension !== 'com_snippets.snippets') {
					throw new \Exception(Text::_('SNIPPETS_CATEGORY_DOESNT_EXIST'), 404);
				}

				$user = Factory::getApplication()->getIdentity();
				$id = $table->id;

				$canEdit = $user->authorise('core.edit', 'com_snippets') || $user->authorise('core.create', 'com_snippets');

				if (!$canEdit && $user->authorise('core.edit.own', 'com_snippets')) {
					$canEdit = $user->id == $table->created_user_id;
				}

				if (!$canEdit) {
					throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
				}

				// Check published state.
				if ($published = $this->getState('filter.published')) {
					if (isset($table->published) && $table->published != $published) {
						return $this->item;
					}
				}

				// Convert the Table to a clean CMSObject.
				$properties = $table->getProperties(1);
				$this->item = ArrayHelper::toObject($properties, CMSObject::class);
			}
		}

		return $this->item;
	}

	/**
	 * Method to get the table.
	 *
	 * @param   string  $type    Name of the Table class.
	 * @param   string  $prefix  Optional prefix for the table class name.
	 * @param   array   $config  Optional configuration array for Table object.
	 *
	 * @return  Table|boolean  Table if found, boolean false on failure.
	 *
	 * @since   1.0.0
	 */
	public function getTable($type = 'Category', $prefix = '\\Joomla\\CMS\\Table\\', $config = array()): Table|bool
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the category form.
	 *
	 * The base form is loaded from XML.
	 *
	 * @param   array    $data      An optional array of data for the form to interrogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \Joomla\CMS\Form\Form|false  A Form object on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = array(), $loadData = true): \Joomla\CMS\Form\Form|false
	{
		$form = $this->loadForm(
			'com_snippets.category',
			'categoryform',
			array(
				'control' => 'jform',
				'load_data' => $loadData,
			)
		);

		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  The default data is an empty array.
	 *
	 * @since   1.0.0
	 */
	protected function loadFormData(): mixed
	{
		$data = Factory::getApplication()->getUserState('com_snippets.edit.category.data', array());

		if (empty($data)) {
			$data = $this->getItem();
		}

		if ($data) {
			return $data;
		}

		return array();
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  int|false  The category ID on success, false on failure.
	 *
	 * @throws  \Exception
	 *
	 * @since   1.0.0
	 */
	public function save($data): int|false
	{
		$id = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('category.id');
		$user = Factory::getApplication()->getIdentity();

		if ($id) {
			// Check the user can edit this item.
			$authorised = $user->authorise('core.edit', 'com_snippets') || $user->authorise('core.edit.own', 'com_snippets');
		} else {
			// Check the user can create new items in this section.
			$authorised = $user->authorise('core.create', 'com_snippets');
		}

		if ($authorised !== true) {
			throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// Force extension to ensure the category belongs to this component.
		$data['extension'] = 'com_snippets.snippets';

		// Set default language if not specified.
		if (empty($data['language'])) {
			$data['language'] = '*';
		}

		// Handle parent_id for nested sets.
		if (empty($data['parent_id']) || $data['parent_id'] == 0) {
			$data['parent_id'] = 1;
		}

		$table = $this->getTable();

		if (!empty($id)) {
			$table->load($id);
		}

		// Set the location in the tree for nested set handling.
		$table->setLocation((int) $data['parent_id'], 'last-child');

		try {
			if ($table->save($data) === true) {
				return $table->id;
			} else {
				Factory::getApplication()->enqueueMessage($table->getError(), 'error');

				return false;
			}
		} catch (\Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return false;
		}
	}

	/**
	 * Method to delete data.
	 *
	 * @param   int  $id  Item primary key.
	 *
	 * @return  int  The id of the deleted item.
	 *
	 * @throws  \Exception
	 *
	 * @since   1.0.0
	 */
	public function delete(int $id): int
	{
		$user = Factory::getApplication()->getIdentity();

		if (empty($id)) {
			$id = (int) $this->getState('category.id');
		}

		if ($id == 0 || $this->getItem($id) == null) {
			throw new \Exception(Text::_('SNIPPETS_CATEGORY_DOESNT_EXIST'), 404);
		}

		if ($user->authorise('core.delete', 'com_snippets') !== true) {
			throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$table = $this->getTable();

		if ($table->delete($id) !== true) {
			throw new \Exception(Text::_('JERROR_FAILED'), 501);
		}

		return $id;
	}

	/**
	 * Method to check in an item.
	 *
	 * @param   integer  $id  The id of the row to check in.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function checkin($id = null): bool
	{
		$id = (!empty($id)) ? $id : (int) $this->getState('category.id');

		if ($id) {
			$table = $this->getTable();

			if (method_exists($table, 'checkin')) {
				if (!$table->checkin($id)) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to check out an item for editing.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function checkout($id = null): bool
	{
		$id = (!empty($id)) ? $id : (int) $this->getState('category.id');

		if ($id) {
			$table = $this->getTable();
			$user = Factory::getApplication()->getIdentity();

			if (method_exists($table, 'checkout')) {
				if (!$table->checkout($user->get('id'), $id)) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Check if data can be saved.
	 *
	 * @return  bool  True if the table instance is valid.
	 *
	 * @since   1.0.0
	 */
	public function getCanSave(): bool
	{
		$table = $this->getTable();

		return $table !== false;
	}
}
