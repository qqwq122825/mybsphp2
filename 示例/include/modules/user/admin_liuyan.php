<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class admin_liuyan extends admin
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
        $this->COOKIES_KEY('C');
    }


    /**
     * @控制面板
     * 
     */
    function call_table()
    {


        $app_name = GetAppInfoNameArray();


        /**
         * 下拉框批量操作
         */
        $Submit_class = set_post('Submit_class');
        $all = set_post('all');
        $select_class = set_post('select_class');
        if ($select_class)
        {

            if ($all == '')
                $this->admin_alert('没有选择记录条目！');

            if ($select_class == 1)
            { //删除账号
                if ($this->db->my_in_delete('bs_php_add', 'id', $all))
                {
                    $this->admin_alert('选择留言已经删除!');
                } else
                {
                    $this->admin_alert('删除失败,SQL执行失败,错误数据已保存!!');
                }

            } else
            {
                $this->admin_alert('你没有选择操作项目');
            }

        } //按钮点击接受


        /*翻页参数初始化*/


        /*系统翻页*/
        $FANYE = (int)set_get('id');


        if ($FANYE > 0)
        {
            $db_ID = $FANYE - 1; //防止注入
        } else
        {
            $db_ID = 0;
            $FANYE = 1;
        }


        $shu = 20; //每页显示
        $db_ID = $db_ID * $shu;


        /**
         * @接收搜搜参数
         * 
         * 这里表显示以搜搜模式来显示
         */
        /*取得搜搜内容*/
        $soso = set_get('soso');
        $soso_id = (int)set_get('soso_id');
        $DESC_id = (int)set_get('DESC');
        $soso_id > 0 ? $soso_id_show = $soso_id - 1 : $soso_id_show = 0;
        $DESC_id == 1 ? $DESC = 'ASC' : $DESC = 'DESC';


        /**
         * @定义搜搜字段
         * 
         * $soso_db_table = 要搜搜的字段
         */
        $soso_db_table = 'add_leix';


        /*开始枚举*/
        $sql = "SELECT*FROM`bs_php_add`WHERE`$soso_db_table`LIKE CONVERT( _utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  ORDER BY`id`$DESC LIMIT $db_ID,$shu";
        $db_array_value = $this->db->Bsphp_my_query($sql);


        /*开始枚举*/
        $sql_rows = "SELECT*FROM`bs_php_add`WHERE`$soso_db_table`LIKE CONVERT( _utf8'%$soso%'USING utf8)COLLATE utf8_general_ci ORDER BY`id`$DESC";

        /**
         * @获取条数
         */
        $tmp = $this->db->Bsphp_my_query($sql_rows);
        $zongshu = $this->db->my_rows($tmp);


        /*翻页URL*/
        $url = "index.php?soso_id={$soso_id}&soso={$soso}&DESC={$DESC_id}&soso_ok=%E6%90%9C%E7%B4%A2&m={$_GET['m']}&c={$_GET['c']}&a={$_GET['a']}&t={$_GET['t']}&id=";


        include bs_lib::load_default_path();
    }


}

?>