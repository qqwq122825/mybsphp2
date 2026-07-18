<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>INFO</title>
<link rel="stylesheet" href="<?php echo get_url_statics() ?>css/admin_center.css" type="text/css" />

<script>
var  highlightcolor='#eafcd5';
//此处clickcolor只能用win系统颜色代码才能成功
var  clickcolor='#51b2f6';
function  changeto(){
source=event.srcElement;
if  (source.tagName=="TR"||source.tagName=="TABLE")
return;
while(source.tagName!="TD")
source=source.parentElement;
source=source.parentElement;
cs  =  source.children;
//alert(cs.length);
if  (cs[1].style.backgroundColor!=highlightcolor&&source.id!="nc"&&cs[1].style.backgroundColor!=clickcolor)
for(i=0;i<cs.length;i++){
	cs[i].style.backgroundColor=highlightcolor;
}
}

function  changeback(){
if  (event.fromElement.contains(event.toElement)||source.contains(event.toElement)||source.id=="nc")
return
if  (event.toElement!=source&&cs[1].style.backgroundColor!=clickcolor)
//source.style.backgroundColor=originalcolor
for(i=0;i<cs.length;i++){
	cs[i].style.backgroundColor="";
}
}

function  clickto(){
source=event.srcElement;
if  (source.tagName=="TR"||source.tagName=="TABLE")
return;
while(source.tagName!="TD")
source=source.parentElement;
source=source.parentElement;
cs  =  source.children;
//alert(cs.length);
if  (cs[1].style.backgroundColor!=clickcolor&&source.id!="nc")
for(i=0;i<cs.length;i++){
	cs[i].style.backgroundColor=clickcolor;
}
else
for(i=0;i<cs.length;i++){
	cs[i].style.backgroundColor="";
}
}
</script>
<style type="text/css">
<!--
.STYLE7 {
	color: #FF0000;
	font-weight: bold;
}
.STYLE9 {color: #0066FF}
.STYLE10 {color: #FF0000}
-->
</style>
</head>

<body>
<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td height="30"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="15" height="30"><img src="<?php echo get_url_statics() ?>images/main_01.gif" width="15" height="30" /></td>
        <td width="1101" background="<?php echo get_url_statics() ?>images/main_02.gif"><img src="<?php echo get_url_statics() ?>images/center_ico01.gif" width="16" height="16" /> <span class="STYLE1">信息</span></td>
        <td width="281" background="<?php echo get_url_statics() ?>images/main_02.gif"><table border="0" align="right" cellpadding="0" cellspacing="0">
        </table></td>
        <td width="14"><img src="<?php echo get_url_statics() ?>images/main_03.gif" width="14" height="30" /></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="9" background="<?php echo get_url_statics() ?>images/main_04.gif">&nbsp;</td>
        <td bgcolor="#f3ffe3"><table width="99%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#c0de98" onMouseOver="changeto()"  onmouseout="changeback()">
          <tr>
            <td width="109" height="26" background="<?php echo get_url_statics() ?>images/main_05.gif" class="STYLE2">项目</td>
            <td width="770" height="26" background="<?php echo get_url_statics() ?>images/main_05.gif" class="STYLE2">信息</td>
          </tr>
          <tr>
            <td height="20" bgcolor="#FFFFFF" class="STYLE2">点数：</td>
            <td height="20" bgcolor="#FFFFFF" class="STYLE2"><?php echo $this->user_array[10]  ?> 点 </td>
          </tr>
          <tr>
            <td height="20" bgcolor="#FFFFFF" class="STYLE2">卡串数量库存：</td>
            <td height="20" bgcolor="#FFFFFF" class="STYLE2"><?php 
			
			
	$sql = "select count(*)as'hangshu'from`bs_php_cardseries`WHERE`car_admin`='{$this->user_array['user_uid']}'";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    echo (int)$tmp_arr['hangshu'];
			
			
			
			 ?> 张 </td>
          </tr>
          <tr>
            <td height="20" bgcolor="#FFFFFF" class="STYLE2">已经激活使用：</td>
            <td height="20" bgcolor="#FFFFFF" class="STYLE2"><?php 
			
			
	$sql = "select count(*)as'hangshu'from`bs_php_cardseries`WHERE`car_admin`='{$this->user_array['user_uid']}'AND`car_IsLock`='1'";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    echo (int)$tmp_arr['hangshu'];
			
			
			
			 ?> 张 </td>
          </tr>
		  <tr>
            <td colspan="2" bgcolor="#FFFFFF" class="STYLE2"><p>&nbsp;</p>
			<!--描述开始  下面是输入描述内容-->

         
         
         
         
         
         
         
			  
			  <!--描述结束  上面是输入描述内容-->
			  </td>
            </tr>
		  

        </table></td>
        <td width="9" background="<?php echo get_url_statics() ?>images/main_06.gif">&nbsp;</td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="29"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="15" height="29"><img src="<?php echo get_url_statics() ?>images/main_07.gif" width="15" height="29" /></td>
        <td width="100%" background="<?php echo get_url_statics() ?>images/main_08.gif"></td>
        <td width="14"><img src="<?php echo get_url_statics() ?>images/main_09.gif" width="14" height="29" /></td>
      </tr>
    </table></td>
  </tr>
</table>


</body>
</html>