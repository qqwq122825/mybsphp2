<?php
defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin','admin');


$purconfig = bs_lib::load_libbsphp_class('purconfig','purconfig');

/**
 * @管理员登录
 * 后台管理员登录  extends admin
 */
class index extends admin
{

    private  $GLOBALS_LANGS, $db, $session;

    function __construct()
    {

        $this->GLOBALS_LANGS = bs_lib::load_langs_array('admin','index');
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');

        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();
        
        @open_code('aWYoQnNwaHBfSE9TVF9TUkUhPT0mIzM5O2h0dHA6Ly9NYW5hZ2VtZW50LkJzcGhwLmNvbS9BdXRob3JpemUyMDE4LyYjMzk7KXtoZWFkZXIoJiMzOTtIVFRQLzEuMSA1MDIgSW50ZXJuYWwgU2VydmVyIEVycm9yJiMzOTspO30=');




        // echo '控制器  继承初始化1<BR/>';
    }


    /**
     * @管理员登录
     * 自动处理完成并载入块
     */
    function call_index()
    {


        /**
         * @判断是否已经登陆
         */

        if ($this->admin_login('not') == 1000)
            header("Location: ?m=admin&c=main&a=index");

        //echo '12345'.$this->admin_login('not');


        $user = strtolower(set_post('amdin_name'));
        $mibao = set_post('admin_mibao');
        $password = set_post('admin_password');
        $adminlogin = set_post('adminlogin');


        if (!empty($adminlogin)) {
            $sql = "SELECT`Admin_AdminUserName`,`Admin_AdminPassWord`,`Admin_MiBao`,`Admin_IsLock`,`Admin_LoGDaTe`,`Admin_LoGinIP`,`Admin_LoGinNum`,`Admin_ID`FROM`bs_php_admin`WHERE`Admin_AdminUserName`='$user'";
            $array = $this->db->Bsphp_my_array($sql);
            /*验证表单*/

            if (is_array($array) == false) {
                bs_lib::addappenlog('admin_login_log', "可疑登录 => 账号不存在 尝试密码:{$password} 密保:{$mibao}", $user);
                die(alert('账号不存在,或者错误!'));
            } elseif ($this->md8($password) != $array['Admin_AdminPassWord']) {
                bs_lib::addappenlog('admin_login_log', "可疑登录 => 密码错误 尝试密码:{$password} 密保:{$mibao}", $user);
                die(alert('密码错误,请重新输入！'));
            } elseif ($mibao != $array['Admin_MiBao']) {
                bs_lib::addappenlog('admin_login_log', "可疑登录 => 密保错误 尝试密码:{$password} 密保:{$mibao}", $user);
                die(alert('验证密保错误,请重新输入！'));

            } elseif ($array['Admin_IsLock'] != 0) {
                bs_lib::addappenlog('admin_login_log', "可疑登录 => 号已经被冻结 尝试密码:{$password} 密保:{$mibao}", $user);
                die(alert('你的账号已经被冻结！'));

            }


            //& $user == $array[0]
            if ($array['Admin_IsLock'] == 0 and $mibao == $array['Admin_MiBao'] and $this->md8($password) == $array['Admin_AdminPassWord']) {
                /**
                 * SQL语句基础配置
                 */
                $uid = $array['Admin_ID'];
                $LoGinIP = getip();
                $CaoShi = HOST_UNIX;
                $LoGDaTe = HOST_DATE;
                $sql = "UPDATE`bs_php_admin`SET`Admin_LoGinIP`='$LoGinIP',`Admin_LoGinNum`=`Admin_LoGinNum`+1,`Admin_CaoShi`='$CaoShi',`Admin_LoGDaTe`='$LoGDaTe'WHERE`bs_php_admin`.`Admin_ID`='$uid';";
                $this->db->Bsphp_my_query($sql);
                /**
                 * COOKIE基础配置
                 */

                $COOKIE_date = $array['Admin_LoGDaTe'];
                $COOKIE_ip = $array['Admin_LoGinIP'];
                $COOKIE_password = cookies_md7($this->md8($password));
                $LoGinNum = $array['Admin_LoGinNum'];
                $LoGinNum++;
                $md7 = cookies_md7($uid . $COOKIE_password . $COOKIE_date . $COOKIE_ip . $LoGinNum);
                set_session_value('ADMIN', 'yse'); //站位
                set_session_value('ADMIN_UID', $uid);
                set_session_value('ADMIN_YSE', $COOKIE_password);
                set_session_value('ADMIN_DATE', $COOKIE_date);
                set_session_value('ADMIN_IP', $COOKIE_ip);
                set_session_value('ADMIN_MD7', $md7);
                bs_lib::addappenlog('admin_login_log', "管理员登录", $user);
                
                
                
                $this->session->links_add_info(0, $user);
                header("Location: ?m=admin&c=main&a=index");


                exit;
            } else
                alert('登陆失败，账号/密码/密码/已经封号，请重新登陆！' . $user . $array['Admin_AdminUserName']);
        }


        include bs_lib::load_default_path();

    }

    /**
     * @管理员登录
     * 自动处理完成并载入块
     */
    function call_logut(){
    
    set_session_value('ADMIN', ''); //站位
    set_session_value('ADMIN_UID', '');
    set_session_value('ADMIN_YSE', '');
    set_session_value('ADMIN_DATE', '');
    set_session_value('ADMIN_IP', '');
    set_session_value('ADMIN_MD7', '');
    session_destroy();//删除
    
      header("Location: ?m=admin");
    }



}
?>