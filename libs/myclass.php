<?php
/**
 * shenzhentong.php
 * dev的基础类库
 * Autuor: Skiychan
 * Contact: developer@zzzzy.com & QQ:1005043848
 * Website: www.zzzzy.com & http://weibo.com/ckiy
 * Date: 2014-10-19
 */

class Myclass {

    /* curl配置: 取网页源码、模拟登陆、POST提交
     * @param $url: 如果非数组，则为http;如是数组，则为https
     * @param $header: 头文件
     * @param $post: post方式提交 array 或 abc=1&bcd=2 形式
	 * @param $cookies: 0默认无cookie,1为设置,2为获取
     */
	public function curls($urls, $header = FALSE, $post = FALSE, $cookies = 0) {
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

    /* 将网页转换成XML，再转换成DOM
     * @param $data 非数组=>源码,数组 array($url, 1)1为file_get_contents,2为curl
     */
    public function pageToDom($data, $encoded = "utf-8"){

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