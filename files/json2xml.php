<?php

/**
 * JSON 数据 转 POSTMAN Bulk Edit
 */
 
$content = isset($_POST['content']) ? $_POST['content'] : '';
$bulk_data = '';
$error = 0;

if (! empty($content)) {
	$obj = json_decode($content, true);
	if (empty($obj)) {
		$error = 1;
	} else {
		$error = 2;
		
		class Exporter
		{
			private $root = 'document';
			private $indentation = '    ';
			// TODO: private $this->addtypes = false; // type="string|int|float|array|null|bool"
			public function export($data)
			{
				$data = array($this->root => $data);
				return '<?xml version="1.0" encoding="UTF-8"?>' . $this->recurse($data, 0) . PHP_EOL;
			}
			private function recurse($data, $level)
			{
				$str = '';
				$indent = str_repeat($this->indentation, $level);
				foreach ($data as $key => $value) {
					$str .= PHP_EOL . $indent . '<' . $key;
					if ($value === null) {
						$str .= ' />';
					} else {
						$str .= '>';
						if (is_array($value)) {
							if ($value) {
								//$temporary = $this->getArrayName($key);
								foreach ($value as $k => $entry) {
									$str .= $this->recurse(array($k => $entry), $level + 1);
								}
								$str .= PHP_EOL . $indent;
							}
						} else if (is_object($value)) {
							if ($value) {
								$str .= $this->recurse($value, $level + 1);
								$str .= PHP_EOL . $indent;
							}
						} else {
							if (is_bool($value)) {
								$value = $value ? 'true' : 'false';
							}
							$str .= $this->escape($value);
						}
						$str .= '</' . $key . '>';
					}
				}
				
				return $str;
			}
			private function escape($value)
			{
				// TODO:
				return $value;
			}
			private function getArrayName($parentName)
			{
				// TODO: special namding for tag names within arrays
				return $parentName;
			}
		}
	}
}
?>
<!doctype html>
<html>
<head>
<title>JSON格式数据 转 XML 格式数据</title>
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
		<h4>XML格式数据</h4>
		<pre><?php print_r($bulk_data); ?></pre>
	<div>
	<?php } else if ($error == 1) { ?>
	<div class='tips'>JSON数据格式有误</div>
	<?php } ?>
</div>
</body>
<html>
