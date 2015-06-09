<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: aboutucenter.inc.php 79 2013-04-16 10:06:12Z xujiakun $
 */

(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) && exit('Access Denied');

$mod = in_array($_GET['mod'],array('ucpassword')) ? $_GET['mod'] : 'ucpassword';

if(submitcheck('ucpasswordsubmit') && $_GET['password']){
	$configfile = DISCUZ_ROOT."./uc_server/data/config.inc.php";

	$salt = substr(uniqid(rand()), 0, 6);
	$ucpassword = $_GET['password'];
	$md5_uc_password = md5(md5($ucpassword).$salt);
	$config = file_get_contents($configfile);
	$config = preg_replace("/define\('UC_FOUNDERSALT',\s*'.*?'\);/i", "define('UC_FOUNDERSALT', '$salt');", $config);
	$config = preg_replace("/define\('UC_FOUNDERPW',\s*'.*?'\);/i", "define('UC_FOUNDERPW', '$md5_uc_password');", $config);
	$fp = @fopen($configfile, 'w');
	@fwrite($fp, $config);
	@fclose($fp);
	cpmsg($toolslang['ucpasswordsuccess'],"action=plugins&pmod=ucenter&cp=ucpassword&operation=$operation&do=$do&identifier=$identifier",'succeed');
	showformheader("plugins&pmod=ucenter&cp=ucpassword&operation=$operation&do=$do&identifier=$identifier",'submit');
	showtableheaders($toolslang['ucpassword']);
	showtablerow('', array('class="td21"'), array(
		$toolslang['ucpassword_tip'],
		'<input type="text" class="txt" name="password" value="" /><input type="submit" class="btn" name="ucpasswordsubmit" value="'.$lang['submit'].'" />'
	));
	showtablefooter();
	showformfooter();
}else{
	showformheader("plugins&pmod=ucenter&cp=ucpassword&operation=$operation&do=$do&identifier=$identifier",'submit');
	showtableheaders($toolslang['ucpassword']);
	showtablerow('', array('class="td21"'), array(
		$toolslang['ucpassword_tip'],
		'<input type="text" class="txt" name="password" value="" /><input type="submit" class="btn" name="ucpasswordsubmit" value="'.$lang['submit'].'" />'
	));
	showtablefooter();
	showformfooter();
}

?>