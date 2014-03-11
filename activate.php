<?php
use Aqua\Core\App;
use Aqua\Core\L10n;

$dir = __DIR__ . '/images';
if(!is_dir($dir)) {
	mkdir($dir, \Aqua\PUBLIC_DIRECTORY_PERMISSION);
}

$tbl = ac_table('v4p_log');
$tblx = ac_table('v4p_tops');
App::connection()->exec("
CREATE TABLE IF NOT EXISTS `$tbl` (
	id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	_ip_address VARCHAR(46) NOT NULL,
	_user_id    BIGINT UNSIGNED NOT NULL,
	_top_id     SMALLINT UNSIGNED NOT NULL,
	_date       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	_evercookie CHAR(32),
	PRIMARY KEY ( id ),
	INDEX `_{$tbl}__top_id_IN` ( _top_id ),
	INDEX `_{$tbl}__vote_IN` ( _user_id, _evercookie ),
	INDEX `_{$tbl}__date_IN` ( _date )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `$tblx` (
	id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
	_interval   TINYINT UNSIGNED NOT NULL DEFAULT 24,
	_credits    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
	_title      VARCHAR(255) NOT NULL,
	_url        VARCHAR(255) NOT NULL,
	_image      VARCHAR(255),
	_date       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	_order      SMALLINT UNSIGNED NOT NULL,
	PRIMARY KEY( id ),
	INDEX `_{$tblx}__order_IN` ( _order )
) ENGINE = MyIsam
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;
");
