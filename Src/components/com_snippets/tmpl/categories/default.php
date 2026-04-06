<?php
/**
 * @version    1.0.6
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip', []);

$user       = Factory::getApplication()->getIdentity();
$userId     = $user->get('id');
$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
$canCreate  = $user->authorise('core.create', 'com_snippets') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'categoryform.xml');
$canEdit    = $user->authorise('core.edit', 'com_snippets');
$canCheckin = $user->authorise('core.manage', 'com_snippets');
$canChange  = $user->authorise('core.edit.state', 'com_snippets');
$canDelete  = $user->authorise('core.delete', 'com_snippets');

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_snippets.snippets');
?>

<?php if ($this->params->get('show_page_heading')) : ?>
    <div class="page-header">
        <h1> <?= $this->escape($this->params->get('page_heading')); ?> </h1>
    </div>
<?php endif; ?>
<div class="row">
    <div class="col-auto snippetCategories">
        <div>
            <?php if ($canCreate) : ?>
                <a title="<?= Text::_('SNIPPETS_ADD_CATEGORY'); ?>"
                    href="<?= Route::_('index.php?option=com_snippets&task=categoryform.edit&id=0', false, 0); ?>"
                    class="btn btn-primary btn-mini hasTooltip"><i class="icon-plus"></i></a>
            <?php endif; ?>
        </div>
        <ul class="nav flex-column">
            <?php foreach ($this->items as $item) : ?>
                <li class="nav-item">
                    <a class="nav-link"
                        href="<?= Route::_('index.php?option=com_snippets&view=category&id=' . (int) $item->id); ?>">
                        <?= $this->escape($item->title); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>