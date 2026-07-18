<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class admin_user extends admin
{

    private $rc, $db, $GLOBALS_LANGS,$user_str_log, $user;

    function __construct()
    {
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->user = bs_lib::load_modules_class('user', 'user');


        $this->GLOBALS_LANGS = bs_lib::load_langs_array();
        $this->user_str_log = bs_lib::load_langs_array('user','user_str_log');
        
        


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
                if ($this->db->my_in_delete('bs_php_user', 'user_uid', $all))
                {
                     $this->admin_alert( '你选择账号已经删除!');
                } else
                {
                    $this->admin_alert(  '删除失败,SQL执行失败,错误数据已保存!!');
                }

            } elseif ($select_class == 2)
            { //解除冻结
                if ($this->db->my_in_SETdata('bs_php_user', 'user_uid', $all, 'user_IsLock', '0'))
                {
                    $this->admin_alert(  '你选择账号已经解除冻结!');
                } else
                {
                    $this->admin_alert(  '解除冻结,SQL执行失败,错误数据已保存!!');
                }


            } elseif ($select_class == 3)
            { //冻结账号
                if ($this->db->my_in_SETdata('bs_php_user', 'user_uid', $all, 'user_IsLock', '1'))
                {
                   $this->admin_alert(  '你选择账号已经冻结账号!');
                } else
                {
                    $this->admin_alert(  '冻结账号,SQL执行失败,错误数据已保存!!');
                }


            } elseif ($select_class == 4)
            { //设为代理商
                if ($this->db->my_in_SETdata('bs_php_user', 'user_uid', $all, 'user_daili', '1'))
                {
                   $this->admin_alert(  '你选择账号已经设为代理商!');
                } else
                {
                  $this->admin_alert( '设为代理商,SQL执行失败,错误数据已保存!!');
                }


            } elseif ($select_class == 5)
            { //取消代理商
                if ($this->db->my_in_SETdata('bs_php_user', 'user_uid', $all, 'user_daili', '0'))
                {
                   $this->admin_alert(  '你选择账号已经取消代理商!');
                } else
                {
                   $this->admin_alert(  '取消代理商,SQL执行失败,错误数据已保存!!');
                }


            } else
            {
               $this->admin_alert( '你没有选择操作项目');
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
         * <option value="1" {$soso_id_arr[1]}>登陆账号</option>
         * <option value="2" {$soso_id_arr[2]} >用户UID</option>
         * <option value="3" {$soso_id_arr[3]}>代理商列表</option>
         * <option value="4" {$soso_id_arr[4]}>用户金额大于</option>
         * <option value="5" {$soso_id_arr[5]}>登录次数大于</option>
         * <option value="6" {$soso_id_arr[6]}>邀请人</option>
         * <option value="7" {$soso_id_arr[7]}>用户状态=&gt;冻结</option>
         * <option value="8" {$soso_id_arr[8]}>用户状态=&gt;正常</option>
         * <option value="9" {$soso_id_arr[8]}>用户QQ</option>
         * <option value="10" {$soso_id_arr[8]}>用户邮箱</option>
         * $soso_db_table = 要搜搜的字段
         */


        if ($soso_id == 2)
        {
            $soso_db_table = 'user_uid';

        } elseif ($soso_id == 3)
        {
            $soso_db_table = 'user_daili';

            $soso = '1';
        } elseif ($soso_id == 4)
        {
            $soso_db_table = 'user_Mobile';


        } elseif ($soso_id == 5)
        {
            $soso_db_table = 'user_Zhe';


        } elseif ($soso_id == 6)
        {
            $soso_db_table = 'user_yao_User';


        } elseif ($soso_id == 7)
        {
            $soso_db_table = 'user_IsLock';

            $soso = '1';
        } elseif ($soso_id == 8)
        {
            $soso_db_table = 'user_IsLock';

            $soso = '0';
        } elseif ($soso_id == 9)
        {
            $soso_db_table = 'user_qq';


        } elseif ($soso_id == 10)
        {
            $soso_db_table = 'user_email';


        } elseif ($soso_id == 11)
        {
            $soso_db_table = 'user_mibao_wenti';


        } elseif ($soso_id == 12)
        {
            $soso_db_table = 'user_mibao_daan';


        } elseif ($soso_id == 13)
        {
            $soso_db_table = 'user_Login_ip';


        } elseif ($soso_id == 14)
        {
            $soso_db_table = 'user_re_ip';


        } elseif ($soso_id == 15)
        { //整数
            $soso_db_table = 'user_rmb';


        } elseif ($soso_id == 16)
        { //整数
            $soso_db_table = 'user_LoGinNum';


        } elseif ($soso_id == 17)
        { //整数
            $soso_db_table = 'user_jifen';


        } else
        {
            $soso_db_table = 'user_user';


        }


        if ($soso_id == 15 || $soso_id == 16 || $soso_id == 17)
        {

            $sql = "SELECT*FROM`bs_php_user`WHERE`$soso_db_table`>='$soso' ORDER BY`user_uid`$DESC LIMIT $db_ID,$shu";
            $sql_rows = "SELECT*FROM`bs_php_user`WHERE`$soso_db_table`>='$soso' ORDER BY`user_uid`$DESC";
        } else
        {

            $sql = "SELECT*FROM`bs_php_user`WHERE`$soso_db_table`LIKE CONVERT( _utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  ORDER BY`user_uid`$DESC LIMIT $db_ID,$shu";

            $sql_rows = "SELECT*FROM`bs_php_user`WHERE`$soso_db_table`LIKE CONVERT( _utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  ORDER BY`user_uid`$DESC";


        }

        /*开始枚举*/
        $db_array_value = $this->db->Bsphp_my_query($sql);


        /**
         * @获取条数
         */
        $tmp = $this->db->Bsphp_my_query($sql_rows);
        $zongshu = $this->db->my_rows($tmp);

       /*翻页URL*/
       $url = "index.php?soso_id={$soso_id}&soso={$soso}&DESC={$DESC_id}&soso_ok=%E6%90%9C%E7%B4%A2&m={$_GET['m']}&c={$_GET['c']}&a={$_GET['a']}&t={$_GET['t']}&id=";


        include bs_lib::load_default_path();
    }


    function call_add()
    {


        $baocun = set_post('baocun');
        if ($baocun)
        {
            $user = set_post('user');
            $pwd = set_post('pwd');
            $qq = set_post('qq');
            $mail = set_post('mail');
            $yao_user = set_post('yao_user');

            $log = $this->user->UserAdd($user, $pwd, $pwd, $qq, $mail, $yao_user);
            alert($this->user_str_log[$log]);


           
        }
     include bs_lib::load_default_path();

    }


    function call_ubs()
    {


        $array = $this->db->my_class_array('bs_php_user', 'user_uid', $id, ' * ');

        $baocun = set_post('baocun');
        if ($baocun)
        {
            $user_uid = set_post('user_uid');
            $user_user = set_post('user_user');
            $user_pwd = set_post('user_pwd');
            $user_jifen = set_post('user_jifen');
            $user_rmb = set_post('user_rmb');
            $user_mibao_wenti = set_post('user_mibao_wenti');
            $user_mibao_daan = set_post('user_mibao_daan');
            $user_IsLock = set_post('user_IsLock');
            $user_Zhe = set_post('user_Zhe');
            $user_qq = set_post('user_qq');
            $user_email = set_post('user_email');
            $user_mail_ok = set_post('user_mail_ok');
            if ($user_mail_ok == '')
                $user_mail_ok = '0'; //数据库语句严格就需要
            $user_yao_User = set_post('user_yao_User');
            $user_yao_Shu = set_post('user_yao_Shu');
            $user_Mobile = set_post('user_Mobile');
            $user_daili = set_post('user_daili');
            if ($user_daili == '')
                $user_daili = '0';

            /**
             * 密码修改
             * 
             * 如果密码不修改就设置密码默认
             */
            if ($user_pwd != '不修改留空忽略')
            {
                $user_pwd = md6($user_pwd);
                $pdw_SQL = "`user_pwd`='$user_pwd',";
            } else
            {
                $pdw_SQL = '';
            }

            $sql = "UPDATE`bs_php_user`SET`user_user`='$user_user',$pdw_SQL`user_IsLock`='$user_IsLock',`user_email`='$user_email',`user_mail_ok`='$user_mail_ok',`user_qq`='$user_qq',`user_Mobile`='$user_Mobile',`user_mibao_wenti`='$user_mibao_wenti',`user_mibao_daan`='$user_mibao_daan',`user_rmb`='$user_rmb',`user_jifen`='$user_jifen',`user_yao_User`='$user_yao_User',`user_yao_Shu`='$user_yao_Shu',`user_Zhe`='$user_Zhe',`user_daili`='$user_daili'WHERE`bs_php_user`.`user_uid`='$user_uid';";
            $tmp = $this->db->Bsphp_my_query($sql);
            if ($tmp)
            {
                alert('保存成功.');
            } else
            {
                alert('信息保存失败.');
            }

        }


        if ($array['user_mail_ok'] != 0)
        {
            $user_mail_ok_ = 'checked';
        } else
        {
            $user_mail_ok_ = '';
        }


        //print_R($array);
        //selected
        $arr_user_IsLock = array(0 => '', 1 => '');
        if ($array['user_IsLock'] != 0)
        {
            $arr_user_IsLock[1] = 'selected';
        } else
        {
            $arr_user_IsLock[0] = '';
        }

        if ($array['user_daili'] == 1)
        {
            $checked_daili = 'checked';
        } else
        {
            $checked_daili = '';
        }


        include bs_lib::load_default_path();
    }






function call_modify(){
    
    $id=set_get('id');
    $array=$this->db->my_class_array('bs_php_user','user_uid',$id,' * ');
    
    $baocun=set_post('baocun');
    if($baocun){
        $user_uid=set_post('user_uid');
        $user_user=set_post('user_user');
        $user_pwd=set_post('user_pwd');
        $user_jifen=set_post('user_jifen');
        $user_rmb=set_post('user_rmb');
        $user_mibao_wenti=set_post('user_mibao_wenti');
        $user_mibao_daan=set_post('user_mibao_daan');
        $user_IsLock=set_post('user_IsLock');
        $user_Zhe=set_post('user_Zhe');
        $user_qq=set_post('user_qq');
        $user_email=set_post('user_email');
        $user_mail_ok=set_post('user_mail_ok');
		if($user_mail_ok=='') $user_mail_ok='0';  //数据库语句严格就需要
        $user_yao_User=set_post('user_yao_User');
        $user_yao_Shu=set_post('user_yao_Shu');
        $user_Mobile=set_post('user_Mobile');  
        $user_daili=set_post('user_daili');
        if($user_daili=='') $user_daili='0';
        
        /**
         * 密码修改
         * 
         * 如果密码不修改就设置密码默认
         */
        if($user_pwd!='不修改留空忽略'){
           $user_pwd= md6($user_pwd);
           $pdw_SQL="`user_pwd`='$user_pwd',";
        }else{
            $pdw_SQL='';
        }

       $sql = "UPDATE`bs_php_user`SET`user_user`='$user_user',$pdw_SQL`user_IsLock`='$user_IsLock',`user_email`='$user_email',`user_mail_ok`='$user_mail_ok',`user_qq`='$user_qq',`user_Mobile`='$user_Mobile',`user_mibao_wenti`='$user_mibao_wenti',`user_mibao_daan`='$user_mibao_daan',`user_rmb`='$user_rmb',`user_jifen`='$user_jifen',`user_yao_User`='$user_yao_User',`user_yao_Shu`='$user_yao_Shu',`user_Zhe`='$user_Zhe',`user_daili`='$user_daili'WHERE`bs_php_user`.`user_uid`='$user_uid';";
       $tmp=$this->db->Bsphp_my_query($sql); 
       if($tmp){
        alert('保存成功.');
       }else{
        alert('信息保存失败.');
       }
       
    }
    

    if($array['user_mail_ok']!=0){
        $user_mail_ok_='checked';
    }else{
        $user_mail_ok_='';
    }
    
    
    //print_R($array);
    //selected
    $arr_user_IsLock=array(0=>'',1=>'');
    if($array['user_IsLock']!=0){
        $arr_user_IsLock[1]='selected';
    }else{
        $arr_user_IsLock[0]='';
    }

    if($array['user_daili']==1){
        $checked_daili='checked';
    }else{
        $checked_daili='';
    }
    
    
    $array['user_re_ip']=getip($array['user_re_ip']);
    $array['user_Login_ip']=getip($array['user_Login_ip']);
    
    
	 include bs_lib::load_default_path();
}






}



?>