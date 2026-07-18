<?PHP
print <<< EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>BSPHP验证系统 数据库升级-Install</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
</head>

<body>
<form method="post" action='UP.php'>
<div class="wrap">
    <div class="container">
        <div id="install">
            <div class="top">
                <div class="version">
                    <dl>
                        <dt class="current">当前版本：</dt>
                        <dd>V5.X</dd>
                    </dl>
                </div>
            </div>
			
EOT;



if($weizhi==1){

print <<< EOT
			     <!--   提示开始   -->
				        <div class="con">
              <div class="box2">
                    <div class="box2-top">
                        <h2>提示</h2>
                    </div>
                <div class="box2-con">
                  <p align="left">&nbsp;</p>

                 
                     <div align="left">{$test}
                     </div>
                  <p align="left">&nbsp;</p>
                  <p align="left">&nbsp;</p>

                  <p align="left"><br /> 
                  </p>
                  <p align="left">
                    <label></label>
                  </p>
                </div>
                    <div class="box2-fot">
                      <div class="handle"></div>
                    </div>
              </div>

EOT;


}else{


print <<< EOT
			<!--   ----------操作开始----------------------------   -->
			
            <div class="con">
              <div class="box2">
                    <div class="box2-top">
                        <h2>BSPHP验证系统数据库升级安装向导</h2>
                    </div>
                <div class="box2-con">
                  <p align="left">请选择从哪一版本升级到当前版本</p>
                  <p align="left">
                    <label></label>
                  </p>
                  <p align="left"><input name="radiobutton" type="radio" value="up"  />
                  升级最新版本
                  </p>
                  <p align="left">&nbsp;</p>
                  <p align="left">&nbsp;</p>
                  <p align="left">&nbsp;</p>
                  <p align="left">说明：本向导是升级数据库的字段信息,在升级前建议先备份数据和升级后都备份一下数据库</p>
                  <p align="left"><br /> 
                     <label></label>
                  </p>
                </div>
                    <div class="box2-fot">
                        <div class="handle">
                            
                              <button type="submit" name="ok"  value="ok"  class="button" >立即升级</button> 
                          
                        </div>
                    </div>
              </div>

EOT;

}


print <<< EOT
<!--   -----结束-----   -->
          </div>
            <div class="fot"></div>
      </div>
    </div>
</div>
<p><a href="http://www.bsphp.com">BSPHP验证系</a> Www.BSPHP.Com      2009-2012</p>
</form>
</body>
</html>
EOT;
?>