<!DOCTYPE html> 
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Language" content="zh-cn" />
<title>找回密码 - <?php echo bs_lib::get_configs_value('sys','name'); ?> Bsphp</title>
<?php  include (load_pach_templet('head')); ?>
</head>
<body class="forget">
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
    	<!--找回密码 -->
    	<div id="mod-forget-area" class="forget-area">
        	<div class="h">
            	<h3><span>找回密码</span></h3>
            </div>
            <div class="b">
         <form id="form1" name="form1" method="post" action="">
                	<!--表单 -->

                	
                	<div class="form-edit-area">
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tbody>

                                <tr>
                                	<th>找回账号：</th>
                                    <td>
                                        <input name="user" type="text"  class="text vam"  placeholder="找回账号"  id="formBean_username" tabindex="1"  value="<?php echo $user ?>" maxlength="50" autocomplete="off"  />
                                        <span id="msg_username"></span>
                                    </td>
                                </tr>
                                <tr>
                                	<th>新密码：</th>
                                    <td>
                                        <input type="text" autocomplete="off"  class="text vam"  placeholder="设置新密码"  id="formBean_username" name="pwd1" maxlength="50" tabindex="1"  />
                                        <span id="msg_username"></span>
                                    </td>
                                </tr>
								
                                <tr>
                                	<th>再次输入密码：</th>
                                    <td>
                                        <input type="text" autocomplete="off"  class="text vam"  placeholder="再次输入新设置的密码"  id="formBean_username" name="pwd2" maxlength="50" tabindex="1"  />
                                        <span id="msg_username"></span> </td>
                                </tr>
								
                                <tr>
                                	<th>密保问题：</th>
                                    <td><select  name="mibao_wenti" class="select_down" id="mibao_wenti"  placeholder="密保问题"  style="height:30px;"  label="选择接收邮箱"  >
                                              <option value="我最喜欢的人是?">我最喜欢的人是?</option>
        <option value="我毕业的那一年?">我毕业的那一年?</option>
        <option value="我最重要的人?">我最重要的人?</option>
        <option value="我的幸运数字?">我的幸运数字?</option>
        <option value="我的小学名字?">我的小学名字?</option>
        <option value="我的出生地?">我的出生地?</option>
        <option value="我的小学班主任?">我的小学班主任?</option>
        <option value="我18岁的那一年是?">我18岁的那一年是?</option>
                                    </select>
                                      <span id="msg_username"></span>
                                    </td>
                                </tr>
								
                                <tr>
                                	<th>密保答案：</th>
                                    <td>
                                        <input name="mibao_daan" type="text"  class="text vam"  placeholder="密保答案"  id="formBean_username" tabindex="1"  value="<?php echo $mibao_daan ?>" maxlength="50" autocomplete="off"  />
                                        <span id="msg_username"></span>
                                    </td>
                                </tr>
							<?php if(bs_lib::get_configs_value('code','coode_backpwd') == true){ ?>
								
                                <tr>
                                	<th>验证码：</th>
                                  <td  colspan="2">    
                                    <table>
                                      <tr>
                                        <td><input type="text" autocomplete="off"  class="verify vam" id="randomCode"  placeholder="验证码"    name="imga" tabindex="4" />
                                          &nbsp;&nbsp; </td>
                                        <td><img class="cimg"  onclick="this.src='index.php?m=coode&time='+new Date().getTime()";  src="index.php?m=coode&time='+new Date().getTime()" alt="验证码" width="120" height="30" border="0" />               </td>
                                        <td><span id="msg_randomCode"></span> </td>
                                      </tr>
                                    </table></td>
                                </tr>
								<?php } ?>
								
								
                                <tr>
                                	<th>&nbsp;</th>
                                  <td>
                                        <input type="submit" id="ok" class="button-next" name="ok"  value="找回密码"  tabindex="3" />
                                      &nbsp;&nbsp;
                                        <a href="index.php">立即登录</a></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
				
            </div>
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



</body>

</html>