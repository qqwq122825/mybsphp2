<?php
 /**
  * 判断是否安装过
  */
@header("content-Type: text/html; charset=utf-8"); //语言强制

$lujing = str_replace('install', '', dirname(__file__));
 if(file_exists($lujing.'Data/install.txt')) {
    exit('<meta http-equiv="refresh" content="0;URL=install.php?step=3" />');
 }
    

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Bsphp软件管理系统-Install</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<style type="text/css">
<!--
#Layer1 {
	position:absolute;
	left:640px;
	top:424px;
	width:370px;
	height:50px;
	z-index:1;
}
-->
</style>
</head>
<body>
<div id="Layer1">
  <p align="left">自动向导安装:通常目录可写安装选用 属性:777</p>
</div>
<div class="wrap">
    <div class="container">
        <div id="install">
            <div class="top">
                <div class="version">
                    <dl>
                        <dt class="current">当前版本：</dt>
                        <dd>V7.X</dd>
                    </dl>
                </div>
            </div>
            <div class="con">
                <div class="box2">
                    <div class="box2-top">
                        <h2>欢迎使用 BSPHP软件管理系统 </h2>
                    </div>
                    <div class="box2-con">
                        <div class="agreement" style="overflow:hidden;">
                           	<ul id="link">
                            <li><a href="install.php?check=1">开始安装Bsphp</a><span><img src="images/1.gif" /></span></li>
                            <li><img src="images/2.gif" /></span><a  target="_blank" href="http://www.bsphp.com/">下载最新版本</a></li>
                            <li><a href="http://bbs.bsphp.com" target="_blank">安装教程</a><a href="http://BBS.BSPHP.COM"></a><span><img src="images/3.gif" /></span></li>
                            <br />
                           	</ul>
                      </div>
                    </div>
                    <div class="box2-fot">

                    </div>
                </div>
                <div id="foot">Copyright 2009-2018 彩虹网络工作室  <a href="http://www.bsphp.com" target="_blank"> Bsphp验证系统 8.X</a> Bsphp.com <br>
  All Rights Reserved </div>
            </div>
            <div class="fot"></div>
        </div>
        <!--/ install-->
    </div>
    <!--/ container-->
</div>
<!--/ wrap-->
</body>
</html>