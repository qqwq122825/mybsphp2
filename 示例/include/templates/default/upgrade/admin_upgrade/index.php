<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("code","name") ?>- by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css">
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/table.js"></script>
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable">
  <div id="texttale"  class="texttale">系统自动升级</DIV>
  [<a href="index.php?m=admin&c=addurl&a=index&url={$URL}">+</a>] </div>
  
<!--  快捷  -->
<!--  内容列表   -->
<form action="index.php?m=upgrade&c=admin_upgrade&a=up_down&begin=go"  name="form"  method="post"    onClick="return allcheckbox();"  >
  <table class="layui-table"  style="width:98%;margin-left: 10px;"  lay-filter="demoEvent">
    <thead>
      <tr>
        <th width="*" >远程升级文件名</th>
        <th width="*" >文件MD5</th>
        <th width="*" >系统版本</th>
        <th width="*" >版本号</th>
        <th width="*" >日期</th>
        <th width="*" >升级内容</th>
      </tr>
    </thead>
    <tbody>
	
	
	
<?php

 if(!defined('OR_BS_SERVER')){

        for ($i = 0; $i < $count_json; $i++)
           {
?>
	
	
	
      <tr>
        <td><?php echo $de_json[$i]['filename'];; ?></td>
        <td width="350"><?php echo $de_json[$i]['md5'];; ?></td>
        <td><?php echo $de_json[$i]['v'];; ?></td>
        <td><?php echo $de_json[$i]['number'];; ?></td>
        <td><?php echo $de_json[$i]['date'];; ?></td>
        <td><?php echo $de_json[$i]['info'];; ?></td>
      </tr>
<?php

     }}
?>
    </tbody>
    <tfoot>
      <tr>
        <th></th>
        <th></th>
        <th></th>
        <th><?php
        
        
        
        if(defined('OR_BS_SERVER')){
            echo 'BAE/SEA模式无法自动更新,如有新版本请手动更新！';
        }else{
		
		
		if($count_json<=0){
		
		   echo '已经是最新版本,不需要升级！';
		}else{
		
		   echo '<input id="admin" type="submit"  name="appenconfig" class="layui-btn layui-btn-normal"  value="一键升级">';
		}}
		 ?></th>
        <td></th>
        <td></th>
        </th>
      </th>      </tr>
    </tfoot>
  </table>
</form>
<script language="javascript">
select_set('stop',<?php echo (int)bs_lib::get_configs_value('code','stop') ?>);
</script>
<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a> Bsphp.com <br>
  All Rights Reserved </div>
</body>
</html>
