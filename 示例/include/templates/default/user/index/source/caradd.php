
		<ul class="resetPassword">
        
        
			<form  action="index.php?m=user&c=index&a=space&do=caradd" method="post"  class="email" id="update-passwd-form" name="frm_login">		


           	<li>
				<span class="lable">充值卡号</span>
				<input id="ka_name" name="ka_name" type="text"  value="<?php echo $ka_name ?>"  class="passwordInput" autocomplete="off"><input name="app" type="submit" id="app"  class="submits"  value="检查" />
                <i><?php echo $app_txt;	?></i>
			</li>
			<li>
				<span class="lable">充值密码</span>
				<input name="ka_pwd" id="ka_pwd" type="text" class="passwordInput"  value="<?php echo $ka_pwd ?>"   autocomplete="off">
				
			</li>

			
			<li><input id="btnSubmit" name="Submitadd" type="submit" class="btn_save" value="充 值"> 
           
            
      
           
            </li>
			
			</form>		</ul>