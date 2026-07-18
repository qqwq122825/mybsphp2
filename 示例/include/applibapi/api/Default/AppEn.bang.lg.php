<?php


  /*<name>*/
  /***********************接口介绍说明******************************************
  * <appen>bang.lg</appen>  
  * <appname>绑定特征</appname> 
  * <sen>1<sen/>                
  * <seng1>key=>绑定特征</seng>
  * <lei>ok</lei>               
  * <info>绑定特征</info> 
  * *****************************************************************************
  */
  /*</name>*/
  //oid Login file
  //oid links file
  $daihao = HOST_DAIHAO;

  $uid = get_session_value('USER_UID');
 
  $user=set_data('user');
  $pwd=set_data('pwd');
  $key = set_data('key');
  
  
  
  //检测登录状态
  if($user!="" and $pwd!=""){
    $log = $this->user->UserWebLogin_key($user,$pwd);
    //读取用户配置
    $User_Info = $this->db->my_class_array('bs_php_user', 'user_user', $user,'`user_uid`,`user_daili`,`user_user`');
    $uid = $User_Info['user_uid'];
    
  }else{
       //登录连接数功能集代码
     //links_chaoshi_login();
    $log = $this->user->UserLoginInfo();
  }

  
  
  if ($log == 1047 or $log == 1011) {
    
    
    
    
    
    

    if ($key == '') PrLog(5015);//绑定特征不能为空
    //判断是否已经过期
   
          /*查询是否重复绑定*/
    if($Global_DB_AppDaTa_Array['app_key_zhong']==1){
        if(login_key_zhong($daihao,$key)){
           PrLog(5012);//'当前特征已经有人绑定,无法在绑定' 
        }
    }
   


 

    $arr = Login_UserInfo($uid, $daihao);
    
    
    //判断是否已经绑定过了
    if ($arr['L_key_info'] != '') PrLog(5016);//已经绑定了,不需要在绑定
    
    
    
    
    //添加绑定
    $sql = "UPDATE`bs_php_pattern_login`SET`L_key_info`='$key'WHERE  `L_id`='{$arr['L_id']}';";
    $tmp = $this->db->Bsphp_my_query($sql);

    if ($tmp) {
      //PrLog(5013);//'绑定成功!'
      PrInfo('5013');//
    } else {
      PrLog(5014);//绑定失败,请重试!
    }



  }
  PrInfo($this->user_str_log[$log]);


?>