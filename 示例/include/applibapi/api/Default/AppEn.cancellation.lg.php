<?php

/*<name>*/
/***********************接口介绍说明******************************************
 * <appen>cancellation.lg</appen>  
 * <appname>注销登陆</appname> 
 * <sen>0<sen/>                
 * <seng1>参数=>接收内容</seng>
 * <lei>ok</lei>               
 * <info>注销用户的登陆</info> 
 * *****************************************************************************
 */
/*</name>*/
  $daihao=HOST_DAIHAO;
  $uid = get_session_value('USER_UID');
  $BSphpSeSsL=set_data('BSphpSeSsL');

    
    
    //oid Login file
    //oid links file
    

    
   $tmp=$this->session->links_delete_one($BSphpSeSsL,$daihao,$uid);
   if($tmp){

    PrInfo(1);
   
   
   }ELSE{
   
    PrInfo(-1);
   
   }
    
    
    

    
    
    
    
    
    
    
?>