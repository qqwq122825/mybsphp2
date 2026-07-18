<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');

set_time_limit(0);

bs_lib::load_modules_class('admin', 'admin');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class admin_upgrade extends admin
{

    private $rc, $db, $GLOBALS_LANGS, $user_str_log, $user;

    function __construct()
    {
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');

        //$this->GLOBALS_LANGS = bs_lib::load_langs_array();
        $this->user_str_log = bs_lib::load_langs_array('user', 'user_str_log');

        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();
        // echo '控制器  继承初始化1<BR/>';
        //后台权限验证
        $this->COOKIES_KEY('B');
    }


    /**
     * @控制面板
     * 
     */
    function call_index()
    {


        //   $postArray ='[{"data":{"hello":"world"},"type":"1234","date":"2012-10-30 17:6:9","user":"000000000000000","time_stamp":1351587969902}, {"data":{"hello":"world"},"type":"1234","date":"2012-10-30 17:12:53","user":"000000000000000","time_stamp":1351588373519}]';


        $postArray = @file_get_contents('http://vip.bsphp.com/bsphp_up/bsphp_v_up_table.php?v_number=' . BSPHP_VERSION_ID . '&php_v=' . PHP_VERSION . '&tmie=' . BSPHP_VERSION_TIME . '&v=' . BSPHP_VERSION);
        $postArray = iconv('GB2312', 'UTF-8//IGNORE', $postArray); //将字符串的编码从GB2312转到UTF-8
        
        
        
        
        //$postArray = '[
        // {"filename":"20150101.zip","upurl":"http://127.0.0.1/bsphpup/","date":"2012-10-30 17:6:9","v":"7.0","number":"20150102","info":"优化功能与数据库配置。","md5":"32323232323232323232323232525654154521"},
        // {"filename":"20150103.zip","upurl":"http://127.0.0.1/bsphpup/","date":"2012-10-30 17:6:9","v":"7.0","number":"20150103","info":"优化功能与数据库配置。","md5":"32323232323232323232323232525654154521"},
        // {"filename":"20150104.zip","upurl":"http://127.0.0.1/bsphpup/","date":"2012-10-30 17:6:9","v":"7.0","number":"20150104","info":"优化功能与数据库配置。","md5":"32323232323232323232323232525654154521"}]';

        // $postArray ='';
        // echo $postArray;
        $de_json = json_decode($postArray, true);
        $count_json = count($de_json);

        
        
        
        
        include bs_lib::load_default_path();
    }


    /**
     * @控制面板 - 升级
     * 
     */
    function call_up_down()
    {


        //这里是开始升级第一步
        $postArray = file_get_contents('http://vip.bsphp.com/bsphp_up/bsphp_v_up_down.php?v_number=' . BSPHP_VERSION_ID . '&php_v=' . PHP_VERSION . '&tmie=' . BSPHP_VERSION_TIME . '&v=' . BSPHP_VERSION);
        $postArray = iconv('GB2312', 'UTF-8//IGNORE', $postArray); //将字符串的编码从GB2312转到UTF-8
        //  echo $postArray;

        $de_json = json_decode($postArray, true);


        $down = bs_lib::load_modules_class('upgrade', 'remotedownload');

        //判断最新版本 跳转到首页
        if ($de_json[0]['id'] == 'newvid')
        {

            echo '<meta http-equiv="refresh" content="0;URL=index.php?m=upgrade&c=admin_upgrade&a=index">';
            exit;
        }


        //  echo $postArray;
        //  echo PHP_VERSION;
        //读取一个当前版本的下一个版本 陆续升级
        // $postArray = '[{"filename":"20150101.zip","upurl":"http://127.0.0.1/bsphpup/","date":"2012-10-30 17:6:9","v":"7.0","number":"20150102","info":"优化功能与数据库配置。","md5":"9ca38a278f57d77c0a9f310970dc0632"}]';

        $Flie_Url = $de_json[0]['upurl'];


        //验证MD5  下载路径
        $newfname = BSPHP_DIR_SYS . 'Data/downup/zip/' . $de_json[0]['md5'] . '.zip';
        //echo $newfname;


        //建立下载目录
        dir_create(BSPHP_DIR_SYS . 'Data/downup/zip/');


        include bs_lib::load_default_path();


        //判断文件是否存在
        if (file_exists($newfname))
        {
            $filemd5 = md5_file($newfname);

            if ($filemd5 == $de_json[0]['md5'])
            {

                //如果已经下载好,直接验证文件进行安装
                //执行安装 解压
                echo "<script>md5('以下载文件正常,2秒后进入下一步！');</script>";
                echo "<script>setFileSize(100);</script>";
                echo "<script>setDownloaded(100);</script>"; //在前台显示已经下载文件大小
                echo '<meta http-equiv="refresh" content="2;URL=index.php?m=upgrade&c=admin_upgrade&a=zip">';
                exit;
            }
        }


        if ($Flie_Url == 'php_v not bad' or $Flie_Url == '0' or $Flie_Url == '')
        {
            echo "<script>setFileSize('下载地址错误！{$Flie_Url}');</script>";
            $size = 0;
            echo "<script>setFileSize($size);</script>";
        } else
        {

            //获取文件大小 显示前台

            $size = $down->get_file_size($Flie_Url);
            echo "<script>setFileSize($size);</script>";


            //文件下载
            $ok = $down->get_file_down($Flie_Url, $de_json[0]['md5']);
            if ($ok == 1)
            {


                $filemd5 = md5_file($newfname);

                if ($filemd5 != $de_json[0]['md5'])
                {
                    echo "<script>md5('文件MD5验证不通过:" . $filemd5 . "');</script>";
                } else
                {
                    echo "<script>md5('文件正常,2秒后进入下一步！');</script>";
                    //执行安装 解压
                    echo '<meta http-equiv="refresh" content="2;URL=index.php?m=upgrade&c=admin_upgrade&a=zip">';

                }


            } else
            {

                echo "<script>setFileSize('下载失败');</script>";

            }

        }
    }


    /**
     * @控制面板 - 升级
     * 
     */
    function call_zip()
    {


        //        //读取一个当前版本的下一个版本 陆续升级
        //        $postArray = '[{"filename":"20150101.zip","upurl":"http://127.0.0.1/bsphpup/","date":"2012-10-30 17:6:9","v":"7.0","number":"20150101","info":"优化功能与数据库配置。","md5":"1354588317f98ba9dcc021688c568231"}]';
        //


        //获取要升级版本文件配置信息
        $postArray = file_get_contents('http://vip.bsphp.com/bsphp_up/bsphp_v_up_down.php?v_number=' . BSPHP_VERSION_ID . '&php_v=' . PHP_VERSION . '&tmie=' . BSPHP_VERSION_TIME . '&v=' . BSPHP_VERSION);
        $postArray = iconv('GB2312', 'UTF-8//IGNORE', $postArray); //将字符串的编码从GB2312转到UTF-8

        $de_json = json_decode($postArray, true);


        //压缩包文件名位置
        $zip_file = BSPHP_DIR_SYS . 'Data/downup/zip/' . $de_json[0]['md5'] . '.zip';


        include bs_lib::load_default_path();

        //开始处理解压
        $zip = new ZipArchive;
        $res = $zip->open($zip_file);
        if ($res === true)
        {

            //解压的路径
            $destination_folder = BSPHP_DIR_SYS . 'Data/downup/updir/' . $de_json[0]['md5'];

            //建立目录解压
            dir_create($destination_folder);


            //解压缩到test文件夹
            $zip->extractTo($destination_folder);
            $zip->close();

            echo "<script>md5('文件解压完成,2秒后进入下一步！');</script>";
            echo '<meta http-equiv="refresh" content="2;URL=index.php?m=upgrade&c=admin_upgrade&a=install">';
        } else
        {
            echo '文件解压失败, code:' . $res;
            exit;
        }


    }


    /**
     * @控制面板 - 升级
     * 
     */
    function call_install()
    {


        //获取要升级版本文件配置信息
        $postArray = file_get_contents('http://vip.bsphp.com/bsphp_up/bsphp_v_up_down.php?v_number=' . BSPHP_VERSION_ID . '&php_v=' . PHP_VERSION . '&tmie=' . BSPHP_VERSION_TIME . '&v=' . BSPHP_VERSION);
        $postArray = iconv('GB2312', 'UTF-8//IGNORE', $postArray); //将字符串的编码从GB2312转到UTF-8


        $de_json = json_decode($postArray, true);


        $filedir = $de_json[0]['number'];
        //升级文件路径
        $up_dir = BSPHP_DIR_SYS . 'Data/downup/updir/' . $de_json[0]['md5'] . '/' . $filedir . '/';


        //  $bool=dir_copy($up_dir,BSPHP_DIR_SYS);
        //  echo $bool;


        include bs_lib::load_default_path();


        /**
         * @更新文件
         */
        $fromdir = dir_path($up_dir . 'file/');
        $todir = dir_path(BSPHP_DIR_SYS);
        //if (!is_dir($fromdir)) return false;

        //OK为了更新失败不跳转
        //$flie_ok=0;
        $flie_ok = 0;
        $flie_ok = dir_copys($fromdir, $todir);

        global $flie_ok;


        /**
         * @更新数据库
         */
        //结构
        $sql = @file_get_contents($up_dir . 'sql/mysql.txt');
        $sql = str_replace('bs_php_', DBQIANHUAN, $sql);
        $sqlarr = explode(";", $sql);


        //数据
        $sql = @file_get_contents($up_dir . 'sql/data.txt');
        $sql = str_replace('bs_php_', DBQIANHUAN, $sql);
        $dataarr = explode(";", $sql);
        
        
        //更新数据表
        if (count($sqlarr) > 0)
        {
            echo "<script>md5('开始更新MYSQL数据表！');</script>";
            for ($i = 0; $i < count($sqlarr) - 1; $i++)
            {
                mysql_query($sqlarr[$i]);


                echo "<script>md5('数据库更新: 执行SQL 完成！');</script>";
            }
        } else
        {
            echo "<script>md5('MYSQL数据表无需更新！');</script>";
        }
        
        
        //更新数据内容
        if (count($dataarr) > 0)
        {
            echo "<script>md5('开始更新MYSQL数据！');</script>";
            for ($i = 0; $i < count($dataarr) - 1; $i++)
            {
                mysql_query($dataarr[$i]);

                echo "<script>md5('数据库更新: 执行SQL 完成！');</script>";
            }
        } else
        {
            echo "<script>md5('MYSQL数据无需更新！');</script>";
        }


        echo "<script>md5('系统版本更新执行完成！');</script>";


        //跳转继续更新
        if ($flie_ok == 0)
        {


            /**
             * 写出配置文件
             */
            $ini = '<?php 
     
// 版本信息
define(\'BSPHP_VERSION\',\'' . $de_json[0]['v'] . '\');
//补丁版本
define(\'BSPHP_VERSION_ID\',\'' . $de_json[0]['number'] . '\');
//时间
define(\'BSPHP_VERSION_TIME\',\'' . HOST_UNIX . '\');

?>';

            $ini_file = BSPHP_DIR_SYS . 'Data/Bsphp_version.php';
            $tmp = @file_put_contents($ini_file, $ini);
            if (!$tmp)
                bs_lib::Alerts('目录无读写权限', '配置保存版本配置失败!,路径' . $ini_file . ',请把目录可写属性为0777');


            echo '<meta http-equiv="refresh" content="2;URL=index.php?m=upgrade&c=admin_upgrade&a=up_down&begin=go">';

        }

    }


}


function dir_copys($fromdir, $todir)
{

    global $flie_ok;

    $fromdir = dir_path($fromdir);
    $todir = dir_path($todir);
    if (!is_dir($fromdir))
        return false;
    if (!is_dir($todir))
        dir_create($todir);
    $list = glob($fromdir . '*');
    if (!empty($list))
    {
        foreach ($list as $v)
        {
            $path = $todir . basename($v);
            if (is_dir($v))
            {
                dir_copys($v, $path);
                $path = iconv('GB2312', 'UTF-8//IGNORE', $path);
                echo "<script>md5(\"更新:{$path}  创建目录完成！\");</script>";
            } else
            {
                //@chmod($path, 0777);

                if (new_is_writeable($path))
                {

                    copy($v, $path);
                    $path = iconv('GB2312', 'UTF-8//IGNORE', $path);
                    echo "<script>md5(\"更新:{$path}  更新文件完成！\");</script>";
                } else
                {
                    $path = iconv('GB2312', 'UTF-8//IGNORE', $path);
                    echo "<script>md5(\"更新:{$path}  <font color='red'>更新文件失败,目录不可写！</font>\");</script>";
                    $flie_ok = 1;
                }
            }
        }
    }
    return $flie_ok;
}

?>