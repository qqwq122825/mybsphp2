<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?>- by BSPHP</title>
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
      <tr onMouseOver="bsphpfaq(this, 'faq1201')" >
        <td nowrap="nowrap">系统名称：<a id="faq1201"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1201"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a> </td>
        <td><input name="name" type="text"  class="layui-form-label"  id="name" value="<?php echo bs_lib::get_configs_value('sys','name') ?>" size="30"/></td>
        <td>系统主显示名称</td>
        <td>bs_lib::get_configs_value('sys','name')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1204')" >
        <td nowrap="nowrap">系统地址：<a id="faq1204"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1202"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="url" type="text"  class="layui-form-label"  id="url" value="<?php echo bs_lib::get_configs_value('sys','url') ?>" size="30"/></td>
        <td >系统目录如果：http://www.bsphp.com/abc/ </td>
        <td >bs_lib::get_configs_value('sys','url')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1205')" >
        <td nowrap="nowrap">系统时间差：<a id="faq1205"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1203"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="time_add" type="text"  class="layui-form-label"  id="ini_date" value="<?php echo (int)bs_lib::get_configs_value('sys','time_add') ?>" size="15"/>
          <div class="layui-form-mid layui-word-aux"> 秒 <a href="index.php?m=admin&c=config&a=date"  > 差距计算器</a></div></td>
        <td >设置系统时间差距单位秒 ,如果服务器时间不对,可以在不此调整 </td>
        <td >bs_lib::get_configs_value('sys','time_add')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1205')" >
        <td nowrap="nowrap">时区设置：<a id="faq1205"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1203"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><select name="timezone"  class="layui-form-label"  id="timezone">
            <option value="PRC">中国</option>
          </select></td>
        <td >系统时区,当前默认是中国时区</td>
        <td >bs_lib::get_configs_value('sys','timezone')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1206')" >
        <td nowrap="nowrap"><span >系统密钥KEY：<a id="faq1206"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1204"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></span></td>
        <td><span >
          <input name="pass_key" type="text"  class="layui-form-label"  id="pass_key" value="<?php echo bs_lib::get_configs_value('sys','pass_key') ?>" />
          <div class="layui-form-mid layui-word-aux"> * </div></span></td>
        <td><span >本处密钥你保存好,更改导致系统帐号无法正常（请勿更改保存好以免丢失）</span></td>
        <td>bs_lib::get_configs_value('sys','pass_key')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td nowrap="nowrap">COOKIES加密：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="cook_key" type="text"  class="layui-form-label"  id="cookie_key" value="<?php echo bs_lib::get_configs_value('sys','cook_key') ?>" />
          <div class="layui-form-mid layui-word-aux"> * </div></td>
        <td>用户登录COOKIE安全加密头,软件连接口验证交互密钥头(可改更改后软件服务器地址也会变动)</td>
        <td>bs_lib::get_configs_value('sys','cook_key')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1208')" >
        <td nowrap="nowrap">系统开关：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <select name="stop"  class="layui-form-label"  id="stop">
            <option value="0"    > 正常开放系统</option>
            <option value="1"     > 关闭系统</option>
          </select>
          </label></td>
        <td >系统开关</td>
        <td >bs_lib::get_configs_value('sys','stop')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1208')" >
        <td nowrap="nowrap">关闭提示：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1207"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="stop_info" type="text"  class="layui-form-label"  id="off_name" value="<?php echo bs_lib::get_configs_value('sys','stop_info') ?>" size="40"/></td>
        <td >关闭提示</td>
        <td >bs_lib::get_configs_value('sys','stop_info')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">用户中心：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
        <input type="radio" name="stop_user" value="1"<?php echo_checkbox_checked('sys','stop_user',1); ?>>

开启</label>
<label>
<input type="radio" name="stop_user" value="0"<?php echo_checkbox_checked('sys','stop_user',0); ?>>

关闭</label></td>
        <td >不需要使用关闭</td>
        <td >bs_lib::get_configs_value('sys','stop_user')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">前台CMS：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="stop_cms" value="1"<?php echo_checkbox_checked('sys','stop_cms',1); ?>>
        
开启</label>
<label>
<input type="radio" name="stop_cms" value="0"<?php echo_checkbox_checked('sys','stop_cms',0); ?>>

关闭</label></td>
        <td >不需要使用关闭</td>
        <td >bs_lib::get_configs_value('sys','stop_cms')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">代理中心：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
        <input type="radio" name="stop_agent" value="1"<?php echo_checkbox_checked('sys','stop_agent',1); ?>>

开启</label>
<label>
<input type="radio" name="stop_agent" value="0"<?php echo_checkbox_checked('sys','stop_agent',0); ?>>

锁定</label></td>
        <td >锁定禁止使用代理中心</td>
        <td >bs_lib::get_configs_value('sys','stop_agent')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">锁定代理中心提示：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="stop_agent_info" type="text"  class="layui-form-label"  id="stop_agent_info" value="<?php echo bs_lib::get_configs_value('sys','stop_agent_info') ?>" size="40"/></td>
        <td >这里是为了有紧急事务而锁定禁止所有代理登录并提示的信息</td>
        <td >bs_lib::get_configs_value('sys','stop_agent_info')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">联系邮箱：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="admin_mail" type="text"  class="layui-form-label"  id="admin_mail" value="<?php echo bs_lib::get_configs_value('sys','admin_mail') ?>" size="80"/></td>
        <td >联系方式</td>
        <td >bs_lib::get_configs_value('sys','admin_mail')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">网站备案号：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="govicp" type="text"  class="layui-form-label"  id="govicp" value="<?php echo bs_lib::get_configs_value('sys','govicp') ?>" size="40"/></td>
        <td >国家工业部备案号</td>
        <td >bs_lib::get_configs_value('sys','govicp')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">统计代码：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="cnzz_id" type="text"  class="layui-form-label"  id="cnzz_id" value="<?php echo bs_lib::get_configs_value('sys','cnzz_id') ?>" size="40"/></td>
        <td >推荐CNZZ代码</td>
        <td >bs_lib::get_configs_value('sys','cnzz_id')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">禁止访问IP：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="stop_ip" type="text"  class="layui-form-label"  id="stop_ip" value="<?php echo bs_lib::get_configs_value('sys','stop_ip') ?>" size="40"/></td>
        <td >逗号隔开,如:114.114.114.114|114.114.114.（.*）|195.（.*）</td>
        <td >bs_lib::get_configs_value('sys','stop_ip')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      
      
      <tr onMouseOver="bsphpfaq(this, 'faq1208')" >
        <td nowrap="nowrap">SQL安全级别：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <select name="safe_sql"  class="layui-input"  id="safe_sql">
            <option value="0"    > 特级防护</option>
            <option value="1"     > 关键级别</option>
            <option value="2"     > 关闭防护</option>
          </select>
          </label></td>
        <td >特级防护:关键注入词,与敏感字符 关键级别:关键注入词  关闭防护:全部关闭 如果调整建议《关键级别》即可</td>
        <td >bs_lib::get_configs_value('sys','safe_sql')</td>
      </tr>
      
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">调试模式：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="error_code" value="1"<?php echo_checkbox_checked('sys','error_code',1); ?>>
        
开启调试</label>
<label>
<input type="radio" name="error_code" value="0"<?php echo_checkbox_checked('sys','error_code',0); ?>>

上线运营</label></td>
        <td >开发调试尽量开启调试模式,上线时候尽量关闭！</td>
        <td >bs_lib::get_configs_value('sys','error_code')</td>
      </tr>
      <tr>
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
select_set('stop',<?php echo (int)bs_lib::get_configs_value('sys','stop') ?>);
select_set('safe_sql',<?php echo (int)bs_lib::get_configs_value('sys','safe_sql') ?>);
</script>
<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a> Bsphp.com <br>
  All Rights Reserved </div>
</body>
</html>
