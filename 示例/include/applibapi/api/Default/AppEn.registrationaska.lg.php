<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>registration.lg</appen>  
* <appname>注册账号</appname> 
* <sen>8<sen/>                
* <seng1>user=>注册账号</seng>
* <seng2>pwd=>密码</seng>
* <seng3>pwdb=>再次输入密码</seng>
* <seng4>qq=>qq</seng>
* <seng5>mail=>邮箱</seng>
* <seng6>key=>绑定特征</seng>
* <seng7>img=>验证码</seng>
* <lei>ok</lei>               
* <info>绑定特征</info> 
* *****************************************************************************
*/
/*</name>*/
//oid Login file
$daihao = HOST_DAIHAO;
$user = set_data('user');
$pwd = set_data('pwd');
$pwdb = set_data('pwdb');
$qq = set_data('qq');
$mail = set_data('mail');

$key = set_data('key');
$img = set_data('img');


$kaid = set_data('kaid');
$kapwd = set_data('kapwd');



$Mobile = set_data('mobile');
$user_mibao_wenti = set_data('mibao_wenti');
$user_mibao_daan = set_data('mibao_daan');

$extension = (int)set_data('extension');


/**
 * 判断检测账号重复
 */

$sql = "SELECT*FROM`bs_php_pattern_login`WHERE`L_daihao`='$daihao'AND`L_key_info`LIKE \"$key\";";
$tmp = $this->db->Bsphp_my_array($sql);
if ($Global_DB_AppDaTa_Array['app_key_zhong'] == 1 & $tmp)
{

    PrLog(5010); //当前绑定特征已经有人绑定了

}



#查询激活码是否存在
$sql = "SELECT * FROM  `bsphp_cardseries` WHERE  `car_name` =  '$kaid' and `car_pwd` =  '$kapwd'  and `car_DaiHao` =  '$daihao'  LIMIT 1;";
$tmp = $this->db->Bsphp_my_array($sql);
if(!$tmp){
   PrInfo("激活码无效"); //当前绑定特征已经有人绑定了 
    
}

if($tmp['car_IsLock']==1){
   PrInfo("激活码无效"); //当前绑定特征已经有人绑定了 
    
}


/**
 * 判断邀请注册IUD是否存在
 */

if ($extension > 0)
{
    $uid_extension = $this->db->my_class_array('bs_php_user', 'user_uid', $extension, '`user_uid`');
    if (!$uid_extension)
        PrInfo('推荐人不错误,没有请不要填写！');

}
/**
 * 用户注册
 */
$log = $this->user->UserAdd($user, $pwd, $pwdb, $qq, $mail, $extension, $Mobile, $user_mibao_wenti, $user_mibao_daan);
if ($log == 1005)
{


    $date = (int)$Global_DB_AppDaTa_Array['app_re_date']; //获取赠送时间


    $date = HOST_UNIX + $date;

    /*查询是否重复绑定*/
    $uid = $this->db->my_class_array('bs_php_user', 'user_user', $user, '`user_uid`');
    
//    
//    PrInfo(login_key_zhong($daihao, $key));
//    exit;
//    if (login_key_zhong($daihao, $key) != false AND $Global_DB_AppDaTa_Array['app_key_zhong'] == 1)
//        PrLog(5011); //'账号注册成功,当前机器特征已经有人绑定'
//        
        
        
    Login_add_key($uid, $daihao, $date, $key,$user,$user); //添加特征

    $this->user->UserChong($user,$kaid,$kapwd,$daihao);
    //PrLog(1008);//'欢迎你首次使用!,请重新登陆.'
    if ((int)$Global_DB_AppDaTa_Array['app_re_date'] == 0)
    {
        PrInfo('注册成功');
    } else
    {
        
        $s = $Global_DB_AppDaTa_Array['app_re_date'] / 3600;
        PrInfo('注册成功,恭喜你获得了' . $s . '小时的使用权');
    }


    //PrInfo((string)$log);
}
PrInfo($this->user_str_log[$log]);

?>