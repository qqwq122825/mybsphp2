<?php

//header("content-type:text/html; char=utf8");

/**
 * @文件说明
 * AppEn: 200
 * 文件用途范围:本文件只做数据接收配置.其他不做使用
 * 
 */


/**
 * *************************
 * @API别名解密
 * 
 * 参数=API别名
 * 
 * 
 * *************************
 */
function bsphp_api($API)
{
    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');


  
    global $Global_DB_AppDaTa_Array;
    

    if ($Global_DB_AppDaTa_Array['app_api_pwd'] == '' or $Global_DB_AppDaTa_Array['app_api_pwd'] == '0')
    {
        return $API;

    }
    
    
   

    if (strstr($API, '.')==true)PrInfo('API接口错误 错误码:BSPHP_940011');
        

    $arr = array(
        '|MH171C' => '=',
        '|H370J' => '+',
        '|TJS7E' => '/');
    $apiname = strtr($API, $arr);


    //$apiname= str_replace('|MH171C', '=', $API);
    //$apiname= str_replace('|H370J', '+', $apiname);
    //$apiname= str_replace('|TJS7E', '/', $apiname);


    //PrInfo($apiname);
    $apiname = des_mdecrypt($apiname, $Global_DB_AppDaTa_Array['app_api_pwd']);


    return $apiname;

}


/**
 * *************************
 * @系统输出显示函数
 * 
 * 系统所有输出都会经过这才会显示,如果要将输出加密的话只要在这NUN一下就可以了。
 * 
 * 输出方式 停止后输出
 * *************************
 */
function PrInfo($data)
{

    //清理缓存输出包括错误信息
    if (bs_lib::get_configs_value('sys', 'error_code') == 0)
    {
       ob_end_clean(); //清理之前输出缓存 
    }

    //-1将不加密回送

    
    //输出格式
    if (app_print_fun != 'notfun_bsphp_output')
    {

        $data = call_user_func(app_print_fun, $data);
    }

     //加密
    if (app_show_encryption != 'notfun_bsphp_mencrypt')
    {

        $data = call_user_func(app_show_encryption, $data, HOST_GET_PWD);
    }

   
    exit($data);
}


/**
 * **************************
 * @取得参数数值
 * 
 * 这里获取只获取接收数据包参数
 * **************************
 */
function set_data($srt)
{
    global $Global_Url_DaTa_Array;
    isset($Global_Url_DaTa_Array[$srt]) ? $a = $Global_Url_DaTa_Array[$srt] : $a = null;
    $a = trim($a);
    unset($Global_Url_DaTa_Array);
    return $a;
}


/**
 * ************************
 * @系统提示输出
 * 
 * 输出系统级调速
 * ************************
 */
function PrLog($id)
{
    $AppEnArrLog = bs_lib::load_langs_array('applib', 'appen_str_log');
    $a = $AppEnArrLog[$id];

    PrInfo($a);
}


/**
 * *****************************
 * @数据包接收
 * 
 * 接收参数变量名：parameter
 * *****************************
 */
function GetUrlData()
{

    $parameter = 'parameter';

    if (HOST_GET != 0)
    {

        /////////////////////////////////////////////
        /*POST方式接收参数*/
        if (isset($_POST[$parameter]))
        {

            $data = rawurldecode($_POST[$parameter]);

        } else
        {
            PrLog(5001);
            return false;
        }
    } else
    {
        ///////////////////////////////////////////////
        /*GET方式接收参数*/

        if (isset($_GET[$parameter]))
        {
            $data = rawurldecode($_GET[$parameter]);

        } else
        {
            PrLog(5000);
            return false;
        }


    }


    //////////////////////////////////////////////////

    ///数据解密可以在这里调用 $data = 数据


    /*********************
    ********数据解密******
    **********************
    **********************
    */
    // $data=PrintInfo_R($data);



    //////////////////////////////////////////////////
    //die($data);

    //检测非UTF8 转码
    $data=goto_utf8($data);
    if(is_array($data))PrInfo('意外的编码格式：'.$data[1].' 错误码:BSPHP_9500331');
    
    

    if (app_get_encryption != 'notfun_bsphp_mdecrypt')
    {

        $data = call_user_func(app_get_encryption, $data, HOST_GET_PWD);
    }


    //客户端加密时候不知道是什么编码所以在检测一次
    //检测非UTF8 转码
    $data=goto_utf8($data);
    if(is_array($data))PrInfo('意外的编码格式：'.$data[1].' 错误码:BSPHP_9500332');


    return $data;
}


/**
 * **************************************
 * @解析得到的数据包
 * 
 * 数据包必须是先解密之后的
 * 数据包验证同时也会判断屏蔽字符
 * 
 * 后面的 foreach 循环是屏蔽关键字的
 * parse_str 解析传输过来的URL &参数
 * **************************************
 */
function Parse_Date($data)
{
    //数组化URL
    parse_str($data, $array);
    //循环判断屏蔽关键字符
    foreach ($array as $value)
    {
        //     if(Antiimplantation($value)){
        //
        //        PrInfo('5002,'.$value);
        //        /////////////////////////////
        //               /*出现屏蔽字符*/
        //               //appen_log(1);
        //     PrLog(5002);
        //     }

    }
    return $array;
}


/**
 * **********************************
 * @判断数据超时选择
 * 
 * HOST_CAOSHI=0 关闭超时
 * 
 * 无需传递任何参数值需要执行API
 * **********************************
 */

function dateChaoshi()
{
    if (HOST_CAOSHI != 0)
    {
        //转换好时间格式
        $date = set_data('date');
        $date = str_replace('#', ' ', $date); //时间空忽略#代替
        $date = (int)strtotime((string )$date); //strtotime转换unix时间

        //$date = date('Y-m-d H:i:s');


        $chaoshi_time = HOST_UNIX - $date; //时差
        $s = 0 - HOST_CAOSHI;

        // die(HOST_UNIX.'SSS'.$chaoshi_time);


        //100
        if ($chaoshi_time >= 0 and $chaoshi_time < HOST_CAOSHI and $chaoshi_time > $s)
        {

            //-100
        } elseif ($chaoshi_time <= 0 and $chaoshi_time < HOST_CAOSHI and $chaoshi_time > $s)
        {


        } else
        {
            //  die(HOST_UNIX.'|'.$chaoshi_time.'|'.$s);
            PrLog(5003); //数据包已经过期,拒绝接收
        }


    }


}


/**
 * *********************************
 * 
 * @读取软件连接软件
 * 
 * 连接方式通过软件代号进行读取
 * 
 * 如果软件部存在返回假
 * 存在返回数据库数组
 * **********************************
 */
function GetAppInfo($daihao)
{
    $db = bs_lib::load_libbsphp_class('mysql', 'mysql');
    $arr = $db->my_class_array('bs_php_appinfo', 'app_daihao', $daihao, ' * ');
    unset($db);
    if ($arr)
    {
        return $arr;
    } else
    {
        //DIE('MW软件连接号错误,访问软件不存在');
        PrLog(5005); //软件连接号错误,访问软件不存在
    }
    return false;
}

?>