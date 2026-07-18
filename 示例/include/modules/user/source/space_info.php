<?PHP

/**
 * 调用修改密码API
 * 
 * 由于需要刷新所有了个跳转
 */

if(set_post('Submit')){
$qq=set_post('qq');
$mail=set_post('mail');
$dh=set_post('dh');
$LOG=$this->user->UserAddInfo($this->seesion_uid,$qq,$mail,$dh);
$name=$this->user_str_log[$LOG];
if($LOG==1017){

$this->alert( '个人信息修改成功!','index.php?m=user&c=index&a=space&do=info');;
}



}else{
    

//读取用户数据库
//$userarr=$this->db->Bsphp_my_array('bs_php_user','user_uid',$this->seesion_uid,'`user_user`,`user_qq`,`user_Mobile`,`user_email`');

 

}

$userarr = $this->db->my_class_array('bs_php_user', 'user_uid', $this->seesion_uid, ' * ');

?>