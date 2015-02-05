<?php
/**
 * shenzhentong.php
 * 深圳通的API
 * @autuor: Skiychan
 * @contact: developer@zzzzy.com & QQ:1005043848
 * @website: www.zzzzy.com & http://weibo.com/ckiy
 * @date: 2014-10-19
 * @readme https://github.com/skiy/dev/blob/master/docs/shenzhentong.md
 */

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
        "card_balance" =>  getTextContent($tr, 3),
        "balance_time" => $expires[1],
        "card_validity" =>  getTextContent($tr, 5),
        "current_time" => date("Y-m-d H:i:s", time()));

    header('Content-Type: text/json; charset=utf-8');
    echo json_encode($results);

?>
 
