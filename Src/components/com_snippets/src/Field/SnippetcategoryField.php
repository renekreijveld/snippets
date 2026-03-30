<?php
/**
 * @version    1.0.3
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */

namespace Snippets\Component\Snippets\Site\Field;

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;

/**
 * Supports an HTML select list of categories
 *
 * @since  1.0.0
 */
class SnippetcategoryField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'snippetcategory';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   1.0.0
     */
    protected function getInput()
    {
        $categoryId = Factory::getApplication()->input->get('catid', 0, 'int');
        $db         = Factory::getContainer()->get('DatabaseDriver');

        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title']))
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('com_snippets.snippets'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('title') . ' ASC');
        $db->setQuery($query);
        $categories = $db->loadObjectlist();

        $html   = array();
        $html[] = '<select class="form-select ' . $this->class . '" name="' . $this->name . '">';
        if (empty($this->value)) {
            $this->value = (string) $categoryId;
        }
        if ($categories) {
            $html[] = '<option value="">' . Text::_('SNIPPETS_CHOOSE') . '</option>';
            foreach ($categories as $cat) {
                if ($cat->id == $this->value) {
                    $html[] = '<option value="' . $cat->id . '" selected>' . $cat->title . '</option>';
                } else {
                    $html[] = '<option value="' . $cat->id . '">' . $cat->title . '</option>';
                }
            }
        }
        $html[] = '</select>';

        return implode($html);
    }
}