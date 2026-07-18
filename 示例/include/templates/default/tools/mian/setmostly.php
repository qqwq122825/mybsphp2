<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?PHP echo bs_lib::get_configs_value("user", "name") ?>- by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP echo get_url_statics(); ?>skin/style/base.css">
<script src="<?PHP echo get_url_statics(); ?>skin/js/table.js"></script>
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable">
  <div id="texttale"  class="texttale">菜单隐藏/显示</DIV>
  [<a href="index.php?m=admin&c=addurl&a=index&url={$URL}">+</a>] </div>
  
<!--  快捷  -->
<div id="Shortcut">
  
</div>
<!--  内容列表   -->
<form action=""  name="form"  method="post"    onClick="return allcheckbox();"  >
  <table class="layui-table"  style="width:98%;margin-left: 10px;"  lay-filter="demoEvent">
    <thead>
      <tr>
        <th width="250" >配置名称</th>
        <th width="350" >表单填写</th>
        <th width="84" >填写提示</th>
        <th width="90" >&nbsp;</th>
      </tr>
      </thead>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>顶部导航隐藏显示<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none"></a></td>
        <td><label></label>
            <label></label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>首页<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none"></a></td>
        <td><label></label>
            <label>
            <input name="top_1" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'top_1', 0); ?>>
显示</label>
            <label>
            <input type="radio" name="top_1" value="1"<?php echo_checkbox_checked('setmostly',
'top_1', 1); ?>>
隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>系统<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none"></a></td>
        <td><label></label>
            <label>
            <input name="top_2" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'top_2', 0); ?>>
显示</label>
            <label>
            <input type="radio" name="top_2" value="1"<?php echo_checkbox_checked('setmostly',
'top_2', 1); ?>>
隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>账号<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none"></a></td>
        <td><label></label>
            <label>
            <input name="top_3" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'top_3', 0); ?>>
显示</label>
            <label>
            <input type="radio" name="top_3" value="1"<?php echo_checkbox_checked('setmostly',
'top_3', 1); ?>>
隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>软件<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none"></a></td>
        <td><label></label>
            <label>
            <input name="top_4" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'top_4', 0); ?>>
显示</label>
            <label>
            <input type="radio" name="top_4" value="1"<?php echo_checkbox_checked('setmostly',
'top_4', 1); ?>>
隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>插件<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none"></a></td>
        <td><label></label>
            <label>
            <input name="top_5" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'top_5', 0); ?>>
显示</label>
            <label>
            <input type="radio" name="top_5" value="1"<?php echo_checkbox_checked('setmostly',
'top_5', 1); ?>>
隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>前台<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none"></a></td>
        <td><label></label>
            <label>
            <input name="top_6" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'top_6', 0); ?>>
显示</label>
            <label>
            <input type="radio" name="top_6" value="1"<?php echo_checkbox_checked('setmostly',
'top_6', 1); ?>>
隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td><a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none"></a></td>
        <td><label></label>
            <label></label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>系统信息设置：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_1_1" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_1_1', 0); ?>>
          显示</label>
            <label>
            <input type="radio" name="xt_1_1" value="1"<?php echo_checkbox_checked('setmostly',
'xt_1_1', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>Session设置：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_1_2" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_1_2', 0); ?>>
          显示</label>
            <label>
            <input type="radio" name="xt_1_2" value="1"<?php echo_checkbox_checked('setmostly',
'xt_1_2', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      

      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>验证码设置：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_1_3" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_1_3', 0); ?>>
          显示</label>
            <label>
            <input type="radio" name="xt_1_3" value="1"<?php echo_checkbox_checked('setmostly',
'xt_1_3', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>用户注册设置：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_1_4" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_1_4', 0); ?>>
          显示</label>
            <label>
            <input type="radio" name="xt_1_4" value="1"<?php echo_checkbox_checked('setmostly',
'xt_1_4', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>邮件设置：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_1_5" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_1_5', 0); ?>>
          显示</label>
            <label>
            <input type="radio" name="xt_1_5" value="1"<?php echo_checkbox_checked('setmostly',
'xt_1_5', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      
      
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>邀请推广设置：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_1_6" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_1_6', 0); ?>>
          显示</label>
            <label>
            <input type="radio" name="xt_1_6" value="1"<?php echo_checkbox_checked('setmostly',
'xt_1_6', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>代理商设置：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_1_9" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_1_9', 0); ?>>
          显示</label>
            <label>
            <input type="radio" name="xt_1_9" value="1"<?php echo_checkbox_checked('setmostly',
'xt_1_9', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>前端续费设置<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_1_10" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_1_10', 0); ?>>
          显示</label>
            <label>
            <input type="radio" name="xt_1_10" value="1"<?php echo_checkbox_checked('setmostly',
'xt_1_10', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      
      
      
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>在线支付设置：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_1_7" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_1_7', 0); ?>>
          显示</label>
            <label>
            <input type="radio" name="xt_1_7" value="1"<?php echo_checkbox_checked('setmostly',
'xt_1_7', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    </thead>
    <tbody>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>模板语言设置：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_1_8" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_1_8', 0); ?>>
          显示</label>
            <label>
            <input type="radio" name="xt_1_8" value="1"<?php echo_checkbox_checked('setmostly',
'xt_1_8', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>系统管理员</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>后台系统管理员：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_2_1" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_2_1', 0); ?>>
          显示</label>
            <label>
            <input name="xt_2_1" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'xt_2_1', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>辅助功能</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>用户反馈信息：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xtuser_2_1" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xtuser_2_1', 0); ?>>
          显示</label>
            <label>
            <input name="xtuser_2_1" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'xtuser_2_1', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>用户用户说说：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xtuser_2_2" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xtuser_2_2', 0); ?>>
          显示</label>
            <label>
            <input name="xtuser_2_2" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'xtuser_2_2', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>当前在线账号：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xtuser_2_3" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xtuser_2_3', 0); ?>>
          显示</label>
            <label>
            <input name="xtuser_2_3" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'xtuser_2_3', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>系统日志记录</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>管理登录日志：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_3_1" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_3_1', 0); ?>>
          显示</label>
            <label>
            <input name="xt_3_1" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'xt_3_1', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>用户登录日志：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_3_2" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_3_2', 0); ?>>
          显示</label>
            <label>
            <input name="xt_3_2" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'xt_3_2', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>邀请推广日志：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_3_3" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_3_3', 0); ?>>
          显示</label>
            <label>
            <input name="xt_3_3" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'xt_3_3', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>佣金提成日志：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_3_4" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_3_4', 0); ?>>
          显示</label>
            <label>
            <input name="xt_3_4" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'xt_3_4', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>代理制卡日志：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_3_5" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_3_5', 0); ?>>
          显示</label>
            <label>
            <input name="xt_3_5" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'xt_3_5', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>反破解日志：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_3_6" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_3_6', 0); ?>>
          显示</label>
            <label>
            <input name="xt_3_6" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'xt_3_6', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>余额扣除日志：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_3_7" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_3_7', 0); ?>>
          显示</label>
            <label>
            <input name="xt_3_7" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'xt_3_7', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>执行失败[SQL]：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="xt_3_8" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'xt_3_8', 0); ?>>
          显示</label>
            <label>
            <input name="xt_3_8" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'xt_3_8', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>用户管理</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>登录账号：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="yh_1_1" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'yh_1_1', 0); ?>>
          显示</label>
            <label>
            <input name="yh_1_1" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'yh_1_1', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>账号维护：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="yh_1_2" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'yh_1_2', 0); ?>>
          显示</label>
            <label>
            <input name="yh_1_2" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'yh_1_2', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>电子商务</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>余额充值订单：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="yh_2_1" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'yh_2_1', 0); ?>>
          显示</label>
            <label>
            <input name="yh_2_1" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'yh_2_1', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>平台购卡订单：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="yh_2_2" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'yh_2_2', 0); ?>>
          显示</label>
            <label>
            <input name="yh_2_2" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'yh_2_2', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>我的软件</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>软件管理：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="rj_1_1" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'rj_1_1', 0); ?>>
          显示</label>
            <label>
            <input name="rj_1_1" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'rj_1_1', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>添加软件：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="rj_1_2" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'rj_1_2', 0); ?>>
          显示</label>
            <label>
            <input name="rj_1_2" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'rj_1_2', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>批量维护</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>充值卡串维护：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="rj_3_1" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'rj_3_1', 0); ?>>
          显示</label>
            <label>
            <input name="rj_3_1" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'rj_3_1', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>软件用户维护：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="rj_3_4" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'rj_3_4', 0); ?>>
          显示</label>
            <label>
            <input name="rj_3_4" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'rj_3_4', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>CMS设置</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>前台模板：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="qt_1_1" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'qt_1_1', 0); ?>>
          显示</label>
            <label>
            <input name="qt_1_1" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'qt_1_1', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>CMS内容管理</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>新闻文章列表：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="qt_2_1" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'qt_2_1', 0); ?>>
          显示</label>
            <label>
            <input name="qt_2_1" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'qt_2_1', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>新闻文章添加：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="qt_2_2" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'qt_2_2', 0); ?>>
          显示</label>
            <label>
            <input name="qt_2_2" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'qt_2_2', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>栏目列表：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="qt_2_3" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'qt_2_3', 0); ?>>
          显示</label>
            <label>
            <input name="qt_2_3" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'qt_2_3', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr onMouseOver="bsphpfaq(this, 'faq1207')" >
        <td>栏目添加：<a id="faq1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faq1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
        <td><label>
          <input name="qt_2_4" type="radio" value="0"<?php echo_checkbox_checked('setmostly',
'qt_2_4', 0); ?>>
          显示</label>
            <label>
            <input name="qt_2_4" type="radio"  value="1"<?php echo_checkbox_checked('setmostly',
'qt_2_4', 1); ?>>
              隐藏</label></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td><input id="admin" type="submit" name="setmostly"  class="layui-btn layui-btn-normal"  value="保存修改"></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    </tbody>
  </table>
</form>
<script language="javascript">
select_set('user_id',<?php echo (int)bs_lib::get_configs_value('user', 'user_id') ?>);
</script>
<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo
BSPHP_VERSION; ?></a> Bsphp.com <br>
  All Rights Reserved </div>
</body>
</html>
