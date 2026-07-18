<?php


  /*<name>*/
  /**
   * **********************接口介绍说明******************************************
   * <appen>balancededuction.lg</appen>  
   * <appname>余额扣除</appname> 
   * <sen>3<sen/>                
   * <seng1>balance=>余额</seng>
   * <seng1>imag=>验证码</seng>
   * <seng1>table=>日志标题</seng>
   * <lei>ok</lei>               
   * <info>用户登陆使用</info> 
   * *****************************************************************************
   */
  /*</name>*/

  //oid Login file
  //oid links file

  $daihao=HOST_DAIHAO;
  $balance=set_data('balance');
  $table=set_data('table');
  $imag=set_data('imag');
  $uid = get_session_value('USER_UID');


  

  
   //登录连接数功能集代码
  // links_chaoshi_login();
   
  $log=$this->user->UserLoginInfo();
if($log==1047){
    
    $log=$this->user->UserBalancedeDuction($uid,$balance,$table);
    if($log==1098) PrInfo($log);
    PrInfo($this->user_str_log[$log]);
    
    
    
    
}
PrInfo($this->user_str_log[$log]);
  


?>