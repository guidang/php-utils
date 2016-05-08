<?php
/**
 * 七牛云离线下载
 * qiniu.php
 * @author  : Skiychan <dev@skiy.net>
 * @link    : https://www.zzzzy.com
 * @created : 5/8/16
 * @modified:
 * @version : 0.0.1
 * @doc     : https://www.zzzzy.com/201605084032.html
 * 用了点 PHP7的新特征：?? 和 PHP5高版本的 [] 代替 array()，可自行修改
 */

$thisPage = basename($_SERVER['PHP_SELF']);

if (empty($_POST)) {
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>七牛云离线下载</title>
        <style>
            input {
                display: block;
            }
            input[type=text] {
                width: 360px;
            }
        </style>
    </head>
    <body>
    <form action="<?php echo $thisPage; ?>" method="post">
        <input type="text" name="url" id="url" placeholder="请输入链接" required>
        <input type="text" name="name" id="name" placeholder="请输入保存的文件名">
        <input type="submit" name="submit" value="提交">
    </form>
    </body>
    </html>

    <?php
    die;
}

$url = $_POST['url'] ?? "";  //PHP7
//$url = isset($_POST['url']) ? $_POST['url'] : "";  //PHP5

if (empty($url)) {
    header("Location: ".$thisPage);
}

function urlsafe_base64_encode($data) {
    $find = array('+', '/');
    $replace = array('-', '_');
    return str_replace($find, $replace, base64_encode($data));
}

function hmac_sha1($str, $key) {
    return hash_hmac("sha1", $str, $key, true);
}

//七牛云配置，请到七牛云上面申请帐号并填写密钥和bucket
define("AccessKey", "AccessKey_12345689");
define("SecretKey", "SecretKey_98764231");
define("BUCKET", "Your bucket");
define("HOST", "http://iovip.qbox.me");
define("DOMAIN", "Your domain");

$uploadFormat = "%s/fetch/%s/to/%s";
$encodeURI = urlsafe_base64_encode($url);

if (! empty($_POST['name'])) {
    $entry = BUCKET.':'.$_POST['name'];
} else {
    $entry = BUCKET.':'.time()."_".explode('?', basename($url))[0];
}

$encodedEntryURI = urlsafe_base64_encode($entry);

$uploadURL = sprintf($uploadFormat, HOST, $encodeURI, $encodedEntryURI);

$parse = parse_url($uploadURL);
$path = $parse['path'];
$signingStr = isset($parse['query']) ? $path."?".$parse['query']."\n" : $path."\n";

$sign = hmac_sha1($signingStr, SecretKey);
$encodedSign = urlsafe_base64_encode($sign);

$accessToken = AccessKey.":{$encodedSign}";

$headers = [
    "Authorization: QBox ".$accessToken,
    "Content-Type: application/x-www-form-urlencoded",
];

$ch = curl_init();
// 设置URL和相应的选项
curl_setopt($ch, CURLOPT_URL, $uploadURL);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$data = curl_exec($ch);
curl_close($ch);

$data_obj = json_decode($data);

if (isset($data_obj->error)) {
    echo "下载失败，<a href='javascript: history.back(-1)'>返回上一页</a>";
} else {
    $data_obj->key = DOMAIN.$data_obj->key;
    echo "下载成功，您的下载地址为：<a href='{$data_obj->key}' target='_blank'>{$data_obj->key}</a><br /><a href='javascript: history.back(-1)'>返回上一页</a>";
    //echo json_encode($data_obj);
}
