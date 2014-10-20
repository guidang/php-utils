<?php
/**
 * tongxingzhengyuyue.php
 * 
 * @author: Skiychan
 * @contact: developer@zzzzy.com & QQ:1005043848
 * @website: www.oupag.com & http://weibo.com/ckiy
 * @date:    2014-10-20
 * @readme:
 */

/**
链接：http://www.sz3e.com:8000/wsyysq/wsyysq/lookfor.jsp

### 接口信息
URL：http://www.sz3e.com:8000/wsyysq/tbCjsqxxTempAjax/search.do
     http://www.sz3e.com:8000/wsyysq/tbCjsqxxTempAjax/search.do?ywbh=030301009656605
POST方法：ywbh:44072219900418212X

### 返回字段 json格式
返回值字段 | 字段类型 | 字段说明
----|------|----
currentDate | string | 当前时间
clzt        | string | 审核状态 (-1 审核失败, 1 为审核通过)
gzzdsbhy    | string | 填表时间+身份证号
qwd         | string | 前往地
sldw        | string | 受理单位
wsyyrq      | string | 预约日期
wsyysj      | string | 预约时间段
ywbh        | string | 受理编号
sqlb        | string | 申请类别 (102 来往港澳, 104 往来台湾, 101 普通护照)
usertype    | string | 用户类型 (15 中华人民共和国出入境通行证, 16 台湾居民来往大陆通行证、签证, 17 外国人签证证件申请)

由于直接使用官方回调的```json```数据，不太清楚其详细信息。

 */
require_once "../libs/myclass.php";

date_default_timezone_set("Asia/Shanghai");

$number = isset($_GET["number"]) ? $_GET["number"] : 0;
$post_number = "ywbh={$number}";
$data = new Myclass();

//curl 的POST方式
//$page = $data->curls("http://www.sz3e.com:8000/wsyysq/tbCjsqxxTempAjax/search.do", false, $post_number);
//直接GET方式
$page = $data->curls("http://www.sz3e.com:8000/wsyysq/tbCjsqxxTempAjax/search.do?ywbh={$number}");

echo $page;

?>
 