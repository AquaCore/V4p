<?php
namespace V4p;

use Aqua\Core\App;
use Aqua\Core\User;
use Aqua\Plugin\Plugin;
use Aqua\Ragnarok\Server;
use Aqua\SQL\Query;
use Aqua\SQL\Search;
use Aqua\User\Account;
use Aqua\Ragnarok\Ragnarok;

class Top
{
	/**
	 * @var int
	 */
	public $id;
	/**
	 * @var string
	 */
	public $title;
	/**
	 * @var string
	 */
	public $url;
	/**
	 * @var string
	 */
	public $image;
	/**
	 * @var string
	 */
	public $imageUrl;
	/**
	 * @var int
	 */
	public $interval;
	/**
	 * @var int
	 */
	public $credits;
	/**
	 * @var \V4p\Top[]
	 */
	public static $tops = array();

	const CACHE_KEY          = 'ac_plugin_v4p_tops';
	const CACHE_TTL          = 0;
	const META_KEY           = 'ac_plugin_v4p_permission';
	const VOTE_RECORDED      = 0;
	const VOTE_NOT_LOGGED_IN = 1;
	const VOTE_NO_PERMISSION = 2;
	const VOTE_ALREADY_VOTED = 3;

	/**
	 * @param array $edit
	 * @return bool
	 */
	public function update(array $edit)
	{
		$values = array();
		$update = '';
		$edit = array_intersect_key($edit, array_flip(array( 'title', 'url', 'image', 'credits', 'interval' )));
		if(empty($edit)) {
			return false;
		}
		$edit = array_map(function($val) { return (is_string($val) ? trim($val) : $val); }, $edit);
		if(isset($edit['title']) && $edit['title'] !== $this->title) {
			$values['title'] = $edit['title'];
			$update.= '_title = ?, ';
		}
		if(isset($edit['url']) && $edit['url'] !== $this->url) {
			$values['url'] = $edit['url'];
			$update.= '_url = ?, ';
		}
		if(isset($edit['credits']) && $edit['credits'] !== $this->credits) {
			$values['credits'] = $edit['credits'];
			$update.= '_credits = ?, ';
		}
		if(isset($edit['interval']) && $edit['interval'] !== $this->interval) {
			$values['interval'] = $edit['interval'];
			$update.= '_interval = ?, ';
		}
		if(array_key_exists('image', $edit) && $edit['image'] !== $this->image) {
			if($edit['image'] === null) {
				$update.= '_image = NULL, ';
			} else {
				$values['image'] = $edit['image'];
				$update.= '_image = ?, ';
			}
		}
		if($update === '') {
			return false;
		}
		$update = substr($update, 0, -2);
		$values[] = $this->id;
		$tbl = ac_table('v4p_tops');
		$sth = App::connection()->prepare("
		UPDATE `$tbl`
		SET {$update}
		WHERE id = ?
		LIMIT 1
		");
		if(!$sth->execute(array_values($values)) || !$sth->rowCount()) {
			return false;
		}
		array_pop($values);
		if(array_key_exists('image', $edit)) {
			if($edit['image'] === null) {
				$this->image = null;
				$this->imageUrl = \Aqua\BLANK;
			} else {
				$this->image = $edit['image'];
				$this->imageUrl = \Aqua\URL . $edit['image'];
			}
		}
		foreach($values as $key => $val) {
			$this->$key = $val;
		}
		return true;
	}

	/**
	 * @return void
	 */
	public function deleteImage()
	{
		if(!$this->image) return;
		$file = \Aqua\ROOT . $this->image;
		if(file_exists($file)) @unlink($file);
	}

	/**
	 * @param \Aqua\Core\User $user
	 * @return int
	 */
	public function vote(User $user)
	{
		if(!App::user()->loggedIn()) {
			return self::VOTE_NOT_LOGGED_IN;
		} else if(!self::canVote($user->account)) {
			return self::VOTE_NO_PERMISSION;
		} else if($this->interval > 0 &&
		          ($time = $this->lastVote(
		                        $user->account->id,
		                        $user->request->cookie(\V4p\EVERCOOKIE_ID)
		          )) > (time() - ($this->interval * 3600))) {
			return self::VOTE_ALREADY_VOTED;
		}
		$tbl = ac_table('v4p_log');
		$sth = App::connection()->prepare("
		INSERT INTO $tbl (_ip_address, _user_id, _top_id, _evercookie)
		VALUES (:ip, :user_id, :top_id, :cookie)
		"
		);
		$sth->bindValue(':ip', $user->request->ipString, \PDO::PARAM_STR);
		$sth->bindValue(':user_id', $user->account->id,  \PDO::PARAM_INT);
		$sth->bindValue(':top_id', $this->id,            \PDO::PARAM_INT);
		if(($cookie = $user->request->cookie(\V4p\EVERCOOKIE_ID, false))) {
			$sth->bindValue(':cookie', $cookie, \PDO::PARAM_STR);
		} else {
			$sth->bindValue(':cookie', null, \PDO::PARAM_NULL);
		}
		$sth->execute();
		$tbl = ac_table('users');
		$sth = App::connection()->prepare("
		UPDATE $tbl
		SET _credits = _credits + :credits
		WHERE id = :id LIMIT 1;
		");
		$sth->bindValue(':credits', $this->credits, \PDO::PARAM_INT);
		$sth->bindValue(':id', $user->account->id, \PDO::PARAM_INT);
		$sth->execute();
		return self::VOTE_RECORDED;
	}

	/**
	 * @param int    $userId
	 * @param string $evercookie
	 * @return int
	 */
	public function lastVote($userId, $evercookie)
	{
		$select = Query::select(App::connection())
			->columns(array( 'date' => 'UNIX_TIMESTAMP(_date)' ))
			->setColumnType(array( 'date' => 'timestamp' ))
			->from(ac_table('v4p_log'))
			->where(array( '_top_id' => $this->id ))
			->order(array( '_date' => 'DESC' ))
			->limit(1);
		if(Plugin::get(\V4p\PLUGIN_ID, 'id')->settings->get('evercookie', false) && !empty($evercookie)) {
			$select->where(array(array( '_user_id' => $userId, 'OR', '_evercookie' => $evercookie )));
		} else {
			$select->where(array( '_user_id' => $userId ));
		}
		$select->query();
		return $select->get('date', 0);
	}

	/**
	 * @return int
	 */
	public function votesTotal()
	{
		return Query::select(App::connection())
			->columns(array( 'count' => 'COUNT(1)' ))
			->setColumnType(array( 'count' => 'integer' ))
			->from(ac_table('v4p_log'))
			->where(array( '_top_id' => $this->id ))
			->query()
			->get('count', 0);
	}

	/**
	 * @return int
	 */
	public function votesToday()
	{
		return Query::select(App::connection())
			->columns(array( 'count' => 'COUNT(1)' ))
			->setColumnType(array( 'count' => 'integer' ))
			->from(ac_table('v4p_log'))
			->where(array(
				'_top_id' => $this->id,
			    '_date'   => array( Search::SEARCH_HIGHER, date('Y-m-d') )
			))
			->query()
			->get('count', 0);
	}

	/**
	 * @param $id
	 * @return \V4p\Top
	 */
	public static function get($id)
	{
		if(!(self::$tops = App::cache()->fetch('v4p_plugin_tops')) || !isset(self::$tops[$id])) {
			$select = Query::select(App::connection())
				->columns(array(
					'id'       => 'id',
					'interval' => '_interval',
					'credits'  => '_credits',
					'title'    => '_title',
					'url'      => '_url',
					'image'    => '_image',
				))
				->from(ac_table('v4p_tops'))
				->where(array( 'id' => $id ))
				->order(array( '_order' => 'ASC' ))
				->parser(array( __CLASS__, 'parseTopSql' ))
				->limit(1)
				->query();
			return ($select->valid() ? $select->current() : null);
		}
		return self::$tops[$id];
	}

	/**
	 * @return array
	 */
	public static function getAll()
	{
		if(!(self::$tops = App::cache()->fetch('v4p_plugin_tops'))) {
			self::$tops = Query::select(App::connection())
				->columns(array(
					'id'       => 'id',
				    'interval' => '_interval',
				    'credits'  => '_credits',
				    'title'    => '_title',
				    'url'      => '_url',
				    'image'    => '_image',
				))
				->from(ac_table('v4p_tops'))
				->order(array( '_order' => 'ASC' ))
				->parser(array( __CLASS__, 'parseTopSql' ))
				->query()
				->results ?: array();
			self::rebuildCache();
		}
		return array_values(self::$tops);
	}

	/**
	 * @param $title      string
	 * @param $url        string
	 * @param $image      string
	 * @param $interval   int
	 * @param $credits    int
	 * @return null|\V4p\Top
	 */
	public static function create($title, $url, $image, $interval, $credits)
	{
		$tbl   = ac_table('v4p_tops');
		$sth   = App::connection()->query("SELECT COUNT(1) FROM $tbl");
		$sth->execute();
		$order = (int)$sth->fetchColumn(0);
		$sth   = App::connection()->prepare("
		INSERT INTO $tbl (
		_title,
		_interval,
		_credits,
		_url,
		_image,
		_order
		)
		VALUES (?, ?, ?, ?, ?, ?)
		"
		);
		$sth->bindValue(1, $title,      \PDO::PARAM_STR);
		$sth->bindValue(2, $interval,   \PDO::PARAM_INT);
		$sth->bindValue(3, $credits,    \PDO::PARAM_INT);
		$sth->bindValue(4, $url,        \PDO::PARAM_STR);
		$sth->bindValue(6, $order,      \PDO::PARAM_INT);
		if($image === null) $sth->bindValue(5, null, \PDO::PARAM_NULL);
		else $sth->bindValue(5, $image, \PDO::PARAM_STR);
		$sth->execute();
		if($sth->rowCount()) {
			$id              = (int)App::connection()->lastInsertId();
			$top             = new self;
			$top->id         = $id;
			$top->title      = $title;
			$top->interval   = (int)$interval;
			$top->credits    = (int)$credits;
			$top->url        = $url;
			$top->image      = $image;
			self::$tops[$id] = $top;
			self::rebuildCache();
			return $top;
		} else {
			return null;
		}
	}

	/**
	 * @param $top \V4p\Top
	 * @return bool
	 */
	public static function delete(self $top)
	{
		$top->deleteImage();
		$tbl  = ac_table('v4p_tops');
		$tblx = ac_table('v4p_log');
		$sth = App::connection()->prepare("
		DELETE FROM `$tbl` WHERE id = :id LIMIT 1;
		DELETE FROM `$tblx` WHERE _top_id = :id;
		");
		$sth->bindValue(':id', $top->id, \PDO::PARAM_INT);
		$sth->execute();
		return (bool)$sth->rowCount();
	}

	/**
	 * @param \Aqua\User\Account $account
	 * @return bool
	 */
	public static function canVote(Account $account)
	{
		$plugin = Plugin::get(\V4p\PLUGIN_ID, 'id');
		$minLevel  = (int)$plugin->settings->get('min_level', 0);
		$char_count = (int)$plugin->settings->get('char_count', 0);
		if($minLevel < 1 || $char_count < 1) {
			return true;
		}
		$permissions = $account->getMeta(self::META_KEY, false);
		if($permissions) {
			return true;
		}
		$status = false;
		$count  = 0;
		foreach(Server::$servers as $server) {
			foreach($server->charmap as $charmap) {
				$count += Query::select($charmap->connection())
					->columns(array( 'count' => 'COUNT(1)' ))
					->setColumnType(array( 'count' => 'integer' ))
					->from($charmap->table('char', 'c'))
					->innerJoin($server->login->table('login'), null, 'l')
					->where(array( 'l.ac_user_id' => $account->id, 'c.base_level' => $minLevel ))
					->query()
					->get('count', 0);
				if($count >= $char_count) {
					$account->setMeta(self::META_KEY, 1);
					$status = true;
					reset($server->charmap);
					break 2;
				}
			}
			reset($server->charmap);
		}
		reset(Server::$servers);
		return $status;
	}

	public static function order(array $newOrder)
	{
		$newOrder = array_unique($newOrder);
		$oldOrder = Query::select(App::connection())
		                 ->columns(array( 'id' => 'id', 'order' => '_order' ))
		                 ->setColumnType(array( 'id' => 'integer' , 'order' => 'integer'))
		                 ->from(ac_table('v4p_tops'))
		                 ->query()
		                 ->getColumn('order', 'id');
		if(empty($oldOrder)) {
			return false;
		}
		$update = Query::update(App::connection());
		$table  = ac_table('v4p_tops');
		foreach($newOrder as $id => $slot) {
			if(!array_key_exists($id, $oldOrder)) {
				return false;
			}
			if($oldOrder[$id] === $slot) {
				continue;
			}
			$update->tables(array( "t$id" => $table ))
			       ->set(array( "t$id._order" => $slot ))
			       ->where(array( "t$id.id" => $id ));
			if(($otherId = array_search($slot, $oldOrder)) !== false &&
			   !array_key_exists($otherId, $newOrder)) {
				$update->tables(array( "t$otherId" => $table ))
				       ->set(array( "t$otherId._order" => $oldOrder[$id] ))
				       ->where(array( "t$otherId.id" => $otherId ));
			}
		}
		if(empty($update->set)) {
			return false;
		}
		$update->query();
		if($update->rowCount) {
			self::clearCache();
		}
		return (bool)$update->rowCount;
	}

	public static function rebuildCache()
	{
		App::cache()->store(self::CACHE_KEY, self::$tops, self::CACHE_TTL);
	}

	public static function clearCache()
	{
		self::$tops = array();
		App::cache()->delete(self::CACHE_KEY);
	}

	/**
	 * @param array $data
	 * @return \V4p\Top
	 */
	public static function parseTopSql(array $data)
	{
		if(!isset(self::$tops[$data['id']])) {
			self::$tops[$data['id']] = new self;
		}
		$top            = self::$tops[$data['id']];
		$top->id        = (int)$data['id'];
		$top->title     = $data['title'];
		$top->url       = $data['url'];
		$top->image     = $data['image'];
		if($top->image) {
			$top->imageUrl  = \Aqua\URL . $top->image;
		} else {
			$top->imageUrl = \Aqua\BLANK;
		}
		$top->interval  = (int)$data['interval'];
		$top->credits   = (int)$data['credits'];
		return $top;
	}
}
