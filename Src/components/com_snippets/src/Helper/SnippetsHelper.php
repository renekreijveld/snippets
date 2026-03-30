<?php
/**
 * @version    1.0.3
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

namespace Snippets\Component\Snippets\Site\Helper;

defined('_JEXEC') or die;

use \Joomla\CMS\Factory;

/**
 * Class SnippetsFrontendHelper
 *
 * @since  1.0.0
 */
class SnippetsHelper
{

	/**
	 * Get category name using category ID
	 * @param integer $category_id Category ID
	 * @return mixed category name if the category was found, null otherwise
	 */
	public static function getCategoryNameByCategoryId($category_id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query
			->select('title')
			->from('#__categories')
			->where('id = ' . intval($category_id));

		$db->setQuery($query);
		return $db->loadResult();
	}

	/**
	 * Gets the files attached to an item
	 *
	 * @param   int     $pk     The item's id
	 *
	 * @param   string  $table  The table's name
	 *
	 * @param   string  $field  The field's name
	 *
	 * @return  array  The files
	 */
	public static function getFiles($pk, $table, $field)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query
			->select($field)
			->from($table)
			->where('id = ' . (int) $pk);

		$db->setQuery($query);

		return explode(',', $db->loadResult());
	}

	/**
	 * Gets the edit permission for an user
	 *
	 * @param   mixed  $item  The item
	 *
	 * @return  bool
	 */
	public static function canUserEdit($item)
	{
		$permission = false;
		$user = Factory::getApplication()->getIdentity();

		if ($user->authorise('core.edit', 'com_snippets') || (isset($item->created_by) && $user->authorise('core.edit.own', 'com_snippets') && $item->created_by == $user->id) || $user->authorise('core.create', 'com_snippets')) {
			$permission = true;
		}

		return $permission;
	}
}
