<?php

/**
 * @version    1.0.7
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

namespace Snippets\Component\Snippets\Site\Service;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Categories\Categories;

/**
 * Content Component Category Tree
 *
 * @since  1.0.0
 */

class Category extends Categories
{
	/**
	 * Class constructor
	 *
	 * @param   array  $options  Array of options
	 *
	 * @since   11.1
	 */
	public function __construct($options = array())
	{
		$options['table']     = '#__snippets';
		$options['extension'] = 'com_snippets.snippets';
		parent::__construct($options);
	}

}
