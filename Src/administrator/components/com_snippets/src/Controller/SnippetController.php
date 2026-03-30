<?php
/**
 * @version    1.0.2
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

namespace Snippets\Component\Snippets\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

/**
 * Snippet controller class.
 *
 * @since  1.0.0
 */
class SnippetController extends FormController
{
	protected $view_list = 'snippets';
}
