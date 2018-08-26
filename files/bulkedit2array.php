<?php

/**
 * POSTMAN Bulk Edit 转 Array 数组
 */
 
$content = isset($_POST['content']) ? $_POST['content'] : '';
$filp = isset($_POST['flip']) ? (string)$_POST['flip'] : ':';
$bulk_data = array();
$error = 0;
//echo $content;exit;
if (! empty($content)) {
	if (! is_string($content)) {
		$error = 1;
	} else {
		$error = 2;
		$arr = explode("\r\n", $content);
		foreach ($arr as $key => $value) {
			$arr2 = explode($filp, $value);
			if (count($arr2) == 2) {
				$bulk_data[$arr2[0]] = $arr2[1];
			}
		}
	}
}
?>
<!doctype html>
<html>
<head>
<title>POSTMAN Bulk Edit 转 Array数组格式</title>
<style>
label,
button {
	display: block;
	margin-top: 8px;
	margin-bottom: 8px;
} 
.container {
	margin: 20px;
}
.tips {
	color: #dd0000;
}
</style>
</head>
<body>
<div class='container'> 
	<form method='POST'>
	<label for='content'>分隔符</label>
	<select name='flip'>
		<option value=':'>:</option>
		<option value='='>=</option>
	</select>
	<label for='content'>POSTMAN Bulk Edit</label>
	<textarea name='content' cols='80' rows='5' required><?php echo $content; ?></textarea>
	<button>提交</button>
	</form> 
	<?php if ($error == 2) {?>
	<div>
		<h4>Array数组格式</h4>
		<pre><?php var_export($bulk_data); ?></pre>
	<div>
	<?php } else if ($error == 1) { ?>
	<div class='tips'>POSTMAN Bulk Edit 数据格式有误</div>
	<?php } ?>
</div>
</body>
<html>