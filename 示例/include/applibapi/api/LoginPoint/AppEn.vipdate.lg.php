<?php


  /*<name>*/
  /***********************接口介绍说明******************************************
  * <appen>vipdate.lg</appen>  
  * <appname>取用户到期时间</appname> 
  * <sen>0<sen/>                
  * <seng></seng>
  * <lei>ok</lei>               
  * <info>取vip时间</info> 
  * *****************************************************************************
  */
  /*</name>*/
   //oid Login file
   //oid links file
   $uid=get_session_value('USER_UID');
   $daihao=HOST_DAIHAO;

   
   
     //登录连接数功能集代码
     //links_chaoshi_login();
  	  //登陆状态
     $log = $this->user->UserLoginInfo();
     if ($log != 1047)PrInfo($this->user_str_log[$log]);
    
        //获取用户信息
		$arr = Login_UserInfo($uid,$daihao);

        PrInfo($arr['L_vip_unix']); 





?>