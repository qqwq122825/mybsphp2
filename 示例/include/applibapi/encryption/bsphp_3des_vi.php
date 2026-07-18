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
function bsphp_3des_vi_bsphp_mdecrypt($encrypted, $key)
{


    


    $key = md5($key);
    $des = new des_vi();
    $ret = $des->decrypt($encrypted,$key);

    return $ret;
    
 
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
function bsphp_3des_vi_bsphp_mencrypt($text, $key)
{


    $key = md5($key);
    $des = new des_vi();
    $ret = $des->encrypt($text,$key);
    return $ret;
}

?>


<?php
class des_vi {
	//var $key = "my.oschina.net/penngo?#@";
	var $iv = "cookies";
         
    
	function encrypt($input,$key){
		$size = mcrypt_get_block_size(MCRYPT_3DES,MCRYPT_MODE_CBC);
		$input = $this->pkcs5_pad($input, $size);
		$key = str_pad($key,24,'0');
		$td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
		if( $this->iv == '' )
		{
			$iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		}
		else
		{
			$iv = $this->iv;
		}
		@mcrypt_generic_init($td, $key, $iv);
		$data = mcrypt_generic($td, $input);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$data = base64_encode($data);
		return $data;
	}
	function decrypt($encrypted,$key){
		$encrypted = base64_decode($encrypted);
		$key = str_pad($key,24,'0');
		$td = mcrypt_module_open(MCRYPT_3DES,'',MCRYPT_MODE_CBC,'');
		if( $this->iv == '' )
		{
			$iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		}
		else
		{
			$iv = $this->iv;
		}
		$ks = mcrypt_enc_get_key_size($td);
		@mcrypt_generic_init($td, $key, $iv);
		$decrypted = mdecrypt_generic($td, $encrypted);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$y=$this->pkcs5_unpad($decrypted);
		return $y;
	}
	function pkcs5_pad ($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}
	function pkcs5_unpad($text){
		$pad = ord($text{strlen($text)-1});
		if ($pad > strlen($text)) {
			return false;
		}
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad){
			return false;
		}
		return substr($text, 0, -1 * $pad);
	}
	function PaddingPKCS7($data) {
		$block_size = mcrypt_get_block_size(MCRYPT_3DES, MCRYPT_MODE_CBC);
		$padding_char = $block_size - (strlen($data) % $block_size);
		$data .= str_repeat(chr($padding_char),$padding_char);
		return $data;
	}
}
/*
$des = new des();
echo $ret = $des->encrypt("来自http://my.oschina.net/penngo的博客",'1111') . "\n";
echo $des->decrypt("I70BJJwkzbYKdmsuFGNCPXuktEyEdDvjoKDzZrV6DcrjvEtlAiMpfw==",'1111') . "\n"; 

*/


?>