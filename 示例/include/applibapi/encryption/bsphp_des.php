<?php

/**
 * @明文加密脚本
 * 加密类型:DES 算法
 * 加密方式 DES ECB模式
 * 
 * 加密解密函数组成 
 * 文件名_bsphp_mencrypt(系统传送数据库参数,系统传送密码参数)  加密
 * 文件名_bsphp_mdecrypt(系统传送数据库参数,系统传送密码参数)  解密
 * 
 * 以上2个为系统固定参数
 * 
 * 当前文档继承老版本7.0以前加密
 */


/**
 * @EDS 解密
 * 
 * 参数1=密码
 * 参数2=加密文档
 * 
 * des_mdecrypt
 */
function bsphp_des_bsphp_mdecrypt($encrypted, $key)
{


    //$encrypted = hexToStr($encrypted);
    $encrypted = base64_decode($encrypted);

    $key = substr(md5($key), 0, 8);

    //$td = mcrypt_module_open(MCRYPT_DES,'',MCRYPT_MODE_ECB,''); //使用MCRYPT_DES算法,cbc模式

    $td = mcrypt_module_open('tripledes', '', MCRYPT_MODE_ECB, ''); //使用MCRYPT_DES算法,cbc模式
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
    $ks = mcrypt_enc_get_key_size($td);
    mcrypt_generic_init($td, $key, $key); //初始处理


    $decrypted = mdecrypt_generic($td, $encrypted);


    mcrypt_generic_deinit($td); //结束
    mcrypt_module_close($td);

    $y = pkcs5_unpad($decrypted);
    return $y;
}


/**
 * @DES 加密
 * 
 * 
 * 参数1=密码
 * 参数2=加密文本
 * 
 * 
 */
function bsphp_des_bsphp_mencrypt($text, $key)
{

    //密码MD5加密取左边8位
    $key = substr(md5($key), 0, 8);

    $y = pkcs5_pad($text);
    //$td = mcrypt_module_open(MCRYPT_DES,'',MCRYPT_MODE_ECB,''); //使用MCRYPT_DES算法,cbc模式ECB
    $td = mcrypt_module_open('tripledes', '', MCRYPT_MODE_ECB, ''); //使用MCRYPT_DES算法,cbc模式
    // $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
    $ks = mcrypt_enc_get_key_size($td);
    mcrypt_generic_init($td, $key, $key); //初始处理
    $encrypted = mcrypt_generic($td, $y); //解密
    mcrypt_generic_deinit($td); //结束
    mcrypt_module_close($td);
    return base64_encode($encrypted);
    //return strToHex($encrypted);
}

?>