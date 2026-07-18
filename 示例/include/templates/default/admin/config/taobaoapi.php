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
  <table class="layui-table" lay-filter="demoEvent">
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
        <td nowrap="nowrap">注册-开放邀请模式：</td>
        <td><select name="select" class="layui-form-label" id="select">
          <option value="0" selected>彻底关闭推广系统</option>
          <option value="1" >开放代理和用户参加推广</option>
          <option value="2" >只开放代理推广</option>

                                        </select></td>
        <td>注册账号名称,基本符号=下划线</td>
        <td>bs_lib::get_configs_value('sys','name')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1204')" >
        <td nowrap="nowrap">注册-邀请获得积分：</td>
        <td><input name="url42323" type="text" class="layui-form-label" id="url42323" value="<?php echo bs_lib::get_configs_value('sys','url') ?>" size="15"/>
          积分</td>
        <td >格式:关键词|关键词|关键词|关键词</td>
        <td >bs_lib::get_configs_value('sys','url')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1204')" >
        <td nowrap="nowrap">注册-每天最都邀请人：</td>
        <td><input name="url42322" type="text" class="layui-form-label" id="url42322" value="<?php echo bs_lib::get_configs_value('sys','url') ?>" size="15"/>
        人</td>
        <td >系统目录如果：http://www.bsphp.com/abc/ </td>
        <td >bs_lib::get_configs_value('sys','url')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1205')" >
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1205')" >
        <td nowrap="nowrap">提成方式：</td>
        <td><label>
        <input type="radio" name="coode_say" value="1"<?php echo_checkbox_checked('code','coode_say',1); ?>>
开启</label>
          <label>
          <input type="radio" name="coode_say" value="0"<?php echo_checkbox_checked('code','coode_say',0); ?>>
关闭</label></td>
        <td >系统时区,当前默认是中国时区</td>
        <td >bs_lib::get_configs_value('sys','timezone')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1206')" >
        <td nowrap="nowrap">提成-推广提成模式：</td>
        <td><select name="select2" class="layui-form-label" id="select2">
          <option value="0" selected>彻底关闭提成</option>
          <option value="1" >开放代理和用户获得提成</option>
          <option value="2" >只开放代理获得提成</option>
        </select></td>
        <td><span >本处密钥你保存好,更改导致系统帐号无法正常（请勿更改保存好以免丢失）</span></td>
        <td>bs_lib::get_configs_value('sys','pass_key')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td nowrap="nowrap">提成-A级提成百分比：</td>
        <td><input name="url4232" type="text" class="layui-form-label" id="url4232" value="<?php echo bs_lib::get_configs_value('sys','url') ?>" size="15"/>
          %</td>
        <td>开启用户注册,可以禁止</td>
        <td>bs_lib::get_configs_value('sys','cook_key')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1208')" >
        <td nowrap="nowrap">提成-B级提成百分比：</td>
        <td><input name="url423" type="text" class="layui-form-label" id="url423" value="<?php echo bs_lib::get_configs_value('sys','url') ?>" size="15"/>
          %</td>
        <td >开启邀请连接注册,账号处可查上级邀请人</td>
        <td >bs_lib::get_configs_value('sys','stop')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1208')" >
        <td nowrap="nowrap">提成-C级提成百分比：</td>
        <td><input name="url422" type="text" class="layui-form-label" id="url422" value="<?php echo bs_lib::get_configs_value('sys','url') ?>" size="15"/>
          %</td>
        <td >通过邀请码注册</td>
        <td >bs_lib::get_configs_value('sys','stop_info')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">提成-在邀请N天内获得提成:</td>
        <td><input name="url42" type="text" class="layui-form-label" id="url42" value="<?php echo bs_lib::get_configs_value('sys','url') ?>" size="15"/>
          %</td>
        <td >邀请码注册成功,把用户推送到指定用户组</td>
        <td >bs_lib::get_configs_value('sys','stop_user')</td>
      </tr>
      <tr>
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <th nowrap="nowrap"></th>
        <th><input id="admin" type="submit"  name="appenconfig" class="layui-btn layui-btn-normal"  value="保存修改"></th>
        <td></th>
        </th>
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
