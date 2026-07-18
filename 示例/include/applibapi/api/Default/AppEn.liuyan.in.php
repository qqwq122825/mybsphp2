<?php


  /*<name>*/
  /***********************接口介绍说明******************************************
  * <appen>liuyan.in</appen>  
  * <appname>用户留言</appname> 
  * <sen>7<sen/>                
  * <seng1>table=>标题</seng>
  * <seng2>qq=>qq</seng>
  * <seng3>leix=>类型</seng>
  * <seng6>txt=>留言内容</seng>
  * <seng6>img=>验证码</seng>
  * <lei>ok</lei>               
  * <info>公告接口</info> 
  * *****************************************************************************
  */
  /*</name>*/

  $daihao = HOST_DAIHAO;
  $table = set_data('table');
  $leix = set_data('leix');
  $qq = set_data('qq');
  $txt = set_data('txt');
  $img= set_data('img');
  $uid = get_session_value('USER_UID');
  
  $user=set_data('user');
  $pwd=set_data('pwd');
  
  
  if($user==-1){
    
  /**
   * 提交留言
   */
  $log= $this->user->UserAddLiuYan($leix,$table,$qq,$uid,$daihao,$txt);
  PrInfo($this->user_str_log[$log]);
    
    
  }
  

  //检测登录状态
  if($user!="" and $pwd!=""){
    $log = $this->user->UserWebLogin_key($user,$pwd);
    //读取用户配置
    $User_Info = $this->db->my_class_array('bs_php_user', 'user_user', $user,'`user_uid`,`user_daili`,`user_user`');
    $uid = $User_Info['user_uid'];
  }else{
    $log = $this->user->UserLoginInfo();
  }

  
  
  if ($log == 1047 or $log == 1011) {
  
  
  
  
  /**
   * 提交留言
   */
  $log= $this->user->UserAddLiuYan($leix,$table,$qq,$uid,$daihao,$txt);
  PrInfo($this->user_str_log[$log]);

}

PrInfo($this->user_str_log[$log]);

?>