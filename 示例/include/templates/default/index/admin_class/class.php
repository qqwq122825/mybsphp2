<!--
<?php
print <<< EOT
-->
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>BSPHP验证系统  by Bsphp</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="../statics/default/admin/skin/style/base.css">
<script src="../statics/default/admin/skin/js/table.js"></script>
<script src="../statics/default/admin/skin/js/pageSystem.js"></script>

</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable">
  <div id="texttale"  class="texttale">栏目列表</DIV>
  [<a href="javascript:window.location.reload();">+</a>]</div>
<!--  搜索  -->
<!--  内容列表   -->
<form action=""  name="form"  method="post"    onClick="return allcheckbox();"  >
  <table width="347" align="center"  onmouseover="changeto()"  onmouseout="changeback()"  class="table">
    <thead>
      <tr>
        <th width="103" height="29" nowrap class="th">ID</th>
        <th width="130" nowrap class="th">栏目名称</th>
        <th width="98" nowrap  class="th"></th>
      </tr>
    </thead>
    <tbody>
      <!--
EOT;

             while ($array_value = mysqli_fetch_array($db_array_value ))
            {

print <<< EOT
-->
      <tr id="1">
        <td nowrap  class="td"   fd="t"><div class="STYLE2" align="center">
            <div align="left">{$array_value[0]}</div>
        </div></td>
        <td nowrap  class="STYLE2"   fd="t">{$array_value[1]}</td>
        <td nowrap class="td"  fd="a"><div class="STYLE2"  align="center"><a href="index.php?m=index&c=admin_class&a=usb&id={$array_value[0]}"    >修改</a> | <a href="index.php?m=index&c=admin_class&a=class&delete={$array_value[0]}"  onclick="return confirm('你确认要删除吗？')">删除</a></div></td>
      </tr>
      <!--
EOT;

    }
	
print <<< EOT
-->
    </tbody>
  </table>
  <div align="left"></div>
</form>






<!--
EOT;
?>
-->


<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>
</body>
</html>