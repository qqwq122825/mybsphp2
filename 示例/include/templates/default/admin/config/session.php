<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?>- by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css?1">
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/table.js"></script>
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable">
  <div id="texttale"  class="texttale">系统信息设置</DIV>
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
      <tr onMouseOver="bsphpfaq(this, 'faq1201')" >
        <td nowrap="nowrap">session默认超时设置：<a id="faq1201"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1201"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a> </td>
        <td><input name="session_ttl" type="text" class="layui-form-label" id="session_ttl" value="<?php echo bs_lib::get_configs_value('sys','session_ttl') ?>" size="20"/>
        <div class="layui-form-mid layui-word-aux"> 秒</div></td>
        <td>每个session超时删除时间,建议设置7天60*60*24*7=604800</td>
        <td>bs_lib::get_configs_value('sys','session_ttl')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1204')" >
        <td nowrap="nowrap">cookies作用域名：<a id="faq1204"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1202"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="cookie_domain" type="text" class="layui-form-label" id="cookie_domain" value="<?php echo bs_lib::get_configs_value('sys','cookie_domain') ?>" size="30"/></td>
        <td >默认留空,留空即可</td>
        <td >bs_lib::get_configs_value('sys','cookie_domain')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1205')" >
        <td nowrap="nowrap">cookies作用目录：<a id="faq1204"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1202"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="cookie_path" type="text" class="layui-form-label" id="ini_date" value="<?php echo bs_lib::get_configs_value('sys','cookie_path') ?>" size="30"/></td>
        <td >默认留空,留空即可</td>
        <td >bs_lib::get_configs_value('sys','cookie_path')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1205')" >
        <td nowrap="nowrap">cookies前缓：<a id="faq1204"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1202"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="cookie_pre" type="text" class="layui-form-label" id="ini_date" value="<?php echo bs_lib::get_configs_value('sys','cookie_pre') ?>" size="30"/></td>
        <td >防止其他程序对cookies名称重复,可空</td>
        <td >bs_lib::get_configs_value('sys','cookie_pre')</td>
      </tr>

        <td nowrap="nowrap">说明</td>
        <td>http://bbs.bsphp.com/thread-7806-1-1.html</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
    <td nowrap="nowrap"></tbody>
    <tfoot>
      <tr>
        <th nowrap="nowrap"></th>
        <th><input id="admin" type="submit" name="appenconfig"  class="layui-btn layui-btn-normal" value="保存修改"></th>
        <td></th>
        <th>
      </th>      </tr>
    </tfoot>
  </table>
</form>
<script language="javascript">
select_set('stop',<?php echo (int)bs_lib::get_configs_value('sys','stop') ?>);
</script>
<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a> Bsphp.com <br>
  All Rights Reserved </div>
</body>
</html>
