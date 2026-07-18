<?PHP
print <<< EOT
<form id="form1" name="form1" method="post" action="index.php?m=user&c=index&a=space&do=mibao">
  	<div class="problem">
			<ul class="lableInput" >
				<li style="*z-index:2;">
					<span class="lable">密保问题</span>
					<div class="selectInput" id="selQuestion1" >
                    
                              <select name="select" class="select" id="selQuestion1" style="*z-index:3;">
        <option value="我最喜欢的人是?">我最喜欢的人是?</option>
        <option value="我毕业的那一年?">我毕业的那一年?</option>
        <option value="我最重要的人?">我最重要的人?</option>
        <option value="我的幸运数字?">我的幸运数字?</option>
        <option value="我的小学名字?">我的小学名字?</option>
        <option value="我的出生地?">我的出生地?</option>
        <option value="我的小学班主任?">我的小学班主任?</option>
        <option value="我18岁的那一年是?">我18岁的那一年是?</option>
      </select>

		        	</div>
					<span id="question_1_err" class="tips_span err_msg" style="display:none;"></span>
				</li>
				<li>
					<span class="lable">密保答案</span>
					<input id="answer_1" name="daan" class="input245" autocomplete="off" />
                    
					<span id="answer_1_err" class="tips_span err_msg"  style="display:none;"></span>
				</li>
			
								
				<li class="mt30">
					<input name="Submit" type="submit" class="btn_submit question_btn" value="提交设置" />
                    <i> {$name}</i>
				</li>
			</ul>
         </div>
        </form>   


         
  <div class="replyTxt">
			<div class="replyTit"><h3>为什么要设置密保？</h3></div>
			<p>1.验证密保可加强账户安全，您可以使用已验密保找回密码；</p>

  </div>

EOT;
?>