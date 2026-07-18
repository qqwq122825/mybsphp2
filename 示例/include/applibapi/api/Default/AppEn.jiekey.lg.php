<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>jiekey.lg</appen>  
* <appname>解除绑定</appname> 
* <sen>0<sen/>                
* <seng1>key=>解除绑定</seng>
* <lei>ok</lei>               
* <info>解除绑定</info> 
* *****************************************************************************
*/
/*</name>*/
//oid Login file
//oid links file
$daihao = HOST_DAIHAO;
$uid = get_session_value('USER_UID');
$user = set_data('user');
$pwd = set_data('pwd');

//检测登录状态
if ($user != "" and $pwd != "")
{
    $log = $this->user->UserWebLogin_key($user, $pwd);

    //读取用户配置
    $User_Info = $this->db->my_class_array('bs_php_user', 'user_user', $user, '`user_uid`,`user_daili`,`user_user`');
    $uid = $User_Info['user_uid'];

} else
{
    //登录连接数功能集代码
    // links_chaoshi_login();
    $log = $this->user->UserLoginInfo();
}


if ($log == 1047 or $log == 1011)
{


    //判断是否已经过期
    $arr = Login_UserInfo($uid, $daihao);
    //判断是否已经绑定过了
    if ($arr['L_key_info'] == '')
        PrInfo('已经是解除绑定了');

    //双模双判断
    if ($arr['L_vip_unix'] > HOST_UNIX)
    {
        /////////////////时间没有到期继续////////////////////

        //*********************判断转绑定后是否会到期
        $app_date = $Global_DB_AppDaTa_Array['app_zhuang_date'];
        //$app_date=$app_date*3600;
        $date = $arr['L_vip_unix'];

        $date = $date - $app_date;
        //双模双判断
        if ($date < HOST_UNIX)
            PrInfo('解除绑定后将到期,解除绑定拒绝!');


        //解除绑定
        $sql = "UPDATE`bs_php_pattern_login`SET`L_key_info`='',`L_vip_unix`='$date' WHERE`L_id` ='{$arr['L_id']}'";
        $tmp = $this->db->Bsphp_my_query($sql);

        if ($tmp)
        {


            PrInfo('解除绑定成功!,新到期时间:' . date('Y-m-d H:i:s', $date));

        } else
        {
            PrInfo('解除绑定失败,请重试!');
        }

    } else
    {
        PrInfo('使用期到了,请先续费。');
    }


}
PrInfo($this->user_str_log[$log]);

?>