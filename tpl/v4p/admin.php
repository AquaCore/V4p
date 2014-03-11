<?php
use Aqua\Core\App;
use Aqua\Core\L10n;
use Aqua\UI\Sidebar;
use Aqua\UI\Tag;
use Aqua\UI\ScriptManager;
use V4p\Top;
/**
 * @var $form  \Aqua\UI\Form
 * @var $tops  \V4p\Top[]
 * @var $token string
 * @var $page  \Page\Admin\V4p
 */
$page->theme->template = 'sidebar-right';
$page->theme->footer->enqueueScript(ScriptManager::script('aquacore.ajax-form'));
$page->theme->footer->enqueueScript('theme.form-functions')
	->type('text/javascript')
	->src($page->theme->url . '/scripts/ajax-form-functions.js');
$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui.timepicker'));
$page->theme->footer->enqueueScript(ScriptManager::script('plugin-v4p'));
if(L10n::getDefault()->code !== 'en') {
	$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui.timepicker-i18n', array(
				'language' => L10n::getDefault()->code
			)));
	$page->theme->footer->enqueueScript(ScriptManager::script('jquery-ui-i18n', array(
				'language' => L10n::getDefault()->code
			)));
}
$page->theme->addWordGroup('plugin-v4p', array( 'confirm-delete-s', 'confirm-delete-p', 'edit-top', 'x-hours' ));
$sidebar = new Sidebar;
$sidebar->wrapper($form->buildTag());
ob_start();
?>
<div class="ac-form-warning"><?php echo $form->field('image')->getWarning() ?></div>
<?php echo $form->field('image')->render();
$sidebar->append('image', array(array(
		'title' => $form->field('image')->getLabel(),
		'content' => ob_get_contents()
	)));
ob_clean();
?>
<div class="ac-form-warning"><?php echo $form->field('title')->getWarning() ?></div>
<?php echo $form->field('title')->render();
$sidebar->append('title', array(array(
		'title' => $form->field('title')->getLabel(),
		'content' => ob_get_contents()
	)));
ob_clean();
?>
<div class="ac-form-warning"><?php echo $form->field('url')->getWarning() ?></div>
<?php echo $form->field('url')->render();
$sidebar->append('Url', array(array(
		'title' => $form->field('url')->getLabel(),
		'content' => ob_get_contents()
	)));
ob_clean();
?>
<table style="width: 100%">
	<tr><td colspan="2" class="ac-form-warning"><?php echo $form->field('credits')->getWarning() ?></td></tr>
	<tr>
		<td style="padding: 0 10px;"><?php echo $form->field('credits')->getLabel() ?></td>
		<td><?php echo $form->field('credits')->render() ?></td>
	</tr>
	<tr><td colspan="2" class="ac-form-warning"><?php echo $form->field('interval')->getWarning() ?></td></tr>
	<tr>
		<td style="width: 40%; padding: 0 10px;"><?php echo $form->field('interval')->getLabel() ?></td>
		<td><?php echo $form->field('interval')->render() ?></td>
	</tr>
</table>
<input class="ac-sidebar-submit ac-post-submit" type="submit" value="<?php echo __('application', 'submit') ?>">
<?php
$sidebar->append('credits', array(
		'class' => 'ac-sidebar-action',
		array(
		'title' => __('plugin-v4p', 'voting-options'),
		'content' => ob_get_contents()
	)))
;
ob_end_clean();
$page->theme->set('sidebar', $sidebar);
$html = '';
?>
<form method="POST">
<table class="ac-table ac-v4p-table">
	<thead>
		<tr>
			<td colspan="8" style="text-align: right">
				<select name="action">
					<option value="save-order"><?php echo __('plugin-v4p', 'save-order') ?></option>
					<option value="delete"><?php echo __('plugin-v4p', 'delete') ?></option>
				</select>
				<input type="submit" name="x-bulk" value="<?php echo __('application', 'apply') ?>">
			</td>
		</tr>
		<tr class="alt">
			<td style="width: 30px; text-align: center"><input type="checkbox" ac-checkbox-toggle="tops[]"></td>
			<td style="width: 200px;"><?php echo __('plugin-v4p', 'image') ?></td>
			<td><?php echo __('plugin-v4p', 'title') ?></td>
			<td style="width: 90px"><?php echo __('plugin-v4p', 'credits') ?></td>
			<td style="width: 120px"><?php echo __('plugin-v4p', 'interval') ?></td>
			<td style="width: 90px"><?php echo __('plugin-v4p', 'votes-total') ?></td>
			<td style="width: 90px"><?php echo __('plugin-v4p', 'votes-today') ?></td>
			<td><?php echo __('application', 'action') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($tops)) : ?>
		<tr><td colspan="8" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td></tr>
	<?php else : foreach($tops as $top) : ?>
		<tr ac-v4p-site-id="<?php echo $top->id ?>">
			<td style="text-align: center">
				<input type="checkbox" name="tops[]" value="<?php echo $top->id ?>">
				<input type="hidden" name="order[]" value="<?php echo $top->id ?>">
			</td>
			<td class="ac-v4p-site-image"><img src="<?php echo $top->imageUrl ?>"></td>
			<td class="ac-v4p-site-title"><?php echo htmlspecialchars($top->title) ?></td>
			<td class="ac-v4p-site-credits"><?php echo number_format($top->credits) ?></td>
			<td class="ac-v4p-site-interval"><?php echo __('plugin-v4p', 'x-hours',  number_format($top->interval)) ?></td>
			<td class="ac-v4p-site-votes"><?php echo number_format($top->votesTotal()) ?></td>
			<td class="ac-v4p-site-votes"><?php echo number_format($top->votesToday()) ?></td>
			<td class="ac-actions">
				<a href="<?php $edit_url = ac_build_url(array(
					'path' => array( 'v4p' ),
					'action' => 'edit',
					'arguments' => array( $top->id )
				)); echo $edit_url; ?>"><button class="ac-action-edit" type="button" value="<?php echo $top->id ?>"><?php echo __('plugin-v4p', 'edit') ?></button></a>
				<a rel="nofollow" href="<?php echo ac_build_url(array(
					'path' => array( 'v4p' ),
					'query' => array(
						'token' => $token,
						'top' => $top->id,
						'x-action' => 'delete'
				))) ?>"><button class="ac-action-delete" type="button"><?php echo __('plugin-v4p', 'delete') ?></button></a>
			</td>
		</tr>
	<?php ob_start() ?>
		<div class="ac-settings" ac-v4p-site-id="<?php echo $top->id ?>">
			<form method="POST" enctype="multipart/form-data" action="<?php echo $edit_url; ?>">
				<table>
					<tr>
						<td></td>
						<td style="text-align: center">
							<div class="ac-delete-wrapper">
								<img src="<?php echo $top->imageUrl ?>">
								<input type="submit" class="ac-delete-button" name="x-delete-image" value="" <?php if(!$top->image) echo 'style="display: none"' ?>>
							</div>
						</td>
					</tr>
					<tr class="ac-form-warning"><td colspan="2"><div></div></td></tr>
					<tr class="ac-form-field">
						<td class="ac-form-label"><?php echo __('plugin-v4p', 'image') ?></td>
						<td class="ac-form-tag">
							<input type="file" class="ac-v4p-image" name="image" accept="image/gif, image/png, image/jpeg">
						</td>
					</tr>
					<tr class="ac-form-warning"><td colspan="2"><div></div></td></tr>
					<tr class="ac-form-field">
						<td class="ac-form-label"><?php echo __('plugin-v4p', 'title') ?></td>
						<td class="ac-form-tag"><input type="text" name="title" value="<?php echo htmlspecialchars($top->title) ?>"></td>
					</tr>
					<tr class="ac-form-warning"><td colspan="2"><div></div></td></tr>
					<tr class="ac-form-field">
						<td class="ac-form-label"><?php echo __('plugin-v4p', 'url') ?></td>
						<td class="ac-form-tag"><input type="text" name="url" value="<?php echo htmlspecialchars($top->url) ?>"></td>
					</tr>
					<tr class="ac-form-warning"><td colspan="2"><div></div></td></tr>
					<tr class="ac-form-field">
						<td class="ac-form-label"><?php echo __('plugin-v4p', 'credits') ?></td>
						<td class="ac-form-tag"><input type="number" min="0" name="credits" value="<?php echo $top->credits ?>"></td>
					</tr>
					<tr class="ac-form-warning"><td colspan="2"><div></div></td></tr>
					<tr class="ac-form-field">
						<td class="ac-form-label"><?php echo __('plugin-v4p', 'interval') ?></td>
						<td class="ac-form-tag"><input type="number" min="1" name="interval" value="<?php echo $top->interval ?>"></td>
					</tr>
					<tr class="ac-form-field">
						<td class="ac-form-tag" colspan="2" style="text-align: right;">
							<div class="ac-form-response"></div>
							<input type="submit" value="<?php echo __('application', 'submit') ?>" ac-default-submit>
						</td>
					</tr>
				</table>
			</form>
		</div>
	<?php $html.= ob_get_contents(); ob_end_clean(); ?>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="8"></td>
		</tr>
	</tfoot>
</table>
</form>
<?php
echo $html;
