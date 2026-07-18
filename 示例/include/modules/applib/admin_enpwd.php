<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class admin_enpwd extends admin
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
        $this->COOKIES_KEY('F');

    }


    /**
     * @控制面板-软件列表
     * 
     */
    function call_index()
    {
        
        
    

        global $enentext,$deapi,$enapi,$dedetext;
        $deapi = @$_POST['deapi'];
        $enapi = @$_POST['enapi'];
        $dedetext = @$_POST['dedetext'];
        $enentext = $dedetext;
        //$apipwd = @$_POST['apipwd'];
        $daihao = @$_POST['daihao'];
        
        if ($deapi or $enapi)
        {
            
            
            @eval(base64_decode('ICAgICAgICRhZG1pbl9pbmZvID0gQWRtaW5fVmVyaWZpY2F0aW9ucygnUGF5bWVudCcpOyAgICAgICBpZiAoJGFkbWluX2luZm8gIT0gMSkNCiAgICAgICBic19saWI6OkFsZXJ0cygn57O757uf5pyq5o6I5p2DJywgJ+WwiuaVrOeUqOaItyzkvaDlvZPliY3ns7vnu5/liKvlkI3lronlhajpmLJIT1NU5L2/55So5o6l5Y+j5pyq57uP5o6I5p2D5peg5rOV5L2/55SoLOivt+WFiOi0reS5sOaOiOadg+WcqOS9v+eUqOOAguWcqOe6v+i0reS5sOWcsOWdgDpodHRwOi8vTWFuYWdlbWVudC5Cc3BocC5jb20nKTsNCiAgICAgICAgIA=='));
            

            $array=get_appdaihao_info_array($daihao);
            $apipwd=$array['app_api_pwd'];
            if($apipwd=='' or $apipwd==0){
                
                $this->admin_alert('当前软件没有开启别名接口加密！');
            }
            
            
            //打开目录
            $fis_Url = BSPHP_DIR_SYS . 'include/applibapi/api';
            $while_id = null;
            $html = null;
            $html_txt = null;
            
            
            dir_copys($fis_Url,$apipwd);


        }
        
        
        
        
        
     $sql="SELECT `app_daihao`,`app_name` ,`app_api_pwd` FROM`bs_php_appinfo`";
     $tmp=$this->db->Bsphp_my_query($sql);
        


        include bs_lib::load_default_path();
    }


}


function dir_copys($fromdir, $apipwd)
{
    
    
    global $enentext,$deapi,$enapi,$dedetext;


    $fromdir = dir_path($fromdir);


    $list = glob($fromdir . '*');

    if (!empty($list))
    {
        foreach ($list as $v)
        {

            if (is_dir($v))
            {
                dir_copys($v, $apipwd);

                // echo "$v".'<br/>';
            } else
            {
                //@chmod($path, 0777);
                $v = goto_utf8($v);
                $FileName = $v;


                $FileName = get_filename($FileName);


                if (strstr($FileName, 'AppEn.') and strstr($FileName, '.php'))
                {


                    $apiname = str_replace('AppEn.', '', $FileName);
                    $apiname = str_replace('.php', '', $apiname);
                    $apiname_pwd = str_replace('=', '|MH171C', des_mencrypt($apiname, $apipwd));
                    $apiname_pwd = str_replace('+', '|H370J', $apiname_pwd);
                    $apiname_pwd = str_replace('/', '|TJS7E', $apiname_pwd);

                    if ($apiname != '')
                    {

                        if ($deapi)
                        {


                            //解决替换前后同名BUG
                            if ($apiname == 'cancellation.lg')
                            {

                                $enentext = str_replace('remotecancellation.lg', 'XIAOZHOUCANLG', $enentext);
                            }

                            if ($apiname == 'cancellation.ic')
                            {

                                $enentext = str_replace('remotecancellation.ic', 'XIAOZHOUCANIC', $enentext);
                            }


                            $enentext = str_replace($apiname, $apiname_pwd, $enentext);


                            //解决替换前后同名BUG
                            if ($apiname == 'cancellation.lg')
                            {

                                $enentext = str_replace('XIAOZHOUCANLG', 'remotecancellation.lg', $enentext);
                            }

                            if ($apiname == 'cancellation.ic')
                            {

                                $enentext = str_replace('XIAOZHOUCANIC', 'remotecancellation.ic', $enentext);
                            }


                        } elseif ($enapi)
                        {
                            $enentext = str_replace($apiname_pwd, $apiname, $enentext);


                        }


                    }


                }


            }


        }
    }
}

?>