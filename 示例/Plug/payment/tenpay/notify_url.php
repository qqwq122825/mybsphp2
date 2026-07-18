<?php

//URL:http://127.0.0.5/Plug/payment/tenpay/notify_url.php?payid=201509190909254488&md5=c5971dc6abfa8be999ddf76b50ac651c
//MD5=订单号+KEY
//NOTMODE 不需要载入控制器
const BSPHP_SET = 'NOTMODE';
require_once ('../../../LibBsphp/Global.Bsphp.Inc.php');
require_once (BSPHP_DIR_SYS . 'include/modules/payment/paycood/tenpay/notify_url.php');

?>