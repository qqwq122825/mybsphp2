<?php

/**
 * 输出文件
 * 
 *  * 文件名_bsphp_output(显示内容)  输出显示函数
 */


/**
 * @输出给客户端mxl格式文件
 */
function bsphp_josn_bsphp_output($data)
{


    if (is_array($data))
    {

        //参数修改
        $code_array_php='';
        foreach ($data as $value => $key)
        {

            $key = addslashes($key);
            $code_array_php .= "\"$value\":\"$key\",";


        }
        
        $data=$code_array_php.'"Bsphp":"NULL"';

    }




    $data = '{"response":{"data":"'.$data.'","date":"'.HOST_DATE.'","unix":"'.HOST_UNIX.'","microtime":"'.execute_time().'"}}';
    


    return ($data);
}

?>