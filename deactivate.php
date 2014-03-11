<?php
use Aqua\Core\App;
use V4p\Top;

$tbl = ac_table('v4p_tops');
$tblx = ac_table('v4p_log');
App::connection()->exec("
DROP TABLE IF EXISTS `$tbl`;
DROP TABLE IF EXISTS `$tblx`;
");

if(!class_exists('V4p\Top')) {
	require __DIR__ . '/lib/Top.php';
}
App::cache()->delete(Top::CACHE_KEY);
