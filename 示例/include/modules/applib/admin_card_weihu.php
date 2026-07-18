<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class admin_card_weihu extends admin
{

    private $rc, $db, $GLOBALS_LANGS, $user_str_log, $user, $card_array_name, $card_oid_array_name;

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
     * @控制面板
     * 
     */
    function call_card()
    {

        $allid = set_get('allid');

        $AND = null;
        /**
         * ID模式
         */
        //this GET paramter
        $ok = set_post('ok');
        if ($ok)
        {
            
            @eval(base64_decode('ICAgICAgICRhZG1pbl9pbmZvID0gQWRtaW5fVmVyaWZpY2F0aW9ucygnUGF5bWVudCcpOyAgICAgICBpZiAoJGFkbWluX2luZm8gIT0gMSkNCiAgICAgICBic19saWI6OkFsZXJ0cygn57O757uf5pyq5o6I5p2DJywgJ+WwiuaVrOeUqOaItyzkvaDlvZPliY3ns7vnu5/lhYXlgLzljaHnrqHnkIbmibnph4/nu7TmiqTmnKrnu4/mjojmnYPml6Dms5Xkvb/nlKgs6K+35YWI6LSt5Lmw5o6I5p2D5Zyo5L2/55So44CC5Zyo57q/6LSt5Lmw5Zyw5Z2AOmh0dHA6Ly9NYW5hZ2VtZW50LkJzcGhwLmNvbScpOw0KICAgICAgICAg'));

            /**
             * 获取表单参数
             */
            $lie_x = set_post('lei_x');
            $text_id = set_post('text_id');
            $appall = set_post('appall');
            $leiall = set_post('leiall');
            $leiall_test = set_post('leiall_test');
            $radiobutton = set_post('radiobutton');
            $day = set_post('day');
            $money = set_post('money');
            $money2 = set_post('money2');
            $cardate = set_post('cardate');
            $select = set_post('select');


            /**
             * 判断选择的大于小于
             */
            if ($select == 1)
            {
                $select = '>';
            } elseif ($select == 2)
            {
                $select = '<';
            } elseif ($select == 3)
            {
                $select = '!=';
            } else
            {
                $select = '=';
            }


            /**
             * 设置方式通过ID  还是通过节选1=ID  2=节选
             */
            //this id
            if ($lie_x == 1)
            {

                $WHERE = "WHERE `car_id`In($text_id)";


                //this excrept
                /**
                 * 节选模式
                 */
            } elseif ($lie_x == 2)
            {


                /**
                 * 软件选择
                 */
                //this Choes app
                if ($appall == 'all')
                {
                    $WHERE = 'WHERE ';
                } elseif ($appall > 0 & $appall != 'all')
                {
                    $WHERE = "WHERE `car_DaiHao`='$appall'";
                    $AND = ' AND ';
                } else
                {
                    $this->admin_alert('请选择维护软件');
                }


                /**
                 * 2次节选
                 *                       <option value="1">全部卡串</option>
                 * <option value="2">状态冻结</option>
                 * <option value="3">状态正常</option>
                 * <option value="4">未使用</option>
                 * <option value="5">已使用</option>
                 * <option value="6">卡类型ID*</option>
                 * <option value="7">充值时间*</option>
                 * <option value="8">销售价格*</option>
                 * <option value="9">代理价格*</option>
                 * <option value="10">制卡人员*</option>
                 * <option value="11">制作时间*</option>
                 * <option value="12">冲卡用户*</option>
                 */
                if ($leiall == 1)
                {
                    $WHERE = '';


                } elseif ($leiall == 2)
                {
                    $WHERE .= "$AND`car_zhuangtai`='1'";
                } elseif ($leiall == 3)
                {
                    $WHERE .= "$AND`car_zhuangtai`='0'";
                } elseif ($leiall == 4)
                {
                    $WHERE .= "$AND`car_IsLock`='0'";
                } elseif ($leiall == 5)
                {
                    $WHERE .= "$AND`car_IsLock`='1'";
                } elseif ($leiall == 6)
                {
                    $WHERE .= "$AND`car_Lei`$select'$leiall_test'";
                } elseif ($leiall == 7)
                {
                    $WHERE .= "$AND`car_pur_date`$select'$leiall_test'";
                } elseif ($leiall == 8)
                {
                    $WHERE .= "$AND`car_Rmb`$select'$leiall_test'";
                } elseif ($leiall == 9)
                {
                    $WHERE .= "$AND`car_DaoLi_Rmb`$select'$leiall_test'";
                } elseif ($leiall == 10)
                {
                    $WHERE .= "$AND`car_admin`$select'$leiall_test'";
                } elseif ($leiall == 11)
                {
                    $WHERE .= "$AND`car_reDATE`$select'$leiall_test'";
                } elseif ($leiall == 12)
                {
                    $WHERE .= "$AND`car_cong_user`$select'$leiall_test'";
                } else
                {
                    if ($leiall)
                        $this->admin_alert('请选择维护2.次节选');
                }

                if ($leiall > 5 & $leiall_test == 'Null')
                    $this->admin_alert('请选择*输入节选内容！');


                //this end  节选结束
            } else
            {

                if ($lie_x == '')
                    $this->admin_alert('请选择节选方式还是ID');
            }

            /**
             * 功能
             * 
             * 设置未使用 
             * 设置已使用 
             * 设置冻结 
             * 设置正常 
             * 设置充值天数  
             * 设置销售价格  
             * 设置代理价格  
             * 设置制卡时间  
             * 删除  

             */
            $UPDATE = "UPDATE`bs_php_cardseries`";
            if ($radiobutton == 1)
            {
                $SET = $UPDATE . "SET`car_IsLock`='0'";

            } elseif ($radiobutton == 2)
            {
                $SET = $UPDATE . "SET`car_IsLock`='1'";

            } elseif ($radiobutton == 3)
            {
                $SET = $UPDATE . "SET`car_zhuangtai`='1'";

            } elseif ($radiobutton == 4)
            {
                $SET = $UPDATE . "SET`car_zhuangtai`='0'";

            } elseif ($radiobutton == 5)
            {
                if ($day == 0)
                    $this->admin_alert('请输入设置天数');
                $SET = $UPDATE . "SET`car_TianShu`='$day'";

            } elseif ($radiobutton == 6)
            {
                if ($money == 0)
                    $this->admin_alert('请输入设置金额');
                $SET = $UPDATE . "SET`car_Rmb`='$money'";

            } elseif ($radiobutton == 7)
            {
                if ($money2 == 0)
                    $this->admin_alert('请输入设置金额');
                $SET = $UPDATE . "SET`car_DaoLi_Rmb`='$money2'";

            } elseif ($radiobutton == 8)
            {
                if ($cardate == 0)
                    $this->admin_alert('请输入设置时间');
                $SET = $UPDATE . "SET`car_reDATE`='$cardate'";

            } elseif ($radiobutton == 9)
            {
                $SET = "DELETE FROM`bs_php_cardseries`";
            } else
            {
                //功能介绍 end

                $this->admin_alert('请选择功能！');

            }


            $sql = "{$SET} {$WHERE}";
            $tmp = $this->db->Bsphp_my_query($sql);
            if ($tmp)
            {
                $this->admin_alert('语句已经执行成功');
            } else
            {
                $this->admin_alert('执行失败！');
            }


        }


        /**
         * this get software array info
         */
        $sql = "SELECT`app_daihao`,`app_name`FROM`bs_php_appinfo`";
        $tmp = $this->db->Bsphp_my_query($sql);


        include bs_lib::load_default_path();
    }

    /**
     * @控制面板
     * 
     */
    function call_loginterm()
    {

        $allid = set_get('allid');
        $date = HOST_DATE;
        $UNIX = HOST_UNIX;
        $AND = null;
        /**
         * ID模式
         */
        //this GET paramter
        $ok = set_post('ok');
        if ($ok)
        {
            @eval(base64_decode('ICAgICAgICRhZG1pbl9pbmZvID0gQWRtaW5fVmVyaWZpY2F0aW9ucygnUGF5bWVudCcpOyAgICAgICBpZiAoJGFkbWluX2luZm8gIT0gMSkNCiAgICAgICBic19saWI6OkFsZXJ0cygn57O757uf5pyq5o6I5p2DJywgJ+WwiuaVrOeUqOaItyzkvaDlvZPliY3ns7vnu5/lhYXova/ku7bnlKjmiLfnrqHnkIbmibnph4/nu7TmiqTmnKrnu4/mjojmnYPml6Dms5Xkvb/nlKgs6K+35YWI6LSt5Lmw5o6I5p2D5Zyo5L2/55So44CC5Zyo57q/6LSt5Lmw5Zyw5Z2AOmh0dHA6Ly9NYW5hZ2VtZW50LkJzcGhwLmNvbScpOw0KICAgICAgICAg'));


            $lie_x = set_post('lei_x');
            $text_id = set_post('text_id');
            $appall = set_post('appall');
            $leiall = set_post('leiall');
            $leiall_test = set_post('leiall_test');
            $radiobutton = set_post('radiobutton');

            $day1 = set_post('day1');
            $day2 = set_post('day2');
            $date_txt = set_post('date_txt');
            $key = set_post('key');
            $select = set_post('select');


            if ($select == 1)
            {
                $select = '>';
            } elseif ($select == 2)
            {
                $select = '<';
            } elseif ($select == 3)
            {
                $select = '!=';
            } else
            {
                $select = '=';
            }

            //this id
            /**
             * 现在通过ID节选
             */
            if ($lie_x == 1)
            {

                $WHERE = "WHERE `L_id`In($text_id)";

                /**
                 * 通过过滤节选,软件加2次节选
                 */
            } elseif ($lie_x == 2)
            {


                /**
                 * 软件选择
                 */
                //this Choes app
                if ($appall == 'all')
                {
                    $WHERE = 'WHERE ';
                } elseif ($appall > 0 & $appall != 'all')
                {
                    $WHERE = "WHERE `L_daihao`='$appall'";
                    $AND = ' AND ';
                } else
                {
                    $this->admin_alert('请选择维护软件');
                }


                /**
                 * 2次节选
                 * <option value="1">全部用户</option>
                 * <option value="2">没到期用</option>
                 * <option value="3">已经到期</option>
                 * <option value="4">X天前到期用户*</option>
                 * <option value="5">用户UID*</option>
                 * <option value="6">绑定特征*</option>
                 * <option value="7">到期时间*</option>
                 * <option value="8">添加时间*</option>
                 */


                if ($leiall == 1)
                {
                    $WHERE = '';
                } elseif ($leiall == 2)
                {
                    $WHERE .= "$AND`L_vip_unix`>'$UNIX'";
                } elseif ($leiall == 3)
                {
                    $WHERE .= "$AND`L_vip_unix`<'$UNIX'";
                } elseif ($leiall == 4)
                {

                    $leiall_test * 24 * 3600;
                    $date_int = HOST_UNIX - $leiall_test;
                    $WHERE .= "$AND`L_vip_unix`>'$date_int'AND`L_vip_unix`<'$UNIX'";

                } elseif ($leiall == 5)
                {
                    $WHERE .= "$AND`L_User_uid`$select'$leiall_test'";
                } elseif ($leiall == 6)
                {
                    $WHERE .= "$AND`L_key_info`$select'$leiall_test'";
                } elseif ($leiall == 7)
                {
                    $WHERE .= "$AND`L_vip_unix`$select'$leiall_test'";
                } elseif ($leiall == 8)
                {
                    $WHERE .= "$AND`L_re_date`$select'$leiall_test'";
                } else
                {
                    if ($leiall)
                        $this->admin_alert('请选择维护2.次节选');
                }

                if ($leiall > 5 & $leiall_test == 'Null')
                    $this->admin_alert('请选择*输入节选内容！');


                /**
                 * 节选没选择结束
                 */
            } else
            {

                if ($lie_x == '')
                    $this->admin_alert('请选择节选方式还是ID');
            }

            //--------------------------------------


            /**
             * 设置内容
             * 
             * 添加时间  天 
             * 减少时间  天 
             * 设置到期时间为当前 
             * 到期时间=  
             * 绑定特征=  
             * 删除 
             * +  ADDDATE(`L_vip_date`,interval $day1 day)
             * -  SSUBDATE(`vip_date_vip`,interval $day2 day)";
             * 
             */

            $UPDATE = "UPDATE`bs_php_pattern_login`";
            if ($radiobutton == 1)
            {
                if ($day1 == '')
                    $this->admin_alert('请选择输入+的秒/点');
                #$day1 = $day1 * 86400;
                $SET = $UPDATE . "SET`L_vip_unix`=`L_vip_unix`+'$day1'";

            } elseif ($radiobutton == 2)
            {
                if ($day2 == '')
                    $this->admin_alert('请选择输入-的秒/点'); //
                #$day1 = $day1 * 86400;
                $SET = $UPDATE . "SET`L_vip_unix`=`L_vip_unix`-'$day1'";

            } elseif ($radiobutton == 3)
            {

                //$date_txt = (int)strtotime((string )$date);
                //$SET = $UPDATE . "SET`L_vip_unix`='$date_txt'";

            } elseif ($radiobutton == 4)
            {
                if ($date_txt == '')
                    $this->admin_alert('请选择输入设置天数');

                //转换时间格式
                $date_txt = (int)strtotime((string )$date_txt);
                $SET = $UPDATE . "SET`L_vip_unix`='$date_txt'";

            } elseif ($radiobutton == 5)
            {
                if ($key == 'Null')
                    $this->admin_alert('请输入key内容');
                $SET = $UPDATE . "SET`L_key_info`='$key'";

            } elseif ($radiobutton == 6)
            {
                $SET = "DELETE FROM`bs_php_pattern_login`";
            }elseif ($radiobutton == 7)
            {
                $SET = $UPDATE . "SET`L_IsLock`='1'";
            }elseif ($radiobutton == 8)
            {
                $SET = $UPDATE . "SET`L_IsLock`='0'";
            } else
            {
                //功能介绍 end

                $this->admin_alert('请选择功能！');

            }


            $sql = "{$SET} {$WHERE}";
              
            $tmp = $this->db->Bsphp_my_query($sql);
            if ($tmp)
            {
                echo "SQL:".$sql;
                echo '执行成功';
                #$this->admin_alert('语句已经执行成功');
            } else
            {
                $this->admin_alert('执行失败！');
            }


        } //end 按钮结束


        /**
         * this get software array info
         */
        $sql = "SELECT`app_daihao`,`app_name`FROM`bs_php_appinfo`WHERE `app_MoShi` ='LoginTerm'";
        $tmp = $this->db->Bsphp_my_query($sql);


        include bs_lib::load_default_path();
    }
}

?>