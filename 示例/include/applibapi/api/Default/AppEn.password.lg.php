<?php


  /*<name>*/
  /***********************接口介绍说明******************************************
  * <appen>password.lg</appen>  
  * <appname>修改密码</appname> 
  * <sen>3<sen/>                
  * <seng1>pwd=>原密码</seng>
  * <seng1>pwda=>新密码</seng>
  * <seng1>pwdb=>再次输入新密码</seng>
  * <lei>ok</lei>               
  * <info></info> 
  * *****************************************************************************
  */
  /*</name>*/

  $daihao = set_data('daihao');
  $user= set_data('user');
  $pwd = set_data('pwd');
  $pwda = set_data('pwda');
  $pwdb = set_data('pwdb');
  $uid = get_session_value('USER_UID');
  $img = set_data('img');


  //检测登录状态
  if($user!="" and $pwd!=""){
    $log = $this->user->UserWebLogin_key($user,$pwd);
    //读取用户配置
    $User_Info = $this->db->my_class_array('bs_php_user', 'user_user', $user,'`user_uid`,`user_daili`,`user_user`');
    $uid = $User_Info['user_uid'];
    
  }else{
  
     //oid links file
	 //登录连接数功能集代码
     //links_chaoshi_login();
     $log =  $this->user->UserLoginInfo();
  }

  
  
  
  if ($log == 1047 or $log == 1011) {
    
    $log= $this->user->UserModifyPassWord($uid,$pwd,$pwda,$pwdb);
    PrInfo($this->user_str_log[$log]);

  }
  PrInfo($this->user_str_log[$log]);


?>