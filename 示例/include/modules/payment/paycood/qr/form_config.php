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
      'pay_config'=>array('label'=>'微信二维码支付:','OpenFile'=>'pay.php','dir'=>'qr','name'=>'qr','type'=>'-1','url'=>bs_lib::get_configs_value('sys','url').'statics/default/payment/imges/qr.png','info'=>''),  //固定值
      
      //智能表单 第三方开发参考 字段名称格式:'pay_英文别名_set  'pay_英文别名_key
      'pay_qr_set'=>array('label'=>'是否启用:','type'=>'radio','values'=>array('0'=>'开启','1'=>'关闭'),'info'=>'是否开启微信扫描支付'),
      
      'pay_qr_id'=>array('label'=>'商户ID:','type'=>'text','values'=>'','size'=>'30','info'=>'BSPHP合作商户平台提供的商户ID(联系群主开通)'),
      'pay_qr_key'=>array('label'=>'key:','type'=>'text','values'=>'','size'=>'30','info'=>'BSPHP合作商户提供的密钥key(联系群主开通)'),

      'pay_post_config'=>array('label'=>'保存修改:','type'=>'submit','values'=>'保存修改'),//记得按钮放最后
    );
?>