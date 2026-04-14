<?php
/**
 * @version    1.0.8
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

namespace Snippets\Component\Snippets\Administrator\Table;
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Access\Access;
use \Joomla\CMS\Table\Table as Table;
use \Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use \Joomla\Database\DatabaseDriver;
use \Joomla\Database\ParameterType;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\Registry\Registry;

/**
 * Snippet table
 *
 * @since 1.0.0
 */
class SnippetTable extends Table implements VersionableTableInterface, TaggableTableInterface
{
	use TaggableTableTrait;

	/**
	 * Indicates that columns fully support the NULL value in the database
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	protected $_supportNullValue = true;

	/**
	 * Check if the alias is unique within the same categories.
	 *
	 * Uses FIND_IN_SET to correctly match category IDs in the comma-separated cat_id column.
	 *
	 * @param   string  $alias  The alias to check for uniqueness.
	 *
	 * @return  bool  True if the alias is unique within the assigned categories.
	 */
	private function isAliasUniqueInCategories(string $alias): bool
	{
		$db = $this->_db;
		$query = $db->getQuery(true);
		$snippetId = (int) $this->{$this->_tbl_key};

		$query
			->select($db->quoteName('id'))
			->from($db->quoteName($this->_tbl))
			->where($db->quoteName('alias') . ' = :alias')
			->where($db->quoteName('id') . ' <> :snippetId')
			->bind(':alias', $alias)
			->bind(':snippetId', $snippetId, ParameterType::INTEGER);

		$categories = array_filter(explode(',', $this->cat_id));

		if (!empty($categories)) {
			$findInSetConditions = [];

			foreach ($categories as $index => $categoryId) {
				$paramName = ':catId' . $index;
				$categoryIdInt = (int) $categoryId;
				$findInSetConditions[] = 'FIND_IN_SET(' . $paramName . ', ' . $db->quoteName('cat_id') . ')';
				$query->bind($paramName, $categoryIdInt, ParameterType::INTEGER);
			}

			$query->andWhere($findInSetConditions);
		}

		$db->setQuery($query);
		$db->execute();

		return $db->getNumRows() === 0;
	}

	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = 'com_snippets.snippet';
		parent::__construct('#__snippets', 'id', $db);
		$this->setColumnAlias('published', 'state');

	}

	/**
	 * Get the type alias for the history table
	 *
	 * @return  string  The alias as described above
	 *
	 * @since   1.0.0
	 */
	public function getTypeAlias()
	{
		return $this->typeAlias;
	}

	/**
	 * Overloaded bind function to pre-process the params.
	 *
	 * @param   array  $array   Named array
	 * @param   mixed  $ignore  Optional array or list of parameters to ignore
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     Table:bind
	 * @since   1.0.0
	 * @throws  \InvalidArgumentException
	 */
	public function bind($array, $ignore = '')
	{
		$date = Factory::getDate();
		$task = Factory::getApplication()->input->get('task');
		$user = Factory::getApplication()->getIdentity();

		$input = Factory::getApplication()->input;
		$task = $input->getString('task', '');

		if ($array['id'] == 0 && empty($array['created_by'])) {
			$array['created_by'] = Factory::getUser()->id;
		}

		if ($array['id'] == 0 && empty($array['modified_by'])) {
			$array['modified_by'] = Factory::getUser()->id;
		}

		if ($task == 'apply' || $task == 'save') {
			$array['modified_by'] = Factory::getUser()->id;
		}

		// Support for multiple field: cat_id
		if (isset($array['cat_id'])) {
			if (is_array($array['cat_id'])) {
				$array['cat_id'] = implode(',', $array['cat_id']);
			} elseif (strpos($array['cat_id'], ',') != false) {
				$array['cat_id'] = explode(',', $array['cat_id']);
			} elseif (strlen($array['cat_id']) == 0) {
				$array['cat_id'] = '';
			}
		} else {
			$array['cat_id'] = '';
		}

		// Support for alias field: alias
		if (empty($array['alias'])) {
			if (empty($array['title'])) {
				$array['alias'] = OutputFilter::stringURLSafe(date('Y-m-d H:i:s'));
			} else {
				if (Factory::getConfig()->get('unicodeslugs') == 1) {
					$array['alias'] = OutputFilter::stringURLUnicodeSlug(trim($array['title']));
				} else {
					$array['alias'] = OutputFilter::stringURLSafe(trim($array['title']));
				}
			}
		}

		if (isset($array['params']) && is_array($array['params'])) {
			$registry = new Registry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

		if (isset($array['metadata']) && is_array($array['metadata'])) {
			$registry = new Registry;
			$registry->loadArray($array['metadata']);
			$array['metadata'] = (string) $registry;
		}

		if (!$user->authorise('core.admin', 'com_snippets.snippet.' . $array['id'])) {
			$actions = Access::getActionsFromFile(
				JPATH_ADMINISTRATOR . '/components/com_snippets/access.xml',
				"/access/section[@name='snippet']/"
			);
			$default_actions = Access::getAssetRules('com_snippets.snippet.' . $array['id'])->getData();
			$array_jaccess = array();

			foreach ($actions as $action) {
				if (key_exists($action->name, $default_actions)) {
					$array_jaccess[$action->name] = $default_actions[$action->name];
				}
			}

			$array['rules'] = $this->JAccessRulestoArray($array_jaccess);
		}

		// Bind the rules for ACL where supported.
		if (isset($array['rules']) && is_array($array['rules'])) {
			$this->setRules($array['rules']);
		}

		return parent::bind($array, $ignore);
	}

	/**
	 * Method to store a row in the database from the Table instance properties.
	 *
	 * If a primary key value is set the row with that primary key value will be updated with the instance property values.
	 * If no primary key value is set a new row will be inserted into the database with the properties from the Table instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0.0
	 */
	public function store($updateNulls = true)
	{
		$isNew = empty($this->id);

		if (!parent::store($updateNulls)) {
			return false;
		}

		// For new snippets, the ID is now available. Check alias uniqueness and update if needed.
		if ($isNew && !$this->isAliasUniqueInCategories($this->alias)) {
			$this->alias = $this->alias . '-' . $this->id;

			if (!parent::store($updateNulls)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * This function convert an array of Access objects into an rules array.
	 *
	 * @param   array  $jaccessrules  An array of Access objects.
	 *
	 * @return  array
	 */
	private function JAccessRulestoArray($jaccessrules)
	{
		$rules = array();

		foreach ($jaccessrules as $action => $jaccess) {
			$actions = array();

			if ($jaccess) {
				foreach ($jaccess->getData() as $group => $allow) {
					$actions[$group] = ((bool) $allow);
				}
			}

			$rules[$action] = $actions;
		}

		return $rules;
	}

	/**
	 * Overloaded check function
	 *
	 * @return bool
	 */
	public function check()
	{
		// If there is an ordering column and this is a new row then get the next ordering value
		if (property_exists($this, 'ordering') && $this->id == 0) {
			$this->ordering = self::getNextOrder();
		}

		// Check if alias is unique within the same categories.
		// For existing snippets, append the snippet ID to make it unique.
		// For new snippets (id=0), uniqueness is enforced after store() assigns the ID.
		if ($this->id > 0 && !$this->isAliasUniqueInCategories($this->alias)) {
			$this->alias = $this->alias . '-' . $this->id;
		}

		return parent::check();
	}

	/**
	 * Define a namespaced asset name for inclusion in the #__assets table
	 *
	 * @return string The asset name
	 *
	 * @see Table::_getAssetName
	 */
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;

		return $this->typeAlias . '.' . (int) $this->$k;
	}

	/**
	 * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
	 *
	 * @param   Table   $table  Table name
	 * @param   integer  $id     Id
	 *
	 * @see Table::_getAssetParentId
	 *
	 * @return mixed The id on success, false on failure.
	 */
	protected function _getAssetParentId($table = null, $id = null)
	{
		// We will retrieve the parent-asset from the Asset-table
		$assetParent = Table::getInstance('Asset');

		// Default: if no asset-parent can be found we take the global asset
		$assetParentId = $assetParent->getRootId();

		// The item has the component as asset-parent
		$assetParent->loadByName('com_snippets');

		// Return the found asset-parent-id
		if ($assetParent->id) {
			$assetParentId = $assetParent->id;
		}

		return $assetParentId;
	}

	//XXX_CUSTOM_TABLE_FUNCTION


	/**
	 * Delete a record by id
	 *
	 * @param   mixed  $pk  Primary key value to delete. Optional
	 *
	 * @return bool
	 */
	public function delete($pk = null)
	{
		$this->load($pk);
		$result = parent::delete($pk);

		return $result;
	}
}
