<?php

/**
 * 文件说明
 * 
 * 由于需要多个版英文中文,这个文件用做界面引擎的
 * 部分
 * Bsphp_index.html
 * Bsphp_index.html
 * 
 */


/**
 * 生成引擎
 * 
 * $uesr_user=用户编号ID 从cookie过来的
 */


class template
{

    private $rc, $db, $GLOBALS_LANGS, $user_str_log, $user, $seesion_uid;

    function __construct()
    {
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->GLOBALS_LANGS = bs_lib::load_langs_array();
    }


    /**
     * @取得当前URL路径
     * 
     * 用了协助信息的返回
     */
    function url()
    {
        $pageURL = 'http';
        if (@$_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }


    function print_Bsphpstyle($skn)
    {
        global $uid, $appen_web_img_login, $db;


        $url_file = BSPHP_DIR_SYS . 'include/templates/' . bs_lib::get_configs_value('sys',
            'cms_template') . '/index/cms_skn';
        $url_file_inc = BSPHP_DIR_SYS . 'include/templates/' . bs_lib::
            get_configs_value('sys', 'cms_template') . '/index/cms_inc';


        $File = $url_file . '/Bsphp_' . $skn . '.html';
        if (!file_exists($File)) {
            if (bs_lib::get_configs_value('sys', 'error_code') == 1) {
                exit(bs_lib::Alerts('Error:要引入模板文件不存在', '需要引入模板文件不存在。<BR/>文件路径:' . $File));
            } else {
                exit(bs_lib::Alerts('Error:要引入模板文件不存在', '需要引入模板文件不存在。<BR/>文件路径:后台开启调试模式'));

            }


        }
        $test = file_get_contents($File); //载入模版


        $File = $url_file . '/Bsphp_head.html'; //载入顶部
        if (!file_exists($File)) {
            if (bs_lib::get_configs_value('sys', 'error_code') == 1) {
                exit(bs_lib::Alerts('Error:要引入模板文件不存在', '需要引入模板文件不存在。<BR/>文件路径:' . $File));
            } else {
                exit(bs_lib::Alerts('Error:要引入模板文件不存在', '需要引入模板文件不存在。<BR/>文件路径:后台开启调试模式'));
            }
        }
        $top = file_get_contents($File); //载入模版


        $File = $url_file . '/Bsphp_foot.html'; //载入尾部
        if (!file_exists($File)) {
            if (bs_lib::get_configs_value('sys', 'error_code') == 1) {
                exit(bs_lib::Alerts('Error:要引入模板文件不存在', '需要引模板件不存在。<BR/>文件路径:' . $File));
            } else {
                exit(bs_lib::Alerts('Error:要引入模板文件不存在', '需要引模板文件不存在。<BR/>文件路径:后台开启调试模式'));
            }
        }
        $class_bak = file_get_contents($File); //载入模版


        $test = str_replace('[Bsphp_head]', $top, $test); //顶部
        $test = str_replace('[Bsphp_foot]', $class_bak, $test); //底部


        //系统名称
        $test = str_replace('[Bsphp_Name]', bs_lib::get_configs_value('sys', 'name'), $test);
        $test = str_replace('[Bsphp_url]', bs_lib::get_configs_value('sys', 'url'), $test);


        //生成模板
        $act = set_get('act');
        if ($act == '') {
            $test = str_replace('[Bsphp_Act]', 'index', $test);
        } else {
            $test = str_replace('[Bsphp_Act]', set_get('act'), $test);
        }

        $print_html = $test;


        //最多只给载入3个模块一个文件,多了会拖累系统
        if (preg_match_all("/\[Bsphp_Block_(.*?)\]/", $test, $m)) {


            foreach ($m[1] as $V) {
                //读取模板文档
                $File = $url_file . '/Bsphp_Block_' . $V . '.html'; //载入尾部
                if (file_exists($File)) {
                    $load_html = file_get_contents($File); //载入模版
                    $print_html = str_replace("[Bsphp_Block_{$V}]", $load_html, $print_html);
                }

                //载入处理的块php
                $File = $url_file_inc . '/Bsphp_Block_' . $V . '.php'; //载入尾部
                if (file_exists($File)) {
                    include ($File);
                }
            }

        }


        $print_html = html_entity_decode($print_html);

        //读取每个文件的php动态文档
        $File = $url_file_inc . '/Bsphp_' . $skn . '.php';
        if (file_exists($File)) {
            include ($File);

        }


        /**
         * $File = $url_file_inc . '/Bsphp_' . $skn . '.php';
         * if (!file_exists($File)) {
         * if (bs_lib::get_configs_value('sys', 'error_code') == 1) {
         * exit(bs_lib::Alerts('Error:要引入php脚本文件不存在', '需要引入php脚步文件不存在。<BR/>php文件路径:' . $File .
         * '<BR/>解决方案:请在指定目录建立对应php文件'));
         * } else {
         * exit(bs_lib::Alerts('Error:要引入php脚本文件不存在',
         * '需要引入php脚步文件不存在。<BR/>php文件路径:后台开启调试模式'));

         * }


         * } else {
         * include ($File);
         * }
         */

        //html代码输出
        print ($print_html);
        //echo $test;


    }


}

?>