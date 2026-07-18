<?php
class user_web {
    
    
    
    
    
  /**
   * @用户信息提示框
   * 
   * 参数1=标题
   * 参数2=跳转连接
   * 
   * 会员也专属提示框
   */
  function alert($a, $url = '') {
    echo '<meta http-equiv="refresh" content="0;URL=index.php?m=user&c=index&a=space&do=alert&at='.$a.'&url='.urlencode($url).'" />';
    exit;

  }

    
    
    
    
	
}
?>