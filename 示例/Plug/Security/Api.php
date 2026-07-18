<?php

const TAB = 'bsphp_';
const OUT = 'GBK';


//NOTMODE 不需要载入控制器
const BSPHP_SET = 'NOTMODE';
require_once ('../../LibBsphp/Global.Bsphp.Inc.php');
$lng = require_once ('../../include/langs/zh-cn/user/user_str_log.php');

include ('des.php');
$db = bs_lib::load_libbsphp_class('mysql', 'mysql');
bs_lib::load_modules_common('applib', 'makecard');
bs_lib::load_modules_common('applib', 'appen_inc');
bs_lib::load_modules_common('applib', 'appen_appuser');


$api_data = set_post('api');
//$G_daihao = set_post('daihao');#软件代号
//$G_md5 = set_post('md5');#软件URLmd5
//$G_ukey = set_post('ukey');#通信密钥
//$G_skey = set_post('skey');#用户机器码
//$G_card = set_post('card');#激活码
//$G_pwd = set_post('pwd');#密码
//$G_date = set_post('date');#数据包时间
//$G_bsphp = set_post('bsphp');#固定类型
//$G_token = set_post('token');#返回加密
$G_password_enpwd = $_SERVER['HTTP_GZID']; #数据包解密密码


$api_data = bsphp_mdecrypt($api_data, $G_password_enpwd);

if (strpos($api_data, "&bs=1") < 0) {

    print_s('数据异常,Code101!', 1);
}


parse_str($api_data, $G_Urlarray);
//print_r($G_Urlarray);


#读取软件配置
$sql = "SELECT * FROM  `bs_php_appinfo` WHERE  `app_daihao` = '{$G_Urlarray['daihao']}' LIMIT 1";

$app_array = $db->Bsphp_my_array($sql);
if (!$app_array) {

    print_s('数据异常,Code102!');
}


if ($app_array['app_MoShi'] !== 'CardTerm') {
    print_s('数据异常,Code103!');
}

if ($G_Urlarray['card'] == '') {
    print_s('请输入激活码!');
}

$log = CarLogin($G_Urlarray['card'], '', $G_Urlarray['skey'], $G_Urlarray['daihao']);

if ($log == 1069) {
    $log = CarLogin($G_Urlarray['card'], '', $G_Urlarray['skey'], $G_Urlarray['daihao']);
}


if ($log == 1081) {

    $carinfo = GetCardInfo($G_Urlarray['card'], '', $G_Urlarray['daihao']);


    $attay_json = array(
        'v' => $G_Urlarray['v'],
        'name' => $G_Urlarray['card'],
        'skey' => $G_Urlarray['skey'],
        'daihao' => $G_Urlarray['daihao'],
        'date' => date(date('Y年m月d日H时i分s秒', $carinfo['L_vip_unix'])),
        );


    $attay_json = json_encode($attay_json);


    $json = request_post('http://127.0.0.1:85', $attay_json);
    $json = json_decode($json);
    if ($json->sdate == 1) {
        print_s("<mss>1433</mss><da>{$json->data}</da>");
    }else{
        print_s('授权服务器异常');
    }

} else {
    print_s( $lng[$log]);
}

function print_s($test, $type = 0)
{
    global $G_Urlarray;

    if (OUT == 'GBK') {

        $test = iconv("UTF-8", "GBK", $test);

    }


    if ($type == 1) {
        echo "<type>$type</type><data>$test</data>";
    } else {
        $des = bsphp_mencrypt($test, $G_Urlarray['pwd']);
        echo "<type>$type</type><data>$des</data>";
    }
    exit;
}
function request_post($url = '', $params)
{ //url为必传  如果该地址不需要参数就不传


    $ch = curl_init(); //初始化curl
    curl_setopt($ch, CURLOPT_URL, $url); //抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0); //设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $data = curl_exec($ch); //运行curl
    curl_close($ch);
    return $data;
}
?>