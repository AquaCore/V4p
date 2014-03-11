<?php
namespace V4p;

use Aqua\Core\App;
use Aqua\SQL\Query;
use Aqua\User\Account;

class Vote
{
	public $id;
	public $ipAddress;
	public $evercookie;
	public $accountId;
	public $date;
	public $topId;

	public function top()
	{
		return Top::get($this->topId);
	}

	public function user()
	{
		return Account::get($this->accountId);
	}

	public function date($format)
	{
		return strftime($format, $this->date);
	}

	/**
	 * @return \Aqua\SQL\Search
	 */
	public static function search()
	{
		$columns = array(
			'id'           => 'v.id',
			'ip_address'   => 'v._ip_address',
			'user_id'      => 'v._user_id',
			'evercookie'   => 'v._evercookie',
			'top_id'       => 'v._top_id',
			'date'         => 'v._date',
			'display_name' => 'u._display_name',
			'username'     => 'u._username',
		);

		return Query::search(App::connection())
			->columns($columns)
			->columns(array('date' => 'UNIX_TIMESTAMP(v._date)'))
			->whereOptions($columns)
			->from(ac_table('v4p_log'), 'v')
			->leftJoin(ac_table('users'), 'u.id = v._user_id', 'u')
			->groupBy('v.id')
			->parser(array(__CLASS__, 'parseVoteSql'));
	}

	public static function parseVoteSql(array $data)
	{
		$vote             = new self;
		$vote->id         = (int)$data['id'];
		$vote->ipAddress  = $data['ip_address'];
		$vote->accountId  = (int)$data['user_id'];
		$vote->evercookie = $data['evercookie'];
		$vote->topId      = (int)$data['top_id'];
		$vote->date       = (int)$data['date'];

		return $vote;
	}
}
