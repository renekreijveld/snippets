<?php
/**
 * @version    1.0.7
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

$canEdit   = Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_snippets');
$canCreate = Factory::getApplication()->getIdentity()->authorise('core.create', 'com_snippets');
$canDelete = Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_snippets');

if (!$canEdit && Factory::getApplication()->getIdentity()->authorise('core.edit.own', 'com_snippets')) {
    $canEdit = Factory::getApplication()->getIdentity()->id == $this->item->created_user_id;
}

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_snippets.snippets');
?>

<div class="row">
    <div class="col-auto snippetCategories">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <?php if ($canCreate) : ?>
                    <a title="<?= Text::_('SNIPPETS_ADD_CATEGORY'); ?>"
                        href="<?= Route::_('index.php?option=com_snippets&task=categoryform.edit&id=0', false, 0); ?>"
                        class="btn btn-primary btn-mini hasTooltip"><i class="icon-plus"></i>
                    </a>
                <?php endif; ?>
            </div>
            <div>
                <?php if ($canDelete) : ?>
                    <div>
                        <a class="btn btn-danger btn-sm" rel="noopener noreferrer" href="#deleteModal" role="button"
                            data-bs-toggle="modal">
                            <i title="<?= Text::_("SNIPPETS_DELETE_CATEGORY"); ?>" class="fas fa-trash hasTooltip"></i>
                        </a>
                    </div>
                    <?= HTMLHelper::_(
                        'bootstrap.renderModal',
                        'deleteModal',
                        array(
                            'title'      => Text::_('SNIPPETS_DELETE_CATEGORY'),
                            'height'     => '50%',
                            'width'      => '20%',
                            'modalWidth' => '400px',
                            'bodyHeight' => '100',
                            'footer'     => '<button class="btn btn-danger btn-sm" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>' . Text::_('JCANCEL') . '</button><a href="' . Route::_('index.php?option=com_snippets&task=category.remove&id=' . $this->item->id, false, 2) . '" class="btn btn-success btn-sm"><i class="fas fa-trash me-1"></i>' . Text::_('SNIPPETS_DELETE_ITEM') . '</a>'
                        ),
                        Text::sprintf('SNIPPETS_DELETE_CATEGORY_CONFIRM', $this->item->title),
                    ); ?>
                <?php endif; ?>
            </div>
        </div>
        <hr>
        <ul class="nav flex-column">
            <?php foreach ($this->categories as $item) : ?>
                <li class="nav-item <?php echo $item->id == $this->item->id ? 'current' : ''; ?>">
                    <a class="nav-link active"
                        href="<?= Route::_('index.php?option=com_snippets&view=category&id=' . (int) $item->id); ?>">
                        <?= $this->escape($item->title); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="col-auto snippetCategory">
        <?php if ($canCreate) : ?>
            <a title="<?= Text::_('SNIPPETS_ADD_SNIPPET'); ?>"
                href="<?= Route::_('index.php?option=com_snippets&task=snippetform.edit&id=0&catid=' . (int) $this->item->id, false, 0); ?>"
                class="btn btn-primary btn-mini hasTooltip"><i class="icon-plus"></i>
            </a>
        <?php endif; ?>
        <hr>
        <ul class="nav flex-column">
            <?php if (empty($this->item->snippets)) : ?>
                <?= Text::_('SNIPPETS_ADD_FIRST_SNIPPET'); ?>
            <?php endif; ?>
            <?php foreach ($this->item->snippets as $snippet) : ?>
                <li class="nav-item">
                    <a class="nav-link"
                        href="<?= Route::_('index.php?option=com_snippets&view=snippet&id=' . (int) $snippet->id . '&catid=' . (int) $this->item->id); ?>">
                        <?= $this->escape($snippet->title); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>