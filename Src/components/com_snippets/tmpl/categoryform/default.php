<?php
/**
 * @version    1.0.4
 * @package    Com_Snippets
 * @author     René Kreijveld <email@renekreijveld.nl>
 * @copyright  2026 René Kreijveld Webdevelopment
 * @license    GNU General Public License version 3 or later; see LICENSE
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');

// Load admin language file
$lang = Factory::getLanguage();
$lang->load('com_snippets', JPATH_SITE);

$user    = Factory::getApplication()->getIdentity();
$canEdit = $user->authorise('core.edit', 'com_snippets') || $user->authorise('core.create', 'com_snippets');

?>

<div class="category-edit front-end-edit">
    <?php if ($this->params->get('show_page_heading')) : ?>
        <div class="page-header">
            <h1> <?= $this->escape($this->params->get('page_heading')); ?> </h1>
        </div>
    <?php endif; ?>
    <?php if (!$canEdit) : ?>
        <h3>
            <?php throw new \Exception(Text::_('SNIPPETS_ERROR_MESSAGE_NOT_AUTHORISED'), 403); ?>
        </h3>
    <?php else : ?>
        <?php if (!empty($this->item->id)) : ?>
            <h1><?= Text::sprintf('SNIPPETS_EDIT_CATEGORY_TITLE', $this->item->id); ?></h1>
        <?php else : ?>
            <h1><?= Text::_('SNIPPETS_ADD_CATEGORY_TITLE'); ?></h1>
        <?php endif; ?>

        <form id="form-category" action="<?= Route::_('index.php?option=com_snippets&task=categoryform.save'); ?>" method="post" class="form-validate form-horizontal" enctype="multipart/form-data">
            <input type="hidden" name="jform[id]" value="<?= isset($this->item->id) ? $this->item->id : ''; ?>" />
            <input type="hidden" name="jform[extension]" value="com_snippets.snippets" />
            <?= HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'category')); ?>
            <?= HTMLHelper::_('uitab.addTab', 'myTab', 'category', Text::_('SNIPPETS_TAB_CATEGORY', true)); ?>
            <div class="row">
                <div class="col-5">
                    <?= $this->form->renderField('title'); ?>
                </div>
                <div class="col-5">
                    <?= $this->form->renderField('alias'); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <?= $this->form->renderField('description'); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-3">
                    <?= $this->form->renderField('published'); ?>
                </div>
                <div class="col-3">
                    <?= $this->form->renderField('access'); ?>
                </div>
                <div class="col-3">
                    <?= $this->form->renderField('language'); ?>
                </div>
            </div>
            <?= HTMLHelper::_('uitab.endTab'); ?>
            <div class="control-group">
                <div class="controls">
                    <?php if ($this->canSave) : ?>
                        <button type="submit" class="validate btn btn-sm btn-primary">
                            <span class="fas fa-check" aria-hidden="true"></span>
                            <?= Text::_('SNIPPETS_SAVE'); ?>
                        </button>
                    <?php endif; ?>
                    <a class="btn btn-danger btn-sm" href="<?= Route::_('index.php?option=com_snippets&task=categoryform.cancel'); ?>" title="<?= Text::_('JCANCEL'); ?>">
                        <span class="fas fa-times me-1" aria-hidden="true"></span> <?= Text::_('JCANCEL'); ?>
                    </a>
                </div>
            </div>

            <input type="hidden" name="option" value="com_snippets" />
            <input type="hidden" name="task" value="categoryform.save" />
            <?= HTMLHelper::_('form.token'); ?>
        </form>
    <?php endif; ?>
</div>