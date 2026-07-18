<?php

/*<name>*/
/**
 * **********************接口介绍说明******************************************
 * <appen>login.lg</appen>  
 * <appname>用户登录</appname> 
 * <sen>3<sen/>                
 * <seng1>user=>用户账号</seng>
 * <seng2>pwd=>用户密码</seng>
 * <seng3>key=>绑定机器码</seng>
 * <seng4>img=>验证码</seng>
 * <seng5>linktag=>链接标记</seng>
 * 
 * <lei>ok</lei>               
 * <info>用户登陆使用</info> 
 * *****************************************************************************
 */
/*</name>*/

//oid Login file
//oid links file
$daihao = HOST_DAIHAO;
$user = set_data('user');
$user_pwd = set_data('pwd');
$key = set_data('key');
$maxoror = set_data('maxoror');

$img = set_data('img');
$BSphpSeSsL = set_data('BSphpSeSsL');


if ($Global_DB_AppDaTa_Array['app_MoShi'] !== 'LoginPoint')
{
    //PrInfo('1119'); //不对应的模式
    PrInfo($this->user_str_log[1119]);
}


/**
 * 用户登陆
 */
$log = $this->user->UserWebLogin($user, $user_pwd);
if ($log == 1011)
{ //登录成功


    $uid = $this->db->my_class_array('bs_php_user', 'user_user', $user, '`user_uid`');


    //建立登录限制
    $log = Login_links_mx($user, $daihao, $maxoror,$uid);
    if ($log != 5047)
        PrInfo($this->appen_str_log[$log]);


    //日志记录
    bs_lib::addappenlog('user_login_log', '软件登录', $user);
    /**
     * 更新登录模式下最后登陆日期
     */
    $date = HOST_DATE;
    $sql = "UPDATE`bs_php_pattern_login`SET`L_login_time`='$date'WHERE`bs_php_pattern_login`.`L_User_uid`='$uid'AND`bs_php_pattern_login`.`L_daihao`='$daihao';";
    $this->db->Bsphp_my_query($sql);

    // print_r(get_included_files());
    //查询是否注册使用过
    $arr = Login_UserInfo($uid, $daihao);

    //没有使用过的话就给程序添加上

    if (!$arr)
    {


        $date = (int)$Global_DB_AppDaTa_Array['app_re_date']; //获取赠送时间


        /*查询是否重复绑定*/
        if (login_key_zhong($daihao, $key) != false & $Global_DB_AppDaTa_Array['app_key_zhong'] == 1)
            PrLog(5009); //'绑定特征码,已经有人绑定过了,不能重复绑定,不能登陆'
        if (Login_add_key($uid, $daihao, $date, $key, $user,$user))
        {
            PrLog(5008); //'欢迎你首次使用!,请重新登陆.'
        } else
        {
            PrInfo('注册新用户失败,请联系管理员！');
        }

    }


    //获取用户信息赋值给变量
    $uesr_key = $arr['L_key_info'];
    $uesr_vipdate = $arr['L_vip_unix'];
    $login_info = null;
    if ($key == $uesr_key & $uesr_key != '' or $Global_DB_AppDaTa_Array['app_set']==0)
        $login_info = $Global_DB_AppDaTa_Array['app_logininfo'];


    //链接数验证


    //$login_ssl = MD5($BSphpSeSsL);
    $this->session->links_add_info($uid, $user, $key, $daihao, $maxoror);


    //-----------------------------------------


    /**
     * 返回数据说明
     */
    if ($arr['L_vip_unix'] > 0)
    {
        //  PrInfo('1|'.$arr['L_key_info'].'|'.$Global_DB_AppDaTa_Array['app_logininfo']);
        //登陆成功  或者在免费使用


        if ($Global_DB_AppDaTa_Array['app_set'] == 1)
        {


            if ($arr['L_key_info'] !== $key)
            {
                //注销登录
                set_session_value('USER_UID', ''); //登陆UID
                set_session_value('USER_YSE', ''); //登陆MD7加密
                set_session_value('USER_DATE', ''); //上一次登陆时间
                set_session_value('USER_IP', ''); //上一次登陆IP
                set_session_value('USER_MD7', ''); //OOKIE MD7验证串
                $this->session->links_out_session_id($this->session->session_id);

               if($arr['L_key_info']=='')PrInfo("还没有绑定,请绑定在登录");





                PrInfo($this->appen_str_log[5035]); 
                
            }
            
        }


    
        //-----------------------------------------
        //记录登录时间用做扣点
        $UNIX = HOST_UNIX;
        $sql = "UPDATE`bs_php_pattern_login`SET`L_timing`='$UNIX'WHERE`bs_php_pattern_login`.`L_User_uid`='$uid'AND`bs_php_pattern_login`.`L_daihao`='$daihao';";
        $this->db->Bsphp_my_query($sql);


        /**
         * 返回说明
         * 1.= 成功返回1
         * 2.= 登陆成功代号
         * 3.= 用户绑定key
         * 4.= 用户登陆成功返回特定数据
         * 5.= VIP到期时间
         */
        PrInfo("01|1011|$uesr_key|$login_info|$uesr_vipdate|||||");
    } else
    {
        //PrInfo("-1|9908|$uesr_vipdate||||||||");
        //$this->session->links_out_session_id($this->session->session_id);
        PrInfo('9908');
    }


}
PrInfo($this->user_str_log[$log]);

?>