<?php

set_time_limit(0);
header('Content-type: charset=utf-8'); 

$file = $_REQUEST['name'] ?: 0;
$url = $_REQUEST['url'] ?: '';
$time = (int)$_REQUEST['time'] ?: 5;

$is_saved = false;
$error_msg = '';

if (! filter_var($url, FILTER_VALIDATE_URL)) {
	$error_msg = 'URL格式错误';
	goto error_flag;
}

$urls = explode('?', $url);
$url = $urls[0];

$info = pathinfo($url);
$filelter_mine = ['php', 'sh'];
if (in_array($info['extension'], $filelter_mine) ) {
	$error_msg = '文件格式不支持';
	goto error_flag;
}

if (! empty($file)) {
	$file_ext = pathinfo($file);	
	if (in_array($file_ext['extension'], $filelter_mine) ) {
		$error_msg = '文件格式不支持';
		goto error_flag;
	}	
}
	
$command = "/usr/bin/expect download.exp {$url} {$time} {$file}";
exec($command, $output, $result);

if (count($output) < 5) {
	goto error_flag;
}

$filename = '';
foreach($output as $key => $value) {
	if ($is_saved && $value === '') {
		break;
	}
	if (false !== strpos($value, 'saved')) {
		$is_saved = true;
	}

	if (false !== strpos($value, 'Saving')) {
		preg_match("/\'([^']*)\'/", $value, $tmp_name);
		if (isset($tmp_name[1])) {
			$tmp_name_arr = explode("/", $tmp_name[1]);		
			$filename = $tmp_name_arr[count($tmp_name_arr) - 1];
		}
	}
}

error_flag:

$msg = [
	'code' => 1,
	'message' => $error_msg ?: '下载失败',
	'data' => [
		'name' => $filename,
		'command' => $command,
	]
];

if ($is_saved) {
	$msg['code'] 	= 0;
	$msg['message'] = '下载成功';
}

if ($msg['code'] !== 0) {
	if (! unlink('wget-log')) {
		$msg['message'] .= ', wget-log清除失败';
	}
}

echo json_encode($msg);

//var_dump($command, $output, $result);