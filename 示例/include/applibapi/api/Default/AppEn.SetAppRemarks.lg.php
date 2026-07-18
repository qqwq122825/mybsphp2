<?php

/*<name>*/
/***********************接口介绍说明******************************************
 * <appen>Perfect.lg</appen>  
 * <appname>完善用户资料</appname> 
 * <sen>3<sen/>                
 * <seng1>qq=>QQ</seng>
 * <seng1>mail=>邮箱</seng>
 * <seng1>Mobile=>电话</seng>
 * <lei>ok</lei>               
 * <info>完善用户资料</info> 
 * *****************************************************************************
 */
/*</name>*/

  //oid Login file
  //oid links file
  $daihao = HOST_DAIHAO;
  $uid = get_session_value('USER_UID');
  

  $remarks = set_data('remarks');


     //登录连接数功能集代码
     //links_chaoshi_login();
  //登陆状态
  $log = $this->user->UserLoginInfo();
  if ($log == 1047) {
    

     
     $sql="UPDATE`bs_php_pattern_login`SET `L_links_info` = '$remarks' WHERE `L_User_uid`='$uid';";
     $tmp=$this->db->Bsphp_my_query($sql);
     if($tmp){
        PrInfo('10001');  
     }else{
        PrInfo('10002');
     }

      





  }

  PrInfo($this->user_str_log[$log]);
?>