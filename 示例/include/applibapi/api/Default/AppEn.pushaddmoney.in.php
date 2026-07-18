<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>GetPleaseregister.lg</appen>  
* <appname>余额充值</appname> 
* <sen>1<sen/>                
* <seng>user=>用户名</seng>
* <lei>ok</lei>               
* <info>判断用户账号是否存在</info> 
* *****************************************************************************
*/
/*</name>*/
$daihao = HOST_DAIHAO;
$user = set_data('user'); //充值账号
$ka = set_data('ka'); //充值卡号


$array_user = $this->db->my_class_array('bs_php_user', 'user_user', $user, '`user_uid`,`user_user`');



if (!is_array($array_user))
{

    PrInfo('充值账号不存在');


}




$array_card = $this->db->my_class_array('plug_bsphp_car_addmoney', 'amoney_kaname', $ka, ' * ');
if (!is_array($array_card))
{

    PrInfo('充值卡错误/或者不存在！');
}

if($array_card['amoney_lock']==1){ PrInfo('充值卡已经冻结');}
if($array_card['amoney_zhuangtai']==1){ PrInfo('充值卡已经充值作废,请购买新充值使用！');}



$sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'{$array_card['amoney_jine']}' WHERE`bs_php_user`.`user_user`='{$array_user['user_user']}';";
$tmp=$this->db->Bsphp_my_query($sql);
if($tmp){
 $date=HOST_DATE;
 
 $sql = "UPDATE`plug_bsphp_car_addmoney`SET `amoney_zhuangtai`='1',`amoeny_chong`='{$array_user['user_user']}',`amoney_yongtime`='$date' WHERE`plug_bsphp_car_addmoney`.`amoney_id`='{$array_card['amoney_id']}';";
 $this->db->Bsphp_my_query($sql); 
    
 PrInfo('充值成功');   
    
}else{
  PrInfo('充值失败！');  
    
}
?>