<?php
	/*
'**************************************************************************************************
' 软件名称: 彩虹网络验证系统 Www.BsPhp.Com

' 官方网站: http://Www.BsPhp.Com
' 官方论坛：http://bbs.qicaihong.cc
' 版权所有: 七彩虹工作室   bu:小周
' 郑重声明:
'    1、任何个人或组织不得在未经授权的情况下删除、修改、拷贝本软件及其他副本上一切关于版权的信息；
'    2、作者保留此软件的法律追究权利
'**************************************************************************************************
*/
  



echo '	

<style type="text/css">
<!--
.STYLE2 {color: #B1C3D9}
.STYLE3 {color: #666666}
.STYLE4 {
	color: #3B5998;
	font-weight: bold;
}
-->
</style>
<table width="100%" height="59" border="0" cellpadding="0" cellspacing="0" bordercolor="#CCCCCC" onmouseover="changeto()"  onmouseout="changeback()">
  <tr>
    <td width="33" height="28" bgcolor="#F7F7F7">&nbsp;</td>
    <td width="117" bgcolor="#F7F7F7"><div align="center"><strong>用户账号</strong></div></td>
    <td width="268" bgcolor="#F7F7F7"><div align="center"><strong><a href="space.php?do=top">积分榜</a></strong>&nbsp; &nbsp; </div></td>
    <td width="451" bgcolor="#F7F7F7"><div align="center"><strong><a href="space.php?do=top&amp;lei=2">邀请人数</a></strong></div></td>
  </tr>';
  
  
  
                $lei=set_get('lei');
				$zi="user_jifen";
				if($lei){
				$zi="user_yao_Shu";
				}
				$sql="SELECT`user_user`,`user_jifen`,`user_yao_Shu`FROM`bs_php_user`WHERE`user_jifen`>'0' OR `user_yao_Shu`>'0'  ORDER BY`$zi`DESC LIMIT 0 , 10";
				$tmp = $db->Bsphp_my_query($sql);
	    		 $id=0;
				while ($app = @mysqli_fetch_array($tmp))
    {
        
        $cheng=strlen($app['user_user']);
        $cheng=$cheng-2;
        if($cheng<=0) $cheng=$cheng+2;
        $arr_user_user=str_split($app['user_user'],$cheng);
        
	$id++;

  print <<< EOT
  <tr>
    <td height="31" nowrap="nowrap"><span class="STYLE2"><strong>{$id}</strong></span><strong>&nbsp;</strong></td>
    <td nowrap="nowrap"><span class="STYLE4">{$arr_user_user[0]}**</span></td>
    <td nowrap="nowrap"><div align="center"><span class="STYLE3">{$app['user_jifen']}</span></div></td>
    <td nowrap="nowrap"><div align="center"><span class="STYLE3">{$app['user_yao_Shu']}</span></div></td>
  </tr>
EOT;
  }
				
				
echo '				
</table>';
?>