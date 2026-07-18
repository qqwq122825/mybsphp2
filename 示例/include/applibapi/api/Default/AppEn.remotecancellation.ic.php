<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>cancellation.ic</appen>  
* <appname>注销登录</appname> 
* <sen>0<sen/>                
* <seng></seng>
* <lei>ok</lei>               
* <info>取vip时间</info> 
* *****************************************************************************
*/
/*</name>*/


//oid links file
$BSphpSeSsL = set_data('BSphpSeSsL');
$icid = set_data('icid');
$icpwd = set_data('icpwd');
$daihao = HOST_DAIHAO;
if ($icid == null)
    PrInfo($this->user_str_log[1115]);

$arr_log = GetCardInfo($icid, $icpwd, $daihao);


if ($arr_log == 1083 or $arr_log == 1084 )
{

    PrInfo($this->user_str_log[$arr_log]);

}

//查询是否注册使用过
// $arr = Login_UserInfo($uid,$daihao);
$tmp = $this->session->links_delete_all_name($daihao, $icid);

if ($tmp == 1)
{
    //登录COOKIES设置
    set_session_value('ic_carid', 'NOT');
    set_session_value('ic_pwd', 'NOT');
    PrInfo($this->user_str_log[1116]);



} else
{

    PrInfo($this->user_str_log[1117]);
}

?>