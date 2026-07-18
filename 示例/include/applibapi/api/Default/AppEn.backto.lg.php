<?php


  /*<name>*/
  /***********************接口介绍说明******************************************
  * <appen>backto.lg</appen>  
  * <appname>找回密码</appname> 
  * <sen>5<sen/>                
  * <seng1>user=>用户账号</seng>
  * <seng1>pwd=>密码</seng>
  * <seng1>pwdb=>再次输入密码</seng>
  * <seng1>wenti=>密保问题</seng>
  * <seng1>daan=>密保答案</seng>
  * <lei>ok</lei>               
  * <info></info> 
  * *****************************************************************************
  */
  /*</name>*/
;
  $daihao = HOST_DAIHAO;
  $user= set_data('user');
  $pwd= set_data('pwd');
  $pwdb= set_data('pwdb');
  $mibao= set_data('mibao');
  $wenti= set_data('daan');
  $img = set_data('img');
  
  

  
  

    
    $log=$this->user->UserMiBaoPwd($user,$pwd,$pwdb,$mibao,$wenti);
     PrInfo($this->user_str_log[$log]);


 
  


?>