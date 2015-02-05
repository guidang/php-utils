## 深圳办理出境通行证预约查询

链接：http://www.sz3e.com:8000/wsyysq/wsyysq/lookfor.jsp

======
### 接口信息
URL：http://www.sz3e.com:8000/wsyysq/tbCjsqxxTempAjax/search.do

POST方法：ywbh:44072219900418212X

====
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

示例：
```json
{
currentDate: "2014-10-20 21:44:23",
tbCjsqxxs: [
    {
        clzt: "-1",
        gzzdsbhy: "20141017113900003644072219900418212X",
        qwd: "HKG",
        sldw: "深圳宝安分局",
        sqlb: "102",
        usertype: "1",
        wsyyrq: "2014-10-24",
        wsyysj: "09:00-10:00",
        ywbh: "030301009656122"
    }
],
tbGahqs: [ ],
tbTwjms: [ ],
tbWgrs: [ ]
}
```

[演示]:(http://api.oupag.com/dev/api/tongxingzhengyuyue.php?number=030301009656122)