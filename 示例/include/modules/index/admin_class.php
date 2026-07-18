<?php
defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if (BSPHP_SET != 'ADMIN')
    die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @用户中心首页 registration
 * 
 */
class admin_class extends admin
{

    private $rc, $db, $GLOBALS_LANGS, $user_str_log, $user, $seesion_uid;

    function __construct()
    {

        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');

        $this->GLOBALS_LANGS = bs_lib::load_langs_array();


        $this->ip = bs_lib::load_libbsphp_class('ip', 'ip');


        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();
        // echo '控制器  继承初始化1<BR/>';


        //后台权限验证
        $this->COOKIES_KEY('B');


        $admin_uid = get_session_value('ADMIN_UID');
        $this->admin_global_array = $this->db->Bsphp_my_array("SELECT * FROM`bs_php_admin`WHERE`Admin_ID`='$admin_uid'");
    }


    function call_class()
    {
        $delete = set_get('delete');
        if ($delete) {
            //删除SQL
            $sql = "DELETE FROM`plug_bsphp_news_class` WHERE `plug_bsphp_news_class`.`class_id`='$delete';";
            $tmp = $this->db->Bsphp_my_query($sql);
            if ($tmp) {
                alert('选择栏目已经删除！');
            } else {
                alert('删除失败,SQL执行失败,错误数据已保存!!');
            }

        }


        /*开始枚举*/

        $sql = "SELECT*FROM`plug_bsphp_news_class`";
        $db_array_value = $this->db->Bsphp_my_query($sql);


        include bs_lib::load_default_path();
    }


    function call_add()
    {

        /*载入html模版*/
        $fis = 'add';

        $ok = set_post('ok');
        if ($ok) {
            $class = set_post('class');
            $name = set_post('name');
            if ($class <= 0)
                $notclass = '请输入栏目ID数字,';
            if ($name == '')
                $notname = '请输入名称,';
            $sql = "INSERT INTO`plug_bsphp_news_class  (`class_id`, `class_name`) `VALUES ('$class','$name');";


            //执行SQL
            if ($class > 0 & $name != '') {
                if ($this->db->Bsphp_my_query($sql))
                    alert('栏目加成功!');
                else
                    alert('添加失败,SQL语句执行失败,已记录错误SQL!');
            }

        }


        include bs_lib::load_default_path();
    }


    function call_usb()
    {


        /*载入html模版*/
        $fis = 'usb';
        $id = set_get('id');
        $ok = set_post('ok');
        if ($ok) {
            $name = set_post('name');
            $class_id = set_post('class_id');

            if ($name == '')
                $notname = '请输入名称,';
            $sql = "UPDATE`plug_bsphp_news_class`SET`class_name`='$name'WHERE`plug_bsphp_news_class`.`class_id`='$class_id';";

            //执行SQL
            if ($class_id > 0 & $name != '') {
                if ($this->db->Bsphp_my_query($sql))
                    alert('栏目修改成功!');
                else
                    alert('修改失败,SQL语句执行失败,已记录错误SQL!');
            } else {

                alert('非法参数！!');
            }


        }


        $array = $this->db->my_class_array('plug_bsphp_news_class', 'class_id', $id,
            ' * ');
        $class = $array[0];
        $name = $array[1];

        include bs_lib::load_default_path();
    }


}
?>