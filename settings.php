<?php
use Aqua\Core\App;
use V4p\Top;
/**
 * @var $this     \Aqua\Plugin\Plugin
 * @var $settings \Aqua\Core\Settings
 * @var $error    array
 * @var $message  string
 */

if((int)$settings->get('char_count', 0) !== (int)$this->settings->get('char_count', 0) ||
   (int)$settings->get('min_level', 0) !== (int)$this->settings->get('min_level', 0)) {
	$tbl = ac_table('user_meta');
	$sth = App::connection()->prepare("DELETE FROM `$tbl` WHERE _key = :key");
	$sth->bindValue(':key', Top::META_KEY, PDO::PARAM_STR);
	$sth->execute();
	$sth->closeCursor();
}

if(!$settings->exists('evercookie') || !$settings->get('evercookie')) {
	$settings->set('evrcookie', '0');
} else {
	$settings->set('evercookie', '1');
}
