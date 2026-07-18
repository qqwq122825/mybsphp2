<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?>- by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css">
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/table.js"></script>
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable">
  <div id="texttale"  class="texttale">系统信息设置</DIV><?php echo $this->quick_html(); ?> </div>
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
  <table class="layui-table"  style="width:98%;margin-left: 10px;"  lay-filter="demoEvent">
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
        <td nowrap="nowrap">CMS前台模板：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><select name="cms_template" class="layui-form-label" id="cms_template">
		    <?php 
			foreach ($temp_array as $value) {
			   echo "<option value=\"$value\">$value</option>";
			}
			?>
        </select></td>
        <td >如果模板不存在着选择系统默认模板default</td>
        <td >bs_lib::get_configs_value('sys','cms_template')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">CMS前台系统语言：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><select name="cms_langs" class="layui-form-label" id="cms_langs">
		    <?php 
			foreach ($dir_array as $value) {
			   echo "<option value=\"$value\">$value</option>";
			}
			?>
                        </select></td>
        <td >如果语言包文件不存在着选择系统默认的zh-cn</td>
        <td >bs_lib::get_configs_value('sys','cms_langs')</td>
      </tr>
      <tr>
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">管理后台模板：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><select name="admin_template" class="layui-form-label" id="admin_template">
            <?php 
			foreach ($temp_array as $value) {
			   echo "<option value=\"$value\">$value</option>";
			}
			?>
                </select></td>
        <td >如果模板不存在着选择系统默认模板default</td>
        <td >bs_lib::get_configs_value('sys','admin_template')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">管理后系统语言：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><select name="admin_langs" class="layui-form-label" id="admin_langs">
            <?php 
			foreach ($dir_array as $value) {
			   echo "<option value=\"$value\">$value</option>";
			}
			?>
        </select></td>
        <td >如果语言包文件不存在着选择系统默认的zh-cn</td>
        <td >bs_lib::get_configs_value('sys','admin_langs')</td>
      </tr>
      <tr>
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">用户中心模板：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><select name="user_template" class="layui-form-label" id="user_template">
            <?php 
			foreach ($temp_array as $value) {
			   echo "<option value=\"$value\">$value</option>";
			}
			?>
                </select></td>
        <td >如果模板不存在着选择系统默认模板default</td>
        <td >bs_lib::get_configs_value('sys','user_template')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">用户中心系统语言：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><select name="user_langs" class="layui-form-label" id="user_langs">
            <?php 
			foreach ($dir_array as $value) {
			   echo "<option value=\"$value\">$value</option>";
			}
			?>
        </select></td>
        <td >如果语言包文件不存在着选择系统默认的zh-cn</td>
        <td >bs_lib::get_configs_value('sys','user_langs')</td>
      </tr>
      <tr>
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">代理中心模板：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><select name="agent_template" class="layui-form-label" id="agent_template">
            <?php 
			foreach ($temp_array as $value) {
			   echo "<option value=\"$value\">$value</option>";
			}
			?>
                </select></td>
        <td >如果模板不存在着选择系统默认模板default</td>
        <td >bs_lib::get_configs_value('sys','agent_template')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">代理中心系统语言：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><select name="agent_langs" class="layui-form-label" id="agent_langs">
            <?php 
			foreach ($dir_array as $value) {
			   echo "<option value=\"$value\">$value</option>";
			}
			?>
        </select></td>
        <td >如果语言包文件不存在着选择系统默认的zh-cn</td>
        <td >bs_lib::get_configs_value('sys','agent_langs')</td>
      </tr>
      <tr>
        <td nowrap="nowrap">模板位置:</td>
        <td>include\templates</td>
        <td ><p>前台模板:<br>
        模板文件:include\templates\模板目录\default\index\cms_skn</p>
        <p>模板掉本include\templates\模板目录\default\index\cms_inc</p></td>
        <td >&nbsp;</td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <th nowrap="nowrap"></th>
        <th><input id="admin" type="submit" name="appenconfig"  class="layui-btn layui-btn-normal"  value="保存修改"></th>
        <td></th>
        <th>
      </th>      </tr>
    </tfoot>
  </table>
</form>
<script language="javascript">
select_value('cms_template','<?php echo bs_lib::get_configs_value('sys','cms_template'); ?>');
select_value('cms_langs','<?php echo bs_lib::get_configs_value('sys','cms_langs'); ?>');

select_value('admin_template','<?php echo bs_lib::get_configs_value('sys','admin_template'); ?>');
select_value('admin_langs','<?php echo bs_lib::get_configs_value('sys','admin_langs'); ?>');

select_value('user_template','<?php echo bs_lib::get_configs_value('sys','user_template'); ?>');
select_value('user_langs','<?php echo bs_lib::get_configs_value('sys','user_langs'); ?>');

select_value('agent_template','<?php echo bs_lib::get_configs_value('sys','agent_template'); ?>');
select_value('agent_langs','<?php echo bs_lib::get_configs_value('sys','agent_langs'); ?>');
</script>
<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a> Bsphp.com <br>
  All Rights Reserved </div>
</body>
</html>
