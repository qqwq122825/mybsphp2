<?PHP
print <<< EOT
		<ul class="resetPassword">
        
        
			<form action="index.php?m=user&c=index&a=space&do=pwd" method="post"  class="email" id="update-passwd-form" name="frm_login">		


           	<li>
				<span class="lable">原始密码</span>
				<input id="oldpassword" name="pwd" type="password" class="passwordInput" autocomplete="off">
			</li>
			<li>
				<span class="lable">新密码</span>
				<input name="pwd1" id="new_password" type="password" class="passwordInput" maxlength="20" autocomplete="off">
				<i>密码由3-15位半角字符（字母、数字、符号）组成，区分大小写</i>
			</li>
			<li>
				<span class="lable">确认密码</span>
				<input name="pwd2" type="password" class="passwordInput" maxlength="20" autocomplete="off">
			</li>
			
			<li><input id="btnSubmit" name="Submit" type="submit" class="btn_save" value="保 存"> 
            
            <i> {$name}</i>
           
            </li>
			
			</form>		</ul>




EOT;
?>