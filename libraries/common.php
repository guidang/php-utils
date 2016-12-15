<?php
/**
 * 公共类
 * File:   Common.php
 * Author: Skiychan <dev@skiy.net>
 * Created: 2014-10-19
 * Updated: 2016-12-15
 */

class Common {
	
	static public function post($key='') {
		if ($key === '') {
			return $_POST;
		}
		return isset($_POST[$key]) ? $_POST[$key] : '';
	}

	static public function get($key='') {
		if ($key === '') {
			return $_GET;
		}
		return isset($_GET[$key]) ? $_GET[$key] : '';
	}

	static public function post_get($key) {
		return isset($_POST[$key]) ? $_POST[$key] :
					isset($_GET[$key]) ? $_GET[$key] : '';
	}

	static public function get_post($key) {
		return isset($_GET[$key]) ? $_GET[$key] : 
					isset($_POST[$key]) ? $_POST[$key] : '';
	}

	static public function file_exists_error($filePath) {
		if (! file_exists($filePath)) {
			header("http/1.1 404 not found");
			exit;
		}
	}

	/**
	 * 
	 * 创建登录日志
	 * $params 参数值(array / string)
	 * $format 参数值 ("arr" / "json"), 
	 * $clear BOOL
	 */	
	static public function createLog($params, $format="arr", $clear=false, $logname="test") {
		
		switch($format) {
			case 'arr':
			$data = json_encode($params);
			break;
			
			case 'json':
			$data = $params;
			break;
			
			default:
			$data = json_encode($params);
		}
		
		$logpath = $logname;
		if (in_array($logname, array('test')) ||
				strstr($logname, 'test')) {
			$logpath = __DIR__."/logs/{$logname}.log";
		}

		if ($clear) {
			file_put_contents($logpath, date('Y-m-d H:i:s').":\r\n".$data);
		} else {
			file_put_contents($logpath, "\r\n".date('Y-m-d H:i:s').":\r\n". $data ."\r\n\r\n", FILE_APPEND); 
		}
	}

	/**
	 * $params 要签名的参数(array / string)
	 * $filter 过滤的参数键名 array, 
	 * $mv     键值为空的值是否移除 BOOL
	 * $sort   排序 (1 键名)
	 * $return 返回数组值 TRUE, 默认不返回 FALSE
	 */
	static public function sign_encode($params, $filter=array(), $mv=TRUE, $sort=1, $return=FALSE) {
		$tmp = array();
		if (is_string($params)) {
			
			/*
			$arrs = explode('&', $params);
			foreach ($arrs as $key => $value) {
				$arr = explode('=', $value);
				$tmp[$arr[0]] = $arr[1];
			} */
			parse_str($params, $tmp);
			
			empty($tmp) || $params = $tmp; 		
		}
		
		if (empty($params) || ! is_array($params)) {
			return '';
		}
		
		$result = array();
		foreach ($params as $key => $value) {
			if (in_array($key, $filter)) {
				continue;
			}
			
			if ($mv && $value === '') {
				continue;
			}
			
			$result[$key] = urldecode($value);
		}
		
		switch ($sort) {
			case 1:
				ksort($result);
				break;
		}
		
		if ($return) {
			return $result;
		}
		
		return urldecode(http_build_query($result));
	}

		/* curl配置: 取网页源码、模拟登陆、POST提交
		 * @param $url: 如果非数组，则为http;如是数组，则为https
		 * @param $header: 头文件
		 * @param $post: post方式提交 array 或 abc=1&bcd=2 形式
		 * @param $cookies: 0默认无cookie,1为设置,2为获取
		 */
	static public function curls($urls, $header = FALSE, $post = FALSE, $cookies = 0) {
		$url = is_array($urls) ? $urls['0'] : $urls;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		 
		//带header方式提交
		if($header != FALSE){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}
		 
		//post提交方式
		if($post != FALSE){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}

		//cookies
		if($cookies == 1){
			curl_setopt($ch, CURLOPT_COOKIEJAR, "cookiefile");
		}else if($cookies == 2){
			curl_setopt($ch, CURLOPT_COOKIEFILE, "cookiefile");
		}

		//https
		if(is_array($urls)){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}
		
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

	/**
	 * 将网页转换成XML，再转换成DOM
	 * @param $data 非数组=>源码,数组 array($url, 1)1为file_get_contents,2为curl
	 */
	static public function pageToDom($data, $encoded = "utf-8"){

		if (is_array($data)) {
		   if ($data[1] == 1) {
			   $datas = @file_get_contents($data[0]);
		   }

		   if ($data[1] == 2) {
			   $datas = @$this->curls($data[0]);
		   }
		} else {
			$datas = $data;
		}

		if (empty($datas)){
			return false;
		}

		$meta = '<meta http-equiv="Content-Type" content="text/html; charset='.$encoded.'"/>';
		$datas = $meta.$datas;
		$xmldoc = new DOMDocument();
		@$xmldoc->loadHTML($datas);
		$xmldoc->normalizeDocument();
		$domresult = new Domxpath($xmldoc);

		return $domresult;
	}
}