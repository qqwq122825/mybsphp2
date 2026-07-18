<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');

/**
 * @系统配置
 *  
 */
class mian extends admin
{

    private $rc, $db, $purconfig;

    function __construct()
    {


        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->purconfig = bs_lib::load_libbsphp_class('purconfig', 'purconfig');
        
        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();
        
        //后台权限验证
        $this->COOKIES_KEY('C');

    }



    /**
     * @系统re_user配置
     * 
     */
    function call_setmostly()
    {
        $array = set_post();

        if (isset($array['setmostly']))
        {

            $bool = $this->purconfig->pur_configs('setmostly', $array);
            if ($bool)
            {
                alert('保存成功！ 请重新登录后台重载入界面','index.php?m=admin&c=main&a=index');
            } else
            {

                alert('保存失败！');
            }


        }
        
        
        
        include bs_lib::load_default_path();
    }


  

}

?>