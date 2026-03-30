<?php
/**
 * @version    1.0.2
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

$canEdit = Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_snippets');
$canCreate = Factory::getApplication()->getIdentity()->authorise('core.create', 'com_snippets');

if (!$canEdit && Factory::getApplication()->getIdentity()->authorise('core.edit.own', 'com_snippets')) {
    $canEdit = Factory::getApplication()->getIdentity()->id == $this->item->created_user_id;
}
// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_snippets.snippets');
?>

<div class="row">
    <div class="col-auto snippetCategories">
        <?php if ($canCreate) : ?>
            <a title="<?= Text::_('SNIPPETS_ADD_CATEGORY'); ?>" href="<?= Route::_('index.php?option=com_snippets&task=categoryform.edit&id=0', false, 0); ?>" class="btn btn-primary btn-mini hasTooltip"><i class="icon-plus"></i></a>
            <hr>
        <?php endif; ?>
        <ul class="nav flex-column">
            <?php foreach ($this->categories as $item) : ?>
                <li class="nav-item <?php echo $item->id == $this->item->id ? 'current' : ''; ?>">
                    <a class="nav-link active" href="<?= Route::_('index.php?option=com_snippets&view=category&id=' . (int) $item->id); ?>">
                        <?= $this->escape($item->title); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="col-auto snippetCategory">
        <?php if ($canCreate) : ?>
            <a title="<?= Text::_('SNIPPETS_ADD_SNIPPET'); ?>" href="<?= Route::_('index.php?option=com_snippets&task=snippetform.edit&id=0&catid=' . (int) $this->item->id, false, 0); ?>" class="btn btn-primary btn-mini hasTooltip"><i class="icon-plus"></i></a>
            <hr>
        <?php endif; ?>
        <ul class="nav flex-column">
            <?php if (empty($this->item->snippets)) : ?>
                <?= Text::_('SNIPPETS_ADD_FIRST_SNIPPET'); ?>
            <?php endif; ?>
            <?php foreach ($this->item->snippets as $snippet) : ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= Route::_('index.php?option=com_snippets&view=snippet&id=' . (int) $snippet->id . '&catid=' . (int) $this->item->id); ?>">
                    <?= $this->escape($snippet->title); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
