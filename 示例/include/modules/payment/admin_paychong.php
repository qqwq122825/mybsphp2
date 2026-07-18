<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class admin_paychong extends admin
{

    private $rc, $db, $GLOBALS_LANGS,$user_str_log, $user;

    function __construct()
    {
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->user = bs_lib::load_modules_class('user', 'user');


        $this->GLOBALS_LANGS = bs_lib::load_langs_array();
        $this->user_str_log = bs_lib::load_langs_array('user','user_str_log');
        
        


        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();
        // echo '控制器  继承初始化1<BR/>';
        //后台权限验证
        $this->COOKIES_KEY('C');
    }


    /**
     * @控制面板
     * 
     */
    function call_table()
    {



   
    
    
    /**
     * 下拉框批量操作
     */
    $Submit_class =set_post('Submit_class');
    $all=set_post('all');
    $select_class=set_post('select_class');
    if($select_class){
    if($select_class==1){  //删除账号
       if($this->db->my_in_delete('bs_php_rmb_pay_log','id',$all)){
        $this->admin_alert('订单信息已经删除!');
       }else{
         $this->admin_alert('订单删除失败,SQL执行失败,错误数据已保存!!');
       }
        
    }elseif($select_class==2){//清理50天前信息
        
        $date=HOST_UNIX - 181200;
        $date = date('Y-m-d H:i:s',$date);
        $sql="DELETE FROM`bs_php_rmb_pay_log`WHERE `pay_date`<'$date'";
        $tmp=$this->db->Bsphp_my_query($sql);
        if($tmp){
            $this->admin_alert('清理50天前信息执行成功');
        }else{
            $this->admin_alert('清理50天前信息执行失败');
        }

        
    }elseif($select_class==3){//清理50天前未成功订单
        
        $date=HOST_UNIX - 181200;
        $date = date('Y-m-d H:i:s',$date);
        $sql="DELETE FROM`bs_php_rmb_pay_log`WHERE `pay_date`>'$date' AND `pay_zhuangtai`!='0'";
        $tmp=$this->db->Bsphp_my_query($sql);
        if($tmp){
            $this->admin_alert('清理50天前未成功订单执行成功');
        }else{
            $this->admin_alert('清理50天前未成功订单执行失败');
        }
        
    }else{
        $this->admin_alert('你没有选择操作项目');
    }
    
    }//按钮点击接受
    
    

    
    
    
    
       /*翻页参数初始化*/





       /*系统翻页*/
       $FANYE = (int)set_get('id');
       
       
       if($FANYE>0){
        $db_ID=$FANYE-1;//防止注入
       }else{
        $db_ID=0;
        $FANYE=1;
       }
       
       
       $shu = 20; //每页显示 
       $db_ID=$db_ID*$shu;
       

       





 


       /**
        * @接收搜搜参数
        * 
        * 这里表显示以搜搜模式来显示
        */
       /*取得搜搜内容*/
       $soso = set_get('soso');
       $soso_id = (int)set_get('soso_id');
       $DESC_id = (int)set_get('DESC');
       $soso_id>0?$soso_id_show=$soso_id-1:$soso_id_show=0;
       $DESC_id==1?$DESC='ASC':$DESC='DESC';

      


       /**
        * @定义搜搜字段
        * 
        * $soso_db_table = 要搜搜的字段
        * 
        *   <option value="0">订单号</option>
            <option value="1">用户UID</option>
            <option value="2">充值状态</option>
            <option value="3">充值方式</option>
            <option value="4">充值金额</option>
        */
        
        
        if($soso_id==1){
            $soso_db_table = 'pay_uid';  
        }elseif($soso_id==2){
            $soso_db_table = 'pay_zhuangtai';  
        }elseif($soso_id==3){
            $soso_db_table = 'pay_lei';  
        }elseif($soso_id==4){
            $soso_db_table = 'pay_rbm';  
        }else{
             $soso_db_table = 'pay_id';   
        }
        
        
       





       /*开始枚举*/
       $sql = "SELECT*FROM`bs_php_rmb_pay_log`WHERE`$soso_db_table`LIKE CONVERT(_utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  ORDER BY`id`$DESC LIMIT $db_ID,$shu";
       $db_array_value = $this->db->Bsphp_my_query($sql);


       $sql_rows = "SELECT*FROM`bs_php_rmb_pay_log`WHERE`$soso_db_table`LIKE CONVERT(_utf8'%$soso%'USING utf8)COLLATE utf8_general_ci ORDER BY`id`$DESC";
       
       /**
        * @获取条数
        */
        $tmp= $this->db->Bsphp_my_query($sql_rows);
        $zongshu = $this->db->my_rows($tmp);





       /*翻页URL*/
       $url = "index.php?soso_id={$soso_id}&soso={$soso}&DESC={$DESC_id}&soso_ok=%E6%90%9C%E7%B4%A2&m={$_GET['m']}&c={$_GET['c']}&a={$_GET['a']}&t={$_GET['t']}&id=";


        include bs_lib::load_default_path();
    }






}



?>