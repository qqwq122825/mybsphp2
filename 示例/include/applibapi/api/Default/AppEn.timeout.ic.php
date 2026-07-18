<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>timeoutupdate.ic</appen>  
* <appname>超时更新</appname> 
* <sen>1<sen/>                
* <seng1>key=>绑定特征</seng>
* <lei>ok</lei>               
* <info>绑定特征</info> 
* *****************************************************************************
*/
/*</name>*/
$daihao = HOST_DAIHAO;
$car_id = get_session_value('ic_carid');
$car_pwd = get_session_value('ic_pwd');
$BSphpSeSsL = set_data('BSphpSeSsL');

//WHETHER_LOGIN   是否登录
//WHETHER_CHAOSHI 是否超时
//WHETHER_LINKS   更新状态
//WHETHER_PCKEY   是否绑定机器
//WHETHER_EXPIRED 是否到期


//获取验证信息 COOKIE内之了
$log = CarCookies($car_id, $car_pwd, $daihao);
if ((int)$log !== 1080)
    PrInfo((string )$log);


$arr = GetCardInfo($car_id, $car_pwd, $daihao);


if ($Global_DB_AppDaTa_Array['app_set'] == 0 or $arr['L_vip_unix'] > HOST_UNIX)
{
    //没有到期
    //PrInfo('5029');

} else
{

    //到期
    PrInfo('5030');
}


//一切正常
PrInfo('5031');

?>