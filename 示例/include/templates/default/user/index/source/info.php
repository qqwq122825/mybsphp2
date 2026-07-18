
		<ul class="personalInfo">
			<form method="post" action="index.php?m=user&c=index&a=space&do=info" name="frm_bang_phone">			<li>
				<span class="label">用户名</span>
				<span class="floatR"><?php echo $userarr['user_user'] ?></span>
			</li>
			<li>
				<span class="label">联系QQ</span>
				<span class="floatR">
					<input value="<?php echo $userarr['user_qq'] ?>" name="qq" type="text" class="perInput" />

					<span class="err_msg err_telephone"  style="display: none;"></span>
				</span>
                
			</li>
			<li>
				<span class="label">联系手机</span>
				<span class="floatR">
					<input value="<?php echo $userarr['user_Mobile'] ?>" name="dh" type="text" class="perInput" />

					<span class="err_msg err_mobile"  style="display: none;"></span>
				</span>
                
			</li>

			<li>
				<span class="label">联系邮箱</span>
				<span class="floatR">
					<input value="<?php echo $userarr['user_email'] ?>" name="mail" type="text" class="perInput" />

					<span class="err_msg err_msn"  style="display: none;"></span>
				</span>
                
			</li>

			<li><input type="submit" name="Submit" value="保存" class="btn_save" />
            <?php echo $name ?>
            
            </li>
			</form>		
			</ul>
