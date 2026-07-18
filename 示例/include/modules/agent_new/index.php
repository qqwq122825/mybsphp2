<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if (BSPHP_SET != 'AGENT')
    die('Not,This File Not Can in Ie Modules');

bs_lib::load_modules_class('user', 'user_web');
/**
 * @代理 registration
 * 
 */
class index extends user_web
{

    private $rc, $db, $GLOBALS_LANGS, $user_str_log, $user, $seesion_uid;

    function __construct()
    {

        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();
        $this->user_str_log = bs_lib::load_langs_array('user', 'user_str_log');

        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->user = bs_lib::load_modules_class('user', 'user');

        $this->GLOBALS_LANGS = bs_lib::load_langs_array();


        if (bs_lib::get_configs_value('sys','stop_agent') == 0)
        {


            bs_lib::Alerts('系统提示',bs_lib::get_configs_value('sys','stop_agent_info'));
            exit;
        }


    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_index()
    {


        /**
         * 获取登录信息
         */

        if (isset($_POST['adminlogin']))
        {


            $amdin_name = set_post('amdin_name');
            $admin_password = set_post('admin_password');
            $imga_yan = set_post('imga');


            //判断验证对错

            if (bs_lib::get_configs_value('imges', 'coode_login') == true)
            {
                $log = push_cood_imges($imga_yan);
                if ($log != 1037)
                    die(alert($this->user_str_log[$log]));

            }

            /**
             * @用户登陆
             * 
             * 直接调用类登陆
             * 1011 = 登陆成功
             */
            $log = $this->user->UserWebLogin($amdin_name, $admin_password);
            if ($log == 1011)
            {
                //验证是否代理商
                $user_array = $this->db->my_class_array('bs_php_user', 'user_user', $amdin_name, ' * ');
                if ($user_array['user_daili'] == 0)
                {
                    set_session_value('USER_UID', 'Not'); //登陆UID
                    alert('需要代理商才可登录,你没有权限。');
                    exit;
                }
                //系统日志记录
                bs_lib::addappenlog('user_login_log', '登录代理平台', $user_array['user_user']);
                //session 在线设置
                $this->session->links_add_info(0, $user_array['user_user']);
                header('Location: index.php?m=agent_new&c=main');
            }

            die(alert($this->user_str_log[$log]));


        }


        include bs_lib::load_default_path();
    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_loginout()
    {
        //等于用户前台和代理登录时候设置cookie
        set_session_value('USER_UID', ''); //登陆UID
        set_session_value('USER_YSE', ''); //登陆MD7加密 , 密码
        set_session_value('USER_DATE', ''); //上一次登陆时间
        set_session_value('USER_IP', ''); //上一次登陆IP
        set_session_value('USER_MD7', ''); //OOKIE MD7验证串

        header("Location: index.php?m=agent_new");


    }


}

?>