<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("code","name") ?>- by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css">




</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable">
  <div id="texttale"  class="texttale">正在自动升级</DIV>
  [<a href="index.php?m=admin&c=addurl&a=index&url={$URL}">+</a>] </div>
  
<!--  快捷  -->
<!--  内容列表   -->
<form action="index.php?m=upgrade&c=admin_upgrade&a=up&begin=go"  name="form"  method="post"    onClick="return allcheckbox();"  >
  <table class="layui-table"  style="width:98%;margin-left: 10px;"  lay-filter="demoEvent">
    <thead>
      <tr>
        <th width="150" >升级文件名</th>
        <th width="300" >文件MD5</th>
        <th width="150" >系统版本</th>
        <th width="150" >版本号</th>
        <th width="150" >日期</th>
        <th width="150" >文件完整验证</th>
        <th width="150" >文件大小(字节)</th>
        <th width="150" >已经下载大小(字节)</th>
        <th width="*" >已完成下载进度</th>
      </tr>
    </thead>
    <tbody>
	
	

	
	
	
      <tr>
        <td width="150"><?php echo $de_json[0]['filename']?></td>
        <td width="300"><?php echo $de_json[0]['md5'];; ?></td>
        <td width="150"><?php echo $de_json[0]['v']?></td>
        <td width="150"><?php echo $de_json[0]['number']?></td>
        <td width="150"><?php echo $de_json[0]['date']?></td>
        <td width="150"><div id="md5">文件验证中</div></td>
        <td width="150"><div id="filesize"></div></td>
        <td width="150"><font color="red">
        <div id="downloaded">0</div></font></td>
        <td><font color="red"><div id="jd" style="float:left;width:1px; text-align:center; color:#FFFFFF; background-color:#0066CC"></div><div id="bf" style=" float:left">0%</div></font></td>
      </tr>
    </tbody>
  </table>
  <p>系统正在自动升级Bsphp系统,在此过程中请勿关闭网页,如果长时间停顿可以尝试刷新！</p>
</form>
<script language="javascript">
select_set('stop',<?php echo (int)bs_lib::get_configs_value('code','stop') ?>);
</script>
<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a> Bsphp.com <br>
  All Rights Reserved </div>
  
  <!--文件计算、进度显示-->
<script type="text/javascript">
   //文件长度
    var filesize=0;
    function $(obj)
	{
        return document.getElementById(obj);
    }
	//设置文件长度
   function setFileSize(fsize)
   {
     filesize=fsize;
	 
     $("filesize").innerHTML=fsize;
   }
   
	//设置文件长度
   function md5(md5)
   {

	 
     $("md5").innerHTML='<font color="red">'+md5+'</font>';
   }
   
   
	//设置已经下载的,并计算百分比
   function setDownloaded(fsize)
   {
		$("downloaded").innerHTML=fsize;
		if(filesize>0)
		{
			var percent=Math.round(fsize*100/filesize);
			$("jd").style.width=(percent+"%");
			if(percent>0)
			{
				$("jd").innerHTML=percent+"%";
				$("bf").innerHTML="";
			}
			else
			{
				$("bf").innerHTML=percent+"%";
			}
    
		}
   }

</script>

</body>
</html>
