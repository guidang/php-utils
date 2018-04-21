<?php
/**
 * 公共函数
 */

date_default_timezone_set('Asia/Shanghai'); //'Asia/Shanghai'   亚洲/上海 

function post($key='') {
	$post_data = $_POST;
	if (empty($post_data)) {
		$post_str = file_get_contents("php://input");
		$post_data = json_decode($post_str, true);

		if (empty($post_data)) {
			parse_str($post_str, $post_data);
		}
	}

	if ($key === '') {
		return $post_data;
	}

    return isset($post_data[$key]) ? $post_data[$key] : '';
}

function get($key='') {
	if ($key === '') {
		return $_GET;
	}
    return isset($_GET[$key]) ? $_GET[$key] : '';
}

function post_get($key) {
    return isset($_POST[$key]) ? $_POST[$key] :
        (isset($_GET[$key]) ? $_GET[$key] : '');
}

function get_post($key) {
    return isset($_GET[$key]) ? $_GET[$key] :
        (isset($_POST[$key]) ? $_POST[$key] : '');
}

function file_exists_error($filePath) {
    if (! file_exists($filePath)) {
        header("http/1.1 404 not found");
        exit;
    }
}

//HTTP json数据请求函数      
function http_post_data($url, $data_string) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);//设置等待时间
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//要求结果为字符串且输出到屏幕上
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json; charset=utf-8',
		'Content-Length: ' . strlen($data_string))
	);
	$result = curl_exec($ch);
	return $result;
}

/**
 * $params 要签名的参数(array / string)
 * $filter 过滤的参数键名 array, 
 * $mv     键值为空的值是否移除 BOOL
 * $sort   排序 (1 键名)
 * $return 返回数组值 TRUE, 默认不返回 FALSE
 */
function sign_encode($params, $filter=array(), $mv=TRUE, $sort=1, $return=FALSE) {
	$tmp = array();
    if (is_string($params)) {
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

/**
	* 转换字符集编码
	* @param $data
	* @param $targetCharset
	* @return string
	*/
function characet($data, $targetCharset) {
	
	if (!empty($data)) {
		$fileType = "UTF-8";
		if (strcasecmp($fileType, $targetCharset) != 0) {
			$data = mb_convert_encoding($data, $targetCharset, $fileType);
			//$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
		}
	}


	return $data;
}

/**
	* 校验$value是否非空
	*  if not set ,return true;
	*    if is null , return true;
	**/
function checkEmpty($value) {
	if (!isset($value))
		return true;
	if ($value === null)
		return true;
	if (trim($value) === "")
		return true;

	return false;
}

/**格式化公钥
 * $pubKey PKCS#1格式的公钥串
 * return pem格式公钥， 可以保存为.pem文件
 */
function formatPubKey($pubKey) {
    $fKey = "-----BEGIN PUBLIC KEY-----\n";
    $len = strlen($pubKey);
    for($i = 0; $i < $len; ) {
        $fKey = $fKey . substr($pubKey, $i, 64) . "\n";
        $i += 64;
    }
    $fKey .= "-----END PUBLIC KEY-----";
    return $fKey;
}


/**格式化公钥
 * $priKey PKCS#1格式的私钥串
 * return pem格式私钥， 可以保存为.pem文件
 */
function formatPriKey($priKey) {
    $fKey = "-----BEGIN RSA PRIVATE KEY-----\n";
    $len = strlen($priKey);
    for($i = 0; $i < $len; ) {
        $fKey = $fKey . substr($priKey, $i, 64) . "\n";
        $i += 64;
    }
    $fKey .= "-----END RSA PRIVATE KEY-----";
    return $fKey;
}

/**RSA签名
 * $data待签名数据
 * $priKey商户私钥
 * 签名用商户私钥
 * 使用MD5摘要算法
 * 最后的签名，需要用base64编码
 * return Sign签名
 */
function sign($data, $priKey) {
    //转换为openssl密钥
    $res = openssl_get_privatekey($priKey);

    //调用openssl内置签名方法，生成签名$sign
    openssl_sign($data, $sign, $res, OPENSSL_ALGO_MD5);

    //释放资源
    openssl_free_key($res);
    
    //base64编码
    $sign = base64_encode($sign);
    return $sign;
}

/**RSA验签
 * $data待签名数据
 * $sign需要验签的签名
 * $pubKey爱贝公钥
 * 验签用爱贝公钥，摘要算法为MD5
 * return 验签是否通过 bool值
 */
function verify($data, $sign, $pubKey)  {
    //转换为openssl格式密钥
    $res = openssl_get_publickey($pubKey);

    //调用openssl内置方法验签，返回bool值
    $result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_MD5);
	
    //释放资源
    openssl_free_key($res);

    //返回资源是否成功
    return $result;
}

function getSignContent($params, $postCharset="UTF-8") {
	ksort($params);

	$stringToBeSigned = "";
	$i = 0;
	foreach ($params as $k => $v) {
		if (false === checkEmpty($v) && "@" != substr($v, 0, 1)) {

			// 转换成目标字符集
			$v = characet($v, $postCharset);

			if ($i == 0) {
				$stringToBeSigned .= "$k" . "=" . "$v";
			} else {
				$stringToBeSigned .= "&" . "$k" . "=" . "$v";
			}
			$i++;
		}
	}

	unset ($k, $v);
	return $stringToBeSigned;
}


//此方法对value做urlencode
function getSignContentUrlencode($params, $postCharset="UTF-8") {
	ksort($params);

	$stringToBeSigned = "";
	$i = 0;
	foreach ($params as $k => $v) {
		if (false === checkEmpty($v) && "@" != substr($v, 0, 1)) {

			// 转换成目标字符集
			$v = characet($v, $postCharset);

			if ($i == 0) {
				$stringToBeSigned .= "$k" . "=" . urlencode($v);
			} else {
				$stringToBeSigned .= "&" . "$k" . "=" . urlencode($v);
			}
			$i++;
		}
	}

	unset ($k, $v);
	return $stringToBeSigned;
}

/**
 * 全新的DEBUG工具
 */
function debugLog($param, $clear=false, $logname="request") {
	if (defined("DEBUG") && DEBUG === false) {
		return false;
	}
  
    defined("APP_ROOT_PATH") || define('APP_ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

	is_string($param) || $param = var_export($param, TRUE);

	$logpath = $logname;
	if (in_array($logname, array('request', 'pay', 'sign', 'test')) ||
			strstr($logname, 'request') || strstr($logname, 'pay') || strstr($logname, 'sign') || strstr($logname, 'test')) {
		$logpath = APP_ROOT_PATH."logs/" . date('Y-m-d') . '_' . $logname . ".log";
	}

	if ($clear) {
		file_put_contents($logpath, date('Y-m-d H:i:s').":\r\n".$param);
	} else {
	    file_put_contents($logpath, "\r\n".date('Y-m-d H:i:s').":\r\n". $param ."\r\n\r\n", FILE_APPEND); 
	}
}

/**
 * 支付日志
 */
function paylog($param, $chan, $type) {
    defined("APP_ROOT_PATH") || define('APP_ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
	$log_path = APP_ROOT_PATH . "paylogs/{$chan}_%s.log";
	if (strtoupper(substr(PHP_OS,0,3))==='WIN') {
		$log_path = "E:\pay_tmp_logs\{$chan}_%s.log";
	}

	switch($type) {
		//回调请求数据
		case 1:
			$log_path = sprintf($log_path, 'params');
			is_string($param) || $param = var_export($param, TRUE);
			break;

		//发货请求参数	
		case 2:
			$log_path = sprintf($log_path, 'request');
			is_string($param) || $param = var_export($param, TRUE);
			break;	

		//发货请求结果	
		case 3:
			$log_path = sprintf($log_path, 'response');
			$result = "未知";
			if ($param['result'] == 'failure') {
				$result = '发货失败';
			} else if ($param['result'] == 'success') {
				$result = '发货成功';
			}

			$param = "状态:{$result}, 订单号:{$param['notice_sn']}, 金额:{$param['amount']}元, user_id:{$param['user_id']}";		
            break;	

		default:
			exit;	
	}

	file_put_contents($log_path, date('Y-m-d H:i:s').":\r\n".$param."\r\n\r\n", FILE_APPEND);
}

/**
 * 将XML转为Array
 * @param $xml
 * @return mixed
 */
function xml2Array($xml) {
	//将XML转为array
	//禁止引用外部xml实体
	libxml_disable_entity_loader(true);
	$result = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
	return $result;
}

  /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $url 转跳页面
     * @param $para_temp 请求参数数组
     * @return 提交表单HTML文本
     * @return string
     */
    function buildRequestForm($url, $para_temp) {

        $sHtml = "<form id='jssubmit' name='htmlsubmit' action='".$url."' method='POST'>";
        foreach ($para_temp as $key => $val) {
            if (false === checkEmpty($val)) {
                //$val = $this->characet($val, $this->postCharset);
                $val = str_replace("'","&apos;",$val);
                //$val = str_replace("\"","&quot;",$val);
                $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
            }
        }

        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit' value='ok' style='display:none;'></form>";

        $sHtml = $sHtml."<script>document.forms['htmlsubmit'].submit();</script>";

        return $sHtml;
    }

    /**
     * 执行转跳页功能
     * @param $gateway_url 网关
     * @param $params 参数数组
     * @param string $httpmethod 请求方法 (默认POST)
     * @return 提交表单HTML文本|string
     */
    function pageExecute($gateway_url, $params, $httpmethod = "POST") {
        if ("GET" == $httpmethod) {
            //拼接GET请求串
            $requestUrl = $gateway_url . "?" . http_build_query($params);

            return $requestUrl;
        } else {
            //拼接表单字符串
            return buildRequestForm($gateway_url, $params);
        }
    }