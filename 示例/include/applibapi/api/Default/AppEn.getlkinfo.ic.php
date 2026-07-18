<?php


  /*<name>*/
  /***********************接口介绍说明******************************************
  * <appen>getlkinfo.ic</appen>  
  * <appname>验证登录状态</appname> 
  * <sen>0<sen/>                
  * <seng></seng>
  * <lei>ok</lei>               
  * <info>取vip时间</info> 
  * *****************************************************************************
  */
  /*</name>*/
  

    $car_id=get_session_value('ic_carid');
    $car_pwd=get_session_value('ic_pwd');
    $daihao=HOST_DAIHAO;
    $key=set_data('key');
        

	
         //获取验证信息 COOKIE内之了
  	    $log=CarCookies($car_id,$car_pwd,$daihao);

        if($log==1080){
            PrInfo('1080');
        }else{
            PrInfo($this->user_str_log[$log]);
        }

        




?>