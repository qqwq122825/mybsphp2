<?php
defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
/**
 * @钩子程序
 * 
 * 钩子插件使用的函数问题
 * @param
 */


/**
 * 参数1=HOOKS名称
 * 参数1=传送参数
 */
function BsRun_Plug($Run_Name, $param)
{
 
    
    $File = BSPHP_DIR_SYS . 'include/hooks/'.$Run_Name.'_'.'*.hooks.php';

    $File_ARRAY = glob($File);
    if(is_array($File_ARRAY)){
    foreach ($File_ARRAY as $filename)
    {
        if (!file_exists($filename))
            exit(bs_lib::Alerts('Error:需要载入的Hooks文件不存在', '需要引入php脚步文件不存在。<BR/>文件路径:' . $filename));

        include ($filename);
        
        $fnuname=get_filename($filename);
        $fnuname = str_replace('.hooks.php', '', $fnuname);
        

        
        call_user_func($fnuname, $param);
        
        

    }
    
    }


}

?>