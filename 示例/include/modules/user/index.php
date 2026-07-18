<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if (BSPHP_SET != 'USER')
    die('Not,This File Not Can in Ie Modules');

bs_lib::load_modules_class('user', 'user_web');
/**
 * @用户中心首页 registration
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


        if (bs_lib::get_configs_value('sys', 'stop_user') == 0)
        {


            bs_lib::Alerts($this->GLOBALS_LANGS['sys'], $this->GLOBALS_LANGS['stop']);

            exit;
        }


    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_index()
    {
    


        //判断是否已经登陆
        if ($this->user->UserLoginInfo() == 1047)
            location('index.php?m=user&c=index&a=space');


        //接收参数
        $imga_yan = set_post('imga');
        $user = set_post('user');
        $pwd = set_post('pwd');
        $login = set_post('login');
        if ($login)
        {
            //判断验证对错
            if (bs_lib::get_configs_value('code', 'coode_login') == true)
            {
                $log = push_cood_imges($imga_yan);
                if ($log != 1037)
                    die(alert($this->user_str_log[$log]));


            }


            //用户登陆
            $log = $this->user->UserWebLogin($user, $pwd);


            if ($log == 1011)
            {


                $ser_array = $this->db->my_class_array('bs_php_user', 'user_uid', $user, ' * ');
                //代理权限验证
                if ($ser_array['user_daili'] > 0)
                {
                    alert2('代理商没有权限登录用户中心');
                    $this->session->links_out_session_id($this->session->session_id);
                    location('index.php?m=user');
                    exit;
                }


                //系统日志记录
                bs_lib::addappenlog('user_login_log', '网页登陆', $user);
                //session 在线设置

                $this->session->links_add_info(0, $user);
                

                header('Location: index.php?m=user&c=index&a=space');
            }
            die(alert($this->user_str_log[$log]));
        }
        include bs_lib::load_default_path();
    }


    /**
     * @控制面板-找回密码
     * 
     */
    function call_password()
    {


        //注册账号表单赋值
        $ok = set_post('ok');
        $user = set_post('user');
        $pwd1 = set_post('pwd1');
        $pwd2 = set_post('pwd2');
        $mibao_wenti = set_post('mibao_wenti');
        $mibao_daan = set_post('mibao_daan');
        $imga_yan = set_post('imga');

        //通过验证开始注册
        if ($ok)
        {


            //判断验证对错
            if (bs_lib::get_configs_value('code', 'coode_backpwd') == true)
            {
                $log = push_cood_imges($imga_yan);
                if ($log != 1037)
                    die(alert($this->user_str_log[$log]));
            }


            /**
             * @找回密码
             * 
             * 参数1=用户账号
             * 参数2=用户新密码
             * 参数3=再次输入用户新密码
             * 参数4=密保问题
             * 参数5=密保答案
             * 
             * 通过密保信息找回密码
             */


            /**
             * @找回密码
             */
            $log = $this->user->UserMiBaoPwd($user, $pwd1, $pwd2, $mibao_wenti, $mibao_daan);

            alert($User_array_log[$log]);


        }


        include bs_lib::load_default_path();
    }


    /**
     * @控制面板-注册账号
     * 
     */
    function call_registration()
    {


        if (isset($_COOKIE['cookie_uid']))
        {
            $cookie_uid = $_COOKIE['cookie_uid'];

        } else
        {
            
            
            if (isset($_GET['u']))
            {
            setcookie('cookie_uid', $_GET['u'], time() + 86400, '/');
            }
            $get_uid = set_get('u');
            if ($get_uid > 0)
            {
                $cookie_uid = $get_uid;
            } else
            {
                $cookie_uid = '0';
            }

        }


        //注册账号表单赋值
        $ok = set_post('submit');
        $user = set_post('user');
        $pwd1 = set_post('pwd1');
        $pwd2 = set_post('pwd2');
        $qq = set_post('qq');
        $mail = set_post('mail');
        $yao_uid = set_post('yao_uid');
        $imga_yan = set_post('coode');

        //通过验证开始注册
        $yao_User = '';
        if ($ok)
        {


            //判断验证对错
            if (bs_lib::get_configs_value('imges', 'coode_registration') == true)
            {
                $log = push_cood_imges($imga_yan);
                if ($log != 1037)
                    die(alert($this->user_str_log[$log]));
            }




            /**
             * @注册账号
             */

            $log = $this->user->UserAdd($user, $pwd1, $pwd2, $qq, $mail, $yao_uid);
            if ($log == 1005)
            {



                /**
                 * 注册成功代码
                 */
                alert2('注册成功');
                location('index.php?m=user');
                exit;
            } else
            {
                alert($this->user_str_log[$log]);
            }

        } //ok end

        include bs_lib::load_default_path();
    }


    /**
     * @控制面板
     * 
     */
    function call_space()
    {
        $do = set_get('do');
        if ($do == null)
            $do = 'index';
        $file_do_pach = BSPHP_DIR_SYS . 'include/modules/user/source/space_' . $do . '.php';
        $file_do_pach_template = BSPHP_DIR_SYS . 'include/modules/user/source/space_' . $do . '.php';


        if (!file_exists($file_do_pach) and $do != null)
        {
            location('index.php?m=user&c=index&a=space');
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

            location('index.php?m=user&c=index');
            exit;
        }


        $this->seesion_uid = get_session_value('USER_UID');
        $user_array = $this->db->my_class_array('bs_php_user', 'user_uid', $this->seesion_uid, ' * ');


        include bs_lib::load_default_path();
    }


    /**
     * @控制面板
     * 
     */
    function call_logout()
    {

            $this->session->links_delete_one_id($this->session->session_id);
            set_session_value('USER_UID','-1');
            set_session_value("USER_UID", '-1');
            set_session_value('USER_YSE', '-1');
            set_session_value('USER_DATE', '-1');
            set_session_value('USER_IP', '-1');
            set_session_value('USER_MD7', '-1');
            location('index.php?m=user');
            exit;

    }


}

?>