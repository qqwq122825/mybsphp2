<?php

/*<name>*/
/***********************接口介绍说明******************************************
 * <appen>setthesecuritycode.lg</appen>  
 * <appname>设置密保信息</appname> 
 * <sen>2<sen/>                
 * <seng1>problem=>密保问题</seng>
 * <seng1>answer=>密保答案</seng>
 * <lei>ok</lei>               
 * <info>设置密保信息</info> 
 * *****************************************************************************
 */
/*</name>*/

  //oid links file

  $uid = get_session_value('USER_UID');
  $problem = set_data('problem');
  $answer = set_data('answer');


     //登录连接数功能集代码
     //links_chaoshi_login();
  //登陆状态
  $log = $this->user->UserLoginInfo();
  if ($log == 1047) {
    
    /**
     * 完善资料
     */
   $user_log= $this->user->UserMiBaoInfo($uid,$problem,$answer);
   if($user_log==1024){
   PrInfo((string)$user_log);
   }
   PrInfo($this->user_str_log[$user_log]);





  }
  PrInfo($this->user_str_log[$log]);
?>