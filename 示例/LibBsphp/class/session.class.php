<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
/**
 * @session 操作
 * 文件名称:session.calss.php
 * 功能:session 操作类
 * 时间:2015.1.18
 * 官网:www.bsphp.com
 * 
 * 载入位置:session.class.php
 * 
 * 
 * 在线用户列表功能块
 */


/**
 * @MYSQL数据库类
 * 
 */
class session
{

    //ID
    public $session_id, $db;
    public $bs_set; //类型 管理员 前台用户 CMS游客 APP用户 常量值:BSPHP_SET
    public $userid; //用户UID
    public $username; //用户名
    public $app_id; //软件代号
    public $key; //绑定特征 - 软件绑定特征
    public $login_key; //APP 掉线重新登陆使用session标记  这里标记经过做重防重复处理
    public $session; //session 名称ID 这里可以重复明
    public $addtime; //添加时间  第一次添加也会添加在超时
    public $out_time; // 超时时间 用用户系统配置时间差做判断
    public $ip; //添加IP
    public $daihao; //软件代号


    function __construct()
    {

        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');


    }


    /** 开始 open*/
    function session_open()
    {

        //        ini_set('session.entropy_length', 128);//设置名称字符长度
        //        ini_set('session.hash_bits_per_character', 6);//设置名称字符
        //        ini_set('session.save_handler', 'user');
        //        ini_set('session.use_cookies', 0);
        //        session_save_path(BSPHP_DIR_SYS . 'Data/session');

        if (BSPHP_SET == 'INDEX' or BSPHP_SET == 'NOTMODE')
        {
            $BSPHP_SETS = 'USER';
        } else
        {
            $BSPHP_SETS = BSPHP_SET;

        }


        if (defined('CONST_SESION'))
        {

            //$this->session_id = session_id();


        } else
        {


            $session_name = bs_lib::get_configs_value("sys", "cookie_pre") . 'BSphpSeSsL_' . strtolower($BSPHP_SETS);

            isset($_COOKIE[$session_name]) ? $COOKIES_session_id = $_COOKIE[$session_name] : $COOKIES_session_id = 'sslid-' . strtolower($BSPHP_SETS) . '-' . MD5(TIME() . rand(1000, 9999) . TIME() . rand(1000,
                9999));

            // isset($_COOKIE[$session_name]) ? $COOKIES_session_id = $_COOKIE[$session_name] : $COOKIES_session_id =session_id();


            session_name($session_name);
            session_id($COOKIES_session_id); //设置$se位session_id
            $this->session_id = $COOKIES_session_id;

            //超时
            session_set_cookie_params(bs_lib::get_configs_value('sys', 'session_ttl'));
            setcookie($session_name, $COOKIES_session_id, time() + bs_lib::get_configs_value('sys', 'session_ttl'));


        }


        // 下面这行代码可以防止使用对象作为会话保存管理器时可能引发的非预期行为
        // register_shutdown_function('session_write_close');


        session_set_save_handler(array(&$this, 'open'), array(&$this, 'close'), array(&$this, 'read'), array(&$this, 'write'), array(&$this, 'destroy'), array(&$this, 'gc'));

        //ob_end_clean();
        session_start();
        $this->session_id = session_id();


    }


    /** 开始 open*/
    function session_appen_open($COOKIES_session_id = null)
    {


        if (defined('CONST_SESION'))
        {

            //$this->session_id = session_id();


        } else
        {


            if ($COOKIES_session_id == '')
                $COOKIES_session_id = 'sslid-' . strtolower(BSPHP_SET) . '-' . MD5(TIME() . rand(1000, 9999)) . TIME() . rand(1000, 9999);
            //if ($COOKIES_session_id == '') $COOKIES_session_id = session_id();

            //  ini_set('session.save_handler', 'user');
            // ini_set('session.use_cookies', 0);
            //session_save_path(BSPHP_DIR_SYS . 'Data/session');


            session_id($COOKIES_session_id); //设置$se位session_id
            $this->session_id = $COOKIES_session_id;


        }


        session_set_save_handler(array(&$this, 'open'), array(&$this, 'close'), array(&$this, 'read'), array(&$this, 'write'), array(&$this, 'destroy'), array(&$this, 'gc'));

        session_start();
        $this->session_id = session_id();


    }


    //打开session
    public function open($save_path, $session_name)
    {


        return (true);
    }


    //关闭session
    public function close()
    {

        $this->gc(bs_lib::get_configs_value('sys', 'session_ttl'));


        //$this->db->destruct();//session关闭 断开数据库连接


        return (true);
    }


    //读取session
    public function read($id)
    {
        //读取SQL
        $sql = "SELECT`links_set`, `links_data`,`links_session_id` FROM`bs_php_links_session` WHERE`links_session_id`='$id'";
        $result_session = $this->db->Bsphp_my_array($sql);

        if ($result_session['links_set'] == -1)
            return '';

        //转换格式
        $result_session['links_data'] = stripslashes($result_session['links_data']);
        return ($result_session['links_data']);


    }


    //获取超时的时间
    /**
     * @参数
     */
    public function get_out_go_time($BSPHP_SET, $daihaos = 0)
    {

        if ($daihaos == 0)
            $daihaos = $this->daihao;

        switch ($BSPHP_SET)
        {


            case "APPEN":

                $sql = "SELECT  `app_daihao`,`app_links_chaoshi` FROM`bs_php_appinfo`WHERE `app_daihao`='$daihaos';";
                $array = $this->db->Bsphp_my_array($sql);

                if ((int)$array['app_links_chaoshi'] <= 60)
                    $array['app_links_chaoshi'] = 60;


                $time = $this->out_time = HOST_UNIX + $array['app_links_chaoshi']+800;
                break;


            default:


                if (bs_lib::get_configs_value('sys', 'session_ttl') < 800)
                {
                    $time = $this->out_time = HOST_UNIX + 800;
                } else
                {
                    $time = $this->out_time = HOST_UNIX + bs_lib::get_configs_value('sys', 'session_ttl') + 800;
                }


        }


        return $time;


    }


    //写入session
    public function write($id, $sess_data)
    {
        $this->sessions($id, $sess_data);

        return (true);


    }


    //写cookis
    function sessions($id, $sess_data)
    {

        if ($id == null)
        {
            return false;
        }


        //正常写入SESION
        $this->bs_set = BSPHP_SET;
        $this->ip = getip();
        $this->addtime = HOST_UNIX;

        $this->session = $id;

        //获取超时时间
        $this->out_time = $this->get_out_go_time(BSPHP_SET);


        //解除数据库空无法写入
        $this->daihao = (int)$this->daihao;
        //这里的-1表示不写数据
        if ($sess_data == -1)
        {


        } else
        {

            $sql = "SELECT  `links_session_id`,`links_user_name` FROM `bs_php_links_session` WHERE `links_session_id`='$id';";
            $array = $this->db->Bsphp_my_array($sql);
            if ($array)
            {

                $sql = "UPDATE`bs_php_links_session`SET `links_out_time` = '$this->out_time', `links_data`='$sess_data' , `links_chaoshi`='$this->addtime'  WHERE `links_session_id`='$id' LIMIT 1;";

            } else
            {
                //删除session
                $this->links_delete_ALL_out();
                $sql = "INSERT INTO `bs_php_links_session` VALUES ( NULL ,'{$this->session}','{$this->bs_set}', '0', '', '$this->daihao','$this->key', '$this->login_key', '$this->session', '$this->addtime', '$this->out_time', '$this->ip', '$this->addtime','','0');";
            }


            if ($result = $this->db->Bsphp_my_query($sql))
            {
                return true;
            } else
            {
              return false;

            }
        }

        return true;
    }


    //删除session
    public function destroy($id)
    {


        $this->db->Bsphp_my_query("DELETE FROM `bs_php_links_session` WHERE `links_session_id` = '$id'  Or `links_set`='-1';"); //与 T出超时
        //$this->db->Bsphp_my_query("DELETE FROM `bs_php_links_session` WHERE `links_session_id` = '$id' Or `links_out_time`<'" . HOST_UNIX . "';"); //与 T出超时

        return true;

    }


    /*********************************************
    * WARNING - You will need to implement some *
    * sort of garbage collection routine here.  *
    *********************************************/
    public function gc($maxlifetime)
    {


        //$this->db->Bsphp_my_query("DELETE FROM `bs_php_links_session` WHERE `links_set` = '-1' OR `links_out_time`<'" . HOST_UNIX . "';"); //删除废物 与 T出超时
        return true;

    }


    /**
     * @登录链接数 获取特征
     * 
     * 用于注册时检查和首次使用检测
     * 
     * 参数1=用户UID
     * 参数2=账号
     * 参数3=绑定特征 可控
     * 参数4=app 软件ID 可空
     * 参数5=登录标记
     */
    function links_add_info($userid, $username, $key = null, $app_id = 0, $login_key = null)
    {
        //建立session 不建立数据,写入session 因为在文件执行完之后
        $this->sessions($this->session_id, -1);

        if ($login_key == null)
            $login_key = md5($this->session_id);
        if ($key == null)
            $key = md5($this->session_id);

        $sql = "UPDATE`bs_php_links_session`SET`links_user_id`='$userid ', `links_user_name`='$username', `links_daihao`='$app_id', `links_key`='$key',`links_biaoji`='$login_key' WHERE`links_session_id`='{$this->session_id}' ";

        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            return true;
        } else
        {
            return false;
        }

    }


    /**
     * @注销指定登录 使用UID
     * 参数1=软件代号
     * 参数2=用户id
     * -3 注销登录,远程注销
     */
    function links_delete_all($daihao, $userid)
    {

        $time = HOST_UNIX;
        $sql = "UPDATE `bs_php_links_session` SET `links_set` = '-1' WHERE  `links_user_id` = '$userid' AND `links_daihao` = '$daihao';";

        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            return 1;
        } else
        {
            return - 1;
        }

    }


    /**
     * @注销指定登录 使用名称
     * 参数1=软件代号
     * 参数2=用户id
     * -3 注销登录,远程注销
     */
    function links_delete_all_name($daihao, $userid)
    {

        $time = HOST_UNIX;
        $sql = "UPDATE `bs_php_links_session` SET `links_set` = '-1' WHERE  `links_user_name` = '$userid' AND `links_daihao` = '$daihao';";

        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            return 1;
        } else
        {
            return - 1;
        }

    }


    /**
     * @注销指定登录 name名称注销
     * 参数1=session
     * 参数2=软件代号
     * 参数3=用户id
     * -2 注销登录
     */
    function links_delete_one_name($session, $daihao, $userid)
    {

        $time = HOST_UNIX;
        $sql = "UPDATE `bs_php_links_session` SET `links_out_time` = '-2' , `links_set` = '-1' WHERE `links_session` = '$session' AND `links_user_name` = '$userid' AND `links_daihao` = '$daihao' and `links_out_time`>'$time';";
        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            return 1;
        } else
        {
            return - 1;
        }

    }


    /**
     * @注销指定登录 UID注销
     * 参数1=session
     * 参数2=软件代号
     * 参数3=用户id
     * -2 注销登录
     */
    function links_delete_one($session, $daihao, $userid)
    {

        $time = HOST_UNIX;
        $sql = "UPDATE `bs_php_links_session` SET `links_out_time` = '-2' , `links_set` = '-1' WHERE `links_session` = '$session' AND `links_user_id` = '$userid' AND `links_daihao` = '$daihao' and `links_out_time`>'$time';";
        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            return 1;
        } else
        {
            return - 1;
        }

    }


    /**
     * @注销T出 专用 通过数据库ID
     * 参数1=session
     * 参数2=软件代号
     * 参数3=用户id
     * 
     * -1 T出
     */
    function links_delete_one_id($links_id)
    {
        //$sql="DELETE FROM `bs_php_links_session` WHERE `links_id` = '$links_id'";
        //$this->db->Bsphp_my_query($sql);
        $sql = "UPDATE `bs_php_links_session` SET `links_out_time` = '0' ,links_data='' WHERE `links_id` = '$links_id'  LIMIT 10;";
        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            return true;
        } else
        {
            return false;
        }

    }


    /**
     * @超时更新,判断被T出等注销功能 登录模式提示文字
     * 参数1=session
     * 参数2=软件代号
     * 参数3=用户id
     * 参数4=超时时间$session,$daihao,$userid,$out_time
     */
    function links_chaoshi_login()
    {

        $time = HOST_UNIX;

        $session = session_id();
        $daihao = HOST_DAIHAO;
        $userid = set_cookie_value('USER_UID');
        $out_time = $AppDaTa['app_links_chaoshi'];

        $sql = "SELECT * FROM `bs_php_links_session` WHERE`links_session`='$session' and `links_daihao`='$daihao' and `links_user_id`=\"$userid\"";
        $array = $this->db->Bsphp_my_array($sql);
        if (!$array)
            PrInfo("未登录,请验证在使用!");
        if ($array['links_out_time'] == -1)
            PrInfo("登录帐号被系统强制退出,请重登录新验证使用!");
        if ($array['links_out_time'] == -2)
            PrInfo("登录帐号已经注销退出,请重新登录验证再使用!");
        if ($array['links_out_time'] == -3)
            PrInfo("登录帐号已经被强制注销退出,请重新登录验证再使用!");
        if ($array['links_out_time'] < HOST_UNIX)
            PrInfo("登录帐号验证超时已经被注销,请重新登录验证再使用!");


        //账号超时更新
        $out_time = HOST_UNIX + $out_time;
        $sql = "UPDATE `bs_php_links_session` SET `links_chaoshi` = '$time',`links_out_time` = '$out_time' WHERE `links_session` = '$session' AND `links_user_id` = '$userid' AND `links_daihao` = '$daihao' and `links_out_time`>'$time';";
        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            return 1;
        } else
        {
            return - 1;
        }

    }

    /**
     * @超时更新,判断被T出等注销功能 登录模式提示文字
     * 参数1=session
     * 参数2=软件代号
     * 参数3=用户id
     * 参数4=超时时间$session,$daihao,$userid,$out_time
     */
    function links_chaoshi_car()
    {

        $time = HOST_UNIX;

        $session = session_id();
        $daihao = HOST_DAIHAO;
        $userid = set_cookie_value('ic_carid');
        $out_time = $AppDaTa['app_links_chaoshi'];

        $sql = "SELECT * FROM `bs_php_links_session` WHERE`links_session`='$session' and `links_daihao`='$daihao' and `links_user_name`=\"$userid\";";
        //PrInfo($sql);
        $array = $this->db->Bsphp_my_array($sql);
        if (!$array)
            PrInfo("卡串未验证使用,请验证在使用!");
        if ($array['links_out_time'] == -1)
            PrInfo("验证卡串被系统强制退出,请重新验证使用!");
        if ($array['links_out_time'] == -2)
            PrInfo("验证卡串已经注销退出,请重新验证再使用!");
        if ($array['links_out_time'] == -3)
            PrInfo("验证卡串已经被强制注销退出,请重新验证再使用!");
        if ($array['links_out_time'] < HOST_UNIX)
            PrInfo("验证卡串验证超时已经被注销,请重新验证再使用!");


        //账号超时更新
        $out_time = HOST_UNIX + $out_time;
        $sql = "UPDATE `bs_php_links_session` SET `links_chaoshi` = '$time',`links_out_time` = '$out_time' WHERE `links_session` = '$session' AND `links_user_id` = '$userid' AND `links_daihao` = '$daihao' and `links_out_time`>'$time';";
        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            return 1;
        } else
        {
            return - 1;
        }

    }


    /**
     * @删除所有超时
     */
    function links_delete_ALL_out()
    {
        $tmie = HOST_UNIX;

     
        $this->db->Bsphp_my_query("DELETE FROM `bs_php_links_session` WHERE `links_out_time` < '$tmie' or `links_set` < '0'  LIMIT 10"); // or `links_set`= -1
        return true;
    }


    /**
     * @删除 one session_id
     * 
     */
    function links_out_session_id($id)
    {

        $sql = "UPDATE`bs_php_links_session`SET`links_set`='-1' WHERE `links_session_id`='{$id}'  LIMIT 1";

        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            return true;
        } else
        {
            return false;
        }

    }


    //get_session
    public function get_session()
    {


        return session_id();
    }


    /**
     * @删除 one mysql_id
     */
    function links_delete_mysql_id($id)
    {


        $sql = "UPDATE`bs_php_links_session`SET`links_set`='-1',`links_data`=''  WHERE `links_id`='{$id}'  LIMIT 10";
        $tmp = $this->db->Bsphp_my_query($sql);
        if ($tmp)
        {
            return true;
        } else
        {
            return false;
        }

    }


}

?>