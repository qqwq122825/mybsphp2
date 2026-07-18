<?php

/**
 * 登录模式调用的文件
 * 
 * 
 * 本文件属于登录模式下的配置文件
 */

/**
 * 判断软件模式
 */


/**
 * @用户登录连接数
 * 
 * 参数1=用户$uid
 * 参数2=软件代号
 * 
 * 用来查询pattern_login 数据库用户信息
 * 查询失败返回false
 */
function Login_links_mx($user, $daihao, $biaoji, $my_setinfoid)
{
    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');
    global $Global_DB_AppDaTa_Array;
    $sessl = set_data('BSphpSeSsL');

    #读取配置 当个
    #自定义模式
    #思路 先限制账号数量,在判断机器数量
    $sql = "SELECT `L_id`,`L_User_uid`,`L_links_open`,`L_links` FROM `bs_php_pattern_login` WHERE `L_User_uid` = '$my_setinfoid'";
    $arrays_set = $db->Bsphp_my_array($sql);

    #账号
    if ($arrays_set['L_links_open'] == 0)
    {
        $login_shu = $Global_DB_AppDaTa_Array['app_links_open'];
    } else
    {
        $login_shu = $arrays_set['L_links_open'];
    }

    #机器
    if ($arrays_set['L_links'] == 0)
    {
        $shebei_shu = $Global_DB_AppDaTa_Array['app_links'];
    } else
    {
        $shebei_shu = $arrays_set['L_links'];
    }

    #echo $sql;
    #exit;


    #账号数量
    if ((int)$login_shu !== -1)
    {

        /*
        //查询当前sessl 如果存在让她翻天也不管了
        $sql = "SELECT * from`bs_php_links_session` WHERE `links_user_name`=\"{$user}\" AND `links_daihao`=\"{$daihao}\" AND `links_session_id`='$sessl'";
        $arrays = $db->Bsphp_my_array($sql);
        if (!$arrays)
        {}  */

        //查询当前登录账号总数
        $sql = "select count(*)as'hangshu' from`bs_php_links_session` WHERE `links_user_name`=\"{$user}\" AND `links_daihao`=\"{$daihao}\" AND `links_set`='0'";
        $arrays = $db->Bsphp_my_array($sql);
        $loginall_hangshu = $arrays['hangshu'];

        if ($loginall_hangshu >= $login_shu)
            return 5050;


    }

    #设备数量
    if ((int)$shebei_shu !== -1)
    {
        
        if($biaoji=='') return 5051;
        
        #ECHO $shebei_shu;
        #exit;

        //查询当前sessl 如果bu存在让她翻天也不管了
        $sql = "SELECT * from`bs_php_links_session` WHERE `links_user_name`=\"{$user}\" AND `links_daihao`=\"{$daihao}\"  AND `links_biaoji`=\"{$biaoji}\" ";
        $arrays = $db->Bsphp_my_array($sql);
        if (!$arrays)
        {


            //查询已经登录机器数量
            $sql = "SELECT COUNT(*) AS `hangshu` FROM `bs_php_links_session` WHERE `links_user_name`=\"{$user}\" AND `links_daihao`=\"{$daihao}\"  AND `links_set`='0' GROUP BY `links_biaoji` ORDER BY `links_biaoji`";
            #ECHO $sql;
            $tmp = $db->Bsphp_my_query($sql);
            $pc_zongshu = $db->my_rows($tmp);
            //验证登录数量
            if ($pc_zongshu >= $shebei_shu)
            {
                
                
                return 5049;
            }
        }


    }


    return 5047;
}

/**
 * @获取登录模式用户的信息 
 * 
 * 参数1=用户$uid
 * 参数2=软件代号
 * 
 * 用来查询pattern_login 数据库用户信息
 * 查询失败返回false
 */
function Login_UserInfo($uid, $daihao)
{
    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');
    $sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_User_uid`='$uid'AND`L_daihao`='$daihao';";

    $array = $db->Bsphp_my_array($sql);
    if (!is_array($array))
        return false;
    return $array;
}

function Update_Login_Key( $ka, $newKey)
{
    // 执行数据库更新操作
    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');
    $sql = "UPDATE `bs_php_pattern_login` SET `L_key_info`='$newKey' WHERE `L_User_uid`='$ka'";
   
     $tmp = $db->Bsphp_my_query($sql);
      return $tmp;
}




/**
 * @添加绑定徐标新
 * 
 * 参数1=用户账号/卡串号(7.0后改版共用)
 * 参数2=软件代号
 * 参数3=VIP到期时间
 * 参数4=绑定特征
 * 参数5=卡串密码 如果为登录模式 $icpwd 为用户账号
 * 参数6=所属用户组
 */
function Login_add_key($user, $daihao, $vip, $key, $icpwd = '', $uid_user = '', $calss = '0', $L_links_open = 0, $L_links = 0)
{
    $reip = getip();
    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');
    $date = HOST_DATE;
    if ($uid_user == '')
        $uid_user = $user;
    $sql = "INSERT INTO`bs_php_pattern_login` (`L_id`, `L_User_uid`, `L_daihao`, `L_re_date`, `L_vip_date`, `L_IsLock`, `L_key_info`, `L_links_info`, `L_login_time`, `L_class`, `L_timing`, `L_vip_unix`, `L_ic_name`, `L_ic_pwd`, `L_ic_info`, `L_login_ip`, `L_re_ip`, `L_login_run`,`L_links_open`,`L_links`) VALUES(NULL,'$user','$daihao','$date','0000-00-00','0',\"$key\",NULL,'$date','$calss','0','$vip',\"$uid_user\",'$icpwd','0','$reip','$reip','0','$L_links_open','$L_links');";
    $tmp = $db->Bsphp_my_query($sql);
    return $tmp;

}


/**
 * 查询绑定账号是否重复
 * 
 * 用于注册时检查和首次使用检测
 */
function login_key_zhong($daihao, $key)
{
    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');

    $sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_daihao`='$daihao'AND`L_key_info`LIKE \"$key\" LIMIT 1;";


    $tmp = $db->Bsphp_my_array($sql);


    return $tmp;
}


/**
 * @登录卡串验证-登录验证
 * 
 * 参数1=卡号
 * 参数2=密码
 * 参数3=key
 * 
 * 1082=到期
 * 1081=正常使用
 * 
 * 
 */
function CarLogin($ka, $pwd, $key, $daihao)
{

    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');
    $sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_User_uid`=\"$ka\"AND`L_ic_pwd`=\"$pwd\"AND`L_daihao`='$daihao' LIMIT 1";
    $login_arr = $db->Bsphp_my_array($sql);


    /***********查询软件名称************/
    $arr = $db->my_class_array('bs_php_appinfo', 'app_daihao', $daihao, '`app_name`,`app_MoShi`,`app_daihao`,`app_point_open`');


    if (!$login_arr)
    {
        /**
         * 到这里发现充值没有在判断下是否第一次激活
         * @激活
         */
        $log = CarAddUser($ka, $pwd, $key, $daihao);

        return $log;

    } //请检查卡串号或者密码错误！


    if ($login_arr['L_User_uid'] == $ka & $login_arr['L_ic_pwd'] == $pwd)
    {
        if ((int)$login_arr['L_IsLock'] !== 0)
            return 1078; //你使用权已经被冻结,无法验证！


        if ($login_arr['L_vip_unix'] < HOST_UNIX)
            return 1082; //你的使用期以到,请够卡在使用。

        $reip = getip();
        //记录登录时间
        $sql = "UPDATE `bs_php_pattern_login` SET `L_login_ip` = '{$reip}',`L_login_time` = '" . HOST_DATE . "', `L_timing` = '" . HOST_UNIX . "' WHERE `bs_php_pattern_login`.`L_id` ='{$login_arr['L_id']}' LIMIT 1";
        $db->Bsphp_my_query($sql);

        //后期需要更新数据可在这里编写

        //登录COOKIES设置
        set_session_value('ic_carid', $ka);
        set_session_value('ic_pwd', $pwd);
        set_session_value('USER_UID', $ka);


        return 1081; //登录验证成功！

    } else
    {

        return 1077; //请检查卡串号或者密码错误！


    }

}


/**
 * @登录卡串验证-登录验证-点模式登录
 * 
 * 参数1=卡号
 * 参数2=密码
 * 参数3=key
 * 
 * 1082=到期
 * 1081=正常使用
 * 
 * 
 */
function CarLogin_point($ka, $pwd, $key, $daihao)
{

    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');
    $sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_User_uid`=\"$ka\"AND`L_ic_pwd`=\"$pwd\"AND`L_daihao`='$daihao' LIMIT 1;";
    $login_arr = $db->Bsphp_my_array($sql);


    /***********查询软件名称************/
    $arr = $db->my_class_array('bs_php_appinfo', 'app_daihao', $daihao, '`app_name`,`app_MoShi`,`app_daihao`,`app_point_open`');


    if (!$login_arr)
    {
        /**
         * 到这里发现充值没有在判断下是否第一次激活
         * @激活
         */
        $log = CarAddUser($ka, $pwd, $key, $daihao);

        return $log;

    } //请检查卡串号或者密码错误！


    if ($login_arr['L_User_uid'] == $ka & $login_arr['L_ic_pwd'] == $pwd)
    {
        if ((int)$login_arr['L_IsLock'] !== 0)
            return 1078; //你使用权已经被冻结,无法验证！


        if ($login_arr['L_vip_unix'] <= 0)
            return 1082; //你的使用期以到,请够卡在使用。

        $reip = getip();
        //记录登录时间
        $sql = "UPDATE `bs_php_pattern_login` SET `L_login_ip` = '{$reip}',`L_login_time` = '" . HOST_DATE . "', `L_timing` = '" . HOST_UNIX . "' WHERE `bs_php_pattern_login`.`L_id` ='{$login_arr['L_id']}' LIMIT 1;";
        $db->Bsphp_my_query($sql);

        //后期需要更新数据可在这里编写

        //登录COOKIES设置
        set_session_value('ic_carid', $ka);
        set_session_value('ic_pwd', $pwd);
        set_session_value('USER_UID', $ka);


        return 1081; //登录验证成功！

    } else
    {

        return 1077; //请检查卡串号或者密码错误！


    }

}


/**
 * @直接充值使用
 * 
 * 参数1=需要充值的卡串
 * 参数2=充值卡卡号
 * 参数3=充值卡密码
 * 
 * 
 *  这个函数调用的是include目录下AppEn.Login.php
 */
function CardChong($user, $ka, $pwd)
{

    if ($user == '')
        return 1052; //1052充值账号不能为空
    if ($ka == '')
        return 1053; //1053充值卡号不能为空
    //if ($pwd == '')
    //    return 1054; //1054充值卡密码不能为空
    if (set32($user))
        return 1055; //1055充值账号不能包含空格
    if (set32($ka))
        return 1056; //1056充值卡号不能包含空格
    if (set32($pwd))
        return 1057; //1057充值卡密码不能包含空格


    ///////*****************获取UID和判断用户是否存在
    //$uid = $this->db->my_class_array('bs_php_user', 'user_user', $user, '`user_uid`');


    //卡模式使用UID/// 充值卡模式直接使用卡串
    $uid = $user;
    //  if ($uid == 0)
    //   return 1059; //用户不存在
    //

    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');
    //-*********************查询充值卡是否存在
    //`car_id`,`car_name`,`car_pwd`,`car_IsLock`,`car_DaiHao`,`car_TianShu`,`car_zhuangtai`FROM`bs_php_cardseries`

    $sql = "SELECT*FROM`bs_php_cardseries`WHERE`car_name`=\"$ka\"AND`car_pwd`=\"$pwd\"";
    $ka_arr = $db->Bsphp_my_array($sql);

    $sql = "SELECT`app_daihao`,`app_MoShi`FROM`bs_php_appinfo`WHERE`app_daihao`='{$ka_arr['car_DaiHao']}'";
    $app_array = $db->Bsphp_my_array($sql);


    if (!$ka_arr)
        return 1060; //充值卡账号密码错误或者不存在
    $car_TianShu = $ka_arr['car_TianShu'];
    $car_diahao = $ka_arr['car_DaiHao'];
    $ka_id = $ka_arr['car_id'];


    //          <option value="0">天</option>
    //          <option value="1">月</option>
    //          <option value="2">年</option>
    //          <option value="3">小时</option>
    //          <option value="4">分种</option>
    //          <option value="5">秒种</option>


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


    $ka_date_unix = $car_TianShu * $daty_shu; //计算卡的时间 1天86400秒*天数数


    if ($ka_arr['car_IsLock'] != 0)
        return 1064; //充值卡已经充值过了

    //**********************查询用户是否在使用当前代号软件和获取对应信息
    //$udi_arr = Login_UserInfo($uid, $car_diahao);

    //********查询对应 Login_UserInfo函数 由于在其他文件使用
    $sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_User_uid`='$uid'AND`L_daihao`='$car_diahao';";
    $udi_arr = $db->Bsphp_my_array($sql);
    if (!is_array($udi_arr))
    {
        //添加特征
        Login_add_key($uid, $car_diahao, '0', ''); //添加特征


        $sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_User_uid`='$uid'AND`L_daihao`='$car_diahao';";
        $udi_arr = $db->Bsphp_my_array($sql);
        if (!is_array($udi_arr))
            return 1061;

    }


    //--------------------


    if (!$udi_arr)
        return 1061; //用户没有使用过要充值软件,拒绝充值


    $vip_date_UNIX = $udi_arr['L_vip_unix'];


    //获取软件模式
    $moshi_array = GetAppMoShiArray($app_array['app_MoShi']);


    if ($moshi_array['card'] == 'date')
    {


        //----------------------------------------------------------------------  3574Begin

        //*//////////////////////判断账号是否是否已经到期
        if ($vip_date_UNIX > HOST_UNIX)
        {


            ///////////////////////账号没有到期/////////////////////////////

            //判断是否重计时间
            if ($ka_arr['car_weight'] == 1)
            {
                $xin_vip_date_unix = HOST_UNIX + $ka_date_unix;

            } else
            {
                //判断充值卡类型 是否到期才可以充值
                if ($ka_arr['lei_for_id'] == 1)
                    return (1112);
                $xin_vip_date_unix = $vip_date_UNIX + $ka_date_unix;
            }


        } else
        {
            ////////////////////////账号已经到期/////////////////////////////
            $xin_vip_date_unix = HOST_UNIX + $ka_date_unix;
        }


        //----------------------------------------------------------------------  3574end


    } else
    {


        //----------------------------------------------------------------------  3575Begin
        //*//////////////////////判断账号是否是否已经到期
        if ($vip_date_UNIX > 0)
        {
            ////////////////////////账号没有到期////////////////////////////

            //判断是否重计时间
            if ($ka_arr['car_weight'] == 1)
            {
                $xin_vip_date_unix = $ka_date_unix;

            } else
            {
                //判断充值卡类型 是否到期才可以充值
                if ($ka_arr['lei_for_id'] == 1)
                    return (1112);
                $xin_vip_date_unix = $vip_date_UNIX + $ka_date_unix;
            }


        } else
        {
            ////////////////////////账号已经到期/////////////////////////////
            $xin_vip_date_unix = $ka_date_unix;
        }

        //----------------------------------------------------------------------  3575end


    }

    //
    //               $this->card_oid_array_name = array(
    //            '不限制只要循环充值完',
    //            '限制只能充值到期账号',
    //            '每个账号只能充值一次');


    //判断是否已经充值使用过
    if ($ka_arr['lei_for_id'] == 2)
    {
        if (strstr($ka_arr['car_for_oid'], $user))
            return (1112);
    }


    $vip_date = $xin_vip_date_unix;
    //****************************执行SQL语句
    $sql = "UPDATE`bs_php_pattern_login`SET`L_links_open`='{$ka_arr['car_links_open']}',`L_links`='{$ka_arr['car_links']}',`L_class`='{$ka_arr['car_class']}',`L_vip_unix`='$vip_date'WHERE`L_User_uid` ='$uid'AND`L_daihao`='$car_diahao' LIMIT 1;";

    $tmp = $db->Bsphp_my_query($sql);
    if ($tmp)
    {


        //**********设置充值卡状态
        $HOST_DATE = HOST_DATE;
        $user_name = $user;

        //充值完成就设置完成
        if ($ka_arr['car_for'] <= 1)
        {
            $car_IsLock = 1;
        } else
        {
            $car_IsLock = 0;
        }
        $sql = "UPDATE`bs_php_cardseries`SET`car_IsLock`='$car_IsLock',  `car_for_oid`=concat(`car_for_oid`,'#$user#') ,`car_for`=`car_for` - 1,`car_pur_date`='$HOST_DATE'WHERE`bs_php_cardseries`.`car_id`='$ka_id';";
        echo $sql;
        $tmp = $db->Bsphp_my_query($sql);
        if (!$tmp)
            $db->Bsphp_my_query($sql);
        return 1062; //充值成功,赶快去使用吧!


    } else
    {
        return 1063; //充值失败!
    }
    return 1055;
}

/**
 * @用户登录模式 多卡模式
 * 
 * 参数1=卡串号
 * 参数2=密码
 * 参数3=软件代号
 * 参数3=到期时间
 * 参数3=分配所属用户
 * 1069=激活成功
 * 
 * 
 * 
 */
function LoginCarAddUsers($caruser, $crapwd, $key, $daihao,$user)
{

    $date = HOST_DATE;
    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');



    $sql = "SELECT * FROM `bs_php_user` WHERE `user_user`='$user';";
    $user_array = $db->Bsphp_my_array($sql);
    if(!$user_array){
       return 1121;
    }




    //获取充值卡信息
    $CarArray = CarArray($caruser, $crapwd);


    if (!$CarArray)
        return 1073; //不存在


    if ($CarArray['car_IsLock'] != 0)
        return 1074; //卡串已经激活过了
    if ($CarArray['car_zhuangtai'])
        return 1086; //卡串已经被冻结
    if ($CarArray['car_DaiHao'] != $daihao)
        return 1113; //卡串已经激活过了1114
    $craid = $CarArray['car_id'];


    $sql = "SELECT`app_daihao`,`app_MoShi`FROM`bs_php_appinfo`WHERE`app_daihao`='{$CarArray['car_DaiHao']}'";
    $app_array = $db->Bsphp_my_array($sql);

    //获取软件模式
    $moshi_array = GetAppMoShiArray($app_array['app_MoShi']);
    if ($moshi_array['moshi'] != 'login')
        return 1114; //卡串已经被冻结


    if ($app_array['app_daihao'] != $daihao)
        return 1113; //卡串已经被冻结


    //计算到期时间
    $vip_date = $CarArray['car_TianShu'];


    //          <option value="0">天</option>
    //          <option value="1">月</option>
    //          <option value="2">年</option>
    //          <option value="3">小时</option>
    //          <option value="4">分种</option>
    //          <option value="5">秒种</option>


    if ($CarArray['car_type'] == 1)
    {
        $daty_shu = 2678400;

    } elseif ($CarArray['car_type'] == 2)
    {
        $daty_shu = 31536000;

    } elseif ($CarArray['car_type'] == 3)
    {
        $daty_shu = 3600;

    } elseif ($CarArray['car_type'] == 4)
    {
        $daty_shu = 60;

    } elseif ($CarArray['car_type'] == 5)
    {
        $daty_shu = 1;

    } else
    {
        $daty_shu = 86400;
    }


    $vip_date = $vip_date * $daty_shu; //天数*1小时*24


    //这里判断充值模式date 否则计点模式
    if ($moshi_array['card'] == 'date')
    {


        $vip_date = HOST_UNIX + $vip_date;
    } else
    {


    }


    $daihao = $CarArray['car_DaiHao'];

    //检查充值卡是否已经添加到了登录列表
    $CarNi = CarNi($caruser, $crapwd);
    if ($CarNi == 1071)
        return 1075; //卡串已经存在,无法激活


    //添加充登录值卡SQL
    $reip = getip();
    //$sql = "INSERT INTO`bs_php_pattern_car`VALUES(NULL,'$caruser','$crapwd','0','$daihao','$date','$vip_date','$key','','','0','','$reip','','{$CarArray['car_class']}','0','$vip_date');";
    $log = Login_add_key($user_array['user_uid'], $daihao, $vip_date, $key, $caruser, $user_array['user_user'], $CarArray['car_class'], $CarArray['car_links_open'], $CarArray['car_links']);
    //$log = $db->Bsphp_my_query($sql);$CarArray['car_class']
    if ($log)
    {

        //设置充值卡已经激活
        $date = HOST_DATE;
        $sql = "UPDATE`bs_php_cardseries`SET`car_IsLock`='1',`car_pur_date`='$date',`car_cong_user`=\"cardid\"WHERE`bs_php_cardseries`.`car_id`='$craid';";

        $log = $db->Bsphp_my_query($sql);
        if ($log)
        {
            return 1122; //激活成功
        } else
        {
            return 1076; //已预料错误出现

        }
    } else
    {
        return 1070; //添加失败
    }


}

/**
 * @添加验证串-添加卡串用户
 * 
 * 参数1=卡串号
 * 参数2=密码
 * 参数3=软件代号
 * 参数3=到期时间
 * 
 * 1069=激活成功
 * 
 * 
 * 
 */
function CarAddUser($caruser, $crapwd, $key, $daihao)
{

    $date = HOST_DATE;
    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');



    //获取充值卡信息
    $CarArray = CarArray($caruser, $crapwd);


    if (!$CarArray)
        return 1073; //不存在


    if ($CarArray['car_IsLock'] != 0)
        return 1074; //卡串已经激活过了
    if ($CarArray['car_zhuangtai'])
        return 1086; //卡串已经被冻结
    if ($CarArray['car_DaiHao'] != $daihao)
        return 1113; //卡串已经激活过了1114
    $craid = $CarArray['car_id'];


    $sql = "SELECT`app_daihao`,`app_MoShi`FROM`bs_php_appinfo`WHERE`app_daihao`='{$CarArray['car_DaiHao']}'";
    $app_array = $db->Bsphp_my_array($sql);

    //获取软件模式
    $moshi_array = GetAppMoShiArray($app_array['app_MoShi']);
    if ($moshi_array['moshi'] != 'card')
        return 1114; //卡串已经被冻结


    if ($app_array['app_daihao'] != $daihao)
        return 1113; //卡串已经被冻结


    //计算到期时间
    $vip_date = $CarArray['car_TianShu'];


    //          <option value="0">天</option>
    //          <option value="1">月</option>
    //          <option value="2">年</option>
    //          <option value="3">小时</option>
    //          <option value="4">分种</option>
    //          <option value="5">秒种</option>


    if ($CarArray['car_type'] == 1)
    {
        $daty_shu = 2678400;

    } elseif ($CarArray['car_type'] == 2)
    {
        $daty_shu = 31536000;

    } elseif ($CarArray['car_type'] == 3)
    {
        $daty_shu = 3600;

    } elseif ($CarArray['car_type'] == 4)
    {
        $daty_shu = 60;

    } elseif ($CarArray['car_type'] == 5)
    {
        $daty_shu = 1;

    } else
    {
        $daty_shu = 86400;
    }


    $vip_date = $vip_date * $daty_shu; //天数*1小时*24


    //这里判断充值模式date 否则计点模式
    if ($moshi_array['card'] == 'date')
    {


        $vip_date = HOST_UNIX + $vip_date;
    } else
    {


    }


    $daihao = $CarArray['car_DaiHao'];

    //检查充值卡是否已经添加到了登录列表
    $CarNi = CarNi($caruser, $crapwd);
    if ($CarNi == 1071)
        return 1075; //卡串已经存在,无法激活


    //添加充登录值卡SQL
    $reip = getip();
    //$sql = "INSERT INTO`bs_php_pattern_car`VALUES(NULL,'$caruser','$crapwd','0','$daihao','$date','$vip_date','$key','','','0','','$reip','','{$CarArray['car_class']}','0','$vip_date');";
    $log = Login_add_key($caruser, $daihao, $vip_date, $key, $crapwd, $CarArray['car_admin'], $CarArray['car_class'], $CarArray['car_links_open'], $CarArray['car_links']);
    //$log = $db->Bsphp_my_query($sql);$CarArray['car_class']
    if ($log)
    {

        //设置充值卡已经激活
        $date = HOST_DATE;
        $sql = "UPDATE`bs_php_cardseries`SET`car_IsLock`='1',`car_pur_date`='$date',`car_cong_user`=\"cardid\"WHERE`bs_php_cardseries`.`car_id`='$craid';";

        $log = $db->Bsphp_my_query($sql);
        if ($log)
        {
            return 1069; //激活成功
        } else
        {
            return 1076; //已预料错误出现

        }
    } else
    {
        return 1070; //添加失败
    }


}


/**
 * @获取卡号信息-充值卡数据库
 * 
 * 参数1=卡号
 * 参数2=密码
 * 
 * 
 */
function CarArray($ka, $pwd)
{
    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');
    $sql = "SELECT*FROM`bs_php_cardseries`WHERE`car_name`=\"$ka\"AND`car_pwd`=\"$pwd\"";

    $arr = $db->Bsphp_my_array($sql);
    unset($db);
    return $arr;
}

/**
 * @检查卡串是否已经存在-卡模式用户库
 * 
 * 参数1=卡号
 * 参数2=密码
 * 
 * 
 */
function CarNi($ka, $ped)
{
    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');
    $sql = "SELECT * FROM`bs_php_pattern_car`WHERE`L_User_uid`=\"$ka\"AND`L_ic_pwd`=\"$ped\"";
    $tmp = $db->Bsphp_my_array($sql);

    unset($db);
    if ($tmp)
    {
        return 1071; //**  糟糕卡串已经存在了
    } else
    {
        return 1072; //**  还没有存在
    }


}


/**
 * @查询卡模式登录用户信息-查询用户信息
 * 参数1=卡号
 * 参数2=密码
 * 参数3=代号
 * 
 */
function GetCardInfo($ka, $pwd, $daihao)
{
    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');
    $sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_User_uid`=\"$ka\" AND`L_ic_pwd`=\"$pwd\" AND`L_daihao`='$daihao'";
    $login_arr = $db->Bsphp_my_array($sql);
    if (!$login_arr)
        return 1083; //没有查询到用户信息
    if ($login_arr['L_daihao'] != $daihao)
        return 1084; //该用户不是使用本软件的
    return $login_arr;
}


/**
 * @验证是否登录状态
 * 卡串验证
 * 
 * 无参数
 * 
 * 
 * 
 */
function CarCookies($car_id, $car_pwd, $daihao)
{

    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');

    //查询SQL语句
    $sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_User_uid`=\"$car_id\"AND`L_ic_pwd`=\"$car_pwd\"";
    $login_arr = $db->Bsphp_my_array($sql);
    if (!$login_arr)
        return 1079; //验证失败
    if ($login_arr['L_daihao'] != $daihao)
        return 1084; //该用户不是使用本软件
    if ($login_arr['L_IsLock'] != 0)
        return 1085; //用户已经被冻结
    if ($login_arr['L_User_uid'] == $car_id & $login_arr['L_ic_pwd'] == $car_pwd)
    {
        //更新在线状态可在这里编写
        return 1080; //状态正常
    } else
    {
        return 1079; //验证失败
    }


}

?>