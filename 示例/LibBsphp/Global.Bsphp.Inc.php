<?php

/**
 * @Bsphp入口
 * 文件名称:Global.Bsphp.Inc.php
 * 功能:全局入口点
 * 时间:2015.1.4
 * 官网:www.bsphp.com
 * 
 * 载入位置:功能入口点 admin/index.php /index.php  /appen.php
 */

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');


// 系统常量定义 路径 内部路径
defined('BSPHP_DIR_LIB') or define('BSPHP_DIR_LIB', str_replace('\\', '/', __dir__ ) . '/'); //模块LibBsphp目录
defined('BSPHP_DIR_SYS') or define('BSPHP_DIR_SYS', str_replace('LibBsphp/', '', BSPHP_DIR_LIB)); //系统目录
defined('BSPHP_DIR_OPEN') or define('BSPHP_DIR_OPEN', dirname($_SERVER['SCRIPT_FILENAME'])); //当前目录URL
defined('BSPHP_DIR_DATA') or define('BSPHP_DIR_DATA', BSPHP_DIR_SYS . 'Data/'); //配置目录
//系统开始时间
define('BSPHP_SYS_START_TIME', microtime());


/**
 * @加载预定义文件
 * 
 * 这里加载的是核心加载文件,所有基本核心功能都在此文件!
 */
include_once (BSPHP_DIR_LIB . '/class/lib.class.php');


/**
 * @初始化配置文件
 * 
 * 
 * 设置系统基础配置 模板 时间 等
 */
bs_lib::Include_initialization();




/**
 * @初始化配置文件
 * 
 * 这里加载系统核心函数库函数 空加载全部的.function.php
 */
bs_lib::load_libbsphp_common();






/**
 * @全局统一时间
 */
date_default_timezone_set(bs_lib::get_configs_value("sys", "timezone"));
define('HOST_UNIX', strtotime(date('Y-m-d H:i:s')) + bs_lib::get_configs_value("sys", "time_add"));
define('HOST_DATE', date('Y-m-d H:i:s', HOST_UNIX));
define('Bsphp_HOST_SRE', base64_decode("aHR0cDovL01hbmFnZW1lbnQuQnNwaHAuY29tL0F1dGhvcml6ZTIwMTgv")); //授权验证URL




//判断开启调试模式
if (bs_lib::get_configs_value('sys', 'error_code') == 1)
{

    error_reporting(E_ALL);
    ini_set("display_errors", "On"); //显示所有错误信息*/

} else
{

    error_reporting(E_ALL & ~ E_NOTICE);
    ini_set("display_errors", "Off"); //屏蔽所有错误信息

}





//IP
if (bs_lib::get_configs_value('sys', 'stop_ip') != '')
{
    if (preg_match("|" . bs_lib::get_configs_value('sys', 'stop_ip') . "|is", getip()))
    {
        bs_lib::Alerts('禁止的IP访问', "你IP已经被禁止访问,有疑问请联系管理人员" . getip());
        exit;
    }
}

//hooks
BsRun_Plug('GlobalBsphp_Begin', 0);


/**
 * @模块加载入口
 * 
 * 系统m c a 模块入口 m模块 c控制器 a调用函数接口
 * 
 * NOTMODE=不需要载入控制器模块入口
 */
if (BSPHP_SET != 'NOTMODE')
    bs_lib::Include_Modules();


//hooks
BsRun_Plug('GlobalBsphp_End', 0);

?>