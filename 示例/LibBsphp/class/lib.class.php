<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');

/**
 * @数据库操作
 * 文件名称:lib.class.php
 * 功能:系统基础类全局功能,支持在所有类调用
 * 时间:2015.1.4
 * 官网:www.bsphp.com
 *
 * 载入位置:Global.Bsphp.Inc.php
 */
class bs_lib
{


    public static $URL_M, $URL_C, $URL_A, $DIR_TEMPLET, $DIR_MODULES, $DIR_STATICS, $DIR_LANGS;

    /**
     * @IE输入方式载入模块类
     * 自动处理完成并载入块
     */
    public static function Include_Modules()
    {


        //转义文件

        $_POST = bs_lib::addslashes_array($_POST);
        //$_GET = bs_lib::addslashes_array($_GET);
        //$_REQUEST = bs_lib::addslashes_array($_REQUEST);
        //$_COOKIE = bs_lib::addslashes_array($_COOKIE);


        if (BSPHP_SET == 'APPEN')
        {

            //模块名称
            self::$URL_M = 'applib';

            //文件名称
            self::$URL_C = 'appen';

            //调用函数名称,如果空默认文件名
            self::$URL_A = 'open_begin';


        } else
        {

            //模块名称
            isset($_GET['m']) == true ? self::$URL_M = $_GET['m'] : exit(bs_lib::Alerts('Error:URL参数错误', '错误URL参数错(M)误,没有找到入口模块.'));

            //文件名称
            isset($_GET['c']) == true ? self::$URL_C = $_GET['c'] : self::$URL_C = 'index';

            //调用函数名称,如果空默认文件名
            isset($_GET['a']) == true ? self::$URL_A = $_GET['a'] : self::$URL_A = self::$URL_C;


            //模板参数
            if (!isset($_GET['t']))
            {
                $_GET['t'] = null;
            }


        }


        //控制器安全
        self::$URL_M = safe_replace(self::$URL_M);
        self::$URL_C = safe_replace(self::$URL_C);
        self::$URL_A = safe_replace(self::$URL_A);

        //入口类型
        switch (BSPHP_SET)
        {


            case "ADMIN":


                /**
                 * @在这里验证商业授权平台验证接口
                 *
                 * 验证授权
                 *
                 *
                 * 在这里直接验证授权读取保存在文件夹中
                 *
                 * agent
                 * 位置  1号
                 */
             

         





                //open_code('ICAgICAgICAgICAgICAgIGlmIChic19saWI6OiRVUkxfTSA9PSAmIzM5O2FkbWluJiMzOTsgYW5kIGJzX2xpYjo6JFVSTF9DID09ICYjMzk7bWFpbiYjMzk7IGFuZCBic19saWI6OiRVUkxfQSA9PSAmIzM5O2luZGV4JiMzOTspDQogICAgICAgICAgICAgICAgew0KDQoNCiAgICAgICAgICAgICAgICAgICAgc2V0X3RpbWVfbGltaXQoMCk7DQogICAgICAgICAgICAgICAgICAgIEJzcGhwX29wZW5zc2xfcG9zdF9WZXJpZmljYXRpb24oKTsNCiAgICAgICAgICAgICAgICAgICAgJGFkbWluX2luZm8gPSBBZG1pbl9WZXJpZmljYXRpb25zKCYjMzk7VW5pdmVyc2FsJiMzOTspOyAvL+mqjOivgeWfn+WQjQ0KICAgICAgICAgICAgICAgICAgICBpZiAoJGFkbWluX2luZm8gIT0gMSkNCiAgICAgICAgICAgICAgICAgICAgICAgIGJzX2xpYjo6QWxlcnRzKCYjMzk757O757uf5pyq5o6I5p2DJiMzOTssICYjMzk75bCK5pWs55So5oi3LOS9oOW9k+WJjeezu+e7n+acque7j+aOiOadg+aXoOazleS9v+eUqCzor7flhYjotK3kubDmjojmnYPlnKjkvb/nlKjjgILlnKjnur/otK3kubDlnLDlnYA6aHR0cDovL01hbmFnZW1lbnQuQnNwaHAuY29tJiMzOTspOw0KICAgICAgICAgICAgICAgIH0NCg0KICAgICAgICAgICAgICAgIGlmIChic19saWI6OiRVUkxfTSA9PSAmIzM5O3RhYmxlJiMzOTspDQogICAgICAgICAgICAgICAgew0KICAgICAgICAgICAgICAgICAgICAkYWRtaW5faW5mbyA9IEFkbWluX1ZlcmlmaWNhdGlvbnMoJiMzOTtVbml2ZXJzYWwmIzM5Oyk7IC8v6aqM6K+B5Z+f5ZCNDQogICAgICAgICAgICAgICAgICAgIGlmICgkYWRtaW5faW5mbyAhPSAxKQ0KICAgICAgICAgICAgICAgICAgICAgICAgYnNfbGliOjpBbGVydHMoJiMzOTvns7vnu5/mnKrmjojmnYMmIzM5OywgJiMzOTvlsIrmlaznlKjmiLcs5L2g5b2T5YmN57O757uf5pyq57uP5o6I5p2D5peg5rOV5L2/55SoLOivt+WFiOi0reS5sOaOiOadg+WcqOS9v+eUqOOAguWcqOe6v+i0reS5sOWcsOWdgDpodHRwOi8vTWFuYWdlbWVudC5Cc3BocC5jb20mIzM5Oyk7DQogICAgICAgICAgICAgICAgfQ0K');

                //设置当前目录HTML模板位置
                self::$DIR_MODULES = BSPHP_DIR_SYS . 'include/modules/' . self::$URL_M . '/';
                self::$DIR_TEMPLET = BSPHP_DIR_SYS . 'include/templates/' . bs_lib::get_configs_value('sys', 'admin_template') . '/' . self::$URL_M . '/';
                self::$DIR_STATICS = BSPHP_DIR_SYS . 'statics/' . bs_lib::get_configs_value('sys', 'admin_template') . '/';

                self::$DIR_LANGS = bs_lib::get_configs_value('sys', 'admin_langs');


                break;
            case "INDEX":
                //设置当前目录HTML模板位置
                self::$DIR_MODULES = BSPHP_DIR_SYS . 'include/modules/' . self::$URL_M . '/';
                self::$DIR_TEMPLET = BSPHP_DIR_SYS . 'include/templates/' . bs_lib::get_configs_value('sys', 'cms_template') . '/' . self::$URL_M . '/';
                self::$DIR_STATICS = BSPHP_DIR_SYS . 'statics/' . bs_lib::get_configs_value('sys', 'cms_template') . '/';


                self::$DIR_LANGS = bs_lib::get_configs_value('sys', 'cms_langs');


                break;
            case "APPEN":


                ///这里的模板不需要载入,语言包在APPEN 时候载入 appen.php  开始段载入


                break;
            case "USER":

                //设置当前目录HTML模板位置
                self::$DIR_MODULES = BSPHP_DIR_SYS . 'include/modules/' . self::$URL_M . '/';
                self::$DIR_TEMPLET = BSPHP_DIR_SYS . 'include/templates/' . bs_lib::get_configs_value('sys', 'user_template') . '/' . self::$URL_M . '/';
                self::$DIR_STATICS = BSPHP_DIR_SYS . 'statics/' . bs_lib::get_configs_value('sys', 'user_template') . '/';

                self::$DIR_LANGS = bs_lib::get_configs_value('sys', 'user_langs');

                break;

            case "AGENT":

                //设置当前目录HTML模板位置
                self::$DIR_MODULES = BSPHP_DIR_SYS . 'include/modules/' . self::$URL_M . '/';
                self::$DIR_TEMPLET = BSPHP_DIR_SYS . 'include/templates/' . bs_lib::get_configs_value('sys', 'agent_template') . '/' . self::$URL_M . '/';
                self::$DIR_STATICS = BSPHP_DIR_SYS . 'statics/' . bs_lib::get_configs_value('sys', 'agent_template') . '/';

                self::$DIR_LANGS = bs_lib::get_configs_value('sys', 'agent_langs');

                break;


            default:
                exit(bs_lib::Alerts('Error:入口文件错误', '错误文件点,该文件不属于BSPHP默认入口点,或文件被修改.'));
        }


        /**
         * @调用控制器
         */
        bs_lib::load_call_m(self::$URL_M, self::$URL_C, self::$URL_A, self::$URL_A);
    }


    /**
     * 返回经addslashes处理过的字符串或数组
     * @param $string 需要处理的字符串或数组
     * @return mixed
     */
    static function addslashes_array($string)
    {
        if (!is_array($string))
        {


            //安全检测
            if (bs_lib::Bsphp_safe($string) == 1)
            {
                header('Content-Type: text/html; charset=utf-8');
                echo '系统提示:为系统安全请勿输入屏蔽敏感关键字！' . $string;
                exit;

            }


            //添加转义
            if (!get_magic_quotes_gpc())
            {
                //return $string;
                return addslashes($string);
            } else
            {

                return $string;
            }


        } else
        {


            foreach ($string as $key => $val)
            {
                $string[$key] = bs_lib::addslashes_array($val);

                /**
                 * 拦截获取屏蔽关键字
                 */
                if (bs_lib::Bsphp_safe($string[$key]) == 1)
                {
                    @header('Content-Type: text/html; charset=utf-8');
                    echo '系统提示:为系统安全请勿输入屏蔽敏感关键字！' . $string;
                    exit;
                }


            }


            return $string;
        }

    }

    /**
     * 返回经addslashes处理过的字符串或数组
     * @param $string 需要处理的字符串或数组
     * @return mixed
     */
    static function stripslashes_array($string)
    {
        if (!is_array($string))
            return stripslashes($string);
        foreach ($string as $key => $val)
            $string[$key] = bs_lib::stripslashes_array($val);
        return $string;
    }


    /**
     * @动态调用CALL
     *
     * 动态调用控制器类
     * 参数1=类名
     * 参数2=调用类中函数名
     *
     * 空默认载入当前URL控制器
     */
    public static function class_call($class_name = null, $fun_call_name = null)
    {


        //默认只有m 参数组
        if ($class_name == null)
            $class_name = self::$URL_M . '_' . self::$URL_C;

        if ($fun_call_name == null)
            $fun_call_name = 'call_' . self::$URL_M;

        call_user_func(array(__namespace__ . '\\' . $class_name, $fun_call_name)); // As of PHP 5.3.0

    }


    /**
     * @载入控制器-类
     *
     *
     * @param 参数1 =模型
     * @param 文件名
     * @param 控制器函数
     *
     */
    public static function load_call_m($m, $c, $a)
    {


        $File = BSPHP_DIR_SYS . 'include/modules/' . $m . '/' . $c . '.php';


        //检测文件
        if (!file_exists($File))
            exit(bs_lib::Alerts('Error:控制入口错误', '需要引入php脚步文件不存在。<BR/>文件路径:' . $File));
        include ($File);


        if (!class_exists($c))
            exit(bs_lib::Alerts('Error:控制入口错误', "找不到入口点类, 类名:{$c}<BR/>文件路径:" . $File));
        //初始化
        $new = new $c();


        //函数名
        $a = 'call_' . $a;

        //检测控制器
        if (false == method_exists($new, $a))
            exit(bs_lib::Alerts('Error:控制入口错误', "需要函数入口不存在不存在,函数名:{$a}。<BR/>文件路径:" . $File));
        //执行控制器函数
        $new->$a();

        //return $new;

    }


    /**
     * @调用功能模块下Class
     *
     *
     * 参数1=模块目录名称
     * 参数2=初始化类类 new
     *
     */
    public static function load_modules_class($path, $file_name = '')
    {

        $File = BSPHP_DIR_SYS . 'include/modules/' . $path . '/class/' . $file_name . '.class.php';


        if (!file_exists($File))
            exit(bs_lib::Alerts('Error:要引入类库文件不存在', '需要引入php脚步文件不存在。<BR/>文件路径:' . $File));


        if (in_array($File, get_included_files()) == false)
            include ($File);


        if (!empty($file_name))
        {

            return new $file_name();
        }

        return false;

    }


    /**
     * @引入文件 调用功能模块下
     *
     *
     * 参数1=模块目录名称
     * 参数2=模块目录 Common Common
     *
     */
    public static function load_modules_common($path, $file_name)
    {


        $File = BSPHP_DIR_SYS . 'include/modules/' . $path . '/common/' . $file_name . '*.function.php';
        $File_ARRAY = glob($File);
        foreach ($File_ARRAY as $filename)
        {
            if (!file_exists($filename))
                exit(bs_lib::Alerts('Error:要引入函数文件不存在', '需要引入php脚步文件不存在。<BR/>文件路径:' . $filename));


            include ($filename);


        }
        return ($File);
    }


    /**
     * @调用libbsphp Class 初始化
     *
     *
     * 参数1=模块目录名称
     * 参数2=是否需要初始化华类
     *
     */
    public static function load_libbsphp_class($path, $new_name = '')
    {
        static $Class_object = array();

        $File = BSPHP_DIR_LIB . 'class/' . $path . '.class.php';
        $File_mad = md5($File);

        //判断类是否已经载入,已经载入直接返回无需再次载入
        if (isset($Class_object[$File_mad]['path']))
        {


            return $Class_object[$File_mad]['object'];
        }


        if (!file_exists($File))
            exit(bs_lib::Alerts('Error:要引入类库文件不存在', '需要引入php脚步文件不存在。<BR/>文件路径:' . $File));

        $Class_object[$File_mad]['path'] = $File;
        include ($File);
        $Class_object[$File_mad]['object'] = new $new_name();
        return $Class_object[$File_mad]['object'];


    }


    /**
     * @引入调用libbsphp 函数
     *
     *
     * 参数=模块目录 common 目录common文件
     *
     */
    public static function load_libbsphp_common($path = '')
    {


        $File = BSPHP_DIR_LIB . 'common/' . $path . '*.function.php';
        $File_ARRAY = glob($File);
        foreach ($File_ARRAY as $filename)
        {
            if (!file_exists($filename))
                exit(bs_lib::Alerts('Error:要引入函数文件不存在', '需要引入php脚步文件不存在。<BR/>文件路径:' . $filename));
            // include ($filename);


            if (in_array($filename, get_included_files()) == false)
                include ($filename);


        }
    }

    /**
     * @语言目录
     *
     * 参数=模板目录名称 空=获取系统当前默认
     * @param 所属模块名
     * @param 文件名
     *
     * @return Array
     */
    public static function load_langs_array($path = null, $file_name = null)
    {


        $langs = self::$DIR_LANGS;
        if ($path == null)
            $path = (string )self::$URL_M;
        if ($file_name == null)
            $file_name = (string )self::$URL_C;

        //获取完整目录路径
        $file_path = BSPHP_DIR_SYS . 'include/langs/' . $langs . '/' . $path . '/' . $file_name . '.php';


        //判断文件是否存在,否则获取系统 原生默认
        if (!file_exists($file_path))
        {
            $file_path = BSPHP_DIR_SYS . 'include/langs/zh-cn/' . $path . "/" . $file_name . '.php';
        }

        if (!file_exists($file_path))
            exit(bs_lib::Alerts('Error:要引入语言文件不存在', '需要引入php语言文件文件不存在。<BR/>文件路径:' . $file_path));
        $file_path_array = include $file_path;


        return $file_path_array;
    }


    /**
     * @读取配置文件 configs
     *
     *
     * 参数1=配置文件名
     * 参数2=要取出配置文件字段
     *
     */
    public static function get_configs_value($path, $value = null)
    {


        static $Array = array();

        if (isset($Array[$path]))
        {
            if (empty($value))
            {
                return $Array[$path];
            } elseif (isset($Array[$path][$value]))
            {
                return $Array[$path][$value];
            }
        }
        
        
         $File = BSPHP_DIR_SYS . 'Data/configs/' . $path . '.config.php';
         if(defined('OR_BS_SERVER')){
            $db = bs_lib::load_libbsphp_class('mysql', 'mysql');
            $sql="SELECT * FROM  `bs_php_sysconfig` WHERE  `sys_ini` =  '$path' LIMIT 1";
            $ARR=$db->Bsphp_my_array($sql);
            $Array[$path] = json_decode($ARR['sys_val'],1);
            
            
         }else{

        
        //判断文件是否存在
        if (file_exists($File))
        {
            $Array[$path] = include $File;
        } else
        {

            return '';
        }
        }


        if ($value == null)
        {
            $Array = bs_lib::stripslashes_array($Array);
            return ($Array[$path]);


        } elseif (isset($Array[$path][$value]))
        {
            $str = stripslashes($Array[$path][$value]);
            return ($str);

        } else
        {


            return '';
        }


    }


    /**
     * @加载初始化配置信息
     * 自动处理完成并载入块
     */
    public static function Include_initialization()
    {

        //载入配置文件
        require (BSPHP_DIR_SYS . 'Data/Bsmysql.Config.php');
        require (BSPHP_DIR_SYS . 'Data/Bsphp_version.php');

    }


    /**
     * @加载控制器模板Html
     *
     * 参数为空=self::$URL_C
     */
    public static function load_default_path($path = null)
    {


        //空参数判断
        if ($path == null)
            $path = self::$URL_C . '/' . self::$URL_A;


        //判断文件是否存在
        if (file_exists(self::$DIR_TEMPLET . $path . '.php'))
        {

            return (self::$DIR_TEMPLET . $path . '.php');


        } else
        {


            //判断文件是否存在 判断默认目录的
            $default = BSPHP_DIR_SYS . 'include/templates/default/' . self::$URL_M . '/' . $path . '.php';
            //echo $default;
            if (file_exists($default))
            {

                return ($default);


            } else
            {


                exit(bs_lib::Alerts('Error:Html模板不存在', '系统没有找到Html的模板. 模板路径:' . self::$DIR_TEMPLET . $path . '.php'));
            }


        }


    }


    /**
     * @系统提示
     *
     * 参数1 标题
     * 参数2 内容
     */
    public static function Alerts($table, $text)
    {
        @header('Content-Type: text/html; charset=utf-8');
        echo '<title>' . $table . '</title> 
         <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
         <div style="border: 1px dashed #cc0000;font-family:Tahoma;background-color:#FBEEEB;width:98%;padding:10px;color:#CC6600;"><strong>' . $table . '<BR/></strong><br>' . $text . '</div>';
        exit;


    }


    /**
     * @系统日志记录
     *
     * 参数1=类型
     * 参数2=内容
     * 参数3=用户名
     * 参数3=默认文字
     *
     * 参数2
     * 1=管理员登录
     * 2=用户登录
     * 3=邀请日志
     *
     * 1=正常操作
     * 2=登录成功
     * 3=密码错误
     */
    static function addappenlog($leixin, $test, $user = null)
    {


        $db = bs_lib::load_libbsphp_class('mysql', 'mysql');

        /*
        $array_log=array(
        1=>'后台登录',
        2=>'用户登录',
        3=>'邀请日志',
        4=>'代理制卡',
        5=>'佣金提成',
        6=>'执行失败',
        7=>'反破解',
        8=>'余额扣除'
        );
        */
        $array_log = array(
            1 => 'admin_login_log',
            2 => 'user_login_log',
            3 => 'yao_registration_log',
            4 => 'agent_ka_log',
            5 => 'yao_money_log',
            6 => 'exit_log',
            7 => 'od_po_log',
            8 => 'money_buy_log');


        $ip = getip();
        $date = HOST_UNIX;


        $sql = "INSERT INTO`bs_php_log`VALUES(NULL,'$leixin','$date','$ip','$test','$user');";
        return $db->Bsphp_my_query($sql);

    }


    static function Bsphp_safe($str)
    {


        if ((int)bs_lib::get_configs_value('sys', 'safe_sql') == 2)
        {
            return (0);
        }

        if ($str == null)
            return (0);
        // TODO 其他安全过滤%'EXP|NEQ|group_concat|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|FORM|NOT IN|IN|into|union'

        // 过滤查询特殊字符  union|into|and  admin
        if (preg_match('/^(|(.*)group_concat(.*)|(.*)LIKE(.*)|(.*)eval(.*)|(.*)NOTLIKE(.*)|(.*)NOT BETWEEN(.*)|(.*)NOTBETWEEN(.*)|(.*)BETWEEN(.*)|(.*)NOTIN(.*)|(.*)FORM(.*)|(.*)NOT IN(.*)|(.*)into(.*)|(.*)union(.*))$/i',
            $str, $DATA)) // if (@eregi('select|insert|update|delete|WHERE|nchar|in|union|into|and', $str))
        {
            return (1);
        }

        if ((int)bs_lib::get_configs_value('sys', 'safe_sql') == 1)
        {

        } else
        {

            if (preg_match('/select|insert|update|delete|\'|\\*|\*|\.\.\/|\.\/|union|into|load_file|outfile/i', $str))
            {
                return (1);
            }
        }

        return (0);
    }


}

?>