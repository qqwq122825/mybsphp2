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
<!--翻页-->
<script language="javascript">
   var psys;
   window.onload = function() {
    psys = new PageSystem({$zongshu}, "pageDiv", 10, showMsg,{$FANYE}); //总记录数, 分页系统容器，每组10页,回调,位置
    psys.init();
  }
  function showMsg(currentPage, pageSize) {
	location="{$url}"+currentPage;
  }
</script>
</head>
<!--
<?php
print <<< EOT
-->
<body leftmargin='8' topmargin='8'>
<table width="580" border="0" cellpadding="0" cellspacing="0" align="center">
  <form name="form" action="" method="POST"  onClick="return allcheckbox();"   >

    <tr bgcolor="#FFFFFF"> 
      <td width="14%" height="20" align="right" nowrap>添加账号：</td>
      <td width="86%" height="20" nowrap><input name="user" type="text" id="user" value="{$uesr}" size="25">
      *<span class="at">{$at_a}</span> </td>
    </tr>
    <tr bgcolor="#FFFFFF">
      <td align="right" nowrap>密码：</td>
      <td nowrap><input name="pass" type="text" id="pass" value="{$pass}" size="25">
     * <span class="at">{$at_b}</span>  </td>
    </tr>
    <tr bgcolor="#FFFFFF">
      <td align="right" nowrap>安全码：</td>
      <td nowrap ><input name="pass_key" type="text" id="pass_key" value="{$pass_key}" size="15">
        * <span class="at">{$at_c} </span></td>
    </tr>
    <tr bgcolor="#FFFFFF">
      <td align="right" nowrap>&nbsp;</td>
      <td nowrap bgcolor="#FFFFFF">用户权限
      </div><input name="all" type="hidden" id="all">
      <a href="javascript:"  onmousedown="A.checked=true;B.checked=true;C.checked=true;D.checked=true;E.checked=true;F.checked=true;P.checked=true;" >全选</a> <a href="javascript:" onMouseDown="A.checked=0;B.checked=0;C.checked=0;D.checked=0;E.checked=0;F.checked=0;P.checked=0;" >取消</a> </td>
    </tr>
	    <tr bgcolor="#FFFFFF">
      <td height="26" align="right">管理权限：</td>
      <td><label>
        <input name="A" type="checkbox" id="A" value="A" checked  {$checkedarray['A']}>
        A.进入后台权限</label>
          <label>
            <input name="B" type="checkbox" id="B" value="B" {$checkedarray['B']}>
            B.系统</label>
          <label>
            <input name="C" type="checkbox" id="C" value="C" {$checkedarray['C']}>
            C.用户</label>
          <label>
            <input name="D" type="checkbox" id="D" value="D" {$checkedarray['D']}>
            D.软件</label>
          <label>
            <input name="E" type="checkbox" id="E" value="E" {$checkedarray['E']}>
            E.前台</label>
        <label>
            <input name="F" type="checkbox" id="F" value="F" {$checkedarray['F']}>
          F.工具 <br>
          <br>
        </label>
          <input name="P" type="checkbox" id="P" value="P" {$checkedarray['P']}>
P.插件 <br>
          <br>
        A.进入后台权限 <br>
        B.操作《系统》权限 <br>
        C.操作《用户》权限 <br>
        D.操作《软件》权限 <br>
        E.操作《前台》权限 <br>
F.操作《工具》权限<br>
P.操作《插件》权限
</label>
<br>
<p>如果用户没有B 权限就无法操作《系统》目录下项目<br>
    如果用户没有A 权限就无法进入后台</p>
  <p>&nbsp;</p></td>
    </tr>
    <tr> 
      <td height="20" colspan="2" align="center">
   	    <div align="center">
   	      <input name="leiadd" type="submit" class="baocun" id="leiadd"  style="width:100px" value="添加">
      </div></td>
    </tr>
  </form>
<script language="javascript">
function insertLabel(strLabel){
	stafrm.zzz.focus();
	var str = document.selection.createRange();
	str.text = strLabel
}
</script>


<!--
EOT;
?>
-->
  <tr bgcolor="#EEF4EA"> 
    <td colspan="4"><table width="100%">
      <tr>
        <td align="center" bgcolor="#ECEFF4"><div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div></td>
      </tr>
    </table></td>
  </tr>
</table>
