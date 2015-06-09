<?php
/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: tools.php 111 2013-03-18 02:43:43Z xujiakun $
 */

/**
 * 密码要求：1、必须且只能包含大写字母、小写字母、数字
 *           2、密码长度大于6
 * 修改下面的 $password = ''，单引号中按照密码要求写入你的密码，举例 $password = 'DiscuzX3' ，注意：请不要把密码设置成 DiscuzX3，以免被人获知。
 */
$tpassword = '188281MWWxjk';

/*************************************以下部分为tools工具箱的核心代码，请不要随意修改**************************************/

error_reporting(0);
define('TMAGIC_QUOTES_GPC', function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc());

define('TOOLS_ROOT', dirname(__FILE__).'/');
define('TOOLS_VERSION', 'Tools 3.0.0');
define('TOOLS_DISCUZ_VERSION', 'Discuz! X3.0');

define('TDISCUZ_ROOT', substr(dirname(dirname(__FILE__)), 0, -13));

$tools_versions = TOOLS_VERSION;
$tools_discuz_version = TOOLS_DISCUZ_VERSION;

if(!TMAGIC_QUOTES_GPC) {
	$_GET = taddslashes($_GET);
	$_POST = taddslashes($_POST);
	$_COOKIE = taddslashes($_COOKIE);
}

if (isset($_GET['GLOBALS']) || isset($_POST['GLOBALS']) ||  isset($_COOKIE['GLOBALS']) || isset($_FILES['GLOBALS'])) {
	show_msg('您当前的访问请求当中含有非法字符，已经被系统拒绝');
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
	$_GET = array_merge($_GET, $_POST);
}

$actionarray = array('index', 'setadmin', 'closesite', 'closeplugin', 'repairdb', 'restoredb', 'updatecache', 'login', 'logout');
$_GET['action'] = htmlspecialchars($_GET['action']);
$action = in_array($_GET['action'], $actionarray) ? $_GET['action'] : 'index';

if(!is_login()) {
	login_page();
	exit;
}

$t = new T();
$t->init();
$config = $t->config;

if($action == 'index') {
//首页
	show_header();
	print<<<END
	<p>欢迎使用 Tools 之 Discuz! 急诊箱功能！我们致力于为您解决 Discuz! 站点的紧急故障，欢迎各位站长朋友们使用。</p>
	<tr><td>
	<h5>适用版本：</h5>
	<ul>
		<li>{$tools_discuz_version}</li>
	</ul>
	<h5>主要功能：</h5>
	<ul>
		<li>重置管理员账号：将把您指定的会员设置为管理员</li>
		<li>开启关闭站点：  此处可以进行站点“关闭/打开”的操作</li>
		<li>一键关闭插件：  一键关闭应用中心开启的所有插件</li>
		<li>修复数据库：    对所有数据表进行检查修复工作</li>
		<li>恢复数据库：    一次性导入论坛数据备份</li>
		<li>更新缓存：      一键更新论坛的数据缓存与模板缓存</li>
	</ul>
	<h5>问题反馈：</h5>
	<p>&nbsp;&nbsp;&nbsp;&nbsp;有关 Tools 工具箱的建议和问题，请反馈到 Discuz! 官方论坛安装使用区（<a href="http://www.discuz.net/forum-2-1.html" target="_blank">http://www.discuz.net/forum-2-1.html</a>），我们会对您的问题进行处理。提交问题时，请注明问题来源于 Discuz! 急诊箱。</p>
END;
	show_footer();

}elseif($action == 'setadmin') {
//找回管理员
	$t->connect_db();
	$founders = @explode(',',$t->config['admincp']['founder']);
	$foundernames = array();
	foreach($founders as $userid) {
		$sql = "SELECT username FROM ".$t->dbconfig['tablepre']."common_member WHERE `uid`='$userid'";
		$foundernames[] = mysql_result(mysql_query($sql, $t->db), 0);
	}
	$foundernames = implode($foundernames, ',');
	print_r($foundernames);
	$sql = "SELECT username FROM ".$t->dbconfig['tablepre']."common_member WHERE `adminid`='1'";
	$query = mysql_query($sql, $t->db) or dir(mysql_error());
	$adminnames = array();
	while($row = mysql_fetch_row($query)) {
		$adminnames[] = $row[0];
	}
	$adminnames = implode($adminnames, ',');
	
	if(!empty($_POST['setadminsubmit'])) {
		if($_GET['username'] == NULL) {
			show_msg('请输入用户名', 'tools.php?action='.$action, 2000);
		}
		
		if($_GET['loginfield'] == 'username') {
			$_GET['username'] = addslashes($_GET['username']);
			$sql = "SELECT uid FROM ".$t->dbconfig['tablepre']."common_member WHERE `username`='".$_GET['username']."'";
			$uid = mysql_result(mysql_query($sql, $t->db), 0);
			$username = $_GET['username'];
		} elseif($_GET['loginfield'] == 'uid') {
			$_GET['username'] = addslashes($_GET['username']);
			$uid = 	$_GET['username'];
			$sql = "SELECT username FROM ".$t->dbconfig['tablepre']."common_member WHERE `uid`='".$_GET['username']."'";
			$username = mysql_result(mysql_query($sql, $t->db), 0);
		}
		
		if($uid && $username) {
			$sql = "UPDATE ".$t->dbconfig['tablepre']."common_member SET `groupid`='1', `adminid`='1' WHERE `uid`='$uid'";
			@mysql_query($sql, $t->db);
			if(!in_array($uid,$founders)) {
				$sql = "REPLACE INTO ".$t->dbconfig['tablepre']. "common_admincp_member (`uid`, `cpgroupid`, `customperm`) VALUES ('$uid', '0', '')";
				@mysql_query($sql, $t->db);
			}
		} else {
			show_msg('没有这个用户', 'tools.php?action='.$action, 2000);
		}
		
		$t->connect_db('ucdb');
		if($_GET['password'] != NULL) {
			$sql = "SELECT salt FROM ".$t->ucdbconfig['tablepre']."members WHERE `uid`='$uid'";
			$salt = mysql_result(mysql_query($sql, $t->db), 0);
			$newpassword = md5(md5(trim($_GET['password'])).$salt);
			$sql = "UPDATE ".$t->ucdbconfig['tablepre']."members SET `password`='$newpassword' WHERE `uid`='$uid'";
			mysql_query($sql, $t->db);
		}
		if($_GET['issecques'] == 1) {
			$sql = "UPDATE ".$t->ucdbconfig['tablepre']."members SET `secques`='' WHERE `uid`='$uid'";
			mysql_query($sql, $t->db);
		}
		$t->close_db();
		show_msg('管理员找回成功！', 'tools.php?action='.$action, 2000);
		
	} else {
		show_header();
		echo "<p>现有创始人：$foundernames</p>";
		echo "<p>现有管理员：$adminnames</p>";
		print<<<END
		<form action="?action={$action}" method="post">
		<h5>{$info}</h5>
		<table id="setadmin">
			<tr><th width="30%"><input class="radio" type="radio" name="loginfield" value="username" checked class="radio">用户名<input class="radio" type="radio" name="loginfield" value="uid" class="radio">UID</th><td width="70%"><input class="textinput" type="text" name="username" size="25" maxlength="40"></td></tr>
			<tr><th width="30%">请输入密码</th><td width="70%"><input class="textinput" type="text" name="password" size="25"></td></tr>
			<tr><th width="30%">是否清除安全提问</th><td width="70%">
			<input class="radio" type="radio" name="issecques" value="1">是&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<input class="radio" type="radio" name="issecques" value="0" class="radio" checked>否</td></tr>
		</table>
			<input type="submit" name="setadminsubmit" value="提 &nbsp; 交">
		</form>
END;
		print<<<END
		<br/>
		恢复步骤: 
		重置管理员<br/>
		<ul>
		<li>选择用户名或者UID。</li>
		<li>输入用户名或者UID。</li>
		<li>如果需要重置密码，输入密码。</li>
		<li>如果需要清除安全提问，请在是否清除安全提问处选择是。</li>
		</ul>
		<br/>
		重置创始人<br/>
		<ul>
		<li>重置用户为创始人。</li>
		<li>修改config_global.php 中 \$_config[\'admincp\'][\'founder\'] = \'管理员的ID\'，多个以半角逗号分割。</li>
		</ul>
END;
		show_footer();
	}

}elseif($action == 'closesite') {
//一键开关站点
	$t->connect_db();
	$sql = "SELECT svalue FROM ".$t->dbconfig['tablepre']."common_setting WHERE skey='bbclosed'";
	$bbclosed = mysql_result(mysql_query($sql, $t->db), 0);

	if(empty($bbclosed)) {
		$closed = '';
		$opened = 'checked';
	} else {
		$closed = 'checked';
		$opened = '';
	}
	$sql = "SELECT svalue FROM ".$t->dbconfig['tablepre']."common_setting WHERE `skey`='closedreason'";
	$closedreason = mysql_result(mysql_query($sql, $t->db), 0);
	if(!empty($_GET['closesitesubmit'])) {
		if($_GET['close'] == 1) {
			$sql = "UPDATE ".$t->dbconfig['tablepre']."common_setting SET `svalue`='1' WHERE `skey`='bbclosed'";
			mysql_query($sql, $t->db);
			$sql = "UPDATE ".$t->dbconfig['tablepre']."common_setting SET `svalue`='tools.php closed' WHERE `skey`='closedreason'";
			mysql_query($sql, $t->db);
		} else {
			$sql = "UPDATE ".$t->dbconfig['tablepre']."common_setting SET `svalue`='0' WHERE `skey`='bbclosed'";
			mysql_query($sql, $t->db);
			$sql = "UPDATE ".$t->dbconfig['tablepre']."common_setting SET `svalue`='' WHERE `skey`='closedreason'";
			mysql_query($sql, $t->db);
		}
		show_msg('关闭/打开站点操作成功，正在更新缓存...', 'tools.php?action=updatecache',2000);
	} else {
		show_header();
		print<<<END
		<h4>关闭/打开站点</h4>
		此处可以进行站点“关闭/打开”的操作。
		<p>
		<form action="?action=closesite" method="post">
		站点当前状态
		<input class="radio" type="radio" name="close" value="0" {$opened} class="radio">打开
		<input class="radio" type="radio" name="close" value="1" {$closed} class="radio">关闭
		</p>
		<p>
		<input type="submit" name="closesitesubmit" value="提 &nbsp; 交">
		</p>
		</form>
END;
		show_footer();
	}

}elseif($action == 'closeplugin') {
//一键关闭插件
	include_once(TDISCUZ_ROOT.'source/class/class_core.php');
	include_once(TDISCUZ_ROOT.'source/function/function_core.php');

	$cachelist = array();
	$discuz = & discuz_core::instance();
	$discuz->cachelist = $cachelist;
	$discuz->init_cron = false;
	$discuz->init_setting = false;
	$discuz->init_user = false;
	$discuz->init_session = false;
	$discuz->init_misc = false;
	
	$discuz->init();
	require_once libfile('function/plugin');
	require_once libfile('function/cache');
	DB::query("UPDATE ".DB::table('common_plugin')." SET available='0'");
	updatecache(array('plugin', 'setting', 'styles'));
	cleartemplatecache();
	show_msg('成功关闭所有插件', 'tools.php?action=index',2000);

}elseif($action == 'repairdb') {
//修复数据库
	show_header();
	$t->connect_db();
	$typearray = array('index', 'repair', 'repairtables', 'allrepair', 'check', 'detail');
	$type = in_array($_GET['type'], $typearray) ? $_GET['type'] : 'index';
	
	if($type == 'index') {
	print<<<END
	<div class=\"bm\">
		<table id="menu">
		<tr>
			<!--<td><a href="?action=repairdb&type=allcheck">一键检查</a></td>--!>
			<td><a href="?action=repairdb&type=allrepair">一键修复</a></td>
			<td><a href="?action=repairdb&type=detail">进入详细页面检查或修复</a></td>
		</tr>
		</table>
		说明 & 提示: 
		<ul>
			<!--<li>一键检查: 对数据库中所有表进行 CHECK TABLE 操作，列出损坏的数据表。</li>--!>
			<li>一键修复: 先执行 CHECK TABLE 操作，然后按照检查的结果对有错误的数据表执行REPAIR TABLE 操作。</li>
			<li>进入详细页面检查或修复: 列出详细表，对单表进行检查或修复。</li>
			<li><span style="color:red">提示1：数据表比较大的情况下，mysql可能会花费比较长的时间进行检查和修复操作。</span></li>
			<li><span style="color:red">提示2：REPAIR TABLE 操作不能修复所有情况，如果修复不了数据表，请登录服务器使用myisamchk进行数据表修复。</span></li>
		</ul>
	</div>
END;
	} elseif ($type == 'allrepair' || $type == 'allcheck' || $type == 'detail' || $type == 'check' || $type == 'repair' || $type == 'repairtables') {
		$sql = "SHOW TABLE STATUS";
		$tablelist = mysql_query($sql, $t->db);
		while($list = mysql_fetch_array($tablelist, MYSQL_ASSOC)) {
			if($type == 'allcheck' || $type == 'allrepair') {
				if($list['Engine'] != 'MEMORY' && $list['Engine'] != 'HEAP') {
					$sql = 'CHECK TABLE '.$list['Name'];
					$query = mysql_query($sql, $t->db);
					$checkresult = mysql_fetch_array($query, MYSQL_ASSOC);
					
					if( $checkresult['Msg_text'] != 'OK') {
						$tablelists[$list['Name']]['statu'] = $checkresult['Msg_text'];
						$tablelists[$list['Name']]['size'] = round(($list['Data_length'] + $list['Index_length'])/1024,2);
					}
				}
			} else {
				$tablelists[$list['Name']]['size'] = round(($list['Data_length'] + $list['Index_length'])/1024,2);
			}
		}
		if($type == 'allrepair') {
			foreach($tablelists as $table => $value) {
				$sql = "REPAIR TABLE `".$table."`";
				$query = mysql_query($sql, $t->db);
				$repairresult = mysql_fetch_array($query, MYSQL_ASSOC);
				$resulttable[$table]['statu'] = $repairresult['Msg_text'];
				$resulttable[$table]['size'] = '未检查';
			}
			$tablelists = $resulttable;
		}

		if($type == 'check') {
			$_GET['table'] = addslashes($_GET['table']);
			$sql = 'CHECK TABLE '.$_GET['table'];
			$query = mysql_query($sql, $t->db);
			$checkresult = mysql_fetch_array($query, MYSQL_ASSOC);
			$tablelists[$_GET['table']]['statu'] = $checkresult['Msg_text'];
		}
		if($type == 'repair') {
			$_GET['table'] = addslashes($_GET['table']);
			$sql = "REPAIR TABLE `".$_GET['table']."`";
			$query = mysql_query($sql, $t->db);
			$repairresult = mysql_fetch_array($query, MYSQL_ASSOC);
			echo '<div style="background:red">';
			show_msg_body('修复表单 '.$_GET['table'].' 结果：'.$repairresult['Msg_text'], "tools.php?action=$action&type=detail", 3000);
			echo '</div>';
		}
		if($type == 'repairtables') {
			if($_POST['optimizesubmit']){
				$repairtables = addslashes($_POST['repairtables']);
				foreach ($repairtables as $value) {
					$sql = "REPAIR TABLE `".$value."`";
					$query = mysql_query($sql, $t->db);
					$repairresult = mysql_fetch_array($query, MYSQL_ASSOC);
					echo '<div style="background:red">';
					show_msg_body('修复表单 '.$value.' 结果：'.$repairresult['Msg_text'], '', 3000);
					echo '</div>';
				}
				echo '<div style="background:red">';
				show_msg_body('复选修复表单完成', "tools.php?action=$action&type=detail", 3000);
				echo '</div>';
			}
		}
			echo '
			<script type="text/javascript">
				var BROWSER = {};
				var USERAGENT = navigator.userAgent.toLowerCase();
				browserVersion({\'ie\':\'msie\',\'firefox\':\'\',\'chrome\':\'\',\'opera\':\'\',\'safari\':\'\',\'mozilla\':\'\',\'webkit\':\'\',\'maxthon\':\'\',\'qq\':\'qqbrowser\'});
				function browserVersion(types) {
					var other = 1;
					for(i in types) {
						var v = types[i] ? types[i] : i;
						if(USERAGENT.indexOf(v) != -1) {
							var re = new RegExp(v + \'(\\/|\\s)([\\d\\.]+)\', \'ig\');
							var matches = re.exec(USERAGENT);
							var ver = matches != null ? matches[2] : 0;
							other = ver !== 0 && v != \'mozilla\' ? 0 : other;
						} else {
							var ver = 0;
						}
						eval(\'BROWSER.\' + i + \'= ver\');
						}
					BROWSER.other = other;
				}
				function jumpurl(url,nw) {
					if(BROWSER.ie) url += (url.indexOf(\'?\') != -1 ?  \'&\' : \'?\') + \'referer=\' + escape(location.href);
					if(nw == 1) {
						window.open(url);	
					} else {
						location.href = url;
					}
					return false;
				}
				</script>';
				echo '
				<script type="text/javascript">
				function checkAll(type, form, value, checkall, changestyle) {
					var checkall = checkall ? checkall : \'chkall\';
					for(var i = 0; i < form.elements.length; i++) {
						var e = form.elements[i];
						if(type == \'option\' && e.type == \'radio\' && e.value == value && e.disabled != true) {
							e.checked = true;
						} else if(type == \'value\' && e.type == \'checkbox\' && e.getAttribute(\'chkvalue\') == value) {
							e.checked = form.elements[checkall].checked;
							if(changestyle) {
								multiupdate(e);
							}
						} else if(type == \'prefix\' && e.name && e.name != checkall && (!value || (value && e.name.match(value)))) {
							e.checked = form.elements[checkall].checked;
							if(changestyle) {
								if(e.parentNode && e.parentNode.tagName.toLowerCase() == \'li\') {
									e.parentNode.className = e.checked ? \'checked\' : \'\';
								}
								if(e.parentNode.parentNode && e.parentNode.parentNode.tagName.toLowerCase() == \'div\') {
									e.parentNode.parentNode.className = e.checked ? \'item checked\' : \'item\';
								}
							}
						}
					}
				}
			</script>';
			print<<<END
			<div class=\"bm\">
				<table class=\"tb\">
					<tbody>
					<form name="cpform" method="post" autocomplete="on" action="tools.php?action=repairdb&type=repairtables" id="cpform">
					<tr>
						<th></th>
						<th width="350px">表名</th>
						<th width="80px">大小</th>
						<th></th>
						<th width="80px"></th>
					</tr>
END;
			foreach($tablelists as $name => $value) {
				if($value['size'] < 1024) {
					echo '<tr><th><input class="checkbox" type="checkbox" name="repairtables[]" value="'.$name.'"></th><th>'.$name.'</th><td style="text-align:right;color:#339900"">'.$value['size'] .'KB</td><td>';
				} elseif(1024 < $value['size'] && $value['size']< 1048576 ) {
					echo '<tr><th><input class="checkbox" type="checkbox" name="repairtables[]" value="'.$name.'"></th><th>'.$name.'</th><td style="text-align:right;color:#3333FF">'.round($value['size']/1024,1) .'MB</td><td>';
				} elseif(1048576 < $value['size']){
					echo '<tr><th><input class="checkbox" type="checkbox" name="repairtables[]" value="'.$name.'"></th><th>'.$name.'</th><td style="text-align:right;color:#FF0000"">'.round($value['size']/1048576,1) .'GB</td><td>';
				}

				if(!isset($value['statu'])) {
					echo "<button type=\"button\" class=\"pn vm\" onclick=\"jumpurl('tools.php?action=repairdb&type=check&table=".$name."')\"><strong>检查</button>";
				} elseif($value['statu']!='OK') {
					echo '<span class=\"red\">'.$value['statu'].'</span>';
				} else {
					echo $value['statu'];
				}

				echo '</td><td>';
				if($value['statu']!='OK' && $value['statu']!='Not Support CHECK') {
					echo "<button type=\"button\" class=\"pn vm\" onclick=\"jumpurl('tools.php?action=repairdb&type=repair&table=".$name."')\"><strong>修复</button></strong>";
				}
				echo '</td></tr>';
				}
				
			echo "<tr><th><input name=\"chkall\" id=\"chkall\" class=\"checkbox\" onclick=\"checkAll('prefix', this.form)\" type=\"checkbox\"></th><th><input type=\"submit\" class=\"btn\" id=\"submit_optimizesubmit\" name=\"optimizesubmit\" title=\"复选修复\" value=\"复选修复\"></th><td></td><td></td><td></td></form>";
			echo '</tbody></table></div>';
			if( count($tablelists) == 0) {
				echo '<div style="background:#00cc66;">没有需要修复的表</div>';
			}
	} elseif ($type == 'allrepair') {
		show_msg("操作成功", "tools.php?action=$action");
	}
	show_footer();

}elseif($action == 'restoredb') {
//恢复数据
	$backfiledir = TDISCUZ_ROOT.'data/';
	$detailarray = array();
	$t->connect_db();

	if(!mysql_select_db($t->dbconfig['name'], $t->db)) {
		$dbname = $t->dbconfig['name'];
		mysql_query("CREATE DATABASE $dbname");
	}

	if(!$_GET['importbak'] && !$_GET['nextfile']) {
		//检测是否关闭站点
		$sql = "SELECT svalue FROM ".$t->dbconfig['tablepre']."common_setting WHERE skey='bbclosed'";
		$closed = mysql_result(mysql_query($sql, $t->db), 0);
		if($closed != '1') {
			show_msg('恢复数据前，请先关闭站点!', 'tools.php?action=closesite', 3000);
		}
		$exportlog = array();
		$dir = dir($backfiledir);
		while($entry = $dir->read()) {
			$entry = $backfiledir."/$entry";
			$num = 0;
			if(is_dir($entry) && preg_match("/backup\_/i", $entry)) {
				$bakdir = dir($entry);
				while($bakentry = $bakdir->read()) {
					$bakentry = "$entry/$bakentry";
					if(is_file($bakentry) && preg_match("/(.*)\-(\d)\.sql/i", $bakentry,$match)) {
						if($_GET['detail']) {
							$detailarray[] = $match['1'];
						}
						$num++;	
					}
					if(is_file($bakentry) && preg_match("/\-1\.sql/i", $bakentry)) {
						$fp = fopen($bakentry, 'rb');
						$bakidentify = explode(',', base64_decode(preg_replace("/^# Identify:\s*(\w+).*/s", "\\1", fgets($fp, 256))));
						fclose ($fp);
						
						if(preg_match("/\-1\.sql/i", $bakentry) || $bakidentify[3] == 'shell') {
							$identify['bakentry'] = $bakentry;
						}
					}
				}
				$detailarray = array_reverse(array_unique($detailarray));
				
				if($num != 0) {
					$exportlog[$entry] = array(	
								'dateline' => date('Y-m-d H:i:s',$bakidentify[0]),
								'version' => $bakidentify[1],
								'type' => $bakidentify[2],
								'method' => $bakidentify[3],
								'volume' => $num,
								'bakentry' => $identify['bakentry'],
								'filename' => str_replace($backfiledir.'/','',$entry));
				}
			}
		}
	}else{
		$bakfile = $_GET['nextfile'] ? $_GET['nextfile'] : $_GET['importbak'];
		if(!file_exists($bakfile)) {
			if($_GET['nextfile']) {
				$tpl = dir(TDISCUZ_ROOT.'data/template');
				while($entry = $tpl->read()) {
					if(preg_match("/\.tpl\.php$/", $entry)) {
						@unlink(TDISCUZ_ROOT.'data/template/'.$entry);
					}
				}
				$tpl->close();
				show_msg('恢复备份成功，请查看论坛，如果数据不同步，请检查数据库前缀。正在更新缓存...', 'tools.php?action=updatecache',2000);
			}
			show_msg('备份文件不存在。');
		}
		if(!is_readable($bakfile)) {
			show_msg('备份文件不可读取。');
		} else {
			@$fp = fopen($bakfile, "r");
			@flock($fp, 3);
			$sqldump = @fread($fp, filesize($bakfile));
			@fclose($fp);
		}
		@$bakidentify = explode(',', base64_decode(preg_replace("/^# Identify:\s*(\w+).*/s", "\\1", substr($sqldump, 0, 256))));
		if(!defined('IN_DISCUZ')) {
			define('IN_DISCUZ', TRUE);
		}
		include_once(TDISCUZ_ROOT.'source/discuz_version.php');
		if($bakidentify[1] != DISCUZ_VERSION) {
			show_msg('备份文件版本错误，不能恢复。');		
		}
		$vol = $bakidentify[4];
		
		$nextfile = taddslashes(str_replace("-$vol.sql","-".($vol+1).'.sql',$bakfile));
		$result = $t->db_runquery($sqldump);
		if($result) {
			show_msg('正在恢复分卷：'.$vol,"tools.php?action=$action&nextfile=$nextfile", 2000);	
		}
	}
	$t->close_db();
	show_header();
	print<<<END
	<div class="bm">
	<form action="tools.php?action={$action}" method="post">
	<table class="tdat"><tbody>
	<tr class=\'alt h\'><th>备份项目</th><th>版本</th><th>时间</th><th>类型</th><th>文件总数</th><th>导入</th></tr>
END;
	foreach( $exportlog  as $value) {
		echo '<tr><td>'.$value['filename'].'</td><td>'.$value['version'].'</td><td>'.$value['dateline'].'</td><td>'.$value['method'].'</td><td>'.$value['volume'].'</td><td><a href="tools.php?action='.$action.'&detail='.$value['filename'].'"><font color="blue">打开</font></a></td></tr>';
	}
	if (count($detailarray)>0) {
		foreach($detailarray as $value) {
			echo '<tr><td colspan="5">'.$value.'</td><td><a href="tools.php?action='.$action.'&importbak='.$value.'-1.sql"><font color="blue">导入</font></a></td></tr>';
		}
	}
	echo '</tbody></table></form></div>';
	show_footer();

}elseif($action == 'updatecache') {
//更新缓存
	include_once(TDISCUZ_ROOT.'source/class/class_core.php');
	include_once(TDISCUZ_ROOT.'source/function/function_core.php');

	$cachelist = array();
	$discuz = & discuz_core::instance();
	$discuz->cachelist = $cachelist;
	$discuz->init_cron = false;
	$discuz->init_setting = false;
	$discuz->init_user = false;
	$discuz->init_session = false;
	$discuz->init_misc = false;
	
	$discuz->init();

	require_once libfile('function/cache');
	updatecache();
	include_once libfile('function/block');
	blockclass_cache();
	//note 清除群组缓存
	require_once libfile('function/group');
	$groupindex['randgroupdata'] = $randgroupdata = grouplist('lastupdate', array('ff.membernum', 'ff.icon'), 80);
	$groupindex['topgrouplist'] = $topgrouplist = grouplist('activity', array('f.commoncredits', 'ff.membernum', 'ff.icon'), 10);
	$groupindex['updateline'] = TIMESTAMP;
	$groupdata = DB::fetch_first("SELECT SUM(todayposts) AS todayposts, COUNT(fid) AS groupnum FROM ".DB::table('forum_forum')." WHERE status='3' AND type='sub'");
	$groupindex['todayposts'] = $groupdata['todayposts'];
	$groupindex['groupnum'] = $groupdata['groupnum'];
	save_syscache('groupindex', $groupindex);
	DB::query("TRUNCATE ".DB::table('forum_groupfield'));

	$tpl = dir(DISCUZ_ROOT.'./data/template');
	while($entry = $tpl->read()) {
		if(preg_match("/\.tpl\.php$/", $entry)) {
			@unlink(DISCUZ_ROOT.'./data/template/'.$entry);
		}
	}
	$tpl->close();
	show_msg('更新数据缓存模板缓存成功！', 'tools.php?action=index', 2000);

}elseif($action == 'logout') {
//登出
	tsetcookie('toolsauth', '', -1);
	@header('Location: tools.php');

}else{
	
}
 //大的分支 结束

/**********************************************************************************
 *
 *	tools.php 通用函数部分
 *
 *
 **********************************************************************************/
 
/*
	checkpassword 函数
	判断密码强度，大小写字母加数字，长度大于6位。
	return flase 或者 errormsg
 */
function checkpassword($password){
	$errormsg = array(	
						0 => '您设置的密码只能使用数字和大小写字母组成，请修改！',
						1 => 'tools.php密码少于6位，请重新修改tools.php中密码。',
						2 => '密码中必须含有数字，请重新修改tools.php中密码。',	
						3 => '密码中必须含有字母，请重新修改tools.php中密码。',
						4 => '密码中必须含有大写字母，请重新修改tools.php中密码。',
						5 => '密码中必须含有小写字母，请重新修改tools.php中密码。',
						6 => '没有设置密码，请使用FTP或者直接编辑论坛根目录下的 tools.php 文件，并根据文件中的说明设置密码',
						7 => '不能使用密码示范中的的 DiscuzX3 为密码',
					);
	if(empty($password))
		return $errormsg[6];
	if(!ctype_alnum($password))
		return $errormsg[0];
	if(strlen($password) < 6)
		return $errormsg[1];
	if($password === 'DiscuzX3'){
		return $errormsg[7];
	}
	$pw_array = str_split($password);

	$is_upper = false;
	$is_lower = false;
	$is_char = false;
	$is_digit = false;
	foreach( $pw_array as $a) {
		if(ctype_digit($a)) {
			$is_digit = true;
		} else {
			$is_char = true;
			if(ctype_lower($a))
				$is_lower = true;
			if(ctype_upper($a))
				$is_upper = true;
		}
	}

	if(!$is_digit)
		return $errormsg[2];

	if(!$is_char)
		return $errormsg[3];

	if(!$is_upper)
		return $errormsg[4];

	if(!$is_lower)
		return $errormsg[5];
	return false;
}

//去掉slassh
function tstripslashes($string) {
	if(empty($string)) return $string;
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = tstripslashes($val);
		}
	} else {
		$string = stripslashes($string);
	}
	return $string;
}

function thash() {
	return substr(md5(substr(time(), 0, -4).TDISCUZ_ROOT), 16);
}

function taddslashes($string, $force = 1) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = taddslashes($val, $force);
		}
	} else {
		$string = addslashes($string);
	}
	return $string;
}

//显示
function show_msg($message, $url_forward='', $time = 1, $noexit = 0) {
	show_header();
	show_msg_body($message, $url_forward, $time, $noexit);
	show_footer();
	!$noexit && exit();
}

function show_msg_body($message, $url_forward='', $time = 1, $noexit = 0) {
	if($url_forward) {
		$url_forward = $_GET['from'] ? $url_forward.'&from='.rawurlencode($_GET['from']) : $url_forward;
		$message = "<a href=\"$url_forward\">$message (跳转中...)</a><script>setTimeout(\"window.location.href ='$url_forward';\", $time);</script>";
	}else{
		$message = "<a href=\"$url_forward\">$message </a>";
	}
	print<<<END
	<table>
	<tr><td>$message</td></tr>
	</table>
END;
}

function login_page() {
	show_header();
	$formhash = thash();
	print<<<END
		<span>急诊箱登录</span>
		<form action="tools.php?action=login" method="post">
			<table class="specialtable">
			<tr>
				<td width="20%"><input class="textinput" type="password" name="toolpassword"></input></td>
				<td><input class="specialsubmit" type="submit" value="登 录"></input></td>
			</tr>
			</table>
			<input type="hidden" name="action" value="login">
			<input type="hidden" name="formhash" value="{$formhash}">
		</form>
END;
	show_footer();
}

function show_header() {
	$_GET['action'] = htmlspecialchars($_GET['action']);
	$nowarr = array($_GET['action'] => ' class="current"');
	print<<<END
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=gbk" />
	<title>Discuz! X3 急诊箱</title>
	<style type="text/css">
	* {font-size:12px; font-family: Verdana, Arial, Helvetica, sans-serif; line-height: 1.5em; word-break: break-all; }
	body { text-align:center; margin: 0; padding: 0; background: #F5FBFF; }
	.bodydiv { margin: 40px auto 0; width:820px; text-align:left; border: solid #86B9D6; border-width: 5px 1px 1px; background: #FFF; }
	h1 { font-size: 18px; margin: 1px 0 0; line-height: 50px; height: 50px; background: #E8F7FC; color: #5086A5; padding-left: 10px; }
	#menu {width: 100%; margin: 10px auto; text-align: center; }
	#menu td { height: 30px; line-height: 30px; color: #999; border-bottom: 3px solid #EEE; }
	.current { font-weight: bold; color: #090 !important; border-bottom-color: #F90 !important; }
	input { border: 1px solid #B2C9D3; padding: 5px; background: #F5FCFF; }
	#footer { font-size: 10px; line-height: 40px; background: #E8F7FC; text-align: center; height: 38px; overflow: hidden; color: #5086A5; margin-top: 20px; }
	table {width:100%;font-size:12px;margin-top:5px;}
		table.specialtable,table.specialtable td {border:0;}
		td,th {padding:5px;text-align:left;}
		caption {font-weight:bold;padding:8px 0;color:#3544FF;text-align:left;}
		th {background:#E8F7FC;font-weight:600;}
		td.specialtd {text-align:left;}
	#setadmin {margin: 0px;}
	.textarea {height: 80px;width: 400px;padding: 3px;margin: 5px;}
	</style>
	</head>
	<body>
	<div class="bodydiv">
	<h1>Discuz! X3 急诊箱</h1><br/>
	<div style="width:90%;margin:0 auto;">
	<table id="menu">
	<tr>
	<td{$nowarr[index]}><a href="?action=index">首页</a></td>
	<td{$nowarr[setadmin]}><a href="?action=setadmin">重置管理员帐号</a></td>
	<td{$nowarr[closesite]}><a href="?action=closesite">开启关闭站点</a></td>
	<td{$nowarr[closeplugin]}><a href="?action=closeplugin">一键关闭插件</a></td>
	<td{$nowarr[repairdb]}><a href="?action=repairdb">修复数据库</a></td>
	<td{$nowarr[restoredb]}><a href="?action=restoredb">恢复数据库</a></td>
	<td{$nowarr[updatecache]}><a href="?action=updatecache">更新缓存</a></td>
	<td{$nowarr[logout]}><a href="?action=logout">退出</a></td>
	</tr>
	</table>
	<br>
END;
}

//页面顶部
function show_footer() {
	global $tools_versions;
	print<<<END
	</div>
	<div id="footer">Powered by {$tools_versions} &copy; Comsenz Inc. 2001-2013 <a href="http://www.comsenz.com" target="_blank">http://www.comsenz.com</a></div>
	</div>
	<br>
	</body>
	</html>
END;
}

//登录判断函数
function is_login() {
	$error = false;
	$errormsg = array();
	global $tpassword;

	if($errormsg = checkpassword($tpassword)) {
		show_msg($errormsg);
	}

	if(isset($_COOKIE['toolsauth'])) {
		if($_COOKIE['toolsauth'] === md5($tpassword.thash())) {
			return TRUE;
		}
	}
	
	$lockfile = TDISCUZ_ROOT.'data/tools.lock';
	if(@file_exists($lockfile)) {
		$errormsg = "急救箱已经锁定，请您先登录服务器ftp，手工删除 ./data/tools.lock 文件，再次重新使用急救箱。";
		show_msg($errormsg);
	}

	if ( $_GET['action'] === 'login') {
		$formhash = $_GET['formhash'];
		if($formhash !== thash()) {
			show_msg('您的请求来路不正或者输入密码超时，请刷新页面后重新输入正确密码！');
		}
		$toolsmd5 = md5($tpassword.thash());
		if(md5($_GET['toolpassword'].thash()) == $toolsmd5) {
			tsetcookie('toolsauth', $toolsmd5, time()+'3600', '', false, '','');
			$lockfile = TDISCUZ_ROOT.'data/tools.lock';
			if(@$fp = fopen($lockfile, 'w')) {
				fwrite($fp, ' ');
				fclose($fp);
			}
			show_msg('登陆成功！', 'tools.php?action=index', 2000);
		} else {
			show_msg( '您输入的密码不正确，请重新输入正确密码！', 'tools.php', 2000);
		}
	} else {
		return FALSE;
	}
}

//登录成功设置cookie
function tsetcookie($var, $value = '', $life = 0, $prefix = '', $httponly = false, $cookiepath, $cookiedomain) {
	$var = (empty($prefix) ? '' : $prefix).$var;
	$_COOKIE[$var] = $value;
	
	if($value == '' || $life < 0) {
		$value = '';
		$life = -1;
	}
	$path = $httponly && PHP_VERSION < '5.2.0' ? $cookiepath.'; HttpOnly' : $cookiepath;
	$secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;

	if(PHP_VERSION < '5.2.0') {
		$r = setcookie($var, $value, $life);
	} else {
		$r = setcookie($var, $value, $life);
	}
}

/* 
	T 类 
	tools.php 主要类
*/
class T{
	var $dbconfig = null;
	var $ucdbconfig = null;
	var $db = null;
	var $ucdb = null;
	// 是否已经初始化
	var $initated = false;

	public function init() {
		if(!$this->initated) {
			$this->_init_config();
			$this->_init_db();
		}
		$this->initated = true;
	}

	public function db_runquery($sql) {
		$tablepre = $this->dbconfig['tablepre'];
		$dbcharset = $this->dbconfig['charset'];

		if(!isset($sql) || empty($sql)) return;

		$sql = str_replace("\r", "\n", str_replace(array(' {tablepre}', ' cdb_', ' `cdb_', ' pre_', ' `pre_'), array(' '.$tablepre, ' '.$tablepre, ' `'.$tablepre, ' '.$tablepre, ' `'.$tablepre), $sql));

		$ret = array();
		$num = 0;
		foreach(explode(";\n", trim($sql)) as $query) {
			$ret[$num] = '';
			$queries = explode("\n", trim($query));
			foreach($queries as $query) {
				$ret[$num] .= (isset($query[0]) && $query[0] == '#') || (isset($query[1]) && isset($query[1]) && $query[0].$query[1] == '--') ? '' : $query;
			}
			$num++;
		}
		unset($sql);
		$this->connect_db();
		foreach($ret as $query) {
			$query = trim($query);
			if($query) {
				if(substr($query, 0, 12) == 'CREATE TABLE') {
					$name = preg_replace("/CREATE TABLE ([a-z0-9_]+) .*/is", "\\1", $query);
					mysql_query($this->db_createtable($query, $dbcharset), $this->db);
				} else {
					mysql_query($query, $this->db);
				}
			}
		}
		return 1;
	}

	public function db_createtable($sql, $dbcharset) {
		$type = strtoupper(preg_replace("/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU", "\\2", $sql));
		$type = in_array($type, array('MYISAM', 'HEAP')) ? $type : 'MYISAM';
		return preg_replace("/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU", "\\1", $sql).(mysql_get_server_info() > '4.1' ? " ENGINE=$type DEFAULT CHARSET=$dbcharset" : " TYPE=$type");
	}
	
	public function connect_db($type = 'db') {
		if($type == 'db') {
			$dbhost = $this->dbconfig['host'];
			$dbuser = $this->dbconfig['user'];
			$dbpw = $this->dbconfig['pw'];
			$dbname = $this->dbconfig['name'];
			$dbcharset = $this->dbconfig['charset'];
		} else {
			$dbhost = $this->ucdbconfig['host'];
			$dbuser = $this->ucdbconfig['user'];
			$dbpw = $this->ucdbconfig['pw'];
			$dbname = $this->ucdbconfig['name'];
			$dbcharset = $this->ucdbconfig['charset'];
		}	
		if(!$this->db = mysql_connect($dbhost, $dbuser, $dbpw, 1))
			show_msg('Discuz! X数据库连接出错，请检查config_global.php中数据库相关信息是否正确，与数据库服务器网络连接是否正常');
		$dbversion = mysql_get_server_info($this->db);
		if($dbversion > '4.1') {
			if($dbcharset) {
				mysql_query("SET character_set_connection=".$dbcharset.", character_set_results=".$dbcharset.", character_set_client=binary", $this->db);
			}
			if($dbversion > '5.0.1') {
				mysql_query("SET sql_mode=''", $this->db);
			}
		}
		@mysql_select_db($dbname, $this->db);
	}

	public function close_db() {
		mysql_close($this->db);
	}

	private function _init_config() {
		$error = false;
		$_config = array();
		
		global $tpassword;
		if($errormsg = checkpassword($tpassword)) {
			$error = true;
		}

		@include TDISCUZ_ROOT.'config/config_global.php';
		if(empty($_config)) {
			$error = true;
			$errormsg = '没有找到config文件，请检查 /config/config_global.php 是否存在或有读权限！';
		}
		
		$uc_config_file = TDISCUZ_ROOT.'config/config_ucenter.php';
		if(!@file_exists($uc_config_file)) {
			$error = true;
			$errormsg = '没有找到uc config文件，请检查 /config/config_ucenter.php 是否存在或有读权限！';
		}
		@include $uc_config_file;
		
		if($error) {
			show_msg($errormsg);
		}
		
		$this->config = & $_config;
		$this->config['dbcharset'] = $_config['db']['1']['dbcharset'];
		$this->config['charset'] = $_config['output']['charset'];
	}

	private function _init_db() {
		$this->dbconfig['host'] = $this->config['db']['1']['dbhost'];
		$this->dbconfig['user'] = $this->config['db']['1']['dbuser'];
		$this->dbconfig['pw'] = $this->config['db']['1']['dbpw'];
		$this->dbconfig['name'] = $this->config['db']['1']['dbname'];
		$this->dbconfig['charset'] = $this->config['db']['1']['dbcharset'];
		$this->dbconfig['tablepre'] = $this->config['db']['1']['tablepre'];

		$this->ucdbconfig['host'] = UC_DBHOST;
		$this->ucdbconfig['user'] = UC_DBUSER;
		$this->ucdbconfig['pw'] = UC_DBPW;
		$this->ucdbconfig['name'] = UC_DBNAME;
		$this->ucdbconfig['charset'] = UC_DBCHARSET;
		$this->ucdbconfig['tablepre'] = UC_DBTABLEPRE;
		
		$this->connect_db();
		$sql = "SHOW FULL PROCESSLIST";
		$query = mysql_query($sql, $this->db);
		$waiting = false;
		$waiting_msg = '';
		while($l = mysql_fetch_array($query, MYSQL_ASSOC)) {
			if($l['State'] == 'Checking table') {
				$this->close_db();
				$waiting = true;
				$waiting_msg = '正在检查表，请稍后...';
			} elseif($l['State'] == 'Repair by sorting') {
				$this->close_db();
				$waiting = true;
				$waiting_msg = '正在修复表，请稍后...';
			}
		}
		if($waiting) {
			show_msg($waiting_msg, 'tools.php?action=repairdb', 3000);
		}
	}
}

//T class 结束
/**
* End of the tools.php
*/
