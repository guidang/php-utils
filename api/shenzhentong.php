<?php
/**
 * 深圳通的API
 * shenzhentong.php
 * @author  : Skiychan <dev@skiy.net>
 * @link    : https://www.zzzzy.com
 * @created : 10/19/14
 * @modified:
 * @version : 0.0.1
 * @doc     : https://www.zzzzy.com/201410193345.html

/**
链接：http://query.shenzhentong.com:8080/sztnet/qrycard.jsp

接口信息
URL：http://query.shenzhentong.com:8080/sztnet/qryCard.do
     http://query.shenzhentong.com:8080/sztnet/qryCard.do?cardno=328375558
POST方法：cardno:328375558

### 返回字段 json格式
返回值字段 | 字段类型 | 字段说明
----|------|----
card_number   | int     | 卡号
card_balance  | string  | 卡内余额
balance_time  | string  | 余额截止时间
card_validity | string  | 卡有效期
current_time  | string  | 查询时间

 */

    require_once "../libs/myclass.php";

    date_default_timezone_set("Asia/Shanghai");

    $cardno = isset($_GET["cardno"]) ? $_GET["cardno"] : die("Please enter cardno!");
    $post_cardno = "cardno={$cardno}";
    $data = new Myclass();

    //curl 的POST方式
    //$page = $data->curls("http://query.shenzhentong.com:8080/sztnet/qryCard.do", false, $post_cardno);
    //直接GET方式
    $page = $data->curls("http://query.shenzhentong.com:8080/sztnet/qryCard.do?cardno={$cardno}");
    $page = $data->pageToDom($page, "GBK");

    $tr = $page->query("//table[@class='tableact']/tr/td");

    function getTextContent($m_query, $m_id) {
        $myTXT = str_replace("：", "", $m_query->item($m_id)->textContent);
        return $myTXT;
    }

    //截止时间内余额
    preg_match("/截止到([^\)]*)/", getTextContent($tr, 2), $expires);

    $results = array(
        "card_number" =>  (int) getTextContent($tr, 1),
        "card_balance" =>  str_replace("元","",getTextContent($tr, 3)),
        "balance_time" => strtotime($expires[1]),
        "card_validity" =>  strtotime(getTextContent($tr, 5)),
        "current_time" => time()
        );

    header('Content-Type: text/json; charset=utf-8');
    
    if (empty($results['card_balance'])) {
		echo json_encode(array("code" => 200, "msg" => "信息获取失败，请稍后重试"));
	    die();
    }
    echo json_encode($results);

?>
 
