<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>底部 BY:BSPHP</title>
<script>if( window.top == window.self ){window.opener=null;window.close();}</script>


<style type="text/css">
<!--
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
.STYLE1 {
	color: #43860c;
	font-size: 12px;
}
-->
</style></head>

<body>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td height="24" background="<?php echo get_url_statics() ?>images/main_47.gif"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="29" height="24"><img src="<?php echo get_url_statics() ?>images/main_45.gif" width="29" height="24" /></td>
        <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="500"><span class="STYLE1">上次登陆在:<?php echo $this->user_array[15] ?> IP地址:<?php echo ip($this->user_array[14]) ?></span></td>
            
            <td width="400" nowrap="nowrap" class="STYLE1"><div align="center"><img src="<?php echo get_url_statics() ?>images/main_51.gif" width="12" height="12" /> 代理商管理平台 我的点数:<?php
            echo  $this->user_array[10].'点';
			
			
			if($this->user_array['user_daili']==1){
			
			$this->user_array[19]==0?$zhe='':$zhe='  制卡折扣:'.$this->user_array[19].'折';
			echo $zhe;
			}
	
?>  <a href="index.php?m=agent&c=main&a=down" class="STYLE1">刷新</a></div></td>
          </tr>
        </table></td>
        <td width="14"><img src="<?php echo get_url_statics() ?>images/main_49.gif" width="14" height="24" /></td>
      </tr>
    </table></td>
  </tr>
</table>
</body>
</html>
