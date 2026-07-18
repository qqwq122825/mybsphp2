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
$type = set_get('type');




$sql = "SELECT * FROM  `bs_php_kalei` WHERE `lei_id` = $shopid LIMIT 1000 ";
$db_array_value_kalei = $this->db->Bsphp_my_array($sql);
$daihao = $db_array_value_kalei['lei_daihao'];


if($type==1){

//读取列表
$sql = "SELECT *  FROM  `bs_php_pattern_login`  WHERE  `L_daihao` ='$daihao' And `L_ic_name`='$user' LIMIT 1";
//echo $sql;
$arrays = $this->db->Bsphp_my_array($sql);

if ($arrays) {
    $array['is'] = 1;
} else {
    $array['is'] = 0;
}
}else{
    $array['is'] = 1;
}

/*   数据库字段说明
`pay_id` ,          订单代号  
`ali_id` ,   
`pay_uid` ,          用户UID
`pay_date` ,         添加时间
`pay_qian_rmb` ,     购买前账号金额
`pay_rmb` ,          购买后账号金额
`pay_ka_shuliang` ,  购买数量
`pay_zhuangtai` ,    订单状态 0 成功  1=已经付款  2没发卡
`ka_shijia` ,        打折后价格
`ka_zhe_jia` ,       优惠价格
`ali_ka_name` ,      卡类名称
`ali_ka_jiage` ,     卡的单价
`ali_ka_zhe` ,       购买折扣
`ali_ka_date` ,      卡的天数
`pay_daihao`         所属软件代号
`pay_app_name`       软件名称

*/
        /**
         * 查询软件名称
         * 
         */

        $app_name = GetAppInfoNameArray();
$ail_id = date('YmdHis', HOST_UNIX) . rand(1000, 9999); //订单号
$ka_kate = HOST_DATE;


$pay_rmb = $db_array_value_kalei['lei_jiage'];


$sql = "INSERT INTO`bs_php_pay_log`VALUES('$ail_id',NULL,'-1','$ka_kate','0','$pay_rmb','1','0','$pay_rmb','0','{$db_array_value_kalei['lei_name']}','$pay_rmb','0','{$app_name[$db_array_value_kalei['lei_daihao']]}','$daihao','{$app_name[$db_array_value_kalei['lei_daihao']]}','{$db_array_value_kalei['lei_type']}');";
if ($this->db->Bsphp_my_query($sql)) {

    $array['code'] = 1;
    $array['sdate'] = 1;
    $array['order'] = $ail_id;
    header('Content-type:text/json');
    $array = json_encode($array);
    echo $array;
    exit;

}

$array['code'] = 1;


header('Content-type:text/json');
$array = json_encode($array);
echo $array;
?>