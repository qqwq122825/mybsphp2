<?php



/**
 * @明文加密脚本
 * 加密类型:RC4编码
 * 
 * 加密解密函数组成 
 * 文件名_bsphp_mencrypt(系统传送数据库参数,系统传送密码参数)  加密
 * 文件名_bsphp_mdecrypt(系统传送数据库参数,系统传送密码参数)  解密
 * 
 * 以上2个为系统固定参数
 */


//
//echo bsphp_rc4_bsphp_mencrypt('sssssss','wEzb8NVc1p7SNf6hIc');

//echo bsphp_rc4_bsphp_mdecrypt('xDTvL9ZAH8c5vhIMtxJ+kR1hzy4kxds+cpVbauxh0AzfrwnAOdMSFn2befIFPfrPo/Lvpkg7GfxjsS/D524IXO4FBnzJ12faatNFEKfuag8Gd/rbQljGMUvRc1S8wgNQzG9kKa77lwFmT327P9uDo3Iaib4PBpiBclVyUl0g3p+pRyAKlcITleuSz2ZzmwqgsRgnxaCDVNWzf255whzbV7On/oBONZ8uHHZAaqSO8Sxm1HKtHV31h13V32KssqBUZ4WRt8XVLBGkVwiIsEdYeSU3PXucwAqUPDMLaNEgwUrmi+yMmprwvygTP/CHTFD7WUdZYo1/xHdyP0Rev8OjIo9Pc132zTug3qp56V+yjBk49+Mosa1g8=','xDTvL9ZAH8c5vhIMtx');




/**
 * @加密
 * 
 * 文件名_bsphp_mencrypt(系统传送数据库参数,系统传送密码参数)  加密
 */
function bsphp_rc4_bsphp_mencrypt($data, $key)
{

    //因为是标准的Base64编码所以key参数用不到
    $data = rc4_en($data,$key);
    return ($data);
}



/**
 * @解密
 * 
 * 文件名_bsphp_mdecrypt(系统传送数据库参数,系统传送密码参数)  解密
 */
function bsphp_rc4_bsphp_mdecrypt($data, $key)
{

    //因为是标准的Base64编码所以key参数用不到
    $data = rc4_de($data,$key);
    return ($data);
}









    function rc4_de($data,$pwd) //$pwd密钥　RC4解密

    {
        $cipher='';
        $data = base64_decode($data);
      
        $key[] = "";
        $box[] = "";
        $pwd_length = strlen($pwd);
        $data_length = strlen($data);
        for ($i = 0; $i < 256; $i++)
        {
            $key[$i] = ord($pwd[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++)
        {
            $j = ($j + $box[$i] + $key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $data_length; $i++)
        {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }
        ///$cipher = base64_decode($cipher);
        return $cipher;
    }




    function rc4_en($data,$pwd) //$pwd密钥　RC4加密

    {
        $cipher='';
  
        $key[] = "";
        $box[] = "";
        $pwd_length = strlen($pwd);
        $data_length = strlen($data);
        for ($i = 0; $i < 256; $i++)
        {
            $key[$i] = ord($pwd[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++)
        {
            $j = ($j + $box[$i] + $key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        
        for ($a = $j = $i = 0; $i < $data_length; $i++)
        {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }
        $cipher = base64_encode($cipher);
        return $cipher;
    }

//echo rc4_en('北海广作投资有限公司','bsphp');
//echo '<BR/>';
//echo rc4_de('nYz3kY04UPKZkzwNxOz0BbV6EnB0MRbWYeM2qYGw','bsphp');
?>