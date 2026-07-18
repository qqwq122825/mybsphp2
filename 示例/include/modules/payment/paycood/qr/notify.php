<?php
header('content-type:text/html;charset=utf-8');
$userid = bs_lib::get_configs_value('pay_qr','pay_qr_id');
$userkey = bs_lib::get_configs_value('pay_qr','pay_qr_key');

$status=$_POST['status'];
$customerid=$_POST['customerid'];
$sdorderno=$_POST['sdorderno'];
$total_fee=$_POST['total_fee'];
$paytype=$_POST['paytype'];
$sdpayno=$_POST['sdpayno'];
$remark=$_POST['remark'];
$sign=$_POST['sign'];

$mysign=md5('customerid='.$customerid.'&status='.$status.'&sdpayno='.$sdpayno.'&sdorderno='.$sdorderno.'&total_fee='.$total_fee.'&paytype='.$paytype.'&'.$userkey);

if($sign==$mysign){
    if($status=='1'){
        
        $pay_log=$user_class->PayInfp($sdorderno);
        echo 'success';
    } else {
        echo 'fail';
    }
} else {
    echo 'signerr';
}
?>
