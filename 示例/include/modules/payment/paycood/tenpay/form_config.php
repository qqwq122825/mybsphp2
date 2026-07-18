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
      'pay_config'=>array('label'=>'财付通免签约:','OpenFile'=>'alipayapi.php','dir'=>'tenpay','name'=>'tenpay','type'=>'-1','url'=>bs_lib::get_configs_value('sys','url').'statics/default/payment/imges/tenpay.jpg','info'=>'财付通接到帐,免接口 '),  //固定值
      //智能表单 第三方开发参考 字段名称格式:'pay_英文别名_set  'pay_英文别名_key
      'pay_tenpay_set'=>array('label'=>'是否启用:','type'=>'radio','values'=>array('0'=>'开启','1'=>'关闭'),'info'=>'是否开启财付通交易'),
      'pay_tenpay_key'=>array('label'=>'key:','type'=>'text','values'=>'','size'=>'30','info'=>'这里是自己定义KEY,用GET确认的  提交回调充值确认'),
      'pay_tenpay_user'=>array('label'=>'收款财付通账号:','type'=>'text','values'=>'','size'=>'30','info'=>'财付通收款账号,申请的账号'),
      'pay_post_config'=>array('label'=>'保存修改:','type'=>'submit','values'=>'保存修改'),//记得按钮放最后
    );
?>