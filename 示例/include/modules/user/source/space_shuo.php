<?php
$arr=$this->db->my_class_array('bs_php_shuoshuo_ini','wei_ini','uesr_shuo_nini',' * ');
//屏蔽关键字
function user_eregi($GuoLu,$name) {

        
        $qsl_biaoda = @eregi($name, $GuoLu);
        if ($qsl_biaoda) {
            return 1 ;
        }
         return 0 ;
    }
    

//提交内容
$url='index.php?m=user&c=index&a=space&do=shuo';
$textarea=set_post('textarea'); 
if ($textarea) {
    if($arr['wei_ini_off']){
         $this->alert('管理员已经禁止用户发表信息！',$url);

        
    }
    
    
    $shuo =set_post('textarea');
    if(user_eregi($shuo,$arr['wei_ini_jin'])){
        
         $this->alert('亲！ 你输入禁止内容请重新输入！请重新输入。',$url);

    }
    $shuo=base64_encode($shuo);
    
    $date=HOST_DATE;
	$ip=getip();
    $sql = "INSERT INTO`bs_php_shuoshuo`VALUES(NULL ,'$shuo','{$user_array['user_user']}','$date','$ip','');";

    if ($this->db->Bsphp_my_query($sql)) {
        $this->alert('你所发布信息已经成功！',$url);
    } else {
        $this->alert('发布失败！',$url);
    }


}

?>