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

use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\View\ViewInterface;

/**
 * Display Component Controller
 *
 * @since  1.0.0
 */
class DisplayController extends \Joomla\CMS\MVC\Controller\BaseController
{
    /**
     * Constructor.
     *
     * @param  array                $config   An optional associative array of configuration settings.
     * Recognized key values include 'name', 'default_task', 'model_path', and
     * 'view_path' (this list is not meant to be comprehensive).
     * @param  MVCFactoryInterface  $factory  The factory.
     * @param  CMSApplication       $app      The JApplication for the dispatcher
     * @param  Input              $input    Input
     *
     * @since  1.0.0
     */
    public function __construct($config = array(), ?MVCFactoryInterface $factory = null, $app = null, $input = null)
    {
        parent::__construct($config, $factory, $app, $input);
    }

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached.
     * @param   boolean  $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link InputFilter::clean()}.
     *
     * @return  \Joomla\CMS\MVC\Controller\BaseController  This object to support chaining.
     *
     * @since   1.0.0
     */
    public function display($cachable = false, $urlparams = false)
    {

        $view = $this->input->getCmd('view', 'categories');
        $view = $view == "featured" ? 'categories' : $view;
        $this->input->set('view', $view);

        parent::display($cachable, $urlparams);
        return $this;
    }

    protected function prepareViewModel(ViewInterface $view)
    {
        // Push the model into the view (as default)
        parent::prepareViewModel($view);

        switch (strtolower($view->getName())) {
            case 'category':
                // Push the Category model into the view
                if ($model = $this->getModel('Category', 'Site', ['base_path' => $this->basePath])) {
                    $view->setModel($model, true);
                }
                // Push the Categories model into the view
                if ($model = $this->getModel('Categories', 'Site', ['base_path' => $this->basePath])) {
                    $view->setModel($model, true);
                }
                break;
            case 'snippet':
                // Push the Snippet model into the view
                if ($model = $this->getModel('Snippet', 'Site', ['base_path' => $this->basePath])) {
                    $view->setModel($model, true);
                }
                // Push the Category model into the view
                if ($model = $this->getModel('Category', 'Site', ['base_path' => $this->basePath])) {
                    $view->setModel($model, true);
                }
                // Push the Categories model into the view
                if ($model = $this->getModel('Categories', 'Site', ['base_path' => $this->basePath])) {
                    $view->setModel($model, true);
                }
                break;
        }
    }

}
