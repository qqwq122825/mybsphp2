<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>BSphpSeSsL.in</appen>  
* <appname>获取BSphpSeSsL</appname> 
* <sen>0<sen/>                
* <seng1>参数=>接收内容</seng>
* <lei>ok</lei>               
* <info>获取验证Session</info> 
* *****************************************************************************
*/
/*</name>*/


//返回Session

if (set_data('BSphpSeSsL') == '') {

    $this->session->session_appen_open();

}

$id = $this->session->session_id;
if($id==''){
    $id =  'sslid-' . strtolower(BSPHP_SET) . '-' . MD5(TIME() . rand(1000, 9999)) . TIME() . rand(1000, 9999);
}

PrInfo($id);
exit;
?>