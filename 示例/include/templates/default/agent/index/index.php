<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?PHP ECHO bs_lib::get_configs_value("sys","name"); ?> - 代理商管理平台 by Bsphp</title>
<script>if(self!=top){parent.location.href='index.php';}</script>
<style type="text/css">
<!--
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
	overflow:hidden;
	background-color: #A2D962;
}
#submita {
	background-color: #FFFFFF;
	height: 0px;
	width: 0px;
	display: marker;
}
#submitb {
	background-color: #FFFFFF;
	height: 0px;
	width: 0px;
	display: marker;
}
.STYLE3 {color: #528311; font-size: 12px; }
.STYLE4 {
	color: #42870a;
	font-size: 12px;
}
-->
</style>
 
<SCRIPT language=javascript>
function Checklogin()
{
	if (login.amdin_name.value=="")
	{
		alert("请填写登录账号");
		login.amdin_name.focus();
		return false;
	}
		if (login.admin_password.value=="")
	{
		alert("密码不能为空");
		login.admin_password.focus();
		return false;
	}
}

</SCRIPT>
</head>
</head>
<body>
<form name="login" method="post" action=""  onSubmit="return Checklogin();">
  <table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td bgcolor="#e5f6cf">&nbsp;</td>
    </tr>
    <tr>
      <td height="608" background="<?php echo get_url_statics() ?>skin/images/login_03.gif" bgcolor="#A2D962"><table width="862" border="0" align="center" cellpadding="0" cellspacing="0">
          <tr>
            <td height="266" background="<?php echo get_url_statics() ?>skin/images/login_04.gif" bgcolor="#A2D962">&nbsp;</td>
          </tr>
          <tr>
            <td height="95"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="424" height="95" background="<?php echo get_url_statics() ?>skin/images/login_06.gif">&nbsp;</td>
                  <td width="183" background="<?php echo get_url_statics() ?>skin/images/login_07.gif"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td width="21%" height="20"><div align="center"><span class="STYLE3">用户</span></div></td>
                        <td width="79%" height="20"><input name="amdin_name" type="text" id="amdin_name"  style="height:18px; width:130px; border:solid 1px #cadcb2; font-size:12px; color:#81b432;"></td>
                      </tr>
                      <tr>
                        <td height="20"><div align="center"><span class="STYLE3">密码</span></div></td>
                        <td height="20"><input name="admin_password" type="password" id="admin_password"  style="height:18px; width:130px; border:solid 1px #cadcb2; font-size:12px; color:#81b432;"></td>
                      </tr>
                      <?php if(bs_lib::get_configs_value('imges','coode_login') == true){ ?>
                      <tr>
                        <td height="20"><div align="center"><span class="STYLE3">验证码</span></div></td>
                        <td height="20" nowrap><input name="imga" type="test" id="imga"  style="height:18px; width:50px; border:solid 1px #cadcb2; font-size:12px; color:#81b432;">
                        </td>
                      </tr>
                      
                      
                      <tr>
                        <td height="30">&nbsp;</td>
                        <td height="30"><img class="cimg"  onclick="this.src='index.php?m=coode&time='+new Date().getTime()";  src="index.php?m=coode&time='+new Date().getTime()" alt="验证码" width="120" height="30" border="0" /></td>
                      </tr>
                      
                      	<?php 
								}
								?>
                  </table></td>
                  <td width="255" background="<?php echo get_url_statics() ?>skin/images/login_08.gif">&nbsp;</td>
                </tr>
              </table></td>
          </tr>
          <tr>
            <td height="247" valign="top" background="<?php echo get_url_statics() ?>skin/images/login_09.gif"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="22%" height="30">&nbsp;</td>
                  <td width="32%">&nbsp;</td>
                  <td width="24%"><img src="<?php echo get_url_statics() ?>skin/images/dl.gif" width="81" height="22" border="0" usemap="#Map">
                    <map name="Map">
                      <area shape="rect" coords="3,3,36,19" href="javascript:login.adminlogin.click();">
                      <area shape="rect" coords="40,3,78,18" href="index.php">
                    </map></td>
                  <td width="22%">&nbsp;</td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td height="30" colspan="2"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td width="44%" height="20">&nbsp;</td>
                        <td width="56%" class="STYLE4">&nbsp;</td>
                      </tr>
                    </table></td>
                  <td>&nbsp;</td>
                </tr>
              </table></td>
          </tr>
        </table></td>
    </tr>
    <tr>
      <td bgcolor="#a2d962">&nbsp;</td>
    </tr>
  </table>
  
  <input name="adminlogin" type="submit" id="submita"  value="">
  <input type="reset" id="submitb" name="reset" value="">
</form>
<label>

</label>
</body>
</html>