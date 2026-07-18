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
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">制卡 - {$app_name}</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>
<!--  快捷  -->
<div id="Shortcut">
  <ul>
    <li><a href="index.php?m=applib&c=admin_app&a=appini&id={$app_daihao}" > 配置软件</a></li>
    <li><a href="index.php?m=applib&c=admin_user&a=table&daihao={$app_daihao}"  > 用户列表</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=table&daihao={$app_daihao}">卡串列表</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=make&daihao={$app_daihao}"> 制卡</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=class&daihao={$app_daihao}">卡串类型 </a></li>
    <li><a href="index.php?m=applib&c=admin_grouping&a=table&daihao={$app_daihao}">用户分组 </a></li>
  </ul>
</div>
<!--  搜索  -->
<!--  内容列表   -->
<form action=""  name="form"  method="post"   class="layui-form"     onClick="return allcheckbox();"  >
  <table width="800" border="0" align="center">
    <tr>
      <td  width="134" height="52" nowrap="nowrap" class="STYLE2"><div align="right">所属软件：</div></td>
      <td nowrap="nowrap" class="STYLE2"><label>{$app_name}(代号:$app_daihao)</label></td>
    </tr>
    <tr>
      <td nowrap="nowrap"  height="52" class="STYLE2"><div align="right">卡串类型：</div></td>
      <td width="310" nowrap="nowrap"><label><div class="layui-input-inline" style="width: 300px;">
        <select name="select">
          <option value="0">请选择制作类型。。。</option>
          <!--
EOT;


             $i = 0;
             while ($array_value = mysqli_fetch_array($db_array_value))
            {
			    if($array_value['lei_for']<=0) $array_value['lei_for']=1;
                $i++;
                print <<< EOT
-->
          <option value="{$array_value[0]}">{$array_value[1]}=>{$array_value[2]}点 </option>
          <!--                  
EOT;
}
print <<< EOT
-->
        </select></div>
      </label></td>
    </tr>
    <tr>
      <td nowrap="nowrap"  height="52" class="STYLE2"><div align="right">制作数量：</div></td>
      <td nowrap="nowrap"><div class="layui-input-inline" style="width: 300px;"><input name="shulian"  class="layui-input"  type="text" id="shulian"  /><div></td>
    </tr>
    <tr>
      <td nowrap="nowrap" height="52"  class="STYLE2"><div align="right"></div></td>
      <td nowrap="nowrap">&nbsp;</td>
    </tr>
    <tr>
      <td nowrap="nowrap" height="52"  class="STYLE2">&nbsp;</td>
      <td nowrap="nowrap"><label>
        <input  id="admin"  type="submit" name="Submit_zhi"  class="layui-btn layui-btn-normal"   value="制作" />
        <br />
      </label></td>
    </tr>
    
  </table>
</form>

<script language="javascript">
      var i = {$i};

if(i==0){

  alert("你还没添加卡串类型,请先添加类型如 月 日 测试卡等类型");
  document.location.href="index.php?m=applib&c=admin_card&a=class&daihao={$app_daihao}";
  }
</script>


<!--
EOT;
?>
-->


<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>
</body>
</html>
