<?php


  /*<name>*/
  /***********************接口介绍说明******************************************
  * <appen>GetPleaseregister.lg</appen>  
  * <appname>检测账号是否存在</appname> 
  * <sen>1<sen/>                
  * <seng>user=>用户名</seng>
  * <lei>ok</lei>               
  * <info>判断用户账号是否存在</info> 
  * *****************************************************************************
  */
  /*</name>*/
  $daihao = HOST_DAIHAO;
  $user=set_data('user');
  
  $log=$this->user->User_in($user);
  if($log==1001){
    PrInfo((string)$log);
    
  }
  PrInfo($this->user_str_log[$log]);
  




?>