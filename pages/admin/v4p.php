<?php
namespace Page\Admin;

use Aqua\Core\App;
use Aqua\Log\ErrorLog;
use Aqua\Site\Page;
use Aqua\SQL\Search;
use Aqua\UI\Form;
use Aqua\UI\Pagination;
use Aqua\UI\Template;
use Aqua\UI\Theme;
use Aqua\Util\ImageUploader;
use V4p\Top;
use V4p\Vote;

class V4p
extends Page
{
	public function index_action()
	{
		$action = null;
		$ids = array();
		if(isset($this->request->data['x-bulk'])) {
			$action = $this->request->getString('action');
			$ids = $this->request->getArray('tops');
		} else if(($token = $this->request->uri->getString('token')) && $token === App::user()->getToken('v4p-action')) {
			$action = $this->request->uri->getString('x-action');
			$ids = array( $this->request->uri->getInt('top') );
		}
		if(!empty($action) && in_array($action, array( 'delete', 'save-order' ))) {
			$this->response->status(302)->redirect(ac_build_url(array( 'path' => array( 'v4p' ) )));
			try {
				if($action === 'delete') {
					if(empty($ids)) return;
					$deleted = array();
					foreach($ids as $id) {
						if(!($top = Top::get($id)) || !Top::delete($top)) continue;
						$deleted[] = htmlspecialchars($top->title);
					}
					if(!empty($deleted)) {
						$count = count($deleted);
						$deleted = implode(', ', $deleted);
						if($count === 1) {
							App::user()->addFlash('success', null, __('plugin-v4p', 'site-deleted', $deleted));
						} else {
							App::user()->addFlash('success', null, __('plugin-v4p', 'x-deleted', $count, $deleted));
						}
					}
				} else  {
					$order = array_unique($this->request->getArray('order'));
					if(($count = count($order)) < 2) return;
					$order = array_map('intval', $order);
					Top::order($order);
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}
			return;
		}
		try {

			$frm = new Form($this->request);
			$frm->enctype = 'multipart/form-data';
			$frm->input('title', true)
				->type('text')
				->required()
		        ->attr('maxlength', 255)
		        ->setLabel(__('plugin-v4p', 'title'));
			$frm->input('url', true)
		        ->type('url')
				->required()
		        ->attr('maxlength', 255)
		        ->setLabel(__('plugin-v4p', 'url'));
			$frm->input('credits', true)
		        ->type('number')
				->required()
				->value(1, false)
		        ->attr('min', 0)
		        ->setLabel(__('plugin-v4p', 'credits'));
			$frm->input('interval', true)
		        ->type('number')
				->required()
		        ->attr('min', 1)
				->value(24, false)
		        ->setLabel(__('plugin-v4p', 'interval'))
				->setDescription(__('plugin-v4p', 'interval-desc'));
			$frm->file('image')
		        ->attr('accept', 'image/png, image/jpeg, image/gif')
		        ->setLabel(__('plugin-v4p', 'image'));
			$frm->validate();
			if($frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('plugin-v4p', 'v4p');
				$tpl = new Template;
				$tpl->set('tops', Top::getAll())
					->set('form', $frm)
					->set('token', App::user()->setToken('v4p-action', 16))
				    ->set('page', $this);
				echo $tpl->render('v4p/admin');
				return;
			}
			$this->response->status(302)->redirect(ac_build_url(array( 'path' => array( 'v4p' ) )));
			try {
				$image = $error = $errorMessage = null;
				if(ac_file_uploaded('image', false, $error, $errorMessage)) {
					$uploader = new ImageUploader;
					$uploader->uploadLocal($_FILES['image']['tmp_name'], $_FILES['image']['name']);
					if($uploader->error || !($path = $uploader->save(\V4p\UPLOAD_DIR))) {
						App::user()->addFlash('warning', null, $uploader->errorStr());
					}
				} else if($error) {
					App::user()->addFlash('warning', null, $errorMessage);
				}
				$top = Top::create(
					$this->request->getString('title'),
					$this->request->getString('url'),
					$image,
					$this->request->getInt('interval'),
					$this->request->getInt('credits')
				);
				if($top) {
					App::user()->addFlash('success', null, __('plugin-v4p', 'site-added', htmlspecialchars($top->title)));
				} else {
					App::user()->addFlash('warning', null, __('plugin-v4p', 'site-not-added'));
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				App::user()->addFlash('error', null, __('application', 'unexpected-error'));
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function edit_action($id = null)
	{
		try {
			if(!$id || !($top = Top::get($id))) {
				$this->error(404);
				return;
			}
			if(isset($this->request->data['x-delete-image']) || (($token = $this->request->uri->getString('token')) && $token = App::user()->getToken('v4p-delete-image')) && $this->request->uri->getString('x-action') === 'delete-image') {
				try {
					$top->deleteImage();
					$top->update(array( 'image' => null ));
					$error = false;
					$message = __('plugin-v4p', 'saved');
				} catch(\Exception $exception) {
					$error = true;
					$message = __('application', 'unexpected-error');
				}
				if($this->request->ajax) {
					$this->theme = new Theme;
					$this->response->setHeader('Content-Type', 'application/json');
					echo json_encode(array(
							'message' => $message,
							'error'   => $error,
							'data'    => array(
								'image_url' => $top->imageUrl,
								'image' => $top->image
							)
						));
				} else {
					$this->response->status(302)->redirect(App::request()->uri->url(array( 'query' => array() )));
					App::user()->addFlash($error ? 'error' : 'success', null, $message);
				}
				return;
			}
			$frm = new Form($this->request);
			$frm->enctype = 'multipart/form-data';
			$frm->file('image')
				->attr('accept', 'image/png, image/jpeg, image/gif')
				->setLabel(__('plugin-v4p', 'image'));
			$frm->input('title', true)
				->type('text')
				->required()
				->attr('maxlength', 255)
				->value(htmlspecialchars($top->title), false)
				->setLabel(__('plugin-v4p', 'title'));
			$frm->input('url', true)
				->type('url')
				->required()
				->attr('maxlength', 255)
				->value(htmlspecialchars($top->url))
				->setLabel(__('plugin-v4p', 'url'), false);
			$frm->input('credits', true)
				->type('number')
				->required()
				->attr('min', 0)
				->value($top->credits, false)
				->setLabel(__('plugin-v4p', 'credits'));
			$frm->input('interval', true)
				->type('number')
				->required()
				->attr('min', 1)
				->value($top->interval, false)
				->setLabel(__('plugin-v4p', 'interval'))
				->setDescription(__('plugin-v4p', 'interval-desc'));
			$frm->submit();
			$frm->validate();
			if(!$this->request->ajax && $frm->status !== Form::VALIDATION_SUCCESS) {
				$this->title = $this->theme->head->section = __('plugin-v4p', 'edit-top');
				$tpl = new Template;
				$tpl->set('top', $top)
					->set('form', $frm)
					->set('token', App::user()->setToken('v4p-delete-image', 16))
				    ->set('page', $this);
				echo $tpl->render('v4p/edit');
				return;
			}
			$message = '';
			$error = false;
			try {
				$updated = 0;
				$options = array();
				if(!$frm->field('title')->getWarning()) {
					$options['title'] = $this->request->getString('title');
				}
				if(!$frm->field('url')->getWarning()) {
					$options['url'] = $this->request->getString('url');
				}
				if(!$frm->field('credits')->getWarning()) {
					$options['credits'] = $this->request->getInt('credits');
				}
				if(!$frm->field('interval')->getWarning()) {
					$options['interval'] = $this->request->getInt('interval');
				}
				if(!$frm->field('image')->getWarning() && ac_file_uploaded('image', false, $errorID, $errorStr)) {
					$uploader = new ImageUploader;
					$uploader->uploadLocal($_FILES['image']['tmp_name'], $_FILES['image']['name']);
					if($uploader->error || !($path = $uploader->save(\V4p\UPLOAD_DIR))) {
						$frm->field('image')->setWarning($uploader->errorStr());
					} else {
						$top->deleteImage();
						$options['image'] = $path;
					}
				} else if(isset($errorID) && $errorID) {
					$frm->field('image')->setWarning($errorStr);
				}
				if($top->update($options)) {
					++$updated;
				}
				if($updated) {
					$message = __('plugin-v4p', 'saved', htmlspecialchars($top->title));
				}
			} catch(\Exception $exception) {
				ErrorLog::logSql($exception);
				$error = true;
				$message = __('application', 'unexpected-error');
			}
			if($this->request->ajax) {
				$this->theme = new Theme;
				$this->response->setHeader('Content-Type', 'application/json');
				$response = array( 'message' => $message, 'error' => $error, 'data' => array(), 'warning' => array()  );
				foreach($frm->content as $key => $field) {
					if($field instanceof Form\FieldInterface && ($warning = $field->getWarning())) {
						$response['warning'][$key] = $warning;
					}
				}
				$response['data'] = array(
					'title'     => $top->title,
					'url'       => $top->url,
					'credits'   => $top->credits,
					'interval'  => $top->interval,
					'image_url' => $top->imageUrl,
					'image'     => $top->image
				);
				echo json_encode($response);
			} else {
				$this->response->status(302)->redirect(App::request()->uri->url(array( 'query' => array() )));
				if($message) {
					App::user()->addFlash($error ? 'error' : 'success', null, $message);
				}
				foreach($frm->content as $field) {
					if($field instanceof Form\FieldInterface && ($warning = $field->getWarning())) {
						App::user()->addFlash('warning', null, $warning);
					}
				}
			}
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}

	public function votes_action()
	{
		$this->theme->head->section = $this->title = __('plugin-v4p', 'v4p-log');
		try {
			$current_page = $this->request->uri->getInt('page', 1, 1);
			$search = Vote::search()
				->calcRows(true)
				->limit(($current_page - 1) * 15, 15);
			if($x = $this->request->uri->getString('ip', '')) {
				$search->where(array( 'ip_address' => array( Search::SEARCH_LIKE, addcslashes($x, '%_\\') . '%' ) ));
			}
			if($x = $this->request->uri->getString('d', false)) {
				$search->where(array( 'display_name' => array( Search::SEARCH_LIKE, '%' . addcslashes($x, '%_\\') . '%' ) ));
			}
			if(($x = $this->request->uri->getArray('s')) && !empty($x)) {
				array_unshift($x, Search::SEARCH_IN);
				$search->where(array( 'top_id' => $x ));
			}
			$search->query();
			$pgn = new Pagination(App::request()->uri, ceil($search->rowsFound / 15), $current_page);
			$tpl = new Template;
			$tpl->set('page', $this)
		        ->set('votes', $search->results)
		        ->set('vote_count', $search->rowsFound)
		        ->set('paginator', $pgn);
			echo $tpl->render('v4p/log');
		} catch(\Exception $exception) {
			ErrorLog::logSql($exception);
			$this->error(500, __('application', 'unexpected-error-title'), __('application', 'unexpected-error'));
		}
	}
}
