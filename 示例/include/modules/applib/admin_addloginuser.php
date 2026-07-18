<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if (BSPHP_SET != 'ADMIN')
    die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class admin_addloginuser extends admin
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


    function call_add()
    {


        $daihao = set_post('daihao');

        $name = set_post('name');


        if ($daihao) {


            $sql = "SELECT * FROM`bs_php_pattern_login` where `L_User_uid` ='$name' and `L_daihao`='$daihao';";
            $AAAA = $this->db->Bsphp_my_array($sql);

            if ($AAAA) {
                $this->admin_alert('已经存在');
            } else {


            }


            bs_lib::load_modules_common('applib', 'appen_appuser');
            Login_add_key($name, $daihao, time(), '');

            $this->admin_alert('指令执行,到对应软件用户下查看即可');

        }


        //读取列表
        $sql = "SELECT`app_daihao`,`app_name`,`app_MoShi`FROM`bs_php_appinfo`;";
        $db_array_value = $this->db->Bsphp_my_query($sql);


        include bs_lib::load_default_path();
    }


    function call_caroff()
    {
        
        
        
        
        $textarea = set_post('textarea');
        $soso_ok = set_post('soso_ok');
        
        $arr = explode("\n",$textarea);
        if($soso_ok){
            
            
           foreach ($arr as $v) {
            $v = trim($v);
            
              $sql =  "UPDATE  `bs_php_cardseries` SET  `car_zhuangtai` =  '1' WHERE  `bs_php_cardseries`.`car_name` ='$v';";
              $this->db->Bsphp_my_query($sql);
            
	
           }
            
            alert("执行完毕");
            
            
        }
        
        
      
        
        
        
        


        include bs_lib::load_default_path();

    }
    
    function call_webapi()
    {
        
        
        
  
        
        


        include bs_lib::load_default_path();

    }

    function call_carinfo()
    {


        //读取列表
        $sql = "SELECT  `app_daihao` ,  `app_name` ,  `app_MoShi` FROM  `bs_php_appinfo` LIMIT 0 , 30";
        $db_array_value_app = $this->db->Bsphp_my_query($sql);



        //获取软件模式
        #$moshi_array = GetAppMoShiArray($app_array['app_MoShi']);
        
        
        $carsys_arr = bs_lib::load_langs_array('applib', 'admin_card');


        $daihao = set_get('daihao');


        $date1 = set_get('date1');
        $date2 = set_get('date2');
        $soso = set_get("soso");

        $soso_id = set_get("soso_id");

         if($soso_id==0){

             $sql = "SELECT * FROM  `bsphp_user`WHERE  `user_user` ='$soso'     LIMIT 1";
             $uids_arr = $this->db->Bsphp_my_array($sql);
             $soso_uid =$uids_arr['user_uid'];
         }else{
             $soso_uid = $soso;
         }


        if ($daihao > 0) {
            
            
            $app_array = get_appdaihao_info_array($daihao);


            $app_name = $app_array['app_name'];
            $app_daihao = $app_array['app_daihao'];



            if($soso!=''){
                $soso_sql = " and `car_admin` = '$soso_uid' ";
            }else{
                $soso_sql='';
            }

            /*开始枚举*/
            $sql = "SELECT *, COUNT( * ) AS  `row` ,  `car_Lei`
FROM  `bs_php_cardseries`

where `car_DaiHao` = '$app_daihao' AND `car_reDATE` > '$date1 00:00:00' AND `car_reDATE` < '$date2 24:00:00'  $soso_sql
GROUP BY  `car_Lei`
ORDER BY  `car_Lei`
LIMIT 1000";

            //echo $sql;
            $db_array_value = $this->db->Bsphp_my_query($sql);


        }
        
        



        include bs_lib::load_default_path();

    }
    function call_carinfob()
    {


        //读取列表
        $sql = "SELECT  `app_daihao` ,  `app_name` ,  `app_MoShi` FROM  `bs_php_appinfo` LIMIT 0 , 30";
        $db_array_value_app = $this->db->Bsphp_my_query($sql);



        //获取软件模式
        #$moshi_array = GetAppMoShiArray($app_array['app_MoShi']);


        $carsys_arr = bs_lib::load_langs_array('applib', 'admin_card');


        $daihao = set_get('daihao');


        $date1 = set_get('date1');
        $date2 = set_get('date2');
        $soso = set_get("soso");
        $soso2 = set_get("soso2");



        if ($daihao > 0) {


            $app_array = get_appdaihao_info_array($daihao);


            $app_name = $app_array['app_name'];
            $app_daihao = $app_array['app_daihao'];







            //echo $sql;



        }





        include bs_lib::load_default_path();

    }
}

?>