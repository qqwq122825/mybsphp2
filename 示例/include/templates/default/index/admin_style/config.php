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


<style type="text/css">
<!--
.STYLE7 {color: #FF0000}
.STYLE9 {color: #009933}
-->
</style>
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">模板列表</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>

<p>
  <!--  内容列表   -->
</p>
<form action=""  name="form"  method="post"    onClick="return allcheckbox();"  >
  <table class="layui-table"  style="width:98%;margin-left: 10px;"  lay-filter="demoEvent"><thead>
    <tr>
      <th width="25%" class="STYLE1"><div align="center" class="STYLE2">分类</div></th>
      <th width="32%" class="STYLE1"><div align="center" class="STYLE2">文件类型</div></th>
      <th width="35%" height="26" class="STYLE1"><div align="center" class="STYLE2">时间</div></th>
      <th width="8%" height="26" class="STYLE1"><div align="center" class="STYLE2">编辑</div></th>
    </tr></thead>
    <!--
EOT;


$fis_Url= BSPHP_DIR_SYS.'include/templates/'.bs_lib::get_configs_value('sys','user_template').'/index/cms_skn';

$while_id=NULL;
$html=NULL;
$html_txt=NULL;
$handle = opendir($fis_Url);
while ($FileName = readdir($handle))
{
if (strstr($FileName,'Bsphp_'))
{
$while_id++;
$html=file_get_contents($fis_Url.'/'.$FileName);

$xianshi='未知文件';
if(isset($array_info[$FileName])){
$xianshi=$array_info[$FileName];
$XP='系统文件';

}else{


$XP='自定义文件';


}


$a=filemtime($fis_Url.'/'.$FileName);
$xianshi=date("Y-m-d H:i:s",$a);
		
                print <<< EOT
-->
    <tr>
      <td nowrap bgcolor="#FFFFFF" class="STYLE2"><div align="center" class="STYLE2 STYLE1" >{$FileName}</div></td>
      <td nowrap bgcolor="#FFFFFF" class="STYLE2"><div align="center" class="STYLE2 STYLE1" >
          <div align="center">{$XP}</div>
      </div></td>
      <td height="18" nowrap bgcolor="#FFFFFF" class="STYLE2"><div align="center" class="STYLE2 STYLE1" >{$xianshi}</div></td>
      <td height="18" nowrap bgcolor="#FFFFFF"><div align="center"><a href="index.php?m=index&c=admin_style&a=set&file=$FileName"  >编辑</a></div></td>
    </tr>
    <!--		  
EOT;
}
            }

            print <<< EOT
-->
  </table>
</form>





<p><span class="STYLE1">默认模板路径:{$fis_Url}/  修改前建议先手动备份</span></p>




<!--
EOT;
?>
-->


<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>
</body>
</html>