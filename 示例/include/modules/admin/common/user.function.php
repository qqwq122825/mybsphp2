<?php


  /**
   * @会员操作
   * 会员的所有操作都会在里调用
   * 
   */

  

    


  /**
   * @用到变量初始化
   */







  /**
   * @用户是否存在
   * 只用作用户监测是否存在
   * 
   * 1001=你选择用户名可以使用
   */
  function User_in($user) {
    global $db,$global_array_set,$Coding_array;
    
    if($global_array_set['ini_re_user_mail']=='checked'){
        if (!PiPei($user, 3)) return 1088; //** 1004 账号格式错误,只能选择 26位字母+数字和_下滑线 */
    }else{
        if (!PiPei($user, 1)) return 1004; //** 1004 账号格式错误,只能选择 26位字母+数字和_下滑线 */
    }
    
    if ($user == '') return 1039; //**  检测账号不能为空!*/
    if (set32($user)) return 1040; //**  账号不能包含空格!*/
    $sql = "SELECT`user_user`FROM`bs_php_user`WHERE`user_user`LIKE CONVERT(_utf8'$user'USING {$Coding_array[$Coding_array['coding']][0]})COLLATE {$Coding_array[$Coding_array['coding']][1]} ";
    $tpm = $db->Bsphp_my_array($sql);
    unset($db);
	unset($global_array_set);
	unset($Coding_array);
	
    if ($tpm) {
      return 1000; //** 用户已经存在。请选择其他!*/
    } else {
      return 1001; //**  你选择用户名可以使用!*/
    }
  }




  /**
   * @会员添加注册
   * 
   * 参数1=添加账号
   * 参数2=密码
   * 参数3=再次密码
   * 参数4=QQ
   * 参数5=邮箱
   * 
   * 备注：
   * 注册时候会再次检测账号是否存在,检测账号密码长度 3-15位数
   * 
   * 1005=恭喜你注册成功
   */
  function UserAdd($user, $pass, $passb, $QQ, $mail, $Yao,$Mobile="",$user_mibao_wenti="",$user_mibao_daan="") {
    $DATE = HOST_DATE;
    $IP = Bsphp_HOST_IP;
    global $db , $global_array_set;
    $user=strtolower($user);
    //-----------------------------------------------------------

    
    
    if($global_array_set['ini_er_qq_not']=='checked'){
        if($QQ=='') return 1092;
        if (!PiPei($QQ, 2)) return 1093; 
     }
    
    if($global_array_set['ini_re_mail_not']=='checked'){
        if($mail=='') return 1090;
        if (!PiPei($mail, 3)) return 1091; 
        
     }
    
    //-----------------------------------------------------------
    
    //判断是否为邮箱注册
    if($global_array_set['ini_re_user_mail']=='checked'){
        if (!PiPei($user, 3)) return 1088; //** 1004 账号格式错误,只能选择 26位字母+数字和_下滑线 */
        if (strlen($user) < 2 | strlen($user) > 50) return 1003; //** 1003 账号长度错误,限3-15位*/
    }else{
        if (!PiPei($user, 1)) return 1004; //** 1004 账号格式错误,只能选择 26位字母+数字和_下滑线 */
        if (strlen($user) < 2 | strlen($user) > 14) return 1003; //** 1003 账号长度错误,限3-15位*/
        
    }
    

    //判断是否需要邮件激活
    $user_IsLock=0;
    if($global_array_set['ini_re_mail_set']=='checked'){
        $user_IsLock=-2;
     }

    
    
    if (User_in($user) == 1000) return 1000; //** 1000 用户已经存在。请选择其他!*/
    if ($pass != $passb) return 1002; //** 1002 2次密码输入不一致!*/
    if (strlen($pass) < 2 | strlen($pass) > 14) return 1007; //** 1007 密码长度 3-15位数 */
    $pass = md6($pass);
    $sql = "INSERT INTO`bs_php_user` (`user_uid`, `user_user`, `user_pwd`, `user_IsLock`, `user_email`, `user_mail_ok`, `user_qq`, `user_Mobile`, `user_mibao_wenti`, `user_mibao_daan`, `user_rmb`, `user_jifen`, `user_re_ip`, `user_re_date`, `user_Login_ip`, `user_Login_date`, `user_CaoShi`, `user_yao_User`, `user_yao_Shu`, `user_Zhe`, `user_LoGinNum`, `user_DenJi_tmp`, `user_DenJi`, `user_daili`) VALUES(NULL,'$user','$pass','$user_IsLock','$mail','0','$QQ','$Mobile','$user_mibao_wenti','$user_mibao_daan','0.00','0','$IP','$DATE',NULL,NULL,'0','$Yao','0','0.0','0','0','0','0');";
    if ($db->Bsphp_my_query($sql)) {
    
    
    //发送邮件激活
    if($global_array_set['ini_re_mail_set']=='checked'){
       $end=Send_Mail($user,"{$global_array_set['ini_name']}{$user}帐号注册成功赶快激活它是吧！","尊敬用户:{$user} \n\n\n你在{$global_array_set['ini_name']}注册帐号还需要激活,请点击下面链接激活它吧 \n\n {$global_array_set['ini_url']}index.php?Activating=".md5(HOST_UNIX.Bsphp_HOST_PASS_KEY.$user.Bsphp_HOST_COOKIES_KEY).'&user='.$user.'&time='.HOST_UNIX);
        return 1089; //** 1005 恭喜你注册成功! */
    }
        
	unset($db);
	unset($global_array_set);	
		
      return 1005; //** 1005 恭喜你注册成功! */
    } else {
      return 1006; //** 1006 账号注册失败 */
    }
  }





  /**
   * @超时更新
   * 
   * 参数1=用户UID
   * 这里更新用户最后操作时间,用这个时间做一些判断
   * 
   * 1041=执行正常
   * 1042=执行失败
   */
  function UserCaoShiInfo($uid) {


    $UNIX = HOST_UNIX;
    global $db,$Coding_array;
    $sql = "UPDATE`bs_php_user`SET`user_CaoShi`='$UNIX',`user_DenJi`=`user_DenJi`+('$UNIX'-`user_DenJi_tmp`),`user_DenJi_tmp`='$UNIX'WHERE`bs_php_user`.`user_uid`='$uid';";
    
	$tmp = $db->Bsphp_my_query($sql);
	unset($db);
	unset($global_array_set);	
    if ($tmp==FALSE) {
	
      return 1042;
    } else {

      return 1041;

    }

  }










  /**
   * @用户登陆状态检测
   * 
   * 检测用户是否是登陆状态
   * 
   * 1047=正常
   * 
   */
  function UserLoginInfo() {
    global $db,$global_array_set;
    /*
    $MD7 = md7($uid.$cookies_pwd.$cookies_DATE.$cookies_ip.$LoGinNum);
    set_cookie('USER_UID', $uid); //登陆UID
    set_cookie('USER_YSE', $cookies_pwd); //登陆MD7加密
    set_cookie('USER_DATE', $cookies_DATE); //上一次登陆时间
    set_cookie('USER_IP', $cookies_ip); //上一次登陆IP
    set_cookie('USER_MD7', $MD7); //OOKIE MD7验证串
    */

    $api_USER_UID = set_cookie_value('USER_UID');
    $api_USER_YSE = set_cookie_value('USER_YSE');
    $api_USER_DATE = set_cookie_value('USER_DATE');
    $api_USER_IP = set_cookie_value('USER_IP');
    $api_USER_MD7 = set_cookie_value('USER_MD7');
    //---------------------------------------------
    //判断COOKIE状态
    if ($api_USER_UID == '' or $api_USER_YSE == '' or $api_USER_MD7 == '') return 1049; //请登陆





    //*--------------------------------------------------- SQL查询语句
    $sql = "SELECT`user_uid`,`user_user`,`user_pwd`,`user_IsLock`,`user_CaoShi`,`user_LoGinNum`FROM`bs_php_user`WHERE`user_uid`='$api_USER_UID'";
    //WHERE`user_user`LIKE CONVERT(_utf8'$api_USER_UID'USING {$Coding_array[$Coding_array['coding']][0]})COLLATE {$Coding_array[$Coding_array['coding']][1]} 
    $arr = $db->Bsphp_my_array($sql);
    if (!$arr) return 1043; //账号不存在
    //------------------------------------赋值
    $USER_UID = $arr['user_uid'];
    $USER_PWD = $arr['user_pwd'];
    $USER_PWD = md7($USER_PWD);
    $USER_IsLock = $arr['user_IsLock'];
    //$LoGinNum = $arr['user_LoGinNum'];
    $LoGinNum = 'ALL';
    $ChaoShi = $arr['user_CaoShi'];
    //----------------------------------------------------
    
    if ($arr['user_IsLock'] ==-2) {
        
       //发送邮件激活
       if($global_array_set['ini_re_mail_set']=='checked'){
       $end=Send_Mail($user,"{$global_array_set['ini_name']}{$user}帐号需要激活才可以使用！","尊敬用户:{$user} \n\n\n你在{$global_array_set['ini_name']}注册帐号还需要激活,请点击下面链接激活它吧 \n\n {$global_array_set['ini_url']}index.php?Activating=".md5(HOST_UNIX.Bsphp_HOST_PASS_KEY.$user.Bsphp_HOST_COOKIES_KEY).'&user='.$user.'&time='.HOST_UNIX);
        return 1094;  //** 1005 恭喜你注册成功! */
       }
        
         
    }

    
    
    //状态判断
    if ($USER_IsLock != 0) return 1044; //账号已经被冻结禁止登陆
    $HOST_UNIX = HOST_UNIX;
    $ChaoShi_UNIX = $HOST_UNIX - $ChaoShi;
    if ($ChaoShi_UNIX > 90000) return 1045; //登陆超时,由于你长时间不停留请重新登陆
    //-----------------------------------------------------

    //*验证MD7*/
    $md7 = md7($api_USER_UID.$api_USER_YSE.$api_USER_DATE.$api_USER_IP.$LoGinNum);

    if ($md7 != $api_USER_MD7) return 1046; //你已经在别处登陆,被迫等出。
    //------------------------------------------------------
    //*账号密码验证*/

	unset($db);
	unset($global_array_set);	
	
    if ($api_USER_UID == $USER_UID & $USER_PWD == $api_USER_YSE) {
      /**
       * @执行更新在线累计时间
       */
      UserCaoShiInfo($api_USER_UID);
      return 1047; //已经登陆
    } else {
      return 1048; //你需要登陆才可以访问
    }


  }










  /**
   * @用户登陆
   * 
   * 参数1=用户账号
   * 参数2=用户密码
   * 
   * 
   * 验证密码错误放到最后面对就直接登陆错误在判断是不是密码错误
   * 
   * 1011=登陆成功
   */
  function UserWebLogin($user, $pass) {
    
    $DATE = HOST_DATE;
    $IP = Bsphp_HOST_IP;
    $UNIX = HOST_UNIX;
    global $db,$global_array_set,$Coding_array;
    //if (strlen($user) < 2 | strlen($user) > 14 or !PiPei($user, 1)) return 1008; //** 1008 账号错误*/
    
    
    if (strlen($pass) < 2 | strlen($pass) > 14) return 1009; // 1009 密码错误*/
    $sql = "SELECT`user_uid`,`user_user`,`user_pwd`,`user_IsLock`,`user_LoGinNum`,`user_Login_ip`,`user_Login_date`FROM`bs_php_user`WHERE`user_user`LIKE CONVERT(_utf8'$user'USING {$Coding_array[$Coding_array['coding']][0]})COLLATE {$Coding_array[$Coding_array['coding']][1]} ";
    $pass = md6($pass);
    $arr = $db->Bsphp_my_array($sql);
    if (!$arr) return 1010; // 1010登陆账号不存在   //返回否定的话就是SQ;查询不存在*/
    if ($arr['user_IsLock'] ==-2) {
        
       //发送邮件激活
       if($global_array_set['ini_re_mail_set']=='checked'){
       $end=Send_Mail($user,"{$global_array_set['ini_name']}{$user}帐号需要激活才可以使用！","尊敬用户:{$user} \n\n\n你在{$global_array_set['ini_name']}注册帐号还需要激活,请点击下面链接激活它吧 \n\n {$global_array_set['ini_url']}index.php?Activating=".md5(HOST_UNIX.Bsphp_HOST_PASS_KEY.$user.Bsphp_HOST_COOKIES_KEY).'&user='.$user.'&time='.HOST_UNIX);
        return 1094;  //** 1005 恭喜你注册成功! */
       }
        
         
    }


    if ($arr['user_IsLock'] != 0) return 1021; //账号已经被冻结禁止登陆
    if (strtolower($arr['user_user']) == strtolower($user) & $pass == $arr['user_pwd']) {
      ///更新登陆时间和IP  和超时
      $uid = $arr['user_uid'];




      //保存本次登陆信息
      $sql = "UPDATE`bs_php_user`SET`user_DenJi_tmp`='$UNIX',`user_Login_ip`='$IP',`user_Login_date`='$DATE',`user_CaoShi`='$UNIX',`user_LoGinNum`=`user_LoGinNum`+1 WHERE`bs_php_user`.`user_uid`='$uid';";

      $tmp = $db->Bsphp_my_query($sql);
    unset($db);
	unset($global_array_set);	
    unset($Coding_array);
      /**
       * 
       * 登陆成功代码
       * 
       */

      /**
       * @设置COOKIES
       * 
       * 
       */
      $sen = 'USER';
      $cookies_pwd = md7($pass);
      $cookies_ip = $arr['user_Login_ip'];
      $cookies_DATE = $arr['user_Login_date'];
      $LoGinNum = $arr['user_LoGinNum'];
      

      /**
       *  MD7=数据验证  最后面的是登陆次数验证
       */
       
       
       //取消都次登录
      $LoGinNum = 'ALL';
      $MD7 = md7($uid.$cookies_pwd.$cookies_DATE.$cookies_ip.$LoGinNum);
      set_cookie("USER_UID", $uid); //登陆UID
      set_cookie('USER_YSE', $cookies_pwd); //登陆MD7加密
      set_cookie('USER_DATE', $cookies_DATE); //上一次登陆时间
      set_cookie('USER_IP', $cookies_ip); //上一次登陆IP
      set_cookie('USER_MD7', $MD7); //OOKIE MD7验证串
     
   //  print_R($_SESSION);
     
      return 1011; //** 登陆成功 */
    } else {
      if ($pass != $arr['user_pwd']) return 1012; //** 密码错误*/
    }
    return 1013;
  }


  /**
   * @用户登陆验证不设置 Cookies
   * 
   * 参数1=用户账号
   * 参数2=用户密码
   * 
   * 
   * 验证密码错误放到最后面对就直接登陆错误在判断是不是密码错误
   * 
   * 1011=登陆成功
   */
  function UserWebLogin_key($user, $pass) {
    
    $DATE = HOST_DATE;
    $IP = Bsphp_HOST_IP;
    $UNIX = HOST_UNIX;
    global $db,$global_array_set,$Coding_array;
    //if (strlen($user) < 2 | strlen($user) > 14 or !PiPei($user, 1)) return 1008; //** 1008 账号错误*/
    
    
    if (strlen($pass) < 2 | strlen($pass) > 14) return 1009; // 1009 密码错误*/
    $sql = "SELECT`user_uid`,`user_user`,`user_pwd`,`user_IsLock`,`user_LoGinNum`,`user_Login_ip`,`user_Login_date`FROM`bs_php_user`WHERE`user_user`LIKE CONVERT(_utf8'$user'USING {$Coding_array[$Coding_array['coding']][0]})COLLATE {$Coding_array[$Coding_array['coding']][1]} ";
    $pass = md6($pass);
    $arr = $db->Bsphp_my_array($sql);
    if (!$arr) return 1010; // 1010登陆账号不存在   //返回否定的话就是SQ;查询不存在*/


    unset($db);
	unset($global_array_set);	
    unset($Coding_array);
    
    if ($arr['user_IsLock'] != 0) return 1021; //账号已经被冻结禁止登陆
    if (strtolower($arr['user_user']) == strtolower($user) & $pass == $arr['user_pwd']) {
      ///更新登陆时间和IP  和超时
      $uid = $arr['user_uid'];


      /**
       * 
       * 登陆成功代码
       * 
       */
      return 1011; //** 登陆成功 */
    } else {
      if ($pass != $arr['user_pwd']) return 1012; //** 密码错误*/
    }
    
    return 1013;
  }





  /**
   * @修改个人信息
   * 
   * 参数1=用户uid
   * 参数2=$qq
   * 参数3=邮箱
   * 参数3=电话
   * 
   * 完善个人信息每个信息都会匹配好
   * 
   * 1017=保存成功
   * 1018=保存失败
   */
  function UserAddInfo($uid, $qq, $mail, $Mobile) {
    global $db;
    if (!PiPei($qq, 2)) return 1014; //**  1014 QQ号错误*/
    if (!PiPei($mail, 3)) return 1015; //**  1016 邮箱错误*/
    if (!PiPei($Mobile, 4)) return 1016; //**  1015 手机号码错误*/
    $sql = "UPDATE`bs_php_user`SET`user_email`='$mail',`user_qq`='$qq',`user_Mobile`='$Mobile'WHERE`bs_php_user`.`user_uid`='$uid';";
    $tmp = $db->Bsphp_my_query($sql);
    unset($db);
    if ($tmp) return 1017; //保存成功
    else  return 1018; //保存失败
  }





  /**
   * @检测密保信息是否已经填写
   * 
   * 参数1=用户UID
   * 参数2=密保问题
   * 参数3=密保答案
   * 1051 = 未填写
   * 1023 = 你的密保信息已经填写
   * 
   * 
   */
  function UserEmptyMiBaoInfo($uid) {
    global $db;

    $sql = "SELECT`user_mibao_wenti`,`user_mibao_daan`FROM`bs_php_user`WHERE`user_uid`=$uid";
    $arr = $db->Bsphp_my_array($sql);
	unset($db);
    if (!$arr) return 1022; //记录查询失败
	
	

	
    if (empty($arr['user_mibao_wenti']) and empty($arr['user_mibao_daan'])) {
      return 1051; //密保信息未填写
    } else  return 1023; //你的密保信息已经填写
  }








  /**
   * @完善密保信息
   * 
   * 参数1=用户UID
   * 参数2=密保问题
   * 参数3=密保答案
   * 
   * 1024=添加成功
   */
  function UserMiBaoInfo($uid, $WenTi, $DaAn) {
    global $db;
    if (strlen($WenTi) < 3 or strlen($DaAn) < 3) return 1050; //密保不能少于4字符
    if (set32($WenTi) == true or set32($DaAn) == true) return 1019; //不能输入空格
    if ($WenTi == '' or $DaAn == '') return 1020; //请输入密保问题或者答案
    $sql = "SELECT`user_mibao_wenti`,`user_mibao_daan`FROM`bs_php_user`WHERE`user_uid`=$uid";
    $arr = $db->Bsphp_my_array($sql);
    if (!$arr) return 1022; //记录查询失败
    if (empty($arr['user_mibao_wenti']) and empty($arr['user_mibao_daan'])) {
      $sql = "UPDATE`bs_php_user`SET`user_mibao_wenti`='$WenTi',`user_mibao_daan`='$DaAn'WHERE`bs_php_user`.`user_uid`='$uid';";
      $tmp = $db->Bsphp_my_query($sql);
      unset($db);
      if ($tmp) return 1024; //密保信息添加成功
      else  return 1025; //密保添加失败
    } else  return 1023; //你的密保信息已经填写
  }






  /**
   * @修改密码
   * 
   * 参数1=用户UID
   * 参数2=旧密码
   * 参数3=新密码
   * 参数4=再次新密码
   * 
   * 修改密码只通过旧密码就可以修改
   */
  function UserModifyPassWord($uid, $pass, $PassWord, $PassWordb) {
    global $db;
    
    
    
    
    if ($pass==NULL) return 1027; //请输入旧密码
    if (set32($PassWord)) return 1026; //密码不能包含空格
    if (strlen($PassWord) < 2 | strlen($PassWord) > 14) return 1007; //** 1007 密码长度 3-15位数 */
    if ($PassWord != $PassWordb) return 1002; //2次密码输入不一致
    if ($pass == $PassWord) return 1028; //旧密码不能和新密码一致
    $sql = "SELECT`user_pwd`FROM`bs_php_user`WHERE`user_uid`=$uid";
    $arr = $db->Bsphp_my_array($sql);
    if (!$arr) return 1022; //记录查询失败
    $pass = md6($pass);
    $PassWord = md6($PassWord);
    if ($arr['user_pwd'] == $pass) {
      $sql = "UPDATE`bs_php_user`SET`user_pwd`='$PassWord'WHERE`bs_php_user`.`user_uid`='$uid';";
      $tmp = $db->Bsphp_my_query($sql);
      unset($db);
      if ($tmp) {
        return 1031;
      } //密码修改成功
      return 1029; //密码修改失败
    }
    return 1030; //旧密码不正确,请重新输入
  }





  /**
   * @找回密码
   * 
   * 参数1=用户账号
   * 参数2=用户新密码
   * 参数3=再次输入用户新密码
   * 参数4=密保问题
   * 参数5=密保答案
   * 
   * 通过密保信息找回密码
   */
  function UserMiBaoPwd($user, $pwd, $pwdb, $MiBaoWenTi, $MiBaoDaAn) {
    global $db,$Coding_array;
    if (empty($user) or empty($pwd) or empty($pwdb) or empty($MiBaoWenTi) or empty($MiBaoDaAn)) {
      return 1032; //表单信息不能为空,请返回填写
    }
    if ($pwd != $pwdb) return 1002; //表单信息不能为空,请返回填写
    if (set32($pwd)) return 1026; //密码不能包含空格
    if (strlen($pwd) < 2 | strlen($pwd) > 14) return 1007; // 1007 密码格式错误*/
    $sql = "SELECT`user_uid`,`user_mibao_wenti`,`user_mibao_daan`FROM`bs_php_user`WHERE`user_user`LIKE CONVERT(_utf8'$user'USING {$Coding_array[$Coding_array['coding']][0]})COLLATE {$Coding_array[$Coding_array['coding']][1]} ";
    $arr = $db->Bsphp_my_array($sql);
    if (!$arr) return 1022; //记录查询失败
    if ($arr['user_mibao_wenti'] == $MiBaoWenTi & $arr['user_mibao_daan'] == $MiBaoDaAn) {
      $uid = $arr['user_uid'];
      $pwd = md6($pwd);
      $sql = "UPDATE`bs_php_user`SET`user_pwd`='$pwd'WHERE`bs_php_user`.`user_uid`='$uid';";
      $tmp = $db->Bsphp_my_query($sql);
      unset($db);
	  unset($Coding_array);
      if ($tmp) {
        return 1033;
      } //密码已经成功通过密保信息修改
      else {
        return 1035;
      } //密码找回修改失败
    } else {
      return 1034; //密保信息错误
    }







  }










  /**
   * @软件使用期续费-登陆模式
   * 
   * 参数1=uid bs_php_pattern_login 数据下的uid
   * 参数2=充值卡卡号
   * 参数3=充值卡密码
   * 
   * 
   *  这个函数调用的是include目录下AppEn.Login.php
   */
  function UserChong($user, $ka, $pwd) {
    global $db;
    if ($user == '') return 1052; //1052充值账号不能为空
    if ($ka == '') return 1053; //1053充值卡号不能为空
    if ($pwd == '') return 1054; //1054充值卡密码不能为空
    if (set32($user)) return 1055; //1055充值账号不能包含空格
    if (set32($ka)) return 1056; //1056充值卡号不能包含空格
    if (set32($pwd)) return 1057; //1057充值卡密码不能包含空格


    ///////*****************获取UID和判断用户是否存在
    $uid = $db->my_class_array('bs_php_user', 'user_user', $user, '`user_uid`');
    if ($uid == 0) return 1059; //用户不存在
    //




    //-*********************查询充值卡是否存在
    //`car_id`,`car_name`,`car_pwd`,`car_IsLock`,`car_DaiHao`,`car_TianShu`,`car_zhuangtai`FROM`bs_php_cardseries`

    $sql = "SELECT*FROM`bs_php_cardseries`WHERE`car_name`=\"$ka\"AND`car_pwd`=\"$pwd\"";
    $ka_arr = $db->Bsphp_my_array($sql);

    $sql = "SELECT`app_daihao`,`app_MoShi`FROM`bs_php_appinfo`WHERE`app_daihao`='{$ka_arr['car_DaiHao']}'";
    $app_array = $db->Bsphp_my_array($sql);


    if (!$ka_arr) return 1060; //充值卡账号密码错误或者不存在
    $car_TianShu = $ka_arr['car_TianShu'];
    $car_diahao = $ka_arr['car_DaiHao'];
    $ka_id = $ka_arr['car_id'];
    
    
//          <option value="0">天</option>
//          <option value="1">月</option>
//          <option value="2">年</option>
//          <option value="3">小时</option>
//          <option value="4">分种</option>
//          <option value="5">秒种</option>
    
    
    if($ka_arr['car_type']==1){
       $daty_shu = 2678400;  
       
    }elseif($ka_arr['car_type']==2){
       $daty_shu=31536000;  
       
    }elseif($ka_arr['car_type']==3){
       $daty_shu = 3600;  
       
    }elseif($ka_arr['car_type']==4){
       $daty_shu = 60;  
       
    }elseif($ka_arr['car_type']==5){
       $daty_shu = 1;  
       
    }else{
       $daty_shu = 86400;   
    }
    

    
    $ka_date_unix = $car_TianShu * $daty_shu; //计算卡的时间 1天86400秒*天数数
    

        
    
    if ($ka_arr['car_IsLock'] != 0) return 1064; //充值卡已经充值过了

    //**********************查询用户是否在使用当前代号软件和获取对应信息
    //$udi_arr = Login_UserInfo($uid, $car_diahao);

    //********查询对应 Login_UserInfo函数 由于在其他文件使用
    $sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_User_uid`='$uid'AND`L_daihao`='$car_diahao';";
    $udi_arr = $db->Bsphp_my_array($sql);
    if (!is_array($udi_arr)) {
    //添加特征
    Login_add_key($uid, $car_diahao, '0','');  //添加特征
    
    
    $sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_User_uid`='$uid'AND`L_daihao`='$car_diahao';";
    $udi_arr = $db->Bsphp_my_array($sql);
    if (!is_array($udi_arr)) return 1061;
    
        }
        

    
    //--------------------


    if (!$udi_arr) return 1061; //用户没有使用过要充值软件,拒绝充值
    
    
    $vip_date_UNIX = $udi_arr['L_vip_unix'];
    
        
    

        //3575=几点模式 3574计时模式
        if($app_array['app_MoShi']==3575){
            
            
            
      //----------------------------------------------------------------------  3575Begin
    //*//////////////////////判断账号是否是否已经到期
    if ($vip_date_UNIX > 0) {
      ////////////////////////账号没有到期////////////////////////////
      
      //判断是否重计时间
      if($ka_arr['car_weight']==1){
        $xin_vip_date_unix = $ka_date_unix;
        
      }else{ 
        $xin_vip_date_unix = $vip_date_UNIX + $ka_date_unix;
      }
      


    } else {
      ////////////////////////账号已经到期/////////////////////////////
      $xin_vip_date_unix =   $ka_date_unix;
    }
    
      //----------------------------------------------------------------------  3575end    
            
            
            
        }else{
            
      //----------------------------------------------------------------------  3574Begin      
            
    //*//////////////////////判断账号是否是否已经到期
    if ($vip_date_UNIX > HOST_UNIX) {
      ///////////////////////账号没有到期/////////////////////////////
         
      //判断是否重计时间
      if($ka_arr['car_weight']==1){
        $xin_vip_date_unix = HOST_UNIX + $ka_date_unix;
        
      }else{ 
        $xin_vip_date_unix = $vip_date_UNIX + $ka_date_unix;
      }
      


    } else {
      ////////////////////////账号已经到期/////////////////////////////
      $xin_vip_date_unix = HOST_UNIX + $ka_date_unix;
    }
    
            
       //----------------------------------------------------------------------  3574end         
 
            
        }




    

    $vip_date = $xin_vip_date_unix;
    //****************************执行SQL语句
    $sql = "UPDATE`bs_php_pattern_login`SET`L_class`='{$ka_arr['car_class']}',`L_vip_unix`='$vip_date'WHERE`L_User_uid` ='$uid'AND`L_daihao`='$car_diahao' LIMIT 1;";
    $tmp = $db->Bsphp_my_query($sql);
    if ($tmp) {
      //**********设置充值卡状态
      $HOST_DATE=HOST_DATE;
      $user_name=$db->my_class_array('bs_php_user','user_uid',$uid,'`user_user`');
      $sql = "UPDATE`bs_php_cardseries`SET`car_IsLock`='1',`car_chong_uid`='$uid',`car_pur_date`='$HOST_DATE',`car_cong_user`='$user_name'WHERE`bs_php_cardseries`.`car_id`='$ka_id';";
      $tmp = $db->Bsphp_my_query($sql);
      if (!$tmp) $db->Bsphp_my_query($sql);
      return 1062; //充值成功,赶快去使用吧!
    } else {
      return 1063; //充值失败!
    }
    return 1055;
  }











  /**
   * @用户留言
   * 
   * 参数1=类型
   * 参数2=标题
   * 参数3=QQ
   * 参数4=UID
   * 参数5=代号
   * 参数6=留言内容
   * 
   */
  function UserAddLiuYan($add_leix, $add_table, $add_qq, $add_uid, $add_daihao, $add_txt) {
    if ($add_table == '') return 1067; //请输入标题
    if ($add_txt == '') return 1068; //请输入留言内容
    if ($add_qq == '') return 1095; //请输入联系方式
    



    global $db;
    $add_date = HOST_DATE;
    $sql = "INSERT INTO`bs_php_add` (`id`, `add_leix`, `add_table`, `add_qq`, `add_txt`, `add_uid`, `add_date`, `add_daihao`) VALUES(NULL,'$add_leix','$add_table','$add_qq','$add_txt','$add_uid','$add_date','$add_daihao');";
    $tmp = $db->Bsphp_my_query($sql);
    if ($tmp) {
      return 1065;
    } else {
      return 1066;
    }
  }










  /**
   * @加入RMB交易订单
   * 
   * 参数1=订单号
   * 参数2=用户Uid
   * 参数3=充值方式   ail=支付宝  tong=财富通
   * 参数3=充值金额
   * 
   */
  function PayAdd($pay_id, $uid, $apy_lei, $pay_rmb) {
    global $db;
    $date=HOST_DATE;
    $sql = "INSERT INTO`bs_php_rmb_pay_log`(`id`, `pay_id`, `pay_uid`, `pay_lei`, `pay_rbm`, `pay_zhuangtai`, `pay_info1`, `pay_info2`, `pay_date`)  VALUES (NULL,'$pay_id','$uid','$apy_lei','$pay_rmb','1','','','$date');";
    $tmp = $db->Bsphp_my_query($sql);
    if ($tmp) {
      return 1;
    } else {
      return 0;
    }

  }




  /**
   * @设置成功  状态码返回设置
   * 
   * 参数1=订单号
   * 
   * 
   * 备注:
   * pay_zhuangtai = 2 状态修改失败
   * pay_zhuangtai = 3 人名币未修改
   * 
   */
  function PayInfp($pay_id) {
    global $db;
    $pay_arr = $db->my_class_array('bs_php_rmb_pay_log', 'pay_id', $pay_id, ' * ');
    $uid = $pay_arr['pay_uid'];
    $rmb = $pay_arr['pay_rbm'];
    $zhuangtai = $pay_arr['pay_zhuangtai'];
    if ($zhuangtai == '0') { //判断是否已经修改过了
      //这里已经修改过
      return true; //已经是0返回真


    //修改订单状态
    } else {
      $pay_sql = "UPDATE`bs_php_rmb_pay_log`SET`pay_zhuangtai`='0'WHERE`bs_php_rmb_pay_log`.`pay_id`='$pay_id';";//购买记录日志
      $uesr_sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$rmb'WHERE`bs_php_user`.`user_uid`='$uid';";     //Rmb添加记录
      
      /** @修改状态 第一步 下单状态修改*/
      $pay_tmp = $db->Bsphp_my_query($pay_sql);
      if ($pay_tmp) { //状态修改成功




         /** @修改状态 第二步  RMB金额修改*/
        $uesr_tmp = $db->Bsphp_my_query($uesr_sql);
        if ($uesr_tmp) { //人民币修改成功
                   
       /** ---------------------执行佣金推广开始-----------------*/
        
        //读取邀请配置
        $COMFIGARR=$db->my_class_array('bs_php_user_yaoqing_ini','ini','yaoini',' * ');
        //you_weizi==0 表示在充值时候获得佣金
        
        //频道开放模式
        $Yao_ini_off=$COMFIGARR['ticheng_oof'];
        
        if($COMFIGARR['you_weizi']==0 & $Yao_ini_off!='0'){
            
            //用户RMB金额
            $User_RMB=$rmb;
            //上级用户帐号
            $User_Yao_User = $User_Inof['user_yao_User'];
            
          
          
          //--------------------3级计算--------------------------------------------------------
          
            //用户提成佣金计算
            $ticheng_baifenbi_1=$COMFIGARR['ticheng_baifenbi_1']/100;
            $ticheng_baifenbi_2=$COMFIGARR['ticheng_baifenbi_2']/100;
            $ticheng_baifenbi_3=$COMFIGARR['ticheng_baifenbi_3']/100;
            
            
            /** 一级代理 */
            if($ticheng_baifenbi_1>0){
                //计算分红
                $User_RMB_A=$User_RMB*$ticheng_baifenbi_1;
                //读取上级用户UID
                $User_Arr_A=$db->my_class_array('bs_php_user','user_user',$User_Yao_User,'`user_uid`,`user_user`,`user_yao_User`,`user_daili`,`user_re_date`');
                if($User_Arr_A){
                      //计算一级提成金额
                      $sql_A="UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$User_RMB_A'WHERE`bs_php_user`.`user_user`='{$User_Arr_A['user_user']}';";
                      //计算注册时间与是否可以提成
                      $yoa_User_date=strtotime($User_Arr_A['user_re_date']);
                      $yoa_Today_date=3600*24*$COMFIGARR['ticheng_time'];
                      $yoa_User_date=$yoa_User_date+$yoa_Today_date;
                      //执行SQL
                      //echo date('Y-m-d H:i:s',$yoa_User_date).'-'.$yoa_User_date.'-'.HOST_UNIX.'<BR/>';
                      if($Yao_ini_off=='2' & $User_Arr_A['user_daili']=='1' & $yoa_User_date>HOST_UNIX or $Yao_ini_off=='1' & $yoa_User_date>HOST_UNIX){
                        if($db->Bsphp_my_query($sql_A)) AddAppenLog(5,"你的下线《A》级用户消费获得:$User_RMB_A 佣金",$User_Arr_A['user_user'],0);
                      }  
                }
            }
            
            
            
            /** 二级代理 */
            if($ticheng_baifenbi_2>0){
                //计算分红
                $User_RMB_B=$User_RMB*$ticheng_baifenbi_2;
                //读取上级用户UID
                $User_Arr_B=$db->my_class_array('bs_php_user','user_user',$User_Arr_A['user_yao_User'],'`user_uid`,`user_user`,`user_yao_User`,`user_daili`,`user_re_date`');
                if($User_Arr_B){
                      //计算一级提成金额
                      $sql_B="UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$User_RMB_B'WHERE`bs_php_user`.`user_user`='{$User_Arr_B['user_user']}';";
                      //计算注册时间与是否可以提成
                      $yoa_User_date=strtotime($User_Arr_B['user_re_date']);
                      $yoa_Today_date=3600*24*$COMFIGARR['ticheng_time'];
                      $yoa_User_date=$yoa_User_date+$yoa_Today_date;
                      //执行SQL
                      
                      if($Yao_ini_off=='2' & $User_Arr_B['user_daili']=='1' & $yoa_User_date>HOST_UNIX OR $Yao_ini_off=='1' & $yoa_User_date > HOST_UNIX){
                        //echo date('Y-m-d H:i:s',$yoa_User_date).'-'.$yoa_User_date.'-'.HOST_UNIX.'二级代理<BR/>';
                        if($db->Bsphp_my_query($sql_B)) AddAppenLog(5,"你的下线《B》级用户消费获得:$User_RMB_B 佣金",$User_Arr_B['user_user'],0);
                      }  
                }
            }
            
            
            
            
            
            /** 三级代理 */
            if($ticheng_baifenbi_3>0){
                //计算分红
                $User_RMB_C=$User_RMB*$ticheng_baifenbi_3;
                //读取上级用户UID
                $User_Arr_C=$db->my_class_array('bs_php_user','user_user',$User_Arr_B['user_yao_User'],'`user_uid`,`user_user`,`user_yao_User`,`user_daili`,`user_re_date`');
                if($User_Arr_C){
                      //计算一级提成金额
                      $sql_C="UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$User_RMB_C'WHERE`bs_php_user`.`user_user`='{$User_Arr_C['user_user']}';";
                      //计算注册时间与是否可以提成
                      $yoa_User_date=strtotime($User_Arr_C['user_re_date']);
                      $yoa_Today_date=3600*24*$COMFIGARR['ticheng_time'];
                      $yoa_User_date=$yoa_User_date+$yoa_Today_date;
                      //执行SQL
                      //echo date('Y-m-d H:i:s',$yoa_User_date).'-'.$yoa_User_date.'-'.HOST_UNIX.'<BR/>';
                      if($Yao_ini_off=='2' & $User_Arr_C['user_daili']=='1'& $yoa_User_date > HOST_UNIX or $Yao_ini_off=='1'& $yoa_User_date>HOST_UNIX){
                        if($db->Bsphp_my_query($sql_C)) if($db->Bsphp_my_query($sql_C)) AddAppenLog(5,"你的下线《C》级用户消费获得:$User_RMB_C 佣金",$User_Arr_C['user_user'],0);
                      }  
                }
            }

            
        //-------------------3级接收
        
        }//end   

        /** --------------------------------------------------- 邀请推广结束*/
          return true;

        //RMB修改失败
        } else { 
          //金额修改失败 错误日志记录
          AddAppenLog(5,"[错误号:80081]余额充值订单>状态已修改>未修改余额,支付订单号:$pay_id,时间:".HOST_DATE,$uid,0);
          return false;
        }


      //状态修改失败
      } else { 
        //记录修改错误记录 余额充值订单 
        AddAppenLog(5,"[错误号:80080]余额充值订单>修改失败>未修改余额,支付订单号:$pay_id,时间:".HOST_DATE,$uid,0);
        return false;
        
        
      } //状态修改失败
      
      
      
    } //非0就是下单状态End
    
    
  } //函数End







    /**
     * 等级处理函数
     * 
     * this a 
     * 
     * 参数1=在线时间
     * 参数2=取的数据 id=需要在线时间 name =名称  miaoshu=描述  
     *  
     */
function GetDenJiArray($tme,$id){
     global $class_array;
     
     
     
     $shu=count($class_array);
     
    
    
     if($tme<$class_array[0]['id']){
        
        return $class_array[0][$id];
        
     }elseif($tme>$class_array[9]['id']){
        
        return $class_array[9][$id];
        
     
     }else{
        
        
     for ($i = 0; $i < $shu; $i++) {

        $y=$i+1;
        
        if($tme > $class_array[$i]['id'] & $tme < $class_array[$y]['id']){
            
          break;   
        }
     }
        
      return $class_array[$i][$id];
        
     }
    


     
}
    
    



  /**
   * @用户扣余额
   * 
   * 参数1=用户UID
   * 参数2=扣除金额
   * 
   */
  function UserBalancedeDuction($uid, $balance,$table) {
      global $db;
      
      if($balance<=0)return 1100;
      if($table==NULL)return 1101;
      
      
      $sql="SELECT * FROM  `bsphp_user` WHERE  `user_uid` ='$uid'";
      $user_array = $db->Bsphp_my_array($sql);
      if($user_array['user_rmb'] <= 0) return 1096;
      
      $balanceint=$user_array['user_rmb']-$balance;
      if($balanceint < 0) return 1097;
      
      

      
      //扣除SQL
      $sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`-'$balance' WHERE`bs_php_user`.`user_uid`='$uid';";
      $tmp = $db->Bsphp_my_query($sql);
      if($tmp){
        
               
                AddAppenLog(8,$table.' 扣除:'.$balance.'元',$uid,0);
                return 1098;
      }else{
                AddAppenLog(6,"[错误号:88001]扣点执行失败,时间:".HOST_DATE,$uid,0);
                return 1099;
        
      }

        //记录修改错误记录 余额充值订单 

  }










?>