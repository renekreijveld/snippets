<?php
/**
 * @version    1.0.3
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
use \Snippets\Component\Snippets\Site\Helper\SnippetsHelper;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');

// Load admin language file
$lang = Factory::getLanguage();
$lang->load('com_snippets', JPATH_SITE);

$user    = Factory::getApplication()->getIdentity();
$canEdit = SnippetsHelper::canUserEdit($this->item, $user);
?>

<style>
    fieldset#jform_target,
    fieldset#jform_target .btn-group {
        margin-bottom: 0;
    }
</style>
<div class="snippet-edit front-end-edit">
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
            <h1><?= Text::sprintf('SNIPPETS_EDIT_ITEM_TITLE', $this->item->title); ?></h1>
        <?php else : ?>
            <h1><?= Text::_('SNIPPETS_ADD_ITEM_TITLE'); ?></h1>
        <?php endif; ?>

        <form id="form-snippet" action="<?= Route::_('index.php?option=com_snippets&task=snippetform.save'); ?>" method="post" class="form-validate form-horizontal" enctype="multipart/form-data">
            <input type="hidden" name="jform[id]" value="<?= isset($this->item->id) ? $this->item->id : ''; ?>" />
            <input type="hidden" name="jform[state]" value="<?= isset($this->item->state) ? $this->item->state : ''; ?>" />
            <?= $this->form->getInput('created_by'); ?>
            <?= $this->form->getInput('modified_by'); ?>
            <?= HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'snippet')); ?>
            <?= HTMLHelper::_('uitab.addTab', 'myTab', 'snippet', Text::_('SNIPPETS_TAB_SNIPPET', true)); ?>
            <div class="row">
                <div class="col-6">
                    <?= $this->form->renderField('title'); ?>
                </div>
                <div class="col-6">
                    <?= $this->form->renderField('alias'); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-3">
                    <?= $this->form->renderField('cat_id'); ?>
                </div>
                <div class="col-6">
                    <?= $this->form->renderField('target'); ?>
                </div>
            </div>
            <a class="btn btn-primary btn-sm" href="https://quickref.me/markdown" target="_blank">
                Markdown Syntax
            </a>
            <?= $this->form->renderField('content'); ?>
            <?= HTMLHelper::_('uitab.endTab'); ?>
            <div class="control-group">
                <div class="controls">
                    <?php if ($this->canSave) : ?>
                        <button type="submit" class="validate btn btn-sm btn-success">
                            <span class="fas fa-check me-1" aria-hidden="true"></span><?= Text::_('SNIPPETS_SAVE'); ?>
                        </button>
                    <?php endif; ?>
                    <a class="btn btn-danger btn-sm" href="<?= Route::_('index.php?option=com_snippets&task=snippetform.cancel&catid=' . $this->catid); ?>" title="<?= Text::_('JCANCEL'); ?>">
                        <span class="fas fa-times me-1" aria-hidden="true"></span> <?= Text::_('JCANCEL'); ?>
                    </a>
                </div>
            </div>
            <input type="hidden" name="option" value="com_snippets" />
            <input type="hidden" name="task" value="snippetform.save" />
            <?= HTMLHelper::_('form.token'); ?>
        </form>
    <?php endif; ?>
</div>