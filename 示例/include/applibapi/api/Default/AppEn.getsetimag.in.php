<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>getsetimag.in</appen>  
* <appname>逻辑提示A 内容</appname> 
* <sen>0<sen/>                
* <seng1>参数=>接收内容</seng>
* <lei>ok</lei>               
* <info>逻辑提示A 内容</info> 
* *****************************************************************************
*/
/*</name>*/
$type = set_data('type');


//
//$arr = array(
//    'INGES_LOGIN' => 'login.lg',
//    'INGES_RE' => 'registration.lg',
//    'INGES_MACK' => 'backto.lg',
//    'INGES_SAY' => 'liuyan.in',
//    );
//

//$arr = array(
//    'login.lg' => 'INGES_LOGIN',
//    'registration.lg' => 'INGES_RE',
//    'backto.lg' => 'INGES_MACK',
//    'liuyan.in' => 'INGES_SAY', 
//    );
//

if (stristr($type, 'INGES_LOGIN'))
{
    if (stristr($Global_DB_AppDaTa_Array['app_coode'], 'login.lg'))
    {
        $type = str_replace('INGES_LOGIN', 'checked', $type);
    }


}
if (stristr($type, 'INGES_RE'))
{


    if (stristr($Global_DB_AppDaTa_Array['app_coode'], 'registration.lg'))
    {
        $type = str_replace('INGES_RE', 'checked', $type);
    }

}
if (stristr($type, 'INGES_MACK'))
{


    if (stristr($Global_DB_AppDaTa_Array['app_coode'], 'backto.lg'))
    {
        $type = str_replace('INGES_MACK', 'checked', $type);
    }

}
if (stristr($type, 'INGES_SAY'))
{


    if (stristr($Global_DB_AppDaTa_Array['app_coode'], 'liuyan.in'))
    {
        $type = str_replace('INGES_SAY', 'checked', $type);
    }

}


PrInfo($type);

?>