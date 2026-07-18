<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0"/>
<title>用户面板 - <?php echo bs_lib::get_configs_value('sys','name'); ?> Bsphp</title>
<link rel="stylesheet" type="text/css" href="<?php echo get_url_statics() ?>style/base.css" />
<link rel="stylesheet" type="text/css" href="<?php echo get_url_statics() ?>style/style1.css" />
<script type="text/javascript" src="<?php echo get_url_statics() ?>style/js/jquery-1.4.2.js"></script>
</head>
<body>
<div class="hearder">

	<div class="logoNav">
		<div class="wrap">
			<div class="logo"><a href="/"><img src="<?php echo get_url_statics() ?>images/logo2.png"></a></div>
			<div class="nav">
				<ul>
					<li ><a href="../">首页</a></li>
					<li ><a href="index.php?m=user&c=index&a=space&do=feed">个人中心</a></li><!--class="current"-->
					<li><a href="index.php?m=user&c=index&a=space&do=ecshop&action=ka" >购买充值卡</a></li>
					<li><a href="index.php?m=user&c=index&a=space&do=caradd" >充值续期</a></li>

					<li><a href="index.php?m=user&c=index&a=space&do=pay&act=pay" >余额充值</a></li>
					<li><a href="index.php?m=user&c=index&a=space&do=pwd" >修改密码</a></li>
					<li><a href="index.php?m=user&c=index&a=logout" >退出</a></li>
			  </ul>
			</div>
		</div>
	</div>


<div id="main">
	<div class="userSetupL pt20">
	<h2 class="pl20">用户中心</h2>
			<ul class="menu pt20"><!-- current--->
				<li class="ico_user"><a href="index.php?m=user&c=index&a=space&do=feed">个人中心</a></li>
				<li class="ico_content "><a href="index.php?m=user&c=index&a=space&do=shuo">大家都说</a></li>
				<li class="ico_password "><a href="index.php?m=user&c=index&a=space&do=mibao">密保设置</a></li>
                
                <?php if(bs_lib::get_configs_value('extension','yaoqing_off')==1){
                    
                    echo '<li class="ico_security "><a href="index.php?m=user&c=index&a=space&do=tui">我要推广</a></li>';
                    
                } ?>
				
				
			</ul>
</div>			<div class="userSetupR">
		<div class="title mt20 pl44">
			<h2>用户中心</h2>
		</div>
		<div class="setupAvatar">
		
		
 <?php include $file_do_pach; //PHP代码控制器 ?>
 <?php include bs_lib::load_default_path('index/source/'.$do); //html代码输出器 ?>

	   </div>
	</div>
</div>

<!--登录结束-->
<div id="footer">
<?php  include load_pach_templet('foot'); ?>
</div>

<!--自定义弹出框 start -->
		<div id="layer_wrap" style="display:none;">
			<div class="layer_con">
				<span id="closeLayer">关闭&nbsp;X&nbsp;</span>
				<div id="layer_dec">
				   <p class="layer_msg">评论成功！</p>  
				 </div> 
			</div>
		</div>
		<!--自定义弹出框 end -->

</body>
</html>