<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: index.inc.php 78 2013-04-09 10:02:02Z xujiakun $
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
define(TOOLS_ROOT, dirname(__FILE__).'/');
require_once TOOLS_ROOT.'./function/tools.func.php';

//展示首页提示
$mes = cplang('discuz_message');
showtipss($toolslang['index_direction_tips'], $id = 'tips', $display = TRUE, $mes);

?>