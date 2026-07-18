<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');

/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class tools extends admin
{

    private $rc, $db,$admin_global_array,$ip;

    function __construct()
    {
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');

        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();
        // echo '控制器  继承初始化1<BR/>';

        //后台权限验证
        $this->COOKIES_KEY('B');
    }


    /**
     * @管理员列表
     * 
     */
    function call_phpinfo()
    {

        phpinfo();

    }
    
    

    /**
     * @管理员列表
     * 
     */
    function call_info()
    {

        
  //关闭所有错误
  error_reporting(E_ALL & ~E_NOTICE);  
 ini_set('display_errors', '0');  
  

  
  
  
  
   //------------------------------------------------------------------------------------------------------------------------
  
  
  
  
    $tody_date = date('Y-m-d', HOST_UNIX); //日期1
    $snutady = date('w', HOST_UNIX); //星期几2
    $tody_unix = HOST_UNIX; //时间3
    $log_info_array = array();

  
  
  
  
    /**
     * @RMB总和*/
    $sql = "select count(*)as'hangshu',SUM(`user_rmb`)as'zongrmb'from`bs_php_user`WHERE `user_daili`<=1";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    $log_info_array['user_tady_all'] = (int)$tmp_arr['hangshu']; //用户总数
    $log_info_array['user_tady_rmb_all'] = (int)$tmp_arr['zongrmb']; //金额总数
    $user_tady_allQ=$log_info_array['user_tady_all'];


    /** 今天用户注册数量*/
    $sql = "select count(*)as'hangshu'from`bs_php_user`WHERE`user_re_date`='$tody_date'";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    $log_info_array['user_tady_re'] = (int)$tmp_arr['hangshu']; //今天注册总数
  
  
    /** 今天登陆过的用户数量*/
    $sql = "select count(*)as'hangshu'from`bs_php_user`WHERE`user_Login_date`>'$tody_date'";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    $log_info_array['user_tady_login'] = (int)$tmp_arr['hangshu']; //今天登陆过的用户
	
	
	
    /** 今天
     * @全部充值卡总金额   * @全部代理充值卡总价*/
    $sql = "select count(*)as'hangshu',SUM(`car_Rmb`)as'car_Rmb',SUM(`car_DaoLi_Rmb`)as'car_DaoLi_Rmb'from`bs_php_cardseries`";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    $log_info_array['ka_all'] = (int)$tmp_arr['hangshu']; //全部充值看数量
    $log_info_array['ka_all_rmb'] = (int)$tmp_arr['car_Rmb']; //全部充值卡销售价格总和
    $log_info_array['ka_all_d_rmb'] = (int)$tmp_arr['car_DaoLi_Rmb']; //全部代理充值卡价格总和
	
	
	
    /** 今天
     * @全部充值卡总金额   * @全部代理充值卡总价*/
    $sql = "select count(*)as'hangshu',SUM(`car_Rmb`)as'car_Rmb',SUM(`car_DaoLi_Rmb`)as'car_DaoLi_Rmb'from`bs_php_cardseries`";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    $log_info_array['ka_all'] = (int)$tmp_arr['hangshu']; //全部充值看数量
    $log_info_array['ka_all_rmb'] = (int)$tmp_arr['car_Rmb']; //全部充值卡销售价格总和
    $log_info_array['ka_all_d_rmb'] = (int)$tmp_arr['car_DaoLi_Rmb']; //全部代理充值卡价格总和


    /**
     * @已激活充值卡
     */
    /**
     * @已激活充值卡销售价格总和 * @已激活充值卡代理价格销售总和*/
    $sql = "select count(*)as'hangshu',SUM(`car_Rmb`)as'car_Rmb',SUM(`car_DaoLi_Rmb`)as'car_DaoLi_Rmb'from`bs_php_cardseries`WHERE`car_IsLock`='1'";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    $log_info_array['ka_aut'] = (int)$tmp_arr['hangshu']; //全部已激活
    $log_info_array['ka_aut_rmb'] = (int)$tmp_arr['car_Rmb']; //全部已激活销售价格总和
    $log_info_array['ka_aut_d_rmb'] = (int)$tmp_arr['car_DaoLi_Rmb']; //全部已激活销代理格总和


    /**
     * @未激活充值卡
     */
    /**
     * @未激活充值卡销售价格总和 * @未激活充值卡代理价格销售总和*/
    $sql = "select count(*)as'hangshu',SUM(`car_Rmb`)as'car_Rmb',SUM(`car_DaoLi_Rmb`)as'car_DaoLi_Rmb'from`bs_php_cardseries`WHERE`car_IsLock`='0'";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    $log_info_array['ka_die'] = (int)$tmp_arr['hangshu']; //全部未激活
    $log_info_array['ka_die_rmb'] = (int)$tmp_arr['car_Rmb']; //全部未激活销售价格总和
    $log_info_array['ka_die_d_rmb'] = (int)$tmp_arr['car_DaoLi_Rmb']; //全部未激活销代理格总和
	
	
  
        /**
         * @今天激活使用全部充值卡- 今天激活
         */
        /** 今天
         * @今天全部制作充值卡总金额   * @今天全部制作代理充值卡总价*/
        $sql = "select count(*)as'hangshu',SUM(`car_Rmb`)as'car_Rmb',SUM(`car_DaoLi_Rmb`)as'car_DaoLi_Rmb'from`bs_php_cardseries`WHERE`car_pur_date`>'$tody_date'AND`car_IsLock`='1'";
        $tmp_arr = $this->db->Bsphp_my_array($sql);
        $log_info_array['ka_tady_out_all'] = (int)$tmp_arr['hangshu']; //全部充值看数量
        $log_info_array['ka_tady_out_all_rmb'] = (int)$tmp_arr['car_Rmb']; //全部充值卡销售价格总和
        $log_info_array['ka_tady_out_all_d_rmb'] = (int)$tmp_arr['car_DaoLi_Rmb']; //全部代理充值卡价格总和
  
  
  
  
      /** 今天在线充值金额*/
    $sql = "select count(`pay_rbm`)as'hangshu'from`bs_php_rmb_pay_log`WHERE `pay_zhuangtai`='0' and `pay_date`= '$tody_date'";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    $log_info_array['user_tady_pay_rmb'] = (int)$tmp_arr['hangshu']; //今天在线充值金额
  
  
    /** 今天邀请注册用户数量*/
    $sql = "select count(*)as'hangshu'from`bs_php_user`WHERE`user_re_date`='$tody_date'AND`user_yao_User`!=''";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    $log_info_array['user_tady_re_yao'] = (int)$tmp_arr['hangshu']; //今天邀请注册总数
	
	
	
	
    /** 今天消费够卡记录*/
    $sql = "select SUM(`ka_shijia`)as'hangshu'from`bs_php_pay_log`WHERE`pay_date`>'$tody_date'AND`pay_zhuangtai`='0'";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    $log_info_array['user_tady_buy_ka_rmb'] = (int)$tmp_arr['hangshu']; //今天消费够卡记录总金额
    
    
    
    
    
    //------------------------------------------------------------------------------------------------------------------------
    
    

        
        
       include bs_lib::load_default_path();  
    }
    
    


}

?>