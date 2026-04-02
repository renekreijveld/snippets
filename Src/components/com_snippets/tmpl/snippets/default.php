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
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Session\Session;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

$user       = Factory::getApplication()->getIdentity();
$userId     = $user->get('id');
$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
$canCreate  = $user->authorise('core.create', 'com_snippets') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'snippetform.xml');
$canEdit    = $user->authorise('core.edit', 'com_snippets') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'snippetform.xml');
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
<form action="<?= htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">
    <?php if (!empty($this->filterForm)) {
        echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
    } ?>
    <div class="table-responsive">
        <table class="table table-striped" id="snippetList">
            <thead>
                <tr>
                    <th>
                        <?= HTMLHelper::_('grid.sort', 'SNIPPETS_SNIPPETS_ID', 'a.id', $listDirn, $listOrder); ?>
                    </th>
                    <th>
                        <?= HTMLHelper::_('grid.sort', 'JPUBLISHED', 'a.state', $listDirn, $listOrder); ?>
                    </th>
                    <th>
                        <?= HTMLHelper::_('grid.sort', 'SNIPPETS_CATEGORY', 'a.cat_id', $listDirn, $listOrder); ?>
                    </th>
                    <th>
                        <?= HTMLHelper::_('grid.sort', 'SNIPPETS_SNIPPETS_TITLE', 'a.title', $listDirn, $listOrder); ?>
                    </th>
                    <th>
                        <?= HTMLHelper::_('grid.sort', 'SNIPPETS_SNIPPETS_ALIAS', 'a.alias', $listDirn, $listOrder); ?>
                    </th>
                    <th>
                        <?= HTMLHelper::_('grid.sort', 'SNIPPETS_SNIPPETS_TARGET', 'a.target', $listDirn, $listOrder); ?>
                    </th>
                    <?php if ($canEdit || $canDelete) : ?>
                        <th class="center">
                            <?= Text::_('SNIPPETS_SNIPPETS_ACTIONS'); ?>
                        </th>
                    <?php endif; ?>

                </tr>
            </thead>
            <tfoot>
                <tr>
                    <td colspan="<?= isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
                        <div class="pagination">
                            <?= $this->pagination->getPagesLinks(); ?>
                        </div>
                    </td>
                </tr>
            </tfoot>
            <tbody>
                <?php foreach ($this->items as $i => $item) : ?>
                    <?php $canEdit = $user->authorise('core.edit', 'com_snippets'); ?>
                    <?php if (!$canEdit && $user->authorise('core.edit.own', 'com_snippets')) : ?>
                        <?php $canEdit = Factory::getApplication()->getIdentity()->id == $item->created_by; ?>
                    <?php endif; ?>
                    <tr class="row<?= $i % 2; ?>">
                        <td>
                            <?= $item->id; ?>
                        </td>
                        <td>
                            <?php $class = ($canChange) ? 'active' : 'disabled'; ?>
                            <a class="btn btn-micro <?= $class; ?>"
                                href="<?= ($canChange) ? Route::_('index.php?option=com_snippets&task=snippet.publish&id=' . $item->id . '&state=' . (($item->state + 1) % 2), false, 2) : '#'; ?>">
                                <?php if ($item->state == 1) : ?>
                                    <i class="icon-publish"></i>
                                <?php else : ?>
                                    <i class="icon-unpublish"></i>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td>
                            <?= $item->cat_id_name; ?>
                        </td>
                        <td>
                            <?php $canCheckin = Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_snippets.' . $item->id) || $item->checked_out == Factory::getApplication()->getIdentity()->id; ?>
                            <?php if ($canCheckin && $item->checked_out > 0) : ?>
                                <a href="<?= Route::_('index.php?option=com_snippets&task=snippet.checkin&id=' . $item->id . '&' . Session::getFormToken() . '=1'); ?>">
                                    <?= HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'snippet.', false); ?></a>
                            <?php endif; ?>
                            <a href="<?= Route::_('index.php?option=com_snippets&view=snippet&id=' . (int) $item->id . '&catid=' . (int) $item->cat_id); ?>">
                                <?= $this->escape($item->title); ?></a>
                        </td>
                        <td>
                            <?= $item->alias; ?>
                        </td>
                        <td>
                            <?= $item->target; ?>
                        </td>
                        <?php if ($canEdit || $canDelete) : ?>
                            <td class="center">
                                <?php $canCheckin = Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_snippets.' . $item->id) || $item->checked_out == Factory::getApplication()->getIdentity()->id; ?>
                                <?php if ($canEdit && $item->checked_out == 0) : ?>
                                    <a href="<?= Route::_('index.php?option=com_snippets&task=snippet.edit&id=' . $item->id, false, 2); ?>" class="btn btn-mini" type="button">
                                        <i class="icon-edit"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($canDelete) : ?>
                                    <a href="<?= Route::_('index.php?option=com_snippets&task=snippetform.remove&id=' . $item->id, false, 2); ?>" class="btn btn-mini delete-button" type="button">
                                        <i class="icon-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($canCreate) : ?>
        <a href="<?= Route::_('index.php?option=com_snippets&task=snippetform.edit&id=0', false, 0); ?>" class="btn btn-success btn-sm">
            <i class="icon-plus me-1"></i><?= Text::_('SNIPPETS_ADD_ITEM'); ?>
        </a>
    <?php endif; ?>
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="filter_order" value="" />
    <input type="hidden" name="filter_order_Dir" value="" />
    <?= HTMLHelper::_('form.token'); ?>
</form>

<?php
if ($canDelete) {
    $wa->addInlineScript("
        jQuery(document).ready(function () {
            jQuery('.delete-button').click(deleteItem);
        });
        function deleteItem() {
            if (!confirm(\"" . Text::_('SNIPPETS_DELETE_MESSAGE') . "\")) {
                return false;
            }
        }
    ", [], [], ["jquery"]);
}
?>