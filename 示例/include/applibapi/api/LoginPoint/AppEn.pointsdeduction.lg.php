<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>timeoutupdate.ic</appen>  
* <appname>扣点 点模式下</appname> 
* <sen>1<sen/>                
* <seng1>key=>绑定特征</seng>
* <lei>ok</lei>               
* <info>绑定特征</info> 
* *****************************************************************************
*/
/*</name>*/
$daihao = HOST_DAIHAO;
$uid = get_session_value('USER_UID');

//$car_id = get_session_value('ic_carid');
//$car_pwd = get_session_value('ic_pwd');
$BSphpSeSsL = set_data('BSphpSeSsL');

$point = set_data('balance'); //扣除点数
if ($Global_DB_AppDaTa_Array['app_MoShi'] !== 'LoginPoint')
{


    PrInfo('90014'); //不对应的模式


}


//登录连接数功能集代码
//links_chaoshi_login();

$log = $this->user->UserLoginInfo();
if ($log == 1047)
{


    if ($point <= 0)
    {
        PrInfo('90011'); //点数不能少或者等于0
    }
    
    
    
    if ($point > 1000)
    {
        PrInfo('90016'); //点数不能少或者等于0
    }


    $arr = Login_UserInfo($uid, $daihao);


    if ($arr['L_vip_unix'] > 0)
    {

        $ConsumptionPoint_time = get_session_value('ConsumptionPoint_time');
        $time_int = HOST_UNIX - $ConsumptionPoint_time;
        if ($time_int < 60)
        {
            PrInfo('90013'); //一分钟只能扣除一次
        }


        $sql = "UPDATE `bs_php_pattern_login`SET`L_vip_unix`=`L_vip_unix`-'$point' WHERE `L_id`='{$arr['L_id']}' LIMIT 1;";
        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            set_session_value('ConsumptionPoint_time', HOST_UNIX);

            //扣点完成
            PrInfo('90010');
        } else
        {


            //扣点失败
            PrInfo('90012');
        }


    } else
    {

        //到期
        PrInfo('90015');
    }


} else
{
    PrInfo($this->user_str_log[$log]);
}

?>