<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');


bs_lib::load_modules_class('admin', 'admin');

/**
 * @管理员 mian 控制面板
 * 后台功能指引面板
 */
class admin_links extends admin
{

    private $rc, $db , $GLOBALS_LANGS;

    function __construct()
    {
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        
        
        $this->construct();
        //$this->GLOBALS_LANGS = bs_lib::load_langs();


        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();

        
        
        
        //后台权限验证
        $this->COOKIES_KEY('C');
    }


    /**
     * @控制面板
     * 
     */
    function call_table()
    {



	
	
	
	$outid=set_get('out');
	if($outid!=NULL){
	
	  $outID=$this->session->links_delete_mysql_id($outid);
	  IF($outID==true){
	     $this->admin_alert('已经踢出!','index.php?m=links&c=admin_links&a=table');
	  }else{
	  
	     $this->admin_alert('T出失败!','index.php?m=links&c=admin_links&a=table');
	  }
	
	}
	
	
    
    
    /**
     * 下拉框批量操作
     */
    $Submit_class =set_post('Submit_class');
    $all=set_post('all');
    $select_class=set_post('select_class');
    if($select_class){
        if($all=='')  $this->admin_alert('没有选择记录条目！');
    if($select_class==1){  //T出
       if($this->db->my_in_SETdata('bs_php_links_session','links_id',$all,'links_set','-1')){
         $this->admin_alert('选择用户已经踢出!');
       }else{
         $this->admin_alert('执行失败!');
       }
        
    }else{
         $this->admin_alert('你没有选择操作项目');
    }
    
    }//按钮点击接受
    
    

    
    //获取软件列表名称
    $app_all_arrayname=GetAppInfoNameArray();
    
  
       /*翻页参数初始化*/





       /*系统翻页*/
       $FANYE = (int)set_get('id');
       
       
       if($FANYE>0){
        $db_ID=$FANYE-1;//防止注入
       }else{
        $db_ID=0;
        $FANYE=1;
       }
       
       
       $shu = 20; //每页显示 
       $db_ID=$db_ID*$shu;
       
       

       





 


       /**
        * @接收搜搜参数
        * 
        * 这里表显示以搜搜模式来显示
        */
       /*取得搜搜内容*/
       $soso = set_get('soso');
       $soso_id = (int)set_get('soso_id');
       $DESC_id = (int)set_get('DESC');
       $soso_id>0?$soso_id_show=$soso_id-1:$soso_id_show=0;
       $DESC_id==1?$DESC='ASC':$DESC='DESC';


       /**
        * @定义搜搜字段
        * 
        <option value="1" {$soso_id_arr[1]}>登陆账号</option>
        <option value="2" {$soso_id_arr[2]} >用户UID</option>
        <option value="3" {$soso_id_arr[3]}>代理商列表</option>
        <option value="4" {$soso_id_arr[4]}>用户金额大于</option>
        <option value="5" {$soso_id_arr[5]}>登录次数大于</option>
        <option value="6" {$soso_id_arr[6]}>邀请人</option>
        <option value="7" {$soso_id_arr[7]}>用户状态=&gt;冻结</option>
        <option value="8" {$soso_id_arr[8]}>用户状态=&gt;正常</option>
		<option value="9" {$soso_id_arr[8]}>用户QQ</option>
		<option value="10" {$soso_id_arr[8]}>用户邮箱</option>
        * $soso_db_table = 要搜搜的字段
        */
     
        

        
        if($soso_id==2){
           $soso_db_table = 'links_key'; 
         
        }elseif($soso_id==3){
           $soso_db_table = 'links_biaoji'; 
           
           
        }elseif($soso_id==4){
           $soso_db_table = 'links_addip'; 
           
  
           
        }elseif($soso_id==5){
           $soso_db_table = 'links_daihao'; 
           
  
           
        }else{
           $soso_db_table = 'links_user_name'; 
           
           
        }
       




        

          $time = HOST_UNIX;
          $sql = "SELECT * FROM `bs_php_links_session` WHERE`$soso_db_table`LIKE CONVERT( _utf8'%$soso%'USING utf8)COLLATE utf8_general_ci AND `links_out_time` > '$time' ORDER BY`links_id`$DESC LIMIT $db_ID,$shu";

          $sql_rows = "SELECT*FROM`bs_php_links_session`WHERE`$soso_db_table`LIKE CONVERT( _utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  AND `links_out_time` > '$time'  ORDER BY`links_id`$DESC";
   


       /*开始枚举*/
      $db_array_value = $this->db->Bsphp_my_query($sql);




       /**
        * @获取条数
        */
        $tmp= $this->db->Bsphp_my_query($sql_rows);
        $zongshu = $this->db->my_rows($tmp);



       /*翻页URL*/
       $url = "index.php?soso_id={$soso_id}&soso={$soso}&DESC={$DESC_id}&soso_ok=%E6%90%9C%E7%B4%A2&m={$_GET['m']}&c={$_GET['c']}&a={$_GET['a']}&t={$_GET['t']}&id=";
       
       
       





        include bs_lib::load_default_path();
       
    }


}

?>