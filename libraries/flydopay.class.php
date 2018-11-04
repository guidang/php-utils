<?php

/**
 *
 * File:  flydopay.class.php
 * Author: Skiychan <dev@skiy.net>
 * Created: 2018/11/04
 */

class FlydoPay {

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * @return string 拼接完成以后的字符串
     */
    public static function createLinkstring($para) {
        $arg = http_build_query($para);
        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return $arg;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串,参数值urlencode编码
     * @param $para 需要拼接的数组
     * @return string 拼接完成以后的字符串
     */
    public static function createLinkEncode($para) {
        $arr = array();
        foreach ($para as $key => $value) {
            $arr[$key] = urlencode($value);
        }
        $arg = http_build_query($arr);
        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return $arg;
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * @param array $filter 要过滤的键名数组
     * @param bool $remove 是否过滤空值
     * @return array 去掉空值与签名参数后的新签名参数组
     */
    public static function paraFilter($para, $filter = array('sign', 'signType'), $remove = true) {
        $para_filter = array();
        foreach ($para as $key => $value) {
            if (in_array($value, $filter)) {
                continue;
            }

            if ($value === '' && $remove) {
                continue;
            }

            $para_filter[$key] = $value;
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * @return mixed 排序后的数组
     */
    public static function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 签名字符串
     * @param $prestr 需要签名的字符串
     * @param string $type 签名类型 默认值：MD5
     * @return string 签名结果
     */
    public static function signData($prestr, $type = 'md5') {
        $sign = '';
        switch (strtolower($type)) {
            case 'md5':
                $sign = md5($prestr);
                break;
        }
        return $sign;
    }

    /**
     * 实现多种字符编码方式
     * @param $input 需要编码的字符串
     * @param $_output_charset 输出的编码格式
     * @param $_input_charset 输入的编码格式
     * @return string 编码后的字符串
     */
    public static function charsetEncode($input, $_output_charset, $_input_charset) {
        if ($_input_charset == $_output_charset || $input == null) {
            $output = $input;
        } elseif (function_exists("mb_convert_encoding")) {
            $output = mb_convert_encoding($input, $_output_charset, $_input_charset);
        } elseif (function_exists("iconv")) {
            $output = iconv($_input_charset, $_output_charset, $input);
        } else {
            exit("sorry, you have no libs support for charset change.");
        }

        return $output;
    }

    /**
     * 实现多种字符解码方式
     * @param $input 需要解码的字符串
     * @param $_input_charset 输出的解码格式
     * @param $_output_charset 输入的解码格式
     * @return string 解码后的字符串
     */
    public static function charsetDecode($input, $_input_charset, $_output_charset) {
        if ($_input_charset == $_output_charset || $input == null) {
            $output = $input;
        } elseif (function_exists("mb_convert_encoding")) {
            $output = mb_convert_encoding($input, $_output_charset, $_input_charset);
        } elseif (function_exists("iconv")) {
            $output = iconv($_input_charset, $_output_charset, $input);
        } else {
            exit("sorry, you have no libs support for charset changes.");
        }

        return $output;
    }

    /**
     * 验签
     * @param $para_temp 参数
     * @param $sign 签名
     * @param $key 密钥
     * @return bool
     */
    public static function verifySign($para_temp, $sign, $key) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = self::paraFilter($para_temp);
        //对待签名参数数组排序
        $para_sort = self::argSort($para_filter);
        //生成签名结果
        $mysign = self::buildMysign($para_sort, $key);
        if ($mysign == $sign) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 生成签名结果
     * @param $sort_para 要签名的数组
     * @param $key 密钥
     * @param bool $encode
     * @return string 签名结果字符串
     */
    public static function buildMysign($sort_para, $key, $encode = true) {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $encode ? self::createLinkEncode($sort_para) : self::createLinkstring($sort_para);
        //把拼接后的字符串再与安全校验码直接连接起来
        $prestr = $prestr . $key;
        $mysgin = self::signData($prestr);
        return $mysgin;
    }

    /**
     * 构造提交表单HTML数据
     * @param $para_temp 请求参数数组
     * @param $gateway 网关地址
     * @param $method 提交方式。两个值可选：post、get
     * @param $button_name 确认按钮显示文字
     * @return 提交表单HTML文本
     */
    public static function buildForm($para_temp, $gateway, $method, $button_name, $key) {
        //待请求参数数组
        $para = self::buildRequestPara($para_temp, $key);

        $sHtml = "<form id='allscoresubmit' name='allscoresubmit' action='" . $gateway . "' method='" . $method . "'>";

        foreach ($para as $key => $value) {
            $value = urlencode($value);
            $sHtml .= "<input type='hidden' name='{$key}' value='{$value}'/>";
        }
        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml . "<input type='submit' value='{$button_name}' style='display:none;'></form>";

        $sHtml = $sHtml . "<script>document.forms['allscoresubmit'].submit();</script>";
        return $sHtml;
    }

    /**
     * 生成要请求的参数数组
     * @param $para_temp 请求前的参数数组
     * @param $key 密钥
     * @return 要请求的参数数组
     */
    public static function buildRequestPara($para_temp, $key) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = self::paraFilter($para_temp);
        //对待签名参数数组排序
        $para_sort = self::argSort($para_filter);

        //生成签名结果
        $mysign = self::buildMysign($para_sort, trim($key));

        //签名结果与签名方式加入请求提交参数组中
        $para_sort['sign'] = $mysign;

        if (isset($para_temp['signType'])) {
            $para_sort['signType'] = $para_temp['signType'];
        }
        return $para_sort;
    }
}