<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("user","name") ?>- by BSPHP</title>
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
        <th >填写提示</th>
        <th >php开发调用方法</th>
      </tr>
	      </thead>
    <tbody>
      <tr onMouseOver="bsphpfaq(this, 'faq1201')" >
        <td nowrap="nowrap">账号格式正则表达式：<a id="faq1201"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1201"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a> </td>
        <td><input name="user_id" type="text" class="layui-form-label" id="user_id" value="<?php echo stripslashes(bs_lib::get_configs_value('user','user_id')) ?>" size="45"/></td>
        <td>更多的注册格式:http://bbs.bsphp.com/thread-7805-1-1.html</td>
        <td>bs_lib::get_configs_value('user','user_id')</td>
      </tr>

      <tr onMouseOver="bsphpfaq(this, 'faq1201')" >
        <td nowrap="nowrap">账号不符合正则提示语：<a id="faq1201"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1201"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a> </td>
        <td><input name="user_id_info" type="text" class="layui-form-label" id="user_id_info" value="<?php echo bs_lib::get_configs_value('user','user_id_info') ?>" size="45"/></td>
        <td>格式不对提示语</td>
        <td>bs_lib::get_configs_value('user','user_id_info')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1204')" >
        <td nowrap="nowrap">注册账号禁止包含关键词：<a id="faq1204"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1202"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><textarea name="useron" cols="40" rows="3" class="layui-form-label" id="useron"><?php echo bs_lib::get_configs_value('user','useron') ?></textarea></td>
        <td >格式:关键词|关键词|关键词|关键词</td>
        <td >bs_lib::get_configs_value('user','useron')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1204')" >
        <td nowrap="nowrap">账号最小位数：<a id="faq1204"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1202"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="user_a" type="text" class="layui-form-label" id="user_a" value="<?php echo bs_lib::get_configs_value('user','user_a') ?>" size="10"/>
          <div class="layui-form-mid layui-word-aux"> 位数</div></td>
        <td >系统目录如果：http://www.bsphp.com/abc/ </td>
        <td >bs_lib::get_configs_value('user','user_a')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1205')" >
        <td nowrap="nowrap">账号最大不超：<a id="faq1205"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1203"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="user_b" type="text" class="layui-form-label" id="user_b" value="<?php echo bs_lib::get_configs_value('user','user_b') ?>" size="10"/>
<div class="layui-form-mid layui-word-aux"> 位数</div></td>
        <td >设置系统时间差距单位秒 ,如果服务器时间不对,可以在不此调整 </td>
        <td >bs_lib::get_configs_value('user','user_b')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1205')" >
        <td nowrap="nowrap">密码最小位数：<a id="faq1205"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1203"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="user_pwd_a" type="text" class="layui-form-label" id="user_pwd_a" value="<?php echo bs_lib::get_configs_value('user','user_pwd_a') ?>" size="10"/>
          <div class="layui-form-mid layui-word-aux"> 位数</div></td>
        <td >系统时区,当前默认是中国时区</td>
        <td >bs_lib::get_configs_value('user','user_pwd_a')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1205')" >
        <td nowrap="nowrap">密码最大不超：<a id="faq1205"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1203"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="user_pwd_b" type="text" class="layui-form-label" id="user_pwd_b" value="<?php echo bs_lib::get_configs_value('user','user_pwd_b') ?>" size="10"/>
<div class="layui-form-mid layui-word-aux"> 位数</div></td>
        <td >系统时区,当前默认是中国时区</td>
        <td >bs_lib::get_configs_value('user','user_pwd_b')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1206')" >
        <td nowrap="nowrap"><span >每个IP限制注册账号时隔：<a id="faq1205"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1203"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a><a id="faq1206"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1204"  title="点击查看解释"  class="faq"target="_blank" style="display:none"></a></span></td>
        <td><input name="user_re_h" type="text" class="layui-form-label" id="user_re_h" value="<?php echo bs_lib::get_configs_value('user','user_re_h') ?>" size="10"/>
        <div class="layui-form-mid layui-word-aux"> 小时</div></td>
        <td><span >每个IP限定时间内注册账号时间</span></td>
        <td>bs_lib::get_configs_value('user','user_re_h')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1206')" >
        <td nowrap="nowrap"><span >时隔内限制注册账号数量：<a id="faq1205"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1203"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a><a id="faq1206"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1204"  title="点击查看解释"  class="faq"target="_blank" style="display:none"></a></span></td>
        <td><input name="user_re_s" type="text" class="layui-form-label" id="user_re_s" value="<?php echo bs_lib::get_configs_value('user','user_re_s') ?>" size="10"/>
          <div class="layui-form-mid layui-word-aux"> 个</div></td>
        <td><span >在限制时间内可以注册多少个账号</span></td>
        <td>bs_lib::get_configs_value('user','user_re_s')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td nowrap="nowrap">用户注册：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="user_re_set" value="1"<?php echo_checkbox_checked('user','user_re_set',1); ?>>
开启</label>
          <label>
          <input type="radio" name="user_re_set" value="0"<?php echo_checkbox_checked('user','user_re_set',0); ?>>
关闭</label></td>
        <td>开启用户注册,可以禁止</td>
        <td>bs_lib::get_configs_value('user','user_re_set')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">账号需要管理员审核：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="user_re_pass_admin" value="1"<?php echo_checkbox_checked('user','user_re_pass_admin',1); ?>>
开启</label>
          <label>
          <input type="radio" name="user_re_pass_admin" value="0"<?php echo_checkbox_checked('user','user_re_pass_admin',0); ?>>
关闭</label></td>
        <td >注册账号是否需要管理员审核</td>
        <td >bs_lib::get_configs_value('user','user_re_pass_admin')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">验证邮箱：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="user_re_pass_mail" value="1"<?php echo_checkbox_checked('user','user_re_pass_mail',1); ?>>
开启</label>
          <label>
          <input type="radio" name="user_re_pass_mail" value="0"<?php echo_checkbox_checked('user','user_re_pass_mail',0); ?>>
关闭</label></td>
        <td >通过验证邮箱验证账号才可使用,(等同管理员审核)</td>
        <td >bs_lib::get_configs_value('user','user_re_pass_mail')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">发送欢迎邮件：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
        <input type="radio" name="user_re_welconme_mail" value="1"<?php echo_checkbox_checked('user','user_re_welconme_mail',1); ?>>
开启</label>
          <label>
          <input type="radio" name="user_re_welconme_mail" value="0"<?php echo_checkbox_checked('user','user_re_welconme_mail',0); ?>>
关闭</label></td>
        <td >为用户发送一封欢迎邮件(注意配置邮箱)</td>
        <td >bs_lib::get_configs_value('user','user_re_welconme_mail')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">注册表单QQ：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="re_qq" value="1"<?php echo_checkbox_checked('user','re_qq',1); ?>>
          必填
        </label>
          <label>
          <input type="radio" name="re_qq" value="0"<?php echo_checkbox_checked('user','re_qq',0); ?>> 
        可空</label></td>
        <td >联系方式</td>
        <td >bs_lib::get_configs_value('user','re_qq')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">注册表单密保邮箱：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="re_mail" value="1"<?php echo_checkbox_checked('user','re_mail',1); ?>>
          必填</label>
          <label>
          <input type="radio" name="re_mail" value="0"<?php echo_checkbox_checked('user','re_mail',0); ?>>
        可空</label></td>
        <td >注册时密保邮箱,用找回密码等</td>
        <td >bs_lib::get_configs_value('user','re_mail')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">注册表单手机号码：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="re_phone" value="1"<?php echo_checkbox_checked('user','re_phone',1); ?>>
          必填</label>
          <label>
          <input type="radio" name="re_phone" value="0"<?php echo_checkbox_checked('user','re_phone',0); ?>>
        可空</label></td>
        <td >手机联系方式</td>
        <td >bs_lib::get_configs_value('user','re_phone')</td>
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
        <th></th>
        <th><input id="admin" type="submit"  name="appenconfig" class="layui-btn layui-btn-normal"  value="保存修改"></th>
        <td></th>
        </th>
      </th>      </tr>
    </tfoot>
  </table>
</form>
<script language="javascript">
select_set('user_id',<?php echo (int)bs_lib::get_configs_value('user','user_id') ?>);
</script>
<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a> Bsphp.com <br>
  All Rights Reserved </div>
</body>
</html>
