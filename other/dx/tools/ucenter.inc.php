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
$_GET['cp'] == '' ? $_GET['cp'] =  'censor_admin' : $_GET['cp'];
$identifier = $_GET['identifier'];
$urls = '&operation='.$operation.'&do='.$do.'&identifier='.$identifier.'&pmod=ucenter';
showsubmenus($toolslang['aboutmaintain'],array(
    array(array('menu' => $toolslang['aboutucenter'], 'submenu' => array(
		array($toolslang['ucpassword'], 'plugins&cp=ucpassword'.$urls),
        ))),
));
$cparray = array('ucpassword',);
$cp = !in_array($_GET['cp'], $cparray) ? 'ucpassword' : $_GET['cp'];
define(TOOLS_ROOT, dirname(__FILE__).'/');

require TOOLS_ROOT.'./include/'.$cp.'.inc.php';
showformfooter();
?>