<?php

/*<name>*/
/***********************接口介绍说明******************************************
 * <appen>userkey.lg</appen>  
 * <appname>取用户绑定key</appname> 
 * <sen>0<sen/>                
 * <seng1>参数=>接收内容</seng>
 * <lei>ok</lei>               
 * <info>取绑定key</info> 
 * *****************************************************************************
 */
/*</name>*/

  //oid Login file
  //oid links file
  $daihao = HOST_DAIHAO;
  $uid = get_session_value('USER_UID');

       //登录连接数功能集代码
     //links_chaoshi_login();
  
  $log = $this->user->UserLoginInfo();
  if ($log == 1047) {
    
    
    /**
     * 查询用户绑定key
     */
   $user_arr= Login_UserInfo($uid,$daihao);
   if($user_arr[6]=="")PrInfo("9981");
   
   PrInfo($user_arr[6]);





  }
  PrInfo($this->user_str_log[$log]);
?>