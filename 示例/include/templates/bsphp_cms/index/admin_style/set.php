<!--
<?php
$text_html=file_get_contents($name);
print <<< EOT
-->
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>BSPHP验证系统  by Bsphp</title>
<link rel="stylesheet" type="text/css" href="../statics/default/admin/skin/style/base.css">
<script src="../statics/default/admin/skin/js/table.js"></script>
<script src="../statics/default/admin/skin/js/pageSystem.js"></script>
 <link rel="stylesheet" href="../statics/default/admin/layui/css/layui.css?t=1515376178709" media="all">
 <script src="../statics/default/admin/layui/layui.js?t=1515376178709"></script>
<style type="text/css">
<!--
#form1 textarea {
	height: 500px;
	width: 99%;
}
.STYLE3 {font-size: 12px}
.STYLE4 {
	font-size: 13px;
	font-weight: bold;
	color: #1F4A65;
}
-->
</style>
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">编辑模板 - {$table_name}</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>

<p>
  <!--  内容列表   -->
</p>
<form id="form1" name="form1" method="post" action="">
              <label>
              <textarea name="textfield">{$text_html}</textarea>
              </label>
				<label> 
				<div align="center"><br />
				  <input type="submit" id="admin"  class="layui-btn layui-btn-normal"  name="Submit" value="保存" />
				  <br>

				</label>
</form>





<!--
EOT;
?>
-->


<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>
</body>
</html>