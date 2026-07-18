<?php
defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
/**
 * @模板输出目录
 * 
 * 本目录下函数全局通用
 */


/**
 * @获取模板路径
 * 
 * 参数=模板目录名称 空=获取系统当前默认
 */
function get_url_statics($path = null)
{
    
    
    if(BSPHP_SET=='ADMIN'){
        
        $url='../';
    }else{
        
        $url=bs_lib::get_configs_value('sys', 'url');
    }

    return ($url . 'statics/default/' . strtolower(BSPHP_SET) . '/');
}


/**
 * @语言目录
 * 
 * 参数=获取静态目录
 */
function get_url___()
{
    return (bs_lib::get_configs_value('sys', 'url') . 'statics/');
}


/**
 * @获取模板路径
 * 
 * 参数=模板文件名
 */
function load_pach_templet($file_name)
{

    return (BSPHP_DIR_SYS . 'include/templates/' . bs_lib::get_configs_value('sys','user_template') . '/user/index/' . $file_name . '.php');
}


/**
 * @单选框选择
 * 
 * 本地配置库有效
 */
function echo_checkbox_checked($pach, $name, $value = 1, $un = false)
{


    if ($pach == '-1')
    {

        if ($name == $value)
        {
            if ($un)
                return (' checked ');
            echo ' checked ';
        } else
        {
            if ($un)
                return (' ');
            echo ' ';
        }


    } else
    {

        if (bs_lib::get_configs_value($pach, $name) == $value)
        {
            if ($un)
                return (' checked ');
            echo ' checked ';
        } else
        {
            if ($un)
                return (' ');
            echo ' ';
        }


    }


}

?>