<?php


//安装检测
if (!file_exists('Data/install.txt'))
{
    
    echo '<meta http-equiv="Refresh" content="0;URL=install/" />';
    exit;
}

//版本检测
if(!PHP_VERSION >= 5.3 AND !PHP_VERSION < 5.5){
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
    echo 'php版本必须为5.3/5.4版本';
    exit;
}




if (isset($_GET['m']) == false)
    $_GET['m'] = 'index';




// 入口字段
const BSPHP_SET = 'INDEX';
require ('LibBsphp/Global.Bsphp.Inc.php');
//location('user/index.php?m=user');



?>