<?php

/**
 * Query 格式转数组
 */
 
$content = isset($_POST['content']) ? $_POST['content'] : '';
$bulk_data = '';
$error = 0;
//echo $content;exit;
if (! empty($content)) {
	if (! is_string($content)) {
		$error = 1;
	} else {
		$error = 2;
		//var_dump($content);
		parse_str($content, $bulk_data);
	}
}
?>
<!doctype html>
<html>
<head>
<title>Query 格式数据 转 Array数组格式</title>
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
	<label for='content'>Query格式数据</label>
	<textarea name='content' cols='80' rows='5' required><?php echo $content; ?></textarea>
	<button>提交</button>
	</form> 
	<?php if ($error == 2) {?>
	<div>
		<h4>Array数组格式</h4>
		<pre><?php var_export($bulk_data); ?></pre>
	<div>
	<?php } else if ($error == 1) { ?>
	<div class='tips'>Query 数据格式有误</div>
	<?php } ?>
</div>
</body>
<html>