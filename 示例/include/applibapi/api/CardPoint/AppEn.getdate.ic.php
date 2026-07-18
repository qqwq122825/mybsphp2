<?php


  /*<name>*/
  /***********************接口介绍说明******************************************
  * <appen>getdate.ic</appen>  
  * <appname>取卡模式到期时间</appname> 
  * <sen>0<sen/>                
  * <seng></seng>
  * <lei>ok</lei>               
  * <info>取vip时间</info> 
  * *****************************************************************************
  */
  /*</name>*/

   //oid links file
    $car_id=get_session_value('ic_carid');
    $car_pwd=get_session_value('ic_pwd');
    $daihao=HOST_DAIHAO;

  	    $arr_log=GetCardInfo($car_id,$car_pwd,$daihao);
         
        if($arr_log==1083 || $arr_log==1084){
           
            PrInfo($this->user_str_log[$arr_log]);
            
        }else{
            
            
         

             PrInfo($arr_log['L_vip_unix']); 

          
        }

        




?>