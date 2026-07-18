<!DOCTYPE html>
<html lang="en-gb" dir="ltr" class="uk-height-1-1">
<head>
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?> - by BSPHP</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="<?php echo get_url_statics()?>skin/style/css.css" rel="stylesheet" type="text/css" />
<?php echo $JS ?>
</head>
<body scroll="no">
  <div id="header">
    <div><a style="width:135px;height:66px;float:left;display:block;" href="http://www.bsphp.com"><img  style=" no-repeat;margin-top: 10px;margin-left: 10px;" name="" src="<?php echo get_url_statics()?>skin/images/logo.png" alt="" /></a>
      <div id="innerHeader">
        <div id="innerHeaders"> <a> <?php echo $this->GLOBALS_LANGS['admin_date'] ?><?php echo HOST_DATE ?> | <?php echo $this->GLOBALS_LANGS['admin_name'] ?> <?php echo $admin_global_array['Admin_AdminUserName'] ?> |</a> <a href="../index.php?m=index" target="_blank"><?php echo $this->GLOBALS_LANGS['admin_cms_url'] ?></a><a>|</a> <a href="../user/index.php?m=user" target="_blank"><?php echo $this->GLOBALS_LANGS['admin_user_url'] ?></a><a>|</a><a href="../agent/index.php" target="_blank"><?php echo $this->GLOBALS_LANGS['admin_agent_url'] ?></a><a>|</a><a href="index.php?m=admin&c=index&a=logut"><?php echo $this->GLOBALS_LANGS['admin_logut_url'] ?></a> </div>
        <div id="topguide" class="ddcolortabs">
          <ul id="topguide-entry">
          
          <?php
          
          if(bs_lib::get_configs_value('setmostly','top_1')==0){
            echo '<li><a style="cursor:pointer" href="index.php?m=admin&c=main&a=index">首 页</a></li> ';
          }
          
          if(bs_lib::get_configs_value('setmostly','top_2')==0){
            echo '<li id="xt" onMouseOver="showguide(this.id);"><a style="cursor:pointer" href="javascript:" onClick="return initguide("xt");">系 统</a></li>';
          }
          
          if(bs_lib::get_configs_value('setmostly','top_3')==0){
            echo '<li id="yh" onMouseOver="showguide(this.id);"><a style="cursor:pointer" href="javascript:" onClick="return initguide("yh");">账 号</a></li>
 ';
          }
          
          if(bs_lib::get_configs_value('setmostly','top_4')==0){
            echo '<li id="rj" onMouseOver="showguide(this.id);"><a style="cursor:pointer" href="javascript:" onClick="return initguide("rj");">软 件</a></li>
';
          }
          
          if(bs_lib::get_configs_value('setmostly','top_5')==0){
            echo ' <li id="cj" onMouseOver="showguide(this.id);"><a style="cursor:pointer" href="javascript:" onClick="return initguide("cj");">插 件</a></li>
';
          }
          
          if(bs_lib::get_configs_value('setmostly','top_6')==0){
            echo '<li id="qt" onMouseOver="showguide(this.id);"><a style="cursor:pointer" href="javascript:" onClick="return initguide("qt");">前 台</a></li>
';
          }
?>
          
            <li id="gj" onMouseOver="showguide(this.id);"><a style="cursor:pointer" href="javascript:" onClick="return initguide('gj');">工 具</a></li>

            <!---->
 		
<?php /* while ($row = mysqli_fetch_assoc($listrc)) { ?>




            <li id="<?php echo $row['list_admin_list_top_tree'] ?>" onMouseOver="showguide(this.id);"><a style="cursor:pointer" href="javascript:" onClick="return initguide('<?php echo $row['list_admin_list_top_tree'] ?>');"><?php echo $row['list_admin_list_name'] ?></a></li>
            <!---->
<?php } */ ?>
			


            <!--
            <li id="system" onMouseOver="showguide(this.id);"><a style="cursor:pointer" href="javascript:" onClick="return initguide('system');"><span><?php echo $this->GLOBALS_LANGS['admin_hard'] ?></span></a></li>-->
          </ul>
        </div>
      </div>
      <div class="c"></div>
      <div id="guide"></div>
      <div id="guide2"> </div>
    </div>
  </div>
  <div id="selector">
    <div id="left" class="inner"></div>
  </div>
  <div id="gird">
    <div class="inner">
      <iframe name="main"  scrolling="Yes"  frameborder="0" id="main" style="height:99.9%;visibility:inherit;width:99.95%;z-index:10"></iframe>
    </div>
  </div>
  <!--下拉目录 开始-->
  <div id="footer">
    <div id="innerFooter"></div>
  </div>
  <iframe name="notice" frameborder="0" id="notice" style="height:0;width:0;"></iframe>
  <div id="menu" style="display:none"></div>
  <div id="showmenu" style="display:none"></div>
    <div id="Layer1"><a href="http://www.bsphp.com?" target="_blank">Bsphp V <?php echo BSPHP_VERSION; ?></a>(2018)</div>

<script type="text/javascript"> 











<!-- 
var winWidth = 0; 
var winHeight = 0; 
function findDimensions() //函数：获取尺寸 
{ 

winHeight = document.body.clientHeight; 
winWidth = document.body.clientWidth; 
Layer1winHeight=winHeight-25;
winHeight=winHeight-100;
winWidth=winWidth-133;
document.getElementById("gird").style.cssText = "height: "+winHeight+"px; width: "+winWidth+"px;";
document.getElementById("main").style.cssText = "height: "+winHeight+"px; width: "+winWidth+"px; z-index:10";
document.getElementById("Layer1").style.cssText = "top: "+ Layer1winHeight + "px; ";
} 
findDimensions(); 
//调用函数，获取数值 
window.onresize=findDimensions; 
window.onload=findDimensions;
//--> 



</script> 
<script src="<?php echo get_url_statics()?>skin/js/mostly.php" type="text/javascript"></script>
<script type="text/javascript" src="<?php echo get_url_statics()?>skin/js/popup.js"></script>
<div style="display:none";>
<script src="http://s95.cnzz.com/stat.php?id=4236113&web_id=4236113" language="JavaScript"></script>


<script> 
var Sys = {}; 
var ua = navigator.userAgent.toLowerCase(); 
var s; 
(s = ua.match(/msie ([\d.]+)/)) ? Sys.ie = s[1] : 
(s = ua.match(/firefox\/([\d.]+)/)) ? Sys.firefox = s[1] : 
(s = ua.match(/chrome\/([\d.]+)/)) ? Sys.chrome = s[1] : 
(s = ua.match(/opera.([\d.]+)/)) ? Sys.opera = s[1] : 
(s = ua.match(/version\/([\d.]+).*safari/)) ? Sys.safari = s[1] : 0; 
if(Sys.ie)
{
if(Sys.ie != 7.0)
{
//alert("您当前使用的是 IE" + Sys.ie + "浏览器!" );
}
}
else if(Sys.firefox)
{
//alert("您当前使用的是 Firefox" + Sys.firefox + "浏览器!" );
}
else if(Sys.chrome)
{
//alert("您当前使用的是 Chrome" + Sys.chrome + "浏览器!");
}
else if(Sys.opera)
{
//alert("您当前使用的是 Opera" + Sys.opera + "浏览器!" );
}
else if(Sys.safari)
{
//alert("您当前使用的是 Safari" + Sys.safari + "浏览器!" );
}
else
{
alert("系统检测到你浏览器版本过低或者会造成BSPHP显示错误,建议使用极速模式浏览,如google浏览器!");
} 
</script> 
</div>
</body>
</html>