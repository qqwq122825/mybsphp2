<?php
	/*
'**************************************************************************************************
' 软件名称: 彩虹网络验证系统 Www.BsPhp.Com
' 官方网站: http://Www.BsPhp.Com
' 官方论坛：http://bbs.qicaihong.cc
' 版权所有: 七彩虹工作室   bu:小周
' 郑重声明:.
'    1、任何个人或组织不得在未经授权的情况下删除、修改、拷贝本软件及其他副本上一切关于版权的信息；
'    2、作者保留此软件的法律追究权利
'**************************************************************************************************
*/

$url=get_url();
//echo $url;
/**
 * 获取GET账号密码
 */
$GET_ka_name=set_get('ka');
$GET_ka_pwd=set_get('mi');

/**
 * POST内容
 */
$Submitadd=set_post('Submitadd');
$ka_name=set_post('ka_name');
$ka_pwd=set_post('ka_pwd');

if($GET_ka_name!=''){
    $ka_name=$GET_ka_name;
    $ka_pwd=$GET_ka_pwd;
    
}
/**
 * 去空格
 */
$ka_name=trim($ka_name);
$ka_pwd=trim($ka_pwd);




/**
 * 判断是否为空
 */
if($Submitadd){
if(trim($ka_name)=='') die($this->alert('充值卡号不能为空!',$url));


bs_lib::load_modules_common('applib', 'appen_appuser');
$log=$this->user->UserChong($user_array['user_user'],$ka_name,$ka_pwd);
//die($this->user_str_log[$log]);

die($this->alert($this->user_str_log[$log],$url.'&ka='.$ka_name.'&mi='.$ka_pwd));

}



/**
 * 检测账号所属软件
 */
$app_txt = '充值卡会自动识别需要充值软件！ 点击检测可查充值卡所属软件';
$app=set_post('app');
if($app) {
   $daihao=$this->db->my_class_array('bs_php_cardseries','car_name',$ka_name,'`car_DaiHao`');
   $app_name = $this->db->my_class_array('bs_php_appinfo','app_daihao',$daihao,'`app_name`');
if($app_name){
$app_txt='所属软件:'.$app_name;
}else{
$app_txt='没有找到对应软件,充值卡无效！';
}
}







?>