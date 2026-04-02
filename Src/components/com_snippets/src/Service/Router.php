<?php

/**
 * @version    1.0.4
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

namespace Snippets\Component\Snippets\Site\Service;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Categories\CategoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Class SnippetsRouter
 *
 */
class Router extends RouterView
{
    private $noIDs;
    /**
     * The category factory
     *
     * @var    CategoryFactoryInterface
     *
     * @since  1.0.0
     */
    private $categoryFactory;

    /**
     * The category cache
     *
     * @var    array
     *
     * @since  1.0.0
     */
    private $categoryCache = [];

    public function __construct(SiteApplication $app, AbstractMenu $menu, CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
    {
        $params = ComponentHelper::getParams('com_snippets');
        $this->noIDs = (bool) $params->get('sef_ids');
        $this->categoryFactory = $categoryFactory;

        $snippets = new RouterViewConfiguration('snippets');
        $snippets->setKey('catid')->setNestable();
        $this->registerView($snippets);
        $snippetform = new RouterViewConfiguration('snippetform');
        $snippetform->setKey('id');
        $this->registerView($snippetform);

        $categories = new RouterViewConfiguration('categories');
        $this->registerView($categories);
        $ccCategory = new RouterViewConfiguration('category');
        $ccCategory->setKey('id')->setParent($categories);
        $this->registerView($ccCategory);
        $ccSnippet = new RouterViewConfiguration('snippet');
        $ccSnippet->setKey('id')->setParent($ccCategory, 'catid');
        $this->registerView($ccSnippet);
        $categoryform = new RouterViewConfiguration('categoryform');
        $categoryform->setKey('id');
        $this->registerView($categoryform);

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    /**
     * Method to get the segment(s) for a category
     *
     * @param   string  $id     ID of the category to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this item
     */
    public function getSnippetsSegment($id, $query)
    {
        $category = $this->getCategories(["access" => true])->get($id);

        if ($category) {
            $path = array_reverse($category->getPath(), true);
            $path[0] = '1:root';

            if ($this->noIDs) {
                foreach ($path as &$segment) {
                    list($id, $segment) = explode(':', $segment, 2);
                }
            }

            return $path;
        }

        return array();
    }
    /**
     * Method to get the segment(s) for an snippet
     *
     * @param   string  $id     ID of the snippet to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this item
     */
    public function getSnippetSegment($id, $query)
    {
        if (!strpos($id, ':')) {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $dbquery = $db->getQuery(true);
            $dbquery->select($dbquery->qn('alias'))
                ->from($dbquery->qn('#__snippets'))
                ->where('id = ' . $dbquery->q($id));
            $db->setQuery($dbquery);

            $id .= ':' . $db->loadResult();
        }

        if ($this->noIDs) {
            list($void, $segment) = explode(':', $id, 2);

            return array($void => $segment);
        }
        return array((int) $id => $id);
    }
    /**
     * Method to get the segment(s) for an snippetform
     *
     * @param   string  $id     ID of the snippetform to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this item
     */
    public function getSnippetformSegment($id, $query)
    {
        return $this->getSnippetSegment($id, $query);
    }

    /**
     * Method to get the id for a category
     *
     * @param   string  $segment  Segment to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     */
    public function getSnippetsId($segment, $query)
    {
        if (isset($query['catid'])) {
            $category = $this->getCategories(["access" => true])->get($query['catid']);

            if ($category) {
                foreach ($category->getChildren() as $child) {
                    if ($this->noIDs) {
                        if ($child->alias == $segment) {
                            return $child->id;
                        }
                    } else {
                        if ($child->id == (int) $segment) {
                            return $child->id;
                        }
                    }
                }
            }
        }

        return false;
    }
    /**
     * Method to get the segment(s) for an snippet
     *
     * @param   string  $segment  Segment of the snippet to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     */
    public function getSnippetId($segment, $query)
    {
        if ($this->noIDs) {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $dbquery = $db->getQuery(true);
            $catId = (int) ($query['catid'] ?? $query['id'] ?? 0);
            $dbquery->select($dbquery->qn('id'))
                ->from($dbquery->qn('#__snippets'))
                ->where($dbquery->qn('alias') . ' = :alias')
                ->where('FIND_IN_SET(:catId, ' . $dbquery->qn('cat_id') . ')')
                ->bind(':alias', $segment)
                ->bind(':catId', $catId, ParameterType::INTEGER);
            $db->setQuery($dbquery);

            return (int) $db->loadResult();
        }
        return (int) $segment;
    }
    /**
     * Method to get the segment(s) for an snippetform
     *
     * @param   string  $segment  Segment of the snippetform to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     */
    public function getSnippetformId($segment, $query)
    {
        return $this->getSnippetId($segment, $query);
    }

    /**
     * Method to get the segment(s) for the categories list view.
     *
     * @param   string  $id     ID of the category to retrieve the segments for.
     * @param   array   $query  The request that is built right now.
     *
     * @return  array  The segments of this item.
     */
    public function getCategoriesSegment($id, $query)
    {
        return array();
    }

    /**
     * Method to get the segment(s) for a single category.
     *
     * @param   string  $id     ID of the category to retrieve the segments for.
     * @param   array   $query  The request that is built right now.
     *
     * @return  array  The segments of this item.
     */
    public function getCategorySegment($id, $query)
    {
        if (!strpos($id, ':')) {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $dbquery = $db->getQuery(true);
            $dbquery->select($dbquery->qn('alias'))
                ->from($dbquery->qn('#__categories'))
                ->where('id = ' . $dbquery->q($id));
            $db->setQuery($dbquery);

            $id .= ':' . $db->loadResult();
        }

        if ($this->noIDs) {
            list($void, $segment) = explode(':', $id, 2);

            return array($void => $segment);
        }

        return array((int) $id => $id);
    }

    /**
     * Method to get the segment(s) for the category form.
     *
     * @param   string  $id     ID of the category to retrieve the segments for.
     * @param   array   $query  The request that is built right now.
     *
     * @return  array  The segments of this item.
     */
    public function getCategoryformSegment($id, $query)
    {
        return $this->getCategorySegment($id, $query);
    }

    /**
     * Method to get the id for the categories list view.
     *
     * @param   string  $segment  Segment to retrieve the ID for.
     * @param   array   $query    The request that is parsed right now.
     *
     * @return  mixed  The id of this item or false.
     */
    public function getCategoriesId($segment, $query)
    {
        return false;
    }

    /**
     * Method to get the id for a single category.
     *
     * @param   string  $segment  Segment of the category to retrieve the ID for.
     * @param   array   $query    The request that is parsed right now.
     *
     * @return  mixed  The id of this item or false.
     */
    public function getCategoryId($segment, $query)
    {
        if ($this->noIDs) {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $dbquery = $db->getQuery(true);
            $dbquery->select($dbquery->qn('id'))
                ->from($dbquery->qn('#__categories'))
                ->where('alias = ' . $dbquery->q($segment))
                ->where($dbquery->qn('extension') . ' = ' . $dbquery->q('com_snippets.snippets'));
            $db->setQuery($dbquery);

            return (int) $db->loadResult();
        }

        return (int) $segment;
    }

    /**
     * Method to get the id for the category form.
     *
     * @param   string  $segment  Segment of the category to retrieve the ID for.
     * @param   array   $query    The request that is parsed right now.
     *
     * @return  mixed  The id of this item or false.
     */
    public function getCategoryformId($segment, $query)
    {
        return $this->getCategoryId($segment, $query);
    }

    /**
     * Method to get categories from cache
     *
     * @param   array  $options   The options for retrieving categories
     *
     * @return  CategoryInterface  The object containing categories
     *
     * @since   1.0.0
     */
    private function getCategories(array $options = []): CategoryInterface
    {
        $key = serialize($options);

        if (!isset($this->categoryCache[$key])) {
            $this->categoryCache[$key] = $this->categoryFactory->createCategory($options);
        }

        return $this->categoryCache[$key];
    }
}
