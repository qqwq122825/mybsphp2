<!DOCTYPE html>
<html>
<head>
<meta name="renderer" content="webkit" />
<TITLE><?PHP ECHO bs_lib::get_configs_value("sys","name"); ?> - <?php echo $this->GLOBALS_LANGS['logo_table']; ?> By:Bsphp</TITLE>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

<script>if(self!=top){parent.location.href='index.php';}</script>
<META content="text/html; charset=utf-8" http-equiv=Content-Type>
<LINK rel=stylesheet type=text/css href="<?php echo get_url_statics() ?>images/login.css">
 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<!--
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
.login .c .su #adminlogin {
	font-size: 18px;
	font-weight: bolder;
	color: #FFFFFF;
}
-->
</style>
</head>
<body  style="BACKGROUND-COLOR: #f4f7f9">
<DIV class=login>
<P class=pl></P>
<DIV class=l></DIV>
<DIV class=c>
<FORM method="post" name="myform" action="index.php?m=admin"/>
<DIV class=to><SPAN class=tol><A class=v href="#" ></A></SPAN><SPAN class=tor><?PHP ECHO bs_lib::get_configs_value("sys","name") ?><?php echo $this->GLOBALS_LANGS['admin_info'] ?></SPAN></DIV>
<DIV class=in>
<DL>
  <DT><?php echo $this->GLOBALS_LANGS['admin_user']; ?></DT>
  <DD><INPUT name=amdin_name type=text id="amdin_name" style="WIDTH: 150px" placeholder="<?php echo $this->GLOBALS_LANGS['admin_pwd_text']; ?>">
  </DD>
  <DD class=e></DD></DL>
<DL>
  <DT><?php echo $this->GLOBALS_LANGS['admin_pwd']; ?></DT>
  <DD><INPUT name=admin_password type=password id="admin_password" style="WIDTH: 150px" placeholder="<?php echo $this->GLOBALS_LANGS['admin_pwd_text']; ?>">
  </DD>
  <DD class=e></DD></DL>
<DL>
  <DT><?php echo $this->GLOBALS_LANGS['admin_cood']; ?></DT>
  <DD><INPUT name=admin_mibao type=text id="admin_mibao" style="WIDTH: 100px" placeholder="<?php echo $this->GLOBALS_LANGS['admin_cood_text']; ?>">
  </DD>
  <DD class=e></DD></DL>
</DIV>
<DIV class=su><SPAN>



<INPUT name="adminlogin" class="layui-btn layui-btn-normal" type="submit"  id="adminlogin" value="<?php echo $this->GLOBALS_LANGS['admin_login']; ?>">


</SPAN><A 
href="http://www.bsphp.com/" target=_blank>BsPhp@2009-2023</A> | <a href="http://bbs.bsphp.com" target="_blank">Bsphp BBS</a> </DIV>
</FORM></DIV>
<DIV class=r></DIV>
<P class=pr></P></DIV>
</body>
</html>