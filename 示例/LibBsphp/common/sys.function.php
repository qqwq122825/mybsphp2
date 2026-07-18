<?php
defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
/**
 * @判断验证码对错
 * 
 * 参数 验证码
 */
function push_cood_imges($cood)
{
    $cood = strtolower($cood);
    if (empty($cood))
        return 1036; //判断验证码不能为
    $cood = MD5($cood); //MD5加密验证码
    $cood_md5 = get_session_value('coode_imges_md5');
    #DIE($cood.'|'.$cood_md5);
    set_session_value('coode_imges_md5', 'not');
    set_session_value('coode_imges', 'not');

    if ($cood == $cood_md5)
    {

        return 1037; //验证码正确
    } else
    {
        return 1038; //验证码错误

    }

}

/**
 * 程序执行时间
 *
 * @return	int	单位ms
 */
function execute_time()
{
    $stime = explode(' ', BSPHP_SYS_START_TIME);
    $etime = explode(' ', microtime());
    return number_format(($etime[1] + $etime[0] - $stime[1] - $stime[0]), 6);
}


/**
 * @返回多维数组 key
 */
function runarraykey($array)
{

    $a = array();
    foreach ($array as $k => $v)
    {

        $a[$k] = null;

    }
    return ($a);
}


/**
 * @IP
 */
function ip($ip)
{

    $ip_class = bs_lib::load_libbsphp_class('ip', 'ip');

    return $ip_class->show($ip); //验证码错误

}


/**
 * @表达式匹配
 * 
 * 参数1=数据
 * 参数2=类型 
 *  
 * 1=匹配中文+字母+数字+下划线   
 * 2=QQ号匹配 
 * 3=邮箱匹配 
 * 4=手机号码 
 * 5=匹配文件
 * 6=匹配中文+下划线
 * 7=匹配中文+字母+数字+下划线
 */
function PiPei($data, $id)
{
    switch ($id)
    {
        case 1:
            $ifno = '^([^\x00-\xff]|[a-zA-Z0-9])([^\x00-\xff]|[a-zA-Z0-9_])+([^\x00-\xff]|[a-zA-Z0-9])$'; //匹配中文+字母+数字+下划线
            break;
        case 2:
            $ifno = '[1-9][0-9]{4,}';
            break;
        case 3:
            $ifno = '(.*)@(.*)\.(.*)';
            break;
        case 4:
            $ifno = '[0-9]{11,}';
            break;
        case 5:
            $ifno = '(.*)\.(.*)';
            break;
        case 6:
            $ifno = '^([^\x00-\xff]|[a-zA-Z0-9_])$'; //匹配中文+下划线
            break;
        case 7:
            $ifno = '([a-z0-9_\-]+)'; //匹配中文+字母+数字+下划线
            break;

    }

    return @eregi($ifno, $data);
}


/**
 * @是否存在空格
 * 
 * 检测输入内容是否存在空格
 */
function set32($data)
{
    return stripos($data, ' ');

}


/**
 *
 * @设置COOKIE值
 * 整个站点的COOKIE都会再这里调用
 * 好让可以换其他方法验证
 */
function set_session_value($ini, $value)
{
    $_SESSION[bs_lib::get_configs_value("sys", "cookie_pre") . $ini] = $value;
}


/**
 *
 *  @取COOKIE值
 * 上面设置有了也要取方法意思一样
 */
function get_session_value($ini)
{

    if (isset($_SESSION[bs_lib::get_configs_value("sys", "cookie_pre") . $ini]))
    {
        return $_SESSION[bs_lib::get_configs_value("sys", "cookie_pre") . $ini];
    } else
    {
        return null;
    }

}


/**
 * @信息框系统框
 * 默认返回前一页
 */
function alert($NAME)
{
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo "<script language=javascript>alert('" . $NAME . "');</script>";
    echo '<script> var url =document.referrer; window.location=url; </script> ';
    exit;
}

/**
 * @信息框系统框
 * 
 * 不会返回
 */
function alert2($NAME)
{
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo "<script language=javascript>alert('" . $NAME . "');</script>";

}


/**
 * 
 * @程序跳转URL 
 * 
 * 代替PHP 用html跳转最佳不过了
 * 
 */
function location($api_url)
{
    echo '<meta http-equiv="Refresh" target="_blank"  content="0;URL=' . $api_url . '" />';
    exit;

}


/**
 * @最顶层窗口打开url
 */
function locationindex()
{
    echo "<script>if(self!=top){parent.location.href='index.php';}</script>";
    exit;

}


/**
 * 下面2个参数接收整站的POST和GET传递过来的参数
 * 参数下面加字符过滤,整站要屏蔽用户传递参数只要在这里设置一下就可以了
 */
function set_post($deta = null)
{


    if (isset($_POST[$deta]))
    {
        return trim($_POST[$deta]);
    } else
    {
        if (is_array($_POST) and $deta == null)
        {

            return $_POST;
        } else
        {

            return '';
        }


    }

}

function set_get($deta = null)
{


    if (isset($_GET[$deta]))
    {
        return trim($_GET[$deta]);
    } else
    {


        if (is_array($_GET) and $deta == null)
        {

            return $_GET;
        } else
        {

            return '';
        }


    }

}




/**
 * *******************************
 * 获取随机数
 * 
 * 参数1=随机数长度
 * 
 */
function gte_rand_str($int)
{

    /**********取随机数字数组**************/
    $check_list = array(
        'q',
        'w',
        'e',
        'r',
        't',
        'y',
        'u',
        'i',
        'o',
        'p',
        'l',
        's',
        'd',
        'f',
        'g',
        'h',
        'j',
        'k',
        'z',
        'z',
        'x',
        'c',
        'v',
        'b',
        'n',
        'm',
        'Q',
        'W',
        'E',
        'R',
        'T',
        'Y',
        'U',
        'I',
        'I',
        'P',
        'A',
        'S',
        'D',
        'F',
        'G',
        'H',
        'J',
        'K',
        'L',
        'Z',
        'X',
        'C',
        'V',
        'B',
        'N',
        'M',
        '1',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
        '0');

    $test = '';
    shuffle($check_list); //打乱
    $ki = 0;
    while ($ki < $int)
    {
        $test .= $check_list[mt_rand(1, 61)];
        $ki++;
    }
    return $test;

}




/**
 * 安全过滤函数 
 * @param $string 要过滤的字符串 
 * @return string 返回处理过的字符串 
 */
function safe_replace($string)
{
    $string = str_replace('%20', '', $string);
    $string = str_replace('%27', '', $string);
    $string = str_replace('%2527', '', $string);
    $string = str_replace('*', '', $string);
    $string = str_replace('"', '&quot;', $string);
    $string = str_replace("'", '', $string);
    $string = str_replace('"', '', $string);
    $string = str_replace(';', '', $string);
    $string = str_replace('<', '&lt;', $string);
    $string = str_replace('>', '&gt;', $string);
    $string = str_replace("{", '', $string);
    $string = str_replace('}', '', $string);
    $string = str_replace('/', '', $string);
    $string = str_replace('\\', '', $string);
    return $string;
}


/**
 * 获取当前页面完整URL地址
 */
function get_url()
{
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
    $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
    $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
    
    
    return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
}


/**
 * @加载控制器模板Html
 * 
 * 参数为空=self::$URL_C
 */
function Include_defaultb($default_name = null)
{
    if ($default_name == null)
        $default_name = bs_Lib::$URL_A;


    if (is_exists(bs_Lib::$DIR_TEMPLET . $default_name . '.php'))
    {
        include (bs_Lib::$DIR_TEMPLET . $default_name . '.php');

    } else
    {

        exit(bs_Lib::Alerts('Error:Html模板不存在', '系统没有找到Html的模板. 模板路径:' . bs_Lib::$DIR_TEMPLET . $default_name . '.php'));
    }


}


/**
 * 文件下载
 * @param $filepath 文件路径
 * @param $filename 文件名称
 */

function file_down($filepath, $filename = '')
{
    if (!$filename)
        $filename = basename($filepath);
    if (is_ie())
        $filename = rawurlencode($filename);
    $filetype = fileext($filename);
    $filesize = sprintf("%u", filesize($filepath));
    if (ob_get_length() !== false)
        @ob_end_clean();
    header('Pragma: public');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Cache-Control: pre-check=0, post-check=0, max-age=0');
    header('Content-Transfer-Encoding: binary');
    header('Content-Encoding: none');
    header('Content-type: ' . $filetype);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-length: ' . $filesize);
    readfile($filepath);
    exit;
}

/**
 *  获取ip
 */
function getip()
{
    if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown'))
    {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown'))
    {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown'))
    {
        $ip = getenv('REMOTE_ADDR');
    } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown'))
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : '';
}


/**
 * 取得文件扩展
 *
 * @param $filename 文件名
 * @return 扩展名
 */
function get_fileext($filename)
{
    return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
}


/**
 * 取得文件扩展
 *
 * @param $filename 文件名
 * @return 扩展名
 */
function get_filename($filename)
{


    return str_replace('/', '', trim(strrchr($filename, '/')));


}

/**
 * @转码UTF-8
 */
function goto_utf8($data)
{
    $encode = mb_detect_encoding($data, array(
        'GB2312',
        'GBK',
        'UTF-8'));

    if ($encode == "GB2312")
    {
        $data = iconv("GBK", "UTF-8", $data);
    } elseif ($encode == "GBK" )
    {
        $data = iconv("GBK", "UTF-8", $data);
    } elseif ($encode == "EUC-CN")
    {
        $data = iconv("GBK", "UTF-8", $data);
    } elseif ($encode == "UTF-8")
    {
        //本来就是UT8就不足处理了
    }elseif ($encode == 'CP936')
    {
        //本来就是UT8就不足处理了
    } else
    {
      
       return array('-1',$encode);
       
    }
   return($data);
}

/**
 * Function dataformat
 * 时间转换
 * @param $n INT时间
 */
function dataformat($n)
{
    $hours = floor($n / 3600);
    $minite = floor($n % 3600 / 60);
    $secend = floor($n % 3600 % 60);
    $minite = $minite < 10 ? "0" . $minite : $minite;
    $secend = $secend < 10 ? "0" . $secend : $secend;
    if ($n >= 3600)
    {
        return $hours . ":" . $minite . ":" . $secend;
    } else
    {
        return $minite . ":" . $secend;
    }

}

/**
 * 分割文本
 * @param $str 分割字符串
 * @param $z 分割字符串
 * @return 返回数组
 */
function id_decode($str, $z)
{
    return explode($z, $id);
}


/**
 * 生成随机字符串
 * @param string $lenth 长度
 * @return string 字符串
 */
function create_randomstr($lenth = 6)
{
    return random($lenth, '123456789bcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ');
}


/**
 * 检测输入中是否含有错误字符
 *
 * @param char $string 要检查的字符串名称
 * @return TRUE or FALSE
 */
function is_badword($string)
{
    $badwords = array(
        "\\",
        '&',
        ' ',
        "'",
        '"',
        '/',
        '*',
        ',',
        '<',
        '>',
        "\r",
        "\t",
        "\n",
        "#");
    foreach ($badwords as $value)
    {
        if (strpos($string, $value) !== false)
        {
            return true;
        }
    }
    return false;
}



/**
 * 
 * @COOKIE加密
 * MD7主要用于用户登录加密COOIE
 * 
 */
function cookies_md7($md7)
{
    return md5(bs_lib::get_configs_value("sys", "cook_key") . $md7 . bs_lib::get_configs_value("sys", "cook_key"));
}



/**
 * 
 * @COOKIE加密
 * MD7主要用于用户登录加密COOIE
 * 
 */
function password_md6($md7)
{
    return md5(bs_lib::get_configs_value("sys", "pass_key") . $md7 . bs_lib::get_configs_value("sys", "pass_key"));
}


/**
 * @时间加减并显示天数
 * 
 * 参数1=显示式时间
 * 
 * 
 * 如果$date小于当前时间返回1 否则返回年到天计算时间
 * 
 */
function show_Time_Day($date)
{


    if (stripos($date, '-') > 0)
    {
        $time = strtotime($date);
        $time = $time - HOST_UNIX;
    } else
    {
        $time = $date-time();
    }


    if ($time == 0)
    {

        return '刚刚';

    } elseif ($time < 60 and $time > 0)
    {

        return (int)$time . '秒后';

    } elseif ($time < 3600 and $time > 0)
    {

        $time = (int)$time / 60;
        return (int)$time . '分钟后';

    } elseif ($time < 86400 and $time > 0)
    {

        $time = (int)$time / 3600;
        return (int)$time . '小时后';

    } elseif ($time < 2678400 and $time > 0)
    {

        $time = $time / 86400;
        return (int)$time . '天后';

    } elseif ($time < 31536000 and $time > 0)
    {

        $time = $time / 2678400;
        return (int)$time . '月后';

    } elseif ($time > 31536000 and $time > 0)
    {


        $time = $time / 31536000;
        return (int)$time . '年后';
    } else
    {


        //前

        $tejia = $time * 2;
        //因为-数所以弄成正数
        $time = $time - $tejia;

        if ($time == 0)
        {

            return '刚刚';

        } elseif ($time < 60 and $time > 0)
        {

            return (int)$time . '秒前';

        } elseif ($time < 3600 and $time > 0)
        {

            $time = (int)$time / 60;
            return (int)$time . '分钟前';

        } elseif ($time < 86400 and $time > 0)
        {

            $time = (int)$time / 3600;
            return (int)$time . '小时前';

        } elseif ($time < 2678400 and $time > 0)
        {

            $time = $time / 86400;
            return (int)$time . '天前';

        } elseif ($time < 31536000 and $time > 0)
        {

            $time = $time / 2678400;
            return (int)$time . '月前';

        } elseif ($time > 31536000 and $time > 0)
        {


            $time = $time / 31536000;
            return (int)$time . '年前';
        }


    }


    return $time;

}


/**
 * @授权解密
 * 
 */
function Bsphp_decrypt($txt, $key = '01084dc3479e789a029e5f5b9803b069')
{


    $txt = str_replace(' ', '', $txt);
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
    $ikey = "-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
    $knum = 0;
    $i = 0;
    $tlen = strlen($txt);
    while (isset($key{$i}))
        $knum += ord($key{$i++});
    $ch1 = $txt{$knum % $tlen};
    $nh1 = strpos($chars, $ch1);
    $txt = substr_replace($txt, '', $knum % $tlen--, 1);
    $ch2 = $txt{$nh1 % $tlen};
    $nh2 = strpos($chars, $ch2);
    $txt = substr_replace($txt, '', $nh1 % $tlen--, 1);
    $ch3 = $txt{$nh2 % $tlen};
    $nh3 = strpos($chars, $ch3);
    $txt = substr_replace($txt, '', $nh2 % $tlen--, 1);
    $nhnum = $nh1 + $nh2 + $nh3;
    $mdKey = substr(md5(md5(md5($key . $ch1) . $ch2 . $ikey) . $ch3), $nhnum % 8, $knum % 8 + 16);
    $tmp = '';
    $j = 0;
    $k = 0;
    $tlen = strlen($txt);
    $klen = strlen($mdKey);
    for ($i = 0; $i < $tlen; $i++)
    {
        $k = $k == $klen ? 0 : $k;
        $j = strpos($chars, $txt{$i}) - $nhnum - ord($mdKey{$k++});
        while ($j < 0)
            $j += 64;
        $tmp .= $chars{$j};
    }
    $tmp = str_replace(array(
        '-',
        '_',
        '.'), array(
        '+',
        '/',
        '='), $tmp);
    return base64_decode($tmp);

    //return open_code('ICAgICAgICAkdHh0PXN0cl9yZXBsYWNlKCcgJywnJywkdHh0KTsKICAgICAgICAkY2hhcnMgPSAiQUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVphYmNkZWZnaGlqa2xtbm9wcXJzdHV2d3h5ejAxMjM0NTY3ODktXy4iOwogICAgICAgICRpa2V5ID0iLXg2ZzZaV20yRzlnX3ZyMEJvLnBPcTNrUkl4c1o2cm0iOwogICAgICAgICRrbnVtID0gMDskaSA9IDA7CiAgICAgICAgJHRsZW4gPSBzdHJsZW4oJHR4dCk7CiAgICAgICAgd2hpbGUoaXNzZXQoJGtleXskaX0pKSAka251bSArPW9yZCgka2V5eyRpKyt9KTsKICAgICAgICAkY2gxID0gJHR4dHska251bSAlICR0bGVufTsKICAgICAgICAkbmgxID0gc3RycG9zKCRjaGFycywkY2gxKTsgCiAgICAgICAgJHR4dCA9IHN1YnN0cl9yZXBsYWNlKCR0eHQsJycsJGtudW0gJSAkdGxlbi0tLDEpOwogICAgICAgICRjaDIgPSAkdHh0eyRuaDEgJSAkdGxlbn07CiAgICAgICAgJG5oMiA9IHN0cnBvcygkY2hhcnMsJGNoMik7CiAgICAgICAgJHR4dCA9IHN1YnN0cl9yZXBsYWNlKCR0eHQsJycsJG5oMSAlICR0bGVuLS0sMSk7CiAgICAgICAgJGNoMyA9ICR0eHR7JG5oMiAlICR0bGVufTsKICAgICAgICAkbmgzID0gc3RycG9zKCRjaGFycywkY2gzKTsKICAgICAgICAkdHh0ID0gc3Vic3RyX3JlcGxhY2UoJHR4dCwnJywkbmgyICUgJHRsZW4tLSwxKTsKICAgICAgICAkbmhudW0gPSAkbmgxICsgJG5oMiArICRuaDM7CiAgICAgICAgJG1kS2V5ID0gc3Vic3RyKG1kNShtZDUobWQ1KCRrZXkuJGNoMSkuJGNoMi4kaWtleSkuJGNoMyksJG5obnVtICUgOCwka251bSAlIDggKyAxNik7CiAgICAgICAgJHRtcCA9ICcnOwogICAgICAgICRqPTA7ICRrID0gMDsKICAgICAgICAkdGxlbiA9IHN0cmxlbigkdHh0KTsKICAgICAgICAka2xlbiA9IHN0cmxlbigkbWRLZXkpOwogICAgICAgIGZvciAoJGk9MDsgJGk8JHRsZW47ICRpKyspIHsKICAgICAgICAgICAgJGsgPSAkayA9PSAka2xlbiA/IDAgOiAkazsKICAgICAgICAgICAgJGogPSBzdHJwb3MoJGNoYXJzLCR0eHR7JGl9KS0kbmhudW0gLSBvcmQoJG1kS2V5eyRrKyt9KTsKICAgICAgICAgICAgd2hpbGUgKCRqPDApICRqKz02NDsKICAgICAgICAgICAgJHRtcCAuPSAkY2hhcnN7JGp9OwogICAgICAgIH0KICAgICAgICAkdG1wID0gc3RyX3JlcGxhY2UoYXJyYXkoJy0nLCdfJywnLicpLGFycmF5KCcrJywnLycsJz0nKSwkdG1wKTsKICAgICAgICByZXR1cm4gYmFzZTY0X2RlY29kZSgkdG1wKTs=');


}


//打开枚举目录
function open_list_dir($pach)
{

    $while_id = 0;
    $array = array();
    $handle = opendir($pach); //OPEN

    while ($FileName = readdir($handle))
    {

        if (strstr($FileName, 'private-') == false and $FileName != '.' and $FileName != '..')
        {

            $array[] = $FileName;
        }


    }
    //关闭目录
    closedir($handle);

    return ($array);
}


/**
 * 忽悠人函数 事实上是要执行 eval
 */
function open_code($code)
{

    return  @eval(base64_decode($code));

}


/**
 * 显示时间的函数 剩余的使用
 */
 function gettime($time)
{
    if($time<=0){
        
      return '已经到期';  
        
    }
    
    
 
    $strtime='';
      if ($time >= 31536000)
    {
        $strtime .= intval($time / 31536000) . '年';
        $time = $time % 31536000;
    } else
    {
        $strtime .= '';
    }  
    if ($time >= 2678400)
    {
        $strtime .= intval($time / 2678400) . '月';
        $time = $time % 2678400;
    } else
    {
        $strtime .= '';
    }
    
    
    if ($time >= 86400)
    {
        $strtime .= intval($time / 86400) . '天';
        $time = $time % 86400;
    } else
    {
        $strtime .= '';
    }
 
    if ($time >= 3600)
    {
        $strtime .= intval($time / 3600) . '小时';
        $time = $time % 3600;
    } else
    {
        $strtime .= '';
    }
    
    
    if ($time >= 60)
    {
        $strtime .= intval($time / 60) . '分钟';
        $time = $time % 60;
    } else
    {
        $strtime .= '';
    }
    if ($time > 0)
    {
        $strtime .= intval($time) . '秒';
    } else
    {
        $strtime = "时间错误";
    }
    return $strtime;
}

?>