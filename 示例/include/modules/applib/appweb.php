<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class appweb
{

    private $rc, $db, $GLOBALS_LANGS, $user_str_log, $appen_str_log, $user, $session;

    function __construct()
    {
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->user = bs_lib::load_modules_class('user', 'user');
        bs_lib::load_modules_common('applib', 'appen_inc');
        bs_lib::load_modules_common('applib', 'appen_appuser');


        $this->GLOBALS_LANGS = bs_lib::load_langs_array();
        $this->user_str_log = bs_lib::load_langs_array('user', 'user_str_log');
        $this->appen_str_log = bs_lib::load_langs_array('applib', 'appen_str_log');

        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');


    }


    /**
     * @登录
     * http://127.0.0.5/index.php?m=applib&c=appweb&a=login&BSphpSeSsL=[验证串:可空]&user=[用户帐户]
     */
    function call_login()
    {
        $log_name = '';
        $BSphpSeSsL = set_get('BSphpSeSsL');
        if ($BSphpSeSsL == '') {
            $this->session->session_open();
        } else {
            $this->session->session_appen_open($BSphpSeSsL);
        }


        $show = set_get('show');

        #验证成功
        $login_sate = set_get('login_sate');
        if ($login_sate == 'YES') {
            echo 'YES';
            exit;

        }


        //判断是否已经登陆
        if ($this->user->UserLoginInfo() == 1047) {
            location("index.php?m=applib&c=appweb&a=login&BSphpSeSsL={$this->session->session_id}&login_sate=YES");
        }

        //接收参数
        $imga_yan = set_post('code');
        $user = set_post('user');
        $pwd = set_post('pwd');
        $login = set_post('login');


        if ($login) {
            //判断验证对错
            if (bs_lib::get_configs_value('code', 'coode_login') == 1) {


                $log = push_cood_imges($imga_yan);
                if ((int)$log !== 1037)
                    $log_name = $this->user_str_log[$log];
                include bs_lib::load_default_path();
                exit;


            }


            //用户登陆
            $log = $this->user->UserWebLogin($user, $pwd);


            if ($log == 1011) {


                $ser_array = $this->db->my_class_array('bs_php_user', 'user_uid', $user, ' * ');
                //代理权限验证
                if ($ser_array['user_daili'] > 0) {
                    $log_name = '代理商没有权限登录用户中心';
                    $this->session->links_out_session_id($this->session->session_id);
                    include bs_lib::load_default_path();
                    exit;
                }


                //系统日志记录
                bs_lib::addappenlog('user_login_log', 'APP网页登录', $user);
                //session 在线设置

                $this->session->links_add_info(0, $user);

                $log_name = '登录成功！';

                location("index.php?m=applib&c=appweb&a=login&BSphpSeSsL={$this->session->session_id}&login_sate=YES");
                exit;
            }


            $log_name = $this->user_str_log[$log];
        } else {
            $user = set_get('user');
            $pwd = set_get('pwd');
        }


        include bs_lib::load_default_path();
    }


    #反馈 http://127.0.0.5/index.php?m=applib&c=appweb&a=feedback&daihao=[软件代号]&uid=[用户UID:自定义]&table=[名称标题:自定义]&leix=[类型:自定义]&BSphpSeSsL=[接口登录后验证传:必传]
    function call_feedback()
    {
        $this->session->session_open();
        #echo $this->session->session_id;
        #ECHO get_session_value('coode_imges_md5');


        $daihao = set_get('daihao');
        $uid = set_get('uid');
        $table = set_get('table');
        $leix = set_get('leix');

        $qq = set_post('qq');
        $txt = set_post('txt');
        $code = set_post('code');

        if (bs_lib::get_configs_value('code', 'coode_say') == 1) {


            $log = push_cood_imges($code);
            if ($log != 1037) {


                $log_name = $this->user_str_log[$log];
                include bs_lib::load_default_path();
                exit;
            } else {

            }


        }


        /**
         * 提交留言
         */
        $log = $this->user->UserAddLiuYan($leix, $table, $qq, $uid, $daihao, $txt);
        $log_name = $this->user_str_log[$log];


        include bs_lib::load_default_path();
    }


    #注册 http://127.0.0.5/admin/index.php?m=applib&c=appweb&a=register
    function call_register()
    {


        include bs_lib::load_default_path();
    }

    #找回密码
    function call_Retrievepassword()
    {


        include bs_lib::load_default_path();
    }


    #新闻列表
    function call_new_info()
    {


        $id = set_get('id');


        $news_array = $this->db->my_class_array('plug_bsphp_news', 'news_id', $id, ' * ');

        $news_class_array = $this->db->my_class_array('plug_bsphp_news_class',
            'class_id', $news_array['news_class'], ' * ');


        $news_test = base64_decode($news_array['news_test']);
        $news_test = stripslashes($news_test);


        include bs_lib::load_default_path();
    }


    #新闻列表 http://127.0.0.5/index.php?m=applib&c=appweb&a=new_list&open_new=[是否新窗口打开:_blank]
    function call_new_list()
    {


        $list = (int)set_get('list');
        $p = (int)set_get('p');
        $act = set_get('act');

        $open_new = set_get('open_new');

        $Page_mober = 15;
        //获取读取数量
        $LIMIT_As = $p;
        if ($LIMIT_As > 0)
            $LIMIT_As = $LIMIT_As - 1;
        $LIMIT_A = $LIMIT_As * $Page_mober;

        $date = null;
        $sql_list = null;
        $news_text = null;
        $i = 1;
        if ($list > 0)
            $sql_list = "AND `news_class`='$list'";
        $sql = "SELECT * FROM `plug_bsphp_news`,`plug_bsphp_news_class` WHERE `plug_bsphp_news`.`news_class`=`plug_bsphp_news_class`.`class_id` $sql_list ORDER BY`news_id`DESC LIMIT $LIMIT_A,$Page_mober";

        $sql_rows = "SELECT * FROM`plug_bsphp_news_class`, `plug_bsphp_news` WHERE `plug_bsphp_news`.`news_class`=`plug_bsphp_news_class`.`class_id`  $sql_list ORDER BY`news_id`DESC";
        $db_array_value = $this->db->Bsphp_my_query($sql);


        //-------------------------------------------

        /**
         * @获取条数<a href='news.htm'>1</a>
         */
        $tmp = $this->db->Bsphp_my_query($sql_rows);
        $zongshu = $this->db->my_rows($tmp);

        /**
         * @计算分页
         */
        //$yueshuls=取整数就小于$zongyue不取整数的了*/
        $zongyue = $zongshu / $Page_mober;

        $yueshuls = intval(strval($zongshu / $Page_mober));

        if ($zongyue > $yueshuls)
            $zongyue = ++$yueshuls;


        $pg_text = null;
        $i = 1;
        while ($i <= $zongyue) {


            $pg_text .= "<li><a href='index.php?m=applib&c=appweb&a=new_list&open_new=$open_new&list=$list&p=$i'>{$i}</a>";
            $i++;

        }

        if ($list > 0) {


            $list_array = $this->db->my_class_array('plug_bsphp_news_class', 'class_id', $list,
                ' * ');


        } else {

            $list_array['class_name'] = '新闻动态';
            $list_array['class_id'] = '0';


        }


        include bs_lib::load_default_path();
    }


    #充值 http://127.0.0.5/admin/index.php?m=applib&c=appweb&a=Recharge&user=944463782&ka=654&mi=321
    function call_Recharge()
    {
        $log_name = '';
        $url = get_url();
        //echo $url;

        /**
         * POST内容
         */
        $Submitadd = set_post('Submitadd');
        $user_user = set_post('user_user');
        $ka_name = set_post('ka_name');
        $ka_pwd = set_post('ka_pwd');


        /**
         * 去空格
         */
        $ka_name = trim($ka_name);
        $ka_pwd = trim($ka_pwd);


        /**
         * 判断是否为空
         */
        if ($Submitadd) {
            if (trim($ka_name) == '')
                $log_name = "充值卡号不能为空";
            $log = $this->user->UserChong($user_user, $ka_name, $ka_pwd);

            $log_name = $this->user_str_log[$log];

        } else {
            /**
             * 获取GET账号密码
             */
            $GET_ka_user = set_get('user');
            $GET_ka_name = set_get('ka');
            $GET_ka_pwd = set_get('mi');


            $ka_name = $GET_ka_name;
            $ka_pwd = $GET_ka_pwd;
            $user_user = $GET_ka_user;


        }


        /**
         * 检测账号所属软件
         */
        /*
        $app_txt = '充值卡会自动识别需要充值软件！ 点击检测可查充值卡所属软件';
        $app=set_post('app');
        if($app) {
        $daihao=$this->db->my_class_array('bs_php_cardseries','car_name',$ka_name,'`car_DaiHao`');
        $app_name = $this->db->my_class_array('bs_php_appinfo','app_daihao',$daihao,'`app_name`');
        if($app_name){
        $app_txt='所属软件:'.$app_name;
        }else{
        $app_txt='没有找到对应软件,充值卡无效！';
        }
        }*/


        include bs_lib::load_default_path();
    }

    /**
     * @登录模式
     * http://127.0.0.5/index.php?m=applib&c=appweb&a=links_login&daihao=[软件代号:可空]&BSphpSeSsL=[验证串:可空]&user=[用户帐户]&pwd=[用户密码]&login=YES
     * 
     */
    function call_links_login()
    {
        $log_name = '';
        $BSphpSeSsL = set_get('BSphpSeSsL');
        if ($BSphpSeSsL == '') {
            $this->session->session_open();
        } else {
            $this->session->session_appen_open($BSphpSeSsL);
        }


        //接收参数
        $id = set_get('id');

        $user = set_get('user');
        $pwd = set_get('pwd');
        $daihao = set_get('daihao');
        $login = set_get('login');


        //判断是否已经登陆
        if ($this->user->UserLoginInfo() == 1047) {
            $USER_UID = get_session_value('USER_UID');
            $user = $this->db->my_class_array('bs_php_user', 'user_uid', $USER_UID,
                '`user_user`');
            if ($user == '') {
                echo 'CODE:信息读取失败';
                exit;
            }
        } else {


            if ($login) {


                //用户登陆
                $log = $this->user->UserWebLogin($user, $pwd);


                if ($log == 1011) {


                } else {
                    $log_name = $this->user_str_log[$log];
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
                    echo 'CODE:' . $log_name;
                    exit;
                }


            } else {
                echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
                echo 'CODE:URL参数错误！';
                exit;


            }
        }


        if ($id > 0) {
            $sql = "UPDATE `bs_php_links_session` SET `links_set` = '-1' WHERE  `links_user_name` = '$user' AND `links_id` = '$id';";
            $this->db->Bsphp_my_query($sql);


            $this->session->links_delete_ALL_out();
        }


        if ($daihao <> 0) {
            $daihao_sql = " AND `links_daihao`= '$daihao'";
        } else {
            $daihao_sql = '';
        }


        $sql = "SELECT * FROM`bs_php_links_session` WHERE `links_user_name`='$user' $daihao_sql ORDER BY`links_biaoji`DESC";
        #echo $sql;
        $db_array_value = $this->db->Bsphp_my_query($sql);


        include bs_lib::load_default_path();
    }

    /**
     * @登录模式
     * http://127.0.0.5/index.php?m=applib&c=appweb&a=links_car&daihao=[软件代号]&BSphpSeSsL=[验证串:可空]&car_id=[卡用户帐户]&car_pwd=[用户密码:可空]&login=YES
     * 
     */
    function call_links_car()
    {
        $log_name = '';
        $BSphpSeSsL = set_get('BSphpSeSsL');
        if ($BSphpSeSsL == '') {
            $this->session->session_open();
        } else {
            $this->session->session_appen_open($BSphpSeSsL);
        }


        //接收参数
        $id = set_get('id');

        $car_id = set_get('car_id');
        $car_pwd = set_get('car_pwd');
        $daihao = set_get('daihao');
        $login = set_get('login');
        //判断是否已经登陆
        if ($car_id == '') {
            $car_id = get_session_value('ic_carid');
            $car_pwd = get_session_value('ic_pwd');
            $daihao = get_session_value('daihao');


        }


        $log = CarCookies($car_id, $car_pwd, $daihao);
        if ($log == 1080) {
            set_session_value('ic_carid', $car_id); //登陆UID
            set_session_value('ic_pwd', $car_pwd); //登陆MD7加密
            set_session_value('daihao', $daihao); //登陆MD7加密

        } else {


            if ($login) {


                //用户登陆
                $carinfo = GetCardInfo($car_id, $car_pwd, $daihao);
                if (is_array($carinfo)) {


                    set_session_value('ic_carid', $car_id); //登陆UID
                    set_session_value('ic_pwd', $car_pwd); //登陆MD7加密
                    set_session_value('daihao', $daihao); //登陆MD7加密


                } else {
                    $log_name = $this->user_str_log[$log];
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
                    echo 'CODES:' . $log_name;
                    exit;
                }


            } else {
                echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
                echo 'CODE:URL参数错误！';
                exit;
            }
        }

        if ($id > 0) {
            $sql = "UPDATE `bs_php_links_session` SET `links_set` = '-1' WHERE  `links_user_name` = '$car_id' AND `links_id` = '$id';";
            $this->db->Bsphp_my_query($sql);
            $this->session->links_delete_ALL_out();
        }

        if ($daihao <> 0) {
            $daihao_sql = " AND `links_daihao`= '$daihao'";
        } else {
            $daihao_sql = '';
        }

        $sql = "SELECT * FROM`bs_php_links_session` WHERE `links_user_name`='$car_id' $daihao_sql ORDER BY`links_biaoji`DESC";
        #echo $sql;
        $db_array_value = $this->db->Bsphp_my_query($sql);


        include bs_lib::load_default_path();
    }
}

?>
