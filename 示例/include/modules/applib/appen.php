<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class appen
{

    private $rc, $db, $GLOBALS_LANGS, $user_str_log, $appen_str_log, $user;

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
     * @控制面板-软件列表
     * 
     */
    function call_open_begin()
    {
        //返回协议 200成功通信
        header("AppEn: 200");


        //-----------------------------------------------------------------------------------------------

        /**
         * @读取URL参数 必须先获取软件ID信息,因为要获取加密解密密码
         */
        if (!isset($_GET['appid']))die('URL地址错误！错误码:BSPHP_850021');
        $appid = $_GET['appid'];


        
        
        
            


        //-----------------------------------------------------------------------------------------------

        /** 3.
         * @连接软件配置
         * 
         * HOST_DAIHAO 这里是代号的常量全局调用 
         */
        define('HOST_DAIHAO', $appid);
        global $Global_DB_AppDaTa_Array;
        $Global_DB_AppDaTa_Array = GetAppInfo($appid);
        if(!is_array($Global_DB_AppDaTa_Array))die('APP错误！错误码:BSPHP_850022');
        
        

        //给Session传递软件代号
        $this->session->daihao = HOST_DAIHAO;

        
         bs_lib::$DIR_LANGS = $Global_DB_AppDaTa_Array['app_langs'];
        //-----------------------------------------------------------------------------------------------


        ///////////////////////////////特殊加载///////////////////////////////////////
        /**
         * 这里读取了加密密码所以放这里
         * 读取接收配置数据 读取的数据库bs_php_appini
         */

        define('HOST_GET', $Global_DB_AppDaTa_Array['app_get']); //接收的方式 0 = GET  1 = POST
        define('HOST_GET_PWD', $Global_DB_AppDaTa_Array['app_pwd']); //接收时候的密码
        define('HOST_CAOSHI', $Global_DB_AppDaTa_Array['app_chaoshi']); //超时的时间单位 秒
        define('HOST_DATA_PWD', $Global_DB_AppDaTa_Array['app_breaking']); //接收时候是否开启随机编码


        //////////////////////////////////////////////////////////////////////////////////////


        //-----------------------------------------------------------------------------------------------
        define('app_print_fun', $Global_DB_AppDaTa_Array['app_output'] . '_bsphp_output'); //输出
        $file_path = BSPHP_DIR_SYS . 'include/applibapi/output/' . $Global_DB_AppDaTa_Array['app_output'] . '.php';
        if (file_exists($file_path))
        {
            include ($file_path);
        } else
        {

            if ($Global_DB_AppDaTa_Array['app_output'] != 'notfun')
                PrInfo('文件不存在:需要的输出文件不存在！错误码:BSPHP_950021');


        }

        //-----------------------------------------------------------------------------------------------

        /**
         * @开始载入配置函数文件 加密 解密
         */


        define('app_get_encryption', $Global_DB_AppDaTa_Array['app_get_encryption'] . '_bsphp_mdecrypt'); //解密
        define('app_show_encryption', $Global_DB_AppDaTa_Array['app_show_encryption'] . '_bsphp_mencrypt'); //加密


        $file_path = BSPHP_DIR_SYS . 'include/applibapi/encryption/' . $Global_DB_AppDaTa_Array['app_get_encryption'] . '.php';
        //默认接口库

        if (file_exists($file_path))
        {
            include ($file_path);
        } else
        {

            if ($Global_DB_AppDaTa_Array['app_get_encryption'] != 'notfun')
                PrInfo('文件不存在:需要的解密文件不存在！错误码:BSPHP_950011');


        }


        if ($Global_DB_AppDaTa_Array['app_show_encryption'] != 'notfun')
        {
            $file_path = BSPHP_DIR_SYS . 'include/applibapi/encryption/' . $Global_DB_AppDaTa_Array['app_show_encryption'] . '.php';
            //默认接口库
            if (file_exists($file_path))
            {

                if ($Global_DB_AppDaTa_Array['app_show_encryption'] != $Global_DB_AppDaTa_Array['app_get_encryption'])
                {
                    include ($file_path);
                }


            } else
            {
                if ($Global_DB_AppDaTa_Array['app_get_encryption'] != 'notfun')
                    PrInfo('文件不存在:需要的加密文件不存在！错误码:BSPHP_950022');
            }

        }
        //-----------------------------------------------------------------------------------------------


        /**
         * 验证发包程序合法信息
         * 
         * 
         * if (!isset($_SERVER['HTTP_ACCEPT_APPEN']))
         * {

         * exit(PrLog(5007));
         * }
         */


        /**
         * 判断系统是否处于维护状态
         * 
         * 这里是系统判断非软件独立判断
         */
        //全局系统关闭
        if (bs_lib::get_configs_value('sys', 'stop') != 0)
        {
            PrInfo(bs_lib::get_configs_value('sys', 'stop_info'));
        }
        //当前软件关闭
        if ($Global_DB_AppDaTa_Array['app_off'] != 0)
        {
            PrInfo($Global_DB_AppDaTa_Array['app_off_name']);
        }

        //-----------------------------------------------------------------------------------------------

        /**
         * @读取URL参数
         */
        if (isset($_GET['md5']))
        {


            $md5 = md5(bs_lib::get_configs_value('sys', 'cook_key') . $appid . bs_lib::get_configs_value('sys', 'cook_key'));

            if ($md5 != $_GET['md5'])
            {
                PrInfo('AppID md5参数错误 服务器地址不正确！错误码:BSPHP_940007');
            }

        } else
        {

            PrInfo('AppID md5参数错误 不能为空！错误码:BSPHP_940006');
        }


        //-----------------------------------------------------------------------------------------------


        /** -1.
         * @获取客户端发送过来的数据
         * 接收处理接收过来的数据
         * @
         * GetUrlData 等于接收过来的POST和GET的开始
         * 
         *  set_data()    该函数只处理数据包参数  参数为URL参数名称
         */
        $UrlDaTa = GetUrlData();


        //-----------------------------------------------------------------------------------------------


        /** 0.
         * @解析数据包参数
         * Parse_Date = 拆分URL
         * 返回数组 $ArrAyDaTa
         */

        global $Global_Url_DaTa_Array;
        $Global_Url_DaTa_Array = Parse_Date($UrlDaTa);


        //-----------------------------------------------------------------------------------------------

        /**
         * @验证交互通信Key
         */
        $mutualkey = md5('xiaozhou' . bs_lib::get_configs_value('sys', 'cook_key') . $appid . bs_lib::get_configs_value('sys', 'cook_key') . 'QQ944463782');


        if (!isset($Global_Url_DaTa_Array['mutualkey']))
            PrInfo('通信认证Key不能为空/错误! 错误码:BSPHP_940008');
        if ($Global_Url_DaTa_Array['mutualkey'] != $mutualkey)
            PrInfo('通信认证Key验证失败,请确定配置是否正确! 错误码:BSPHP_940009');


        //-----------------------------------------------------------------------------------------------


        /** 1.
         * @验证超时
         * 
         * 无需传递任何参数值需要执行API
         */
        dateChaoshi();

        //-----------------------------------------------------------------------------------------------
        if ($Global_Url_DaTa_Array['api'] == '')
            PrLog(5004); //接口不存在,连接失败
        //解密API,不使用别名直接返回原来API
        $api = bsphp_api($Global_Url_DaTa_Array['api']);


        //------------------------------------------------------------------------------------------------

        /** 2.
         * @检测接口是否存在
         * 
         * $file_api=全局文件接口名称
         * 
         */
        $file_api = 'AppEn.' . $api . '.php';
        $file_urlA = BSPHP_DIR_SYS . 'include/applibapi/api/Default/' . $file_api;
        $file_urlB = BSPHP_DIR_SYS . 'include/applibapi/api/' . $Global_DB_AppDaTa_Array['app_MoShi'] . '/' . $file_api;
        $file_urlC = BSPHP_DIR_SYS . 'include/applibapi/api/' . $Global_DB_AppDaTa_Array['app_api_dir'] . '/' . $file_api;

        //用户自定义接口库
        if (!file_exists($file_urlC))
        {
            //模式接口库
            if (!file_exists($file_urlB))
            {


                //默认接口库
                if (!file_exists($file_urlA))
                {


                    PrLog(5004); //接口不存在,连接失败

                } else
                {
                    $file_url = $file_urlA;
                }


            } else
            {
                $file_url = $file_urlB;
            }


        } else
        {
            $file_url = $file_urlC;
        }


        //-----------------------------------------------------------------------------------------------
        //BSphpSeSsL 不可为空
        if (set_data('BSphpSeSsL') == null and $api != 'internet.in' and $api != 'BSphpSeSsL.in')
            PrInfo('BSphpSeSsL连接串不可为空! 错误码:BSPHP_940010');

        //-----------------------------------------------------------------------------------------------


        /**
         * @设置BSphpSeSsL
         */
        if (set_data('BSphpSeSsL') != '')
        {
            $this->session->session_appen_open($Global_Url_DaTa_Array['BSphpSeSsL']);
            set_session_value('AppEn_session', 'OK'); //登陆UID


        }
        //-----------------------------------------------------------------------------------------------


        /** 4.
         * @程序MD5验证
         * 
         * $AppDaTa['app_md5']=空内容默认关闭MD5验证
         * 
         */
        $md5 = $Global_DB_AppDaTa_Array['app_md5'];

        if ($md5 != '')
        {

            $get_md5 = set_data('md5'); //md5
            $get_user = set_data('user'); //参数账号
            $get_key = set_data('key'); //机器码

            $get_daihao = set_data('app_daihao'); //软件代号
            $get_uid = get_session_value('USER_UID'); //用户cookies uid

            $get_car_id = get_session_value('ic_carid'); //卡串 cookies uid
            $get_icid = set_data('icid'); //卡串号参数

            if (!stristr($md5, set_data('md5')) or $get_md5 == '')
            {

                if (HOST_DATA_PWD == 1)
                {

                    //日志记录
                    bs_lib::addappenlog('od_po_log', "软件MD5无法效验MD5:$get_md5  软件号:$get_daihao 机器码:$get_key");

                    //封号处理
                    if ($get_user != null)
                    {


                        $sql = "UPDATE `bs_php_user` SET `user_IsLock` = '1' WHERE `bs_php_user`.`user_user` = \"$get_user\";";
                        $db->Bsphp_my_query($sql);
                        bs_lib::addappenlog('od_po_log', "Bsphp系统安全:已经将帐号为:$get_user 执行封号处理成功。");

                    } elseif ($get_uid != null)
                    {

                        $sql = "UPDATE `bs_php_user` SET `user_IsLock` = '1' WHERE `bs_php_user`.`user_uid` = \"$get_uid\";";
                        $db->Bsphp_my_query($sql);
                        bs_lib::addappenlog('od_po_log', "Bsphp系统安全:已经将UID为:$get_uid 执行封号处理成功。");

                    }
                } elseif ($get_car_id != null)
                {


                    $sql = "UPDATE `bs_php_pattern_car` SET `ic_IsLock` = '1' WHERE `bs_php_pattern_car`.`ic_name` = \"$get_car_id\";";
                    $db->Bsphp_my_query($sql);
                    bs_lib::addappenlog('od_po_log', "Bsphp系统安全:已经将卡串为:$get_car_id 执行冻结处理成功。");

                } elseif ($get_icid != null)
                {


                    $sql = "UPDATE `bs_php_pattern_car` SET `ic_IsLock` = '1' WHERE `bs_php_pattern_car`.`ic_name` = \"$get_icid\";";
                    $db->Bsphp_my_query($sql);
                    bs_lib::addappenlog('od_po_log', "Bsphp系统安全:已经将卡串为:$get_icid 执行冻结处理成功。");

                }

                PrLog(5006);

            }

        }


        /**
         * 验证码机制
         */


        if (stristr($Global_DB_AppDaTa_Array['app_coode'], $api))
        {
            $coodes = push_cood_imges(set_data('coode'));
            if ($coodes == 1038)
                PrInfo('验证码不正确,请重新输入验证码！');
            if ($coodes == 1036)
                PrInfo('验证码不能为空,请重新输入验证码！');


        }


        /**
         * @载入接口库
         * 
         * 下面载入接口库
         *  PHP验证库路径:H:\AppServ\www\app\include\lib\
         * 
         */

        include ($file_url);
    }


}

?>