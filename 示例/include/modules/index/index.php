<?php
header('Access-Control-Allow-Origin:*');
defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
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
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->template = bs_lib::load_modules_class('index', 'template');
        $this->GLOBALS_LANGS = bs_lib::load_langs_array();
    }


    function call_index()
    {


        if (bs_lib::get_configs_value('sys', 'stop_cms') == 0)
        {
            $this->template->print_Bsphpstyle('stop_index');
            exit;
        }


        //推广代码COOKIES记录

        //int setcookie(string name,string value,int expire,string path,string domain,int secure)
        if (isset($_GET['u']))
        {
            setcookie('cookie_uid', $_GET['u'], time() + 86400, '/');
        }
        
        
        location('user/index.php?m=user');


        //生成模板
        $act = set_get('act');
        if ($act == '')
        {
            $this->template->print_Bsphpstyle('index');

        } else
        {
            $this->template->print_Bsphpstyle($act);
        }
  }


}

?>