<?php

$tenpay = bs_lib::get_configs_value('pay_tenpay','pay_tenpay_user');



    /**
     * 模块
     */
   
     include_once ('wbox.class.php');

    $wboxpay = new wbox();
    
    #商户PID Wbox网站获取
     $wboxpay->pid = bs_lib::get_configs_value('pay_wbox_wx','pay_wbox_pid');

    #商户密钥 Wbox网站获取
     $wboxpay->key = bs_lib::get_configs_value('pay_wbox_wx','pay_wbox_key');

    #支付金额,分为单位
     $wboxpay->total_fee = $pay_amount*100;

    #支付结果跳转地址
     $wboxpay->run_url = bs_lib::get_configs_value('sys','url').'user/index.php?m=user&c=index&a=space';

    #支付结果通知地址
     $wboxpay->notify_url = bs_lib::get_configs_value('sys','url').'Plug/payment/wbox_wx/notify_url.php';

    #商品标题
     $wboxpay->litle = $pay_beizhu;

    #商户订单编号,回调返回
     $wboxpay->orderid = $pay_id;

    #附加数据1 回调时候传回
     $wboxpay->body1 = '';

    #附加数据2 回调时候传回
     $wboxpay->body2 = '';


    #支付方式, 1=支付宝 2=微信 3=QQ
     $wboxpay->paylist = 2;
    
     #返回 JSON/JSON格式,XML/XML格式
     $wboxpay->output = 'JSON';

     
     #发送请求
     $data = $wboxpay->GetWboxPayInfo();
     //echo $data;
     $data_json = json_decode($data);
     
     
     if($data_json->code==9000){
     
       echo '<meta http-equiv="refresh" content="0;url=http://buy.wbox.top/pay/index.html?order='.$data_json->payorderid.'">';

      }else{
        echo $data_json->code. $data_json->msg;
      }



//$md5=md5($tenpay."&".$pay_amount."&P".$pay_id.'');
//header("location:https://www.tenpay.com/v2/account/pay/paymore_cft.shtml?data=$tenpay%26".$pay_amount."%26P".$pay_id."&validate=$md5");

?>