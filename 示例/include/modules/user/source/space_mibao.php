<?PHP


  /**
   * 调用修改密码API
   * 
   * 由于需要刷新所有了个跳转
   */
   
   $LOG=$this->user->UserEmptyMiBaoInfo($this->seesion_uid);
   if($LOG==1023)  exit($this->alert( '你的密保信息已经填写了。!','index.php?m=user&c=index&a=space'));


  $daan = set_post('daan');
  $select = set_post('select');
  if ($select) {
    $LOG = $this->user->UserMiBaoInfo($this->seesion_uid,$select, $daan);
    $name = $this->user_str_log[$LOG];
    if ($LOG == 1024) {
      alert2('密保设置成功请牢记            \n\n\n问题:'.$select.'\n答案:'.$daan);
      $this->alert( '密保添加成功!','index.php?m=user&c=index&a=space');
    } else {
       
    }
  } else {

    $name = null;
  }


?>