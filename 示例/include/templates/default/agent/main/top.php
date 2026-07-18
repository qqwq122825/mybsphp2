<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Top</title>
<link rel="stylesheet" href="<?php echo get_url_statics() ?>css/admin.css" type="text/css" />
<script>if( window.top == window.self ){window.opener=null;window.close();}</script>
</head>
<body>
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="table-layout:fixed;">
  <tr>
    <td height="9" style="line-height:9px; background-image:url(<?php echo get_url_statics() ?>images/top_02.gif)"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="97" height="9" background="<?php echo get_url_statics() ?>images/top_01.gif">&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="47" background="<?php echo get_url_statics() ?>images/top_03.gif"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="38" height="47" background="<?php echo get_url_statics() ?>images/top_04.gif">&nbsp;</td>
        <td width="59"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="29" background="<?php echo get_url_statics() ?>images/top_05.gif">&nbsp;</td>
          </tr>
          <tr>
            <td height="18" background="<?php echo get_url_statics() ?>images/top_06.gif"><table width="100%" border="0" cellspacing="0" cellpadding="0" style="table-layout:fixed;">
              <tr>
                <td  style="width:1px;">&nbsp;</td>
                <td ><span class="STYLE3"><?php

echo $this->user_array['user_user'];

?></span></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
        <td width="155" background="<?php echo get_url_statics() ?>images/top_07.gif">&nbsp;</td>
        <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="23" valign="bottom"><img src="<?php echo get_url_statics() ?>images/top_08.gif" width="367" height="23" border="0" usemap="#Map" /></td>
          </tr>
        </table></td>
        <td width="200" background="<?php echo get_url_statics() ?>images/top_09.gif"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="11%" height="23">&nbsp;</td>
            <td width="89%" valign="bottom">
			<span class="STYLE3">日期：
			<?php

date_default_timezone_set('Asia/Shanghai');
$a = array("日", "一", "二", "三", "四", "五", "六");
echo date("Y年m月d日" . " " . "星期" . $a[date('w')]);

?>
			</span>
			</td>
          </tr>
        </table></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="5" style="line-height:5px; background-image:url(images/top_10.gif)"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="180" background="<?php echo get_url_statics() ?>images/top_11.gif"  style="line-height:5px;">&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    </table></td>
  </tr>
</table>

<map name="Map" id="Map">
  <area shape="rect" coords="5,0,74,19" href="<?php bs_lib::get_configs_value('sys','url'); ?>" target="_blank" />
  <area shape="rect" coords="80,2,143,21" href="index.php?m=agent&c=main&a=info" target="centerFrame" />
  <area shape="rect" coords="150,2,197,21" href="index.php?m=agent&c=main&a=info" target="centerFrame" />
  <area shape="rect" coords="208,1,303,20" href="index.php?m=agent&c=sp&a=password" target="centerFrame" />
  <area shape="rect" coords="309,2,362,21" href="index.php?m=agent&c=index&a=loginout" target="_top" />
</map>

</body>
</html>
