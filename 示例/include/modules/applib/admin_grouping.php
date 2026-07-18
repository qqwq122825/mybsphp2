<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class admin_grouping extends admin
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
        $this->COOKIES_KEY('D');
        
    }





    /**
     * @控制面板
     * 
     */
    function call_table()
    {


        $daihao = set_get('daihao');
        $app_array = get_appdaihao_info_array($daihao);
        if (!$app_array)
            alert('软件不存在!');

        $app_name = $app_array['app_name'];
        $app_daihao = $app_array['app_daihao'];
         /*******查询分类列表*****/
        $sql = "SELECT*FROM`bs_php_userclass`WHERE`class_daihao`='$daihao'";
        $db_array_value = $this->db->Bsphp_my_query($sql);


        /**
         * 删除
         */
        $delete = set_get('delete');
        if ($delete)
        {
            $tmp = $this->db->my_in_delete('bs_php_userclass', 'class_id', $delete);
            if ($tmp)
            {
                alert('分类已经删除!');
            } else
            {
                alert('删除失败,错误已经记录');
            }
        }


        include bs_lib::load_default_path();
    }


    /**
     * @控制面板
     * 
     */
    function call_add()
    {


        $daihao = set_get('daihao');


        $userclass_mark = set_post('userclass_mark');
        $userclass_name = set_post('userclass_name');
        $leiadd = set_post('leiadd');


        $app_array = get_appdaihao_info_array($daihao);
        if (!$app_array)
            alert('软件不存在!');

        $app_name = $app_array['app_name'];
        $app_daihao = $app_array['app_daihao'];


        if ($leiadd)
        {
            if ($userclass_mark == '' or $userclass_name == '')
            {
                alert2('请输分类名或者名称');
            } else
            {
                $sql = "INSERT INTO `bs_php_userclass` (`class_id`, `class_name`, `calss_mark`, `class_daihao`) VALUES (NULL, '$userclass_name', '$userclass_mark', '$app_daihao');";
                $tmp = $this->db->Bsphp_my_query($sql);
                if ($tmp)
                {
                    alert('新分组添加成功!');
                } else
                {
                    alert('添加失败,错误已经保存!');
                }
            }


        }


        include bs_lib::load_default_path();
    }


    /**
     * @控制面板
     * 
     */
    function call_modify()
    {


        $daihao = set_get('daihao');
        $id = set_get('id');
        $userclass_id = set_post('userclass_id');
        $userclass_mark = set_post('userclass_mark');
        $userclass_name = set_post('userclass_name');
        $leiadd = set_post('leiadd');


        $app_array = get_appdaihao_info_array($daihao);
        if (!$app_array)
            alert('软件不存在!');

        $app_name = $app_array['app_name'];
        $app_daihao = $app_array['app_daihao'];


        if ($leiadd)
        {
            $sql = "UPDATE`bs_php_userclass`SET`calss_mark`='$userclass_mark',`class_name`='$userclass_name' WHERE `class_id`='$userclass_id';";
            //DIE($sql);
            $tmp = $this->db->Bsphp_my_query($sql);
            if ($tmp)
            {
                alert('分组已经修改成功!');
            } else
            {
                alert('分组修改失败,错误已经记录');
            }
        }


        /************读取修改属性记录***********/
        $array = $this->db->my_class_array('bs_php_userclass', 'class_id', $id, ' * ');


        include bs_lib::load_default_path();
    }

}

?>