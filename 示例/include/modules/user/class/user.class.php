<?php

class user
{
    private $db, $global_array_set;
    function __construct()
    {
        $this->sessiona = bs_lib::load_libbsphp_class('session', 'session');
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->global_array_set = bs_lib::get_configs_value('user');
    }
    function User_in($user)
    {
        BsRun_Plug('User_in_Begin', 0);
        $ip = getip();
        $date = (int)bs_lib::get_configs_value('user', 'user_re_h') * 60 * 60;
        $date = HOST_UNIX - $date;
        $date = date('Y-m-d H:i:s', $date);
        $sql = "select count(*)as'hangshu' from`bs_php_user` WHERE`user_re_ip`LIKE '$ip' AND `user_re_date`>'$date'";
        $arrays = $this->db->Bsphp_my_array($sql);
        if ($arrays['hangshu'] >= bs_lib::get_configs_value('user', 'user_re_s'))
        {
            BsRun_Plug('User_in_End', 1111);
            return 1111;
        }
        if (bs_lib::get_configs_value('user', 'user_re_set') != 1)
        {
            BsRun_Plug('User_in_End', 1110);
            return 1110;
        }
        if (preg_match('/^(' . bs_lib::get_configs_value('user', 'useron') . ')$/i', $user))
        {
            BsRun_Plug('User_in_End', 1105);
            return 1105;
        }
        if (!preg_match(stripslashes(bs_lib::get_configs_value('user', 'user_id')), $user))
        {
            BsRun_Plug('User_in_End', 1004);
            return 1004;
        }
        if ($user == '')
            return 1039;
        if (set32($user))
            return 1040;
        $sql = "SELECT`user_user`FROM`bs_php_user`WHERE`user_user`LIKE CONVERT(_utf8'$user'USING utf8)COLLATE utf8_general_ci ";
        $tpm = $this->db->Bsphp_my_array($sql);
        if ($tpm)
        {
            BsRun_Plug('User_in_End', 1000);
            return 1000;
        } else
        {
            BsRun_Plug('User_in_End', 1001);
            return 1001;
        }
    }
    function UserAdd($user, $pass, $passb, $QQ, $mail, $Yao, $Mobile = "", $user_mibao_wenti = "", $user_mibao_daan = "")
    {
        BsRun_Plug('UserAdd_begin', 0);
        $ip = getip();
        $date = (int)bs_lib::get_configs_value('user', 'user_re_h') * 60 * 60;
        $date = HOST_UNIX - $date;
        $date = date('Y-m-d H:i:s', $date);
        $sql = "select count(*)as'hangshu' from`bs_php_user` WHERE`user_re_ip`LIKE '$ip' AND `user_re_date`>'$date'";
        $arrays = $this->db->Bsphp_my_array($sql);
        if ($arrays['hangshu'] >= bs_lib::get_configs_value('user', 'user_re_s'))
        {
            BsRun_Plug('User_in_End', 1111);
            return 1111;
        }
        if (bs_lib::get_configs_value('user', 'user_re_set') != 1)
        {
            return 1110;
        }
        
        #禁止包含关键字
        if (preg_match('/^(' . bs_lib::get_configs_value('user', 'useron') . ')$/i', $user))
        {
            return 1105;
        }
        #echo stripslashes(bs_lib::get_configs_value('user', 'user_id'));
        #exit;
        if (!preg_match(stripslashes(bs_lib::get_configs_value('user', 'user_id')), $user))
        {
            return 1004;
        }
        $DATE = HOST_DATE;
        $IP = getip();
        $user = strtolower($user);
        if (bs_lib::get_configs_value('user', 're_qq') == 1)
        {
            if ($QQ == '')
                return 1092;
            if (!PiPei($QQ, 2))
                return 1093;
        }
        if (bs_lib::get_configs_value('user', 're_mail') == 1)
        {
            if ($mail == '')
                return 1090;
            if (!PiPei($mail, 3))
                return 1091;
        }
        if (bs_lib::get_configs_value('user', 're_phone') == 1)
        {
            if ($mail == '')
                return 1090;
            if (!PiPei($mail, 4))
                return 1106;
        }
        if (strlen($user) < (int)bs_lib::get_configs_value('user', 'user_a') | strlen($user) > (int)bs_lib::get_configs_value('user', 'user_b'))
            return 1003;
        if ($this->User_in($user) == 1000)
            return 1000;
        if ($pass != $passb)
            return 1002;
        if (strlen($pass) < (int)bs_lib::get_configs_value('user', 'user_pwd_a') | strlen($pass) > (int)bs_lib::get_configs_value('user', 'user_pwd_b'))
            return 1007;
        if (bs_lib::get_configs_value('user', 'user_re_pass_admin') == 1)
        {
            $locks = '-2';
        } elseif (bs_lib::get_configs_value('user', 'user_re_pass_mail') == 1)
        {
            $locks = '-3';
        } else
        {
            $locks = '0';
        }
        $pass = password_md6($pass);
        $sql = "INSERT INTO`bs_php_user` (`user_uid`, `user_user`, `user_pwd`, `user_IsLock`, `user_email`, `user_mail_ok`, `user_qq`, `user_Mobile`, `user_mibao_wenti`, `user_mibao_daan`, `user_rmb`, `user_jifen`, `user_re_ip`, `user_re_date`, `user_Login_ip`, `user_Login_date`, `user_CaoShi`, `user_yao_User`, `user_yao_Shu`, `user_Zhe`, `user_LoGinNum`, `user_DenJi_tmp`, `user_DenJi`, `user_daili`) VALUES (NULL,'$user','$pass','$locks','$mail','0','$QQ','$Mobile','$user_mibao_wenti','$user_mibao_daan','0.00','0','$IP','$DATE',NULL,NULL,'0','$Yao','0','0.0','0','0','0','0');";


            
        if ($this->db->Bsphp_my_query($sql))
        {
            
            
            
            
            
            
            //设置用户邀请加入
            if ($Yao > 0)
            {
                
                
                


                //读取用户配置
                $User_Info = $this->db->my_class_array('bs_php_user', 'user_uid', $Yao, '`user_uid`,`user_daili`,`user_user`');
                $yao_User = $User_Info['user_user'];


                //读取邀请推广配置

                $COMFIGARR['yaoqing_off']=bs_lib::get_configs_value('extension','yaoqing_off');
                $COMFIGARR['yaoqing_jifen']=bs_lib::get_configs_value('extension','yaoqing_jifen');
                
                
                
                if ($User_Info['user_daili'] == 0 and $COMFIGARR['yaoqing_off'] == 2)
                {
                    $COMFIGARR['yaoqing_off']='0';
                   

                }
                
                

                //执行邀请
                if ($COMFIGARR['yaoqing_off'] > 0)
                {
                    
                    //读取当天邀请人数
                    $todaydate = strtotime(date('Y-m-d'));
                    $sql = "select count(*)as'hangshu'from`bs_php_log`WHERE`leixing`='yao_registration_log'AND `user`='%$yao_User%'AND`date`>'$todaydate'";
                    $logarray = $this->db->Bsphp_my_array($sql);
                    $YaoQingRenShu = $logarray[0];


                    if ($YaoQingRenShu < bs_lib::get_configs_value('extension', 'yaoqing_today_RenShu'))
                    {
                        
                        
            
            
  
                        
                 
                        $sql="UPDATE`bs_php_user`SET `user_jifen` = `user_jifen`+'{$COMFIGARR['yaoqing_jifen']}',`user_yao_Shu`=`user_yao_Shu`+1 WHERE`bs_php_user`.`user_uid`='$Yao';";  
                        bs_lib::addappenlog('yao_registration_log', '成功邀请>' . $user . ' 获得' .$COMFIGARR['yaoqing_jifen'] . '积分奖励', $yao_User);
                        $this->db->Bsphp_my_query($sql);
                          
                    


                    } else
                    {

                        //执行邀请记录
                        if ($YaoQingRenShu == bs_lib::get_configs_value('extension', 'yaoqing_today_RenShu'))
                            bs_lib::addappenlog('yao_registration_log', '今天邀请已达到限制,请明天在邀请吧', $yao_User);
                        
                    }


                } 


          

            
            }
            

            
            
            

              
            if ($locks == -2)
            {
                BsRun_Plug('UserAdd_end', array(1107,$user));
                return 1107;
            } elseif ($locks == -3)
            {
                BsRun_Plug('UserAdd_end', array(1107,$user));
                return 1108;
            } else
            {
                BsRun_Plug('UserAdd_end', array(1107,$user));
                return 1005;
            }
            
            
          
            
        } else
        {
            BsRun_Plug('UserAdd_end', 1006);
            return 1006;
        }
    }
    
    
    
    function UserCaoShiInfo($uid)
    {
        $UNIX = HOST_UNIX;
        $sql = "UPDATE`bs_php_user`SET`user_CaoShi`='$UNIX',`user_DenJi`=`user_DenJi`+('$UNIX'-`user_DenJi_tmp`),`user_DenJi_tmp`='$UNIX'WHERE`bs_php_user`.`user_uid`='$uid';";
        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp == false)
        {
            return 1042;
        } else
        {
            return 1041;
        }
    }
    function UserLoginInfo()
    {
        $api_USER_UID = get_session_value('USER_UID');
        $api_USER_YSE = get_session_value('USER_YSE');
        $api_USER_DATE = get_session_value('USER_DATE');
        $api_USER_IP = get_session_value('USER_IP');
        $api_USER_MD7 = get_session_value('USER_MD7');
        if ($api_USER_UID == '' or $api_USER_YSE == '' or $api_USER_MD7 == '')
            return 1049;
        $sql = "SELECT`user_uid`,`user_user`,`user_pwd`,`user_IsLock`,`user_CaoShi`,`user_LoGinNum`,`user_daili`FROM`bs_php_user`WHERE`user_uid`='$api_USER_UID'";
        $arr = $this->db->Bsphp_my_array($sql);
        if (!$arr)
            return 1043;
            
            
        if($arr['user_daili']>0 and BSPHP_SET !== 'AGENT'){
            
            return 1118;
            
         } 
            
            
        $USER_UID = $arr['user_uid'];
        $USER_PWD = $arr['user_pwd'];
        $USER_PWD = cookies_md7($USER_PWD);
        $USER_IsLock = $arr['user_IsLock'];
        $LoGinNum = 'ALL';
        $ChaoShi = $arr['user_CaoShi'];
        if ($arr['user_IsLock'] == -2)
        {
            if ($this->global_array_set['ini_re_mail_set'] == true)
            {
                $end = Send_Mail($user, "{$this->global_array_set['ini_name']}{$user}帐号需要激活才可以使用！", "尊敬用户:{$user} \n\n\n你在{$this->global_array_set['ini_name']}注册帐号还需要激活,请点击下面链接激活它吧 \n\n {$this->global_array_set['ini_url']}index.php?Activating=" . md5(HOST_UNIX . Bsphp_HOST_PASS_KEY . $user . Bsphp_HOST_COOKIES_KEY) .
                    '&user=' . $user . '&time=' . HOST_UNIX);
                return 1094;
            }
        }
        if ($USER_IsLock != 0)
            return 1044;
        $HOST_UNIX = HOST_UNIX;
        $ChaoShi_UNIX = $HOST_UNIX - $ChaoShi;
        if ($ChaoShi_UNIX > 90000)
            return 1045;
        $cookies_md7 = cookies_md7($api_USER_UID . $api_USER_YSE . $api_USER_DATE . $api_USER_IP . $LoGinNum);
        if ($cookies_md7 != $api_USER_MD7)
            return 1046;
        if ($api_USER_UID == $USER_UID & $USER_PWD == $api_USER_YSE)
        {
            $this->UserCaoShiInfo($api_USER_UID);
            return 1047;
        } else
        {
            return 1048;
        }
    }
    function UserWebLogin($user, $pass)
    {
        $DATE = HOST_DATE;
        $IP = getip();
        $UNIX = HOST_UNIX;
        if (strlen($pass) < 2 | strlen($pass) > 14)
            return 1009;
        $sql = "SELECT`user_uid`,`user_user`,`user_pwd`,`user_IsLock`,`user_LoGinNum`,`user_Login_ip`,`user_Login_date`,`user_daili`FROM`bs_php_user`WHERE`user_user`LIKE CONVERT(_utf8'$user'USING utf8)COLLATE utf8_general_ci ";
        $pass = password_md6($pass);
        $arr = $this->db->Bsphp_my_array($sql);
        if (!$arr)
            return 1010;
            
         if($arr['user_daili']> 0 and BSPHP_SET !== 'AGENT'){
            
            return 1118;
            
         }
            

            
            
            
        if ($arr['user_IsLock'] != 0)
            return 1021;
        if (strtolower($arr['user_user']) == strtolower($user) & $pass == $arr['user_pwd'])
        {
            $uid = $arr['user_uid'];
            $sql = "UPDATE`bs_php_user`SET`user_DenJi_tmp`='$UNIX',`user_Login_ip`='$IP',`user_Login_date`='$DATE',`user_CaoShi`='$UNIX',`user_LoGinNum`=`user_LoGinNum`+1 WHERE`bs_php_user`.`user_uid`='$uid';";
            $tmp = $this->db->Bsphp_my_query($sql);
            $sen = 'USER';
            $cookies_pwd = cookies_md7($pass);
            $cookies_ip = $arr['user_Login_ip'];
            $cookies_DATE = $arr['user_Login_date'];
            $LoGinNum = $arr['user_LoGinNum'];
            $LoGinNum = 'ALL';
            $cookies_md7 = cookies_md7($uid . $cookies_pwd . $cookies_DATE . $cookies_ip . $LoGinNum);
            set_session_value("USER_UID", $uid);
            set_session_value('USER_YSE', $cookies_pwd);
            set_session_value('USER_DATE', $cookies_DATE);
            set_session_value('USER_IP', $cookies_ip);
            set_session_value('USER_MD7', $cookies_md7);
            
            return 1011;
        } else
        {
            if ($pass != $arr['user_pwd'])
                return 1012;
        }
        return 1013;
    }
    
    
    
    function UserWebLogin_key($user, $pass)
    {
        $DATE = HOST_DATE;
        $IP = getip();
        $UNIX = HOST_UNIX;
        if (strlen($pass) < 2 | strlen($pass) > 14)
            return 1009;
        $sql = "SELECT`user_uid`,`user_user`,`user_pwd`,`user_IsLock`,`user_LoGinNum`,`user_Login_ip`,`user_Login_date`FROM`bs_php_user`WHERE`user_user`LIKE CONVERT(_utf8'$user'USING utf8)COLLATE utf8_general_ci ";
        $pass = password_md6($pass);
        $arr = $this->db->Bsphp_my_array($sql);
        if (!$arr)
            return 1010;
        if ($arr['user_IsLock'] != 0)
            return 1021;
        if (strtolower($arr['user_user']) == strtolower($user) & $pass == $arr['user_pwd'])
        {
            $uid = $arr['user_uid'];
            return 1011;
        } else
        {
            if ($pass != $arr['user_pwd'])
                return 1012;
        }
        return 1013;
    }
    function UserAddInfo($uid, $qq, $mail, $Mobile)
    {
        if (!PiPei($qq, 2))
            return 1014;
        if (!PiPei($mail, 3))
            return 1015;
        if (!PiPei($Mobile, 4))
            return 1016;
        $sql = "UPDATE`bs_php_user`SET`user_email`='$mail',`user_qq`='$qq',`user_Mobile`='$Mobile'WHERE`bs_php_user`.`user_uid`='$uid';";
        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
            return 1017;
        else
            return 1018;
    }
    function UserEmptyMiBaoInfo($uid)
    {
        $sql = "SELECT`user_mibao_wenti`,`user_mibao_daan`FROM`bs_php_user`WHERE`user_uid`=$uid";
        $arr = $this->db->Bsphp_my_array($sql);
        if (!$arr)
            return 1022;
        if (empty($arr['user_mibao_wenti']) and empty($arr['user_mibao_daan']))
        {
            return 1051;
        } else
            return 1023;
    }
    function UserMiBaoInfo($uid, $WenTi, $DaAn)
    {
        if (strlen($WenTi) < 3 or strlen($DaAn) < 3)
            return 1050;
        if (set32($WenTi) == true or set32($DaAn) == true)
            return 1019;
        if ($WenTi == '' or $DaAn == '')
            return 1020;
        $sql = "SELECT`user_mibao_wenti`,`user_mibao_daan`FROM`bs_php_user`WHERE`user_uid`=$uid";
        $arr = $this->db->Bsphp_my_array($sql);
        if (!$arr)
            return 1022;
        if (empty($arr['user_mibao_wenti']) and empty($arr['user_mibao_daan']))
        {
            $sql = "UPDATE`bs_php_user`SET`user_mibao_wenti`='$WenTi',`user_mibao_daan`='$DaAn'WHERE`bs_php_user`.`user_uid`='$uid';";
            $tmp = $this->db->Bsphp_my_query($sql);
            if ($tmp)
                return 1024;
            else
                return 1025;
        } else
            return 1023;
    }
    
    
    function UserModifyPassWord($uid, $pass, $PassWord, $PassWordb)
    {
        if ($pass == null)
            return 1027;
        if (set32($PassWord))
            return 1026;
        if (strlen($PassWord) < 2 | strlen($PassWord) > 14)
            return 1007;
        if ($PassWord != $PassWordb)
            return 1002;
        if ($pass == $PassWord)
            return 1028;
        $sql = "SELECT`user_pwd`,`user_is_pwd`FROM`bs_php_user`WHERE`user_uid`=$uid ";
        $arr = $this->db->Bsphp_my_array($sql);
        if (!$arr)
            return 1022;
            
        if($arr['user_is_pwd']==1){
            return 1120;
        }
            
        $pass = password_md6($pass);
        $PassWord = password_md6($PassWord);
        if ($arr['user_pwd'] == $pass)
        {
            $sql = "UPDATE`bs_php_user`SET`user_pwd`='$PassWord'WHERE`bs_php_user`.`user_uid`='$uid';";
            $tmp = $this->db->Bsphp_my_query($sql);
            if ($tmp)
            {
                return 1031;
            }
            return 1029;
        }
        return 1030;
    }
    function UserMiBaoPwd($user, $pwd, $pwdb, $MiBaoWenTi, $MiBaoDaAn)
    {
        if (empty($user) or empty($pwd) or empty($pwdb) or empty($MiBaoWenTi) or empty($MiBaoDaAn))
        {
            return 1032;
        }
        if ($pwd != $pwdb)
            return 1002;
        if (set32($pwd))
            return 1026;
        if (strlen($pwd) < 2 | strlen($pwd) > 14)
            return 1007;
        $sql = "SELECT`user_uid`,`user_mibao_wenti`,`user_mibao_daan`FROM`bs_php_user`WHERE`user_user`LIKE CONVERT(_utf8'$user'USING utf8)COLLATE utf8_general_ci ";
        $arr = $this->db->Bsphp_my_array($sql);
        if (!$arr)
            return 1109;
        if ($arr['user_mibao_wenti'] == $MiBaoWenTi & $arr['user_mibao_daan'] == $MiBaoDaAn)
        {
            $uid = $arr['user_uid'];
            $pwd = password_md6($pwd);
            $sql = "UPDATE`bs_php_user`SET`user_pwd`='$pwd'WHERE`bs_php_user`.`user_uid`='$uid';";
            $tmp = $this->db->Bsphp_my_query($sql);
            if ($tmp)
            {
                return 1033;
            } else
            {
                return 1035;
            }
        } else
        {
            return 1034;
        }
    }
    
    
    function UserChong($user, $ka, $pwd)
    {
        if ($user == '')
            return 1052;
        if ($ka == '')
            return 1053;
        if (set32($user))
            return 1055;
        if (set32($ka))
            return 1056;
        if (set32($pwd))
            return 1057;
        $uid = $this->db->my_class_array('bs_php_user', 'user_user', $user, '`user_uid`');
        if ($uid == 0)
           return 1059;
            
            
            
        $sql = "SELECT*FROM`bs_php_cardseries`WHERE`car_name`=\"$ka\"AND`car_pwd`=\"$pwd\"";
        $ka_arr = $this->db->Bsphp_my_array($sql);
        $sql = "SELECT`app_daihao`,`app_MoShi`FROM`bs_php_appinfo`WHERE`app_daihao`='{$ka_arr['car_DaiHao']}'";
        $app_array = $this->db->Bsphp_my_array($sql);
        if (!$ka_arr)
            return 1060;
        $car_TianShu = $ka_arr['car_TianShu'];
        $car_diahao = $ka_arr['car_DaiHao'];
        $ka_id = $ka_arr['car_id'];
        if ($ka_arr['car_type'] == 1)
        {
            $daty_shu = 2678400;
        } elseif ($ka_arr['car_type'] == 2)
        {
            $daty_shu = 31536000;
        } elseif ($ka_arr['car_type'] == 3)
        {
            $daty_shu = 3600;
        } elseif ($ka_arr['car_type'] == 4)
        {
            $daty_shu = 60;
        } elseif ($ka_arr['car_type'] == 5)
        {
            $daty_shu = 1;
        } else
        {
            $daty_shu = 86400;
        }
        $ka_date_unix = $car_TianShu * $daty_shu;
        if ($ka_arr['car_IsLock'] != 0)
            return 1064;
        $sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_User_uid`='$uid'AND`L_daihao`='$car_diahao';";
        $udi_arr = $this->db->Bsphp_my_array($sql);
        if (!is_array($udi_arr))
        {
            
            Login_add_key($uid, $car_diahao, '0', '','',$user);
            $sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_User_uid`='$uid'AND`L_daihao`='$car_diahao';";
            $udi_arr = $this->db->Bsphp_my_array($sql);
            if (!is_array($udi_arr))
                return 1061;
        }
        
        
        
        if (!$udi_arr)
            return 1061;
        $vip_date_UNIX = $udi_arr['L_vip_unix'];
        $moshi_array = GetAppMoShiArray($app_array['app_MoShi']);
        if ($moshi_array['card'] == 'date')
        {
            if ($vip_date_UNIX > HOST_UNIX)
            {
                if ($ka_arr['car_weight'] == 1 and $ka_arr['car_class'] != $udi_arr['L_class'])
                {
                    $xin_vip_date_unix = HOST_UNIX + $ka_date_unix;
                } else
                {
                    if ($ka_arr['lei_for_id'] == 1)
                        return (1112);
                    $xin_vip_date_unix = $vip_date_UNIX + $ka_date_unix;
                }
            } else
            {
                $xin_vip_date_unix = HOST_UNIX + $ka_date_unix;
            }
        } else
        {
            if ($vip_date_UNIX > 0)
            {
                if ($ka_arr['car_weight'] == 1 and $ka_arr['car_class'] != $udi_arr['L_class'])
                {
                    $xin_vip_date_unix = $ka_date_unix;
                } else
                {
                    if ($ka_arr['lei_for_id'] == 1)
                        return (1112);
                    $xin_vip_date_unix = $vip_date_UNIX + $ka_date_unix;
                }
            } else
            {
                $xin_vip_date_unix = $ka_date_unix;
            }
        }
        if ($ka_arr['lei_for_id'] == 2)
        {
            if (strstr($ka_arr['car_for_oid'], $user))
                return (1112);
        }
        $vip_date = $xin_vip_date_unix;
        $sql = "UPDATE`bs_php_pattern_login`SET `L_class`='{$ka_arr['car_class']}',`L_vip_unix`='$vip_date' , `L_ic_pwd`='$ka'   WHERE`L_User_uid` ='$uid'  AND`L_daihao`='$car_diahao' LIMIT 1;";
        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            

            
            $HOST_DATE = HOST_DATE;
            $user_name = $this->db->my_class_array('bs_php_user', 'user_uid', $uid, '`user_user`');
            
            $sql = "UPDATE`bs_php_user`SET `user_rmb`=`user_rmb`+'{$ka_arr['car_money']}' WHERE `user_uid`='$uid';";
           
            
            $this->db->Bsphp_my_query($sql);
            
            if ($ka_arr['car_for'] <= 1)
            {
                $car_IsLock = 1;
            } else
            {
                $car_IsLock = 0;
            }
            $sql = "UPDATE`bs_php_cardseries`SET `car_IsLock`='$car_IsLock',  `car_for_oid`=concat(`car_for_oid`,'#$user#') , `car_chong_uid`='$uid',`car_for`=`car_for` - 1,`car_pur_date`='$HOST_DATE',`car_cong_user`='$user_name'WHERE`bs_php_cardseries`.`car_id`='$ka_id';";
            $tmp = $this->db->Bsphp_my_query($sql);
            if (!$tmp)
                $this->db->Bsphp_my_query($sql);
            return 1062;
        } else
        {
            return 1063;
        }
        
        
        return 1055;
    }
    
    
   
    function UserChongcard($user, $ka, $pwd)
    {
        if ($user == '')
            return 1052;
        if ($ka == '')
            return 1053;
        if (set32($user))
            return 1055;
        if (set32($ka))
            return 1056;
        if (set32($pwd))
            return 1057;
        //$uid = $this->db->my_class_array('bs_php_user', 'user_user', $user, '`user_uid`');
        //if ($uid == 0)
          //  return 1059;
            
          $uid  = $user; 
            
        $sql = "SELECT*FROM`bs_php_cardseries`WHERE`car_name`=\"$ka\"AND`car_pwd`=\"$pwd\"";
        $ka_arr = $this->db->Bsphp_my_array($sql);
        $sql = "SELECT`app_daihao`,`app_MoShi`FROM`bs_php_appinfo`WHERE`app_daihao`='{$ka_arr['car_DaiHao']}'";
        $app_array = $this->db->Bsphp_my_array($sql);
        if (!$ka_arr)
            return 1060;
        $car_TianShu = $ka_arr['car_TianShu'];
        $car_diahao = $ka_arr['car_DaiHao'];
        $ka_id = $ka_arr['car_id'];
        if ($ka_arr['car_type'] == 1)
        {
            $daty_shu = 2678400;
        } elseif ($ka_arr['car_type'] == 2)
        {
            $daty_shu = 31536000;
        } elseif ($ka_arr['car_type'] == 3)
        {
            $daty_shu = 3600;
        } elseif ($ka_arr['car_type'] == 4)
        {
            $daty_shu = 60;
        } elseif ($ka_arr['car_type'] == 5)
        {
            $daty_shu = 1;
        } else
        {
            $daty_shu = 86400;
        }
        $ka_date_unix = $car_TianShu * $daty_shu;
        if ($ka_arr['car_IsLock'] != 0)
            return 1064;
        $sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_User_uid`='$uid'AND`L_daihao`='$car_diahao';";
        $udi_arr = $this->db->Bsphp_my_array($sql);
        if (!is_array($udi_arr))
        {
            
            Login_add_key($uid, $car_diahao, '0', '','',$user);
            $sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_User_uid`='$uid'AND`L_daihao`='$car_diahao';";
            $udi_arr = $this->db->Bsphp_my_array($sql);
            if (!is_array($udi_arr))
                return 1061;
        }
        
        
        
        if (!$udi_arr)
            return 1061;
        $vip_date_UNIX = $udi_arr['L_vip_unix'];
        $moshi_array = GetAppMoShiArray($app_array['app_MoShi']);
        if ($moshi_array['card'] == 'date')
        {
            if ($vip_date_UNIX > HOST_UNIX)
            {
                if ($ka_arr['car_weight'] == 1 and $ka_arr['car_class'] != $udi_arr['L_class'])
                {
                    $xin_vip_date_unix = HOST_UNIX + $ka_date_unix;
                } else
                {
                    if ($ka_arr['lei_for_id'] == 1)
                        return (1112);
                    $xin_vip_date_unix = $vip_date_UNIX + $ka_date_unix;
                }
            } else
            {
                $xin_vip_date_unix = HOST_UNIX + $ka_date_unix;
            }
        } else
        {
            if ($vip_date_UNIX > 0)
            {
                if ($ka_arr['car_weight'] == 1 and $ka_arr['car_class'] != $udi_arr['L_class'])
                {
                    $xin_vip_date_unix = $ka_date_unix;
                } else
                {
                    if ($ka_arr['lei_for_id'] == 1)
                        return (1112);
                    $xin_vip_date_unix = $vip_date_UNIX + $ka_date_unix;
                }
            } else
            {
                $xin_vip_date_unix = $ka_date_unix;
            }
        }
        if ($ka_arr['lei_for_id'] == 2)
        {
            if (strstr($ka_arr['car_for_oid'], $user))
                return (1112);
        }
        $vip_date = $xin_vip_date_unix;
        $sql = "UPDATE`bs_php_pattern_login`SET `L_class`='{$ka_arr['car_class']}',`L_vip_unix`='$vip_date'WHERE`L_User_uid` ='$uid'AND`L_daihao`='$car_diahao' LIMIT 1;";
        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            

            
            $HOST_DATE = HOST_DATE;
            $user_name = $this->db->my_class_array('bs_php_user', 'user_uid', $uid, '`user_user`');
            
            $sql = "UPDATE`bs_php_user`SET `user_rmb`=`user_rmb`+'{$ka_arr['car_money']}' WHERE `user_uid`='$uid';";
           
            
            $this->db->Bsphp_my_query($sql);
            
            if ($ka_arr['car_for'] <= 1)
            {
                $car_IsLock = 1;
            } else
            {
                $car_IsLock = 0;
            }
            $sql = "UPDATE`bs_php_cardseries`SET `car_IsLock`='$car_IsLock',  `car_for_oid`=concat(`car_for_oid`,'#$user#') , `car_chong_uid`='$uid',`car_for`=`car_for` - 1,`car_pur_date`='$HOST_DATE',`car_cong_user`='$user_name'WHERE`bs_php_cardseries`.`car_id`='$ka_id';";
            $tmp = $this->db->Bsphp_my_query($sql);
            if (!$tmp)
                $this->db->Bsphp_my_query($sql);
            return 1062;
        } else
        {
            return 1063;
        }
        
        
        return 1055;
    }
    
    
    
    
    
    function UserAddLiuYan($add_leix, $add_table, $add_qq, $add_uid, $add_daihao, $add_txt)
    {
        if ($add_table == '')
            return 1067;
        if ($add_txt == '')
            return 1068;
        if ($add_qq == '')
            return 1095;
        $add_date = HOST_DATE;
        $sql = "INSERT INTO`bs_php_add` (`id`, `add_leix`, `add_table`, `add_qq`, `add_txt`, `add_uid`, `add_date`, `add_daihao`) VALUES (NULL,'$add_leix','$add_table','$add_qq','$add_txt','$add_uid','$add_date','$add_daihao');";
        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            return 1065;
        } else
        {
            return 1066;
        }
    }
    function PayAdd($pay_id, $uid, $apy_lei, $pay_rmb)
    {
        $date = HOST_DATE;
        $sql = "INSERT INTO`bs_php_rmb_pay_log` (`id`, `pay_id`, `pay_uid`, `pay_lei`, `pay_rbm`, `pay_zhuangtai`, `pay_info1`, `pay_info2`, `pay_date`)  VALUES (NULL,'$pay_id','$uid','$apy_lei','$pay_rmb','1','','','$date');";
        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            return 1;
        } else
        {
            return 0;
        }
    }
    function PayInfp($pay_id,$pay_rmb=-1)
    {
        $pay_arr = $this->db->my_class_array('bs_php_rmb_pay_log', 'pay_id', $pay_id, ' * ');
        $uid = $pay_arr['pay_uid'];
        $rmb = $pay_arr['pay_rbm'];
        $zhuangtai = $pay_arr['pay_zhuangtai'];
        if ($zhuangtai == '0')
        {
            return true;
        } else
        {
            
            
            if($pay_rmb==-1){
              $uesr_sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$rmb'WHERE`bs_php_user`.`user_uid`='$uid';";
              $pay_sql = "UPDATE`bs_php_rmb_pay_log`SET`pay_zhuangtai`='0'WHERE`bs_php_rmb_pay_log`.`pay_id`='$pay_id';";
            }else{
              $uesr_sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$pay_rmb'WHERE`bs_php_user`.`user_uid`='$uid';";
              $pay_sql = "UPDATE`bs_php_rmb_pay_log`SET`pay_zhuangtai`='0',`pay_rbm`='$pay_rmb'WHERE`bs_php_rmb_pay_log`.`pay_id`='$pay_id';"; 
            }
            
            
            $pay_tmp = $this->db->Bsphp_my_query($pay_sql);
            if ($pay_tmp)
            {
                $uesr_tmp = $this->db->Bsphp_my_query($uesr_sql);
                if ($uesr_tmp)
                {
                    $COMFIGARR['you_weizi'] = bs_lib::get_configs_value('extension', 'you_weizi');
                    $COMFIGARR['ticheng_oof'] = bs_lib::get_configs_value('extension', 'ticheng_oof');
                    $COMFIGARR['ticheng_baifenbi_1'] = bs_lib::get_configs_value('extension', 'ticheng_baifenbi_1');
                    $COMFIGARR['ticheng_baifenbi_2'] = bs_lib::get_configs_value('extension', 'ticheng_baifenbi_2');
                    $COMFIGARR['ticheng_baifenbi_3'] = bs_lib::get_configs_value('extension', 'ticheng_baifenbi_3');
                    $COMFIGARR['ticheng_time'] = bs_lib::get_configs_value('extension', 'ticheng_baifenbi_time');
                    $Yao_ini_off = $COMFIGARR['ticheng_oof'];
                    if ($COMFIGARR['you_weizi'] == 0 & $Yao_ini_off != '0')
                    {
                        $User_RMB = $rmb;
                        $User_Yao_User = $User_Inof['user_yao_User'];
                        $ticheng_baifenbi_1 = $COMFIGARR['ticheng_baifenbi_1'] / 100;
                        $ticheng_baifenbi_2 = $COMFIGARR['ticheng_baifenbi_2'] / 100;
                        $ticheng_baifenbi_3 = $COMFIGARR['ticheng_baifenbi_3'] / 100;
                        if ($ticheng_baifenbi_1 > 0)
                        {
                            $User_RMB_A = $User_RMB * $ticheng_baifenbi_1;
                            $User_Arr_A = $this->db->my_class_array('bs_php_user', 'user_uid', $User_Yao_User, '`user_uid`,`user_user`,`user_yao_User`,`user_daili`,`user_re_date`');
                            if ($User_Arr_A)
                            {
                                $sql_A = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$User_RMB_A'WHERE`bs_php_user`.`user_user`='{$User_Arr_A['user_user']}';";
                                $yoa_User_date = strtotime($User_Arr_A['user_re_date']);
                                $yoa_Today_date = 3600 * 24 * $COMFIGARR['ticheng_time'];
                                $yoa_User_date = $yoa_User_date + $yoa_Today_date;
                                if ($Yao_ini_off == '2' & $User_Arr_A['user_daili'] == '1' & $yoa_User_date > HOST_UNIX or $Yao_ini_off == '1' & $yoa_User_date > HOST_UNIX)
                                {
                                    if ($this->db->Bsphp_my_query($sql_A))
                                        bs_lib::addappenlog('yao_money_log', "你的下线《A》级用户消费获得:$User_RMB_A 佣金", $User_Arr_A['user_user']);
                                }
                            }
                        }
                        if ($ticheng_baifenbi_2 > 0)
                        {
                            $User_RMB_B = $User_RMB * $ticheng_baifenbi_2;
                            $User_Arr_B = $this->db->my_class_array('bs_php_user', 'user_user', $User_Arr_A['user_yao_User'], '`user_uid`,`user_user`,`user_yao_User`,`user_daili`,`user_re_date`');
                            if ($User_Arr_B)
                            {
                                $sql_B = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$User_RMB_B'WHERE`bs_php_user`.`user_user`='{$User_Arr_B['user_user']}';";
                                $yoa_User_date = strtotime($User_Arr_B['user_re_date']);
                                $yoa_Today_date = 3600 * 24 * $COMFIGARR['ticheng_time'];
                                $yoa_User_date = $yoa_User_date + $yoa_Today_date;
                                if ($Yao_ini_off == '2' & $User_Arr_B['user_daili'] == '1' & $yoa_User_date > HOST_UNIX or $Yao_ini_off == '1' & $yoa_User_date > HOST_UNIX)
                                {
                                    if ($this->db->Bsphp_my_query($sql_B))
                                        bs_lib::addappenlog('yao_money_log', "你的下线《B》级用户消费获得:$User_RMB_B 佣金", $User_Arr_B['user_user']);
                                }
                            }
                        }
                        if ($ticheng_baifenbi_3 > 0)
                        {
                            $User_RMB_C = $User_RMB * $ticheng_baifenbi_3;
                            $User_Arr_C = $this->db->my_class_array('bs_php_user', 'user_user', $User_Arr_B['user_yao_User'], '`user_uid`,`user_user`,`user_yao_User`,`user_daili`,`user_re_date`');
                            if ($User_Arr_C)
                            {
                                $sql_C = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$User_RMB_C'WHERE`bs_php_user`.`user_user`='{$User_Arr_C['user_user']}';";
                                $yoa_User_date = strtotime($User_Arr_C['user_re_date']);
                                $yoa_Today_date = 3600 * 24 * $COMFIGARR['ticheng_time'];
                                $yoa_User_date = $yoa_User_date + $yoa_Today_date;
                                if ($Yao_ini_off == '2' & $User_Arr_C['user_daili'] == '1' & $yoa_User_date > HOST_UNIX or $Yao_ini_off == '1' & $yoa_User_date > HOST_UNIX)
                                {
                                    if ($this->db->Bsphp_my_query($sql_C))
                                        if ($this->db->Bsphp_my_query($sql_C))
                                            bs_lib::addappenlog('yao_money_log', "你的下线《C》级用户消费获得:$User_RMB_C 佣金", $User_Arr_C['user_user']);
                                }
                            }
                        }
                    }
                    return true;
                    
                    
                    
                    
                } else
                {
                    bs_lib::addappenlog('exit_log', "[错误号:80081]余额充值订单>状态已修改>未修改余额,支付订单号:$pay_id,时间:" . HOST_DATE, $uid);
                    return false;
                }
            } else
            {
                bs_lib::addappenlog('exit_log', "[错误号:80080]余额充值订单>修改失败>未修改余额,支付订单号:$pay_id,时间:" . HOST_DATE, $uid);
                return false;
            }
        }
    }
    function GetDenJiArray($tme, $id)
    {
        global $class_array;
        $shu = count($class_array);
        if ($tme < $class_array[0]['id'])
        {
            return $class_array[0][$id];
        } elseif ($tme > $class_array[9]['id'])
        {
            return $class_array[9][$id];
        } else
        {
            for ($i = 0; $i < $shu; $i++)
            {
                $y = $i + 1;
                if ($tme > $class_array[$i]['id'] & $tme < $class_array[$y]['id'])
                {
                    break;
                }
            }
            return $class_array[$i][$id];
        }
    }
    function UserBalancedeDuction($uid, $balance, $table)
    {
        if ($balance <= 0)
            return 1100;
        if ($table == null)
            return 1101;
        $sql = "SELECT * FROM  `bs_php_user` WHERE  `user_uid` ='$uid'";
        $user_array = $this->db->Bsphp_my_array($sql);
        if ($user_array['user_rmb'] <= 0)
            return 1096;
        $balanceint = $user_array['user_rmb'] - $balance;
        if ($balanceint < 0)
            return 1097;
        $sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`-'$balance' WHERE`bs_php_user`.`user_uid`='$uid';";
        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            bs_lib::addappenlog('money_buy_log', $table . '|' . $balance ,$user_array['user_user']);
            return 1098;
        } else
        {
            bs_lib::addappenlog('exit_log', "[错误号:88001]扣点执行失败|" . HOST_DATE);
            return 1099;
        }
    }
}

?>
