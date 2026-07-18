<?php
header('content-type:text/html;charset=utf-8');
$userid = bs_lib::get_configs_value('pay_qr','pay_qr_id');
$userkey = bs_lib::get_configs_value('pay_qr','pay_qr_key');

$status=$_GET['status'];
$customerid=$_GET['customerid'];
$sdorderno=$_GET['sdorderno'];
$total_fee=$_GET['total_fee'];
$paytype=$_GET['paytype'];
$sdpayno=$_GET['sdpayno'];
$remark=$_GET['remark'];
$sign=$_GET['sign'];

$mysign=md5('customerid='.$customerid.'&status='.$status.'&sdpayno='.$sdpayno.'&sdorderno='.$sdorderno.'&total_fee='.$total_fee.'&paytype='.$paytype.'&'.$userkey);
$user_class=bs_lib::load_modules_class('user','user');
if($sign==$mysign){
    if($status=='1'){
        
        $pay_log=$user_class->PayInfp($sdorderno);
        if($pay_log){
           echo '支付成功！';  
        }else{
           echo '服务订单修改失败,请联系管理.订单号：'.$v_oid;    
        }
        
        
    } else {
        echo '未付款';
    }
} else {
    echo '验证签名失败';
}
?>
