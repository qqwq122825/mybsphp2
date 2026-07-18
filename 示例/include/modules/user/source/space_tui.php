<?php
	$User_Inof=$this->db->my_class_array('bs_php_user','user_uid',$this->seesion_uid,' * ');
    
    $COMFIGARR=$this->db->my_class_array('bs_php_user_yaoqing_ini','ini','yaoini',' * ');
    $user_url=bs_lib::get_configs_value('sys','url');
?>