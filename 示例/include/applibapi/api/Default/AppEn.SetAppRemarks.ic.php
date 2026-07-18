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

$remarks =  set_data('remarks');



//获取验证信息 COOKIE内之了
$log = CarCookies($car_id, $car_pwd, $daihao);
if ($log == 1080)
{

     $sql="UPDATE`bs_php_pattern_login`SET `L_links_info` = '$remarks' WHERE `L_User_uid`='$car_id';";
     $tmp=$this->db->Bsphp_my_query($sql);
     if($tmp){
        PrInfo('10001');  
     }else{
        PrInfo('10002');
     }


} else
{
    PrInfo($this->user_str_log[$log]);
}

?>