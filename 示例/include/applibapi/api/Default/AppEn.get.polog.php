<?php
	
     //登录连接数功能集代码
     links_chaoshi_login();
  $log = UserLoginInfo();
  $dataid=(int)set_data('dataid');
  if($dataid==0)$dataid='1';
  
  
  if ($log == 1047 || $log==1087) {
    
        
        $sql="SELECT * FROM `plug_bsphp_polog` WHERE `idname` = '$dataid'";
        $array=$this->db->Bsphp_my_array($sql);
        PrInfo($array[1]);
    
    

  }else{
    
      PrInfo($this->user_str_log[$log]);
  }
  
    
    
?>