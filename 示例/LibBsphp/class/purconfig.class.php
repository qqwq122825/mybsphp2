<?php
defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
	/**
	 * @写入系统config配置
     * 
     * 用配置系统参数
     * 
     * 2015.2.21
	 */
    //addslashes stripslashes
class purconfig {
                                                                
        private $file_pach_name='';//是否使用表格布局
                                                                
        private $action;//表单要提交到的URL
       
       
                                
     /**
      * @数组合并函数
      * 
      * 旧数组与新数组合并
      * 参数1=旧数组
      * 参数2=新数组
      * 旧数组没有是否新添加
      */
     function add_array_code ($oid_array,$new_array){
        
        $return_array=array();
        
        //参数修改
        foreach ($oid_array as $value=>$key){
            
            if(isset($new_array[$value])==FALSE){
                $return_array[$value]=$oid_array[$value];
            }else{
                $return_array[$value]=$new_array[$value];
                
            } 
        }
        
        return $return_array;
    }

    
    
    
    
    
    
       
     /**
      * @输出可转换php代码
      * 
      * 输出配置函数
      * 
      */
     function echo_array_code ($array){
        
        $code_array_php='<?php return array(';
        
        //参数修改
        foreach ($array as $value=>$key){
            
            $key=addslashes($key);
            $code_array_php.="'$value'=>'$key',";


        }
        $code_array_php.='); ?>';
        
        return $code_array_php;
    }
    
    
    
    
    
     /**
      * @输出保存转换php代码
      * 
      * 输出配置函数
      * 
      */
     function pur_file ($pach,$code){
        
         $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
         
         if(defined('OR_BS_SERVER')){
            
            $code = json_encode($code);
            $sql="UPDATE  `bs_php_sysconfig` SET  `sys_val` =  \"$code\" WHERE  `bs_php_sysconfig`.`sys_ini` =  '$pach';";
            $this->db->Bsphp_my_query($sql);
            return true;
            
            
         }else{
            
            $File = BSPHP_DIR_SYS . 'Data/configs/' . $path . '.config.php';
            $tmp=file_put_contents($File,$code);
             return $tmp;
         }
         
        

       
    }
    
    
    
    
     /**
      * @保存配置
      * 
      * 输出配置函数
      * 参数1=配置文件名
      * 参数2=数组配置
      * 参数3=样板数组
      * 参数4=保存前缓
      * 
      */
     function pur_configs ($pach,$new_array,$oid_array=NULL,$haed=''){
         

         
         $db = bs_lib::load_libbsphp_class('mysql', 'mysql');

         if(defined('OR_BS_SERVER')){
              if($oid_array==NULL)$oid_array=bs_lib::get_configs_value($pach);
              $code=$this->add_array_code($oid_array,$new_array);
         
            $code = addslashes(json_encode($code));
            $sql=
            "INSERT INTO bs_php_sysconfig (sys_ini, sys_val) VALUES ('{$haed}{$pach}',  \"$code\")ON DUPLICATE KEY UPDATE `sys_val` =  \"$code\""; 
            
            
            
            $db->Bsphp_my_query($sql);
            return true;
            
            
         }else{
            if($oid_array==NULL)$oid_array=bs_lib::get_configs_value($pach);
         $a=$this->add_array_code($oid_array,$new_array);
         $code=$this->echo_array_code($a);
         $File = BSPHP_DIR_SYS . 'Data/configs/' .$haed. $pach . '.config.php';
         $tmp=file_put_contents($File,$code);
         return $tmp;
         }
         

    }
    
}

    
?>