<?php


  /*<name>*/
  /***********************接口介绍说明******************************************
  * <appen>getdata.ic</appen>  
  * <appname>取验证数据</appname> 
  * <sen>0<sen/>                
  * <seng></seng>
  * <lei>ok</lei>               
  * <info>取vip时间</info> 
  * *****************************************************************************
  */
  /*</name>*/
   //include (__HOST__.'include/AppEn.Car.php');
   //oid links file
    $car_id=get_session_value('ic_carid');
    $car_pwd=get_session_value('ic_pwd');
    $daihao=HOST_DAIHAO;
    $key=set_data('key');
         
 
	
         //获取验证信息 COOKIE内之了
  	    $log=CarCookies($car_id,$car_pwd,$daihao);

        if($log==1080){
            //查询信息
            $arr_log=GetCardInfo($car_id,$car_pwd,$daihao);
            
            
              if($arr_log['L_key_info']==$key or $Global_DB_AppDaTa_Array['app_set']==0){
                PrInfo($Global_DB_AppDaTa_Array['app_logininfo']);
              }else{
                PrInfo('5033');
              }


        }else{
         PrInfo($this->user_str_log[$log]);
        }

?>