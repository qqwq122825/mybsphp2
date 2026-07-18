<?php

/*<name>*/
/***********************接口介绍说明******************************************
 * <appen>setcode.lg</appen>  
 * <appname>检测密保信息是否填写</appname> 
 * <sen>0<sen/>                
 * <lei>ok</lei>               
 * <info>检测密保信息是否填写</info> 
 * *****************************************************************************
 */
/*</name>*/

  //oid Login file
  //oid links file

  $uid = get_session_value('USER_UID');



     //登录连接数功能集代码
     //links_chaoshi_login();
  //登陆状态
  $log = $this->user->UserLoginInfo();
  if ($log == 1047) {
    
    
    /**
     * 密保信息
     */
   $user_log= $this->user->UserEmptyMiBaoInfo($uid);
   PrInfo((string)$user_log);
//   if($user_log==1024){
//   
//   }
//   PrInfo($this->user_str_log[$user_log]);





  }
  PrInfo($this->user_str_log[$log]);
?>