<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='AGENT')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('user', 'user_web');
bs_lib::load_modules_common('agent', 'agent');
/**
 * @代理 registration
 * 
 */
class main extends user_web
{

    private $rc, $db, $user_array, $GLOBALS_LANGS, $user_str_log, $user, $seesion_uid;

    function __construct()
    {

        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();


        $this->user_str_log = bs_lib::load_langs_array('user', 'user_str_log');

        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->user = bs_lib::load_modules_class('user', 'user');


        $this->GLOBALS_LANGS = bs_lib::load_langs_array();


        if (bs_lib::get_configs_value('sys', 'stop_agent') == 0)
        {


            bs_lib::Alerts($this->GLOBALS_LANGS['sys'], bs_lib::get_configs_value('sys', 'stop_agent_info'));

            exit;
        }


        $USER_UID = get_session_value('USER_UID'); //登陆UID
        $this->user_array = $this->db->my_class_array('bs_php_user', 'user_uid', $USER_UID, ' * ');


        //代理权限验证
        if ($this->user_array['user_daili'] == 0)
        {
            alert2('你没有权限,请先登录。');
            location('index.php');
            exit;
        }

        /**
         * @登陆状态验证
         * 
         * 不等于登陆状态跳转到登陆页面
         */
        $login_log = $this->user->UserLoginInfo();
        if ($login_log != 1047)
        {
            alert2('你没有权限,请先登录。');
            location('index.php');

            exit;
        }

    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_main()
    {


        include bs_lib::load_default_path();
    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_center()
    {

        include bs_lib::load_default_path();
    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_down()
    {

        include bs_lib::load_default_path();
    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_left()
    {

        include bs_lib::load_default_path();
    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_top()
    {


        include bs_lib::load_default_path();
    }

    /**
     * @控制面板-登录面板
     * 
     */
    function call_info()
    {


        include bs_lib::load_default_path();
    }


}

?>