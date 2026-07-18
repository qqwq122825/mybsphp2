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


$shopid = set_get('shopid');
$user = set_get('user');

$sql = "SELECT `bs_php_kalei`.* FROM  `bs_php_kalei` WHERE `lei_id`>$shopid LIMIT 1000 ";
$db_array_value_kalei = $this->db->Bsphp_my_array($sql);
$daihao = $db_array_value_kalei['lei_daihao'];


//读取列表
$sql = "SELECT *  FROM  `bs_php_pattern_login`  WHERE  `L_daihao` ='$daihao' And `L_ic_name`='$user' LIMIT 1";
//echo $sql;
$arrays = $this->db->Bsphp_my_array($sql);

if($arrays){
    $array['is'] = 1;
}else{
    $array['is'] = 0;
}




$array['code']=1;




header('Content-type:text/json');
$array = json_encode($array);
ECHO $array;
?>