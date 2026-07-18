<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("code","name") ?>- by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css">
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/table.js"></script>
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable">
  <div id="texttale"  class="texttale">邮件设置</DIV>
  [<a href="index.php?m=admin&c=addurl&a=index&url={$URL}">+</a>] </div>
  
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
        <td nowrap="nowrap">发送邮件方式：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><select name="set" class="layui-form-label" id="set">
          <option value="0">不使用</option>
          <option value="1">SMTP</option>
                </select></td>
        <td >邮件模式</td>
        <td >bs_lib::get_configs_value('mail','set')</td>
      </tr>
  
  
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">SMTP服务器：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="smtp_host" type="text" class="layui-form-label" id="smtp_host" value="<?php echo bs_lib::get_configs_value('mail','smtp_host') ?>" size="20"/></td>
        <td >如:SMTP.163.COM</td>
        <td >bs_lib::get_configs_value('mail','smtp_host')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">端口：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="port" type="text" class="layui-form-label" id="port" value="<?php echo bs_lib::get_configs_value('mail','port') ?>" size="10"/></td>
        <td height="30">一般默认:25</td>
        <td >bs_lib::get_configs_value('mail','port')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">显示发信人邮箱：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="send_show_mail" type="text" class="layui-form-label" id="send_show_mail" value="<?php echo bs_lib::get_configs_value('mail','send_show_mail') ?>" size="35"/></td>
        <td >显示邮箱</td>
        <td >bs_lib::get_configs_value('mail','send_show_mail')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">发信账号：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="send_mail_user" type="text" class="layui-form-label" id="send_mail_user" value="<?php echo bs_lib::get_configs_value('mail','send_mail_user') ?>" size="35"/></td>
        <td >发信账号</td>
        <td >bs_lib::get_configs_value('mail','send_mail_user')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">发信账号密码：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="send_mail_user_pwd" type="password" class="layui-form-label" id="send_mail_user_pwd" value="<?php echo bs_lib::get_configs_value('mail','send_mail_user_pwd') ?>" size="35"/></td>
        <td >账号的密码</td>
        <td >bs_lib::get_configs_value('mail','send_mail_user_pwd')</td>
      </tr>
      <tr>
        <td nowrap="nowrap">系统邮件模板目录:</td>
        <td><?php echo BSPHP_DIR_SYS; ?>Data/maill/</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <th nowrap="nowrap">用于二次开发使用预留功能</th>
        <th><input id="admin" type="submit"  name="appenconfig" class="layui-btn layui-btn-normal"  value="保存修改"></th>
        <td></th>
		<td></th>
      </th> </th>	  </tr>
      <tr>
        <th nowrap="nowrap">&nbsp;</th>
        <th>&nbsp;</th>
        <th >&nbsp;</th>
        <th >&nbsp;</th>
      </tr>
      <tr>
        <td nowrap="nowrap">测试发送邮箱：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="send_mail_text" type="text" class="layui-form-label" id="send_mail_text" value="" size="30"/>
        <input id="appenconfig" type="submit" name="send_mail"class="layui-btn layui-btn-sm"  value="测试发送"></td>
        <td >测试发送邮件,发送时记得先保存</td>
        <td >&nbsp;</td>
      </tr>
    </tfoot>
  </table>
</form>
<script language="javascript">
select_set('set',<?php echo (int)bs_lib::get_configs_value('mail','set') ?>);
</script>
<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a> Bsphp.com <br>
  All Rights Reserved </div>
</body>
</html>
