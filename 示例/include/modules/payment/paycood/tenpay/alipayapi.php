<?php

$tenpay = bs_lib::get_configs_value('pay_tenpay','pay_tenpay_user');
$md5=md5($tenpay."&".$pay_amount."&P".$pay_id.'');
header("location:https://www.tenpay.com/v2/account/pay/paymore_cft.shtml?data=$tenpay%26".$pay_amount."%26P".$pay_id."&validate=$md5");

?>