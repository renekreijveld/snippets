<?php
/**
 * @version    1.0.5
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

namespace Snippets\Component\Snippets\Site\Model;

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\ParameterType;
use Joomla\Database\DatabaseInterface;

/**
 * Methods supporting a list of snippet categories.
 *
 * @since  1.0.0
 */
class CategoriesModel extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since  1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id',
				'a.id',
				'title',
				'a.title',
				'alias',
				'a.alias',
				'published',
				'a.published',
				'access',
				'a.access',
				'level',
				'a.level',
				'lft',
				'a.lft',
				'parent_id',
				'a.parent_id',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 *
	 * @since   1.0.0
	 */
	protected function populateState($ordering = null, $direction = null): void
	{
		// List state information.
		parent::populateState('a.title', 'ASC');

		$app = Factory::getApplication();
		$list = $app->getUserState($this->context . '.list');

		$value = $app->getUserState($this->context . '.list.limit', $app->get('list_limit', 25));
		$list['limit'] = $value;

		$this->setState('list.limit', $value);

		$value = $app->input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $value);

		$ordering = $this->getUserStateFromRequest($this->context . '.filter_order', 'filter_order', 'a.lft');
		$direction = strtoupper($this->getUserStateFromRequest($this->context . '.filter_order_Dir', 'filter_order_Dir', 'ASC'));

		if (!empty($ordering) || !empty($direction)) {
			$list['fullordering'] = $ordering . ' ' . $direction;
		}

		$app->setUserState($this->context . '.list', $list);

		$context = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $context);

		// Split context into component and optional section
		if (!empty($context)) {
			$parts = FieldsHelper::extract($context);

			if ($parts) {
				$this->setState('filter.component', $parts[0]);
				$this->setState('filter.section', $parts[1]);
			}
		}
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  \Joomla\Database\DatabaseQuery
	 *
	 * @since   1.0.0
	 */
	protected function getListQuery(): \Joomla\Database\DatabaseQuery
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'DISTINCT a.*'
			)
		);

		$query->from($db->quoteName('#__categories', 'a'));

		// Only show categories for this extension.
		$query->where($db->quoteName('a.extension') . ' = ' . $db->quote('com_snippets.snippets'));

		// Exclude root category.
		$query->where($db->quoteName('a.level') . ' > 0');

		// Join over the users for the checked out user.
		$query->select($db->quoteName('uc.name', 'uEditor'));
		$query->join('LEFT', $db->quoteName('#__users', 'uc') . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

		// Filter by published state.
		if (!Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_snippets')) {
			$query->where($db->quoteName('a.published') . ' = 1');
		} else {
			$query->where($db->quoteName('a.published') . ' IN (0, 1)');
		}

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$searchId = (int) substr($search, 3);
				$query->where($db->quoteName('a.id') . ' = :searchId')
					->bind(':searchId', $searchId, ParameterType::INTEGER);
			} else {
				$search = '%' . $db->escape($search, true) . '%';
				$query->where($db->quoteName('a.title') . ' LIKE ' . $db->quote($search));
			}
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering', 'a.title');
		$orderDirn = $this->state->get('list.direction', 'ASC');

		if ($orderCol && $orderDirn) {
			$query->order($db->quoteName('a.title'));
		}

		return $query;
	}

	/**
	 * Method to get an array of data items enriched with snippet counts.
	 *
	 * @return  mixed  An array of data on success, false on failure.
	 *
	 * @since   1.0.0
	 */
	public function getItems(): mixed
	{
		$items = parent::getItems();

		$db = Factory::getContainer()->get(DatabaseInterface::class);

		foreach ($items as $item) {
			// Count snippets assigned to this category using FIND_IN_SET.
			$categoryId = (int) $item->id;
			$query = $db->getQuery(true);

			$query->select('COUNT(*)')
				->from($db->quoteName('#__snippets'))
				->where('FIND_IN_SET(:catId, ' . $db->quoteName('cat_id') . ')')
				->bind(':catId', $categoryId, ParameterType::INTEGER);

			$db->setQuery($query);
			$item->snippet_count = (int) $db->loadResult();
		}

		return $items;
	}

	/**
	 * Overrides the default function to check Date fields format, identified by
	 * "_dateformat" suffix, and erases the field if it's not correct.
	 *
	 * @return  mixed
	 *
	 * @since   1.0.0
	 */
	protected function loadFormData(): mixed
	{
		$app = Factory::getApplication();
		$filters = $app->getUserState($this->context . '.filter', array());
		$error_dateformat = false;

		foreach ($filters as $key => $value) {
			if (strpos($key, '_dateformat') && !empty($value) && $this->isValidDate($value) == null) {
				$filters[$key] = '';
				$error_dateformat = true;
			}
		}

		if ($error_dateformat) {
			$app->enqueueMessage(Text::_('SNIPPETS_SEARCH_FILTER_DATE_FORMAT'), 'warning');
			$app->setUserState($this->context . '.filter', $filters);
		}

		return parent::loadFormData();
	}

	/**
	 * Checks if a given date is valid and in a specified format (YYYY-MM-DD).
	 *
	 * @param   string  $date  Date to be checked
	 *
	 * @return  string|null  The formatted date or null if invalid.
	 *
	 * @since   1.0.0
	 */
	private function isValidDate(string $date): ?string
	{
		$date = str_replace('/', '-', $date);

		return (date_create($date)) ? Factory::getDate($date)->format('Y-m-d') : null;
	}
}
