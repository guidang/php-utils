<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: safe.inc.php 77 2013-04-10 09:59:38Z xujiakun $
 */

//检测全局变量是否定义
(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) && exit('Access Denied');

//引入语言包
if(file_exists(DISCUZ_ROOT.'./data/plugindata/tools.lang.php')){
	include DISCUZ_ROOT.'./data/plugindata/tools.lang.php';
} else {
	loadcache('pluginlanguage_template');
	loadcache('pluginlanguage_script');
	$scriptlang['tools'] = $_G['cache']['pluginlanguage_script']['tools'];
}
$toolslang = $scriptlang['tools'];

//引入tools函数
require_once DISCUZ_ROOT.'./source/plugin/tools/function/tools.func.php';

//读取版本
include_once(DISCUZ_ROOT.'./source/discuz_version.php');
$xver = preg_replace('/(X|R|C)/im','',DISCUZ_VERSION);

//分页相关
$ppp = 20;
$page = max(1, intval($_GET['page']));
$startlimit = ($page - 1) * $ppp;
$deletes = '';
$extrasql = '';

//filter相关
$filter = $_GET['filter'];
if($filter == 'banned') {
	$extrasql = "AND replacement LIKE '%BANNED%'";
} elseif($filter == 'mod') {
	$extrasql = "AND replacement LIKE '%MOD%'";	
} elseif($filter == 'replace') {
	$extrasql = "AND replacement NOT LIKE '%MOD%' AND replacement NOT LIKE '%BANNED%'";
} else {
	$extrasql = '';	
}

$rule = get_rule();
$_GET['cp'] == '' ? $_GET['cp'] =  'censor_admin' : $_GET['cp'];
$identifier = $_GET['identifier'];
$urls = '&operation='.$operation.'&do='.$do.'&identifier='.$identifier.'&pmod=safe';
showsubmenus($toolslang['aboutsafe'],array(
	array(array('menu' => $toolslang['info_sec'], 'submenu' => array(
			array($toolslang['censor_admin'], 'plugins&cp=censor_admin'.$urls),
            array($toolslang['scan_bbsinfo'], 'plugins&cp=scan_bbsinfo'.$urls),
			array($toolslang['scan_note'], 'plugins&cp=scan_note'.$urls),
		))),
    array(array('menu' => $toolslang['site_sec'], 'submenu' => array(
			array($toolslang['scan_file'], 'plugins&cp=scan_file'.$urls),
			array($toolslang['change_key'], 'plugins&cp=change_key'.$urls),
		))),
        ));
$cparray = array('censor_admin', 'scan_bbsinfo', 'scan_note', 'scan_file', 'change_key');
$cp = !in_array($_GET['cp'], $cparray) ? 'censor_admin' : $_GET['cp'];
define(TOOLS_ROOT, dirname(__FILE__).'/');

require TOOLS_ROOT.'./include/'.$cp.'.inc.php';
showformfooter();

?>