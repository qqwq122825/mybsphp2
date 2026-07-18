<?php



    //-----------------------------------------页面头部------------------------------------------------
    print <<< EOT

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Bsphp软件管理系统安装向导-Install</title>
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
                        <dd>V8.X(专制版)</dd>
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
                        <h2>请阅读 Bsphp软件管理验证系统 安装协议</h2>
                    </div>
                    <div class="box2-con">
                      <div class="agreement">
        <h3 align="center">Bsphp软件收费验证系统　使用协议</h3>
        <p align="center">&nbsp;</p>
        <p align="left">我们是《南宁火蝶科技有限公司》在以下协议简称:本公司</p>
        <p align="left">Bsphp软件收费验证系统是本公司旗下团队开发一款产品,使用前请请认真阅读使用条款！</p>
        <p align="left">感谢您使用Bsphp,希望我们的努力能为您提供一个高效的客户端管理方案。        </p>
        <p align="left">BSPHP系统版权属于本公司所有,所有的使用者禁止以任何新形式修改发布衍生新版本并且贩卖,BSPHP系统已经在国家版权局申请版权,如有违约者必追究一切法律责任与赔偿。          </p>
        <p align="left">在使用BSPHP系统期间所产生的任何法律责任自行处理,且请勿用于非法用途否则一切后果自行承担,与本公司无关。</p>
        <p align="left">环境安全协议,本公司只提供BSPHP系统是授权使用,BSPHP安装使用环境与安全由用户自由支配,因为安全问题引起一切后果以及数据损失由用户自行承担,本公司不承担任何责任。</p>
        <p align="left">使用须知用户出于自愿而使用本BSPHP系统，您必须了解使用本系统的风险，在尚未购买产品与技术服务之前，我们不承诺提供任何形式的技术支持、使用维护担保，也不承担任何因使用本系统而产生问题的相关责任。</p>
        <p align="left">禁止在 BSPHP系统 的整体或任何部分基础上以发展任何派生版本、修改版本或第三方版本用于重新分发,如果您未能遵守本协议的条款，您的授权将被终止，所被许可的权利将被收回，并承担相应法律责任。</p>
        <p align="left">你同意以上内容点击同意并且继续安装,否则点击拒绝 </p>
        <p align="left">安装使用BSPHP系统使用起等同协议生效</p>
        <p align="left">&nbsp;</p>

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
	
	
	
	$PHP_V=PHP_VERSION;
	
	if(PHP_VERSION >= 5.3 AND PHP_VERSION < 5.5){
	  
	  $PHP_V_HTML='<span style="color:#0C6; font-weight:bold;">&radic; 环境可用</span>';
	}ELSE{
	  $PHP_V_HTML='<span style="color:#F00; font-weight:bold;">环境要求php5.3/5.4</span>';
	  $url1='#';
	}
	
	
	
	
	
	$able=get_loaded_extensions();
	if(in_array('Zend Guard Loader',$able)){
	   $ZEND_HTML='<span style="color:#0C6; font-weight:bold;">&radic; 环境可用</span>';
	}else{
	   $ZEND_HTML='<span style="color:#F00; font-weight:bold;">组件不支持</span>';
	    $url1='#';
	
	}


        //-----------------------------------------安装第1步，检测属性------------------------------------------------
        print <<< EOT

<div class="box3">
  <h2>系统使用环境监测</h2>
  <table>
    <tr>
      <th width="455">名称</th>
      <td width="147"><span style="color:#07519A; font-weight:bold;">状 态</span></td>
    </tr>
	
    <tr>
      <th>当前PHP版本:{$PHP_V}</th>
      <td>{$PHP_V_HTML}</td>
    </tr>
	
    <tr>
      <th>Zend Guard Loader组件</th>
      <td>{$ZEND_HTML}</td>
    </tr>
	




EOT;
            $report_txt = array(1 =>
                '<span style="color:#0C6; font-weight:bold;">&radic; 正常可写</span>', 2 =>
                '<span style="color:#F00; font-weight:bold;">X 不可写</span>', 3 =>
                '<span style="color:#F00; font-weight:bold;">X 不存在</span>', );

            foreach ($file_check_report as $report)
            {
			
		
                print <<< EOT

			
    <tr>
      <th>目录:$report[0]&nbsp; </th>
      <td>{$report_txt[$report[1]]}</td>
    </tr>

EOT;
            }

            print <<< EOT
            
            

          <tr>
      <th>提示不要去想跳过这步,因为跳过无益！</th>
      <td></td>
    </tr>

  
    </table>
  
    
 <p>
  <div class="handle">
                
                    <button type="button" class="button" onclick="window.location.href='install.php?check=1'">上一步</button> 
					<button  type="submit" class="button" onclick="window.location.href='$url1'" >下一步</button>
              
      </div>
   </div>
  


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
                            <h2>填写数据库信息</h2>


                            <input type="hidden" name="step" value="4">
                            <table>
                                <tr>
                                    <th width="106"><div align="right">新浪SEA配置：</div></th>
                                    <td width="180">直接下一步可以</td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr>
                                    <th><div align="right">百度BAE配置：</div></th>
                                    <td>论坛参考</td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr>
                                  <th><div align="right">配置路径：</div></th>
                                  <td>data/Bsmysql.Config.php</td>
                                  <td>把对应内容</td>
                                </tr>
     


                                <tr>
                                <td colspan='3'>                                 </td>
                                </tr>
                            </table>

                        </div><!--/ box3-->

                      </div>
                <div class="handle">


                    <button type="button" onclick="window.location.href='as_sql.php?step=1'" class="button">上一步</button> 
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
                        <strong class="black">成功安装BSPHP软件管理系统， <font color="Red"><a href="#">请删除install安装目录</a></font>
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
                        <strong class="black">BSPHP已经安装,请及时删除文件</strong>
						<br/>
						你当前版本是新浪SEA/BAE版,请及时删除install安装文件夹
                    </div>
                </div>
            </div>

  
EOT;
}
    //-----------------------------------------页面底部------------------------------------------------
    print <<< EOT

               <div id="foot">Copyright 2009-2018 彩虹网络工作室  <a href="http://www.bsphp.com" target="_blank"> Bsphp验证系统 V8.X <?php echo BSPHP_VERSION; ?></a> Bsphp.com <br>
  All Rights Reserved </div>
            </div>
            <div class="fot"></div>
        </div>
    </div>
</div>
</body>
</html>
EOT;
?>
