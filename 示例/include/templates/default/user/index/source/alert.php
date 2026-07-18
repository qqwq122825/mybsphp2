<meta http-equiv="refresh" content="6;URL=<?php echo $uplink; ?>" />
<style type="text/css">
<!--
.at {
	width: 500px;
	float: left;
	margin-top: 10%;
	margin-left: 17%;
	background-color: #FFF;
	padding: 1px;
	border: 1px solid #E9E9E9;
	font-family: "新宋体";
	font-size: 15px;
	line-height: 15px;
	color: #666666;
}
.at .biaoti {
	width: 100px;
	margin-top: 10px;
	font-family: "宋体";
	margin-left: 30px;
	margin-bottom: 20px;
	font-weight: lighter;
}
.at .tishi {
	margin-top: 20px;
	margin-left: 30px;
	width: 440px;
	height: 50px;
	font-weight: lighter;
}
.at .di {
	margin-left: 230px;
	margin-top: 10px;
	text-decoration: blink;
	font-weight: lighter;
	font-size: 11px;
	margin-bottom: 5px;
}
-->
</style>

<div class="at">
<div class="biaoti"><strong>温馨提示</strong></div>
<div class="tishi"><?php 
if(isset($_GET['at'])){
echo  rawurldecode  ($_GET['at']);
}

 ?></div>
<div class="di">3秒后返回之前页面 
<input type="submit"  class="submit"  name="Submit" value="确定"  onclick="window.location.href='<?php echo $uplink; ?>'" />
</div>
</div>