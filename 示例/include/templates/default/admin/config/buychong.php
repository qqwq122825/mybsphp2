<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name");  ?>- by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="../statics/default/admin/skin/style/base.css">
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/table.js"></script>


</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable">
  <div id="texttale"  class="texttale">系统信息设置</DIV>
   [<a href="javascript:window.location.reload();">+</a>]</div>
  
<!--  快捷  -->
<div id="Shortcut">
  <ul>
    <li><a href="index.php?m=admin&c=config&a=sys">系统信息设置</a></li>
    <li><a href="index.php?m=admin&c=config&a=session">session设置</a></li>
    <li><a href="index.php?m=admin&c=config&a=re_user">用户注册设置</a></li>
    <li><a href="index.php?m=admin&c=config&a=template">模板/语言设置</a></li>
    <li><a href="index.php?m=admin&c=config&a=code">验证码设置</a></li>
	<li><a href="index.php?m=admin&c=config&a=mail">邮件设置</a></li>
	<li><a href="index.php?m=payment&c=config&a=payl_list">在线支付设置</a></li>
	<li><a href="index.php?m=admin&c=config&a=extension">邀请推广设置</a></li>
    <li><a href="index.php?m=admin&c=config&a=agent">代理商设置</a></li>
    <li><a href="index.php?m=admin&c=config&a=buychong">前端续费设置</a></li>
  </ul>
</div>
<!--  内容列表   -->
<form action=""  name="form"  method="post"    onClick="return allcheckbox();"  >
  <table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">
    <thead>
      <tr>
        <th width="250" nowrap="nowrap" >配置名称</th>
        <th width="350" >表单填写</th>
        <th >填写提示</th>
        <th >php开发调用方法</th>
      </tr>
    </thead>
    <tbody>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">默认显示软件：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="buy_app_name" type="text"  class="layui-form-label"  id="car_on_time_2" value="<?php echo bs_lib::get_configs_value('buychong','buy_app_name') ?>" size="40"/></td>
        <td >填写软件代号,空表示全部,号隔开</td>
        <td >bs_lib::get_configs_value('buychong','buy_app_name')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">默认显示软件分类：<a id="faq"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="buy_app_lei" type="text"  class="layui-form-label"  id="car_on_time_3" value="<?php echo bs_lib::get_configs_value('buychong','buy_app_lei') ?>" size="40"/></td>
        <td >用户选择充值冲分类套餐</td>
        <td >bs_lib::get_configs_value('buychong','buy_app_lei')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">公告内容：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="buy_gg" type="text"  class="layui-form-label"  id="buy_gg" value="<?php echo bs_lib::get_configs_value('buychong','buy_gg') ?>" size="40"/></td>
        <td >在页面上显示的商户公告内容</td>
        <td >bs_lib::get_configs_value('buychong','buy_gg')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">充值框标题</td>
        <td><input name="buy_bz_lv" type="text"  class="layui-form-label"  id="buy_bz_lv" value="<?php echo bs_lib::get_configs_value('buychong','buy_bz_lv') ?>" size="40"/></td>
        <td >充值框名称标题</td>
        <td >bs_lib::get_configs_value('buychong','buy_bz_lv')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">充值框提示<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none"></a></td>
        <td><input name="buy_bz_test" type="text"  class="layui-form-label"  id="car_on_time_5" value="<?php echo bs_lib::get_configs_value('buychong','buy_bz_test') ?>" size="40"/></td>
        <td >在输入账号框提示</td>
        <td >bs_lib::get_configs_value('buychong','buy_bz_test')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">联系客服QQ：<a id="faq2"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="buy_qq" type="text"  class="layui-form-label"  id="buy_qq" value="<?php echo bs_lib::get_configs_value('buychong','buy_qq') ?>" size="40"/></td>
        <td >激活码激活多久不能删除不能冻结0,不限制填9999999..</td>
        <td >bs_lib::get_configs_value('buychong','buy_qq')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">底部版权说明：<a id="faq16"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="buy_bsphp" type="text"  class="layui-form-label"  id="car_delete_time_3" value="<?php echo bs_lib::get_configs_value('buychong','buy_bsphp') ?>" size="40"/></td>
        <td >底部说明,免费用户请保留BSPHP标识.</td>
        <td >bs_lib::get_configs_value('buychong','buy_bsphp')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">购卡/充值模式：<a id="faq12"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="buy_mode" value="1"<?php echo_checkbox_checked('buychong','buy_mode',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="buy_mode" value="0"<?php echo_checkbox_checked('buychong','buy_mode',0); ?>>
        关闭</label></td>
        <td >开启表示账号充值/不然就是购买充值卡</td>
        <td >bs_lib::get_configs_value('buychong','buy_mode')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">企业支付开启：<a id="faq9"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="z_zfb_off" value="1"<?php echo_checkbox_checked('buychong','z_zfb_off',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="z_zfb_off" value="0"<?php echo_checkbox_checked('buychong','z_zfb_off',0); ?>>
            关闭</label></td>
        <td >是否开启支付宝支付</td>
        <td >bs_lib::get_configs_value('buychong','z_zfb_off')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">支付宝PID：<a id="faq4"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="z_zfb_pid" type="text"  class="layui-form-label"  id="z_zfb_pid" value="<?php echo bs_lib::get_configs_value('buychong','z_zfb_pid') ?>" size="40"/></td>
        <td >是否开启支付宝支付</td>
        <td ><p>bs_lib::get_configs_value('buychong','z_zfb_pid')</p></td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">支付宝KEY：<a id="faq10"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="z_zfb_key" type="text"  class="layui-form-label"  id="z_zfb_key" value="<?php echo bs_lib::get_configs_value('buychong','z_zfb_key') ?>" size="40"/></td>
        <td >是否开启支付宝支付</td>
        <td >bs_lib::get_configs_value('buychong','z_zfb_key')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">支付宝收款账户：<a id="faq11"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="z_zfb_user" type="text"  class="layui-form-label"  id="car_delete_time_4" value="<?php echo bs_lib::get_configs_value('buychong','z_zfb_user') ?>" size="40"/></td>
        <td >是否开启支付宝支付</td>
        <td >bs_lib::get_configs_value('buychong','z_zfb_user')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">Wbox接口支付宝显示：<a id="faq7"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="wbox_zfb" value="1"<?php echo_checkbox_checked('buychong','wbox_zfb',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="wbox_zfb" value="0"<?php echo_checkbox_checked('buychong','wbox_zfb',0); ?>>
        关闭</label></td>
        <td >是否开启支付宝支付</td>
        <td >bs_lib::get_configs_value('buychong','wbox_zfb')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">Wbox接口QQ显示：<a id="faq6"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="wbox_qq" value="1"<?php echo_checkbox_checked('buychong','wbox_qq',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="wbox_qq" value="0"<?php echo_checkbox_checked('buychong','wbox_qq',0); ?>>
        关闭</label></td>
        <td >是否开启QQ支付</td>
        <td >bs_lib::get_configs_value('buychong','wbox_qq')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">Wbox接口微信显示：<a id="faq8"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="wbox_wx" value="1"<?php echo_checkbox_checked('buychong','wbox_wx',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="wbox_wx" value="0"<?php echo_checkbox_checked('buychong','wbox_wx',0); ?>>
        关闭</label></td>
        <td >是否开启微信支付</td>
        <td >bs_lib::get_configs_value('buychong','wbox_wx')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">Wbox商户PID：<a id="faq5"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="wbox_pid" type="text"  class="layui-form-label"  id="govicp4" value="<?php echo bs_lib::get_configs_value('buychong','wbox_pid') ?>" size="40"/></td>
        <td >Wbox注册PID</td>
        <td >bs_lib::get_configs_value('buychong','wbox_pid')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">Wbox商户Key:</td>
        <td><input name="wbox_key" type="text"  class="layui-form-label"  id="wbox_key" value="<?php echo bs_lib::get_configs_value('buychong','wbox_key') ?>" size="40"/></td>
        <td >Wbox商户设置KEY</td>
        <td >bs_lib::get_configs_value('buychong','wbox_key')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">交易记录:</td>
        <td><a href="index.php?m=payment&c=admin_payinfo&a=table">点击查看交易记录</a></td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
    
      <tr>
        <td nowrap="nowrap"></td>
        <td><input id="admin" type="submit" name="appenconfig"  class="layui-btn layui-btn-normal"  value="保存修改"></td>
        <td></td>
        <td></td>
      </tr>
  </tbody>
  </table>
</form>
<script language="javascript">
select_set('stop',<?php echo (int)bs_lib::get_configs_value('buychong','stop') ?>);
select_set('safe_sql',<?php echo (int)bs_lib::get_configs_value('buychong','safe_sql') ?>);
</script>
<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a> Bsphp.com <br>
  All Rights Reserved </div>
</body>
</html>
