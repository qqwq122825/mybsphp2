

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="<?php echo get_url_statics() ?>css/admin_center.css" type="text/css" />
<!--
<?php
$get_url_statics=get_url_statics();
print <<< EOT
-->
<title>编辑</title>
<style type="text/css">
<!--
#form1 textarea {
	height: 300px;
	width: 99%;
}
.STYLE4 {
	font-size: 13px;
	font-weight: bold;
	color: #1F4A65;
}
-->
</style>
</head>
<body>
<label></label>
 <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td height="30"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="15" height="30"><img src="{$get_url_statics}images/main_01.gif" width="15" height="30" /></td>
        <td  background="{$get_url_statics}images/main_02.gif"><img src="{$get_url_statics}images/center_ico01.gif" width="16" height="16" /><span class=""><span class="STYLE4">折扣调整</span></span></td>
        <td width="281" background="{$get_url_statics}images/main_02.gif"><table border="0" align="right" cellpadding="0" cellspacing="0">
        </table></td>
        <td width="14"><img src="{$get_url_statics}images/main_03.gif" width="14" height="30" /></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="9" background="{$get_url_statics}images/main_04.gif">&nbsp;</td>
        <td bgcolor="#f3ffe3"><table width="99%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#c0de98">
          <tr>
            <td width="100%" height="100%" bgcolor="#FFFFFF" ><form id="form1" name="form1" method="post" action="">

				<p>&nbsp;</p>
				<table width="736" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="134" nowrap class="STYLE2"><div align="right">账号:</div></td>
                    <td width="496" nowrap><label class="STYLE4">{$_GET['users']}<span class="sosodiv">
                      <input name="users" type="hidden" id="users" value="{$_GET['users']}">
                    </span></label></td>
                    <td width="106" nowrap>&nbsp;</td>
                  </tr>
                  <tr>
                    <td nowrap class="STYLE2"><div align="right">价格基础加:</div></td>
                    <td nowrap class="STYLE4"><input name="money" type="text" id="money" value="{$array_user['user_Zhe']}" size="10" maxlength="5">
                    1为加1折:10块钱+1块钱,可以负为减</td>
                    <td nowrap>&nbsp;</td>
                  </tr>
                  <tr>
                    <td nowrap class="STYLE2"><div align="right"></div></td>
                    <td  ><label class="STYLE4">调整折扣后代理商将在代理价格添加或减少折扣</label></td>
                    <td nowrap>&nbsp;</td>
                  </tr>
                  <tr>
                    <td nowrap>&nbsp;</td>
                    <td nowrap>&nbsp;</td>
                    <td nowrap><label></label></td>
                  </tr>
                  <tr>
                    <td nowrap>&nbsp;</td>
                    <td nowrap><label>
                      <input type="submit" id="admin" name="ok" value="提交">
                      
                    </label></td>
                    <td nowrap><label></label></td>
                  </tr>
                </table>
				</label>
                        <p>&nbsp;</p> 
                        <p>&nbsp;</p>
                        <p>&nbsp;</p>
            </form>
            </td>
          </tr>
		  
        </table></td>
        <td width="9" background="{$get_url_statics}images/main_06.gif"></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="29"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="15" height="29"><img src="{$get_url_statics}images/main_07.gif" width="15" height="29" /></td>
        <td width="100%" background="{$get_url_statics}images/main_08.gif">&nbsp;</td>
        <td width="14"><img src="{$get_url_statics}images/main_09.gif" width="14" height="29" /></td>
      </tr>
    </table></td>
  </tr>
</table>
</body>
</html>
<!--
EOT;
?>
-->