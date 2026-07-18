<?php


/**
 * @文件说明
 * 
 * 本文件用做数据库升级文件
 * 
 */


//载入需要文件
include ('../m.php');
include __HOST__ . '/Data/inc.config.php';
include __HOST__ . '/include/AppEn.conn.php';
include __HOST__ . '/include/AppEn.function.php';
$weizhi = null;
$test = null;


//判断按钮点击,否则载入选择模版
$ok = set_post('ok');
$radiobutton = set_post('radiobutton');
if ($ok) {
 
    //升级文件路径
   $fle_iurl = __HOST__ . '/install/data/file_sql/';



   $fle_iurl .= 'file_'.$radiobutton . '.txt';



    /**
     * 读取数据库
     * 
     * AppEn.mysql.txt=结构
     */
    //结构
    $log_info = '';
    $sql = file_get_contents($fle_iurl);
    $sql = str_replace('bs_php_', $DBQIANHUAN, $sql);
    $sqlarr = explode(";", $sql);


    for ($i = 0; $i < count($sqlarr) - 1; $i++) {

        $tmp = mysql_query($sqlarr[$i].';');
        
        
        if (!$tmp) {
            $log_info .='错误信息:'. mysql_errno().'错误SQL语句:'.$sqlarr[$i].'<br>';
            
        }
    }

       
   
    if($log_info==''){
        
        $test='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;安装成功A！请直接关闭本页。<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;为你系统安全请删除刚上传的升级文件：up.Php <br><br>';
        
        
    }else{
        // $test='升级失败<br>是不是你重复升级了或者你选择升级版本不对<br>1.如果你是重复升级直接关闭删除升级文件:up.Php<br>2.如果是选择版本不对请返回重新选择<br>3.如果以上2种都不是请到论坛发帖求助记得发下面错误信息:bbs.bsphp.com<br><br>本次SQL错误信息:<br>'.$log_info;
        
       $test='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;安装成功B！请直接关闭本页。<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;为你系统安全请删除刚上传的升级文件：up.Php <br><br>';
        
        
    }


    //$weizhi=1; 载入完成模版  $test=提示内容
    $weizhi = 1;
} else {
    //$weizhi=0;  载入选择模版
    $weizhi = 0;
}

include  'template/R-MySql.php';





?>