<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?> - by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css">
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/table.js"></script>
<!--
<?php
print <<< EOT
-->
</head>
<body leftmargin='8' topmargin='8'>

<!--  标题  -->

<div class="nametable"><div id="texttale"  class="texttale">编辑用户分组信息</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>
<!--  快捷  -->
<div id="Shortcut">
  <ul>
	      <li><a href="index.php?m=applib&c=admin_grouping&a=table&daihao={$app_daihao}">  返回</a></li>



  </ul>
</div>




  <form name="form1" class="layui-form"  action="" method="POST" >
<table class="layui-table"  style="width:98%;margin-left: 10px;"  lay-filter="demoEvent">
    <tr > 
      <td width="20%" height="52" align="right">软件名：</td>
      <td width="80%" height="52><input name="userclass_daihao" type="hidden" id="userclass_daihao" value="{$app_daihao}">
      $app_name (代号:$app_daihao) </td>
    </tr>
    <tr >
      <td align="right">ID：</td>
      <td><input name="userclass_id" type="hidden" id="userclass_id"  class="layui-form-label"   value="{$array['class_id']}">
      {$array['class_id']}</td>
    </tr>
    <tr >
      <td align="right">分类名称：</td>
      <td><input name="userclass_name" type="text" id="userclass_name"  class="layui-form-label"  value="{$array['class_name']}" size="25">
        分类名称</td>
    </tr>
    <tr >
      <td align="right">分类代号：</td>
      <td><input name="userclass_mark" type="text" id="userclass_mark"  class="layui-form-label"   value="{$array['calss_mark']}" size="25"> 
      分类别名,用做软件读取,文字对软件不好</td>
    </tr>
    <tr> 
      <td height="20" colspan="2" align="center">
      	<div align="center">
      	  <input name="leiadd" type="submit"    class="layui-btn layui-btn-normal"    id="leiadd" style="width:100px" value="修改分类"> 
   	    <span class="STYLE8">*必填</span> </div></td>
    </tr>

  
</table>
<div id="foot">Copyright 2009-2018 <a href="http://www.bsphp.com" target="_blank">Bsphp验证系统 <?php echo BSPHP_VERSION; ?></a>  Bsphp.com <br>
All Rights Reserved </div>



  </form>
<script language="javascript">
function insertLabel(strLabel){
	stafrm.zzz.focus();
	var str = document.selection.createRange();
	str.text = strLabel
}
</script>

<script language="javascript">
select_set('lei_type',{$lei_type});

</script>

<!--
EOT;
?>
-->
</body>
</html>
