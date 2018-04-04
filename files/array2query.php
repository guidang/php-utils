<?php

/**
 * Array 数据 转 JSON格式数据
 */
 
$content = isset($_POST['content']) ? $_POST['content'] : '';
$bulk_data = '';
$error = 0;

if (! empty($content)) {
	$content_data = '$content_str = '.$content.';';
	eval($content_data);
	$json = $content_str;
	if (empty($json)) {
		$error = 1;
	} else {
		$error = 2;
		$bulk_data = $content_str;
	}
}
?>
<!doctype html>
<html>
<head>
<title>Array数组格式 转 Query格式数据</title>
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
.text {
	width: 480px;
}
</style>
</head>
<body>
<div class='container'> 
	<form method='POST'>
	<label for='content'>Array数组格式</label>
	<textarea name='content' cols='80' rows='5' placeholder='Array格式：array("a" => "123", "b" => "456")' required><?php echo $content; ?></textarea>
	<button>提交</button>
	</form> 
	<?php if ($error == 2) {?>
	<div>
		<h4>JSON格式Query格式数据数据</h4>
		<pre class='text'>
		<?php echo http_build_query($bulk_data); ?></pre>
	</div>
	<?php } else if ($error == 1) { ?>
	<div class='tips'>Array数组格式有误</div>
	<?php } ?>
</div>
</body>
<html>
 