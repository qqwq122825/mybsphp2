<?php

/**
 * @明文加密脚本
 * 加密类型:AES 算法
 * 加密方式 AES ECB模式
 * http://blog.csdn.net/csdn49532/article/details/50686222
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
function bsphp_aes_cjj_bsphp_mdecrypt($encryptStr, $key)
{


        $localIV = 'gfdertfghjkuyrtg';
        $encryptKey = $key;
        
 
        //Open module
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);
 
        //print "module = $module <br/>" ;
 
        mcrypt_generic_init($module, $encryptKey, $localIV);
 
        $encryptedData = base64_decode($encryptStr);
        $encryptedData = mdecrypt_generic($module, $encryptedData);
        
        #$encryptedData =base64_decode($encryptedData);
        $encryptedData = iconv("GB2312", "UTF-8", $encryptedData);
 
 
/**
 *          echo '我是中文12222：';
 *         echo $encryptedData;
 *         exit;
 */
 
 
      $INFO = preg_match("/<httpdatas>(.*?)<\/httpdatas>/", $encryptedData, $array);
        if ($INFO)
        {
  
        $encryptedData = $array[1];
      }else{
         return -1;
      }
        
        
        
        #$encryptedData=findstr($encryptedData,"<httpdatas>","</httpdatas>");

 
        return $encryptedData;
}


/**
 * @DES 加密
 * 
 * 
 * 参数1=密码
 * 参数2=加密文本 URdecode(AES(<httpdatas>数据包</httpdatas>))
 * 
 * 
 */
function bsphp_aes_cjj_bsphp_mencrypt($encryptStr, $key)
{
    
    
    $encryptStr = iconv("UTF-8", "GB2312", $encryptStr);

        $localIV = 'gfdertfghjkuyrtg';
        $encryptKey = $key;
 
        //Open module
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);
 
        //print "module = $module <br/>" ;
 
        mcrypt_generic_init($module, $encryptKey, $localIV);
 
        //Padding
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $pad = $block - (strlen($encryptStr) % $block); //Compute how many characters need to pad
        $encryptStr .= str_repeat(chr($pad), $pad); // After pad, the str length must be equal to block or its integer multiples
 
        //encrypt
        $encrypted = mcrypt_generic($module, $encryptStr);
 
        //Close
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
 
        return base64_encode($encrypted);
}


function findstr($str, $findstr, $endstr)
{

    $f = stripos($findstr, '<');

    if ($f !== false)
    {
        $findstr = htmlentities($findstr);
    }

    $i = stripos($endstr, '<');

    if ($i !== false)
    {
        $endstr = htmlentities($endstr);
    }

    $num = strstr($str, $findstr);
    $num = strstr($num, $endstr, true);
    $num = str_replace($findstr, '', $num);
    return $num;
}

?>