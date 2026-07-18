<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>setpushdate.ic</appen>  
* <appname>解除绑定</appname> 
* <sen>0<sen/>                
* <seng1></seng>
* <lei>ok</lei>               
* <info>解除绑定-卡模式</info> 
* *****************************************************************************
*/
/*</name>*/
   include (__HOST__.'include/AppEn.Car.php');
   //oid links file

    $newicid=set_data('newicid');
	
	
	
	if(set_data('icid')!=NULL){
		$car_id=set_data('icid');
        $car_pwd=set_data('icpwd');
	}else{
	    $car_id=get_session_value('ic_carid');
        $car_pwd=get_session_value('ic_pwd');
		
    //超时等T出更新
	links_chaoshi_car();
	
	}

    $daihao=HOST_DAIHAO;

         
         
         //获取验证信息 COOKIE内之了
  	    $log=CarCookies($car_id,$car_pwd,$daihao);
        if($log==1080){
            
            //读取用户数据
            $Get_DaTa=GetInfo($car_id,$car_pwd,$daihao);
            
            
            
            








        }else{
         PrInfo($this->user_str_log[$log]);
        }










?>