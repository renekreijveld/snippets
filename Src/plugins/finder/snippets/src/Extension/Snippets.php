<?php

/**
 * Smart Search plugin for com_snippets content.
 *
 * Indexes snippet titles and content so they can be found via Joomla's Smart Search.
 *
 * @package    Snippets.Plugin
 * @subpackage Finder.Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Snippets\Plugin\Finder\Snippets\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Finder as FinderEvent;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Helper;
use Joomla\Component\Finder\Administrator\Indexer\Indexer;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

/**
 * Smart Search plugin for indexing Snippets content.
 *
 * Extends the Finder Adapter which provides batch indexing via onBuildIndex.
 *
 * @since  1.0.0
 */
final class Snippets extends Adapter implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * The plugin identifier used by the indexer.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $context = 'Snippets';

    /**
     * The extension name (component) that owns the content.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $extension = 'com_snippets';

    /**
     * The sublayout to use when rendering search results.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $layout = 'snippet';

    /**
     * The type title displayed in Smart Search for this content type.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type_title = 'Snippet';

    /**
     * The database table that holds the content.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $table = '#__snippets';

    /**
     * The name of the published state column in the table.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $state_field = 'state';

    /**
     * Whether to auto-load the plugin language file.
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Returns the events this subscriber listens to.
     *
     * Merges with the parent Adapter events (onBuildIndex, onStartIndex,
     * onBeforeIndex, onFinderGarbageCollection) which handle batch indexing.
     *
     * @return  array<string, string>  Map of event names to handler method names.
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return array_merge(parent::getSubscribedEvents(), [
            'onFinderAfterDelete'         => 'onFinderAfterDelete',
            'onFinderAfterSave'           => 'onFinderAfterSave',
            'onFinderBeforeSave'          => 'onFinderBeforeSave',
            'onFinderChangeState'         => 'onFinderChangeState',
            'onFinderCategoryChangeState' => 'onFinderCategoryChangeState',
        ]);
    }

    /**
     * Prepare the indexer before a reindex run.
     *
     * @return  boolean  True on success.
     *
     * @since   1.0.0
     */
    protected function setup(): bool
    {
        return true;
    }

    /**
     * Index a single snippet item.
     *
     * This method processes a snippet result object and passes it to the indexer
     * with the appropriate metadata, taxonomy and search weighting.
     *
     * @param   Result  $item  The content item to index.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function index(Result $item): void
    {
        $item->setLanguage();

        // Bail out if the component is disabled.
        if (ComponentHelper::isEnabled($this->extension) === false) {
            return;
        }

        // Set the item context for the indexer.
        $item->context = 'com_snippets.snippet';

        // Prepare content for indexing through the Finder helper.
        $item->summary = Helper::prepareContent($item->summary ?? '', $item->params ?? null, $item);
        $item->body    = Helper::prepareContent($item->body ?? '', $item->params ?? null, $item);

        // Determine the first category ID for the route (cat_id is comma-separated).
        $catId = 0;

        if (!empty($item->cat_id)) {
            $catIds = array_filter(array_map('intval', explode(',', (string) $item->cat_id)));

            if ($catIds !== []) {
                $catId = reset($catIds);
            }
        }

        // Create the URL as identifier to recognise items again.
        $item->url   = $this->getUrl($item->id, $this->extension, $this->layout);
        $item->route = 'index.php?option=com_snippets&view=snippet&id=' . (int) $item->id
            . '&catid=' . $catId;

        // Set access level (snippets have no individual access level, default to public).
        $item->access = 1;

        // Translate the publication state.
        $item->state = $this->translateState($item->state);

        // Get taxonomies to display.
        $taxonomies = $this->params->get('taxonomies', ['type', 'category', 'language']);

        // Add the type taxonomy data.
        if (\in_array('type', $taxonomies)) {
            $item->addTaxonomy('Type', 'Snippet');
        }

        // Add category taxonomy if available.
        if (\in_array('category', $taxonomies) && !empty($item->cat_id)) {
            $categoryIds = array_filter(array_map('intval', explode(',', (string) $item->cat_id)));

            if ($categoryIds !== []) {
                $categoryNames = $this->getCategoryNames($categoryIds);

                foreach ($categoryNames as $categoryName) {
                    $item->addTaxonomy('Category', $categoryName);
                }
            }
        }

        // Add the language taxonomy.
        if (\in_array('language', $taxonomies)) {
            $item->addTaxonomy('Language', $item->language);
        }

        // Index the item.
        $this->indexer->index($item);
    }

    /**
     * Build the base query used to retrieve items for indexing.
     *
     * The Finder adapter reuses this single query in getContentCount(), getItem()
     * and getItems(), so it must be generic enough for all three use cases.
     *
     * @param   mixed  $query  A QueryInterface object or null.
     *
     * @return  QueryInterface  A database object.
     *
     * @since   1.0.0
     */
    protected function getListQuery($query = null)
    {
        $db = $this->getDatabase();

        $query = $query instanceof QueryInterface ? $query : $db->getQuery(true)
            ->select(
                $db->quoteName(
                    [
                        'a.id',
                        'a.title',
                        'a.alias',
                        'a.state',
                        'a.cat_id',
                        'a.created_by',
                        'a.modified_by',
                        'a.ordering',
                    ]
                )
            )
            // Map content to summary for the indexer.
            ->select($db->quoteName('a.content', 'summary'))
            // No separate body field, set to empty string.
            ->select($db->quote('') . ' AS ' . $db->quoteName('body'))
            // Snippets do not have a language column; default to all languages.
            ->select($db->quote('*') . ' AS ' . $db->quoteName('language'))
            // Snippets do not have individual access levels; default to public.
            ->select('1 AS ' . $db->quoteName('access'))
            // Author name for display in search results.
            ->select($db->quoteName('u.name', 'author'))
            ->from($db->quoteName('#__snippets', 'a'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.created_by'));

        return $query;
    }

    /**
     * Build a query to load the published state for a snippet.
     *
     * Overrides the parent because snippets use cat_id (comma-separated) instead
     * of the standard catid column, so the default JOIN on #__categories does not work.
     *
     * @return  QueryInterface  A database object.
     *
     * @since   1.0.0
     */
    protected function getStateQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('a.id'))
            ->select($db->quoteName('a.state'))
            ->select('1 AS ' . $db->quoteName('access'))
            ->from($db->quoteName('#__snippets', 'a'));

        return $query;
    }

    /**
     * Handle the after-save event to reindex a snippet.
     *
     * @param   FinderEvent\AfterSaveEvent  $event  The event object.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onFinderAfterSave(FinderEvent\AfterSaveEvent $event): void
    {
        $context = $event->getContext();
        $row     = $event->getItem();

        if ($context === 'com_snippets.snippet' || $context === 'com_snippets.form') {
            $this->reindex((int) $row->id);
        }
    }

    /**
     * Handle the before-save event.
     *
     * @param   FinderEvent\BeforeSaveEvent  $event  The event object.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onFinderBeforeSave(FinderEvent\BeforeSaveEvent $event): void
    {
        // Nothing to store before save for snippets (no individual access level).
    }

    /**
     * Handle the after-delete event to remove a snippet from the index.
     *
     * @param   FinderEvent\AfterDeleteEvent  $event  The event object.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onFinderAfterDelete(FinderEvent\AfterDeleteEvent $event): void
    {
        $context = $event->getContext();
        $table   = $event->getItem();

        if ($context === 'com_snippets.snippet' || $context === 'com_snippets.form') {
            $id = $table->id;

            if (!$id) {
                return;
            }

            $this->remove($id);
        } elseif ($context === 'com_finder.index') {
            $this->remove($table->link_id);
        }
    }

    /**
     * Handle state changes (publish, unpublish, trash, archive) for snippets.
     *
     * @param   FinderEvent\AfterChangeStateEvent  $event  The event object.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onFinderChangeState(FinderEvent\AfterChangeStateEvent $event): void
    {
        $context = $event->getContext();
        $pks     = $event->getPks();
        $value   = $event->getValue();

        if ($context === 'com_snippets.snippet' || $context === 'com_snippets.form') {
            $this->itemStateChange($pks, $value);
        }

        // Handle when the plugin is disabled.
        if ($context === 'com_plugins.plugin' && $value === 0) {
            $this->pluginDisable($pks);
        }
    }

    /**
     * Handle category state changes.
     *
     * Since snippets use comma-separated cat_id values and categories live in
     * #__categories, this event triggers a reindex for all snippets linked
     * to the affected categories.
     *
     * @param   FinderEvent\AfterCategoryChangeStateEvent  $event  The event object.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onFinderCategoryChangeState(FinderEvent\AfterCategoryChangeStateEvent $event): void
    {
        if ($event->getExtension() !== 'com_snippets') {
            return;
        }

        $pks = $event->getPks();
        $db  = $this->getDatabase();

        foreach ($pks as $categoryId) {
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__snippets'))
                ->where('FIND_IN_SET(:catId, ' . $db->quoteName('cat_id') . ')')
                ->bind(':catId', $categoryId, ParameterType::INTEGER);

            $snippetIds = $db->setQuery($query)->loadColumn();

            foreach ($snippetIds as $snippetId) {
                $this->reindex((int) $snippetId);
            }
        }
    }

    /**
     * Retrieve category names for the given category IDs.
     *
     * @param   int[]  $categoryIds  Array of category IDs.
     *
     * @return  string[]  Array of category title strings.
     *
     * @since   1.0.0
     */
    private function getCategoryNames(array $categoryIds): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('title'))
            ->from($db->quoteName('#__categories'))
            ->whereIn($db->quoteName('id'), $categoryIds);

        return $db->setQuery($query)->loadColumn();
    }
}
