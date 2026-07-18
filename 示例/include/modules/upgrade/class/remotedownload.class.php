<?php

/**
 * @远程下载文件
 * 
 * 下载文件到服务器
 * 
 * 2015.3.5
 * 
 * BY xiaozhou
 */
class remotedownload
{


    /**
     * @远程获取文件大小
     * 
     */
    function get_file_size($fileurl)
    {
        // 获取文件大小
        $filesize = -1;
        $headers = get_headers($fileurl, 1);
        if ((!array_key_exists("Content-Length", $headers)))
        {
            $filesize = 0;
        }
        $filesize = $headers["Content-Length"];


        return ($filesize);
    }


    /**
     * @远程下载文件
     * 
     */
    function get_file_down($url,$fileSS)
    {
        
       

        $file = fopen($url, "rb");
        if ($file)
        {

            $newfname = BSPHP_DIR_SYS . 'Data/downup/zip/'. $fileSS . '.zip';
            $newf = fopen($newfname, "wb");
            $downlen = 0;
            if ($newf)
                while (!feof($file))
                {
                    $data = fread($file, 1024 * 8); //默认获取8K
                    $downlen += strlen($data); // 累计已经下载的字节数
                    fwrite($newf, $data, 1024 * 8);
                    echo "<script>setDownloaded($downlen);</script>"; //在前台显示已经下载文件大小
                    ob_flush();
                    flush();
                }
         
         
          fclose($file);   
           return 1;    
        }else{
            
            return -1;
        }
        


    }


}

?>