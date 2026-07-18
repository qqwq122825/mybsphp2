<?php
	return array(
      //基础配置
      //label=名称
      //OpenFile=入口文件
      //dir目录名称
      //name=英文别名与目录名称一致
      //type=pay_config内-1
      //url=图片LOGO位置
      //info=注释说明
      'pay_config'=>array('label'=>'Wbox支付宝免签:','OpenFile'=>'wboxpay.php','dir'=>'wbox','name'=>'wbox','type'=>'-1','url'=>bs_lib::get_configs_value('sys','url').'statics/default/payment/imges/alipay.jpg','info'=>'需要开通到http://www.wbox.top 开通'),  //固定值
      //智能表单 第三方开发参考 字段名称格式:'pay_英文别名_set  'pay_英文别名_key
      'pay_wbox_set'=>array('label'=>'是否启用:','type'=>'radio','values'=>array('0'=>'开启','1'=>'关闭'),'info'=>'是否开启Wbox集成交易'),
      'pay_wbox_pid'=>array('label'=>'pid:','type'=>'text','values'=>'','size'=>'30','info'=>'Wbox商户PID'),
      'pay_wbox_key'=>array('label'=>'key:','type'=>'text','values'=>'','size'=>'30','info'=>'商户KEY wbox后台设置'),
      
      'pay_post_config'=>array('label'=>'保存修改:','type'=>'submit','values'=>'保存修改'),//记得按钮放最后
    );
?>