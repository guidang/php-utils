<?php

/**
 * JSON 数据 转 POSTMAN Bulk Edit
 */
 
$content = isset($_POST['content']) ? $_POST['content'] : '';
$bulk_data = '';
$error = 0;

if (! empty($content)) {
	$obj = json_decode($content);
	if (empty($obj)) {
		$error = 1;
	} else {
		$error = 2;
		$data_str = '';
		foreach($obj as $key => $value) {
			$data_str .= "\n".$key.':'.$value;
		}
		$bulk_data = $data_str;
	}
}
?>
<!doctype html>
<html>
<head>
<title>JSON格式数据 转POSTMAN BulkEdit格式数据</title>
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
		<h4>BulkEdit格式数据</h4>
		<pre><?php echo $bulk_data; ?></pre>
	</div>
	<?php } else if ($error == 1) { ?>
	<div class='tips'>JSON数据格式有误</div>
	<?php } ?>
</div>
</body>
<html>
 