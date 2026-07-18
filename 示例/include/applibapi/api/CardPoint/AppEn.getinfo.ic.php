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
    $info=set_data('info');
    

	
  	    $arr_log=GetCardInfo($car_id,$car_pwd,$daihao);
         
        if($arr_log==1083 || $arr_log==1084){
           
            PrInfo($this->user_str_log[$arr_log]);
            
        }else{
            
            
   
            
            
            
            
            /**
            信息字段： 
            激活时间=ReDate   
            激活时Ip=ReIp   
            用户状态=IsLock    
            登录时间=LogInDate   
            登录Ip=LogInIp    
            到期时=VipDate   
            绑定特征=Key
            用户分组名称=Class_Nane
            用户分组别名=Class_Mark
            */
            
            
            $info=str_replace('ReDate',$arr_log['L_re_date'],$info);
            $info=str_replace('ReIp',$arr_log['L_re_ip'],$info);
            $info=str_replace('IsLock',$arr_log['L_IsLock'],$info);
            $info=str_replace('LogInDate', $arr_log['L_login_time'],$info);
            $info=str_replace('LogInIp',$arr_log['L_login_ip'],$info);
            
            if($arr_log['L_key_info']=='')$arr_log['L_key_info']='9981';
            
            $info=str_replace('Key',$arr_log['L_key_info'],$info);
            
            
            
            if($arr_log['L_class']==0){
                $class_array['class_name']="未分组";
                $class_array['calss_mark']="null";
                
            }else{
                
            //获取类型
            $class_array = $this->db->my_class_array('bs_php_userclass', 'class_id', $arr_log['L_class'], ' * ');
                
            }
            
            
            $info=str_replace('Class_Nane',$class_array['class_name'],$info);
            $info=str_replace('Class_Mark',$class_array['calss_mark'],$info);

         
         

          
            $info=str_replace('VipDate',$arr_log['L_vip_unix'],$info);
            
            
            if($arr_log['L_vip_unix']>0){
               $info=str_replace('VipWhether', '1',$info); 
            }else{
               $info=str_replace('VipWhether', '2',$info);
            }

    
    
 
            PrInfo($info); 
            


            

         
        }

        




?>