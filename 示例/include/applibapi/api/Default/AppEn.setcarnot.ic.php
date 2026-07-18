<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>setcarnot.ic</appen>  
* <appname>解除绑定</appname> 
* <sen>0<sen/>                
* <seng1></seng>
* <lei>ok</lei>               
* <info>解除绑定-卡模式</info> 
* *****************************************************************************
*/
/*</name>*/

//oid links file
$daihao = HOST_DAIHAO;

if (set_data('icid') != null)
{
    $car_id = set_data('icid');
    $car_pwd = set_data('icpwd');
} else
{
    $car_id = get_session_value('ic_carid');
    $car_pwd = get_session_value('ic_pwd');


}


//获取验证信息 COOKIE内之了
$log = CarCookies($car_id, $car_pwd, $daihao);
if ($log == 1080)
{

    //读取用户数据
    $Get_DaTa = GetCardInfo($car_id, $car_pwd, $daihao);
    if (is_array($Get_DaTa))
    {
        //判断是否已经解除绑定
        if ($Get_DaTa['L_key_info'] == '')
            PrInfo($this->appen_str_log[5022]);
            
        //判断用户是否到期
        if ($Get_DaTa['L_vip_unix'] < HOST_UNIX)
            PrInfo($this->appen_str_log[5018]);

        //判断解除绑定是否会到期
        $Usb_date = $Global_DB_AppDaTa_Array['app_zhuang_date'];

        //$Usb_date=$Usb_date*3600;
        $uesr_date = $Get_DaTa['L_vip_unix'];
        $uesr_date = $uesr_date - $Usb_date;
        if ($uesr_date < HOST_UNIX)
            PrInfo($this->appen_str_log[5019]) ;


        //解除绑定
        $sql = "UPDATE`bs_php_pattern_login`SET`L_key_info`='',`L_vip_unix`='$uesr_date' WHERE`L_id` ='{$Get_DaTa['L_id']}'";
        $tmp = $this->db->Bsphp_my_query($sql);

        if ($tmp)
        {


            PrInfo('解除绑定成功!,新到期时间:' . date('Y-m-d H:i:s', $uesr_date));

        } else
        {
            PrInfo('解除绑定失败,请重试!');
        }


    } else
    {
        PrInfo($this->user_str_log[$Get_DaTa]);
    }


} else
{
    PrInfo($this->user_str_log[$log]);
}

?>