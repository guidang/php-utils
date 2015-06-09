<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: censor_scanbbs.inc.php 79 2012-04-16 10:06:12Z wangbin $
 */

(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) && exit('Access Denied');

if(submitcheck('bbsscansubmit',1)) {
	//一次扫描跳转数
    $rpp = '5';
    $convertedrows = isset($_GET['convertedrows']) ? $_GET['convertedrows'] : 0;
	//开始结束数
    $start = isset($_GET['start']) && $_GET['start'] > 0 ? $_GET['start'] : 0;
    $end = $start + $rpp - 1;
    $converted = 0;
    $scaned = isset($_GET['scaned']) && $_GET['scaned'] > 0 ? $_GET['scaned'] : 0;
    
	//开始结束扫描词
    $wordstart = isset($_GET['wordstart']) && $_GET['wordstart'] > 0 ? $_GET['wordstart'] : 0;
    $wordend =  $scaned ? ($scaned + $rpp -1) : ($wordstart + $rpp - 1);
    
	//最大帖子ID 帖子分表
    $maxid = isset($_GET['maxid']) ? $_GET['maxid'] : 0;
    if($posttablemaxid == 0) {
    	$posttablemaxid = DB::result_first("SELECT MAX(posttableid) FROM ".DB::table('forum_thread'));
    }
    $posttableid = isset($_GET['posttableid']) ? $_GET['posttableid'] : $posttablemaxid;
    if($posttableid > 0){
    	$posttable = "forum_post_".$posttableid;	
    } else {
    	$posttable = "forum_post";
    }
    $wordmaxid = isset($_GET['wordmaxid']) ? $_GET['wordmaxid'] : 0;
    
    $threads_mod = isset($_GET['threads_mod']) ? $_GET['threads_mod'] : 0;
    $threads_banned = isset($_GET['threads_banned']) ? $_GET['threads_banned'] : 0;
    $posts_mod = isset($_GET['posts_mod']) ? $_GET['posts_mod'] : 0;
    
	//读出扫描记录
    $log = toolsgetsetting('bbsltime');
    $logs = explode('|',$log);
    
    $array_find = $array_replace = $array_findmod = $array_findbanned = array();
    if($wordmaxid == 0) {
    	$result = DB::fetch_first("SELECT MIN(id) AS wordminid, MAX(id) AS wordmaxid FROM ".DB::table('common_word'));
    	$wordstart = $result['wordminid'] ? $result['wordminid'] - 1 : 0;
    	$wordmaxid = $result['wordmaxid'];
    }
    
    
    $wordextsql = "where id >= $wordstart AND id <= $wordend";
	//获得现有规则{BANNED}放回收站 {MOD}放进审核列表
    $query = DB::query("SELECT find,replacement from ".DB::table('common_word')." $wordextsql");
    while($row = DB::fetch($query)) {
    	$find = preg_quote($row['find'], '/');
    	$replacement = $row['replacement'];
    	if($replacement == '{BANNED}') {
    		$array_findbanned[] = $find;
    	} elseif($replacement == '{MOD}') {
    		$array_findmod[] = $find;
    	} else {
    		$array_find[] = $find;
    		$array_replace[] = $replacement;
    	}
    }
    
    $array_find = topattern_array($array_find);
    $array_findmod = topattern_array($array_findmod);
    $array_findbanned = topattern_array($array_findbanned);	
    
	//是否是增量扫描
    $scantype = $_GET['scantype'];
    if($scantype == 'addscan') {
    	$logs[0] = $logs[0] ? $logs[0] : 0;
    	$lasttime = $logs[0];
    	if($lasttime) {
    		$sqlplus = "AND dateline > $lasttime";
    	}
    }
    
    //最小 最大帖子ID
    if($maxid == 0) {
    	$result = DB::fetch_first("SELECT MIN(pid) AS minid, MAX(pid) AS maxid FROM ".DB::table($posttable));
    	$start = $result['minid'] ? $result['minid'] - 1 : 0;
    	$maxid = $result['maxid'];
    }
    
    $sql = "SELECT pid, tid, first, subject, message from ".DB::table($posttable)." where pid >= $start and pid <= $end AND invisible = 0 $sqlplus";
    if(DB::result_first("SELECT count(pid) from ".DB::table($posttable)." where pid >= $start and pid <= $end AND invisible = 0 $sqlplus") == 0 && $scantype == 'addscan' && ($posttableid < 1)) {
    	cpmsg($toolslang[censor_noneedtoscan], "action=plugins&cp=scan_bbsinfo&pmod=safe&operation=$operation&do=$do&identifier=$identifier&a=scanbbs", 'succeed');
    } elseif(DB::result_first("SELECT count(pid) from ".DB::table($posttable)." where pid >= $start and pid <= $end AND invisible = 0 $sqlplus") == 0 && $scantype == 'addscan') {
    	$posttableid2 = $posttableid-1;
    	cpmsg($toolslang[censor_jumpinto], "action=plugins&cp=scan_bbsinfo&pmod=safe&operation=$operation&do=$do&identifier=$identifier&a=scanbbs&posttableid=$posttableid2&scantype=addscan&bbsscansubmit=yes", 'loading',array('id' => $posttableid));
    }
    
    $query = DB::query($sql);
    
    while($row =  DB::fetch($query)) {
    	$pid = $row['pid'];
    	$tid = $row['tid'];
    	$subject = $row['subject'];
    	$message = $row['message'];
    	$first = $row['first'];
    	$displayorder = 0;//  -2 MOD -1 Banned
    	if(count($array_findmod) > 0) {
    		foreach($array_findmod as $value) {
    			if(preg_match($value,$subject.$message)) {
    				$displayorder = '-2';
    				break;
    			}
    		}
    	}
    	if(count($array_findbanned) > 0) {
    		foreach($array_findbanned as $value) {
    			if(preg_match($value,$subject.$message)) {
    				$displayorder = '-1';
    				break;
    			}
    		}
    	}
    	
    	if($displayorder < 0) {
    		if($displayorder == '-2' && $first == 0) {
    			if(DB::affected_rows(DB::query("UPDATE ".DB::table($posttable)." SET invisible = '$displayorder' WHERE pid = $pid AND invisible >= 0")) > 0) {
    				$xver >= 2 && updatemoderate('pid',$pid);
    				$posts_mod ++;
    			}
    		} else {
    			if(DB::affected_rows(DB::query("UPDATE ".DB::table('forum_thread')." SET displayorder = '$displayorder' WHERE tid = $tid and displayorder >= 0")) > 0) {
    				if($displayorder == '-2'){
    					$threads_mod ++;
    					$xver >= 2 && updatemoderate('tid',$tid);	
    				}
    				$displayorder == '-1' && $threads_banned ++;
    			}
    		}
    	}
    	$subject = preg_replace($array_find,$array_replace,addslashes($subject));
    	$message = preg_replace($array_find,$array_replace,addslashes($message));
    	if($subject != addslashes($row['subject']) || $message != addslashes($row['message'])) {
    		if(DB::query("UPDATE ".DB::table($posttable)." SET subject = '$subject', message = '$message' WHERE pid = $pid")) {
    			$convertedrows ++;
    		}
    	}
    	$converted = 1;
    
    }

    $sql2 = "SELECT tid,subject from ".DB::table('forum_thread')." where tid >= $start and tid <= $end AND displayorder = 0 $sqlplus";
    $query2 = DB::query($sql2);
    while($row2 = DB::fetch($query2)) {
    	$tid = $row2['tid'];
    	$subject = $row2['subject'];
    	$subject = preg_replace($array_find,$array_replace,addslashes($subject));
    	if($subject != addslashes($row2['subject'])) {
    		DB::query("UPDATE ".DB::table('forum_thread')." SET subject = '$subject' WHERE tid = $tid");
    	}
    	$converted = 1;
    }
    $discuz_user = $_G['uid'];
	$mod = $posts_mod + $threads_mod;
	$counter = $convertedrows + $mod + $threads_banned;
    toolssetsetting('bbsltime',"$_G[timestamp]|$discuz_user|$counter|$convertedrows|$mod|$threads_banned");
    if($converted  || $end < $maxid) {
    	$nextlink = "action=plugins&cp=scan_bbsinfo&pmod=safe&operation=$operation&do=$do&identifier=$identifier&start=$end&maxid=$maxid&threads_mod=$threads_mod&threads_banned=$threads_banned&posts_mod=$posts_mod&convertedrows=$convertedrows&wordstart=$wordstart&wordmaxid=$wordmaxid&posttableid=$posttableid&bbsscansubmit=yes";
    	cpmsg($toolslang[censor_scanstart], $nextlink, 'loading', array('start' => $start,'end' => $end,'wordstart' => $wordstart,'wordend' => $wordend,'posttableid' => $posttableid));
    } elseif($wordend < $wordmaxid) {
    	$nextlink = "action=plugins&cp=scan_bbsinfo&pmod=safe&operation=$operation&do=$do&identifier=$identifier&start=0&maxid=$maxid&threads_mod=$threads_mod&threads_banned=$threads_banned&posts_mod=$posts_mod&convertedrows=$convertedrows&wordstart=$wordend&wordmaxid=$wordmaxid&posttableid=$posttableid&bbsscansubmit=yes";
    	cpmsg($toolslang[censor_scanstart], $nextlink, 'loading',array('start' => $start,'end' => $end,'wordstart' => $wordstart,'wordend' => $wordend,'posttableid' => $posttableid));
    } elseif(($posttableid > 0) &&($end >= $maxid || $wordend >= $wordmaxid)) {
    	$posttableid2 = $posttableid - 1;
    	$nextlink = "action=plugins&cp=scan_bbsinfo&pmod=safe&operation=$operation&do=$do&identifier=$identifier&start=0&threads_mod=$threads_mod&threads_banned=$threads_banned&posts_mod=$posts_mod&convertedrows=$convertedrows&posttableid=$posttableid2&bbsscansubmit=yes";
    	cpmsg($toolslang[censor_jumpposttable], $nextlink, 'loading',array('id' => $posttableid));
    } elseif($end >= $maxid || $wordend >= $wordmaxid) {
    	cpmsg($toolslang[censor_scanresult], "action=plugins&cp=scan_bbsinfo&pmod=safe&operation=$operation&do=$do&identifier=$identifier", 'succeed',array('count' => $counter));
    }
}
showformheader("plugins&cp=scan_bbsinfo&operation=$operation&do=$do&identifier=$identifier&pmod=safe");
showtableheaders($toolslang['censor_bbsinfo'],'censor');
$log = toolsgetsetting('bbsltime');
$logs = explode('|',$log);
$bbsltime = $logs[0];
$totalthreadcount = DB::result_first("SELECT count(tid) FROM ".DB::table('forum_thread'));
$baththreadcount = DB::result_first("SELECT count(tid) FROM ".DB::table('forum_thread')." WHERE dateline >= '$bbsltime'");
$posttablemaxid = DB::result_first("SELECT MAX(posttableid) FROM ".DB::table('forum_thread'));
$id = 0;
$postcount = 0;
$totalcount= 0;
while($id <= $posttablemaxid){
	if($id == 0){
		$totalpostcount = DB::result_first("SELECT count(pid) FROM ".DB::table('forum_post'));
		$bathpostcount = DB::result_first("SELECT count(pid) FROM ".DB::table('forum_post')." WHERE dateline >= '$bbsltime'");	
	} else {
		$totalpostcount = DB::result_first("SELECT count(pid) FROM ".DB::table('forum_post_'.$id));
		$bathpostcount = DB::result_first("SELECT count(pid) FROM ".DB::table('forum_post_'.$id)." WHERE dateline >= '$bbsltime'");	
	}
	$postcount = $postcount + $bathpostcount;
	$totalcount = $totalcount + $totalpostcount;
	$id ++;
}

showtablerow('', array('class="td21"'), array($toolslang['censor_threadcount'],$totalthreadcount));
showtablerow('', array('class="td21"'), array($toolslang['censor_newthreadcount'],$baththreadcount));	
showtablerow('', array('class="td21"'), array($toolslang['censor_postcount'],$totalcount));
showtablerow('', array('class="td21"'), array($toolslang['censor_newpostcount'],$postcount));
showtablefooter();

showtableheader($toolslang['censor_scan']);
showformheader('plugins&cp=scan_bbsinfo&operation=$operation&do=$do&identifier=$identifier&pmod=safe');
showsetting($toolslang['censor_scantype'],array('scantype',array(array('addscan',$toolslang['censor_addscan']),array('allscan',$toolslang['censor_allscan']))),'addscan','mradio','',0,$toolslang['censor_scantips']);
showsubmit('bbsscansubmit', $toolslang['censor_beginscan']);
showformfooter();
showtablefooter();

showtablefooter();
?>