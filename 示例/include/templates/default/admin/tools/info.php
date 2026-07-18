<?php
echo '<html>
<head>
<meta charset="utf-8">
<title>';ECHO bs_lib::get_configs_value("sys","name") ;echo ' - by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="';ECHO get_url_statics();echo 'layui/css/layui.css?t=1515376178709" media="all">
 <script src="';ECHO get_url_statics();echo 'layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="';ECHO get_url_statics();;echo 'skin/style/base.css">
<script src="';ECHO get_url_statics();echo 'skin/js/table.js"></script>

<style type="text/css">
<!--
.STYLE7 {
	color: #FF0000;
	font-weight: bold;
}
.STYLE9 {color: #0066FF}
.STYLE10 {color: #FF0000}
-->
</style>
</head>

<body>

';

echo ' <div style="border: 1px dashed #cc0000;font-family:Tahoma;background-color:#FBEEEB;width:98%;padding:10px;color:#CC6600;"><strong>BSPHP升级提示<BR/></strong>当前版本已开源，从2020年开始不在更新，新版本代号PRO版！由于框架原因无法在继续更新感谢大家10年来对bsphp支持.我们也将回馈我们老用户继续使用更新维护对此开源，禁止销售否则本公司将追究数倍损失赔偿</div>';

;echo '

<table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent"><thead>
  <tr>
    <th height="26" class="STYLE2">公告</th>
  </tr></thead>
  <tr>
    <td bgcolor="#FFFFFF" class="STYLE2">BSPHP开源版，10年前我们向我们用户保证不更新就开源，今天我们开源了当前版本！可用二开但禁止用商业打包销售！</td>
  </tr>
</table>

';;echo '
<br>
<table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">
<thead>

</table>
<br>
<table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">
 <thead> <tr>
    <th width="154" class="STYLE2">Bsphp系统整体信息</th>
    <th width="372" class="STYLE2">信息</th>
    <th width="124" height="26" class="STYLE2">Bsphp系统整体信息</th>
    <th width="431" height="26" class="STYLE2">信息</th>
  </tr></thead>
  <tr>
    <td width="154" bgcolor="#FFFFFF" class="STYLE2">总用户数量：</td>
    <td width="372" bgcolor="#FFFFFF" class="STYLE2">';echo $log_info_array['user_tady_all'] ;echo '个</td>
    <td width="124" height="20" bgcolor="#FFFFFF" class="STYLE2">今天注册数量：</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">';echo $log_info_array['user_tady_re'] ;echo '个</td>
  </tr>
  <tr>
    <td bgcolor="#FFFFFF" class="STYLE2">今天登录用户：</td>
    <td bgcolor="#FFFFFF" class="STYLE2">';echo $log_info_array['user_tady_login'] ;echo '个</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">今天邀请注册数量</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">';echo $log_info_array['user_tady_re_yao'] ;echo '个</td>
  </tr>
  <tr>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">总用户余额：</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">';echo $log_info_array['user_tady_rmb_all'] ;echo '元</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">总充值卡数量：</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2"><a href="http://www.bsphp.com" target="_blank"></a>';echo $log_info_array['ka_all'] ;echo '张</td>
  </tr>
  <tr>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">今天充值：</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">';echo $log_info_array['user_tady_pay_rmb'] ;echo '元</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">总未充值使用卡：</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2"><a href="http://www.bsphp.com" target="_blank"></a>';echo $log_info_array['ka_die'] ;echo '张</td>
  </tr>
  <tr>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">今天消费：</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">';echo $log_info_array['user_tady_buy_ka_rmb'] ;echo '元</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">总已经充值使用卡：</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2"><a href="http://www.bsphp.com" target="_blank"></a>';echo $log_info_array['ka_aut'] ;echo '张</td>
  </tr>
  <tr>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">&nbsp;</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">&nbsp;</td>
    <td width="124" height="20" bgcolor="#FFFFFF" class="STYLE2">今天充值使用卡：</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2"><a href="http://www.bsphp.com" target="_blank"></a>';echo $log_info_array['ka_tady_out_all'] ;echo '张</td>
  </tr>
</table>
<br>
<table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">
  <thead> <tr>
    <th width="154" class="STYLE2">项目</th>
    <th width="372" class="STYLE2">信息</th>
    <th width="124" height="26" class="STYLE2">项目</th>
    <th width="431" height="26" class="STYLE2">信息</th>
  </tr> </thead>
  <tr>
    <td width="154" bgcolor="#FFFFFF" class="STYLE2"><span class="STYLE7">当前服务系统时间：</span></td>
    <td width="372" bgcolor="#FFFFFF" class="STYLE2"><span class="STYLE7">
      ';
echo HOST_DATE;
;echo '    </span></td>
    <td width="124" height="20" bgcolor="#FFFFFF" class="STYLE2">最大执行时间：</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">';
echo ini_get('max_execution_time')
;echo '      seconds</td>
  </tr>
  <tr>
    <td width="154" bgcolor="#FFFFFF" class="STYLE2">软件验证系系统目录：</td>
    <td width="372" bgcolor="#FFFFFF" class="STYLE2">';
;
echo  bs_lib::get_configs_value('sys','url');;echo '</td>
    <td width="124" height="20" bgcolor="#FFFFFF" class="STYLE2">PHP 运行方式：</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">';
echo PHP_SAPI
;echo '</td>
  </tr>
  <tr>
    <td width="154" bgcolor="#FFFFFF" class="STYLE2">HTTP协议版本：</td>
    <td width="372" bgcolor="#FFFFFF" class="STYLE2">';
echo @$_SERVER['SERVER_PROTOCOL'];
;echo '</td>
    <td width="124" height="20" bgcolor="#FFFFFF" class="STYLE2">PHP版本：</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">php';echo PHP_VERSION ;echo '&nbsp;&nbsp;&nbsp; <a href="index.php?m=admin&c=tools&a=phpinfo" >phpInfo</a></td>
  </tr>
  <tr>
    <td width="154" bgcolor="#FFFFFF" class="STYLE2">服务器HOST：</td>
    <td width="372" bgcolor="#FFFFFF" class="STYLE2">';
echo @$_SERVER['SERVER_NAME']
;echo '      (
      ';
echo @$_SERVER['SERVER_ADDR'] .":".@$_SERVER['SERVER_PORT'];
;echo '      )</td>
    <td width="124" height="20" bgcolor="#FFFFFF" class="STYLE2">最新BsPhp版本：</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">Bsphp ';echo $pieces[4] ;echo ' (<a href="index.php?m=upgrade&c=admin_upgrade&a=index">立即升级</a>)</td>
  </tr>
  <tr>
    <td width="154" bgcolor="#FFFFFF" class="STYLE2">程序安装目录：</td>
    <td width="372" bgcolor="#FFFFFF" class="STYLE2">';
echo dirname(dirname($_SERVER['SCRIPT_FILENAME']));
;echo '</td>
    <td width="124" height="20" bgcolor="#FFFFFF" class="STYLE2">当前BsPhp版本：</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">Bsphp <a href="http://www.bsphp.com" target="_blank">';       echo BSPHP_VERSION ;  if(defined('OR_BS_SERVER')){echo '  BAE/SAE模式';}    echo ' (';echo BSPHP_VERSION_ID ;echo ')</a></td>
  </tr>
  <tr>
    <td width="154" bgcolor="#FFFFFF" class="STYLE2">Web服务器：</td>
    <td width="372" bgcolor="#FFFFFF" class="STYLE2">';
echo $_SERVER['SERVER_SOFTWARE']
;echo '</td>
    <td width="124" height="20" bgcolor="#FFFFFF" class="STYLE2">技术团队</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2">彩虹网络工作室</td>
  </tr>
</table>
<br>
<table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">
  <thead> <tr>
    <th width="150" height="26" class="STYLE2">项目</th>
    <th height="26" class="STYLE2">信息</th>
  </tr> </thead>
  <tr>
    <td width="150" height="20" bgcolor="#FFFFFF" class="STYLE2">上次登陆时间：</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2"><span class="STYLE2 STYLE10">
      ';
echo  get_session_value('ADMIN_DATE') ;
;echo '    </span></td>
  </tr>
  <tr>
    <td width="150" height="20" bgcolor="#FFFFFF" class="STYLE2">上传登录的IP:</td>
    <td height="20" bgcolor="#FFFFFF" class="STYLE2"><span class="STYLE2 STYLE10">
      ';
echo ip(get_session_value('ADMIN_IP')) ;
;echo '    </span></td>
  </tr>
</table>
<p>&nbsp;&nbsp;&nbsp;首次安装请将系统设置里面配置好正确系统路径</p>
<p>&nbsp;&nbsp;&nbsp;想要共赢;共同遵守合法权益,拒绝盗版、破解、侵权。(希望在开发者们在自己软件也能加上,一起宣传。)</p>

<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms ';echo BSPHP_VERSION;;echo '</a>   Bsphp.com <br>
All Rights Reserved </div>
</body>
</html>';?>
