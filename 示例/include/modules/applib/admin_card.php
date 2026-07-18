<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class admin_card extends admin
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


        $this->card_array_name = bs_lib::load_langs_array('applib','admin_card_icname');
        $this->card_oid_array_name = bs_lib::load_langs_array('applib','admin_card_icset');


    }


    /**
     * @控制面板-卡串列表
     * 
     */
    function call_table()
    {
        $carsys_arr=bs_lib::load_langs_array('applib','admin_card');
        
       

        $daihao = set_get('daihao');
        $app_array = get_appdaihao_info_array($daihao);
        if (!$app_array)
            alert('软件不存在!');

        $app_name = $app_array['app_name'];
        $app_daihao = $app_array['app_daihao'];



        //获取软件模式
        $moshi_array = GetAppMoShiArray($app_array['app_MoShi']);

        /**
         * 下拉框批量操作
         */
        $Submit_class = set_post('baocun');
        $all = set_post('all');
        $select_class = set_post('select_class');

        if ($Submit_class)
        {
            if ($select_class == 1)
            { //删除
                if ($this->db->my_in_delete('bs_php_cardseries', 'car_id', $all))
                {
                    $this->admin_alert('你选择卡串已经删除!');
                } else
                {
                    $this->admin_alert('删除失败,SQL执行失败,错误数据已保存!!');
                }

            } elseif ($select_class == 2)
            { //标记
                if ($this->db->my_in_SETdata('bs_php_cardseries', 'car_id', $all, 'car_BaoJi', '1'))
                {
                    $this->admin_alert('你选卡串号已经标记!');
                } else
                {
                    $this->admin_alert('标记,SQL执行失败,错误数据已保存!!');
                }


            } elseif ($select_class == 3)
            { //取消标记
                if ($this->db->my_in_SETdata('bs_php_cardseries', 'car_id', $all, 'car_BaoJi', '0'))
                {
                    $this->admin_alert('你选择卡串已经取消标记!');
                } else
                {
                    $this->admin_alert('取消标记,SQL执行失败,错误数据已保存!!');
                }


            } elseif ($select_class == 4)
            { //设已使用
                if ($this->db->my_in_SETdata('bs_php_cardseries', 'car_id', $all, 'car_IsLock', '1'))
                {
                    $this->admin_alert('你选择卡串已设已经使!');
                } else
                {
                    $this->admin_alert('设已使用,SQL执行失败,错误数据已保存!!');
                }


            } elseif ($select_class == 5)
            { //设未使用
                if ($this->db->my_in_SETdata('bs_php_cardseries', 'car_id', $all, 'car_IsLock', '0'))
                {
                    $this->admin_alert('你选择卡串已经设未使用!');
                } else
                {
                    $this->admin_alert('设未使用,SQL执行失败,错误数据已保存!!');
                }

            } elseif ($select_class == 6)
            { //设未使用
                if ($this->db->my_in_SETdata('bs_php_cardseries', 'car_id', $all, 'car_zhuangtai', '1'))
                {
                    $this->admin_alert('你选择卡串已冻结!');
                } else
                {
                    $this->admin_alert('冻结,SQL执行失败,错误数据已保存!!');
                }

            } elseif ($select_class == 7)
            { //设未使用
                if ($this->db->my_in_SETdata('bs_php_cardseries', 'car_id', $all, 'car_zhuangtai', '0'))
                {
                    $this->admin_alert('你选择卡串已解冻!');
                } else
                {
                    $this->admin_alert('解冻,SQL执行失败,错误数据已保存!!');
                }

            } elseif ($select_class == 8)
            {

                location('index.php?m=applib&c=admin_card_weihu&a=card&allid=' . $all);

            } else
            {
                $this->admin_alert('你没有选择操作项目');
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
        $FANYE = (int)set_get('id');


        if ($FANYE > 0)
        {
            $db_ID = $FANYE - 1; //防止注入
        } else
        {
            $db_ID = 0;
            $FANYE = 1;
        }


        $shu = 20; //每页显示
        $db_ID = $db_ID * $shu;


        /**
         * @接收搜搜参数
         * 
         * 这里表显示以搜搜模式来显示
         */
        /*取得搜搜内容*/
        $soso = set_get('soso');
        $soso_id = (int)set_get('soso_id');
        $DESC_id = (int)set_get('DESC');
        $soso_id > 0 ? $soso_id_show = $soso_id - 1 : $soso_id_show = 0;
        $DESC_id == 1 ? $DESC = 'ASC' : $DESC = 'DESC';


        /**
         * @定义搜搜字段
         * 
         * $soso_db_table = 要搜搜的字段
         */
        $soso_id_array = array(
            '',
            '',
            ''); //搜搜内容选择数组
        if ($soso_id == 2)
        { //制作时间
            $soso_db_table = 'car_reDATE';
            $soso_id_array[2] = 'selected';

        } elseif ($soso_id == 3)
        { //充值用户
            $soso_db_table = 'car_cong_user';
            $soso_id_array[3] = 'selected';
        } elseif ($soso_id == 4)
        { //制卡人
            $soso_db_table = 'car_admin';
            $soso_id_array[4] = 'selected';
        } elseif ($soso_id == 5)
        { //状态停封
            $soso_db_table = 'car_zhuangtai';
            $soso_id_array[5] = 'selected';
            $soso = 1;
        } elseif ($soso_id == 6)
        { //状态正常
            $soso_db_table = 'car_zhuangtai';
            $soso_id_array[6] = 'selected';
            $soso = 0;
        } elseif ($soso_id == 7)
        { //使用=未使用
            $soso_db_table = 'car_IsLock';
            $soso_id_array[7] = 'selected';
            $soso = 0;
        } elseif ($soso_id == 8)
        { //试用-已使用
            $soso_db_table = 'car_IsLock';
            $soso_id_array[8] = 'selected';
            $soso = 1;
        } elseif ($soso_id == 9)
        { //充值天数
            $soso_db_table = 'car_TianShu';
            $soso_id_array[8] = 'selected';
        } else
        {
            $soso_db_table = 'car_name';
            $soso_id_array[1] = 'selected';

        }


        /*开始枚举*/
        $sql = "SELECT*FROM`bs_php_cardseries`WHERE`car_DaiHao`='$daihao'AND`$soso_db_table`LIKE CONVERT(_utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  ORDER BY`car_id`$DESC LIMIT $db_ID,$shu";
        $db_array_value = $this->db->Bsphp_my_query($sql);


        $sql_rows = "SELECT*FROM`bs_php_cardseries`WHERE`car_DaiHao`='$daihao'AND`$soso_db_table`LIKE CONVERT(_utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  ORDER BY`car_id`$DESC";
        /**
         * @获取条数
         */
        $tmp = $this->db->Bsphp_my_query($sql_rows);
        $zongshu = $this->db->my_rows($tmp);


       /*翻页URL*/
       $url = "index.php?soso_id={$soso_id}&soso={$soso}&DESC={$DESC_id}&soso_ok=%E6%90%9C%E7%B4%A2&m={$_GET['m']}&c={$_GET['c']}&a={$_GET['a']}&t={$_GET['t']}&daihao={$app_daihao}&id=";




        //-------------------------------------------------------------------------------

        /*******获取用户分组*****/
        $sql = "SELECT*FROM`bs_php_userclass`WHERE`class_daihao`='$daihao'";
        $dbs_array_value = $this->db->Bsphp_my_query($sql);


        $class_array[0] = '未分组';

        if ($dbs_array_value)
        {
            while ($array_value = mysqli_fetch_array($dbs_array_value))
            {
                $class_array[$array_value[0]] = $array_value[1];
            }

        }
        //-------------------------------------------------------------------------------


        include bs_lib::load_default_path('admin_card/'.$moshi_array['templates'].'/table');
    }


    /**
     * @控制面板-制作充值卡
     * 
     */
    function call_make()
    {
        $daihao = set_get('daihao');
        $app_array = get_appdaihao_info_array($daihao);
        if (!$app_array)
            alert('软件不存在!');

        $app_name = $app_array['app_name'];
        $app_daihao = $app_array['app_daihao'];

        //获取软件模式
        $moshi_array = GetAppMoShiArray($app_array['app_MoShi']);
        
        
        /*******读取类目信息*****/
        $sql = "SELECT*FROM`bs_php_kalei`WHERE`lei_daihao`='$daihao'";
        $db_array_value = $this->db->Bsphp_my_query($sql);
        
        
        $leixing_arr=bs_lib::load_langs_array('applib','admin_card');


        /**********制作充值卡*************/
        $Submit_zhi = set_post('Submit_zhi'); //按钮
        $select = set_post('select'); //栏目
        $shulian = set_post('shulian'); //数量
        $car_admin_beizhu = set_post('car_admin_beizhu'); //数量
        $agnet_id = set_post('agnet_id');
        
        if($agnet_id==''){
            $agnet_id=$this->get_admin_name();
        }else{
            
            
            if($agnet_id==0){
               alert('代理UID错误'); 
            }
            
            
        }

        /**
         * 执行制作函数 提交按钮
         * 
         * 
         */
        if ($Submit_zhi)
        {
            if ($select == 0)
                alert('请选择你制作类型');
            if ($shulian <= 0)
                alert('请输入制作数量,建议1-500张一次');


            //引入文件
            bs_lib::load_modules_common('applib', 'makecard');

            $admin_uid = get_session_value('ADMIN_UID');
            $admin_global_array = $this->db->Bsphp_my_array("SELECT * FROM`bs_php_admin`WHERE`Admin_ID`='$admin_uid'");


            $tmp = ZhiZuoC($shulian, $select,$agnet_id ,'','',$car_admin_beizhu);
            if ($tmp)
            {
                location("index.php?soso_id=2&soso=$tmp&css=%E5%8D%A1%E5%8F%B7%3A%7BKAID%7D+%E5%AF%86%E7%A0%81%3A%7BKAPWD%7D+%E7%8A%B6%E6%80%81%3A%7BAPPSDATES%7D+%E6%97%B6%E9%97%B4%3A%7BAPPTIME%7D+%E8%BD%AF%E4%BB%B6%3A%7BAPPNAME%7D&DESC=0&soso_ok=%E6%90%9C%E7%B4%A2&act=table&daihao=$daihao&m=applib&c=admin_card&a=makeshow");
                
                
                
            } else
            {
                alert('充值卡制作失败!');
            }

        }


        include bs_lib::load_default_path('admin_card/'.'/make');//.$moshi_array['templates']
    }


    /**
     * @控制面板-充值卡类型
     * 
     */
    function call_class()
    {
        
        $carsys_arr=bs_lib::load_langs_array('applib','admin_card');
        
        $daihao = set_get('daihao');
        $app_array = get_appdaihao_info_array($daihao);
        if (!$app_array)
            alert('软件不存在!');

        $app_name = $app_array['app_name'];
        $app_daihao = $app_array['app_daihao'];


        //获取软件模式
        $moshi_array = GetAppMoShiArray($app_array['app_MoShi']);

        /*******查询卡类列表*****/
        $sql = "SELECT*FROM`bs_php_kalei`WHERE`lei_daihao`='$daihao'";
        $db_array_value = $this->db->Bsphp_my_query($sql);


        /**
         * 删除
         */
        $delete = set_get('delete');
        if ($delete)
        {
            $tmp = $this->db->my_in_delete('bs_php_kalei', 'lei_id', $delete);
            if ($tmp)
            {
                alert('类型已经删除!');
            } else
            {
                alert('删除失败,错误已经记录');
            }
        }


        //-------------------------------------------------------------------------------

        /*******获取用户分组*****/
        $sql = "SELECT*FROM`bs_php_userclass`WHERE`class_daihao`='$daihao'";
        $dbs_array_value = $this->db->Bsphp_my_query($sql);


        $class_array[0] = '未分组';
        if ($dbs_array_value)
        {
            while ($array_value = mysqli_fetch_array($dbs_array_value))
            {
                $class_array[$array_value[0]] = $array_value[1];
            }
        }

        //-------------------------------------------------------------------------------
//echo bs_lib::load_default_path('admin_card/'.$moshi_array['templates'].'/class');
        include bs_lib::load_default_path('admin_card/'.$moshi_array['templates'].'/class');
        //include bs_lib::load_default_path();
    }


    /**
     * @控制面板-充值卡类型
     * 
     */
    function call_modify()
    {
        $daihao = set_get('daihao');
        $app_array = get_appdaihao_info_array($daihao);
        if (!$app_array)
            alert('软件不存在!');

        $app_name = $app_array['app_name'];
        $app_daihao = $app_array['app_daihao'];

        //获取软件模式
        $moshi_array = GetAppMoShiArray($app_array['app_MoShi']);


        $id = set_get('id');

        $ka_id = set_post('ka_id');
        $leiadd = set_post('leiadd');
        $ka_name = set_post('ka_name');
        $ka_date = set_post('ka_date');
        $ka_jiage = set_post('ka_jiage');
        $ka_dai = set_post('ka_dai');
        $leiadd = set_post('leiadd');
        $ka_ktzf = set_post('ka_ktzf');
        $lei_type = set_post('lei_type');
        $lei_money = set_post('lei_money');

        $car_class = set_post('car_class');
        $car_weight = (int)set_post('car_weight');


        $lei_cardint = (int)set_post('lei_cardint');
        if ($lei_cardint == 0)
            $lei_cardint = 25;
        $lei_cardset = (int)set_post('lei_cardset');
        $lei_pwdint = (int)set_post('lei_pwdint');
        $lei_pwdset = (int)set_post('lei_pwdset');
        $lei_for = (int)set_post('lei_for');
        if ($lei_for == 0)
            $lei_for = 1;
        $lei_for_id = (int)set_post('lei_for_id');

        $lei_links_open = (int)set_post('lei_links_open');
        $lei_links = (int)set_post('lei_links');
        
        if ($leiadd)
        {
            $sql = "UPDATE`bs_php_kalei`SET`lei_links_open`='$lei_links_open',`lei_links`='$lei_links',`lei_money`='$lei_money',`lei_cardint`='$lei_cardint',`lei_cardset`='$lei_cardset',`lei_pwdint`='$lei_pwdint',`lei_pwdset`='$lei_pwdset',`lei_for`='$lei_for',`lei_for_id`='$lei_for_id',`lei_class`='$car_class',`lei_weight`='$car_weight',`lei_type`='$lei_type',`lei_ktzf`='$ka_ktzf',`lei_name`='$ka_name',`lei_date`='$ka_date',`lei_jiage`='$ka_jiage',`lei_daili`='$ka_dai'WHERE`bs_php_kalei`.`lei_id`='$ka_id';";
      
            $tmp = $this->db->Bsphp_my_query($sql);
            if ($tmp)
            {
                alert('属性已经修改成功。!');
            } else
            {
                alert('属性修改失败,错误已经记录');
            }
        }


        /************读取修改属性记录***********/
        $array = $this->db->my_class_array('bs_php_kalei', 'lei_id', $id, ' * ');
        if($array['lei_weight']==1){ $array['lei_weight'] = 'checked';}
     


        /*******查询用户列表*****/
        $sql = "SELECT*FROM`bs_php_userclass`WHERE`class_daihao`='$daihao'";
        $db_array_value = $this->db->Bsphp_my_query($sql);




        include bs_lib::load_default_path('admin_card/'.$moshi_array['templates'].'/modify');
    }

    /**
     * @控制面板-充值卡类型
     * 
     */
    function call_carmodify()
    {
        $daihao = set_get('daihao');
        $app_array = get_appdaihao_info_array($daihao);
        if (!$app_array)
            alert('软件不存在!');

        $app_name = $app_array['app_name'];
        $app_daihao = $app_array['app_daihao'];

        //获取软件模式
        $moshi_array = GetAppMoShiArray($app_array['app_MoShi']);


        $id = set_get('id');
        
        
        
        
        $car_admin_beizhu = set_post('car_admin_beizhu');
        $car_agnet_beizhu = set_post('car_agnet_beizhu');
        
        
        $carid = set_post('carid');


        if ($carid)
        {
            $sql = "UPDATE `bsphp_cardseries` SET`car_admin_beizhu`='$car_admin_beizhu',`car_agnet_beizhu`='$car_agnet_beizhu' WHERE `car_id`='$carid';";
      
            $tmp = $this->db->Bsphp_my_query($sql);
            if ($tmp)
            {
                alert('属性已经修改成功。!');
            } else
            {
                alert('属性修改失败,错误已经记录');
            }
        }
        
        
/*

        ************读取修改属性记录***********
        $array = $this->db->my_class_array('bs_php_kalei', 'lei_id', $id, ' * ');
        if($array['lei_weight']==1){ $array['lei_weight'] = 'checked';}
     
*/

        /*******查询用户列表*****/
        #$sql = "SELECT*FROM`bs_php_cardseries`WHERE`car_id`='$id'";
        #$db_array = $this->db->Bsphp_my_array($sql);

        $value = $this->db->my_class_array('bs_php_cardseries', 'car_id', $id, ' * ');
        if(!$value){
            alert('卡不存在!'.$id);
        }
        
			$value['car_IsLock']==1?$test='<span  style="color: #FF9900">已使用</span>':$test='未使用';
			$value['car_zhuangtai']==1?$zhuangtai='<span  style="color: #FF9900">冻结</span>':$zhuangtai='正常';

			if($value[16]=='0000-00-00 00:00:00')$value[16]='';
		
			
			(int)$value[15]!=0?$value[15]='(uid:'.$value[15].')':$value[15]='';
			
			

			$value['car_BaoJi']==1?$url_img='nem':$url_img='nop';
			
			$value[5]=show_Time_Day($value[5]);
			
            if($value['car_pwd']=='')$value['car_pwd']='无';
			
			$value['car_weight']==1?$value['car_weight']='是':$value['car_weight']='否';
			
						
			if($value['car_links_open']==0)$value['car_links_open']='默认';
            if($value['car_links']==0)$value['car_links']='默认';


        include bs_lib::load_default_path('admin_card/carmodify');
    }



    /**
     * @控制面板-充值卡类型
     * 
     */
    function call_add()
    {

        $daihao = set_get('daihao');
        $app_array = get_appdaihao_info_array($daihao);
        if (!$app_array)
            alert('软件不存在!');

        $app_name = $app_array['app_name'];
        $app_daihao = $app_array['app_daihao'];
        
       
        //获取软件模式
        $moshi_array = GetAppMoShiArray($app_array['app_MoShi']);

        $leiadd = set_post('leiadd');
        $ka_name = set_post('ka_name');
        $ka_date = set_post('ka_date');
        $ka_jiage = set_post('ka_jiage');
        $ka_dai = set_post('ka_dai');
        $ka_ktzf = set_post('ka_ktzf');
        $lei_type = set_post('lei_type');
        $car_class = set_post('car_class');
        $car_weight = (int)set_post('car_weight');
        $lei_money = set_post('lei_money');
        
        if($lei_money=='')$lei_money=0;


        $lei_cardint = (int)set_post('lei_cardint');
        if ($lei_cardint == 0)
            $lei_cardint = 25;
        $lei_cardset = (int)set_post('lei_cardset');
        $lei_pwdint = (int)set_post('lei_pwdint');
        $lei_pwdset = (int)set_post('lei_pwdset');
        $lei_for = (int)set_post('lei_for');
        if ($lei_for == 0)
            $lei_for = 1;
        $lei_for_id = (int)set_post('lei_for_id');
        
        
        $lei_links_open = (int)set_post('lei_links_open');
        $lei_links = (int)set_post('lei_links');


        if ($leiadd)
        {
            if ($ka_name == '' or $ka_date == 0)
            {
                alert2('请输入类型名称或者延期时间');
            } else
            {
                $sql = "INSERT INTO`bs_php_kalei` (`lei_id`, `lei_name`, `lei_date`, `lei_jiage`, `lei_daili`, `lei_daihao`, `lei_ktzf`, `lei_type`, `lei_weight`, `lei_class`, `lei_cardint`, `lei_cardset`, `lei_pwdint`, `lei_pwdset`, `lei_for`, `lei_for_id`, `lei_for_oid`, `lei_money`,`lei_links_open`,`lei_links`) 
                VALUES(NULL ,'$ka_name','$ka_date','$ka_jiage','$ka_dai','$daihao','$ka_ktzf','$lei_type','$car_weight','$car_class','$lei_cardint','$lei_cardset','$lei_pwdint','$lei_pwdset','$lei_for','$lei_for_id','','$lei_money','$lei_links_open','$lei_links');";
                $tmp = $this->db->Bsphp_my_query($sql);

                if ($tmp)
                {
                    alert('新类型添加成功!');
                } else
                {
                    alert('添加失败,错误已经保存!');
                }
            }


        }


        /*******查询卡类列表*****/
        $sql = "SELECT*FROM`bs_php_userclass`WHERE`class_daihao`='$daihao'";
        $db_array_value = $this->db->Bsphp_my_query($sql);









        include bs_lib::load_default_path('admin_card/'.$moshi_array['templates'].'/add');


    }


    /**
     * @控制面板-充值卡类型
     * 
     */
    function call_makeshow()
    {
        
        

        $daihao = set_get('daihao');
        $app_array = get_appdaihao_info_array($daihao);
        if (!$app_array)
            alert('软件不存在!');

        $app_name = $app_array['app_name'];
        $app_daihao = $app_array['app_daihao'];


        $data = set_get('date');
        

        $GLOBALS_LANGS = bs_lib::load_langs_array('applib','admin_card');

        /**
         * @接收搜搜参数
         * 
         * 这里表显示以搜搜模式来显示
         */
        /*取得搜搜内容*/
        $soso = set_get('soso');
        $css = set_get('css');
        $soso_id = (int)set_get('soso_id');
        $DESC_id = (int)set_get('DESC');
        $soso_id > 0 ? $soso_id_show = $soso_id - 1 : $soso_id_show = 0;
        $DESC_id == 1 ? $DESC = 'ASC' : $DESC = 'DESC';


        /**
         * @定义搜搜字段
         * 
         * $soso_db_table = 要搜搜的字段
         */
        $soso_id_array = array(
            '',
            '',
            ''); //搜搜内容选择数组
        if ($soso_id == 2)
        { //制作时间
            $soso_db_table = 'car_reDATE';
            $soso_id_array[2] = 'selected';

        } elseif ($soso_id == 3)
        { //充值用户
            $soso_db_table = 'car_cong_user';
            $soso_id_array[3] = 'selected';
        } elseif ($soso_id == 4)
        { //制卡人
            $soso_db_table = 'car_admin';
            $soso_id_array[4] = 'selected';
        } elseif ($soso_id == 5)
        { //状态停封
            $soso_db_table = 'car_zhuangtai';
            $soso_id_array[5] = 'selected';
            $soso = 1;
        } elseif ($soso_id == 6)
        { //状态正常
            $soso_db_table = 'car_zhuangtai';
            $soso_id_array[6] = 'selected';
            $soso = 0;
        } elseif ($soso_id == 7)
        { //使用=未使用
            $soso_db_table = 'car_IsLock';
            $soso_id_array[7] = 'selected';
            $soso = 0;
        } elseif ($soso_id == 8)
        { //试用-已使用
            $soso_db_table = 'car_IsLock';
            $soso_id_array[8] = 'selected';
            $soso = 1;
        } elseif ($soso_id == 9)
        { //充值天数
            $soso_db_table = 'car_TianShu';
            $soso_id_array[8] = 'selected';
        } else
        {
            $soso_db_table = 'car_name';
            $soso_id_array[1] = 'selected';

        }


        /*开始枚举*/
        $sql = "SELECT*FROM`bs_php_cardseries`WHERE`car_DaiHao`='$daihao'AND`$soso_db_table`LIKE CONVERT(_utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  ORDER BY`car_id`$DESC LIMIT 20000";
        $db_array_value = $this->db->Bsphp_my_query($sql);

            $tmp='';
            while ($value= mysqli_fetch_array($db_array_value ))
            {
                
                $value['car_IsLock']==1?$test='已使用':$test='未使用';
                $s=$css;
                $s=str_replace("{KAID}",$value['car_name'],$s);
                $s=str_replace("{KAPWD}",$value['car_pwd'],$s);
                $s=str_replace("{APPSDATES}",$value['car_IsLock'],$s);
                $s=str_replace("{APPTIME}",$value['car_TianShu'].$GLOBALS_LANGS[$value['car_type']],$s);
                $s=str_replace("{APPNAME}",$app_name,$s);
                
                
                
                
                
                $tmp.=$s."\r\n";
                
            }


        /**
         * 
         
        //引入文件
        bs_lib::load_modules_common('applib', 'makecard');
        $tmp = kaliebiao($data, $app_name);
*/
        include bs_lib::load_default_path();


    }

}

?>