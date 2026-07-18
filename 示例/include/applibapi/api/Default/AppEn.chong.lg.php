<?php


  /*<name>*/
  /***********************接口介绍说明******************************************
  * <appen>chong.lg</appen>  
  * <appname>软件使用期充值</appname> 
  * <sen>3<sen/>                
  * <seng1>user=>用户账号</seng>
  * <seng2>ka=>卡号</seng>
  * <seng3>pwd=>密码</seng>
  * <lei>ok</lei>               
  * <info>解除绑定</info> 
  * *****************************************************************************
  */
  /*</name>*/
  //oid Login file

  $daihao = HOST_DAIHAO;
  $user = set_data('user');
  $userpwd = set_data('userpwd');
  $userset = set_data('userset');
  $ka = set_data('ka');
  $pwd = set_data('pwd');
  
  
  //验证密码
  
  /**
   * 用户登陆
   */
   
   
  
   if($userset == '1'){
      $log = $this->user->UserWebLogin($user, $userpwd);
      if($log!=1011) PrInfo($this->user_str_log[$log]);
    
   }
  

  
  
  $log= $this->user->UserChong($user,$ka,$pwd,$daihao);
  PrInfo($this->user_str_log[$log]);





 


?>