<?php
/**
 * @模板php代码/对应模板目录对应文件htmlphp代码
 * 
 * 2015.10.17
 * 
 * 小周
 * 
 * $print_html可以拦截模板HOOK
 * 
 * 
 */

//读取列表
$sql = "SELECT  `app_daihao` ,  `app_name` ,  `app_MoShi` FROM  `bs_php_appinfo` LIMIT 100";
$db_array_value_app = $this->db->Bsphp_my_query($sql);


$array_app = array();
$arr_name = array();
while ($value = mysqli_fetch_array($db_array_value_app)) {
  $array_app[]=array('key'=>$value['app_daihao'],'v'=>$value['app_name']);
  $arr_name[$value['app_daihao']] = $value['app_name'];
}


$sql = "SELECT `bs_php_kalei`.* FROM  `bs_php_kalei` WHERE `lei_money` > 0 LIMIT 1000 ";
$db_array_value_kalei = $this->db->Bsphp_my_query($sql);
$array_kalei = array();
while ( $var = mysqli_fetch_array($db_array_value_kalei)) {
  
  
  if(isset($arr_name[$var['lei_daihao']])){
  $array_kalei[]=array('id' => $var[0],'list'=>$var['lei_daihao'],'appname' => $arr_name[$var['lei_daihao']],'money' => $var['lei_money'],'name' => $arr_name[$var['lei_daihao']].'-'.$var['lei_name'] );
  }
        

} 



$array['code']=1;
$array['list']=$array_app;
$array['shop']=$array_kalei;


header('Content-type:text/json');
$array = json_encode($array);
ECHO $array;
?>