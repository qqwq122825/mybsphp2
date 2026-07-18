<?php


  $daihao = set_get('daihao');
  $url = "index.php?m=user&c=index&a=space&do=ecshop&action=tb&daihao=".$daihao;
  $action = set_get('action');
  
  
  
  
  $User_Inof=$this->db->my_class_array('bs_php_user','user_uid',$this->seesion_uid,' * ');
  $carsys_arr=bs_lib::load_langs_array('applib','admin_card');
?>