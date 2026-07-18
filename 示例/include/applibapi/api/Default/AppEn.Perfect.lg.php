<?php

/*<name>*/
/***********************接口介绍说明******************************************
 * <appen>Perfect.lg</appen>  
 * <appname>完善用户资料</appname> 
 * <sen>3<sen/>                
 * <seng1>qq=>QQ</seng>
 * <seng1>mail=>邮箱</seng>
 * <seng1>Mobile=>电话</seng>
 * <lei>ok</lei>               
 * <info>完善用户资料</info> 
 * *****************************************************************************
 */
/*</name>*/

  //oid Login file
  //oid links file
  $daihao = HOST_DAIHAO;
  $uid = get_session_value('USER_UID');
  

  $qq = set_data('qq');
  $mail=set_data('mail');
  $Mobile=set_data('mobile');

     //登录连接数功能集代码
     //links_chaoshi_login();
  //登陆状态
  $log = $this->user->UserLoginInfo();
  if ($log == 1047) {
    
    
    /**
     * 完善资料
     */
   $user_log= $this->user->UserAddInfo($uid,$qq,$mail,$Mobile);
//   if($user_log==1017){
//   PrInfo((string)$user_log);
//   }
   PrInfo($this->user_str_log[$user_log]);





  }

  PrInfo($this->user_str_log[$log]);
?>