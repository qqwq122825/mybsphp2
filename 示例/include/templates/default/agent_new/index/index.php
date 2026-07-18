
<!DOCTYPE html>
<html style="height: 100%;">
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
  <meta http-equiv="X-UA-Compatible" content="IE=edge" /> 
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" /> 
  <title><?PHP ECHO bs_lib::get_configs_value("sys","name"); ?> - 代理商管理平台 by Bsphp</title> 
 
  <link href="../statics/default/agent_new/login/css/global.css" rel="stylesheet" /> 
  <link href="../statics/default/agent_new/login/css/bootstrap-grid.css" rel="stylesheet" /> 
  <link href="../statics/default/agent_new/login/css/form.authy.css" rel="stylesheet" /> 

 </head> 
 <body style="position: relative; min-height: 100%; margin: 0px; padding: 0px; background-color: rgb(11, 65, 130);" dh="1" rlt="1"> 
  <div style="position: absolute; top: -120px; left: 0px; width: 100%; min-height: 100%; background: linear-gradient(0deg, #0b4182 1%, #1e88e5 100%);"> 
   <div class="loggedout_vcenter"> 
    <div style="padding: 8px;"> 
     <div style="min-height:420px; max-width: 420px; padding:40px; background-color:#ffffff; margin-left: auto; margin-right: auto; border-radius:4px; overflow-x: hidden;"> 
      <a href="#" style="display:block; height:58px; width:167px; margin:0 auto 30px auto;  background-size: auto 30px; background-repeat:no-repeat;"><?PHP ECHO bs_lib::get_configs_value("sys","name"); ?>-代理商</a> 
     <form name="form1"   method="post" action=""> 
 
       <input name="amdin_name" type="text" style="width:100%;" placeholder="代理账号" value="" required="required" autofocus="" /> 
       <hr class="hr15" /> 
       <input name="admin_password" type="password" style="width:100%;" placeholder="登录密码"  value=""  required="required" /> 
	   <hr class="hr15" /> 
       
       <?php if(bs_lib::get_configs_value('imges','coode_login') == true){ ?>
       <input name="imga" type="text" style="width:100%;" placeholder="验证码"  value=""  /> 
	   <hr class="hr15" /> 
       
 
       
      
	    <img class="cimg"  onclick="this.src='index.php?m=coode&time='+new Date().getTime()";  src="index.php?m=coode&time='+new Date().getTime()" alt="验证码" width="120" height="30" border="0" />
	   <hr class="hr15" /> 
       <?php 
								}
								?>
	  
       <input type="submit" name="adminlogin" value="登录" style="width:100%;" /> 
       <hr class="hr20" />  
       
      </form> 
     </div> 
    </div> 
   </div> 
  </div> 
 <div id="div" style="display: none">
<script type="text/javascript">var cnzz_protocol = (("https:" == document.location.protocol) ? " https://" : " http://");document.write(unescape("%3Cspan id='cnzz_stat_icon_1257359158'%3E%3C/span%3E%3Cscript src='" + cnzz_protocol + "s11.cnzz.com/stat.php%3Fid%3D1257359158' type='text/javascript'%3E%3C/script%3E"));</script>
</div>
 </body>
</html>