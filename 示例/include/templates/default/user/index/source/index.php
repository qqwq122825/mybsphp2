<?php
print <<<END

		<ul class="personalInfo" >
 
         	<li>
				<span class="label">用户名:</span>
				<span class="floatR">{$userarr['user_user']}</span>
			</li>
            
         	<li>
				<span class="label">UID:</span>
				<span class="floatR">{$userarr['user_uid']}</span>
			</li>
            
         	<li>
				<span class="label">QQ:</span>
				<span class="floatR">{$userarr['user_qq']} (<a href="index.php?m=user&c=index&a=space&do=info">修改/设置</a>)</span>
			</li>
         	<li>
				<span class="label">邮箱:</span>
				<span class="floatR">{$userarr['user_email']} (<a href="index.php?m=user&c=index&a=space&do=info">修改/设置</a>)</span>
			</li>
          	<li>
				<span class="label">手机:</span>
				<span class="floatR">{$userarr['user_Mobile']} (<a href="index.php?m=user&c=index&a=space&do=info">修改/设置</a>)</span>
			</li>
         	<li>
				<span class="label">密保信息:</span>
				<span class="floatR">{$mibao}</span>
			</li>
            
         	<li>
				<span class="label">余额:</span>
				<span class="floatR">{$userarr['user_rmb']}</span>
			</li>
            
         	<li>
				<span class="label">积分:</span>
				<span class="floatR">{$userarr['user_jifen']}</span>
			</li>
         	<li>
				<span class="label">邀请人数:</span>
				<span class="floatR">{$userarr['user_yao_Shu']}</span>
			</li>
          	<li>
				<span class="label">注册时间:</span>
				<span class="floatR">{$userarr['user_re_date']}</span>
			</li>
            
          	<li>
				<span class="label">上次登录时间:</span>
				<span class="floatR">$loginDATE</span>
			</li>
            
          	<li>
				<span class="label">上次登录IP:</span>
				<span class="floatR">$loginip</span>
			</li>
            

            
		</ul>
		
		
		
  <div class="replyTxt">
			<div class="replyTit"><h3>用户操作记录日志</h3></div>
			&nbsp;&nbsp;&nbsp;&nbsp;
  <input type="submit"  class="submit"  name="Submit" value="购卡记录"  onclick="window.location.href='index.php?m=user&c=index&a=space&do=web&action=pay'" />
  <input type="submit"  class="submit" name="Submit2" value="平台充值记录" onclick="window.location.href='index.php?m=user&c=index&a=space&do=web&action=ail'" />
  <input type="submit" class="submit"  name="Submit2" value="登录日志" onclick="window.location.href='index.php?m=user&c=index&a=space&do=web&action=login'" />
  <input type="submit" class="submit"  name="Submit22" value="邀请日志" onclick="window.location.href='index.php?m=user&c=index&a=space&do=web&action=yao'" />
  <input type="submit" class="submit"  name="Submit22" value="佣金提成记录" onclick="window.location.href='index.php?m=user&c=index&a=space&do=web&action=yon'" />

  </div>
	

END;


?> 