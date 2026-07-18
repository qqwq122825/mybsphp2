<?php 
$act=set_get('act');
if($act=='xiangxi'){
$daihao=set_get('daihao');
$tmp = $db->my_class_array('bs_php_appinfo','app_daihao',$daihao,'`app_miaoshu`,`app_name`');

if($tmp){

echo '《',$tmp[1],'的介绍》<br><br> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;    ',$tmp[0];
}else{
echo '没有详细内容哦';
}


}else{


print <<< EOT


<table width="100%" height="59" border="0" cellpadding="0" cellspacing="0" bordercolor="#CCCCCC" onmouseover="changeto()"  onmouseout="changeback()">
  <tr>
    <td width="33" height="28" bgcolor="#F7F7F7">&nbsp;</td>
    <td width="188" bgcolor="#F7F7F7"><div align="center"><strong>软件名称</strong></div></td>
    <td width="596" bgcolor="#F7F7F7"><div align="center"><strong>软件介绍</strong></div></td>
    <td width="69" bgcolor="#F7F7F7">&nbsp;</td>
    <td width="69" bgcolor="#F7F7F7">&nbsp;</td>
  </tr>
  
EOT;
					
			

				$sql="SELECT`app_daihao`,`app_name`,`app_miaoshu`,`app_URL`FROM`bs_php_appinfo`";
				$tmp = $db->Bsphp_my_query($sql);
				
				

				 $id=0;
				while ($app = @mysqli_fetch_array($tmp))
    {
	$miaoshu=str_split($app[2],70);
	$id++;
	
	

	

	


print <<< EOT

			     
  
  <tr>
    <td height="31" nowrap="nowrap"><span class="STYLE2"><strong>{$id}.</strong></span><strong>&nbsp;</strong></td>
    <td nowrap="nowrap"><span class="STYLE4">{$app[1]}</span></td>
    <td nowrap="nowrap"><div align="left" class="pc" id="miaoshu">{$miaoshu[0]}</div></td>
    <td nowrap="nowrap"><a href="space.php?do=app&daihao={$app[0]}&act=xiangxi" >详细</a></td>
    <td nowrap="nowrap"><div align="center"><a href="{$app[3]}" target="#">下载</a></div></td>
  </tr>
  

EOT;

  }

echo '</table>';

}
?>