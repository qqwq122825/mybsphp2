<?php



//MD5值=(订单号+自定KEY值))


//载入需要文件
$user_class = bs_lib::load_modules_class('user', 'user');


//获取充值参数
$pid = $_POST['pid'];
$paymoney = $_POST['paymoney'];
$odermoeny = $_POST['odermoeny'];
$orderid = $_POST['orderid'];
$m_oderid = $_POST['m_oderid'];
$odersdate = $_POST['odersdate'];
$body1 = $_POST['body1'];
$body2 = $_POST['body2'];
$downdate = $_POST['downdate'];
$okdate= $_POST['okdate'];
$sgin= $_POST['sgin'];
$pid=bs_lib::get_configs_value('pay_wbox_wx','pay_wbox_pid');
$data = "&pid=$pid&paymoney={$paymoney}&odermoeny={$odermoeny}&orderid={$orderid}&m_oderid={$m_oderid}&odersdate={$odersdate}&body1={$body1}&body2={$body2}&downdate=$downdate&okdate=$okdate&key=".bs_lib::get_configs_value('pay_wbox_wx','pay_wbox_key');
#计算MD5值
$md5=md5($data);
          


//MD5比对
if ($md5 == $sgin)
{


    $pay_log = $user_class->PayInfp($orderid,$paymoney);
    if ($pay_log)
    {
        echo 'success';
    } else
    {
        echo 'badno';
    }


} else
{

    echo 'sgin 验证失败！';
}

?>