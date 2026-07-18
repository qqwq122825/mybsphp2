<?php



    //-----------------------------------------页面头部------------------------------------------------
    print <<< EOT

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>BSPHP验证系统安装向导-install</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
</head>

<body>
<div class="wrap">
    <div class="container">
        <div id="install">
            <div class="top">
                <div class="version">
                    <dl>
                        <dt class="current">当前版本：</dt>
                        <dd>V8.X</dd>
                    </dl>
                </div>
            </div>
            <div class="con">

EOT;
    if (empty($step))
    {
        //-----------------------------------------打印安装协议------------------------------------------------
        if (!isset($check))
        {
            print <<< EOT

            <div class="box3">
                <div id="error">
                    <div class="box4">
                        <img src="images/icon_06.gif" alt="错误" /><br />
                        <strong class="red">出错啦！请正确运行安装.</strong><br />
                        <strong class="black"></strong>
                    </div>
                </div>
            </div>

EOT;
        } else
        {
            print <<< EOT

<div class="box2">
                    <div class="box2-top">
                        <h2>请阅读 BSPHP验证系统 安装协议</h2>
                    </div>
                    <div class="box2-con">
                      <div class="agreement">
        <h3 align="center">BSPHP软件收费验证系统　使用权限协议</h3>
        <p>&nbsp;</p>
        <p align="left">感谢您使用BSPHP软件会员收费验证系统。希望我们的努力能为您提供一个高效的软件验证方案。<br />
验证系统从2012年2月28日开始构思直到4月底发布1.0版本,系统都具备了会员、卡号、代理商平台、日志、系统后台、软件配置 细致设计让管理员方便快捷操控后台。</p>
        <p align="left">BSPHP软件会员收费验证系统x2(第2版)2012年7月份发X2布第一个版本,第2个版本重新改编的一个全新版本收集前一个版本的基础上改版。简单化操作。</p>
        <p align="left">系统的调用环境说明<br />
          WEB服务器环境：Apache2和 IIS环境, php 版本5.3以上,mysql 版本5.0以上,WEB空间文件读写</p>
        <p align="left">BSPHP软件验证系统 版权属于作者所有,使用者不得以任何新形式修改发布并且贩卖。如有违约必追究责任。
系统在使用期间所产生的任何法律责任自行处理,与作者无关。
本软件为共享软件，未经书面授权，不得向任何第三方提供本软件系统。</p>
        <p align="left">用户出于自愿而使用本软件，您必须了解使用本软件的风险，在尚未购买产品技术服务之前，我们不承诺提供任何形式的技术支持、使用担保，也不承担任何因使用本软件而产生问题的相关责任。</p>
        <p align="left">禁止在 BSPHP软件收费验证系统 的整体或任何部分基础上以发展任何派生版本、修改版本或第三方版本用于重新分发,如果您未能遵守本协议的条款，您的授权将被终止，所被许可的权利将被收回，并承担相应法律责任。</p>
        <p align="left">你同意以上内容点击同意并且继续安装,否则点击拒绝 </p>
                      </div>
                    </div>
                    <div class="box2-fot">
                        <div class="handle">
                            <form method="get" action=''>
                                <input type="hidden" name="step" value="1">
                                <button type="submit" class="button" >同&nbsp;&nbsp;意</button> <button type="button" class="button" onclick="javascript: window.close();">拒&nbsp;&nbsp;绝</button>
                            </form>
                        </div>
                    </div>
              </div>

EOT;
        }
    } elseif ($step == 1)
    {


        //-----------------------------------------安装第1步，检测属性------------------------------------------------
        print <<< EOT

<div class="box3">
  <h2>Mysql数据库连接检测手自安装</h2>
  <table>
    <tr>
      <th width="455">目 录 名</th>
      <td width="147"><span style="color:#07519A; font-weight:bold;">状 态</span></td>
    </tr>



			
    <tr>
      <th>&nbsp; </th>
      <td></td>
    </tr>


            
            
            
       

  
    </table>
  
    
 <p>
  <div class="handle">
                
                    <button type="button" class="button" onclick="window.location.href='installea.php?check=1'">上一步</button> 
					<button  type="submit" class="button" onclick="window.location.href='$url1'" >下一步</button>
              
      </div>
        </div>
  

 <br>
  <br>
    <br>
   
 </p>
  <div style="border-bottom:1px solid #DFECF7;"></div>
        
EOT;
         //-----------------------------------------安装第2步，安装方式选择------------------------------------------------
    }elseif($step==2){

        print <<< EOT
        
           
   <div id="step">
                    <div id="step2">
                        第二步
                    </div>
                    <form method="post" action=''>
                    
                    <div id="database">
                        <div class="box3">
                            <h2>Mysql数据库信息 检测</h2>


                            <p align="center">
                              <input type="hidden" name="step" value="4">
                              <br />

                            <table>
                                <tr>
                                    <th colspan="3"><p>请手动配置还你的数据库文件位置: 根目录/Data/inc.config.php</p>
									<p> &lt;?php这个符号前请勿留空行或者空格</p>
									<p>?&gt;这个符号后请勿留空行或者空格</p>
                                      <p>如果你已经配置好请点击<span class="black">下一步</span>继续安装<br />
<br />
                                        <br />
                                        </p></th>
                                </tr>
                                


                                <tr>
                                <td colspan='3'>
								
                                <p>------------------------------------------------------------------------------------------</p>
								一般请按下面代码进行设置：请填写好数据库信息到复制到 根目录/Data/inc.config.php<br />
                                  &lt;?php
                                    \$DBHOST = &quot; localhost:3306&quot;; //MYsql服务器地址 localhost:3306<br />
                                    \$DBUSER = &quot;数据库用户名&quot;;  //数据库用户名 <br />
                                    \$DBPASS = &quot;数据库密码&quot;;  //数据库密码 <br />
                                    \$DBTABLE = &quot;数据库名&quot;;  //数据库名 <br />
                                    \$DBQIANHUAN = &quot;Bsphp_&quot;;  //默认即可 <br />
                                    ?&gt;<p>------------------------------------------------------------------------------------------ </p>
                                    </p>
                                  </p>
                                  （暂不支持）
                                  <p>新浪SEA设置:直接复制下列代码到根目录/Data/inc.config.php 即可(全部覆盖内容)</p>
                                    &lt;?php</p>
                                    \$DBHOST = SAE_MYSQL_HOST_M.&quot;:&quot;.SAE_MYSQL_PORT; //MYsql服务器地址<br />
                                    \$DBUSER = SAE_MYSQL_USER;  //数据库用户名 <br />
                                    \$DBPASS = SAE_MYSQL_PASS;  //数据库密码 <br />
                                    \$DBTABLE = SAE_MYSQL_DB;  //数据库名 <br />
                                    \$DBQIANHUAN = &quot;Bsphp_&quot;;  //默认即可 <br />
                                  ?&gt;<p>------------------------------------------------------------------------------------------ </p>
                                  </p>
                                  （暂不支持）
                                  <p>百度BEA设置:请填写好数据库信息到复制到 根目录/Data/inc.config.php</p>
                                  &lt;?php</p>
                                    \$DBHOST   = &quot;sqld.duapp.com:4050&quot;; //MYsql服务器地址 百度默认:sqld.duapp.com:4050<br />
                                    \$DBUSER   = &quot;应用API Key&quot;;      //数据库用户名 <br />
                                    \$DBPASS   = &quot;应用Secret Key&quot; ;  //数据库密码 <br />
                                    \$DBTABLE  = &quot;创建的数据库名&quot; ; //数据库名 <br />
                                    \$DBQIANHUAN = &quot;Bsphp_&quot;;  //默认即可 <br />
                                  ?&gt;
								  
								  
								  
                              ?&gt;<p>------------------------------------------------------------------------------------------ </p>
                                  </p>
                                  （支持）
                                  <p>phpcloud云设置:请填写好数据库信息到复制到 根目录/Data/inc.config.php</p>
                                  &lt;?php</p>
                                    \$DBHOST   = &quot;APP应用名-db.my.phpcloud.com&quot;; //MYsql服务器地址 <br />
                                    \$DBUSER   = &quot;APP应用名&quot;;      //数据库用户名 <br />
                                    \$DBPASS   = &quot;登录phpcloud密码&quot; ;  //数据库密码 <br />
                                    \$DBTABLE  = &quot;APP应用名&quot; ; //数据库名 <br />
                                    \$DBQIANHUAN = &quot;Bsphp_&quot;;  //默认即可 <br />
                                  ?&gt;
								  
								  
								  
								  
								  
								  </td>
                                </tr>
                            </table>

                        </div><!--/ box3-->

                      </div>
                <div class="handle">


                    <button type="button" onclick="window.location.href='installea.php?step=1'" class="button">上一步</button> 
					<button type="submit" id="submit" name="submit" class="button">下一步</button>
                </div>



  
EOT;
    }elseif($step==5){
        print <<< EOT
        
           
  
                        <div id="step">
            <div id="step4">
                第四步
            </div>
            <div class="box3">
                <div id="error">
                    <div class="box4">
                        <img src="images/icon_04.gif" alt="成功" /><br />
                        <strong class="black">成功安装BSPHP验证系统， <font color="Red"><a href="../Admin">点击进入后台</a></font>
                    </div>
                </div>
            </div>

  
EOT;
}elseif($step==3){
        print <<< EOT
        
           
  
                        <div id="step">
            <div id="step1">
                第四步
            </div>
            <div class="box3">
                <div id="error">
                    <div class="box4">
                        <img src="images/icon_04.gif" alt="成功" /><br />
                        <strong class="black">BSPHP验证系统已经安装过了</strong>
						<br/>
						如果需要重新安装请选删除根目录下 <strong class="black">Data/install.txt</strong> 文件
                    </div>
                </div>
            </div>

  
EOT;
}
    //-----------------------------------------页面底部------------------------------------------------
    print <<< EOT

                <div class="copyright">Copyright &copy; 2009-2018 BSPHP验证系统(WWW.BSPHP.COM) All Rights Reserved. </div>
            </div>
            <div class="fot"></div>
        </div>
    </div>
</div>
</body>
</html>
EOT;
?>
