<?php



//MD5值=(订单号+自定KEY值))


//载入需要文件
$user_class = bs_lib::load_modules_class('user', 'user');


//获取充值参数
$out_trade_no = $_GET['payid'];
$md5 = $_GET['md5'];

//验证KEY MD5
$odl = md5($out_trade_no . bs_lib::get_configs_value('pay_zhifubao', 'pay_zhifubao_key'));


//MD5比对
if ($md5 == $odl)
{


    $pay_log = $user_class->PayInfp($out_trade_no);
    if ($pay_log)
    {
        echo 'success';
    } else
    {
        echo '服务订单修改失败,请联系管理.订单号：' . $v_oid;
    }


} else
{

    echo 'key 验证失败！';
}

?>