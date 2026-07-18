<!--
<?php
print <<< EOT
-->
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>BSPHP验证系统  by Bsphp</title>
<!--系统CSS-->

 <link rel="stylesheet" href="../statics/default/admin/layui/css/layui.css?t=1515376178709" media="all">
 <script src="../statics/default/admin/layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="../statics/default/admin/skin/style/base.css">
<script src="../statics/default/admin/skin/js/table.js"></script>
<script src="../statics/default/admin/skin/js/pageSystem.js"></script>
<style type="text/css">
<!--
.STYLE1 {color: #FF0000}
-->
</style>
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable">
  <div id="texttale"  class="texttale">栏目添加</DIV>
  [<a href="javascript:window.location.reload();">+</a>]</div>
<!--  搜索  -->
<!--  内容列表   -->
<form action=""    class="layui-form" name="form"  method="post"    onClick="return allcheckbox();"  >

  
 <table class="layui-table"  style="width:500px;margin-left: 30%;" lay-filter="demoEvent">
    <tr bgcolor="#FFFFFF">
      <td width="20%" align="right"><div align="right">ID：</div></td>
      <td width="80%"><input class="layui-form-label"  name="class" type="text" id="class" value="{$class}" size="25">
          <span class="STYLE8"><span class="STYLE1">{$notclass}</span>(数字)</span></td>
    </tr>
    <tr bgcolor="#FFFFFF">
      <td align="right"><div align="right">名称：</div></td>
      <td><p>
          <input  class="layui-form-label"   name="name" type="text" id="name" value="{$name}" size="20">
          <span class="STYLE1">{$notname}</span><span class="STYLE8"></span></p></td>
    </tr>
    <tr>
      <td height="20" colspan="2"  align="center"><div align="center">
        <input  name="ok" type="submit" class="layui-btn layui-btn-normal" id="ok" style="width:100px" value="添加栏目">
      </div></td>
    </tr>

  
  </table>
</form>






<!--
EOT;
?>
-->


<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>
</body>
</html>