<?php

/**
 * JSON 数据 转 POSTMAN Bulk Edit
 */
 
$content = isset($_POST['content']) ? $_POST['content'] : '';
$bulk_data = '';
$error = 0;

if (! empty($content)) {
	$arr = json_decode($content, true);
	if (empty($arr)) {
		$error = 1;
	} else {
		$error = 2;
		$data_str = '';
		var_export($arr);
		$bulk_data = $data_str;
	}
}
?>
<!doctype html>
<html>
<head>
<title>JSON格式数据 转 Array数组格式</title>
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
	<label for='content'>JSON格式数据</label>
	<textarea name='content' cols='80' rows='5' placeholder='JSON格式：{"key1":"value1","key2":"value2","key3":"value3"}' required><?php echo $content; ?></textarea>
	<button>提交</button>
	</form> 
	<?php if ($error == 2) {?>
	<div>
		<h4>Array数组格式</h4>
		<pre><?php echo $bulk_data; ?></pre>
	<div>
	<?php } else if ($error == 1) { ?>
	<div class='tips'>JSON数据格式有误</div>
	<?php } ?>
</div>
</body>
<html>
 