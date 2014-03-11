<?php
use Aqua\Core\App;
use Aqua\Plugin\Plugin;
use Aqua\UI\Sidebar;
use Aqua\UI\Tag;
use V4p\Top;
/**
 * @var $page       \Page\Admin\V4p
 * @var $votes      \V4p\Vote[]
 * @var $vote_count int
 * @var $paginator  \Aqua\UI\Pagination
 */
$useEvercookie = Plugin::get(\V4p\PLUGIN_ID, 'id')->settings->get('evercookie', false);
if($useEvercookie) {
	$colspan = 6;
} else {
	$colspan = 5;
}
$page->theme->template = 'sidebar-right';
$sidebar = new Sidebar;
$frm = new Tag('form');
$frm->attr('method', 'GET')
	->attr('action', App::request()->uri->url());
$sidebar->wrapper($frm);
$datetime_format = App::settings()->get('datetime_format');
ob_start();
ac_form_path();
?>
<input type="text" name="d" value="<?php echo $page->request->uri->getString('d') ?>">
<?php
$sidebar->append('display_name', array(array(
		'title' => __('profile', 'display-name'),
		'content' => ob_get_contents()
	)));
ob_clean();
?>
<input type="text" name="ip" value="<?php echo $page->request->uri->getString('ip') ?>">
<?php
$sidebar->append('ip', array(array(
		'title' => __('profile', 'ip-address'),
		'content' => ob_get_contents()
	)));
ob_clean();
$selected = array_flip($page->request->uri->getArray('s'));
?>
<select name="s[]" multiple>
	<?php foreach(Top::getAll() as $site) : ?>
		<option value="<?php echo $site->id ?>" <?php if(isset($selected[$site->id])) echo 'selected' ?>><?php echo htmlspecialchars($site->title) ?></option>
	<?php endforeach; ?>
</select>
<?php
$sidebar->append('site', array(array(
		'title' => __('plugin-v4p', 'voting-site'),
		'content' => ob_get_contents()
	)))->append('submit', array('class' => 'ac-sidebar-action', array(
		'content' => '<input class="ac-sidebar-submit ac-post-submit" type="submit" value="' . __('application', 'search') . '">'
	)));
ob_end_clean();
$page->theme->set('sidebar', $sidebar);
$base_acc_url = ac_build_url(array(
		'path' => array( 'user' ),
		'action' => 'view',
		'arguments' => array( '' )
	))
?>

<table class="ac-table">
	<thead>
		<tr class="alt">
			<td style="width: 70px"><?php echo __('plugin-v4p', 'id') ?></td>
			<td><?php echo __('profile', 'ip-address') ?></td>
			<td><?php echo __('profile', 'user') ?></td>
			<?php if($useEvercookie) : ?>
				<td><?php echo __('plugin-v4p', 'evercookie') ?></td>
			<?php endif ?>
			<td><?php echo __('plugin-v4p', 'voting-site') ?></td>
			<td style="width: 200px"><?php echo __('plugin-v4p', 'date') ?></td>
		</tr>
	</thead>
	<tbody>
	<?php if(empty($votes)) : ?>
		<tr>
			<td colspan="<?php echo $colspan ?>" class="ac-table-no-result"><?php echo __('application', 'no-search-results') ?></td>
		</tr>
	<?php else: foreach($votes as $vote) : ?>
		<tr>
			<td><?php echo $vote->id ?></td>
			<td><?php echo $vote->ipAddress ?></td>
			<td><a href="<?php echo $base_acc_url . $vote->accountId ?>"><?php echo $vote->user()->display() ?></a></td>
			<?php if($useEvercookie) : ?>
				<td><?php echo htmlspecialchars($vote->evercookie) ?></td>
			<?php endif ?>
			<td><?php echo $vote->top()->title ? htmlspecialchars($vote->top()->title) : __('plugin-v4p', 'untitled') ?></td>
			<td><?php echo $vote->date($datetime_format) ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="<?php echo $colspan ?>" style="text-align: center"><?php echo $paginator->render() ?></td>
		</tr>
	</tfoot>
</table>
<span class="ac-search-result"><?php echo __('application', 'search-results-' . ($vote_count === 1 ? 's' : 'p'), number_format($vote_count)) ?></span>
