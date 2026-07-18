<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>cancellation.lg</appen>  
* <appname>注销登陆</appname> 
* <sen>0<sen/>                
* <seng1>参数=>接收内容</seng>
* <lei>ok</lei>               
* <info>注销用户的登陆</info> 
* *****************************************************************************
*/
/*</name>*/
//oid Login file
//oid links file
$daihao = HOST_DAIHAO;
$user = set_data('user');
$user_pwd = set_data('pwd');


/**
 * 用户登陆
 */
$log = $this->user->UserWebLogin_key($user, $user_pwd);
if ($log == 1011)
{


    $uid = $this->db->my_class_array('bs_php_user', 'user_user', $user, '`user_uid`');

    //查询是否注册使用过
    // $arr = Login_UserInfo($uid,$daihao);
    $tmp = $this->session->links_delete_all($daihao, $uid);

    if ($tmp == 1)
    {
        PrInfo($this->user_str_log[1116]);
    } else
    {
       PrInfo($this->user_str_log[1117]);
    }


} //登录成功
PrInfo($this->user_str_log[$log]);

?>