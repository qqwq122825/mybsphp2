<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if (BSPHP_SET != 'ADMIN')
    die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');

/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class main extends admin
{

    private $rc, $db, $GLOBALS_LANGS, $session;

    function __construct()
    {


        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');


        $this->GLOBALS_LANGS = bs_lib::load_langs_array();


        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();
        // echo '控制器  继承初始化1<BR/>';


        //后台权限验证
        $this->COOKIES_KEY('A');


    }


    /**
     * @控制面板
     * 
     */
    function call_index()
    {
        
        
        
        //设置数据库长度
        #$sql="ALTER TABLE `bs_php_user` CHANGE `user_yao_User` `user_yao_User` CHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL";
        #$this->db->Bsphp_my_query($sql);
        
        //读取导航
        $sql = "SELECT `list_admin_list_name` ,`list_admin_list_top_tree` FROM `bs_php_list_admin` WHERE`list_admin_list_top`=1 AND `list_admin_show`=1;";
        $listrc = $this->db->Bsphp_my_query($sql);
        
        //UPDATE `bsphp_pattern_login`,`bsphp_user` SET `bsphp_pattern_login`.`L_ic_name` = `bsphp_user`.`user_user` WHERE `bsphp_pattern_login`.`L_User_uid` =`bsphp_user`.`user_uid`;

        //版本用户名显示
        $sql="UPDATE `bs_php_pattern_login`,`bs_php_user` SET `bs_php_pattern_login`.`L_ic_name` = `bs_php_user`.`user_user` WHERE `bs_php_pattern_login`.`L_User_uid` =`bs_php_user`.`user_uid`;";
        $this->db->Bsphp_my_query($sql);
        
        
        //更新
        #$this->db->Bsphp_my_query("ALTER TABLE`bs_php_kalei`ADD`lei_money` FLOAT( 9, 2)NOT NULL AFTER`lei_for_oid` ");
        #$this->db->Bsphp_my_query("ALTER TABLE`bs_php_cardseries`ADD`car_money` FLOAT( 9, 2)NOT NULL AFTER`car_div` ");
        

        //  echo mysql::my_rc_rows();
        $this->db->Bsphp_my_query("UPDATE `bs_php_list_admin`SET`list_admin_show`='1'");
        
        
        
        //删除7天前日志
        #$date=HOST_UNIX - 864000;
        #$this->db->Bsphp_my_query("DELETE FROM`bs_php_log` WHERE `date`<'$date'");
        
      
        //ECHO $sql;
       $this->db->Bsphp_my_query("DELETE FROM `bs_php_links_session` WHERE `links_out_time` < '" . HOST_UNIX . "' AND `links_set` = '-1';");
        
        
        

        //       while ($row = mysqli_fetch_array( bs_index::$rc,MYSQL_ASSOC)) {
        //             echo $row['list_admin_list_name'];
        //
        // }

        $admin_uid = get_session_value('ADMIN_UID');
        $admin_global_array = $this->db->Bsphp_my_array("SELECT * FROM`bs_php_admin`WHERE`Admin_ID`='$admin_uid'");


        //插件

        //打开目录
        $fis_Url = BSPHP_DIR_SYS.'Plug/Admin_List';
        $while_id = null;
        $html = null;
        $html_txt = null;
        $handle = opendir($fis_Url); //OPEN

        while ($FileName = readdir($handle)) {
            if (strstr($FileName, 'class_')) {
                $while_id++;
                $html = file_get_contents($fis_Url . '/' . $FileName);
                $html = strtr($html, '@id@', $while_id);


                $html_txt .= $html;

            }

        }
        //关闭目录

        //授权参数接收函数
        //$db->my_file_array($handle,$SET_CON); //@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ 授权写入
        //closedir($handle);


        if ($html_txt == null) {

            $JS = "
<script language=\"javascript\">
var bsphp_chajiang={
	
  'cj_' : {
			'cj_1' : ['没有插件哦','http://www.bsphp.com','']
			
			}
}
</script>
";


        } else {

            $JS = "
<script language=\"javascript\">
var bsphp_chajiang={
	
  'cj_' : {
			$html_txt
			
			}
}
</script>
";


        }






        include bs_lib::load_default_path();
    }


}

?>