<?php
defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if (BSPHP_SET != 'ADMIN')
    die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');


/**
 * @用户中心首页 registration
 * 
 */
class admin_news extends admin
{

    private $rc, $db, $GLOBALS_LANGS, $user_str_log, $user, $seesion_uid;

    function __construct()
    {

        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');

        $this->GLOBALS_LANGS = bs_lib::load_langs_array();


        $this->ip = bs_lib::load_libbsphp_class('ip', 'ip');


        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();
        // echo '控制器  继承初始化1<BR/>';


        //后台权限验证
        $this->COOKIES_KEY('B');


        $admin_uid = get_session_value('ADMIN_UID');
        $this->admin_global_array = $this->db->Bsphp_my_array("SELECT * FROM`bs_php_admin`WHERE`Admin_ID`='$admin_uid'");
    }


    function call_news()
    {
        
   /**
     * 下拉框批量操作
     */
    $Submit_class =set_post('baocun');
    $all=set_post('all');
    $select_class=set_post('select_class');
    if($Submit_class){
    if($select_class==1){
       if($this->db->my_in_delete('plug_bsphp_news','news_id',$all)){
         alert('选择已经删除!');
       }else{
        alert('删除失败,SQL执行失败,错误数据已保存!!');
       }
        
    }else{
        alert('你没有选择操作项目');
    }
    
    
    
   }//按钮接受 
    
    
    
    
    
    
    
    
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
        * $soso_db_table = 要搜搜的字段
        */
       $soso_db_table = 'news_table';





       /*开始枚举*/
       
       $sql="SELECT*FROM`plug_bsphp_news`WHERE`$soso_db_table`LIKE '%$soso%'ORDER BY `news_id` $DESC LIMIT $db_ID,$shu";
       $db_array_value = $this->db->Bsphp_my_query($sql);


       $sql_rows="SELECT*FROM`plug_bsphp_news`WHERE`$soso_db_table`LIKE '%$soso%'ORDER BY `news_id` $DESC LIMIT $db_ID,$shu";
       /**
        * @获取条数
        */
        $tmp= $this->db->Bsphp_my_query($sql_rows);
        $zongshu =  $this->db->my_rows($tmp);


       /*翻页URL*/
       $url = "index.php?m=index&c=admin_news&a=news&soso_id=$soso_id&soso=$soso&soso_ok=%CB%D1%CB%D1&act=table&id=";
       /*载入html模版*/
       $fis = 'table';
       
       
       
        
        
          include bs_lib::load_default_path();
        
      }





    function call_add()
    {


     /*载入html模版*/
       $fis = 'add';
       
       $submit=set_post('Submit');
       if($submit){
        
        
        @eval(base64_decode('ICAgICAgICRhZG1pbl9pbmZvID0gQWRtaW5fVmVyaWZpY2F0aW9ucygnUGF5bWVudCcpOyAgICAgICBpZiAoJGFkbWluX2luZm8gIT0gMSkNCiAgICAgICBic19saWI6OkFsZXJ0cygn57O757uf5pyq5o6I5p2DJywgJ+WwiuaVrOeUqOaItyzkvaDlvZPliY3ns7vnu5/liY3lj7Dlip/og73mnKrnu4/mjojmnYPml6Dms5Xkvb/nlKgs6K+35YWI6LSt5Lmw5o6I5p2D5Zyo5L2/55So44CC5Zyo57q/6LSt5Lmw5Zyw5Z2AOmh0dHA6Ly9NYW5hZ2VtZW50LkJzcGhwLmNvbScpOw0KICAgICAgICAg'));
        
        
       $news_class=set_post('news_class');
       $news_test=$_POST['news_test'];
       $news_table=set_post('news_table');
       $news_unix=HOST_UNIX;
     	
         if (@get_magic_quotes_gpc()) {
			$htmlData = stripslashes($news_test);
     	} else {
		    $htmlData = $news_test;
		}
        
       
       $news_test=base64_encode($htmlData);
       $sql="INSERT INTO`plug_bsphp_news` (`news_id`, `news_table`, `news_test`, `news_class`, `news_unix`)  VALUES (NULL,'$news_table','$news_test','$news_class','$news_unix');";
       
       if ($this->db->Bsphp_my_query($sql)) alert( '新闻文章添加成功!');
       else  alert( '添加失败,SQL语句执行失败,已记录错误SQL!');
       
       }
       
       
       
    /**
     * 查询类型SQL-选择列表框
     */
    $sql="SELECT*FROM`plug_bsphp_news_class` ORDER BY `class_id` ASC ";
    $db_array_value=$this->db->Bsphp_my_query($sql);


        include bs_lib::load_default_path();
    }
    
    
    
    function call_usb()
    {
        
      /*载入html模版*/
       $fis = 'usb';
       $id=set_get('id');
       $submit=set_post('Submit');
       if($submit){
       $news_class=set_post('news_class');
       $news_test=$_POST['news_test'];
       
       
       $news_table=set_post('news_table');
       
       
       $news_unix=HOST_UNIX;
       
       /**
     	if (@get_magic_quotes_gpc()) {
			$htmlData = stripslashes($news_test);
            
     	} else {
		$htmlData = $news_test;
        
		}
        
        */
        

         if (@get_magic_quotes_gpc()) {
            
			$htmlData = stripslashes($news_test);
     	} else {
     	  
		    $htmlData = $news_test;
		}
        
      //$news_test = stripslashes($news_test);
        
        
       $news_test=base64_encode ($htmlData);
    
        
       //htmlspecialchars
       //执行SQLhtmlspecialchars 
       $sql="UPDATE`plug_bsphp_news`SET`news_table`='$news_table',`news_test`=\"$news_test\",`news_class`='$news_class' WHERE`plug_bsphp_news`.`news_id`='$id';";
      
 
      
       if ($this->db->Bsphp_my_query($sql)) alert( '新闻文章修改成功!');
       else  alert( '修改失败,SQL语句执行失败,已记录错误SQL!');
       
       }
       
       
    /**
     * 查询类型SQL-选择列表框
     */
    $sql="SELECT*FROM`plug_bsphp_news_class` ORDER BY `class_id` ASC ";
    $db_array_value=$this->db->Bsphp_my_query($sql);
    
    $array=$this->db->my_class_array('plug_bsphp_news','news_id',$id,' * ');
    $array[2]=base64_decode($array[2]);
    $array[2]=stripslashes ($array[2]);
        include bs_lib::load_default_path();  

}
    
    
    

}
?>