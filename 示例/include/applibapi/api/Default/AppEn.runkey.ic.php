<?php

$car_id = set_data('icid');

$daihao = HOST_DAIHAO;

$carinfo = GetCardInfo($car_id, '', $daihao);

$NEW_ID = gte_test_arr(16,0);
//添加绑定
$sql = "UPDATE`bs_php_pattern_login`SET`L_key_info`='$NEW_ID'  WHERE  `L_id`='{$carinfo['L_id']}';";
$tmp = $this->db->Bsphp_my_query($sql);

PrInfo($NEW_ID);

?>