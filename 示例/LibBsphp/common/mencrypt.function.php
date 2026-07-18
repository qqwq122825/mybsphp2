<?php


defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');

/**
 * @DES 算法
 * 
 * 
 * DES ECB模式
 * 
 * 
 */




/**
 * @EDS 解密
 * 
 * 参数1=密码
 * 参数2=加密文档
 * 
 * des_mdecrypt
 */
function des_mdecrypt($encrypted,$key)
{ 
    
	
     //$encrypted = hexToStr($encrypted);
     $encrypted = base64_decode($encrypted);
    
    $key = substr(md5($key), 0, 8);

    //$td = mcrypt_module_open(MCRYPT_DES,'',MCRYPT_MODE_ECB,''); //使用MCRYPT_DES算法,cbc模式
    
    $td = mcrypt_module_open('tripledes','',MCRYPT_MODE_ECB,''); //使用MCRYPT_DES算法,cbc模式
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
    $ks = mcrypt_enc_get_key_size($td);
    mcrypt_generic_init($td, $key, $key);       //初始处理

    $decrypted = mdecrypt_generic($td, $encrypted);       //解密

    mcrypt_generic_deinit($td);       //结束
    mcrypt_module_close($td);

    $y=pkcs5_unpad($decrypted);
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
function des_mencrypt($text,$key)
{
 
	
    //密码MD5加密取左边8位
    $key = substr(md5($key), 0, 8);

    $y=pkcs5_pad($text);
    //$td = mcrypt_module_open(MCRYPT_DES,'',MCRYPT_MODE_ECB,''); //使用MCRYPT_DES算法,cbc模式ECB
    $td = mcrypt_module_open('tripledes','',MCRYPT_MODE_ECB,''); //使用MCRYPT_DES算法,cbc模式
  // $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
    $ks = mcrypt_enc_get_key_size($td);
    mcrypt_generic_init($td, $key, $key);       //初始处理
    $encrypted = mcrypt_generic($td, $y);       //解密
    mcrypt_generic_deinit($td);       //结束
    mcrypt_module_close($td);
    return base64_encode($encrypted);
    //return strToHex($encrypted);
}





function pkcs5_pad($text,$block=8)
{
        $pad = $block - (strlen($text) % $block);
        return $text . str_repeat(chr($pad), $pad);
}


function pkcs5_unpad($text)
{
   $pad = ord($text{strlen($text)-1});
   if ($pad > strlen($text)) return $text;
   if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return $text;
   return substr($text, 0, -1 * $pad);
}







 //编码
function   strToHex($string)   
{   
      $hex="";   
      for   ($i=0;$i<strlen($string);$i++)   
      $hex.=dechex(ord($string[$i]));   
      $hex=strtoupper($hex);   
      return   $hex;   
   }   
   
   //解码
function   hexToStr($hex)   
   {   
      $string="";   
      for   ($i=0;$i<strlen($hex)-1;$i+=2)   
      $string.=chr(hexdec($hex[$i].$hex[$i+1]));   
      return   $string;   
}





?>