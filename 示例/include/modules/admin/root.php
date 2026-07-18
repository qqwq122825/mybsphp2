<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');

/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class root extends admin
{

    private $rc, $db, $GLOBALS_LANGS,$admin_global_array,$ip,$session;

    function __construct()
    {
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        
        $this->GLOBALS_LANGS = bs_lib::load_langs_array();


        $this->ip = bs_lib::load_libbsphp_class('ip','ip');


        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();
        // echo '控制器  继承初始化1<BR/>';
        
        
        //后台权限验证
        $this->COOKIES_KEY('B');
        
        
        
        $admin_uid = get_session_value('ADMIN_UID');
        $this->admin_global_array = $this->db->Bsphp_my_array("SELECT * FROM`bs_php_admin`WHERE`Admin_ID`='$admin_uid'");

        
    }


    /**
     * @管理员列表
     * 
     */
    function call_table()
    {


        /**
         * 下拉框批量操作
         */
        $Submit_class = set_post('baocun');
        $all = set_post('all');
        $select_class = set_post('select_class');
        if ($Submit_class)
        {
            if ($select_class == 1)
            {
                if ($this->db->my_in_delete('bs_php_admin', 'Admin_ID', $all))
                {
                    $this->admin_alert('你选择账号已经删除!');
                } else
                {
                   $this->admin_alert( '删除失败,SQL执行失败,错误数据已保存!!');
                }

            } elseif ($select_class == 2)
            { //解除冻结
                if ($this->db->my_in_SETdata('bs_php_admin', 'Admin_ID', $all, 'Admin_IsLock', '0'))
                {
                    $this->admin_alert('你选择账号已经解除冻结!');
                } else
                {
                   $this->admin_alert( '解除冻结,SQL执行失败,错误数据已保存!!');
                }


            } elseif ($select_class == 3)
            { //冻结账号
                if ($this->db->my_in_SETdata('bs_php_admin', 'Admin_ID', $all, 'Admin_IsLock', '1'))
                {
                   $this->admin_alert( '你选择账号已经冻结账号!');
                } else
                {
                   $this->admin_alert( '冻结账号,SQL执行失败,错误数据已保存!!');
                }


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
         * @定义搜搜字段
         * 
         * $soso_db_table = 要搜搜的字段
         */
        $soso_db_table = 'Admin_AdminUserName';


        /*开始枚举*/
        $sql = "SELECT*FROM`bs_php_admin`WHERE`$soso_db_table`LIKE CONVERT( _utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  ORDER BY`Admin_ID`$DESC LIMIT $db_ID,$shu";
        $db_array_value = $this->db->Bsphp_my_query($sql);


        /**
         * @获取条数
         */
        $sql_rows = "SELECT*FROM`bs_php_admin`WHERE`$soso_db_table`LIKE CONVERT( _utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  ORDER BY`Admin_ID`$DESC";
        $tmp = $this->db->Bsphp_my_query($sql_rows);
        $zongshu = $this->db->my_rows($tmp);


       /*翻页URL*/
       $url = "index.php?soso_id={$soso_id}&soso={$soso}&DESC={$DESC_id}&soso_ok=%E6%90%9C%E7%B4%A2&m={$_GET['m']}&c={$_GET['c']}&a={$_GET['a']}&t={$_GET['t']}&id=";





        include bs_lib::load_default_path();
    }
    
    
    
    
    

    /**
     * @修改管理员账号
     * 
     */
    function call_usb()
    {
        
        
       /**
        * 接收修改参数
        */
       $uesr = set_post('user');
       $pass = set_post('pass');
       $pass_key = set_post('pass_key');
       
       
       $A=set_post('A');
       $B=set_post('B');
       $C=set_post('C');
       $D=set_post('D');
       $E=set_post('E');
       $F=set_post('F');
       $P=set_post('P');
       $quan = $A.','.$B.','.$C.','.$D.','.$E.','.$F.','.$P;
       
       $id=set_get('id');
       
       
       
       
       /**
        * @判断修改格式是否正确范围
        */
       empty($uesr) ? $at_a = '请输入添加账号 3-15位数' : $at_a = null;
       empty($pass) ? $at_b = '请输入登录密码 5-15位数' : $at_b = null;
       empty($pass_key) ? $at_c = '请输入安全码 1-15位数' : $at_c = null;
       if ($uesr != '' & $pass != '' & $pass_key != '') {
        $pwd_sql='';
        if($pass!='不修改留空'){
            $pass = $this->md8($pass);
            $pwd_sql="`Admin_AdminPassWord`='$pass',";

        }

        $sql="UPDATE`bs_php_admin`SET`Admin_AdminUserName`='$uesr',$pwd_sql`Admin_MiBao`='$pass_key',`Admin_Permission`='$quan'WHERE`bs_php_admin`.`Admin_ID`='$id';";
            if ($this->db->Bsphp_my_query($sql)) alert( '修改成功!');
            else  alert( '修改失败,SQL语句执行失败,已记录错误SQL!');
       }
    
    
    
    
    
    
    
    
    $array=$this->db->my_class_array('bs_php_admin','Admin_ID',$id,' * ');
    $checkedarray=$this->IsLockfunction($array[9]);

        
        
      include bs_lib::load_default_path();  
    }

    
    
    /**
     * @添加管理员账号
     * 
     */
    function call_add()
    {
        
       /**
        * 接收修改参数
        */
       $uesr = set_post('user');
       $pass = set_post('pass');
       $pass_key = set_post('pass_key');
       
       $A=set_post('A');
       $B=set_post('B');
       $C=set_post('C');
       $D=set_post('D');
       $E=set_post('E');
       $F=set_post('F');
       $P=set_post('P');
       $all = $A.','.$B.','.$C.','.$D.','.$E.','.$F.','.$P;
       
       
       
       
       
       
       
       
       /**
        * @判断修改格式是否正确范围
        */
       empty($uesr) ? $at_a = '请输入添加账号 3-15位数' : $at_a = null;
       empty($pass) ? $at_b = '请输入登录密码 5-15位数' : $at_b = null;
       empty($pass_key) ? $at_c = '请输入安全码 1-15位数' : $at_c = null;
       if ($uesr != '' & $pass != '' & $pass_key != '') {
            $pass = $this->md8($pass);
            $sql = "INSERT INTO`bs_php_admin`(`Admin_ID`, `Admin_AdminUserName`, `Admin_AdminPassWord`, `Admin_MiBao`, `Admin_CaoShi`, `Admin_LoGinIP`, `Admin_LoGinNum`, `Admin_LoGDaTe`, `Admin_IsLock`, `Admin_Permission`) VALUES(NULL,'$uesr','$pass','$pass_key','0',NULL,'0',NULL ,'0','$all');";
            if ($this->db->Bsphp_my_query($sql)) alert( '管理员添加成功!');
            else  alert( '添加失败,SQL语句执行失败,已记录错误SQL!');
       }
       
    $checkedarray=$this->IsLockfunction($all);
        include bs_lib::load_default_path();  
    }
    


    /**
     * @checked处理函数
     */
    function IsLockfunction($IsLock)
    {


        /**
         * @checked是否选择
         */
        $checkedarray = array(
            'A' => '',
            'B' => '',
            'C' => '',
            'D' => '',
            'E' => '',
            'F' => '',
            'P' => '',
            'G' => '');


        $arr = explode(',', $IsLock);


        $i = 0;
        $count = count($arr);
        while ($i < $count):


            if ($arr[$i] == 'A')
            {

                $checkedarray['A'] = 'checked';
                $checkedarray['G'] .= '进入后台权限,';

            } elseif ($arr[$i] == 'B')
            {

                $checkedarray['B'] = 'checked';
                $checkedarray['G'] .= '系统,';

            } elseif ($arr[$i] == 'C')
            {

                $checkedarray['C'] = 'checked';
                $checkedarray['G'] .= '用户,';

            } elseif ($arr[$i] == 'D')
            {

                $checkedarray['D'] = 'checked';
                $checkedarray['G'] .= '软件,';

            } elseif ($arr[$i] == 'E')
            {

                $checkedarray['E'] = 'checked';
                $checkedarray['G'] .= '前台,';

            } elseif ($arr[$i] == 'F')
            {

                $checkedarray['F'] = 'checked';
                $checkedarray['G'] .= '工具,';

            } elseif ($arr[$i] == 'P')
            {

                $checkedarray['P'] = 'checked';
                $checkedarray['G'] .= '插件';

            }


            $i++;
        endwhile;


        return ($checkedarray);
    }


}

?>