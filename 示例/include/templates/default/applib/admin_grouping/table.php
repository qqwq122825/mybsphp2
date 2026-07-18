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
<!--
<?php
print <<< EOT
-->
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">用户分组{$app_name}</DIV>
[<a href="javascript:window.location.reload();">+</a>]</div>



<!--  快捷  -->
<div id="Shortcut">
  <ul>
    <li><a href="index.php?m=applib&c=admin_app&a=appini&id={$app_daihao}" > 配置软件</a></li>
    <li><a href="index.php?m=applib&c=admin_user&a=table&daihao={$app_daihao}"  > 用户列表</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=table&daihao={$app_daihao}">卡串列表</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=make&daihao={$app_daihao}"> 制卡</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=class&daihao={$app_daihao}">卡串类型 </a></li>
    <li><a href="index.php?m=applib&c=admin_grouping&a=table&daihao={$app_daihao}">用户分组 </a></li>
          <li>|</li>
          <li><a href="index.php?m=applib&c=admin_grouping&a=add&daihao={$daihao}"   >添加新分组</a></li>
  </ul>
</div>

<!--  内容列表   -->
<form action=""   class="layui-form" name="form"  lay-filter="baocun"   method="post"  >
<table class="layui-table"  style="overflow: scroll;word-break: keep-all;width:98%;margin-left: 10px;">

 <thead>
<tr align="center" bgcolor="" height="52">
                  <th  width="*">ID</th>
                    <th  width="*">分组名称</th>
                    <th    width="*">代号</th>
                    <th   width="*"></th>
</tr></thead>
			  <!--
EOT;


       while ($array_value = mysqli_fetch_array($db_array_value ))
            {

			
			
		
                print <<< EOT
-->





<tr align='center'   height="22" >

                    <td>{$array_value['class_id']}</td>
                    <td>{$array_value['class_name']}</td>
                    <td>{$array_value['calss_mark']}</td>
                    <td><a href="index.php?m=applib&c=admin_grouping&a=modify&daihao={$app_daihao}&id={$array_value[0]}"   >修改</a> | <a href="index.php?m=applib&c=admin_grouping&a=table&daihao={$app_daihao}&delete={$array_value[0]}"  onclick="return confirm('你确认要删除吗？')">删除</a></td>
</tr>

<!--		  
EOT;
            }

            print <<< EOT
-->
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
