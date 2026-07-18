<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?> - by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css">
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/table.js"></script>
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/pageSystem.js"></script>
</head>
<!--
<?php
print <<< EOT
-->
<body leftmargin='8' topmargin='8'>
<table width="580" border="0" cellpadding="0" cellspacing="0" align="center">
  <form name="form" action="" method="POST"  onClick="return allcheckbox();"   >

    <tr bgcolor="#FFFFFF"> 
      <td width="15%" height="20" align="right" nowrap>账号：</td>
      <td width="85%" height="20" nowrap><input name="user" type="text" id="user" size="40">
      * 用户账号 建议5-15位</td>
    </tr>
    <tr bgcolor="#FFFFFF">
      <td align="right" nowrap>密码：</td>
      <td nowrap><input name="pwd" type="text" id="pwd" size="40">
     *登录密码 建议不要过于简单</td>
    </tr>
    <tr bgcolor="#FFFFFF">
      <td height="39" align="right" nowrap>联系QQ：</td>
      <td nowrap ><input name="qq" type="text" id="qq" size="25">
      联系,即时与用户交谈</td>
    </tr>
    <tr bgcolor="#FFFFFF">
      <td align="right" nowrap>邮箱：</td>
      <td nowrap bgcolor="#FFFFFF"><input name="mail" type="text" id="mail" size="40"> 
        联系邮箱事务通知与联系</td>
    </tr>
	    <tr bgcolor="#FFFFFF">
      <td height="26" align="right">所属代理：</td>
      <td><label></label><p>
        <input name="yao_user" type="text" id="yao_user" size="40">
        邀请人,用分红系统</p>
  </td>
    </tr>
    <tr> 
      <td height="20" colspan="2" align="center">
   	    <div align="center">
   	      <input name="baocun" type="submit" class="baocun" id="baocun" style="width:100px" value="添加帐号">
   	    </div></td>
    </tr>
  </form>
<!--
EOT;
?>
-->
<script language="javascript">
function insertLabel(strLabel){
	stafrm.zzz.focus();
	var str = document.selection.createRange();
	str.text = strLabel
}
</script>
  <tr bgcolor="#EEF4EA"> 
    <td colspan="4"><table width="100%">
      <tr>
        <td align="center" bgcolor="#ECEFF4"><div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div></td>
      </tr>
    </table></td>
  </tr>
</table>
