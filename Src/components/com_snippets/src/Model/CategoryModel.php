<?php
/**
 * @version    1.0.2
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
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Category model for the frontend.
 *
 * @since  1.0.0
 */
class CategoryModel extends ItemModel
{
	/**
	 * The item object.
	 *
	 * @var    object|null
	 * @since  1.0.0
	 */
	public $_item;

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
		$user = $app->getIdentity();

		// Check published state.
		if ((!$user->authorise('core.edit.state', 'com_snippets')) && (!$user->authorise('core.edit', 'com_snippets'))) {
			$this->setState('filter.published', 1);
		}

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
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @throws  \Exception
	 *
	 * @since   1.0.0
	 */
	public function getItem($id = null): mixed
	{
		if ($this->_item === null) {
			$this->_item = false;

			if (empty($id)) {
				$id = $this->getState('category.id');
			}

			// Get a level row instance.
			$table = $this->getTable();

			// Attempt to load the row.
			if ($table && $table->load($id)) {
				// Verify this is a category for our extension.
				if ($table->extension !== 'com_snippets.snippets') {
					throw new \Exception(Text::_('SNIPPETS_CATEGORY_DOESNT_EXIST'), 404);
				}

				// Check published state.
				if ($published = $this->getState('filter.published')) {
					if (isset($table->published) && $table->published != $published) {
						throw new \Exception(Text::_('SNIPPETS_CATEGORY_NOT_LOADED'), 403);
					}
				}

				// Convert the Table to a clean CMSObject.
				$properties = $table->getProperties(1);
				$this->_item = ArrayHelper::toObject($properties, CMSObject::class);
			}

			if (empty($this->_item)) {
				throw new \Exception(Text::_('SNIPPETS_CATEGORY_DOESNT_EXIST'), 404);
			}
		}

		// Resolve created_user_id to a username.
		$container = Factory::getContainer();
		$userFactory = $container->get(UserFactoryInterface::class);

		if (isset($this->_item->created_user_id) && $this->_item->created_user_id) {
			$createdUser = $userFactory->loadUserById($this->_item->created_user_id);
			$this->_item->created_user_name = $createdUser->name;
		}

		if (isset($this->_item->modified_user_id) && $this->_item->modified_user_id) {
			$modifiedUser = $userFactory->loadUserById($this->_item->modified_user_id);
			$this->_item->modified_user_name = $modifiedUser->name;
		}

		// Load snippets belonging to this category.
		$this->_item->snippets = $this->getSnippetsForCategory((int) $this->_item->id);

		return $this->_item;
	}

	/**
	 * Load published snippets that belong to the given category.
	 *
	 * @param   int  $categoryId  The category ID.
	 *
	 * @return  array  Array of snippet objects.
	 *
	 * @since   1.0.0
	 */
	public function getSnippetsForCategory(int $categoryId): array
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true);

		$query->select($db->quoteName(['id', 'title', 'alias', 'state', 'cat_id']))
			->from($db->quoteName('#__snippets'))
			->where('FIND_IN_SET(:catId, ' . $db->quoteName('cat_id') . ')')
			->bind(':catId', $categoryId, ParameterType::INTEGER)
			->where($db->quoteName('state') . ' = 1')
			->order($db->quoteName('title') . ' ASC');

		$db->setQuery($query);

		return $db->loadObjectList() ?: [];
	}

	/**
	 * Get an instance of Table class.
	 *
	 * @param   string  $type    Name of the Table class to get an instance of.
	 * @param   string  $prefix  Prefix for the table class name. Optional.
	 * @param   array   $config  Array of configuration values for the Table object. Optional.
	 *
	 * @return  Table|bool  Table if success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function getTable($type = 'Category', $prefix = '\\Joomla\\CMS\\Table\\', $config = array()): Table|bool
	{
		return Table::getInstance($type, $prefix, $config);
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
	 * Publish or unpublish the category.
	 *
	 * @param   int  $id     Item id.
	 * @param   int  $state  Publish state.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function publish(int $id, int $state): bool
	{
		$table = $this->getTable();
		$table->load($id);
		$table->published = $state;

		return $table->store();
	}

	/**
	 * Method to delete an item.
	 *
	 * @param   int  $id  Element id.
	 *
	 * @return  bool  True on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function delete(int $id): bool
	{
		$table = $this->getTable();

		return $table->delete($id);
	}
}
