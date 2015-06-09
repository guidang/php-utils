<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: censor_scanbbs.inc.php 79 2012-04-16 10:06:12Z wangbin $
 */

(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) && exit('Access Denied');
showformheader("plugins&cp=censor_scanbbs&pmod=safe&operation=$operation&do=$do&identifier=$identifier");
$log = toolsgetsetting('bbsltime');
$logs = explode('|',$log);
showtableheader($toolslang['censor_scanlog']);
$logs[0] = date('Y-m-d H:i',$logs[0]);
$logs[1] = DB::result_first("SELECT username FROM ".DB::table('common_member')." WHERE uid = '$logs[1]'");
echo "<tr><th>$toolslang[censor_scantime]</th><th>$toolslang[censor_scanuser]</th><th>$toolslang[censor_scancount]</th><th>$toolslang[censor_scanrep]</th><th>$toolslang[censor_scanmod]</th><th>$toolslang[censor_scanban]</th></tr>";
showtablerow('','',$logs);
showtablefooter();
?>