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

use Joomla\CMS\MVC\Controller\FormController;

/**
 * Categories list controller.
 *
 * @since  1.0.0
 */
class CategoriesController extends FormController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since   1.0.0
	 */
	public function getModel($name = 'Categories', $prefix = 'Site', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}
}
