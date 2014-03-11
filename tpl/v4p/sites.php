<?php
use Aqua\UI\ScriptManager;
use Aqua\UI\StyleManager;
use Aqua\Core\App;
use Aqua\Plugin\Plugin;
use V4p\Top;
/**
 * @var $tops \V4p\Top[]
 * @var $page \Page\Main\V4p
 */
$page->theme->head->enqueueLink(StyleManager::style('plugin-v4p'));
$user = App::user();
$plugin = Plugin::get(\V4p\PLUGIN_ID, 'id');
$min_level  = (int)$plugin->settings->get('min_level', 0);
$char_count = (int)$plugin->settings->get('char_count', 0);
if($plugin->settings->get('evercookie', false)) {
	if(!($cookie = App::request()->cookie(\V4p\EVERCOOKIE_ID))) {
		$cookie = bin2hex(secure_random_bytes(16));
		App::response()->setCookie(\V4p\EVERCOOKIE_ID, array(
			'http_only' => false,
			'secure' => false,
			'value' => $cookie,
			'expire' => 31536000 * 3
		));
	}
	$page->theme->addSettings('everCookieSettings', array(
		'cookieID'    => \V4p\EVERCOOKIE_ID,
	    'cookieValue' => $cookie,
	    'history'     => false,
	    'baseurl'     => Plugin::DIRECTORY . '/' . $plugin->folder . '/evercookie/',
	    'domain'      => '.' . \Aqua\DOMAIN
	));
	$page->theme->footer->enqueueScript(ScriptManager::script('v4p-plugin.vote'));
} else {
	$cookie = '';
}
?>
<?php if(!$user->loggedIn()) : ?>
	<div class="ac-warning"><?php echo __('plugin-v4p', 'login-required')?></div>
<?php elseif($min_level > 0 && $char_count > 0 && !$user->account->getMeta(Top::META_KEY, false)) : ?>
	<div class="ac-warning"><?php echo __('plugin-v4p', 'lvl-requirement-' . ($char_count > 1 ? 'p' : 's'), $char_count, $min_level)?></div>
<?php endif; ?>
<?php foreach($tops as $top) : ?>
	<?php
	$time  = 0;
	$voted = ($top->interval > 0 && $user->loggedIn() &&
	         ($time = $top->lastVote($user->account->id, $cookie)) > (time() - ($top->interval * 3600)));
	?>
	<form method="POST" target="_self">
		<div class="ac-v4p-site">
			<?php if($voted) : ?>
				<div class="ac-v4p-interval">
					<div class="alpha"></div>
					<div class="wrapper"><div class="inner">
						<?php echo __('plugin-v4p', 'last-vote', strftime('%A %H:%M', $time), $top->interval) ?>
					</div></div>
				</div>
			<?php endif; ?>
			<input type="hidden" name="v4p-id" value="<?php echo $top->id ?>">
			<div class="ac-v4p-title"><?php echo htmlspecialchars($top->title) ?></div>
			<button type="submit"
			        class="ac-v4p-button"
			        <?php echo $voted ? 'disabled' : '' ?>
				>
				<img src="<?php echo $top->imageUrl ?>">
			</button>
			<div class="ac-v4p-credits"><?php echo __('donation', 'credit-points', number_format($top->credits))?></div>
		</div>
	</form>
<?php endforeach; ?>
