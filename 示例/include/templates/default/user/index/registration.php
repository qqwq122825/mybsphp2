<!DOCTYPE html> 
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Language" content="zh-cn" />
<title>用户注册 - <?php echo bs_lib::get_configs_value('sys','name'); ?> Bsphp</title>
<?php  include (load_pach_templet('head')); ?>
</head>
<body class="reg">
<!-- 头部  --> 
<div class="customer-header">
	<div class="g">
		 <table cellpadding="0" cellspacing="0">
                <tr>
                    <td><img src="<?php echo get_url_statics() ?>images/logo2.png" alt="../" /></td>
                    <td style="padding-left:5px;"><img src="<?php echo get_url_statics() ?>images/split1.png" /></td>
                    <td>
	                    <span>
	                    	<!-- edit Logo font -->
	                    		<!--  -->
								<?php echo bs_lib::get_configs_value('sys','name'); ?> 
							<!-- end edit -->
	                    </span>
                    </td>
                </tr>
      </table>
    </div>
</div>
    <div class="g">
    	<!--注册 -->
    	<div class="tab_div"></div>
    	<div class="reg-area reg-apply-area">
        	<div class="h" >
            	<h3><span>注册帐号</span></h3>
            </div>
            <div class="b" >

				<form id="registerForm" name="registerForm" method="post" action="">
                	<!--表单 -->

				    
                	<div class="form-edit-area">
					<input name="yao_uid" type="hidden"   class="test" id="yao_uid" value="<?php echo $cookie_uid ?>" size="10"  />
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tbody>
                                <tr>
                                	<th>账号：</th>
                                    <td>      	
                                    	<input type="text" autocomplete="off"  class="text vam ime-disabled"  placeholder="注册账号"  name="user" id="username" maxlength="50" tabindex="1">                                    </td>
                                </tr>
                                <tr>
                                	<th>密码：</th>
                                    <td>
                                        <div><input type="password" class="text vam" value="" name="pwd1" maxlength="32"  placeholder="密码"  tabindex="2"/><span id="msg_password"></span></div>
                                    	<div class="pwd-letter"><span>弱</span><span>中</span><span>强</span></div>                                    </td>
                                </tr>
                                <tr>
                                  <th>确认密码：</th>
                                  <td><input type="password" class="text vam" value=""  name="pwd2"  placeholder="再次输入密码"  maxlength="32" tabindex="3" />
                                      <span id="msg_checkPassword"></span> </td>
                                </tr>
								
								
                                <tr>
                                  <th>联系QQ：</th>
                                  <td>  <input type="text" class="text vam" value=""  name="qq" maxlength="32" placeholder="联系QQ"  tabindex="3" /><span id="msg_qq"></span> 
                                </tr>
								
								
                                <tr>
                                	<th>密保邮箱：</th>
                                    <td>
                                        <input type="text" class="text vam" value=""  name="mail"  placeholder="密保邮箱" maxlength="32" tabindex="3" /><span id="js_existemail"></span>
									</td>
                                </tr>

								<?php if(bs_lib::get_configs_value('code','coode_registration') == 1){ ?>
								
                                <tr>
                                	<th>验证码：</th>
                                    <td>
                                    	<table>
                                    		<tr>
                                    			<td>
                                    				<input type="text" autocomplete="off"   placeholder="验证码"  class="verify vam" id="randomCode"   name="coode" tabindex="4" />
&nbsp;&nbsp;                                    			</td>
                                    			<td>
                                    				<img class="cimg"  onclick="this.src='index.php?m=coode&time='+new Date().getTime()";  src="index.php?m=coode&time='+new Date().getTime()" alt="验证码" width="120" height="30" border="0" />                                  			</td>
                                    			<td>
													<span id="msg_randomCode"></span>												</td>
                                    		</tr>
                                    	</table>                                    </td>
                                </tr>
								
								<?php } ?>
								
								
                                <tr>
                                	<th>&nbsp;</th>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr>
                                	<th>&nbsp;</th>
                                  <td>
                                      <input type="submit" name="submit" class="button-reg" id=""  value="立即注册" tabindex="6" />&nbsp;<a href="index.php">立即登录</a></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="f tar">
            <a href="index.php" class="button-login-2 vam" title="登录">登录</a>
            </div>
            <div class="cloud"></div>
    	</div>
    </div>
 <div class="hr-60"></div>
<!-- 底部  -->

<div class="customer-footer">
	<div class="g">
		<!--授权  -->
        <div class="warrant-area">
          <?php  include (load_pach_templet('foot')); ?>
        
        </div>
    </div>
</div>
	<!--注册协议-->
</body>
</html>