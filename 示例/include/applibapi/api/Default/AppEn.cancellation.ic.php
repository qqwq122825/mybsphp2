<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>cancellation.ic</appen>  
* <appname>注销登录</appname> 
* <sen>0<sen/>                
* <seng></seng>
* <lei>ok</lei>               
* <info>取vip时间</info> 
* *****************************************************************************
*/
/*</name>*/



$BSphpSeSsL = set_data('BSphpSeSsL');
$car_id = get_session_value('ic_carid');
$car_pwd = get_session_value('ic_pwd');
$daihao = HOST_DAIHAO;

$tmp = $this->session->links_delete_one_name($BSphpSeSsL, $daihao, $car_id);
if ($tmp)
{

    PrInfo(1);


} else
{

    PrInfo(-1);

}

?>