<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: censor_admin.inc.php 79 2013-04-10 09:59:38Z xujiakun $
 */

(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) && exit('Access Denied');

if(submitcheck('censorsubmit')){

	if($ids = dimplode($_GET['delete'])) {
		DB::delete('common_word', "id IN ($ids) AND ('{$_G['adminid']}'='1' OR admin='{$_G['username']}')");
	}

	if(is_array($_GET['find'])) {
		foreach($_GET['find'] as $id => $val) {
			$_GET['find'][$id]  = $val = trim(str_replace('=', '', $_GET['find'][$id]));
			if(strlen($val) < 3) {
				cpmsg('censor_keywords_tooshort', '', 'error');
			}
			$_GET['replace'][$id] = $_GET['replace'][$id] == '{REPLACE}' ? $_GET['replacecontent'][$id] : $_GET['replace'][$id];
			$_GET['replace'][$id] = daddslashes(str_replace("\\\'", '\'', $_GET['replace'][$id]), 1);
			DB::update('common_word', array(
				'find' => $_GET['find'][$id],
				'replacement' => $_GET['replace'][$id],
			), "id='$id' AND ('{$_G['adminid']}'='1' OR admin='{$_G['username']}')");
		}
	}

	$newfind_array = !empty($_GET['newfind']) ? $_GET['newfind'] : array();
	$newreplace_array = !empty($_GET['newreplace']) ? $_GET['newreplace'] : array();
	$newreplacecontent_array = !empty($_GET['newreplacecontent']) ? $_GET['newreplacecontent'] : array();
	
	foreach($newfind_array as $key => $value) {
		$newfind = trim(str_replace('=', '', $newfind_array[$key]));
		$newreplace  = trim($newreplace_array[$key]);
		
		if($newfind != '') {
			if(strlen($newfind) < 3) {
				cpmsg('censor_keywords_tooshort', '', 'error');
			}
			if($newreplace == '{REPLACE}') {
				$newreplace = daddslashes(str_replace("\\\'", '\'', $newreplacecontent_array[$key]), 1);
			}
			if($oldcenser = DB::fetch_first("SELECT admin FROM ".DB::table('common_word')." WHERE find='$newfind'")) {
				cpmsg('censor_keywords_existence', '', 'error');
			} else {
				DB::insert('common_word', array(
					'admin' => $_G['username'],
					'find' => $newfind,
					'replacement' => $newreplace,
				));
			}
		}
	}

	updatecache('censor');
	cpmsg('censor_succeed', "action=plugins&cp=censor&pmod=safe&operation=$operation&do=$do&page=$page", 'succeed');
}
if(submitcheck('censorsercsubmit')) {
	if($_GET['beforeword']) {
		$extrasql = "AND find LIKE '%$_GET[beforeword]%'";	
	}
	//echo $extrasql = "AND find LIKE %$_GET[beforeword]%";exit;
}
showformheader("plugins&cp=censor_admin&pmod=safe&operation=$operation&do=$do");
showtableheaders($toolslang['censor_admin'],'censor');
	showsubmit('censorsercsubmit', $toolslang['censorsearch'], $toolslang['find'].' <input name="beforeword" value="" class="txt" />');
	$count = DB::result_first("SELECT COUNT(*) FROM ".DB::table('common_word')." w WHERE 1 $extrasql");
	$multipage = multi($count, $ppp, $page, ADMINSCRIPT."?action=plugins&cp=censor_admin&pmod=safe&operation=$operation&do=$do&filter=$filter");
	echo "<td>".$toolslang['tips'].$count.
		$toolslang['filter'].
		"<a href='$BASESCRIPT?action=plugins&cp=censor_admin&pmod=safe&operation=$operation&do=$do&filter=banned'><font color=red>$toolslang[censor_banned]</font></a> ".
		"<a href='$BASESCRIPT?action=plugins&cp=censor_admin&pmod=safe&operation=$operation&do=$do&filter=mod'><font color=green>$toolslang[censor_mod]</font></a> ".
		"<a href='$BASESCRIPT?action=plugins&cp=censor_admin&pmod=safe&operation=$operation&do=$do&filter=replace'><font color=magenta>$toolslang[censor_re]</font></a> ".
		"<a href='$BASESCRIPT?action=plugins&cp=censor_admin&pmod=safe&operation=$operation&do=$do'>$toolslang[censor_all]</a>".
		"</td>";
	showtablefooter();

	showtableheader($toolslang['censor_view'], 'fixpadding');
	showsubtitle(array('', 'misc_censor_word', 'misc_censor_replacement', 'operator'));
	
	$query = DB::query("SELECT * FROM ".DB::table('common_word')." WHERE 1 $extrasql ORDER BY find ASC LIMIT $startlimit, $ppp");
	while($censor =	DB::fetch($query)) {
		$censor['replacement'] = dstripslashes($censor['replacement']);
		$censor['replacement'] = dhtmlspecialchars($censor['replacement']);
		$censor['find'] = dhtmlspecialchars($censor['find']);
		$disabled = $_G['adminid'] != 1 && $censor['admin'] != $_G['member']['username'] ? 'disabled' : NULL;
		if(in_array($censor['replacement'], array('{BANNED}', '{MOD}'))) {
			$replacedisplay = 'style="display:none"';
			$optionselected = array();
			foreach(array('{BANNED}', '{MOD}') as $option) {
				$optionselected[$option] = $censor['replacement'] == $option ? 'selected' : '';
			}
		} else {
			$optionselected['{REPLACE}'] = 'selected';
			$replacedisplay = '';
		}
		showtablerow('', array('class="td25"', '', '', 'class="td26"'), array(
			"<input class=\"checkbox\" type=\"checkbox\" name=\"delete[]\" value=\"$censor[id]\" $disabled>",
			"<input type=\"text\" class=\"txt\" size=\"30\" name=\"find[$censor[id]]\" value=\"$censor[find]\" $disabled>",
			'<select name="replace['.$censor['id'].']" onchange="if(this.options[this.options.selectedIndex].value==\'{REPLACE}\'){$(\'divbanned'.$censor['id'].'\').style.display=\'\';$(\'divbanned'.$censor['id'].'\').value=\'\';}else{$(\'divbanned'.$censor['id'].'\').style.display=\'none\';}" '.$disabled.'>
			<option value="{BANNED}" '.$optionselected['{BANNED}'].'>'.cplang('misc_censor_word_banned').'</option><option value="{MOD}" '.$optionselected['{MOD}'].'>'.cplang('misc_censor_word_moderated').'</option><option value="{REPLACE}" '.$optionselected['{REPLACE}'].'>'.cplang('misc_censor_word_replaced').'</option></select>
			<input class="txt" type="text" size="10" name="replacecontent['.$censor['id'].']" value="'.$censor['replacement'].'" id="divbanned'.$censor['id'].'" '.$replacedisplay.' '.$disabled.'>',
			$censor['admin']
		));
	}
	$misc_censor_word_banned = cplang('misc_censor_word_banned');
	$misc_censor_word_moderated = cplang('misc_censor_word_moderated');
	$misc_censor_word_replaced = cplang('misc_censor_word_replaced');
	echo <<<EOT
	<script type="text/JavaScript">
	var rowtypedata = [
	[[1,''], [1,'<input type="text" class="txt" size="30" name="newfind[]">'], [1, ' <select onchange="if(this.options[this.options.selectedIndex].value==\'{REPLACE}\'){this.nextSibling.style.display=\'\';}else{this.nextSibling.style.display=\'none\';}" name="newreplace[]" $disabled><option value="{BANNED}">$misc_censor_word_banned</option><option value="{MOD}">$misc_censor_word_moderated</option><option value="{REPLACE}">$misc_censor_word_replaced</option></select><input class="txt" type="text" size="15" name="newreplacecontent[]" style="display:none;">'], [1,'']],
	];
	</script>
EOT;
	echo '<tr><td></td><td colspan="2"><div><a href="###" onclick="addrow(this, 0)" class="addtr">'.$lang['add_new'].'</a></div></td></tr>';
	
	showsubmit('censorsubmit', 'submit', 'del', "<input type=hidden value=$page name=page />", $multipage);
	showtablefooter();

?>