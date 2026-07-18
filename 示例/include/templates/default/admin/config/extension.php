<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("extension","name") ?>- by BSPHP</title>
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
  <table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">
    <thead>
      <tr>
        <th width="250" nowrap="nowrap" >配置名称</th>
        <th width="350" >表单填写</th>
        <th nowrap="nowrap" >填写提示</th>
        <th >php开发调用方法</th>
      </tr>
    </thead>
    <tbody>
      <tr onMouseOver="bsphpfaq(this, 'faq1201')" >
        <td nowrap="nowrap">注册-开放邀请模式：</td>
        <td><select name="yaoqing_off" class="layui-form-label" id="yaoqing_off">
          <option value="0" selected>彻底关闭推广系统</option>
          <option value="1" >开放代理和用户参加推广</option>
          <option value="2" >只开放代理推广</option>

                                        </select></td>
        <td nowrap="nowrap">推广获得积分的用户类型<br></td><td>bs_lib::get_configs_value('extension','yaoqing_off')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1204')" >
        <td nowrap="nowrap">注册-邀请获得积分：</td>
        <td><input name="yaoqing_jifen" type="text" class="layui-form-label" id="yaoqing_jifen" value="<?php echo bs_lib::get_configs_value('extension','yaoqing_jifen') ?>" size="15"/>
          <div class="layui-form-mid layui-word-aux"> 积分<div></td>
        <td nowrap="nowrap" >每邀请一个用户可获得的积分<br></td><td >bs_lib::get_configs_value('extension','yaoqing_jifen')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1204')" >
        <td nowrap="nowrap">注册-每天最都邀请人：</td>
        <td><input name="yaoqing_today_RenShu" type="text" class="layui-form-label" id="yaoqing_today_RenShu" value="<?php echo bs_lib::get_configs_value('extension','yaoqing_today_RenShu') ?>" size="15"/>
        <div class="layui-form-mid layui-word-aux"> 天<div></td>
        <td nowrap="nowrap" >防止用户恶意刷积分,自己参考设置自己希望数量<br></td><td >bs_lib::get_configs_value('extension','yaoqing_today_RenShu')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1205')" >
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td nowrap="nowrap" >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1205')" >
        <td nowrap="nowrap">提成方式：</td>
        <td><label>
        <input type="radio" name="you_weizi" value="0"<?php echo_checkbox_checked('extension','you_weizi',0); ?>>
充值</label>
          <label>
          <input type="radio" name="you_weizi" value="1"<?php echo_checkbox_checked('extension','you_weizi',1); ?>>
          平台
          </label></td>
        <td nowrap="nowrap" >充值&gt;这个提成用户在s使用支付宝 网银等。。充值,上级获得充值的百分比提成<br>
        平台&gt;用户在使用平台上帐号余额够卡,上级获得提成</td>
        <td >bs_lib::get_configs_value('extension','you_weizi')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1206')" >
        <td nowrap="nowrap">提成-推广提成模式：</td>
        <td><select name="ticheng_oof" class="layui-form-label" id="ticheng_oof">
          <option value="0" selected>彻底关闭提成</option>
          <option value="1" >开放代理和用户获得提成</option>
          <option value="2" >只开放代理获得提成</option>
        </select></td>
        <td nowrap="nowrap">邀请用户消费获得提成的用户类型</td>
        <td>bs_lib::get_configs_value('extension','ticheng_oof')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td nowrap="nowrap">提成-A级提成百分比：</td>
        <td><input name="ticheng_baifenbi_1" type="text" class="layui-form-label" id="ticheng_baifenbi_1" value="<?php echo bs_lib::get_configs_value('extension','ticheng_baifenbi_1') ?>" size="15"/>
        <div class="layui-form-mid layui-word-aux"> %<div></td>
        <td nowrap="nowrap">A级用户消费-&gt;上级用户获得百分比分红 如:10% 设置0关闭本级 关闭本级,下级不关闭,下级可以继续领取分红<br></td><td>bs_lib::get_configs_value('extension','ticheng_baifenbi_1')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1208')" >
        <td nowrap="nowrap">提成-B级提成百分比：</td>
        <td><input name="ticheng_baifenbi_2" type="text" class="layui-form-label" id="ticheng_baifenbi_2" value="<?php echo bs_lib::get_configs_value('extension','ticheng_baifenbi_2') ?>" size="15"/>
        <div class="layui-form-mid layui-word-aux"> %<div></td>
        <td nowrap="nowrap" >B级用户消费-&gt;A的上级用户获得百分比分红 如:5% 设置0关闭本级<br></td><td >bs_lib::get_configs_value('extension','ticheng_baifenbi_2')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1208')" >
        <td nowrap="nowrap">提成-C级提成百分比：</td>
        <td><input name="ticheng_baifenbi_3" type="text" class="layui-form-label" id="ticheng_baifenbi_3" value="<?php echo bs_lib::get_configs_value('extension','ticheng_baifenbi_3') ?>" size="15"/>
        <div class="layui-form-mid layui-word-aux"> %<div></td>
        <td nowrap="nowrap" >C级用户消费-&gt;A的上级用户获得百分比分红 如:2% 设置0关闭本级<br></td><td >bs_lib::get_configs_value('extension','ticheng_baifenbi_3')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">提成-在邀请N天内获得提成:</td>
        <td><input name="ticheng_baifenbi_time" type="text" class="layui-form-label" id="ticheng_baifenbi_time" value="<?php echo bs_lib::get_configs_value('extension','ticheng_baifenbi_time') ?>" size="15"/>
        <div class="layui-form-mid layui-word-aux"> 天<div></td>
        <td nowrap="nowrap" >被邀请人在注册后N天内消费上级可以获得提成,过期限上级将不在获得提成 如:365天<br></td><td >bs_lib::get_configs_value('extension','ticheng_baifenbi_time')</td>
      </tr>
      <tr>
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td nowrap="nowrap" >&nbsp;</td>
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
select_set('yaoqing_off',<?php echo (int)bs_lib::get_configs_value('extension','yaoqing_off') ?>);
select_set('ticheng_oof',<?php echo (int)bs_lib::get_configs_value('extension','ticheng_oof') ?>);
</script>
<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a> Bsphp.com <br>
  All Rights Reserved </div>
</body>
</html>
