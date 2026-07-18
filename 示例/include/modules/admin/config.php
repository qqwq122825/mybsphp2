<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if (BSPHP_SET != 'ADMIN')
    die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');

/**
 * @系统配置
 *  
 */
class config extends admin
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
     * @系统配置
     * 
     */
    function call_sys()
    {
        $array = set_post();
        if (isset($array['appenconfig'])) {

            $bool = $this->purconfig->pur_configs('sys', $array);
            if ($bool) {
                alert('保存成功！');
            } else {

                alert('保存失败！');
            }


        }
        include bs_lib::load_default_path();
    }


    /**
     * @系统session配置
     * 
     */
    function call_session()
    {
        $array = set_post();
        if (isset($array['appenconfig'])) {

            $bool = $this->purconfig->pur_configs('sys', $array);
            if ($bool) {
                alert('保存成功！');
            } else {

                alert('保存失败！');
            }


        }
        include bs_lib::load_default_path();
    }


    /**
     * @系统re_user配置
     * 
     */
    function call_re_user()
    {
        $array = set_post();

        if (isset($array['appenconfig'])) {

            $bool = $this->purconfig->pur_configs('user', $array);
            if ($bool) {
                alert('保存成功！');
            } else {

                alert('保存失败！');
            }


        }


        include bs_lib::load_default_path();
    }


    /**
     * @系统template配置
     * 
     */
    function call_template()
    {
        $array = set_post();

        if (isset($array['appenconfig'])) {

            $bool = $this->purconfig->pur_configs('sys', $array);
            if ($bool) {
                alert('保存成功！');
            } else {

                alert('保存失败！');
            }


        }


        //语言
        $path = BSPHP_DIR_SYS . 'include/langs';
        $dir_array = open_list_dir($path);


        //模板
        $path = BSPHP_DIR_SYS . 'include/templates';
        $temp_array = open_list_dir($path);


        include bs_lib::load_default_path();
    }


    /**
     * @系统template配置
     * 
     */
    function call_code()
    {
        $array = set_post();
        if (isset($array['appenconfig'])) {

            $bool = $this->purconfig->pur_configs('code', $array);
            if ($bool) {
                alert('保存成功！');
            } else {

                alert('保存失败！');
            }


        }
        include bs_lib::load_default_path();
    }


    /**
     * @系统配置-计算时间
     * 
     */
    function call_date()
    {


        $xitong = set_post('xitong');
        date_default_timezone_set('PRC');
        $date = date('Y-m-d H:i:s');
        $cha = strtotime($date) - strtotime($xitong);

        include bs_lib::load_default_path();
    }


    /**
     * @邮件配置
     * 
     */
    function call_mail()
    {

        $array = set_post();
        if (isset($array['appenconfig'])) {

            $bool = $this->purconfig->pur_configs('mail', $array);
            if ($bool) {
                alert('保存成功！');
            } else {

                alert('保存失败！');
            }


        }


        if (isset($array['send_mail'])) {

            $mailobj = bs_lib::load_libbsphp_class('mail', 'mail');

            $bool = $mailobj->send_mail($array['send_mail_text'], 'BsphpSend @' . HOST_DATE,
                'Bsphp@' . HOST_DATE);

            if ($bool == -1) {
                alert('发送成功！' . 'BsphpSend @' . HOST_DATE);
            } else {

                alert('发送失败，错误信息:' . $bool);
            }


        }

        include bs_lib::load_default_path();
    }


    /**
     * @淘宝api
     * 
     */
    function call_taobaoapi()
    {

        $array = set_post();
        if (isset($array['appenconfig'])) {

            $bool = $this->purconfig->pur_configs('taobaoapi', $array);
            if ($bool) {
                alert('保存成功！');
            } else {

                alert('保存失败！');
            }


        }


        include bs_lib::load_default_path();
    }


    /**
     * @推广
     * 
     */
    function call_extension()
    {

        $array = set_post();
        if (isset($array['appenconfig'])) {

            $bool = $this->purconfig->pur_configs('extension', $array);
            if ($bool) {
                alert('保存成功！');
            } else {

                alert('保存失败！');
            }


        }


        include bs_lib::load_default_path();
    }


    /**
     * @系统agent配置
     * 
     */
    function call_agent()
    {
        $array = set_post();

        if (isset($array['appenconfig'])) {
            // print_r($array);

            // exit;
            $bool = $this->purconfig->pur_configs('agents', $array);
            if ($bool) {

                alert('保存成功！');
            } else {

                alert('保存失败！');
            }


        }
        include bs_lib::load_default_path();
    }


    /**
     * @系统agent配置
     * 
     */
    function call_buychong()
    {
        $array = set_post();

        if (isset($array['appenconfig'])) {
            //print_r($array);

            // exit;
            $bool = $this->purconfig->pur_configs('buychong', $array);
            if ($bool) {

                alert('保存成功！');
            } else {

                alert('保存失败！');
            }


        }
        include bs_lib::load_default_path();
    }


}

?>