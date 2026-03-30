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
use \Joomla\CMS\Session\Session;
use FastVolt\Helper\Markdown;

require_once(JPATH_ROOT . '/libraries/Snippets/Markdown/vendor/autoload.php');

$markdown = new Markdown();
$markdown->setContent($this->item->content);
$snippetContent = $markdown->getHtml();

if ($this->item->target == 1) {
    $snippetContent = preg_replace('/<a\s+(?!.*?target=)(.*?)>/i', '<a $1 target="_blank">', $snippetContent);
}

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip', []);

$canEdit   = Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_snippets');
$canCreate = Factory::getApplication()->getIdentity()->authorise('core.create', 'com_snippets');

if (!$canEdit && Factory::getApplication()->getIdentity()->authorise('core.edit.own', 'com_snippets')) {
    $canEdit = Factory::getApplication()->getIdentity()->id == $this->item->created_by;
}
// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_snippets.highlight')
    ->useStyle('com_snippets.tomorrow-night-blue')
    ->useStyle('com_snippets.snippets')
    ->useScript('com_snippets.highlight')
    ->useScript('com_snippets.bash')
    ->useScript('com_snippets.css')
    ->useScript('com_snippets.javascript')
    ->useScript('com_snippets.php')
    ->useScript('com_snippets.scss')
    ->useScript('com_snippets.sql')
    ->useScript('com_snippets.xml');
?>
<script>hljs.highlightAll();</script>

<div class="row">
    <div class="col-auto snippetCategories">
        <?php if ($canCreate) : ?>
            <a title="<?= Text::_('SNIPPETS_ADD_CATEGORY'); ?>" href="<?= Route::_('index.php?option=com_snippets&task=categoryform.edit&id=0', false, 0); ?>" class="btn btn-primary btn-mini hasTooltip">
                <i class="icon-plus"></i>
            </a>
            <hr>
        <?php endif; ?>
        <ul class="nav flex-column">
            <?php foreach ($this->categories as $item) : ?>
                <li class="nav-item <?php echo $item->id == $this->item->cat_id ? 'current' : ''; ?>">
                    <a class="nav-link active" href="<?= Route::_('index.php?option=com_snippets&view=category&id=' . (int) $item->id); ?>">
                        <?= $this->escape($item->title); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="col-auto snippetCategory">
        <?php if ($canCreate) : ?>
            <a title="<?= Text::_('SNIPPETS_ADD_SNIPPET'); ?>" href="<?= Route::_('index.php?option=com_snippets&task=snippetform.edit&id=0&catid=' . $this->item->cat_id, false, 0); ?>" class="btn btn-primary btn-mini hasTooltip">
                <i class="icon-plus"></i>
            </a>
            <hr>
        <?php endif; ?>
        <ul class="nav flex-column">
            <?php if (empty($this->snippets)) : ?>
                <?= Text::_('SNIPPETS_ADD_FIRST_SNIPPET'); ?>
            <?php endif; ?>
            <?php foreach ($this->snippets as $snippet) : ?>
                <li class="nav-item <?php echo $snippet->id == $this->item->id ? 'current' : ''; ?>">
                    <a class="nav-link" href="<?= Route::_('index.php?option=com_snippets&view=snippet&id=' . (int) $snippet->id . '&catid=' . (int) $this->item->cat_id); ?>">
                        <?= $this->escape($snippet->title); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="col snippet">
        <h1 class="m-0 h3 lh-1"><?= $this->item->title; ?></h2>
        <hr>
        <?php $canCheckin = Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_snippets.' . $this->item->id) || $this->item->checked_out == Factory::getApplication()->getIdentity()->id; ?>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <?php if ($canEdit && $this->item->checked_out == 0) : ?>
                    <a class="btn btn-success btn-sm" href="<?= Route::_('index.php?option=com_snippets&task=snippet.edit&id=' . $this->item->id); ?>">
                        <i title="<?= Text::_("SNIPPETS_EDIT_SNIPPET"); ?>" class="icon-edit hasTooltip"></i>
                    </a>
                <?php elseif ($canCheckin && $this->item->checked_out > 0) : ?>
                    <a class="btn btn-outline-primary btn-sm" href="<?= Route::_('index.php?option=com_snippets&task=snippet.checkin&id=' . $this->item->id . '&' . Session::getFormToken() . '=1'); ?>">
                        <?= Text::_("JLIB_HTML_CHECKIN"); ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php if (Factory::getApplication()->getIdentity()->authorise('core.delete', 'com_snippets.snippet.' . $this->item->id)) : ?>
                <div>
                    <a class="btn btn-danger btn-sm" rel="noopener noreferrer" href="#deleteModal" role="button" data-bs-toggle="modal">
                        <i title="<?= Text::_("SNIPPETS_DELETE_SNIPPET"); ?>" class="icon-trash hasTooltip"></i>
                    </a>
                </div>
                <?= HTMLHelper::_(
                    'bootstrap.renderModal',
                    'deleteModal',
                    array(
                        'title' => Text::_('SNIPPETS_DELETE_ITEM'),
                        'height' => '50%',
                        'width' => '20%',

                        'modalWidth' => '50',
                        'bodyHeight' => '100',
                        'footer' => '<button class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button><a href="' . Route::_('index.php?option=com_snippets&task=snippet.remove&id=' . $this->item->id, false, 2) . '" class="btn btn-danger">' . Text::_('SNIPPETS_DELETE_ITEM') . '</a>'
                    ),
                    Text::sprintf('SNIPPETS_DELETE_CONFIRM', $this->item->id),
                ); ?>
            <?php endif; ?>
        </div>
        <hr>
        <?= $snippetContent; ?>
    </div>
</div>

