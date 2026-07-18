<?php



$userarr=$this->db->my_class_array('bs_php_user','user_uid',$this->seesion_uid,' * ');
$loginip=get_session_value('USER_IP');
$loginDATE=get_session_value('USER_DATE');
if($userarr['user_mibao_wenti']!='' & $userarr['user_mibao_daan']!='' ){
$mibao = '已经填写!';
}else{
$mibao = '未设置!  (<a href="index.php?m=user&c=index&a=space&do=mibao">设置密保</a>)';
}
$loginip=ip($loginip);
$dengji=$this->user->GetDenJiArray($userarr['user_DenJi'],'name');

?> 