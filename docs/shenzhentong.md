## 深圳通余额查询

链接：http://query.shenzhentong.com:8080/sztnet/qrycard.jsp

======
### 接口信息
URL：http://query.shenzhentong.com:8080/sztnet/qryCard.do

POST方法：cardno:328375550

====
### 返回字段 json格式
返回值字段 | 字段类型 | 字段说明
----|------|----
card_number   | int     | 卡号
card_balance  | string  | 卡内余额
balance_time  | string  | 余额截止时间
card_validity | string  | 卡有效期
current_time  | string  | 查询时间

示例：
```json
{
card_number: "10000",
card_balance: "13.45元",
balance_time: "2014-08-13 20:29:16",
card_validity: "2021-11-17",
current_time: "2014-10-19 21:39:31"
}
```

演示：

[http://api.oupag.com/dev/api/shenzhentong.php?cardno=328375558](http://api.oupag.com/dev/api/shenzhentong.php?cardno=328375558)