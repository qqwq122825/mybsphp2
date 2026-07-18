<?php

/*<name>*/
/***********************接口介绍说明******************************************
 * <appen>lgkey.lg</appen>  
 * <appname>取得登陆成功返回数据包</appname> 
 * <sen>1<sen/>                
 * <seng1>key=>绑定key</seng>
 * <lei>ok</lei>               
 * <info>取得用户登陆成功返回信息 过期不会返回</info> 
 * *****************************************************************************
 */
/*</name>*/
 
  //oid Login file
  //oid links file
  $daihao=HOST_DAIHAO;
  $key=set_data('key');
  $uid=get_session_value('USER_UID');
  

  
     //登录连接数功能集代码
     //links_chaoshi_login();
	 
  $log=$this->user->UserLoginInfo();
if($log==1047){
    
    
//    /**
//     * 查询用户绑定key
//     */
      if($Global_DB_AppDaTa_Array['app_set'] == 1){
         $user_arr= Login_UserInfo($uid,$daihao);
         //if($arr['L_key_info']!==$user_arr[6])PrInfo('5034');
         if($key!==$user_arr[6])PrInfo('5034');
    }
    
    
    
    //判断是否已经过期
      $arr = Login_UserInfo($uid,$daihao );
      if ($Global_DB_AppDaTa_Array['app_set'] == 1 or $arr['L_vip_unix'] > 0) {
       PrInfo($Global_DB_AppDaTa_Array['app_logininfo']); 
      }else{
       PrInfo('5033');  
      }
    

}
PrInfo($this->user_str_log[$log]);
?>