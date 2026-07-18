<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class admin_app extends admin
{

    private $rc, $db, $GLOBALS_LANGS, $user_str_log, $user;

    function __construct()
    {
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->user = bs_lib::load_modules_class('user', 'user');


        $this->GLOBALS_LANGS = bs_lib::load_langs_array();
        $this->user_str_log = bs_lib::load_langs_array('user', 'user_str_log');


        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();
        // echo '控制器  继承初始化1<BR/>';


        //后台权限验证
        $this->COOKIES_KEY('D');

    }


    /**
     * @控制面板-软件列表
     * 
     */
    function call_table()
    {


        /**
         * 删除软件
         */

        /**
         * 下拉框批量操作
         */
        $id = set_get('id');
        $Submit_class = set_post('Submit_class');
        $all = set_post('all');
        $select_class = set_post('select_class');


        if ($Submit_class)
        {
            if ($all == '')
                $this->admin_alert('请选择要删除软件!');
            if ($select_class == 1)
            {
                if ($this->db->my_in_delete('bs_php_appinfo', 'app_daihao', $all))
                {
                    $this->admin_alert('你选择软件已经删除!');
                } else
                {
                    $this->admin_alert('删除失败,SQL执行失败,错误数据已保存!!');
                }

            } else
            {
                $this->admin_alert('你没有选择操作项目');
            }


        } //按钮接受


        //读取模式下字段  名称和字符
        $arr = $this->db->my_class_array('bs_php_moshi', 'S_MoShi', $id, '`S_Name`,`s_zhi`');
        $mosho_name = $arr[0];
        $mosho_ziduan = $arr[1];
        //读取列表
        $sql = "SELECT`app_daihao`,`app_name`,`app_MoShi`FROM`bs_php_appinfo`;";
        $db_array_value = $this->db->Bsphp_my_query($sql);


        include bs_lib::load_default_path();
    }


    function call_add()
    {


        $daihao = set_post('daihao');
        $moshi = set_post('moshi');
        $name = set_post('name');
        $atname = null;

        $path_moshiset = BSPHP_DIR_SYS . 'include/applibapi/moshiset/';

        //模板
        $path = BSPHP_DIR_SYS . 'include/applibapi/api';
        $applibapi_array = open_list_dir($path);

        if ((int)$daihao <= 0)
        {
            $atname = '代号不能为0 必须数字!';
        } elseif ($name == '')
        {
            $atname = '软件别名不能为空!';
        } else
        {
            $app_pwd = gte_rand_str(18);
            //添加开始''

            $sql = "INSERT INTO`bs_php_appinfo` (

 `app_daihao` ,
 `app_name` ,
 `app_off` ,
 `app_off_name` ,
 `app_set` ,
 `app_set_date` ,
 `app_WEB_URL` ,
 `app_URL` ,
 `app_v` ,
 `app_key_zhong` ,
 `app_gg` ,
 `app_LogicA` ,
 `app_LogicB` ,
 `app_LogicinfoA` ,
 `app_LogicinfoB` ,
 `app_logininfo` ,
 `app_md5` ,
 `app_MoShi` ,
 `app_miaoshu` ,
 `app_info` ,
 `app_re_date` ,
 `app_zhuang_date` ,
 `app_links` ,
 `app_coode` ,
 `app_chaoshi` ,
 `app_pwd` ,
 `app_get` ,
 `app_breaking` ,
 `app_links_chaoshi` ,
 `app_chargeset` ,
 `app_links_open` ,
 `app_point_open` ,
 `app_api_pwd` ,
 `app_api_dir` ,
 `app_get_encryption` ,
 `app_show_encryption` ,
 `app_output` ,
 `app_langs` 
)
VALUES('$daihao','$name','0',\"系统维护,暂时性关闭,请稍后在访问！\",'0','0','http://www.bsphp.com/index.php','http://www.bsphp.com/up.zip','v1.0','0',\"Bsphp公告\",'0','0','A','B',\"验证数据\",NULL,'$moshi',NULL,NULL,'864000','600','99','','12000','{$app_pwd}','1','0','12000','0','99','0','0','-1','bsphp_des','bsphp_des','notfun','zh-cn') ";


            $tpm = $this->db->Bsphp_my_query($sql);
            if ($tpm)
            {
                $atname = $name . ' 添加成功,请到软件管理中查看! ';
                $daihao = '';
                $name = '';
            } else
            {
                $atname = '添加失败,软件已经存在!';
            }


        }


        include bs_lib::load_default_path();
    }

    /**
     * @控制面板-软件配置
     * 
     */
    function call_appini()
    {

        $id = set_get('id');
        $daihao = $id;
        $app_array = get_appdaihao_info_array($daihao);
        if (!$app_array)
            alert('软件不存在!');

        $app_name = $app_array['app_name'];
        $app_daihao = $app_array['app_daihao'];


        /**
         * 保存数据
         */
        $appenconfig = set_post('appenconfig');
        if ($appenconfig)
        {
            $app_daihao = set_post('app_daihao');
            $app_name = set_post('app_name');
            $app_off = set_post('app_off');
            $app_off_name = set_post('app_off_name');
            $app_set = set_post('app_set');
            $app_v = set_post('app_v');
            $app_WEB_URL = set_post('app_WEB_URL');
            $app_URL = set_post('app_URL');
            $app_gg = set_post('app_gg');
            $app_logininfo = set_post('app_logininfo');
            $app_key_zhong = set_post('app_key_zhong');
            $app_LogicA = (int)set_post('app_LogicA');
            $app_LogicinfoA = set_post('app_LogicinfoA');
            $app_LogicB = (int)set_post('app_LogicB');
            $app_LogicinfoB = set_post('app_LogicinfoB');
            $app_md5 = set_post('app_md5');
            $app_miaoshu = set_post('app_miaoshu');
            $app_info = set_post('app_info');
            $app_set_date = set_post('app_set_date');
            $app_re_date = set_post('app_re_date');
            $app_zhuang_date = set_post('app_zhuang_date');
            $app_links = set_post('app_links');
            $app_chaoshi = set_post('chaoshi');
            $app_pwd = set_post('password');
            $app_get = set_post('get_post');
            $app_breaking = set_post('app_breaking');
            $app_chargeset = set_post('app_chargeset');
            $app_links_chaoshi = set_post('app_links_chaoshi');
            $app_links_open = set_post('app_links_open');
            $app_coode = set_post('app_coode');


            $app_api_pwd = set_post('app_api_pwd');
            $app_api_dir = set_post('app_api_dir');
            $app_get_encryption = set_post('app_get_encryption');
            $app_show_encryption = set_post('app_show_encryption');
            $app_output = set_post('app_output');

            $app_langs = set_post('app_langs');


            $sql = "UPDATE`bs_php_appinfo`SET`app_langs`='$app_langs',`app_coode`='$app_coode',`app_api_pwd`='$app_api_pwd',`app_api_dir`='$app_api_dir',`app_get_encryption`='$app_get_encryption',`app_show_encryption`='$app_show_encryption',`app_output`='$app_output',`app_links_open`='$app_links_open',`app_chargeset`='$app_chargeset',`app_links_chaoshi`='$app_links_chaoshi',`app_breaking`='$app_breaking',`app_chaoshi`='$app_chaoshi',`app_pwd`='$app_pwd',`app_get`='$app_get',`app_name`='$app_name',`app_off`='$app_off',`app_off_name`='$app_off_name',`app_set`='$app_set',`app_set_date`='$app_set_date',`app_WEB_URL`='$app_WEB_URL',`app_URL`='$app_URL',`app_v`='$app_v',`app_key_zhong`='$app_key_zhong',`app_gg`='$app_gg',`app_LogicA`='$app_LogicA',`app_LogicB`='$app_LogicB',`app_LogicinfoA`='$app_LogicinfoA',`app_LogicinfoB`='$app_LogicinfoB',`app_logininfo`='$app_logininfo',`app_md5`='$app_md5',`app_miaoshu`='$app_miaoshu',`app_info`='$app_info',`app_re_date`='$app_re_date',`app_zhuang_date`='$app_zhuang_date',`app_links`='$app_links' WHERE`bs_php_appinfo`.`app_daihao`='$app_daihao';";


            $tpm = $this->db->Bsphp_my_query($sql);
            if ($tpm)
            {
                $this->admin_alert('信息保存成功!');
            } else
            {
                $this->admin_alert('信息保存失败,请重试!');
            }


        }


        $id = set_get('id');
        $Bsphp_Host = bs_lib::get_configs_value('sys', 'url');
        $md5 = md5(bs_lib::get_configs_value('sys', 'cook_key') . $id . bs_lib::get_configs_value('sys', 'cook_key'));
        $md5s = md5('xiaozhou' . bs_lib::get_configs_value('sys', 'cook_key') . $id . bs_lib::get_configs_value('sys', 'cook_key') . 'QQ944463782');


        $array = $this->db->my_class_array('bs_php_appinfo', 'app_daihao', $id, ' * ');


        //加密解密文件存放
        $path = BSPHP_DIR_SYS . 'include/applibapi/encryption';
        $encryption_array = open_list_dir($path);

        //系统输出格式 xml、josn
        $path = BSPHP_DIR_SYS . 'include/applibapi/output';
        $output_array = open_list_dir($path);


        //第三方替代库
        $path = BSPHP_DIR_SYS . 'include/applibapi/api';
        $api_array = open_list_dir($path);

        //语言包

        //语言
        $path = BSPHP_DIR_SYS . 'include/langs';
        $dir_array = open_list_dir($path);


        //API别名列表
        //------------------------------------------------------------


        //查询表单
        $apipwd = $array['app_api_pwd'];


        //打开目录
        $fis_Url = BSPHP_DIR_SYS . 'include/applibapi/api';
        $while_id = null;
        $html = null;
        $html_txt = null;
        //$handle = opendir($fis_Url); //OPEN


        //------------------------------------------------------------
        
   
        include bs_lib::load_default_path();
    }


}


function dir_copys($fromdir,$apipwd)
{


    $fromdir = dir_path($fromdir);


    $list = glob($fromdir . '*');

    if (!empty($list))
    {
        foreach ($list as $v)
        {

            if (is_dir($v))
            {
                dir_copys($v,$apipwd);

                // echo "$v".'<br/>';
            } else
            {
                //@chmod($path, 0777);
                $v=goto_utf8($v);
                $FileName = $v;
                
                
                
                
                
                $FileName=get_filename($FileName);
                

                if (strstr($FileName, 'AppEn.') AND strstr($FileName, '.php'))
                {


                    $apiname = str_replace('AppEn.', '', $FileName);
                    $apiname_pwd = str_replace('.php', '', $apiname);
                    $aip_name = $apiname_pwd;

                    $apiname_pwd = des_mencrypt($apiname_pwd, $apipwd);
                    $apiname_pwd = str_replace('=', '|MH171C', $apiname_pwd);
                    $apiname_pwd = str_replace('+', '|H370J', $apiname_pwd);
                    $apiname_pwd = str_replace('/', '|TJS7E', $apiname_pwd);


                    $apiname_show = str_replace('|MH171C', '=', $apiname_pwd);
                    $apiname_show = str_replace('|H370J', '+', $apiname_show);
                    $apiname_show = str_replace('|TJS7E', '/', $apiname_show);
                    $apiname_show = des_mdecrypt($apiname_show, $apipwd);
                    
                    $v = str_replace(BSPHP_DIR_SYS, '..', $v);


                    if ($apiname != '' and $apiname != '.php')
                    {
                        echo '
          <tr>
            <td height="30" nowrap bgcolor="#FFFFFF" class="STYLE2">' . $aip_name . '</td>
            <td height="30" nowrap bgcolor="#FFFFFF" class="STYLE2">' . $v . '</td>
            <td height="30" bgcolor="#FFFFFF" class="STYLE2">' . $apiname_pwd . '</td>
			<td height="30" bgcolor="#FFFFFF" class="STYLE2">' . $apiname_show . '</td>
            <td height="30" bgcolor="#FFFFFF" class="STYLE4"><a href="http://tools.bsphp.com/soso.php?soso='.$aip_name.'" target="_blank">info</a></td>
            

	 </tr>    ';
                    }


                }


                
            }
        }
    }

}

?>