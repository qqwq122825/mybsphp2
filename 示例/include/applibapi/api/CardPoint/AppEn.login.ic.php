<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>login.ic</appen>  
* <appname>卡模式用户登录验证</appname> 
* <sen>3<sen/>                
* <seng1>icid=>卡号</seng>
* <seng2>icpwd=>密码</seng>
* <seng3>key=>key</seng>
* <lei>ok</lei>               
* <info>登录</info> 
* *****************************************************************************
*/
/*</name>*/
$icid=set_data('icid');
$icpwd=set_data('icpwd');
$key=set_data('key');
$maxoror = set_data('maxoror');
$daihao=HOST_DAIHAO;
//oid links file
if ($Global_DB_AppDaTa_Array['app_MoShi'] !== 'CardPoint')
{
    //PrInfo('1119'); //不对应的模式
    PrInfo($this->user_str_log[1119]);
}
$log=CarLogin_point($icid,$icpwd,$key,$daihao);
IF($log==1081){
    
    
    
    //建立登录限制
    $log = Login_links_mx($icid,$daihao,$maxoror,$icid,$icid);
    if($log!=5047)PrInfo($this->appen_str_log[$log]);
    
    /**
     * 查询用户信息
     */
    $carinfo = GetCardInfo($icid,$icpwd,$daihao);
    if($carinfo==1083 || $carinfo==1084) PrInfo($this->user_str_log[$arr_log]);
           
           
    if($Global_DB_AppDaTa_Array['app_set'] ==1){
       if((string)$carinfo['L_key_info']!=(string)$key){
        
        //注销登录
        set_session_value('ic_carid', ''); //登陆UID
        set_session_value('ic_pwd', ''); //登陆MD7加密
        set_session_value('USER_UID','');

        PrInfo(5035);
       }
    }
           
           
           
      
//---------------------------------------
  
  
//链接数验证


      //$login_ssl = MD5($BSphpSeSsL);
      $this->session->links_add_info('-1', $icid,$key,$daihao,$maxoror);
  
  
//-----------------------------------------
  //记录登录时间用做扣点




    $ic_vipdate=  $carinfo['L_vip_unix'];

    $ic_key=$carinfo['L_key_info'];

    $login_info=NULL;
    if($key=$ic_key & $ic_key!='') $login_info=$Global_DB_AppDaTa_Array['app_logininfo'];

    
    
    
       /**
        * 返回说明
        * 1.= 成功返回1
        * 2.= 登陆成功代号
        * 3.= 用户绑定key
        * 4.= 用户登陆成功返回特定数据
        * 5.= VIP到期时间
        */
         PrInfo("01|1081|$ic_key|$login_info|$ic_vipdate|||||");
    
    
    
    

    
    
}else{

    PrInfo($this->user_str_log[$log]);
}

?>