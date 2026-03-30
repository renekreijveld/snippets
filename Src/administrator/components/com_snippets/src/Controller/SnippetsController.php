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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\Utilities\ArrayHelper;

/**
 * Snippets list controller class.
 *
 * @since  1.0.0
 */
class SnippetsController extends AdminController
{
	/**
	 * Method to clone existing Snippets
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
	public function duplicate()
	{
		// Check for request forgeries
		$this->checkToken();

		// Get id(s)
		$pks = $this->input->post->get('cid', array(), 'array');

		try {
			if (empty($pks)) {
				throw new \Exception(Text::_('SNIPPETS_NO_ELEMENT_SELECTED'));
			}

			ArrayHelper::toInteger($pks);
			$model = $this->getModel();
			$model->duplicate($pks);
			$this->setMessage(Text::_('SNIPPETS_ITEMS_SUCCESS_DUPLICATED'));
		} catch (\Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}

		$this->setRedirect('index.php?option=com_snippets&view=snippets');
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    Optional. Model name
	 * @param   string  $prefix  Optional. Class prefix
	 * @param   array   $config  Optional. Configuration array for model
	 *
	 * @return  object	The Model
	 *
	 * @since   1.0.0
	 */
	public function getModel($name = 'Snippet', $prefix = 'Administrator', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$pks = $this->input->post->get('cid', array(), 'array');
		$order = $this->input->post->get('order', array(), 'array');

		// Sanitize the input
		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return) {
			echo "1";
		}

		// Close the application
		Factory::getApplication()->close();
	}
}
