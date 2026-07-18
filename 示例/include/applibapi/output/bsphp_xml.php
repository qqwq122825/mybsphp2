<?php

/**
 * 输出文件
 * 
 *  * 文件名_bsphp_output(显示内容)  输出显示函数
 */


/**
 * @输出给客户端mxl格式文件
 */
function bsphp_xml_bsphp_output($data)
{


    if (is_array($data))
    {

        //参数修改
        $code_array_php='';
        foreach ($data as $value => $key)
        {

            $key = addslashes($key);
            $code_array_php .= "<$value>$key</$value>
            ";


        }
        
        $data=$code_array_php;

    }


    $data = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<response>
<data>{$data}</data>
<date>" . HOST_DATE . "</date>
<unix>" . HOST_UNIX . "</unix>
<microtime>" . execute_time() . "</microtime>
<msgid>" . set_data('msgid') . "</msgid>
</response>
";


    return ($data);
}

?>