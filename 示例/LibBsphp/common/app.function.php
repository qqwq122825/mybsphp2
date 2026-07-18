<?php
defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
/**
 * @applibapi 应用函数
 * 
 * 本目录下函数全局通用
 */

/**
 * 获取模式配置数组
 */
function get_moshiset_array($moshisetname)
{

    $path_moshiset = BSPHP_DIR_SYS . 'include/applibapi/moshiset/' . $moshisetname . '.php';

    
    //判断文件是否存在
    if (file_exists($path_moshiset))
    {

        //引入文件并 判断文件是否符合类型
        $moshiset_array = include ($path_moshiset);

        if (is_array($moshiset_array))
        {
            return $moshiset_array;

        } else
        {

            return false;
        }


    } else
    {

        return (false);
    }


}



function GetAppMoShiArray ($moshi){
    $path_moshiset = BSPHP_DIR_SYS . 'include/applibapi/moshiset/'.$moshi.'.php';
    $array = include ($path_moshiset);
    return($array);
	
}


  
  /**
   * 或者软件名称信息,对应的软件ID获取名字
   */
function GetAppInfoNameArray (){
	$db = bs_lib::load_libbsphp_class('mysql', 'mysql');
    /**
     * 查询软件名称
     * 
     */
     
     $sql="SELECT `app_daihao`,`app_name` FROM`bs_php_appinfo`";
     $tmp=$db->Bsphp_my_query($sql);

     $apparray=array();
     while ($value= mysqli_fetch_array($tmp))
            {
                $apparray[$value['app_daihao']]=$value['app_name'];

            }
     return $apparray;
}
  

  





/**
 * 读取软件配置
 */
function get_appdaihao_info_array($daihao)
{
  
  
  $db = bs_lib::load_libbsphp_class('mysql', 'mysql');
  $arr = $db->my_class_array('bs_php_appinfo', 'app_daihao', $daihao, ' * ');

  return($arr);
  

}


  /**
   * *******************************
   * 获取随机数
   * 
   * 参数1=随机数长度
   * 
   */
  function gte_test_arrs($int) {
    
      /**********取随机数字数组**************/
  $check_list = array('q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', 'l', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'z', 'z', 'x', 'c', 'v', 'b', 'n', 'm', 'Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'I', 'P', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'Z', 'X', 'C', 'V', 'B', 'N', 'M', 1, 2, 3, 4, 5, 6, 7,
    8, 9, 0);
    
    $test = '';
    shuffle($check_list); //打乱
    $ki = 0;
    while ($ki < $int) {
      $test .= $check_list[mt_rand(1, 61)];
      $ki++;
    }
    return $test;

  }
  
  

?>