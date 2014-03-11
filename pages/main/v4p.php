<?php
namespace Page\Main;

use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Plugin\Plugin;
use Aqua\Site\Page;
use Aqua\UI\Template;
use V4p\Top;

class V4p
extends Page
{
	public function index_action()
	{
		if(($id = $this->request->getInt('v4p-id')) && ($top = Top::get($id))) {
			try {
				$status = $top->vote(App::user());
				if(!($time = (int)Plugin::get(\V4p\PLUGIN_ID, 'id')->settings->get('redirect_time', 0))) {
					$this->response->status(302)->redirect($top->url);
					return;
				}
				$this->response->status(302)->redirect(ac_build_url(array(
							'path'   => array( 'v4p' ),
							'action' => 'vote'
						)));
				$session = array( 'id' => $top->id, 'status' => $status );
				switch($status) {
					case Top::VOTE_RECORDED:
						$session['message'] = __('plugin-v4p', 'thank-you');
						break;
					case Top::VOTE_NOT_LOGGED_IN:
						$session['message'] = __('plugin-v4p', 'login-required');
						break;
					case Top::VOTE_NO_PERMISSION:
						$plugin = Plugin::get(\V4p\PLUGIN_ID, 'id');
						$char_count = $plugin->settings->get('char_count', 0);
						$min_level  = $plugin->settings->get('min_level', 0);
						$session['message'] = __('plugin-v4p', 'lvl-requirement-' . ($char_count > 1 ? 'p' : 's'), $char_count, $min_level);
						break;
					case Top::VOTE_ALREADY_VOTED:
					default:
						$session['message'] = __('plugin-v4p', 'already-voted', $top->interval);
						break;
				}
				App::user()->session->flash('ac-v4p-status', $session);
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
			}
			return;
		}
		$this->theme->head->section = $this->title = __('plugin-v4p', 'vote-for-points');
		$tops = Top::getAll();
		$tpl = new Template;
		$tpl->set('page', $this)
	        ->set('tops', $tops);
		echo $tpl->render('v4p/sites');
	}

	public function vote_action()
	{
		if(!($x = App::user()->session->get('ac-v4p-status')) || !isset($x['id']) || !($top = Top::get($x['id']))) {
			$this->response->status(302)->redirect(ac_build_url(array( 'path' => array( 'v4p' ) )));
			return;
		}
		$this->theme->head->section = $this->title = __('plugin-v4p', 'vote-for-points');
		$url = $top->url;
		$time = (int)Plugin::get(\V4p\PLUGIN_ID, 'id')->settings->get('redirect_time', 0);
		$this->layout = 'v4p';
		$tpl = new Template;
		$tpl->set('page', $this)
	        ->set('time', $time)
	        ->set('status', $x['status'])
	        ->set('message', $x['message'])
	        ->set('url', $top->url);
		echo $tpl->render('v4p/redirect');
		$this->response
			->setHeader('Cache-Control', 'no-store, co-cache, must-revalidate, max-age=0')
			->setHeader('Expires', time() - 1)
			->setHeader('Refresh', $time . '; url=' . $url);
	}
}
