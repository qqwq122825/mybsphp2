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
        <td nowrap="nowrap">一级代理：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a>(后台添加)</td>
        <td>后台添加的是一级代理</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">冻结充值卡功能：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
        <input type="radio" name="car_sdate_1" value="1" <?php echo_checkbox_checked('agents','car_sdate_1',1); ?>>

开启</label>
<label>
<input type="radio" name="car_sdate_1" value="0"<?php echo_checkbox_checked('agents','car_sdate_1',0); ?>>

关闭</label></td>
        <td >开启,显示冻结按钮</td>
        <td >bs_lib::get_configs_value('agents','car_sdate_1')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">解冻充值卡功能：<a id="faq"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="car_sdate_no_1" value="1"<?php echo_checkbox_checked('agents','car_sdate_no_1',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="car_sdate_no_1" value="0"<?php echo_checkbox_checked('agents','car_sdate_no_1',0); ?>>
            关闭</label></td>
        <td >开启显示解除冻结按钮</td>
        <td >bs_lib::get_configs_value('agents','car_sdate_no_1')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">冻结删除充值卡关联登录账号：<a id="faq15"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="car_on_1" value="1"<?php echo_checkbox_checked('agents','car_on_1',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="car_on_1" value="0"<?php echo_checkbox_checked('agents','car_on_1',0); ?>>
        关闭</label></td>
        <td >开启后,充值卡冻结/解冻结充值卡/账号同步</td>
        <td >bs_lib::get_configs_value('agents','car_on_1')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">激活几秒不能冻结：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="car_on_time_1" type="text"  class="layui-form-label"  id="car_on_time_1" value="<?php echo bs_lib::get_configs_value('agents','car_on_time_1') ?>" size="40"/></td>
        <td >激活几个小时不能冻结 0不能冻结,可以冻结填写9999999..</td>
        <td >bs_lib::get_configs_value('agents','car_on_time_1')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">未使用的卡不能冻结</td>
        <td><label>
          <input type="radio" name="car_off_1" value="1"<?php echo_checkbox_checked('agents','car_on_1',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="car_off_1" value="0"<?php echo_checkbox_checked('agents','car_on_1',0); ?>>
        关闭</label></td>
        <td >还有使用卡不能冻结</td>
        <td >bs_lib::get_configs_value('agents','car_off_1')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">删除充值卡：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="car_delete_1" value="1"<?php echo_checkbox_checked('agents','car_delete_1',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="car_delete_1" value="0"<?php echo_checkbox_checked('agents','car_delete_1',0); ?>>
        关闭</label></td>
        <td >开启显示删除充值卡功能</td>
        <td >bs_lib::get_configs_value('agents','car_delete_1')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">激活充值卡N秒不可删除：<a id="faq2"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="car_delete_time_1" type="text"  class="layui-form-label"  id="car_delete_time_1" value="<?php echo bs_lib::get_configs_value('agents','car_delete_time_1') ?>" size="40"/></td>
        <td >激活码激活多久不能删除不能冻结0,不限制填9999999..</td>
        <td >bs_lib::get_configs_value('agents','car_delete_time_1')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">显示余额充值：<a id="faq19"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="pay_chong_1" value="1"<?php echo_checkbox_checked('agents','pay_chong_1',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="pay_chong_1" value="0"<?php echo_checkbox_checked('agents','pay_chong_1',0); ?>>
        关闭</label></td>
        <td >开启显示删除充值卡功能</td>
        <td >bs_lib::get_configs_value('agents','pay_chong_1')</td>
      </tr>
      
      
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">二级代理：<a id="faq8"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a>(代理平台添加)</td>
        <td>代理平台添加可发展下级二级代理</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">冻结充值卡功能：<a id="faq7"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="car_sdate_2" value="1"<?php echo_checkbox_checked('agents','car_sdate_2',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="car_sdate_2" value="0"<?php echo_checkbox_checked('agents','car_sdate_2',0); ?>>
            关闭</label></td>
        <td >开启,显示冻结按钮</td>
        <td >bs_lib::get_configs_value('agents','car_sdate_2')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">解冻充值卡功能：<a id="faq6"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="car_sdate_no_2" value="1"<?php echo_checkbox_checked('agents','car_sdate_no_2',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="car_sdate_no_2" value="0"<?php echo_checkbox_checked('agents','car_sdate_no_2',0); ?>>
            关闭</label></td>
        <td >开启显示解除冻结按钮</td>
        <td >bs_lib::get_configs_value('agents','car_sdate_no_2')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">冻结删除充值卡关联登录账号：<a id="faq16"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="car_on_2" value="1"<?php echo_checkbox_checked('agents','car_on_2',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="car_on_2" value="0"<?php echo_checkbox_checked('agents','car_on_2',0); ?>>
            关闭</label></td>
        <td >开启后,充值卡冻结/解冻结充值卡/账号同步</td>
        <td >bs_lib::get_configs_value('agents','car_on_2')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">激活几秒不能冻结：<a id="faq5"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="car_on_time_2" type="text"  class="layui-form-label"  id="govicp4" value="<?php echo bs_lib::get_configs_value('agents','car_on_time_2') ?>" size="40"/></td>
        <td >激活几N秒不能冻结 0不能冻结,可以冻结填写9999999..</td>
        <td >bs_lib::get_configs_value('agents','car_on_time_2')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">未使用的卡不能冻结</td>
        <td><label>
          <input type="radio" name="car_off_2" value="1"<?php echo_checkbox_checked('agents','car_on_1',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="car_off_2" value="0"<?php echo_checkbox_checked('agents','car_on_1',0); ?>>
            关闭</label></td>
        <td >还有使用卡不能冻结</td>
        <td >bs_lib::get_configs_value('agents','car_off_2')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">删除充值卡：<a id="faq4"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="car_delete_2" value="1"<?php echo_checkbox_checked('agents','car_delete_2',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="car_delete_2" value="0"<?php echo_checkbox_checked('agents','car_delete_2',0); ?>>
            关闭</label></td>
        <td >开启显示删除充值卡功能</td>
        <td >bs_lib::get_configs_value('agents','car_delete_2')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">激活充值卡N小时不可删除：<a id="faq3"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="car_delete_time_2" type="text"  class="layui-form-label"  id="govicp3" value="<?php echo bs_lib::get_configs_value('agents','car_delete_time_2') ?>" size="40"/></td>
        <td >代理前端显示充值接口</td>
        <td >bs_lib::get_configs_value('agents','car_delete_time_2')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">显示余额充值：<a id="faq18"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="pay_chong_2" value="1"<?php echo_checkbox_checked('agents','pay_chong_2',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="pay_chong_2" value="0"<?php echo_checkbox_checked('agents','pay_chong_2',0); ?>>
            关闭</label></td>
        <td >开启显示删除充值卡功能</td>
        <td >bs_lib::get_configs_value('agents','pay_chong_2')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1208')" >
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">三级代理：<a id="faq14"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a>(代理平台添加)</td>
        <td>代理平台添加不可发展下级三级代理</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">冻结充值卡功能：<a id="faq13"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="car_sdate_3" value="1"<?php echo_checkbox_checked('agents','car_sdate_3',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="car_sdate_3" value="0"<?php echo_checkbox_checked('agents','car_sdate_3',0); ?>>
        关闭</label></td>
        <td >开启,显示冻结按钮</td>
        <td >bs_lib::get_configs_value('agents','car_sdate_3')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">解冻充值卡功能：<a id="faq12"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="car_sdate_no_3" value="1"<?php echo_checkbox_checked('agents','car_sdate_no_3',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="car_sdate_no_3" value="0"<?php echo_checkbox_checked('agents','car_sdate_no_3',0); ?>>
        关闭</label></td>
        <td >开启显示解除冻结按钮</td>
        <td >bs_lib::get_configs_value('agents','car_sdate_no_3')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">冻结删除充值卡关联登录账号：<a id="faq17"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="car_on_3" value="1"<?php echo_checkbox_checked('agents','car_on_3',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="car_on_3" value="0"<?php echo_checkbox_checked('agents','car_on_3',0); ?>>
        关闭</label></td>
        <td >开启后,充值卡冻结/解冻结充值卡/账号同步</td>
        <td >bs_lib::get_configs_value('agents','car_on_3')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">激活几秒不能冻结：<a id="faq11"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="car_on_time_3" type="text"  class="layui-form-label"  id="govicp6" value="<?php echo bs_lib::get_configs_value('agents','car_on_time_3') ?>" size="40"/></td>
        <td >激活N秒不能冻结 0不能冻结,可以冻结填写9999999..</td>
        <td >bs_lib::get_configs_value('agents','car_on_time_3')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">未使用的卡不能冻结</td>
        <td><label>
          <input type="radio" name="car_off_3" value="1"<?php echo_checkbox_checked('agents','car_on_1',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="car_off_3" value="0"<?php echo_checkbox_checked('agents','car_on_1',0); ?>>
            关闭</label></td>
        <td >还有使用卡不能冻结</td>
        <td >bs_lib::get_configs_value('agents','car_off_1')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">删除充值卡：<a id="faq10"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="car_delete_3" value="1"<?php echo_checkbox_checked('agents','car_delete_3',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="car_delete_3" value="0"<?php echo_checkbox_checked('agents','car_delete_3',0); ?>>
        关闭</label></td>
        <td >开启显示删除充值卡功能</td>
        <td >bs_lib::get_configs_value('agents','car_delete_3')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">激活充值卡N秒不可删除：<a id="faq9"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="car_delete_time_3" type="text"  class="layui-form-label"  id="govicp5" value="<?php echo bs_lib::get_configs_value('agents','car_delete_time_3') ?>" size="40"/></td>
        <td >激活码激活多久不能删除离开天下0,不限制填9999999..</td>
        <td >bs_lib::get_configs_value('agents','car_delete_time_3')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">显示余额充值：<a id="faq20"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="pay_chong_3" value="1"<?php echo_checkbox_checked('agents','pay_chong_3',1); ?>>
          开启</label>
          <label>
            <input type="radio" name="pay_chong_3" value="0"<?php echo_checkbox_checked('agents','pay_chong_3',0); ?>>
            关闭</label></td>
        <td >开启显示删除充值卡功能</td>
        <td >bs_lib::get_configs_value('agents','pay_chong_3')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1208')" >
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
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
select_set('stop',<?php echo (int)bs_lib::get_configs_value('agents','stop') ?>);
select_set('safe_sql',<?php echo (int)bs_lib::get_configs_value('agents','safe_sql') ?>);
</script>
<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a> Bsphp.com <br>
  All Rights Reserved </div>
</body>
</html>
