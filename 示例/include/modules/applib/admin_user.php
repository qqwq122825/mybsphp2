<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if (BSPHP_SET != 'ADMIN')
    die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class admin_user extends admin
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
     * @控制面板-软件列表
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


        //获取软件模式
        $moshi_array = GetAppMoShiArray($app_array['app_MoShi']);


        /**
         * 下拉框批量操作
         */
        $Submit_class = set_post('baocun');
        $all = set_post('all');
        $select_class = set_post('select_class');
        if ($Submit_class)
        {
            if ($select_class == 1)
            { //删除
                if ($this->db->my_in_delete('bs_php_pattern_login', 'L_id', $all))
                {
                    $this->admin_alert('删除成功!');
                } else
                {
                    $this->admin_alert('删除失败,SQL执行失败,错误数据已保存!!');
                }

            } elseif ($select_class == 2)
            {

                location('index.php?m=applib&c=admin_card_weihu&a=loginterm&allid=' . $all);

            } else
            {
                $this->admin_alert('你没有选择操作项目');
            }


        } //按钮接受


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
         * 
         *      <option value="1">用户名</option>
         * <option value="2">UID</option>
         * <option value="3">是否到期=&gt;到期</option>
         * <option value="4">是否到期=&gt;在用</option>
         * <option value="5">绑定特征</option>
         * <option value="6">添加时间</option>
         * 
         * 
         */


        /**
         * @定义搜搜字段
         * 
         * $soso_db_table = 要搜搜的字段
         */


        if ($soso_id == 2)
        {

            $soso_db_table = 'L_ic_name';


        } elseif ($soso_id == 3)
        {
            
            if($moshi_array['card']=='pint'){
               $soso_db_table = '`L_vip_unix` < 0 '; 
            }else{
               $soso_db_table = '`L_vip_unix` < \'' . HOST_UNIX . '\''; 
            }
            
            

            $soso = '0';

        } elseif ($soso_id == 4)
        {
            
            if($moshi_array['card']=='pint'){
               $soso_db_table = '`L_vip_unix` > 1 '; 
            }else{
                $soso_db_table = '`L_vip_unix` > \'' . HOST_UNIX . '\'';
            }
            
            
           

            $soso = '1';


        } elseif ($soso_id == 5)
        {
            $soso_db_table = 'L_key_info';


        } elseif ($soso_id == 6)
        {
            $soso_db_table = 'L_key_info';


        } else
        {
            $soso_db_table = 'L_User_uid';


        }


        /**
         * SELECT user_uid , user_user
         * FROM bs_php_pattern_login, bs_php_user
         * where bs_php_pattern_login.L_id=bs_php_user.user_uid

         */


        /*开始枚举 '%sd%'*/
        //        if ($soso_id == 3 | $soso_id == 4)
        //        {
        //            $sql = "SELECT `bs_php_pattern_login`.*,`bs_php_user`.`user_user`  FROM`bs_php_pattern_login`,`bs_php_user` WHERE `bs_php_pattern_login`.`L_User_uid`=`bs_php_user`.`user_uid` AND $soso_db_table AND`L_daihao`='$daihao'  ORDER BY`L_id`$DESC LIMIT $db_ID,$shu";
        //            $sql_rows = "SELECT `bs_php_pattern_login`.*,`bs_php_user`.`user_user`  FROM`bs_php_pattern_login`,`bs_php_user` WHERE `bs_php_pattern_login`.`L_User_uid`=`bs_php_user`.`user_uid` AND $soso_db_table AND`L_daihao`='$daihao'  ORDER BY`L_id`$DESC ";
        //
        //
        //        } else
        //        {
        //
        //            $sql = "SELECT `bs_php_pattern_login`.*,`bs_php_user`.`user_user`  FROM`bs_php_pattern_login`,`bs_php_user` WHERE `bs_php_pattern_login`.`L_User_uid`=`bs_php_user`.`user_uid` AND `$soso_db_table`LIKE CONVERT(_utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  AND`L_daihao`='$daihao' ORDER BY`L_id`$DESC LIMIT $db_ID,$shu";
        //            $sql_rows = "SELECT `bs_php_pattern_login`.*,`bs_php_user`.`user_user`  FROM`bs_php_pattern_login`,`bs_php_user` WHERE `bs_php_pattern_login`.`L_User_uid`=`bs_php_user`.`user_uid` AND `$soso_db_table`LIKE CONVERT(_utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  AND`L_daihao`='$daihao' ORDER BY`L_id`$DESC ";
        //
        //
        //        }
        //


        if ($soso_id == 3 | $soso_id == 4)
        {
            $sql = "SELECT * FROM`bs_php_pattern_login` WHERE  $soso_db_table AND`L_daihao`='$daihao'  ORDER BY`L_id`$DESC LIMIT $db_ID,$shu";
            $sql_rows = "SELECT * FROM`bs_php_pattern_login` WHERE  $soso_db_table AND`L_daihao`='$daihao'  ORDER BY`L_id`$DESC ";


        } else
        {

            $sql = "SELECT * FROM`bs_php_pattern_login` WHERE  `$soso_db_table`LIKE CONVERT(_utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  AND`L_daihao`='$daihao' ORDER BY`L_id`$DESC LIMIT $db_ID,$shu";
            $sql_rows = "SELECT *  FROM`bs_php_pattern_login` WHERE `$soso_db_table`LIKE CONVERT(_utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  AND`L_daihao`='$daihao' ORDER BY`L_id`$DESC ";


        }


        $db_array_value = $this->db->Bsphp_my_query($sql);


        /**
         * @获取条数
         */
        $tmp = $this->db->Bsphp_my_query($sql_rows);
        $zongshu = $this->db->my_rows($tmp);

        /*翻页URL*/
        $url = "index.php?soso_id={$soso_id}&soso={$soso}&DESC={$DESC_id}&soso_ok=%E6%90%9C%E7%B4%A2&m={$_GET['m']}&c={$_GET['c']}&a={$_GET['a']}&t={$_GET['t']}&daihao={$app_daihao}&id=";


        //-------------------------------------------------------------------------------

        /*******获取用户分组*****/
        $sql = "SELECT*FROM`bs_php_userclass`WHERE`class_daihao`='$daihao'";
        $dbs_array_value = $this->db->Bsphp_my_query($sql);


        $class_array[0] = '未分组';
        if ($dbs_array_value)
        {
            while ($array_value = mysqli_fetch_array($dbs_array_value))
            {
                $class_array[$array_value[0]] = $array_value[1];
            }
        }

        //-------------------------------------------------------------------------------


        include bs_lib::load_default_path('admin_user/' . $moshi_array['templates'] . '/table');
    }


    /**
     * @控制面板-软件配置
     * 
     */
    function call_modify()
    {

        $id = set_get('id');
        $daihao = set_get('daihao');


        $app_array = get_appdaihao_info_array($daihao);
        if (!$app_array)
            alert('软件不存在!');

        $app_name = $app_array['app_name'];
        $app_daihao = $app_array['app_daihao'];


        //获取软件模式
        $moshi_array = GetAppMoShiArray($app_array['app_MoShi']);


        $leiadd = set_post('leiadd');

        if ($leiadd)
        {
            $tid = set_post('tid');
            $date = set_post('date');
            $key = set_post('key');
            $class_id = set_post('class_id');
            
            
            $L_links_open = (int)set_post('L_links_open');
            $L_links = (int)set_post('L_links');
            
            $L_beizhu = set_post('L_beizhu');


            //转换时间格式
            // if ($app_moshi == 3574)
           
            if ($moshi_array['card'] == 'pint') //card
            {

                //$array['L_vip_unix'] = date('Y-m-d H:i:s', $array['L_vip_unix']);

            } else
            {

                 $date = (int)strtotime((string )$date);

            }

            $sql = "UPDATE`bs_php_pattern_login`SET`L_beizhu`='$L_beizhu',`L_links_open`='$L_links_open',`L_links`='$L_links',`L_class`='$class_id',`L_vip_unix`='$date',`L_key_info`='$key'WHERE`bs_php_pattern_login`.`L_id` ='$tid';";
            $tmp = $this->db->Bsphp_my_query($sql);
            if ($tmp)
            {
                $atname = '保存成功';
            } else
            {
                $atname = '保存失败';
            }


        }


        /**
         * 读取内容
         */

        $array = $this->db->my_class_array('bs_php_pattern_login', 'L_id', $id, ' * ');


        //转换时间格式
        //if ($app_moshi == 3574)

        //print_R($moshi_array);

        if ($moshi_array['card'] == 'pint') //card
        {

            //$array['L_vip_unix'] = date('Y-m-d H:i:s', $array['L_vip_unix']);

        } else
        {

            $array['L_vip_unix'] = date('Y-m-d H:i:s', $array['L_vip_unix']);

        }


        include bs_lib::load_default_path('admin_user/' . $moshi_array['templates'] . '/modify');
    }


}

?>