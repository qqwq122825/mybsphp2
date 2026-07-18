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

    private $rc, $db, $user_array, $GLOBALS_LANGS, $user_str_log, $user, $seesion_uid,$Grade;

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
        
        if($this->user_array['user_daili']==1){
            $this->Grade = 1;
        }else if($this->user_array['user_daili']==2){
            $this->Grade = 2;
        }else{
            $this->Grade = 3;
        }
        
        
        
        

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
        
        
        
        $ok = set_get('ok');
        $ids = set_get('ids');
        if ($ok==1)
        {
            
            #判断是否有冻结权限
            if(bs_lib::get_configs_value('agents',"car_sdate_{$this->Grade}")==1){
            
            $sql = "SELECT *  FROM  `bs_php_cardseries`  WHERE  `car_id` = $ids AND `car_admin`='{$this->user_array['user_uid']}';";
            $array = $this->db->Bsphp_my_array($sql);
            if(!$array){
               alert('不存在'); 
            }
            
            
            
            if($array['car_IsLock']==1){
               
                 $car_pur_date = $array['car_pur_date'];
                 $car_pur_date = strtotime($car_pur_date);
                 
                 $car_on_time = bs_lib::get_configs_value('agents',"car_on_time_{$this->Grade}");
                 $car_pur_date = HOST_UNIX-$car_pur_date;
                 if($car_pur_date>$car_on_time){
                    
                    alert('激活时间超'.$car_on_time.'秒,无法冻结');
                    
                 }
  
            }
            
            
            
       	    if(bs_lib::get_configs_value('agents',"car_off_{$this->Grade}")==1){
       	        if($array['car_IsLock']==0){
       	            alert('未激活卡不能冻结');
       	        }
       	    }
            
            
            
            
             
             $sql = "UPDATE  `bs_php_cardseries` SET  `car_zhuangtai` =  '1' WHERE  `bs_php_cardseries`.`car_id` = $ids AND `car_admin`='{$this->user_array['user_uid']}';";
             $this->db->Bsphp_my_query($sql);
                 
                 
                 #判断是否关联登录标记一起冻结
                 if(bs_lib::get_configs_value('agents',"car_on_{$this->Grade}")==1){
             /*
//echo $array['car_cong_user'];
                    if($array['car_cong_user']=='cardid'){          
                      $sql = "UPDATE  `bs_php_pattern_login` SET   `L_IsLock` =  '1' WHERE `L_daihao`='{$array['car_DaiHao']}' AND(`L_User_uid` ='{$array['car_name']}' OR `L_ic_pwd`='{$array['car_name']}');";
                     
                      $this->db->Bsphp_my_query($sql);
                    }else{
                      $sql = "UPDATE  `bs_php_pattern_login` SET   `L_IsLock` =  '1' WHERE `L_daihao`='{$array['car_DaiHao']}' AND `L_User_uid` ='{$array['car_chong_uid']}' AND `L_ic_pwd` ='{$array['car_chong_uid']}';";
                      $this->db->Bsphp_my_query($sql);
                      
                     
                      
                    }
                    */
                    
                   
                      $sql = "UPDATE  `bs_php_pattern_login` SET   `L_IsLock` =  '1' WHERE `L_daihao`='{$array['car_DaiHao']}' AND(`L_User_uid` ='{$array['car_name']}' OR `L_ic_pwd`='{$array['car_name']}');";
                      $this->db->Bsphp_my_query($sql);
                   
                      $sql = "UPDATE  `bs_php_pattern_login` SET   `L_IsLock` =  '1' WHERE `L_daihao`='{$array['car_DaiHao']}' AND `L_User_uid` ='{$array['car_chong_uid']}' AND `L_ic_pwd` ='{$array['car_chong_uid']}';";
                      $this->db->Bsphp_my_query($sql);

                    
                    // echo $sql;
                    
                }
                
                
                alert('冻结成功');
                 
                    
            }else{
                alert('你没有权限冻结');
            }       
                    
        }
        
        
        
        
        
        $ok = set_get('ok');
        if ($ok==2)
        {
            #判断是否有权限
            if(bs_lib::get_configs_value('agents',"car_sdate_no_{$this->Grade}")==1){
                
                
            $sql = "SELECT *  FROM  `bs_php_cardseries`  WHERE  `car_id` = $ids AND `car_admin`='{$this->user_array['user_uid']}';";
            $array = $this->db->Bsphp_my_array($sql);
            if(!$array){
               alert('不存在'); 
            }
                
                
                
                
             $ids = set_get('ids');
             $sql = "UPDATE  `bs_php_cardseries` SET  `car_zhuangtai` =  '0' WHERE  `bs_php_cardseries`.`car_id` =$ids AND `car_admin`='{$this->user_array['user_uid']}';";
             $this->db->Bsphp_my_query($sql);
             
                 #判断是否关联登录标记一起冻结
                 if(bs_lib::get_configs_value('agents',"car_on_{$this->Grade}")==1){

                    
                    if($array['car_cong_user']=='cardid'){
                      $sql = "UPDATE  `bs_php_pattern_login` SET   `L_IsLock` =  '0' WHERE `L_daihao`='{$array['car_DaiHao']}' AND   `L_User_uid` ='{$array['car_name']}';";
                      $this->db->Bsphp_my_query($sql);
                    }else{
                      $sql = "UPDATE  `bs_php_pattern_login` SET   `L_IsLock` =  '0' WHERE `L_daihao`='{$array['car_DaiHao']}' AND `L_User_uid` ='{$array['car_chong_uid']}' AND `L_ic_pwd` ='{$array['car_chong_uid']}';";
                      $this->db->Bsphp_my_query($sql);
                    }
                    
                    
                }
                
                
                alert('解结成功');
                    
                    
            }else{
               alert('没有权限解冻'); 
            }
        }
        
        
        
        
        
        $ok = set_get('ok');
        if ($ok == 3)
        {
            
            #判断使用权限
            if(bs_lib::get_configs_value('agents',"car_delete_{$this->Grade}")==1){
                
           
            $sql = "SELECT *  FROM  `bs_php_cardseries`  WHERE  `car_id` = $ids AND `car_admin`='{$this->user_array['user_uid']}';";
            $array = $this->db->Bsphp_my_array($sql);
            if(!$array){
               alert('不存在'); 
            }
            
            
            
            if($array['car_IsLock']==1){
               
                 $car_pur_date = $array['car_pur_date'];
                 $car_pur_date = strtotime($car_pur_date);
                 
                 $car_on_time = bs_lib::get_configs_value('agents',"car_delete_time_{$this->Grade}");
                 $car_pur_date = HOST_UNIX-$car_pur_date;
                 if($car_pur_date>$car_on_time){
                    
                    alert('激活时间超'.$car_on_time.'秒,无法冻结');
                    
                 }
  
            }
            
            
            
            $ids = set_get('ids');
            $sql = "DELETE FROM `bs_php_cardseries`  WHERE  `bs_php_cardseries`.`car_id` =$ids AND `car_admin`='{$this->user_array['user_uid']}';";
            $this->db->Bsphp_my_query($sql);
            
                 #判断是否关联登录标记一起冻结
                 if(bs_lib::get_configs_value('agents',"car_on_{$this->Grade}")==1){
                    
                    if($array['car_cong_user']=='cardid'){   
                      $sql = "DELETE FROM `bs_php_pattern_login`  WHERE `L_daihao`='{$array['car_DaiHao']}' AND   `L_User_uid` ='{$array['car_name']}';";
                      $this->db->Bsphp_my_query($sql);
                    }else{
                      $sql = "DELETE FROM `bs_php_pattern_login`  WHERE `L_daihao`='{$array['car_DaiHao']}' AND `L_User_uid` ='{$array['car_chong_uid']}' AND `L_ic_pwd` ='{$array['car_chong_uid']}';";
                      $this->db->Bsphp_my_query($sql);
                    }
                    
                    
                  }  
           }else{
               alert('没有权限删除'); 
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
        $url = "index.php?m=agent_new&c=sp&a=table&daihao={$daihao}&soso_id=$soso_id&soso=$soso&soso_ok=%CB%D1%CB%D1&act=table&id=";
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
        $beizhu = set_post('beizhu');
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
                $zhi_date = ZhiZuoC($shu, $select, $this->user_array['user_uid'], '',-10,'', $beizhu);
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


        

            location('index.php?m=agent_new&c=sp&a=show&date=' . $zhi_date . '&id=' . $select);
            //print_R($leixing_array);

        }


        /**
         * 查询所有充值卡类型and `bs_php_kalei`.`lei_daihao`=`bs_php_appinfo`.`app_daihao`  WHERE `lei_daili` !='-1'  ORDER BY `lei_daihao `ASC
         */
        $sql = "SELECT `bs_php_kalei`.* FROM `bs_php_kalei`,`bs_php_appinfo` WHERE `lei_daili`>-1 and `bs_php_appinfo`.`app_daihao`=`bs_php_kalei`.`lei_daihao` ";
        //echo $sql;
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


    function call_cardinfo()
    {


        //读取列表
        $sql = "SELECT  `app_daihao` ,  `app_name` ,  `app_MoShi` FROM  `bs_php_appinfo` LIMIT 0 , 30";
        $db_array_value_app = $this->db->Bsphp_my_query($sql);



        //获取软件模式
        #$moshi_array = GetAppMoShiArray($app_array['app_MoShi']);


        $carsys_arr = bs_lib::load_langs_array('applib', 'admin_card');


        $daihao = set_get('daihao');


        $date1 = set_get('date1');
        $date2 = set_get('date2');
        $soso = set_get("soso");

        $soso_id = set_get("soso_id");

        if($soso_id==0){

            $sql = "SELECT * FROM  `bsphp_user`WHERE  `user_user` ='$soso'     LIMIT 1";
            $uids_arr = $this->db->Bsphp_my_array($sql);
            $soso_uid =$uids_arr['user_uid'];
        }else{
            $soso_uid = $soso;
        }


        if ($daihao > 0) {


            $app_array = get_appdaihao_info_array($daihao);
            $app_name = $app_array['app_name'];
            $app_daihao = $app_array['app_daihao'];



            if($soso ==''){
                $soso_sql = " and `car_admin` = '{$this->user_array[0]}' ";
            }else{
                $sql = "SELECT * FROM  `bsphp_user`WHERE  `user_user` ='$soso'  and `user_yao_User` = '{$this->user_array[1]}'  LIMIT 1";
                $uids_arr = $this->db->Bsphp_my_array($sql);
                $soso_uid =$uids_arr['user_uid'];

               // $soso_sql = " and `user_yao_User` = '{$this->user_array[0]}' And ";

                $soso_sql = " and `car_admin` = '{$soso_uid}' ";

            }

;

            /*开始枚举 AND `car_reDATE` > '$date1 00:00:00' AND `car_reDATE` < '$date2 24:00:00' */
            $sql = "SELECT *, COUNT( * ) AS  `row` ,  `car_Lei`
FROM  `bs_php_cardseries`

where `car_DaiHao` = '$app_daihao'   $soso_sql
GROUP BY  `car_Lei`
ORDER BY  `car_Lei`
LIMIT 1000";

            //echo $sql;
            $db_array_value = $this->db->Bsphp_my_query($sql);






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
            $pwd1 = set_post('pwd1');
            $pwd2 = set_post('pwd2');


            //修改密码
            $log = $this->user->UserModifyPassWord($this->user_array['0'], $password, $pwd1, $pwd2);
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
            $xiaji = set_post('xiaji');
            
            
            $yao_user = $this->user_array['user_user'];

            $log = $this->user->UserAdd($user, $pwd, $pwd, $qq, $mail, $yao_user);
            
            
            if($xiaji==1){
               $dengji=3; 
            }else{
                $dengji=2;
            }
            
            

            if ($log == 1005)
            {
                $sql = "UPDATE `bs_php_user`SET`user_daili`='$dengji',`user_anget_carid`='{$this->user_array['user_anget_carid']}' WHERE `bs_php_user`.`user_user`='$user';";
               // echo $sql;
                
                $this->db->Bsphp_my_query($sql);
            }
 //exit;

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
    function call_app()
    {
        $baocun = set_post('ok');
        $ids = set_get('ids');
        $user_Zhe = set_post('user_Zhe');
        $user_anget_carid = set_post('user_anget_carid');
        $user_beizhu = set_post('user_beizhu');
        $user_is_pwd = set_post('user_is_pwd');
        $array_user = $this->db->my_class_array('bs_php_user', 'user_uid', $ids, ' * ');
             if (!$array_user)
            {
                alert('账号不存在！');
            }

        if ($baocun)
        {
            
            
            if($user_is_pwd!==''){
                $user_is_pwd = password_md6($user_is_pwd);
                $sqls = " `user_pwd`='$user_is_pwd' ,  ";
            }else{
                $sqls ='';
            }
            
            
            
            
     $sql="ALTER TABLE  `bs_php_user` ADD  `user_anget_beizhu` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT  '代理商备注' AFTER  `user_anget_carid`";
     $this->db->Bsphp_my_query($sql);
     
     $sql ="ALTER TABLE  `bs_php_user` CHANGE  `user_Zhe`  `user_Zhe` FLOAT( 9, 1 ) NULL DEFAULT  '0.0'";
     $this->db->Bsphp_my_query($sql);
     
     
     
          $sql = "UPDATE`bs_php_user`SET`user_anget_beizhu`='$user_beizhu',`user_anget_carid`='$user_anget_carid',$sqls `user_Zhe`='$user_Zhe'WHERE`bs_php_user`.`user_uid`='$ids' And `user_yao_User`='{$this->user_array['user_user']}';";
       $tmp=$this->db->Bsphp_my_query($sql);
//echo $sql;
                
                alert('信息修改成功');

          

        }



        /*******查询卡类列表*****/
        $sql = "SELECT*FROM`bs_php_kalei`";
        $db_array_value = $this->db->Bsphp_my_query($sql);


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
            
            
            if ($array_user['user_yao_User'] != $this->user_array[1])
            {
                alert('您没有权限,他不是您下级代理');
            }
            


                if ($money > 10000)
                {
                    alert('单次转账不能大于10000');
                }
                
                
                if($money > 0){
                    
                  $bei = $this->user_array['user_rmb'] ;
                  if ($money > $bei)
                   {
                      alert('点不足');
                   }
                  
                    
                }else{
                    
                    $as = $this->$array_user['user_rmb'] - $money;
                    if($as < 0){
                        
                        alert('扣除账号点数不足');
                    }
 
                    
                }



                $sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$money' WHERE`bs_php_user`.`user_uid`='{$array_user[0]}';";
                $this->db->Bsphp_my_query($sql);
                bs_lib::addappenlog('money_buy_log', "{$this->user_array[1]}向 $array_user[1] 你加 $money 点", $array_user[0]);
                $sql = "UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`-'$money' WHERE`bs_php_user`.`user_uid`='{$this->user_array[0]}';";
                $this->db->Bsphp_my_query($sql);
                bs_lib::addappenlog('money_buy_log', "你{$this->user_array[1]}向 $array_user[1] 加 $money 点", $this->user_array[1]);
                alert('成功');
        }
        include bs_lib::load_default_path();

    }
    
    
    

    /**
     * @控制面板-疲劳查询充值卡
     * 
     */
    function call_socard()
    {
        $jieguo ='';
        $baocun = set_post('soso_ok');
        if ($baocun)
        {
        $textarea = set_post('sosotxt');
        
        
        $arr = explode("\n",$textarea);
      
            
            $ka_txt = '';//全部
            $ka_txt1 = '';//未激活
            $ka_txt2 = '';//已激活
            $ka_txt3 = '';//已冻结
            $ka_txt4 = '';//已冻结
           foreach ($arr as $v) {
            $v = trim($v);
            
            
            if($v=='') break;
            
 
        $info = $this->db->my_class_array('bs_php_cardseries', 'car_name', $v,' `car_id`,`car_zhuangtai`,`car_IsLock`,`car_pur_date`,`car_cong_user` ');


        if (!$info) {
            $ka_txt .=$v. " >>>激活码不存在\n\r";
            $ka_txt4 .=$v. " >>>\n\r";

        } else {

            if ($info['car_zhuangtai'] == 1) {
                
                $ka_txt .=$v. " >>>您查询的授权码被冻结\n\r";
                $ka_txt3.=$v."\n\r";;


            } else {


                if ($info['car_IsLock'] == 1) {
                    
                    
                    
                    if($info['car_cong_user']=='cardid'){
                        $bsphp_pattern_login = $this->db->my_class_array('bs_php_pattern_login', 'L_User_uid',$v, ' `L_id`, `L_vip_unix`');
                        $login_name = '卡号';
                    }else{
                        $bsphp_pattern_login = $this->db->my_class_array('bs_php_pattern_login', 'L_ic_pwd',$v, ' `L_id`, `L_vip_unix`');
                        $login_name = $info['car_cong_user'];
                        
                    }
                    
                    
                    


                    
                    $ka_txt .=$v. " >>>授权码已激活 激活时间:{$info['car_pur_date']} 到期时间:".date('Y-m-d H:i:s', $bsphp_pattern_login['L_vip_unix'])." 充值账号:$login_name \n\r";
                    $ka_txt2.=$v."\n\r";;
                        
                        
                } else {
                    
                    $ka_txt .=$v. " >>>您查询的授权码未激活\n\r";
                    
                     $ka_txt1  .=$v."\n\r";
                }
                
            }

        }
            
                    
$jieguo = "
-------------全部激活码-------------
$ka_txt
-------------全部未激活-------------
$ka_txt1
-------------全部已激活-------------
$ka_txt2
-------------全部已冻结-------------
$ka_txt3
-------------全部不存在-------------
$ka_txt4

";
            
           }
            
           
            
            
    
        
        }
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



        
        $ok = set_get('ok');
        if ($ok==1)
        {
            $sdate = set_get('sdate');
            $ids = set_get('ids');

            if ($sdate == '1')
            { //nem=1
                $this->db->my_in_SETdata('bs_php_user', 'user_uid', $ids, 'user_IsLock', 0);
            } else
            {
                $this->db->my_in_SETdata('bs_php_user', 'user_uid', $ids, 'user_IsLock', 1);
            }


        }

        


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

   function call_pushcard()
    {
        //读取列表
        $sql = "SELECT  `app_daihao` ,  `app_name` ,  `app_MoShi` FROM  `bs_php_appinfo` LIMIT 0 , 30";
        $db_array_value_app = $this->db->Bsphp_my_query($sql);


        $act = set_get('act');
        $daihao = set_get('daihao');
        
        
        $type = set_get('type');
        $soso = set_get('soso');

        if($type==1){//备注
            
            $soso_db_table = " `car_agnet_beizhu`='$soso'  AND `car_zhuangtai`='0' ";
        }else if($type==2){//激活状态(0/1)
            $soso_db_table = " `car_IsLock`='$soso' AND `car_zhuangtai`='0' ";
        }else{//时间
           $soso_db_table = " `car_reDATE`='$soso'  AND `car_zhuangtai`='0' ";
        }
        


        /**
         * 查询软件名称
         * 
         */

        $app_name = GetAppInfoNameArray();



        $sql = "SELECT*FROM`bs_php_cardseries`WHERE`car_admin`='{$this->user_array['user_uid']}'AND $soso_db_table ORDER BY`car_id`DESC LIMIT 10000";

        $db_array_value = $this->db->Bsphp_my_query($sql);

        //echo $sql;
        $push_list = null;
        
        
        #导出充值卡
        if($db_array_value){                 
             while ($array_value = mysqli_fetch_array($db_array_value ))
            {
                $push_list .= $array_value['car_name']."\r";
            }
        }else{
            $push_list = '没有充值卡列表';
        }
        
        
      
        /*载入html模版*/
        $fis = 'table';


        include bs_lib::load_default_path();
    }


}

?>