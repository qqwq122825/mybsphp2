<?php
defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if (BSPHP_SET != 'ADMIN')
    die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @用户中心首页 registration
 * 
 */
class admin_style extends admin
{

    private $rc, $db, $GLOBALS_LANGS, $user_str_log, $user, $seesion_uid;

    function __construct()
    {

        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');

        $this->GLOBALS_LANGS = bs_lib::load_langs_array();


        $this->ip = bs_lib::load_libbsphp_class('ip', 'ip');


        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();
        // echo '控制器  继承初始化1<BR/>';


        //后台权限验证
        $this->COOKIES_KEY('B');


        $admin_uid = get_session_value('ADMIN_UID');
        $this->admin_global_array = $this->db->Bsphp_my_array("SELECT * FROM`bs_php_admin`WHERE`Admin_ID`='$admin_uid'");
    }


    function call_config()
    {


        $array_info = array(
            'Bsphp_down.html' => '下载页',
            'Bsphp_common.html' => '简介-关于我们',
            'Bsphp_reserved.html' => '简介-知识产权声明',
            'Bsphp_Disclaimer.html' => '简介-免责声明',
            'Bsphp_Contact.html' => '简介-联系我们',
            'Bsphp_head.html' => '顶部代码块',
            'Bsphp_foot.html' => '低部代码块',
            'Bsphp_newsSee.html' => '新闻/文章查看页',
            'Bsphp_index.html' => '首页',
            'Bsphp_news.html' => '新闻/文章列表页',
            'Bsphp_downinfo.html' => '下载详细信息页',
            'Bsphp_LiuYan.html' => '留言查看',
            'Bsphp_AddLiuYan.html' => '添加留言',
            'Bsphp_Downinfo.html' => '下载页信息');


        include bs_lib::load_default_path();
    }


    function call_set()
    {


        $url = BSPHP_DIR_SYS.'include/templates/'.bs_lib::get_configs_value('sys','user_template').'/index/cms_skn/';


        $file = set_get('file');


        //新代码begin】
       // if (!isset($array_info[$url.$file])) {
       //     alert('文件不存在,或非系统文件!'.$url.$file);
       //     exit;
       //   }
       //end


        if (isset($array_info[$file])) {
            $table_name = $array_info[$file];
        } else {

            $table_name = '用户自定义文件';
        }
        
        
        
        $name = $url . $file;


        /**
         * 保存
         */
        $Submit = set_post('Submit');
        isset($_POST['textfield']) ? $textfield = $_POST['textfield'] : $textfield = null;
        ;

        if ($Submit) {
            $tmp = file_put_contents($name, stripslashes($textfield));
            if ($tmp) {

                alert('保存成功!'); //'Admin.style.php?act=usb&file='.$file,
            } else {
                alert('保存失败!');
            }

        }


        include bs_lib::load_default_path();
    }

}
?>