<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class admin_shuoshuo extends admin
{

    private $rc, $db, $GLOBALS_LANGS, $user_str_log, $user;

    function __construct()
    {
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->user = bs_lib::load_modules_class('user', 'user');


        $this->GLOBALS_LANGS = bs_lib::load_langs_array();
        $this->user_str_log = bs_lib::load_langs_array('user', 'user_str_log');


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
    function call_index()
    {

    /**
     * 保存
     */
    $arr=$this->db->my_class_array('bs_php_shuoshuo_ini','wei_ini','uesr_shuo_nini',' * ');
    $Submit_bao=set_post('Submit_bao');
    if($Submit_bao){
        $checkbox=(int)set_post('checkbox');
        $textarea=set_post('textarea');
        $sql="UPDATE`bs_php_shuoshuo_ini`SET`wei_ini_off`='$checkbox',`wei_ini_jin`='$textarea'WHERE`bs_php_shuoshuo_ini`.`wei_ini`='uesr_shuo_nini';";
        $tmp=$this->db->Bsphp_my_query($sql);
        if($tmp){
             $this->admin_alert('保存成功!');
        }else{
            $this->admin_alert('保存失败,SQL已经记录!');
        }
    }
    
    
    /**
     * 删除
     */
    $Submit_dele=set_post('Submit_dele');
    if($Submit_dele){
        $date=set_post('date');
        if($date=='')  $this->admin_alert('请输入删除日期!');
        $sql = "delete from`bs_php_shuoshuo`where`wei_date`<'$date';";
        $tmp=$this->db->Bsphp_my_query($sql);
        if($tmp){
            $this->admin_alert($date.'前的记录已经删除!');
        }else{
             $this->admin_alert('删除失败,SQL已经记录!');
        }
    }
    
    

        include bs_lib::load_default_path();
    }


}

?>