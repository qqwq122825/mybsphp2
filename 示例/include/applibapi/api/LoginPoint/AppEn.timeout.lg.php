<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>timeoutupdate.lg</appen>  
* <appname>超时更新</appname> 
* <sen>1<sen/>                
* <seng1>key=>绑定特征</seng>
* <lei>ok</lei>               
* <info>绑定特征</info> 
* *****************************************************************************
*/
/*</name>*/
//oid Login file
//oid links file

$daihao = HOST_DAIHAO;
$uid = get_session_value('USER_UID');
$BSphpSeSsL = set_data('BSphpSeSsL');


//  $type=set_data('type');
//WHETHER_LOGIN   是否登录
//WHETHER_CHAOSHI 是否超时
//WHETHER_LINKS   更新状态
//WHETHER_PCKEY   是否绑定机器
//WHETHER_EXPIRED 是否到期


// 1041 => '长时空闲超时执行正常',
// 1042 => '长时空闲超时执行异常',
//成功返回:1041  否则返回1042
$log = $this->user->UserCaoShiInfo($uid);
if ($log == 1042)
    PrInfo((string )$log);


//判断是否登录
$log = $this->user->UserLoginInfo();
if ((int)$log !== 1047)
    PrInfo((string )$log);


//获取用户信息
$arr = Login_UserInfo($uid, HOST_DAIHAO);
//---------------------------------------
//获取用户信息判断是否到期


if ($Global_DB_AppDaTa_Array['app_set'] == 0 or $arr['L_vip_unix'] > 0)
{
    //没有到期
   // PrInfo('5029');
    
} else
{
    
    //到期
    PrInfo('5030');
}
//---------------------------------------




//一切正常
PrInfo('5031');

?>