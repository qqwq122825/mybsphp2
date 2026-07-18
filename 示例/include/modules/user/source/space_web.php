<?php


$action = set_get('action');
$fid=(int)set_get('fid');
$x=null;//判断是否有循环
$url_v=NULL;


function  NotRen ($URL){
    $fid=(int)set_get('fid');
	if($fid>0){
    alert2('已经是最后一页了请返回。');
	location($URL);
	}
}






$uid=$user_array['user_uid'];

//翻译配置
if($action!='pa'){


$sqlid=$fid*10;//数据库翻页
$shang=$fid-1;//上一页
$xia = $fid+1;//下一页
if($shang<0)$shang=0;

//上一页页面URL
$url_v="index.php?m=user&c=index&a=space&do=web&action=$action&fid=0";

}

?>
