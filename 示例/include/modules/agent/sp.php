<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if (BSPHP_SET != 'AGENT')
    die('Not,This File Not Can in Ie Modules');

bs_lib::load_modules_class('user', 'user_web');
bs_lib::load_modules_common('agent', 'agent');
/**
 * @代理 registration
 * 
 */
class sp extends user_web
{

    private $rc, $db, $user_array, $GLOBALS_LANGS, $user_str_log, $user, $seesion_uid;

    function __construct()
    {

        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();


        $this->user_str_log = bs_lib::load_langs_array('user', 'user_str_log');

        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->user = bs_lib::load_modules_class('user', 'user');


        $this->GLOBALS_LANGS = bs_lib::load_langs_array();


        if (bs_lib::get_configs_value('sys', 'stop_agent') == 0)
        {


            bs_lib::Alerts($this->GLOBALS_LANGS['sys'], bs_lib::get_configs_value('sys', 'stop_agent_info'));

            exit;
        }


        $USER_UID = get_session_value('USER_UID'); //登陆UID
        $this->user_array = $this->db->my_class_array('bs_php_user', 'user_uid', $USER_UID, ' * ');


        //代理权限验证
        if ($this->user_array['user_daili'] == 0)
        {
            alert2('你没有权限,请先登录。');
            location('index.php');
            exit;
        }

        /**
         * @登陆状态验证
         * 
         * 不等于登陆状态跳转到登陆页面
         */
        $login_log = $this->user->UserLoginInfo();
        if ($login_log != 1047)
        {
            alert2('你没有权限,请先登录。');
            location('index.php');

            exit;
        }


    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_table()
    {


        $act = set_get('act');
        $daihao = set_get('daihao');


        /**
         * 查询软件名称
         * 
         */

        $app_name = GetAppInfoNameArray();


        /**
         * 下拉框批量操作
         */
        $Submit_class = set_post('Submit_class');
        $all = set_post('all');
        $select_class = set_post('select_class');
        if ($Submit_class)
        {


            if ($select_class == 2)
            { //标记
                if ($this->db->my_in_SETdata('bs_php_cardseries', 'car_id', $all, 'car_BaoJi', '1'))
                {
                    alert('你选卡串号已经标记!');
                } else
                {
                    alert('标记,SQL执行失败,错误数据已保存!!');
                }


            } elseif ($select_class == 3)
            { //取消标记
                if ($this->db->my_in_SETdata('bs_php_cardseries', 'car_id', $all, 'car_BaoJi', '0'))
                {
                    alert('你选择卡串已经取消标记!');
                } else
                {
                    alert('取消标记,SQL执行失败,错误数据已保存!!');
                }


            } elseif ($select_class == 4)
            { //取消标记
                if ($this->db->my_in_SETdata('bs_php_cardseries', 'car_id', $all, 'car_zhuangtai', '1'))
                {
                    alert('你选择卡串已经冻结!');
                } else
                {
                    alert('冻结,SQL执行失败,错误数据已保存!!');
                }


            } elseif ($select_class == 5)
            { //取消标记
                if ($this->db->my_in_SETdata('bs_php_cardseries', 'car_id', $all, 'car_zhuangtai', '0'))
                {
                    alert('你选择卡串已经取消冻结!');
                } else
                {
                    alert('取消冻结,SQL执行失败,错误数据已保存!!');
                }


            } else
            {
                alert('你没有选择操作项目');
            }


        } //按钮接受


        /**
         * 卡号列表-标记
         */
        $biaoji = set_get('biaoji');
        if ($biaoji)
        {
            $id = set_get('biaojiid');

            if ($biaoji == 'nem')
            { //nem=1
                $this->db->my_in_SETdata('bs_php_cardseries', 'car_id', $id, 'car_BaoJi', 0);
            } else
            {
                $this->db->my_in_SETdata('bs_php_cardseries', 'car_id', $id, 'car_BaoJi', 1);
            }


        }


        /*翻页参数初始化*/


        /*系统翻页*/
        $FANYE = set_get('id');
        $shu = 25; //每页显示


        /**
         * @查询数量
         * 
         * 查询数据库数量
         */

        $sql = "select count(*)as'hangshu'from`bs_php_cardseries`WHERE`car_admin`='{$this->user_array['user_uid']}'";
        $tmp_arr = $this->db->Bsphp_my_array($sql);
        $zongshu = (int)$tmp_arr['hangshu'];


        /**
         * @计算分页
         */
        //$yueshuls=取整数就小于$zongyue不取整数的了*/
        $zongyue = $zongshu / $shu;
        $yueshuls = intval(strval($zongshu / $shu));
        if ($zongyue > $yueshuls)
            $zongyue = ++$yueshuls;


        /**
         * @定义页
         * 
         * 不为空状态前后和数据库的开始ID
         */
        if (!empty($FANYE))
        {
            $JIAN = $FANYE - 1; //上一页
            $JIA = $FANYE + 1; //下一页
            if ($JIAN <= 0)
                $JIAN = 1;
            if ($FANYE >= $zongyue)
                $JIA = $zongyue;
            $lingshi = $FANYE - 1;
            $db_ID = $lingshi * $shu;

        } else
        {


            /**
             * @ID为空时候定义
             */

            $db_ID = 0; //数据库ID
            $JIAN = 1; //上一页
            $JIA = 2; //下一页
            $FANYE = 1; //当前页
            if ($zongshu <= $shu)
                $JIA = 1;
        }


        /**
         * @定义前后顶状态
         */
        $ata = null;
        $atb = null;
        if ($FANYE >= $zongyue)
            $ata = " onclick=\"return alert('前面没有了哦!')\" ";
        if ($FANYE <= 1)
            $atb = " onclick=\"return alert('前面没有了哦!')\" ";


        /**
         * @接收搜搜参数
         * 
         * 这里表显示以搜搜模式来显示
         */
        /*取得搜搜内容*/
        $soso = set_get('soso');
        $soso_id = set_get('soso_id');


        /**
         * @定义搜搜字段
         * 
         * $soso_db_table = 要搜搜的字段
         */

        $soso_db_table = 'car_name';


        /*开始枚举*/ // $sql = "SELECT*FROM`bs_php_cardseries`WHERE`car_DaiHao`='$daihao'AND`$soso_db_table`LIKE CONVERT(_utf8'%$soso%'USING {$Coding_array[$Coding_array['coding']][0]})COLLATE {$Coding_array[$Coding_array['coding']][1]}  ORDER BY`car_id`DESC LIMIT $db_ID,$shu";

        $sql = "SELECT*FROM`bs_php_cardseries`WHERE`car_admin`='{$this->user_array['user_uid']}'AND`$soso_db_table`LIKE CONVERT(_utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  ORDER BY`car_id`DESC LIMIT $db_ID,$shu";

        $db_array_value = $this->db->Bsphp_my_query($sql);


        /*翻页URL*/
        $url = "index.php?m=agent&c=sp&a=table&daihao={$daihao}&soso_id=$soso_id&soso=$soso&soso_ok=%CB%D1%CB%D1&act=table&id=";
        /*载入html模版*/
        $fis = 'table';


        include bs_lib::load_default_path();
    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_add()
    {


        /**
         * 查询软件名称
         * 
         */

        $app_name = GetAppInfoNameArray();


        /**
         * 提交信息
         */
        $Submit = set_post('Submit');
        $select = set_post('select');
        $shu = set_post('shu');
        if ($Submit)
        {

            /**
             * 判断输入信息是否合理
             */
            if ($shu <= 0)
                alert('请输入制作的数量。');
            if ($shu > 100)
                alert('超出范围,每次制卡最大数量100张');
            if ($select == 0)
                alert('请选择你要制作的软件的充值卡类型');


            /**
             * 获取充值卡类型信息
             */
            $leixing_array = $this->db->my_class_array('bs_php_kalei', 'lei_id', $select, ' * ');


            if ((int)$leixing_array['lei_daili'] == -1)
                alert('卡类不存在');


            //一级代理处理模式
            if ($this->user_array['user_daili'] == 1)
            {


                $jiage = $leixing_array[4];


                /**
                 * 外来信息
                 */
                $uesr_zhe = $this->user_array[19];

                /**
                 * ==0表示没有折扣
                 * 
                 * 如果有折扣在吧价格计算下
                 * 
                 * $jiage=单价
                 */
                $zheinfo = null;
                if ($uesr_zhe > 0)
                { //==0表示没有折扣

                    $jiage = $jiage / 100 * $uesr_zhe * 10; //单张折扣后价格
                    $zheinfo = $uesr_zhe . "折后,";

                }
                $zong = $jiage * $shu;
                $cha = $this->user_array[10] - $zong;
                if ($zong > $this->user_array[10])
                {
                    alert("{$zheinfo}你当前价格还不够制作{$shu}张卡,还差{$cha}点");
                    exit;
                }


                //引入文件
                bs_lib::load_modules_common('applib', 'makecard');
                $zhi_date = ZhiZuoC($shu, $select, $this->user_array['user_uid'], '', $jiage);
                bs_lib::addappenlog('agent_ka_log', "UID:{$this->user_array['user_uid']},制作数量:$shu,金额:$zong,购卡折扣:$uesr_zhe,制作时间:$zhi_date", $this->user_array[1]);
                /**
                 * 在这里写金额扣除SQL
                 */
                $sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`-'{$zong}'WHERE`bs_php_user`.`user_uid`='{$this->user_array['user_uid']}';";
                $tmp = $this->db->Bsphp_my_query($sql);
                if (!$tmp)
                {
                    $tmp = $this->db->Bsphp_my_query($sql);
                }


                //二级代理模式
            } elseif ($this->user_array['user_daili'] == 2)
            {

                //获取1级代理价格
                $user_array_1 = $this->db->my_class_array('bs_php_user', 'user_user', $this->user_array['user_yao_User'], ' * ');
                if (!$user_array_1)
                {
                    alert('获取你的总销商信息失败！');
                    exit;

                }


                $uesr_zhe = $user_array_1['user_Zhe'];
                $jiage = $leixing_array[4];
                if ($uesr_zhe > 0)
                { //==0表示没有折扣
                    $jiage_1 = $jiage / 100 * $uesr_zhe * 10; //单张折扣后价格

                } else
                {
                    $jiage_1 = $jiage;

                }
                $zong_1 = $jiage_1 * $shu;
                //这里判断使用
                //$cha = $user_array_1 - $zong_1;
                if ($zong_1 > $user_array_1['user_rmb'])
                {
                    alert("制卡失败,请联系你客服人员查看！失败原因:总商账号余额不足");
                    exit;
                }


                //自己二级商
                //这里是代理价格加减价格
                $uesr_zhe = $this->user_array['user_Zhe'];

                if ($uesr_zhe >= 0)
                {

                    $zhe_lei = 1;
                } else
                {

                    $zhe_lei = 0;

                    //负数变整数
                    $a = 0 - $uesr_zhe;
                    $uesr_zhe = $a;

                }

                //开始计算加减价格多少钱
                if ($uesr_zhe !== 0)
                { //==0表示没有折扣

                    $jiagecha = $jiage_1 / 100 * $uesr_zhe * 10; //单张折扣后价格

                }


                //计算出代理二的单价格
                if ($zhe_lei == 1)
                {
                    $jiage_2 = $jiage_1 + $jiagecha;

                } else
                {
                    $jiage_2 = $jiage_1 - $jiagecha;

                }
                $zong_2 = $jiage_2 * $shu;
                $cha = $this->user_array['user_rmb'] - $zong_2;
                if ($zong_2 > $this->user_array['user_rmb'])
                {
                    alert("你当前价格还不够制作{$shu}张卡,还差{$cha}点");
                    exit;
                }


                //引入文件
                bs_lib::load_modules_common('applib', 'makecard');
                $zhi_date = ZhiZuoC($shu, $select, $this->user_array['user_uid'], '', $jiage_2);


                bs_lib::addappenlog('agent_ka_log', "总销:{$user_array_1['user_user']} 二级UID:{$this->user_array['user_uid']},制作数量:$shu,金额:$zong_2 元,制作时间:$zhi_date", $this->user_array[1]);
                bs_lib::addappenlog('agent_ka_log', "总销:{$user_array_1['user_user']} 二级UID:{$this->user_array['user_uid']},制作数量:$shu,金额:$zong_1 元,制作时间:$zhi_date", $user_array_1['user_user']);


                /**
                 * 在这里写金额扣除SQL
                 */


                $sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`-'{$zong_2}'WHERE`bs_php_user`.`user_uid`='{$this->user_array['user_uid']}';";
                $tmp = $this->db->Bsphp_my_query($sql);
                if (!$tmp)
                {
                    $tmp = $this->db->Bsphp_my_query($sql);
                }

                $sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`-'{$zong_1}'WHERE`bs_php_user`.`user_uid`='{$user_array_1['user_uid']}';";
                $tmp = $this->db->Bsphp_my_query($sql);
                if (!$tmp)
                {
                    $tmp = $this->db->Bsphp_my_query($sql);
                }


                //三级代理模式
            } elseif ($this->user_array['user_daili'] == 3)
            {


                $user_2 = $this->user_array['user_yao_User']; //自己
                $array = $this->db->my_class_array('bs_php_user', 'user_user', $user_2, ' * ');
                $user_1 = $array['user_yao_User'];


                //获取1级代理价格
                $user_array_1 = $this->db->my_class_array('bs_php_user', 'user_user', $user_1, ' * ');
                if (!$user_array_1)
                {
                    alert('获取你的总销商信息失败！');
                    exit;

                }
                $uesr_zhe = $user_array_1['user_Zhe'];
                $jiage = $leixing_array[4];
                if ($uesr_zhe > 0)
                { //==0表示没有折扣
                    $jiage_1 = $jiage / 100 * $uesr_zhe * 10; //单张折扣后价格

                } else
                {
                    $jiage_1 = $jiage;

                }


                $zong_1 = $jiage_1 * $shu;
                //这里判断使用
                //$cha = $user_array_1 - $zong_1;
                if ($zong_1 > $user_array_1['user_rmb'])
                {
                    alert("制卡失败,请联系你客服人员查看！失败原因:总商账号余额不足");
                    exit;
                }


                //二级商
                //这里是代理价格加减价格
                $user_array_2 = $this->db->my_class_array('bs_php_user', 'user_user', $user_2, ' * ');
                if (!$user_array_2)
                {
                    alert('获取你的总销商信息失败！');
                    exit;

                }
                $uesr_zhe = $user_array_2['user_Zhe'];
                if ($uesr_zhe >= 0)
                {

                    $zhe_lei = 1;
                } else
                {

                    $zhe_lei = 0;

                    //负数变整数
                    $a = 0 - $uesr_zhe;
                    $uesr_zhe = $a;

                }

                //开始计算加减价格多少钱
                if ($uesr_zhe !== 0)
                { //==0表示没有折扣

                    $jiagecha = $jiage_1 / 100 * $uesr_zhe * 10; //单张折扣后价格

                }


                //计算出代理二的单价格
                if ($zhe_lei == 1)
                {
                    $jiage_2 = $jiage_1 + $jiagecha;

                } else
                {
                    $jiage_2 = $jiage_1 - $jiagecha;

                }
                $zong_2 = $jiage_2 * $shu;
                $cha = $user_array_2['user_rmb'] - $zong_2;
                if ($zong_2 > $user_array_2['user_rmb'])
                {
                    alert("制卡失败,请联系你客服人员查看。余额不足！");
                    exit;
                }


                //自己三级商
                //这里是代理价格加减价格
                //$user_array_3 = $this->db->my_class_array('bs_php_user', 'user_user', $user_2, ' * ');
                $uesr_zhe = $this->user_array['user_Zhe'];
                if ($uesr_zhe >= 0)
                {

                    $zhe_lei = 1;
                } else
                {

                    $zhe_lei = 0;

                    //负数变整数
                    $a = 0 - $uesr_zhe;
                    $uesr_zhe = $a;

                }

                //开始计算加减价格多少钱
                if ($uesr_zhe !== 0)
                { //==0表示没有折扣

                    $jiagecha = $jiage_2 / 100 * $uesr_zhe * 10; //单张折扣后价格

                }


                //计算出代理二的单价格
                if ($zhe_lei == 1)
                {
                    $jiage_3 = $jiage_2 + $jiagecha;

                } else
                {
                    $jiage_3 = $jiage_2 - $jiagecha;

                }
                $zong_3 = $jiage_3 * $shu;
                $cha = $this->user_array['user_rmb'] - $zong_3;
                if ($zong_3 > $this->user_array['user_rmb'])
                {
                    alert("你当前价格还不够制作{$shu}张卡,还差{$cha}点");
                }


                //引入文件
                bs_lib::load_modules_common('applib', 'makecard');
                $zhi_date = ZhiZuoC($shu, $select, $this->user_array['user_uid'], '', $jiage_3);


                bs_lib::addappenlog('agent_ka_log', "总销:{$user_array_1['user_user']} 二级:{$user_array_2['user_user']} 三级:{$this->user_array['user_user']},制作数量:$shu,金额:$zong_3 元,制作时间:$zhi_date", $this->user_array['user_user']);
                bs_lib::addappenlog('agent_ka_log', "总销:{$user_array_1['user_user']} 二级:{$user_array_2['user_user']} 三级:{$this->user_array['user_user']},制作数量:$shu,金额:$zong_2 元,制作时间:$zhi_date", $user_array_2['user_user']);
                bs_lib::addappenlog('agent_ka_log', "总销:{$user_array_1['user_user']} 二级:{$user_array_2['user_user']} 三级:{$this->user_array['user_user']},制作数量:$shu,金额:$zong_1 元,制作时间:$zhi_date", $user_array_1['user_user']);


                /**
                 * 在这里写金额扣除SQL
                 */


                $sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`-'{$zong_3}'WHERE`bs_php_user`.`user_uid`='{$this->user_array['user_uid']}';";
                $tmp = $this->db->Bsphp_my_query($sql);
                if (!$tmp)
                {
                    $tmp = $this->db->Bsphp_my_query($sql);
                }

                $sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`-'{$zong_1}'WHERE`bs_php_user`.`user_uid`='{$user_array_1['user_uid']}';";
                $tmp = $this->db->Bsphp_my_query($sql);
                if (!$tmp)
                {
                    $tmp = $this->db->Bsphp_my_query($sql);
                }

                $sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`-'{$zong_2}'WHERE`bs_php_user`.`user_uid`='{$user_array_2['user_uid']}';";
                $tmp = $this->db->Bsphp_my_query($sql);
                if (!$tmp)
                {
                    $tmp = $this->db->Bsphp_my_query($sql);
                }


            }


            location('index.php?m=agent&c=sp&a=show&date=' . $zhi_date . '&id=' . $select);
            //print_R($leixing_array);

        }


        /**
         * 查询所有充值卡类型and `bs_php_kalei`.`lei_daihao`=`bs_php_appinfo`.`app_daihao`  WHERE `lei_daili` !='-1'  ORDER BY `lei_daihao `ASC
         */
        $sql = "SELECT `bs_php_kalei`.* FROM `bs_php_kalei`,`bs_php_appinfo` WHERE `lei_daili`>-1 and `bs_php_appinfo`.`app_daihao`=`bs_php_kalei`.`lei_daihao` ";
        $tmp = $this->db->Bsphp_my_query($sql);


        include bs_lib::load_default_path();
    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_show()
    {


        $date = set_get('date');
        $id = set_get('id');


        /**
         * 查询刚刚制作充值卡列表
         */
        //引入文件
        bs_lib::load_modules_common('applib', 'makecard');
        $leixing = $this->db->my_class_array('bs_php_kalei', 'lei_id', $id, '`lei_daihao`');
        $app_name = $this->db->my_class_array('bs_php_appinfo', 'app_daihao', $leixing, '`app_name`');
        $tset = kaliebiao_USER($date, $app_name, $this->user_array['user_uid']);


        include bs_lib::load_default_path();
    }
    /**
     * @控制面板-登录面板
     * 
     */
    function call_adduser()
    {


        $date = set_get('date');
        $id = set_get('id');
        
        $qq = set_post('qq');


        $daihao = 666666;
       
        $name = set_post('name');
        $beizhu = set_post('beizhu');
        
        
        
        
        
        if($qq){
            
            
            
        $sql = "SELECT * FROM`bs_php_pattern_login` where `L_User_uid` ='$qq' and `L_daihao`='$daihao';";
        $AAAA = $this->db->Bsphp_my_array($sql);
            
            if($AAAA){
                
                
            $sql = "UPDATE`bs_php_pattern_login`SET `L_beizhu`='{$beizhu}' WHERE `L_User_uid`='$qq' ;";
            $this->db->Bsphp_my_query($sql);
            
            alert('已经存在,已经修改备注');
            }else{
                
                
                
            }
            
            
            bs_lib::load_modules_common('applib', 'appen_appuser');
            Login_add_key($qq, $daihao, time()+2592000, '8080');
            
            
            $sql = "UPDATE`bs_php_pattern_login`SET `L_beizhu`='{$beizhu}' WHERE `L_User_uid`='$qq' ;";
            $this->db->Bsphp_my_query($sql);
            
            
            alert('指令执行,到对应软件用户下查看即可');
            
        }
        
        

        include bs_lib::load_default_path();
    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_password()
    {


        $ok = set_post('ok');
        if ($ok)
        {
            //修改密码接收参数
            $password = set_post('password');
            $pwd = set_post('pwd');
            $pwd2 = set_post('pwd2');


            //修改密码
            $log = $this->user->UserModifyPassWord($this->user_array['0'], $password, $pwd, $pwd2);
            if ($log == 1031)
            {
                alert('密码修改成功,请重新登陆系统。');
            } else
            {
                alert($this->user_str_log[$log]);
            }


        }


        include bs_lib::load_default_path();

    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_zhe()
    {
        $users = set_get('users');

        $ok = set_post('ok');
        if ($ok)
        {
            //修改密码接收参数
            $users = set_post('users');
            $money = set_post('money');


            $sql = "UPDATE`bs_php_user`SET `user_Zhe`='$money' WHERE `bs_php_user`.`user_user`='$users' and `user_yao_User`='{$this->user_array['user_user']}' ;";
            //echo $sql;
            //exit;

            $this->db->Bsphp_my_query($sql);

            alert('折扣设置成功！');


        }

        $array_user = $this->db->my_class_array('bs_php_user', 'user_user', $users, ' * ');
        include bs_lib::load_default_path();

    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_useradd()
    {


        $baocun = set_post('ok');
        if ($baocun)
        {

            if ($this->user_array['user_daili'] == 3)
            {
                alert('你无权添加代理商');
            }

            $user = set_post('user');
            $pwd = set_post('pwd');
            $qq = set_post('qq');
            $mail = set_post('mail');
            $yao_user = $this->user_array['user_user'];

            $log = $this->user->UserAdd($user, $pwd, $pwd, $qq, $mail, $yao_user);
            $dengji = $this->user_array['user_daili'] + 1;

            if ($log == 1005)
            {
                $sql = "UPDATE`bs_php_user`SET`user_daili`='$dengji' WHERE `bs_php_user`.`user_user`='$user';";
                //echo $sql;
                // exit;

                $this->db->Bsphp_my_query($sql);
            }

            alert($log . $this->user_str_log[$log]);

        }

        include bs_lib::load_default_path();

    }

    /**
     * @控制面板-登录面板
     * 
     */
    function call_useradds()
    {


        $baocun = set_post('ok');
        if ($baocun)
        {
            $user = set_post('user');
            $pwd = set_post('pwd');
            $qq = set_post('qq');
            $mail = set_post('mail');
            $yao_user = set_post('yao_user');

            $log = $this->user->UserAdd($user, $pwd, $pwd, $qq, $mail, $yao_user);


            alert($log . $this->user_str_log[$log]);

        }

        include bs_lib::load_default_path();

    }

    /**
     * @控制面板-登录面板
     * 
     */
    function call_userchong()
    {


        $baocun = set_post('ok');
        if ($baocun)
        {
            $user = set_post('user');
            $kaid = set_post('kaid');
            $kapwd = set_post('kapwd');
bs_lib::load_modules_common('applib', 'appen_appuser');

            $log = $this->user->UserChong($user, $kaid, $kapwd);


            alert($log . $this->user_str_log[$log]);

        }

        include bs_lib::load_default_path();

    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_money()
    {
        $baocun = set_post('ok');
        if ($baocun)
        {
            $heuser = set_post('heuser');
            $money = set_post('money');
            $select = set_post('select');

            $array_user = $this->db->my_class_array('bs_php_user', 'user_user', $heuser, ' * ');
            if (!$array_user)
            {
                alert('账号不存在！');
            }


            if ($array_user['user_yao_User'] !== $this->user_array['user_user'])
            {
                alert('加减点账号非代理下线,失败操作！');
            }


            if ($select == 1)
            {

                $A = $array_user['user_rmb'] - $money;
                if ($A < 0)
                {
                    alert('减点失败,余点不够。');


                }


                $sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`-'$money' WHERE`bs_php_user`.`user_uid`='{$array_user[0]}';";
                $this->db->Bsphp_my_query($sql);
                bs_lib::addappenlog('money_buy_log', "{$this->user_array[1]}向 $array_user[1] 减 $money 点", $this->user_array[1]);
                alert('成功减除' . $money . '点');

            } else
            {

                if ($money < 0)
                {
                    alert('加减点不能小于0');
                }


                $bei = $this->user_array['user_rmb'] * 10;
                if ($money > $bei)
                {
                    alert('你好加减点不能大于账号剩余点10倍,' . $bei . '点');
                }


                $sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$money' WHERE`bs_php_user`.`user_uid`='{$array_user[0]}';";
                $this->db->Bsphp_my_query($sql);
                bs_lib::addappenlog('money_buy_log', "{$this->user_array[1]}向 $array_user[1] 加 $money 元", $this->user_array[1]);
                alert('成功增加' . $money . '点');

            }


        }


        /*
        $baocun = set_post('ok');
        if ($baocun)
        {
        $heuser = set_post('heuser');
        $money = set_post('money');
        $lock = set_post('lock');
        
        
        $array_user=$this->db->my_class_array('bs_php_user','user_user',$heuser,' * ');
        if(!$array_user){
        alert('账号不存在！');
        }
        
        if($array_user['user_user']!=$this->user_array['user_yao_User'] and $lock==1){
        alert('输入账并非你邀请的代理！');
        }
        
        
        if($money < 0){
        alert('转赠金额不能小于0');
        }
        
        
        if($this->user_array['user_rmb']<=0){
        alert('余额不足！');
        }
        
        
        
        if($money  > $this->user_array['user_rmb']){
        alert('A.转账额不能大于'.$this->user_array['user_rmb'] );
        }
        $A=$this->user_array['user_rmb']-$money;
        if($A<0){
        alert('B.转账额不能大于'.$this->user_array['user_rmb'] );
        }
        
        
        $sql="UPDATE`bsphp_user`SET`user_rmb`=`user_rmb`-'$money' WHERE`bsphp_user`.`user_uid`='{$this->user_array[0]}';";
        $this->db->Bsphp_my_query($sql); 
        
        $sql="UPDATE`bsphp_user`SET`user_rmb`=`user_rmb`+'$money' WHERE`bsphp_user`.`user_uid`='{$array_user[0]}';";
        $this->db->Bsphp_my_query($sql); 
        
        bs_lib::addappenlog('money_buy_log', "{$this->user_array[1]}(我) 向 $array_user[1] 转 $money 元",$this->user_array[1]);
        
        bs_lib::addappenlog('money_buy_log', "{$this->user_array[1]} 向 $array_user[1](我) 转 $money 元",$array_user[1]);


        
        alert('转账成功,请联系查收！');
        
        }
        */


        include bs_lib::load_default_path();

    }

    /**
     * @控制面板-登录面板
     * 
     */
    function call_tuimoeny()
    {


        $ok = set_post('ok');
        $kaid = set_post('kaid');
        $kapwd = set_post('kapwd');

        if ($ok)
        {

            $sql = "SELECT*FROM`bs_php_cardseries`WHERE`car_name`=\"$kaid\"AND`car_pwd`=\"$kapwd\"";
            $ka_arr = $this->db->Bsphp_my_array($sql);
            if(!$ka_arr)  alert('充值卡号/密码错误！');
            
            
            if($ka_arr['car_IsLock']==0){
                alert('充值卡是未激活使用的');
                exit;
            }
            
            if($ka_arr['car_admin']!==$this->user_array['user_uid']){
                alert('充值卡非你制作充值卡无法清退');
                exit;
            }
            
            
           $inttime= strtotime($ka_arr['car_pur_date']);
           $inttime_vip=HOST_UNIX-$inttime;
           
      
           if($inttime_vip > 864000){
            
                alert('激活大于10天无法退返');
                exit;
           }
            
            
        $car_TianShu = $ka_arr['car_TianShu'];
        if ($ka_arr['car_type'] == 1)
        {
            $daty_shu = 2678400;
        } elseif ($ka_arr['car_type'] == 2)
        {
            $daty_shu = 31536000;
        } elseif ($ka_arr['car_type'] == 3)
        {
            $daty_shu = 3600;
        } elseif ($ka_arr['car_type'] == 4)
        {
            $daty_shu = 60;
        } elseif ($ka_arr['car_type'] == 5)
        {
            $daty_shu = 1;
        } else
        {
            $daty_shu = 86400;
        }
        $ka_date_unix = $car_TianShu * $daty_shu;
        
        
        if($inttime_vip>$ka_date_unix){
            alert('充值卡已经使用过期作废');
            exit;
            
        }
        
        $ka_date_unix_5=$ka_date_unix/5;

        if($inttime_vip>$ka_date_unix_5){
            alert('此卡已经使用超过1/5时间,系统疑似恶意退款！');
            exit;
            
        }
        
        
        





        }


        include bs_lib::load_default_path();
    }


    /**
     * @控制面板-登录面板
     * 
     */
    function call_dailiuser()
    {


        $act = set_get('act');
        $daihao = set_get('daihao');


        /**
         * 查询软件名称
         * 
         */

        $app_name = GetAppInfoNameArray();


        /**
         * 下拉框批量操作
         */
        $Submit_class = set_post('Submit_class');
        $all = set_post('all');
        $select_class = set_post('select_class');
        if ($Submit_class)
        {

            /*
            if ($select_class == 2)
            { //标记
            if ($this->db->Bsphp_my_query('bs_php_cardseries', 'car_id', $all, 'car_BaoJi', '1')
            {
            alert( '选择账号已经冻结!');
            } else
            {
            alert( '标记,SQL执行失败,错误数据已保存!!');
            }


            } elseif ($select_class == 3)
            { //取消标记
            if ($this->db->Bsphp_my_query('bs_php_cardseries', 'car_id', $all, 'car_BaoJi', '0'))
            {
            alert( '你选择卡串已经取消标记!');
            } else
            {
            alert( '取消标记,SQL执行失败,错误数据已保存!!');
            }


            } else
            {
            alert('你没有选择操作项目');
            }



            */
        } //按钮接受


        /*系统翻页*/
        $FANYE = set_get('id');
        $shu = 25; //每页显示


        /**
         * @查询数量
         * 
         * 查询数据库数量
         */

        $sql = "select count(*)as'hangshu'from`bs_php_user`WHERE`user_yao_User`='{$this->user_array['user_user']}'";
        $tmp_arr = $this->db->Bsphp_my_array($sql);
        $zongshu = (int)$tmp_arr['hangshu'];


        /**
         * @计算分页
         */
        //$yueshuls=取整数就小于$zongyue不取整数的了*/
        $zongyue = $zongshu / $shu;
        $yueshuls = intval(strval($zongshu / $shu));
        if ($zongyue > $yueshuls)
            $zongyue = ++$yueshuls;


        /**
         * @定义页
         * 
         * 不为空状态前后和数据库的开始ID
         */
        if (!empty($FANYE))
        {
            $JIAN = $FANYE - 1; //上一页
            $JIA = $FANYE + 1; //下一页
            if ($JIAN <= 0)
                $JIAN = 1;
            if ($FANYE >= $zongyue)
                $JIA = $zongyue;
            $lingshi = $FANYE - 1;
            $db_ID = $lingshi * $shu;

        } else
        {


            /**
             * @ID为空时候定义
             */

            $db_ID = 0; //数据库ID
            $JIAN = 1; //上一页
            $JIA = 2; //下一页
            $FANYE = 1; //当前页
            if ($zongshu <= $shu)
                $JIA = 1;
        }


        /**
         * @定义前后顶状态
         */
        $ata = null;
        $atb = null;
        if ($FANYE >= $zongyue)
            $ata = " onclick=\"return alert('前面没有了哦!')\" ";
        if ($FANYE <= 1)
            $atb = " onclick=\"return alert('前面没有了哦!')\" ";


        /**
         * @接收搜搜参数
         * 
         * 这里表显示以搜搜模式来显示
         */
        /*取得搜搜内容*/
        $soso = set_get('soso');
        $soso_id = set_get('soso_id');


        /**
         * @定义搜搜字段
         * 
         * $soso_db_table = 要搜搜的字段
         */

        $soso_db_table = 'user_user';


        /*开始枚举*/ // $sql = "SELECT*FROM`bs_php_cardseries`WHERE`car_DaiHao`='$daihao'AND`$soso_db_table`LIKE CONVERT(_utf8'%$soso%'USING {$Coding_array[$Coding_array['coding']][0]})COLLATE {$Coding_array[$Coding_array['coding']][1]}  ORDER BY`car_id`DESC LIMIT $db_ID,$shu";


        $sql = "SELECT*FROM`bs_php_user`WHERE`$soso_db_table`LIKE CONVERT( _utf8'%$soso%'USING utf8)COLLATE utf8_general_ci AND `user_yao_User`='{$this->user_array['user_user']}'   ORDER BY`user_uid` DESC LIMIT $db_ID,$shu";
        //echo $sql;
        $db_array_value = $this->db->Bsphp_my_query($sql);


        /*翻页URL*/
        $url = "index.php?m=agent&c=sp&a=dailiuser&daihao={$daihao}&soso_id=$soso_id&soso=$soso&soso_ok=%CB%D1%CB%D1&act=table&id=";
        /*载入html模版*/
        $fis = 'table';


        include bs_lib::load_default_path();
    }


}

?>