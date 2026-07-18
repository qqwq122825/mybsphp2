<?php


defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
/**
 * @后台管理员权限控制器
 * 
 */
class admin
{
    private $GLOBALS_LANGS, $db, $sessiona,$BSPHP_INFO;
    
    


    function construct()
    {
        //开启session
        //  $this->sessiona = bs_lib::load_libbsphp_class('session', 'session');
        //$this->sessiona->session_open();
          $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');

        /**
         * @判断是否已经登陆 简单判断
         */

        // if ($this->admin_login() <> 1000) header("Location: ?m=admin");
        // echo '管理员 权限类 载入授权1 <BR/>';

    }


    function Bsphp_fun()
    {


    }


    /**
     * MD8管理系统后台加密
     */
    function md8($data)
    {
        return md5($data);
    }


    /**
     * @登陆页面简单判断一下是否已经登陆
     */
    function admin_login()
    {
        $api_uid = get_session_value('ADMIN_UID');
        $api_password = get_session_value('ADMIN_YSE');
        $api_cookies_md7 = get_session_value('ADMIN_MD7');
        if ($api_uid != '' or $api_password != '' or $api_cookies_md7 != '') {
            return 1000;
        } else {
            return false;
        }

    }


    /**
     * 
     * 
     * 权限验证
     * 管理后台管理员用户权限验证
     * 参数是用户权限如果  A,B,C.......
     * 
     * A.进入后台权限 
     * B.操作《系统》权限 
     * C.操作《用户》权限 
     * D.操作《软件》权限 
     * E.操作《前台》权限 
     * F.操作《工具》权限 
     * P.操作《插件》权限
     * 
     * $ADMIN_USER = 全局变量
     */
    function COOKIES_KEY($quan)
    {
        
        $this->construct();
          
        /*$api_db = new mysql();
        $cookies_md7 = cookies_md7($user . $COOKIE_password . $COOKIE_date . $COOKIE_ip . $LoGinNum);
        set_cookie('ADMIN', $date);  //站位
        set_cookie('ADMIN_USER', $user);
        set_cookie('ADMIN_YSE', $COOKIE_password);
        set_cookie('ADMIN_DATE', $COOKIE_date);
        set_cookie('ADMIN_IP', $COOKIE_ip);
        set_cookie('ADMIN_cookies_md7', $cookies_md7);
        */

        $api_uid = get_session_value('ADMIN_UID');
        $api_password = get_session_value('ADMIN_YSE');
        $api_date = get_session_value('ADMIN_DATE');
        $api_ip = get_session_value('ADMIN_IP');
        $api_cookies_md7 = get_session_value('ADMIN_MD7');
        $otu = get_session_value('otu');
        if ($otu != 'yes') {

            //   if(infovip()){
            //    set_cookie('otu','yes');  //大写不通过
            //    }else{
            //   set_cookie('otu','Yes');  //大写不通过
            //    }
            //set_cookie('otu','yes');  //大写不通过
        }

        //------------------------------------------------------------
        //判断COOKIE状态
        if ($api_uid == '' or $api_password == '' or $api_cookies_md7 == '')
            exit($this->admin_alert( '请先登陆','?m=admin'));


        //--------------------------------------------/**获取完毕*/
        $sql = "SELECT`Admin_ID`,`Admin_AdminPassWord`,`Admin_LoGinNum`,`Admin_IsLock`,`Admin_Permission`,`Admin_AdminUserName`FROM`bs_php_admin`WHERE`Admin_ID`='$api_uid'";


        $array = $this->db->Bsphp_my_array($sql);
        //$array=$this->db->Bsphp_my_query($sql);
        
        if (!is_array($array))
            exit($this->admin_alert( '你没有权限！','?m=admin&c=index&a=logut'));
        //-------------------------------------------------------------------------

        //赋值表单

        $password = cookies_md7($array['Admin_AdminPassWord']);
        $LoGinNum = $array['Admin_LoGinNum']; 
        $IsLock = $array['Admin_IsLock'];
        $Permission = $array['Admin_Permission'];
        $uid = $array['Admin_ID'];
        global $ADMIN_USER;
        $ADMIN_USER = $array['Admin_AdminUserName'];
        //-------------------------------------------------
        if ($IsLock != 0)
            exit($this->admin_alert( '你账号已经被冻结！','?m=admin&c=index&a=logut'));
        //--------------------------------------------------

        //*cookies_md7验证*/
        $cookies_md7 = cookies_md7($api_uid . $api_password . $api_date . $api_ip . $LoGinNum);
        if ($cookies_md7 != $api_cookies_md7)
            exit($this->admin_alert( '账号在别处已经登陆,账号被迫登出！','?m=admin&c=index&a=logut'));

        //---------------------------------------------------
        //*验证账号密码*/
        if ($api_uid != $uid or $api_password != $password)
            exit($this->admin_alert('你没有权限,请登陆！','?m=admin&c=index&a=logut'));

        //----------------------------------------------------
        //*权限验证*/
        if ($quan != 'not') {
            if (stristr($Permission, $quan) == false)
                die($this->admin_alert( '你权限不够无法浏览该页,需要权限' . $quan,'Admin.info.php'));
        }
        
        
        /*

        if ($otu == 'Yes' & $otu != '' & set_get('act') != 'appenvip')
            die($this->atu('Admin.root.php?act=appenvip', 'not'));
        unset($db);
        
        */
        
        

    }
     /*权限验证结束*/


    function get_admin_name()
    {
        
        $this->construct();
          


        $api_uid = get_session_value('ADMIN_UID');


        //--------------------------------------------/**获取完毕*/
        $sql = "SELECT`Admin_ID`,`Admin_AdminUserName`,`Admin_LoGinNum`,`Admin_IsLock`,`Admin_Permission`,`Admin_AdminUserName`FROM`bs_php_admin`WHERE`Admin_ID`='$api_uid'";


        $array = $this->db->Bsphp_my_array($sql);

        


       return $array['Admin_AdminUserName'];
        

    }
/**

  /**
   *管理后台专属提示框
   */
  function admin_alert( $name = '',$url = '') {

    if ($name == '') $name = '信息已经保存....';
	echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo "<script language=javascript>alert('" . $name . "');</script>";
    if($url=='')$url=get_url();
	location($url);
    exit;
  }
  
  
  
  
  
  
  function quick_html(){
    
    
    
    
   $URL=urlencode(get_url()); 
    
    echo '[<a href="index.php?m=admin&c=addurl&a=index&url='.$URL.'">+</a>]';
	
}
  
  
  
  
  


}
?>