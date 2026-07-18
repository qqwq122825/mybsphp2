<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');


/**
 * @支付连接 跳转
 * 
 * 
 * 传递变量
 * $pay_user=充值用户
 * $pay_amount=金额
 * $pay_leixing=充值方式
 * 
 * 
 * 
 */
class Confirmthepayment
{

    private $rc,$GLOBALS_LANGS, $db, $user_str_log, $user, $seesion_uid;

    function __construct()
    {

        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();
        $this->user_str_log = bs_lib::load_langs_array('user', 'user_str_log');

        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->user = bs_lib::load_modules_class('user', 'user');
        
        $this->GLOBALS_LANGS = bs_lib::load_langs_array();

    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_Confirmthepayment()
    {

        //充值信息

        if(set_post('Submit_pay')){
            
          $pay_user = set_post('pay_user'); //充值用户
          $pay_amount = set_post('pay_amount'); //金额
          $pay_leixing = set_post('pay_leixing'); //充值方式
            
            
        }else{
          $pay_user = set_get('pay_user'); //充值用户
          $pay_amount = set_get('pay_amount'); //金额
          $pay_leixing = set_get('pay_leixing'); //充值方式
            
        }

        
        $pay_id = date('YmdHis', HOST_UNIX) . rand(1000, 9999); //订单号
        $pay_beizhu = bs_lib::get_configs_value('sys','name') . ' - 充值用户:' . $pay_user . ' - 余额充值';
        


        /**
         * 是否已经输入充值钱
         */

        if ($pay_amount <= 0)
        {

           echo '你输入要充值的金额,请返回！';
           exit;

        }
        

      

         $pay_uid = $this->db->my_class_array('bs_php_user', 'user_user', $pay_user, '`user_uid`');

         if(!$pay_uid){

           echo '需要充用户不存在,请正确输入,请返回！';
           exit;

        }



        /**
         * 下单
         */
        $pay_log = $this->user->PayAdd($pay_id, $pay_uid, $pay_leixing, $pay_amount);

        if (!$pay_log)
        {

           echo '下单失败,请返回重试！';
           exit;

        }

        $openfileconfig=BSPHP_DIR_SYS.'include/modules/payment/paycood/'.$pay_leixing.'/form_config.php';
        
        
        if (!file_exists($openfileconfig))
        exit(bs_lib::Alerts('Error:要引入支付文件不存在', '需要引入php脚步文件不存在。<BR/>文件路径:' . $openfileconfig));
        $config_array=include ($openfileconfig);
        
        $openfileconfig=BSPHP_DIR_SYS.'include/modules/payment/paycood/'.$pay_leixing.'/'.$config_array['pay_config']['OpenFile'];
        if (!file_exists($openfileconfig))
        exit(bs_lib::Alerts('Error:要引入支付文件不存在', '需要引入php脚步文件不存在。<BR/>文件路径:' . $openfileconfig));
        $config_array=include ($openfileconfig);
    }


}

?>