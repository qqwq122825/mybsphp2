<?php
  @header("content-Type: text/html; charset=utf-8"); //语言强制
  $lujing = str_replace('install', '', dirname(__file__));


/**
 * 转化 \ 为 /
 * 
 * @param	string	$path	路径
 * @return	string	路径
 */
function dir_path($path)
{
    $path = str_replace('\\', '/', $path);
    if (substr($path, -1) != '/')
        $path = $path . '/';
    return $path;
}

/**
 * 创建目录
 * 
 * @param	string	$path	路径
 * @param	string	$mode	属性
 * @return	string	如果已经存在则返回true，否则为flase
 */
function dir_create($path, $mode = 0777)
{
    if (is_dir($path))
        return true;
    $ftp_enable = 0;
    $path = dir_path($path);
    $temp = explode('/', $path);
    $cur_dir = '';
    $max = count($temp) - 1;
    for ($i = 0; $i < $max; $i++) {
        $cur_dir .= $temp[$i] . '/';
        if (@is_dir($cur_dir))
            continue;
        @mkdir($cur_dir, 0777, true);
        @chmod($cur_dir, 0777);
    }
    return is_dir($path);
}

/**
 * 拷贝目录及下面所有文件
 * 
 * @param	string	$fromdir	原路径
 * @param	string	$todir		目标路径
 * @return	string	如果目标路径不存在则返回false，否则为true
 */
function dir_copy($fromdir, $todir)
{
    $fromdir = dir_path($fromdir);
    $todir = dir_path($todir);
    if (!is_dir($fromdir))
        return false;
    if (!is_dir($todir))
        dir_create($todir);
    $list = glob($fromdir . '*');
    if (!empty($list)) {
        foreach ($list as $v) {
            $path = $todir . basename($v);
            if (is_dir($v)) {
                dir_copy($v, $path);
            } else {
                copy($v, $path);
                @chmod($path, 0777);
            }
        }
    }
    return true;
}





/**
 * @取得当前URL路径
 * 
 * 用了协助信息的返回
 */
  function url() {

    $pageURL = 'http';
    if (@$_SERVER["HTTPS"] == "on") {
      $pageURL .= "s";
    }
    
    
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
      $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    } else {
      $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $pageURL;
  }



  /**
   * *******************************
   * 获取随机数
   * 
   * 参数1=随机数长度
   * 
   */
  function gte_test_arr($int) {
    
      /**********取随机数字数组**************/
  $check_list = array('q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', 'l', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'z', 'z', 'x', 'c', 'v', 'b', 'n', 'm', 'Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'I', 'P', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'Z', 'X', 'C', 'V', 'B', 'N', 'M', 1, 2, 3, 4, 5, 6, 7,
    8, 9, 0);
    
    $test = '';
    shuffle($check_list); //打乱
    $ki = 0;
    while ($ki < $int) {
      $test .= $check_list[mt_rand(1, 61)];
      $ki++;
    }
    return $test;

  }
  



  /**
   * @信息框系统框
   * 默认返回前一页
   */
  function alert($NAME) {
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo "<script language=javascript>alert('".$NAME."');</script>";
    echo '<script> var url =document.referrer; window.location=url; </script> ';
    exit;
  }

isset($_GET['check']) ?$check = $_GET['check'] : $check = null;
isset($_GET['step']) ? $step = $_GET['step'] : $step = null;

 /**
  * 判断是否安装过
  */


 if(file_exists($lujing.'Data/install.txt') & $step!=3) {
    if( $step!=5) exit('<meta http-equiv="refresh" content="0;URL=install.php?step=3" />');
	  

 }


 
 

    isset($_POST['dbhost']) ? $dbhost = $_POST['dbhost'] : $dbhost = '127.0.0.1';
    isset($_POST['dbuser']) ? $dbuser = $_POST['dbuser'] : $dbuser = 'root';
    isset($_POST['dbpw']) ? $dbpw = $_POST['dbpw'] : $dbpw = '';
    isset($_POST['dbname']) ? $dbname = $_POST['dbname'] : $dbname = '';
    isset($_POST['dbtop']) ? $dbtop = $_POST['dbtop'] : $dbtop = 'bsphp_';
    isset($_POST['Coding']) ? $Coding = $_POST['Coding'] : $Coding = '0';




$file_check_error = 1;
if ($step == 1) //----------------------------------------系全新检测------------------------------------------
{
    $report = array();

    
    include_once ('data/fi_array.php');
    $ok=0;
    $url1='install.php?step=1';
    $a = count($arr_Afig);
    $i = 1;
    while ($i <= $a)
    {
        $file_check_report[$i][0] = $lujing . $arr_Afig[$i];
        $filename = $lujing . $arr_Afig[$i];
        if (file_exists($filename))
        {
            if (is_writable($filename))
            {
                $file_check_report[$i][1] = '1';
                $filename_ok = '1';
                
				IF($ok==0)$ok = 2;
                
            } else
            {
                $file_check_report[$i][1] = '2';
                $ok=1;
            }
        } else
        {
            $file_check_report[$i][1] = '3';
            $ok=1;
        }
        $i++;
    }
    
    if($ok==2) $url1='install.php?step=2';
    
} elseif ($step == 2)
//---------------------------------------------安装数据库升级的和全新的---------------------------------------
{

   

    if (isset($_POST['submit']))
    {
        
        
     	if(PHP_VERSION >= 5.3 AND PHP_VERSION < 5.4){
           $file_dir='5.3';
            
        }elseif(PHP_VERSION >= 5.4 AND PHP_VERSION < 5.5){
            $file_dir='5.4';
            
        }else{
            echo 'PHP环境不正确,请选择php5.3/5.4环境+Zend支持,安装不能继续因为无法使用！';
            exit;
        }
       
        
        /**
         * @拷贝文件
         */
        dir_copy('PhpFile/LibBsphp'.$file_dir,'../LibBsphp/');
        dir_copy('PhpFile/modules'.$file_dir,'../include/modules/');
        
        
        
        

        $link = mysql_connect($dbhost, $dbuser, $dbpw) or die(alert('数据库连接失败 IP地址、用户名或者密码错误 MYSQL错误信息:'.mysql_error()));
        
        
        $db=mysql_select_db($dbname, $link)OR die(alert('数据库不存在,请输入已经存在数据表名  MYSQL错误信息:'.mysql_error()));
      
         mysql_query("set names 'utf8'");
        /**
         * 写出配置文件
         */
     $ini='<?php 
     //以下是MYSQL配置信息,迁移时只需要配置好以下信息即可
	 //南宁火蝶科技有限公司
	 //Bsphp Inc
     const   DBHOST = "'.$dbhost.'";     //数据库服务器地址 127.0.0.1
     const   DBUSER = "'.$dbuser.'";     //数据库用户名
     const   DBPASS = "'.$dbpw.'";       //数据库密码
     const   DBTABLE = "'.$dbname.'";    //数据库名
     const   DBQIANHUAN = "'.$dbtop.'";        //表格缓 
     ?>';
     $tmp=file_put_contents('../Data/Bsmysql.Config.php',$ini);
     if(!$tmp) alert('配置保存失败,/Data/Bsmysql.Config.php不可写!,');
        
     
     
       /**
        * 读取数据库
        * 
        * AppEn.mysql.txt=结构
        */
        //结构
        $sql = file_get_contents('data/AppEn.mysql.txt');
        $sql = str_replace('bs_php_',$dbtop , $sql);
		
	

		
	
		
        $sqlarr=explode(";",$sql);
        
        
        //数据
        $sql = file_get_contents('data/mysql.data.txt');
        $sql = str_replace('bs_php_',$dbtop , $sql);
		
		$sql = str_replace('BSPHP_HOSY_PASSWORD','Bsphp_'.gte_test_arr(10) , $sql);
		$sql = str_replace('BSPHP_HOSY_COOKIES' ,'Bsphp_'.gte_test_arr(10) , $sql);
        
        $BSPHP_HOSY_HOST_URL=str_replace('install/install.php?step=2' , '', strtolower(url()));
        $sql = str_replace('BSPHP_HOST_URL' ,$BSPHP_HOSY_HOST_URL , $sql);
		
        $dataarr=explode(";",$sql);
        
        for($i=0;$i<count($sqlarr)-1;$i++){
		mysql_query($sqlarr[$i]);
     	}
        
        for($i=0;$i<count($dataarr)-1;$i++){
		mysql_query($dataarr[$i]);
     	}
        
       $tmp=file_put_contents($lujing.'Data/install.txt','ok');
       echo '<meta http-equiv="refresh" content="0;URL=install.php?step=5" />';

  }


}elseif ($step == 3)
//---------------------------------------------安装数据库升级的和全新的---------------------------------------
{

 if(!file_exists($lujing.'Data/install.txt')) {
    if( $step!=5) exit('<meta http-equiv="refresh" content="0;URL=index.php" />');
	  

 }



}
include_once ('template/install.php');

?>