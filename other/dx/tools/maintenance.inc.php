<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: index.inc.php 78 2012-04-16 10:02:02Z wangbin $
 */

(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) && exit('Access Denied');

if(file_exists(DISCUZ_ROOT.'./data/plugindata/tools.lang.php')){
	include DISCUZ_ROOT.'./data/plugindata/tools.lang.php';
} else {
	loadcache('pluginlanguage_template');
	loadcache('pluginlanguage_script');
	$scriptlang['tools'] = $_G['cache']['pluginlanguage_script']['tools'];
}

$toolslang = $scriptlang['tools'];
define(TOOLS_ROOT, dirname(__FILE__).'/');
require_once TOOLS_ROOT.'./function/tools.func.php';
$mes = cplang('discuz_message');
showtipss($toolslang['index_direction_tips'], $id = 'tips', $display = TRUE, $mes);
?>