<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>setcaron.ic</appen>  
* <appname>解除绑定</appname> 
* <sen>0<sen/>                
* <seng1></seng>
* <lei>ok</lei>               
* <info>解除绑定-卡模式</info> 
* *****************************************************************************
*/
/*</name>*/

//oid links file

$key = set_data('key');


if (set_data('icid') != null)
{
    $car_id = set_data('icid');
    $car_pwd = set_data('icpwd');
} else
{
    $car_id = get_session_value('ic_carid');
    $car_pwd = get_session_value('ic_pwd');


}


$daihao = HOST_DAIHAO;


//获取验证信息 COOKIE内之了
$log = CarCookies($car_id, $car_pwd, $daihao);
if ($log == 1080)
{

    //读取用户数据
    $Get_DaTa = GetCardInfo($car_id, $car_pwd, $daihao);
    if (is_array($Get_DaTa))
    {
        //判断是否已经解除绑定
        if ($key == '')
            PrInfo($this->appen_str_log[5023]);
        if ($Get_DaTa['L_key_info'] != '')
            PrInfo($this->appen_str_log[5025]);


        //添加绑定
        $sql = "UPDATE`bs_php_pattern_login`SET`L_key_info`='$key'WHERE  `L_id`='{$Get_DaTa['L_id']}';";
        $tmp = $this->db->Bsphp_my_query($sql);

        if ($tmp)
        {
            //PrLog(5013);//'绑定成功!'
            PrInfo('5013'); //
        } else
        {
            PrLog(5014); //绑定失败,请重试!
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