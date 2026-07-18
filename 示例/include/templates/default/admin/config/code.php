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
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css">
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/table.js"></script>
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable">
  <div id="texttale"  class="texttale">验证码设置</DIV>
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
        <td nowrap="nowrap">网页用户中心验证码：<a id="faq1201"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1201"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a> </td>
        <td><label>
          <input name="coode_login" type="radio" value="1" <?php echo_checkbox_checked('code','coode_login',1); ?> >
         
        开启  </label>
        <label>
        <input type="radio" name="coode_login" value="0"<?php echo_checkbox_checked('code','coode_login',0); ?>>
        
        关闭</label></td>
        <td>网页用户管理中心登录时验证码,包含代理中心</td>
        <td>bs_lib::get_configs_value('code','coode_login')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1204')" >
        <td nowrap="nowrap">网页用户注册验证码：<a id="faq1204"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1202"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="coode_registration" value="1"<?php echo_checkbox_checked('code','coode_registration',1); ?>>
        
开启</label>
<label>
<input type="radio" name="coode_registration" value="0"<?php echo_checkbox_checked('code','coode_registration',0); ?>>

关闭</label></td>
        <td >用户注册时验证码</td>
        <td >bs_lib::get_configs_value('code','coode_registration')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1205')" >
        <td nowrap="nowrap">网页用户留言反馈验证码：<a id="faq1205"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1203"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="coode_say" value="1"<?php echo_checkbox_checked('code','coode_say',1); ?>>
        
开启</label>
<label>
<input type="radio" name="coode_say" value="0"<?php echo_checkbox_checked('code','coode_say',0); ?>>

关闭</label></td>
        <td >用户在线反馈时候的验证码</td>
        <td >bs_lib::get_configs_value('code','coode_say')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1205')" >
        <td nowrap="nowrap">网页找回密码验证码：<a id="faq1205"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1203"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="coode_backpwd" value="1"<?php echo_checkbox_checked('code','coode_backpwd',1); ?>>
       
开启 </label>
<label>
<input type="radio" name="coode_backpwd" value="0"<?php echo_checkbox_checked('code','coode_backpwd',0); ?>>

关闭</label></td>
        <td >找回密码时的验证码</td>
        <td >bs_lib::get_configs_value('code','coode_backpwd')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">验证码连接地址:</td>
        <td>http://系统URL根目录/index.php?m=coode&amp;sessl=</td>
        <td >全局调用验证码的连接地址</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">验证码数字：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="charset_1" value="1"<?php echo_checkbox_checked('code','charset_1',1); ?>>
        
包含数字</label>
<label>
<input type="radio" name="charset_1" value="0"<?php echo_checkbox_checked('code','charset_1',0); ?>>

取消数字</label></td>
        <td >1三项不选全有 01没有</td>
        <td >bs_lib::get_configs_value('code','charset_1')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">验证码小写字母：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
        <input type="radio" name="charset_a" value="1"<?php echo_checkbox_checked('code','charset_a',1); ?>>
        
包含小写</label>
<label>
<input type="radio" name="charset_a" value="0"<?php echo_checkbox_checked('code','charset_a',0); ?>>

取消小写</label></td>
        <td >2三项不选全有 ol没有</td>
        <td >bs_lib::get_configs_value('code','charset_a')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">验证码大写字母：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
        <input type="radio" name="charset_aa" value="1"<?php echo_checkbox_checked('code','charset_aa',1); ?>>
        
包含大写</label>
<label>
<input type="radio" name="charset_aa" value="0"<?php echo_checkbox_checked('code','charset_aa',0); ?>>

取消大写</label></td>
        <td >3三项不选全有 OL没有</td>
        <td >bs_lib::get_configs_value('code','charset_A')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">字体大小：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="fontsize" type="text" class="layui-form-label" id="fontsize" value="<?php echo bs_lib::get_configs_value('code','fontsize') ?>" size="10"/>
          <div class="layui-form-mid layui-word-aux"> px</div></td>
        <td >建议:3-5位</td>
        <td >bs_lib::get_configs_value('code','fontsize')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">字符位数：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="codelen" type="text" class="layui-form-label" id="codelen" value="<?php echo bs_lib::get_configs_value('code','codelen') ?>" size="10"/>
        <div class="layui-form-mid layui-word-aux"> 位</div></td>
        <td >建议:3-5位</td>
        <td >bs_lib::get_configs_value('code','codelen')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">图片宽度：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="width" type="text" class="layui-form-label" id="width" value="<?php echo bs_lib::get_configs_value('code','width') ?>" size="10"/></td>
        <td >建议:130</td>
        <td >bs_lib::get_configs_value('code','width')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">图片高度：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="height" type="text" class="layui-form-label" id="height" value="<?php echo bs_lib::get_configs_value('code','height') ?>" size="10"/></td>
        <td >建议:45</td>
        <td >bs_lib::get_configs_value('code','height')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">图片字体文件：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="font" type="text" class="layui-form-label" id="font" value="<?php echo bs_lib::get_configs_value('code','font') ?>" size="25"/></td>
        <td >默认:elephant.ttf 字体文件位置:Data/font/xxxx.ttf</td>
        <td >bs_lib::get_configs_value('code','font')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">图片背景颜色：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="back_color" type="text" class="layui-form-label" id="back_color" value="<?php echo bs_lib::get_configs_value('code','back_color') ?>" size="15"/></td>
        <td >-1随机 颜色格式:#EF3F22 建议:-1</td>
        <td >bs_lib::get_configs_value('code','back_color')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">图片字体颜色：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><input name="fontcolor_text" type="text" class="layui-form-label" id="fontcolor_text" value="<?php echo bs_lib::get_configs_value('code','fontcolor_text') ?>" size="15"/></td>
        <td >-1随机 颜色格式:#EF3FFF 建议:-1</td>
        <td >bs_lib::get_configs_value('code','fontcolor_text')</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faqN1209')">
        <td nowrap="nowrap">字体打斜度：<a id="faq1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input type="radio" name="text_r" value="1"<?php echo_checkbox_checked('code','text_r',1); ?>>
          开启斜度        </label>
          <label>
<input type="radio" name="text_r" value="0"<?php echo_checkbox_checked('code','text_r',0); ?>>

关闭斜度</label></td>
        <td >添加防识别难度,建议可以别开,用户看更清楚</td>
        <td >bs_lib::get_configs_value('code','text_r')</td>
      </tr>
      <tr>
        <td nowrap="nowrap">添加线条:</td>
        <td><label>
        <input type="radio" name="createLine_a" value="1"<?php echo_checkbox_checked('code','createLine_a',1); ?>>
        
开启线条</label>
<label>
<input type="radio" name="createLine_a" value="0"<?php echo_checkbox_checked('code','createLine_a',0); ?>>

关闭线条</label></td>
        <td >添加防识别难度,建议可以别开,用户看更清楚</td>
        <td >bs_lib::get_configs_value('code','createLine_a')</td>
      </tr>
      <tr>
        <td nowrap="nowrap">添加雪花:</td>
        <td><label>
          <input type="radio" name="createLine_b" value="1"<?php echo_checkbox_checked('code','createLine_b',1); ?>>
        
开启雪花</label>
<label>
<input type="radio" name="createLine_b" value="0"<?php echo_checkbox_checked('code','createLine_b',0); ?>>

关闭线条</label></td>
        <td >添加防识别难度,建议可以别开,用户看更清楚</td>
        <td >bs_lib::get_configs_value('code','createLine_b')</td>
      </tr>
      <tr>
        <td nowrap="nowrap">&nbsp;</td>
        <td>&nbsp;</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr>
        <td nowrap="nowrap">验证码预览：</td>
        <td><img class="cimg"  onclick="this.src='index.php?m=coode&time='+new Date().getTime()";  src="index.php?m=coode&time='+new Date().getTime()" alt="验证码" width="<?php echo bs_lib::get_configs_value('code','width') ?>" height="<?php echo bs_lib::get_configs_value('code','height') ?>" border="0" /></td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <th nowrap="nowrap"></th>
        <th><input id="admin" type="submit"  name="appenconfig" class="layui-btn layui-btn-normal"  value="保存修改"></th>
        <td></th>
        <th></th> 
           </tr>
    </tfoot>
  </table>
</form>
<script language="javascript">
select_set('stop',<?php echo (int)bs_lib::get_configs_value('code','stop') ?>);
</script>
<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a> Bsphp.com <br>
  All Rights Reserved </div>
</body>
</html>
