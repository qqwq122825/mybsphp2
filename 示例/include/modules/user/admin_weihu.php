<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class admin_weihu extends admin
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
        
        
        
        //后台权限验证
        $this->COOKIES_KEY('C');
    }


    /**
     * @控制面板
     * 
     */
    function call_user()
    {


        $ok = set_post('ok');
        //维护提交
        if ($ok)
        {
            
            
            @eval(base64_decode('ICAgICAgICRhZG1pbl9pbmZvID0gQWRtaW5fVmVyaWZpY2F0aW9ucygnUGF5bWVudCcpOyAgICAgICBpZiAoJGFkbWluX2luZm8gIT0gMSkNCiAgICAgICBic19saWI6OkFsZXJ0cygn57O757uf5pyq5o6I5p2DJywgJ+WwiuaVrOeUqOaItyzkvaDlvZPliY3ns7vnu5/nlKjmiLfnrqHnkIbmibnph4/nu7TmiqTmnKrnu4/mjojmnYPml6Dms5Xkvb/nlKgs6K+35YWI6LSt5Lmw5o6I5p2D5Zyo5L2/55So44CC5Zyo57q/6LSt5Lmw5Zyw5Z2AOmh0dHA6Ly9NYW5hZ2VtZW50LkJzcGhwLmNvbScpOw0KICAgICAgICAg'));


            /**
             * 接收参数
             */
            $select = set_post('select');
            $checkbox = set_post('checkbox');
            $radiobutton = set_post('radiobutton'); //
            $text_add = set_post('text_add'); //
            $text_bus = set_post('text_bus'); //
            $text_zhe = set_post('text_zhe'); //
            $day = set_post('day'); //OK


            /**
             * 选择代理是否在范围
             */

            if ($checkbox)
            {

                $daiil = "`bs_php_user`.`user_daili`=='0'";
            } else
            {
                $daiil = "`bs_php_user`.`user_daili`=='1'";//顶级代理
            }


            /**
             * 用户范围-//SQL头
             */


            if ($select == 'user_all')
            {

                $ifno_WHERE = "WHERE $daiil";

            }
            elseif ($select == 'out_1')
            { //状态冻结

                $ifno_WHERE = "WHERE`bs_php_user`.`user_IsLock`='1' AND $daiil";

            }
            elseif ($select == 'out_0')
            { //状态正常

                $ifno_WHERE = "WHERE`bs_php_user`.`user_IsLock`='0' AND $daiil";

            }
            elseif ($select == 'login_not')
            { //未登录过用户

                $ifno_WHERE = "WHERE`bs_php_user`.`user_LoGinNum`='0' AND $daiil";

            }
            elseif ($select == 'login_today')
            { //今天登陆用户
                $tody_date = date('Y-m-d', HOST_UNIX); //日期1
                $ifno_WHERE = "WHERE`bs_php_user`.`user_Login_date`>'$tody_date' AND $daiil";

            }
            elseif ($select == 'all')
            { //全部代理商

                $ifno_WHERE = "WHERE`bs_php_user`.`user_daili`='1' AND $daiil";

            } else
            {
                alert('请选择维护的范围！');
            }


            /**
             * SQL设置内容
             * 
             * 1=添加金额
             * 2=减少金额
             * 3=折扣
             * 4=状态冻结
             * 5=状态解冻
             * 6=删除
             * 7=删除->时间
             */


            if ($radiobutton == 1)
            {
                if ($text_add < 0)
                    $this->admin_alert('请输入要添加金额');
                $sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$text_add'  $ifno_WHERE";
            }
            elseif ($radiobutton == 2)
            {
                if ($text_bus < 0)
                    $this->admin_alert('请输入要减少金额');
                $sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`-'$text_bus'  $ifno_WHERE";
            }
            elseif ($radiobutton == 3)
            {
                $sql = "UPDATE`bs_php_user`SET`user_Zhe`='$text_zhe' $ifno_WHERE";
            }
            elseif ($radiobutton == 4)
            {
                $sql = "UPDATE`bs_php_user`SET`user_IsLock`='1' $ifno_WHERE";
            }
            elseif ($radiobutton == 5)
            {
                $sql = "UPDATE`bs_php_user`SET`user_IsLock`='0' $ifno_WHERE";
            }
            elseif ($radiobutton == 6)
            { //删除
                $sql = "DELETE FROM`bs_php_user`$ifno_WHERE";
            }
            elseif ($radiobutton == 7)
            { //删除+时间
                if ($day < 0)
                    $this->admin_alert('请输入都天没登录范围');
                $zhi = 3600 * 24 * $day;
                $zhi = HOST_UNIX - $zhi;
                $tody_date = date('Y-m-d', $zhi); //日期1
                $sql = "DELETE FROM`bs_php_user`$ifno_WHERE AND`bs_php_user`.`user_Login_date`<'$tody_date'";
            } else
            {
                $this->admin_alert('请选择操作维护的类型！');
            }


            /**
             * 执行SQL
             */
            $tmp = $this->db->Bsphp_my_query($sql);

            if ($tmp)
            {

                $this->admin_alert('执行成功！');
            } else
            {

                $this->admin_alert('执行失败！');
            }


        }


        include bs_lib::load_default_path();
    }


}

?>