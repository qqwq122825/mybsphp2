<?php

/**
 * @明文加密脚本
 * 加密类型:Base64编码
 * 
 * 加密解密函数组成 
 * 文件名_bsphp_mencrypt(系统传送数据库参数,系统传送密码参数)  加密
 * 文件名_bsphp_mdecrypt(系统传送数据库参数,系统传送密码参数)  解密
 * 
 * 以上2个为系统固定参数
 */









/**
 * @加密
 */
function bsphp_base64_bsphp_mencrypt($data, $key)
{

    //因为是标准的Base64编码所以key参数用不到
    $data = base64_encode($data);
    return ($data);
}



/**
 * @解密
 */
function bsphp_base64_bsphp_mdecrypt($data, $key)
{

    //因为是标准的Base64编码所以key参数用不到
    $data = base64_decode($data);
    return ($data);
}

?>