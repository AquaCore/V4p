<?php
use Aqua\Core\App;
use Aqua\UI\Template;
use Aqua\UI\ScriptManager;
use Aqua\UI\StyleManager;
/**
 * @var $this       \Aqua\Plugin\Plugin
 * @var $dispatcher \Aqua\Site\Dispatcher
 */

$dispatcher = App::registryGet('ac_dispatcher');

define('V4p\UPLOAD_DIR', \Aqua\Plugin\Plugin::DIRECTORY . "/{$this->folder}/images");
define('V4p\PLUGIN_ID', $this->id);
define('V4p\EVERCOOKIE_ID', 'aquacore_v4p_id');

App::autoloader('Page')->addDirectory(__DIR__ . '/pages');
App::autoloader('V4p')->addDirectory(__DIR__ . '/lib');

array_push(Template::$directories, \Aqua\Plugin\Plugin::DIRECTORY . "/{$this->folder}/tpl");

switch(\Aqua\PROFILE) {
	case 'MAIN':
		$dispatcher->permissions->set('main/v4p')->allowAll();
		StyleManager::register('plugin-v4p')
			->href($this->url . '/assets/css/v4p.css');
		ScriptManager::register('swfobject')
			->src($this->url . '/evercookie/swfobject-2.2.min.js');
		ScriptManager::register('evercookie')
			->src($this->url . '/evercookie/evercookie.js')
			->dependsOn('swfobject');
		ScriptManager::register('v4p-plugin.vote')
			->src($this->url . '/assets/js/vote.js')
			->dependsOn('evercookie');
		break;
	case 'ADMINISTRATION':
		$url = ac_build_url(array(
			'path' => array( 'v4p' ),
			'action' => ''
		));
		$menu = App::registryGet('ac_admin_menu');
		$menu->add('pugin.v4p', $menu->pos('ragnarok') + 1, array(
			'class' => array( 'option-v4p' ),
			'title' => __('admin-menu', 'v4p'),
			'url'   => $url . 'index',
			'submenu' => array(
				array(
					'title' => __('admin-menu', 'v4p-sites'),
					'url'   => $url . 'index',
				),
				array(
					'title' => __('admin-menu', 'v4p-log'),
					'url'   => $url . 'votes',
				)
			)
		));
		ScriptManager::register('plugin-v4p')
			->src($this->url . '/assets/js/admin.js')
			->dependsOn( 'jquery' );
		App::$styleSheet.=
				'.option-v4p .menu-option-icon {' .
				'background: transparent url("' . $this->url . '/assets/images/admin.png") center center no-repeat;' .
				'}' .
				'.option-v4p:hover .menu-option-icon {' .
				'background: transparent url("' . $this->url . '/assets/images/admin-h.png") center center no-repeat;' .
				'}' .
				'.ac-v4p-site-image {' .
				'text-align: center;' .
				'}' .
				'.ac-v4p-site-image img {' .
				'max-height: 50px;' .
				'max-width: 200px;' .
				'}';
		break;
}
