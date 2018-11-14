<?php

/**
 * 公共函数
 * @author: Skiychan <dev@skiy.net>
 */

date_default_timezone_set('Asia/Shanghai'); //'Asia/Shanghai'   亚洲/上海 

if (!function_exists('post')) {
    function post($key = '') {
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
}

if (!function_exists('get')) {
    function get($key = '') {
        if ($key === '') {
            return $_GET;
        }
        return isset($_GET[$key]) ? $_GET[$key] : '';
    }
}


if (!function_exists('post_get')) {
    function post_get($key = '') {
        return post($key) ? post($key) :
            (get($key) ? get($key) : '');
    }
}

if (!function_exists('get_post')) {
    function get_post($key) {
        return isset($_GET[$key]) ? $_GET[$key] :
            (isset($_POST[$key]) ? $_POST[$key] : '');
    }
}

if (!function_exists('file_exists_error')) {
    function file_exists_error($filePath) {
        if (!file_exists($filePath)) {
            header("http/1.1 404 not found");
            exit;
        }
    }
}

if (!function_exists('http_post_data')) {
    /**
     * HTTP json数据请求函数
     * @param $url
     * @param $data_string
     * @return mixed
     */
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
}

if (!function_exists('sign_encode')) {
    /**
     * 签名
     * @param $params 要签名的参数(array / string)
     * @param array $filter 过滤的参数键名 array,
     * @param bool $mv 键值为空的值是否移除 BOOL
     * @param int $sort 排序 (1 键名)
     * @param bool $return
     * @param bool $encode 是否encode编码值
     * @return array|string
     */
    function sign_encode($params, $filter = array(), $mv = TRUE, $sort = 1, $return = FALSE, $encode = FALSE) {
        $tmp = array();
        if (is_string($params)) {
            parse_str($params, $tmp);

            empty($tmp) || $params = $tmp;
        }

        if (empty($params) || !is_array($params)) {
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

            $result[$key] = $encode ? urlencode($value) : $value;
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

}

if (!function_exists('characet')) {
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
}

if (!function_exists('checkEmpty')) {
    /**
     * 校验$value是否非空
     * @param $value
     * @return bool
     */
    function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }
}

if (!function_exists('format_publickey')) {
    /**
     * 格式化公钥
     * $pubKey PKCS#1格式的公钥串
     * return pem格式公钥， 可以保存为.pem文件
     */
    function format_publickey($pubKey) {
        $fKey = "-----BEGIN PUBLIC KEY-----\n";
        $len = strlen($pubKey);
        for ($i = 0; $i < $len;) {
            $fKey = $fKey . substr($pubKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END PUBLIC KEY-----";
        return $fKey;
    }
}

if (!function_exists('format_privatekey')) {
    /**
     * 格式化公钥
     * @param $priKey PKCS#1格式的私钥串
     * @return string pem格式私钥， 可以保存为.pem文件
     */
    function format_privatekey($priKey) {
        $fKey = "-----BEGIN RSA PRIVATE KEY-----\n";
        $len = strlen($priKey);
        for ($i = 0; $i < $len;) {
            $fKey = $fKey . substr($priKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END RSA PRIVATE KEY-----";
        return $fKey;
    }
}

if (!function_exists('sign')) {
    /**
     * RSA签名
     * @param $data 数据
     * @param $priKey 私钥
     * @param int $alg 加密方式
     * @return string
     */
    function sign($data, $priKey, $alg = OPENSSL_ALGO_MD5) {
        //转换为openssl密钥
        $res = openssl_get_privatekey($priKey);

        //调用openssl内置签名方法，生成签名$sign
        openssl_sign($data, $sign, $res, $alg);

        //释放资源
        openssl_free_key($res);

        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }
}

if (!function_exists('verify')) {
    /**
     * RSA验签
     * @param $data 数据
     * @param $sign
     * @param $pubKey 公钥
     * @return bool
     */
    function verify($data, $sign, $pubKey, $alg = OPENSSL_ALGO_MD5) {
        //转换为openssl格式密钥
        $res = openssl_get_publickey($pubKey);

        //调用openssl内置方法验签，返回bool值
        $result = (bool)openssl_verify($data, base64_decode($sign), $res, $alg);

        //释放资源
        openssl_free_key($res);

        //返回资源是否成功
        return $result;
    }
}

if (!function_exists('getSignContent')) {
    /**
     * @param $params
     * @param string $postCharset
     * @return string
     */
    function getSignContent($params, $postCharset = "UTF-8") {
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
}

if (!function_exists('getSignContentUrlencode')) {
    /**
     * 此方法对value做urlencode
     * @param $params
     * @param string $postCharset
     * @return string
     */
    function getSignContentUrlencode($params, $postCharset = "UTF-8") {
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
}

if (!function_exists('debugLog')) {
    /**
     * 全新的 DEBUG LOG 工具
     * @param $param
     * @param bool $clear
     * @param string $logname 请求参数 ('request', 'pay', 'sign', 'test') 或 'debug,abc'方式
     * @param bool $date 是否添加日期作为文件名
     * @return bool
     */
    function debugLog($param, $clear = false, $logname = "request", $date = true) {
        if (defined("DEBUG") && DEBUG === false) {
            return false;
        }

        defined("LOG_PATH") || define('LOG_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

        is_string($param) || $param = var_export($param, TRUE);

        $log_path = $logname;
        if (in_array($logname, array('request', 'pay', 'sign', 'test')) ||
            strstr($logname, 'request') || strstr($logname, 'pay') || strstr($logname, 'sign') || strstr($logname, 'test')) {

            $date_str = $date ? date('Y-m-d') . '_' : '';
            $log_path = LOG_PATH . "logs/" . $date_str . $logname . ".log";
        } else if (strstr($logname, 'debug')) {
            $paths = explode(',', $logname);
            (count($paths) < 2) && $paths[1] = 'debug';

            $date_str = $date ? '_' . date('Y-m-d') : '';
            $log_path = LOG_PATH . "logs/" . $paths[1] . $date_str . ".log";
        }
//        var_dump($log_path);
        if ($clear) {
            file_put_contents($log_path, date('Y-m-d H:i:s') . ":\r\n" . $param);
        } else {
            file_put_contents($log_path, "\r\n" . date('Y-m-d H:i:s') . ":\r\n" . $param . "\r\n\r\n", FILE_APPEND);
        }
    }
}

if (!function_exists('paylog')) {
    /**
     * 支付日志
     * @param $param
     * @param $chan
     * @param $type
     */
    function paylog($param, $chan, $type) {
        defined("PAYLOG_PATH") || define('PAYLOG_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
        $log_path = PAYLOG_PATH . "paylogs/{$chan}_%s.log";
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $log_path = "E:\pay_tmp_logs\{$chan}_%s.log";
        }

        switch ($type) {
            //回调请求数据
            case 1:
                $log_path = sprintf($log_path, 'params');
                is_string($param) || $param = var_export($param, TRUE);
                break;

            //订单创建请求参数
            case 2:
                $log_path = sprintf($log_path, 'request');
                is_string($param) || $param = var_export($param, TRUE);
                break;

            //订单创建请求结果
            case 3:
                $log_path = sprintf($log_path, 'response');
                is_string($param) || $param = var_export($param, TRUE);
                break;

            //订单创建请求结果
            case -1:
                $paths = explode(',', $chan);
                (count($paths) < 2) && $paths[1] = 'debug';

                $log_path = APP_ROOT_PATH . "paylogs/{$paths[0]}_%s.log";
                $log_path = sprintf($log_path, $paths[1]);
                is_string($param) || $param = var_export($param, TRUE);
                break;

            default:
                exit;
        }

        file_put_contents($log_path, date('Y-m-d H:i:s') . ":\r\n" . $param . "\r\n\r\n", FILE_APPEND);
    }
}

if (!function_exists('xml2Array')) {
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
}

if (!function_exists('buildRequestForm')) {
    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $url 转跳页面
     * @param $para_temp 请求参数数组
     * @return 提交表单HTML文本
     * @param bool $encode 是否编码
     * @return string
     */
    function buildRequestForm($url, $para_temp, $encode = true) {

        $sHtml = "<form id='jssubmit' name='htmlsubmit' action='" . $url . "' method='POST'>";
        foreach ($para_temp as $key => $val) {
            if (false === checkEmpty($val)) {
                if ($encode) {
                    $val = urlencode($val);
                }
                $sHtml .= "<input type='hidden' name='{$key}' value='{$val}' />";
            }
        }

        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml . "<input type='submit' value='ok' style='display:none;'></form>";

        $sHtml = $sHtml . "<script>document.forms['htmlsubmit'].submit();</script>";

        return $sHtml;
    }
}

if (!function_exists('pageExecute')) {
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
}

if (!function_exists('ramdom_md5')) {
    /**
     * 生成随机的32位字符串/
     * @param string $string
     * @return string
     */
    function ramdom_md5($string = '') {
        //获取当前时间的微秒
        list($usec, $sec) = explode(' ', microtime());
        $microtime = ((float)$usec + (float)$sec);
        $microtime = str_replace('.', '', $microtime);

        //将微秒时间加长一个0-1000的随机变量
        for ($i = 0; $i < 19; $i++) {
            $microtime .= rand(0, 9);
        }

        $long_string = $string . $microtime;

        //md5加密后再base64编码
        $result = sha1(base64_encode(md5($long_string, true)));

        return $result;
    }
}

if (!function_exists('page_format')) {
    /**
     * 设置网页格式
     * @param string $type 格式
     */
    function page_format($type = 'html') {
        $formats = [
            'json' => 'application/json',
            'array' => 'application/json',
            'csv' => 'application/csv',
            'html' => 'text/html',
            'jsonp' => 'application/javascript',
            'php' => 'text/plain',
            'serialized' => 'application/vnd.php.serialized',
            'xml' => 'application/xml'
        ];

        $contentType = $formats[$type] ?: $formats['html'];
        header('Content-type: ' . $contentType);
    }
}

if (!function_exists('random_string')) {
    /**
     * 创建随机字符串
     * @param    string    type of random string.  basic, alpha, alnum, numeric, nozero, unique, md5, encrypt and sha1
     * @param    int    长度
     * @return    string
     */
    function random_string($type = 'alnum', $len = 8) {
        switch ($type) {
            case 'basic':
                return mt_rand();
            case 'alnum':
            case 'numeric':
            case 'nozero':
            case 'alpha':
                switch ($type) {
                    case 'alpha':
                        $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        break;
                    case 'alnum':
                        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        break;
                    case 'numeric':
                        $pool = '0123456789';
                        break;
                    case 'nozero':
                        $pool = '123456789';
                        break;
                }
                return substr(str_shuffle(str_repeat($pool, ceil($len / strlen($pool)))), 0, $len);
            case 'unique': // todo: remove in 3.1+
            case 'md5':
                return md5(uniqid(mt_rand()));
            case 'encrypt': // todo: remove in 3.1+
            case 'sha1':
                return sha1(uniqid(mt_rand(), true));
        }
    }
}

if (!function_exists('rand_number')) {
    /**
     * 生成随机数，并且过滤对应值
     * @param $start 开始(含)
     * @param $end 结束(含)
     * @param array $filter 过滤数组值
     * @return bool|int
     */
    function rand_number($start, $end, $filter = array()) {
        //过滤值已填满时则错误
        if (($end - $start + 1) == count($filter)) {
            return false;
        }
        $num = rand($start, $end);
        if (in_array($num, $filter)) {
            return rand_number($start, $end, $filter);
        }
        return $num;
    }
}

if (!function_exists('str_json_encode')) {
    /**
     * api不支持中文转义的json结构
     * @param $arr
     * @return string
     */
    function str_json_encode($arr) {
        if (count($arr) == 0) return "[]";
        $parts = array();
        $is_list = false;
        //Find out if the given array is a numerical array
        $keys = array_keys($arr);
        $max_length = count($arr) - 1;

        if (($keys [0] === 0) && ($keys [$max_length] === $max_length)) { //See if the first key is 0 and last key is length - 1
            $is_list = true;
            for ($i = 0; $i < count($keys); $i++) { //See if each key correspondes to its position
                if ($i != $keys [$i]) { //A key fails at position check.
                    $is_list = false; //It is an associative array.
                    break;
                }
            }
        }

        foreach ($arr as $key => $value) {
            if (is_array($value)) { //Custom handling for arrays
                if ($is_list)
                    $parts[] = str_json_encode($value); /* :RECURSION: */
                else
                    $parts[] = '"' . $key . '":' . str_json_encode($value); /* :RECURSION: */
            } else {
                $str = '';
                if (!$is_list)
                    $str = '"' . $key . '":';
                //Custom handling for multiple data types
                if (!is_string($value) && is_numeric($value) && $value < 2000000000)
                    $str .= $value; //Numbers
                elseif ($value === false)
                    $str .= 'false'; //The booleans
                elseif ($value === true)
                    $str .= 'true';
                else
                    $str .= '"' . addslashes($value) . '"'; //All other things
                // :TODO: Is there any more datatype we should be in the lookout for? (Object?)
                $parts [] = $str;
            }
        }
        $json = implode(',', $parts);
        if ($is_list)
            return '[' . $json . ']'; //Return numerical JSON
        return '{' . $json . '}'; //Return associative JSON
    }

}

if (!function_exists('http_post')) {
    /**
     * POST 请求
     * @param string $url 链接
     * @param array $param 参数
     * @param array $headers 用户头部信息
     * @param boolean $post_file 是否文件上传
     * @param array $use_cert 用户证书 (数组或字符串)
     * @param int $second 超时时间
     * @param bool $status 是否返回请求状态信息
     * @return bool|mixed
     */
    function http_post($url, $param, $headers = array(), $post_file = false, $use_cert = array(), $second = 30, $status = false) {
        $oCurl = curl_init();
        //设置超时
        curl_setopt($oCurl, CURLOPT_TIMEOUT, $second);

        //设置头
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, $headers);
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            //curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
        }
        if (PHP_VERSION_ID >= 50500 && class_exists('\CURLFile')) {
            $is_curlFile = true;
        } else {
            $is_curlFile = false;
            if (defined('CURLOPT_SAFE_UPLOAD')) {
                curl_setopt($oCurl, CURLOPT_SAFE_UPLOAD, false);
            }
        }
        if (empty($param)) {
            $strPOST = array();
        } elseif (is_string($param)) {
            $strPOST = $param;
        } elseif ($post_file) {
            if ($is_curlFile) {
                foreach ($param as $key => $val) {
                    if (substr($val, 0, 1) == '@') {
                        $param[$key] = new \CURLFile(realpath(substr($val, 1)));
                    }
                }
            }
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach ($param as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        //设置header
        curl_setopt($oCurl, CURLOPT_HEADER, false);
        //设置证书
        if (!empty($use_cert)) {
            $sslkey = '';
            if (is_string($use_cert)) {
                $sslcert = $use_cert;
            } else if (is_array($use_cert)) {
                $sslcert = $use_cert[0];
                empty($use_cert[1]) || $sslkey = $use_cert[1];
            }
            //第一种方法，cert 与 key 分别属于两个.pem文件
            //第二种方式，两个文件合成一个.pem文件
            curl_setopt($oCurl, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($oCurl, CURLOPT_SSLCERT, $sslcert);
            //第一种方式
            if (!empty($sslkey)) {
                curl_setopt($oCurl, CURLOPT_SSLKEYTYPE, 'PEM');
                curl_setopt($oCurl, CURLOPT_SSLKEY, $sslkey);
            }
        }
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);

        if ($status) {
            return $aStatus;
        }

        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }
}

if (!function_exists('http_get')) {
    /**
     * GET 请求
     * @param string $url 链接
     * @param array $headers 用户头部信息
     * @param bool $status 是否返回状态信息
     * @return bool|mixed
     */
    function http_get($url, $headers = array(), $status = false) {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, $headers);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);


        if ($status) {
            return $aStatus;
        }

        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }
}

if (!function_exists('server_ip')) {
    /**
     * 不安全的获取 IP 方式，在开启CDN的时候，如果被人猜到真实 IP，则可以伪造。
     * @return string
     */
    function server_ip() {
        if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
            $ip = $_SERVER['HTTP_CDN_SRC_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $arr = array_filter(explode(',', $ip));
            $ip = end($arr);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return long2ip(ip2long($ip));
    }
}

if (!function_exists('client_ip')) {
    /**
     * 获取客户端IP
     * @return array|false|string
     */
    function client_ip() {
        //判断服务器是否允许$_SERVER
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            //不允许就使用getenv获取
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $realip = getenv('HTTP_CLIENT_IP');
            } else {
                $realip = getenv('REMOTE_ADDR');
            }
        }

        return $realip;
    }
}

if (!function_exists('string_in_array')) {
    /**
     * 搜索字符串是否包含在数组中的值里
     * @param $str 搜索或被搜 的字符串
     * @param $arr 数组(或二维数组) 如 array('aa', 'a1' => 'bb') 或 array(array('aa1', 'a1' => 'bb'), array('aa2', 'a1' => 'bb'))
     * @param string $key a1
     * @param bool $multi 是否为二维数组
     * @param bool $direction 是从 str 搜索 arr,还是反过来
     * @return bool
     */
    function string_in_array($str, $arr, $key = '', $multi = false, $direction = true) {
        if (empty($str) || empty($arr)) {
            return false;
        }

        if ($multi) {
            foreach ($arr as $k => $v) {
                if (!is_array($v)) {
                    continue;
                }

                $res = string_in_array($str, $v, $key, false);
                if ($res) {
                    return true;
                }
            }

            return false;
        }

        if (!empty($key)) {
            if (empty($arr[$key])) {
                return false;
            }

            if ($direction) {
                if (strstr($arr[$key], $str)) {
                    return true;
                }
            } else {
                if (strstr($str, $arr[$key])) {
                    return true;
                }
            }
        }

        foreach ($arr as $k => $v) {
            if (!empty($key)) {
                if (!isset($v[$key])) {
                    continue;
                }
                $v = $v[$key];
            }
            if ($direction) {
                if (strstr($v, $str)) {
                    return true;
                }
            } else {
                if (strstr($str, $v)) {
                    return true;
                }
            }
        }

        return false;
    }
}


if (!function_exists('create_captcha')) {
    /**
     * 创建验证码
     * @param $word 字符串
     * @param array $params 配置参数
     */
    function create_captcha($word, $params = array()) {
        header("Expires: " . date(DATE_RFC822));
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        header("Content-type: image/png");

        srand((double)microtime() * 1000000);

        $defaults = array(
            'img_width' => 50,
            'img_height' => 20,
            'font_size' => 5,
            'font_path' => '',
            'colors' => array(
                'black' => array(226, 100, 76),
                'text' => array(255, 255, 255),
                'gray' => array(200, 200, 200),
                'border' => array(153, 102, 102),
            )
        );

        foreach ($defaults as $key => $val) {
            if (!is_array($params) && empty($$key)) {
                $$key = $val;
            } else {
                $$key = isset($params[$key]) ? $params[$key] : $val;
            }
        }

        $im = imagecreate($img_width, $img_height);

        is_array($colors) || $colors = $defaults['colors'];

        foreach (array_keys($defaults['colors']) as $key) {
            is_array($colors[$key]) || $colors[$key] = $defaults['colors'][$key];
            $colors[$key] = ImageColorAllocate($im, $colors[$key][0], $colors[$key][1], $colors[$key][2]);
        }

        imagefill($im, $img_width, $img_height, $colors['gray']);

        $length = strlen($word);
        $angle = ($length >= 6) ? mt_rand(-($length - 6), ($length - 6)) : 0;
        $x_axis = mt_rand(6, (360 / $length) - 16);
        $y_axis = ($angle >= 0) ? mt_rand($img_height, $img_width) : mt_rand(6, $img_height);

        $theta = 1;
        $thetac = 7;
        $radius = 16;
        $circles = 20;
        $points = 32;

        for ($i = 0, $cp = ($circles * $points) - 1; $i < $cp; $i++) {
            $theta += $thetac;
            $rad = $radius * ($i / $points);
            $x = ($rad * cos($theta)) + $x_axis;
            $y = ($rad * sin($theta)) + $y_axis;
            $theta += $thetac;
            $rad1 = $radius * (($i + 1) / $points);
            $x1 = ($rad1 * cos($theta)) + $x_axis;
            $y1 = ($rad1 * sin($theta)) + $y_axis;
            imageline($im, $x, $y, $x1, $y1, $colors['gray']);
            $theta -= $thetac;
        }

        $use_font = ($font_path !== '' && file_exists($font_path) && function_exists('imagettftext'));
        if ($use_font === FALSE) {
            if ($font_size > 5 && $img_height <= 20) {
                $font_size = 5;
            }

            if ($font_size <= 5) {
                $x = 5;
            } else {
                $x = mt_rand(0, $img_width / $length);
            }
        } else {
            ($font_size > 30) && $font_size = 30;
            $x = mt_rand(0, $img_width / ($length / 1.5));
        }

        $text_width = $img_width / ($length + 1);
        for ($i = 0; $i < $length; $i++) {
            if ($use_font === FALSE) {
                if ($font_size <= 5) {
                    $y = mt_rand(0, $img_height / 2 - 5);
                } else {
                    $y = mt_rand(0, $img_height / 2);
                }
                imagestring($im, $font_size, $x, $y, $word[$i], $colors['text']);
                $x += mt_rand($text_width, ($text_width + $font_size / $img_height));
            } else {
                $y = mt_rand($img_height / 2, $img_height - 3);
                imagettftext($im, $font_size, $angle, $x, $y, $colors['text'], $font_path, $word[$i]);
                $x += $font_size;
            }
        }

//        imagestring($im, $font_size, 8, 2, $word, $colors['text']);

        imagerectangle($im, 0, 0, $img_width - 1, $img_height - 1, $colors['border']);

        for ($i = 0; $i < 90; $i++) {
            imagesetpixel($im, rand() % 70, rand() % 30, $colors['gray']);
        }
        ImagePNG($im);
        ImageDestroy($im);
    }

    if (!function_exists('number_rmb')) {
        /**
         * 阿拉伯数字金额转中文金额大写
         * @param $num
         * @return string
         */
        function number_rmb($num) {
            $cny = array('零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
            $cny_ext = array('拾', '佰', '仟', '万', '亿');
            $currency = array('圆', '角', '分', '整');

            $cny_1 = array('圆', '万', '亿', '万');
            $cny_2 = array('拾', '佰', '仟');

            $number = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);

            $number_str = number_format($num, 2, '.', '');
            $number_arr = explode('.', $number_str);
            $v = str_replace($number, $cny, $num);

            $real_val = '';

            //整数部分
            if ($number_arr[0] > 0) {
                $integer_arr = str_split(strrev($number_arr[0]), 4);

                $max_key = count($integer_arr) - 1;
                foreach ($integer_arr as $key => $value) {
                    $str = '';

                    $value = str_pad(strrev($value), 4, "0", STR_PAD_LEFT);

                    $str .= $value[3];
                    if ($value[2] != 0) {
                        $str = $value[2] . $cny_2[0] . $str;
                    } else {
                        $str = '0' . $str;
                    }

                    if ($value[1] != 0) {
                        $str = $value[1] . $cny_2[1] . $str;
                    } else {
                        $str = '0' . $str;
                    }

                    if ($value[0] != 0) {
                        $str = $value[0] . $cny_2[2] . $str;
                    } else {
                        $str = '0' . $str;
                    }

                    if ($max_key == $key) {
                        $str = ltrim($str, "0");
                    }

                    $str = str_replace($number, $cny, $str);

                    if ($value[3] > 0) {
                        $str = str_replace('零零零', '零', $str);
                        $str = str_replace('零零', '零', $str);
                    } else {
                        $str = rtrim($str, "零");
                        $str = str_replace('零零零', '', $str);
                    }

                    $real_val = $str . $cny_1[$key] . $real_val;
                }
            }

            //小数部分
            if ($number_arr[1] == 0) {
                $real_val .= $currency[3];
            } else if ($number_arr[1] < 10) {
                $real_val .= str_replace($number, $cny, $number_arr[1]) . $currency[2];
            } else {
                $real_val .= str_replace($number, $cny, $number_arr[1][0]) . $currency[1];

                if ($number_arr[1][1] > 0) {
                    $real_val .= str_replace($number, $cny, $number_arr[1][1]) . $currency[2];
                }
            }

            return $real_val;
        }
    }
}

if (!function_exists('urltolower')) {
    /**
     * 将URL中的域名及协议转小写
     * @param $url
     * @return bool|mixed
     */
    function urltolower($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        $parse_url_arr = parse_url($url);
        $pre_url = $parse_url_arr['scheme'] . '://' . $parse_url_arr['host'];
        $real_url = str_replace($pre_url, strtolower($pre_url), $url);
        return $real_url;
    }
}

if (!function_exists('mcrypt_decrypt')) {
    /**
     * PHP 7.2 + 支持 mcrypt
     * @param $cipher
     * @param $key
     * @param $data
     * @param $mode
     * @param $iv
     * @return string
     */
    function mcrypt_decrypt($cipher, $key , $data , $mode, $iv = '') {
        $method = mcrypt_method($cipher, $mode);
        $result = openssl_decrypt(base64_encode($data), $method, $key, 0, $iv);
        return $result;
    }
}

if (! function_exists('mcrypt_encrypt')) {

    /**
     * 支持PHP7.2 mcrypt_encrypt
     * @param $cipher
     * @param $key
     * @param $data
     * @param $mode
     * @param $iv
     * @return bool|string
     */
    function mcrypt_encrypt($cipher, $key, $data, $mode, $iv) {
        $method = mcrypt_method($cipher, $mode);
        return base64_decode(openssl_encrypt($data, $method, $key, 0, $iv));
    }
}

if (! function_exists('mcrypt_module_close')) {
    /**
     * 加密常量
     */
    function mcrypt_module_close() {
        define ('MCRYPT_ENCRYPT', 0);
        define ('MCRYPT_DECRYPT', 1);
        define ('MCRYPT_DEV_RANDOM', 0);
        define ('MCRYPT_DEV_URANDOM', 1);
        define ('MCRYPT_RAND', 2);
        define ('MCRYPT_3DES', "tripledes");
        define ('MCRYPT_ARCFOUR_IV', "arcfour-iv");
        define ('MCRYPT_ARCFOUR', "arcfour");
        define ('MCRYPT_BLOWFISH', "blowfish");
        define ('MCRYPT_BLOWFISH_COMPAT', "blowfish-compat");
        define ('MCRYPT_CAST_128', "cast-128");
        define ('MCRYPT_CAST_256', "cast-256");
        define ('MCRYPT_CRYPT', "crypt");
        define ('MCRYPT_DES', "des");
        define ('MCRYPT_ENIGNA', "crypt");
        define ('MCRYPT_GOST', "gost");
        define ('MCRYPT_LOKI97', "loki97");
        define ('MCRYPT_PANAMA', "panama");
        define ('MCRYPT_RC2', "rc2");
        define ('MCRYPT_RIJNDAEL_128', "rijndael-128");
        define ('MCRYPT_RIJNDAEL_192', "rijndael-192");
        define ('MCRYPT_RIJNDAEL_256', "rijndael-256");
        define ('MCRYPT_SAFER64', "safer-sk64");
        define ('MCRYPT_SAFER128', "safer-sk128");
        define ('MCRYPT_SAFERPLUS', "saferplus");
        define ('MCRYPT_SERPENT', "serpent");
        define ('MCRYPT_THREEWAY', "threeway");
        define ('MCRYPT_TRIPLEDES', "tripledes");
        define ('MCRYPT_TWOFISH', "twofish");
        define ('MCRYPT_WAKE', "wake");
        define ('MCRYPT_XTEA', "xtea");
        define ('MCRYPT_IDEA', "idea");
        define ('MCRYPT_MARS', "mars");
        define ('MCRYPT_RC6', "rc6");
        define ('MCRYPT_SKIPJACK', "skipjack");
        define ('MCRYPT_MODE_CBC', "cbc");
        define ('MCRYPT_MODE_CFB', "cfb");
        define ('MCRYPT_MODE_ECB', "ecb");
        define ('MCRYPT_MODE_NOFB', "nofb");
        define ('MCRYPT_MODE_OFB', "ofb");
        define ('MCRYPT_MODE_STREAM', "stream");
    }

    mcrypt_module_close();
}

if (! function_exists('mcrypt_method')) {

    /**
     * 加密方式
     * @param $cipher
     * @param $mode
     * @return string
     */
    function mcrypt_method($cipher, $mode) {
        $method = '';
        switch ($cipher) {
            case MCRYPT_BLOWFISH:
                $method .= 'bf-';
                break;

            case MCRYPT_RIJNDAEL_128:
                $method .= 'aes-128-';
                break;

            case MCRYPT_RIJNDAEL_192:
                $method .= 'aes-192-';
                break;

            case MCRYPT_RIJNDAEL_256:
                $method .= 'aes-256-';
                break;
        }

        $method = strtoupper($method . $mode);
        return $method;
    }
}