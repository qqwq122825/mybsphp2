<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
if(BSPHP_SET !='ADMIN')die('Not,This File Not Can in Ie Modules');


bs_lib::load_modules_class('admin', 'admin');

/**
 * @系统配置
 *  
 */
class log extends admin
{

    private $rc, $db, $purconfig ,$GLOBALS_LANGS;

    function __construct()
    {


        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->purconfig = bs_lib::load_libbsphp_class('purconfig', 'purconfig');
        

        //开启session
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->session->session_open();

        $this->GLOBALS_LANGS = bs_lib::load_langs_array();
        //后台权限验证
        $this->COOKIES_KEY('B');
    }


    /**
     * @系统配置
     * 
     */
    function call_table()
    {



    $T = set_get('t');

    
    /**
     * 下拉框批量操作
     */
    $Submit_class =set_post('Submit_class');
    $all=set_post('all');
    $select_class=set_post('select_class');
    if($select_class){
        if($all=='') $this->admin_alert('没有选择记录条目！');
        
    if($select_class==1){  //删除50天之前记录date('Y-m-d H:i:s',$date);
        $date=HOST_UNIX - 181200;
        $sql="DELETE FROM`bs_php_log`WHERE `date`<'$date' AND `leixing`=\"{$T}\"";
        $tmp=$this->db->Bsphp_my_query($sql);
        if($tmp){
           $this->admin_alert('清理50天前信息执行成功');
        }else{
            $this->admin_alert('清理50天前信息执行失败');
        }



        
    }elseif($select_class==2){ //选择性删除
       if($this->db->my_in_delete('bs_php_log','id',$all)){
      
         $this->admin_alert('选择的记录已经删除!');
         
         
         
       }else{
         
         $this->admin_alert('删除失败,SQL执行失败,错误数据已保存!');
       }
        
        
    }elseif($select_class==3){  //7
    
        $date=HOST_UNIX - 604800;
        $sql="DELETE FROM`bs_php_log`WHERE `date`<'$date' AND `leixing`=\"{$T}\"";
        $tmp=$this->db->Bsphp_my_query($sql);
        if($tmp){
           $this->admin_alert('清理7天前信息执行成功');
        }else{
            $this->admin_alert('清理7天前信息执行失败');
        }
    
 
    }elseif($select_class==4){  //all list
        $sql="DELETE FROM`bs_php_log`WHERE `leixing`=\"{$T}\"";
        $tmp=$this->db->Bsphp_my_query($sql);
        if($tmp){
           $this->admin_alert('清理全部信息执行成功');
        }else{
            $this->admin_alert('清理全部信息执行失败');
        }
    
 
    }else{
        $this->admin_alert('你没有选择操作项目');
  
    }//按钮点击接受
    

    
    }
    
    
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
        <option value="1">用户/管理</option>
        <option value="2">IP段</option>
        <option value="3" {$soso_id_A3}>信息包含</option>
        */
        if($soso_id==2){
           $soso_db_table = 'ip'; 
           $soso_id_A2='selected';
        }elseif($soso_id==3){
           $soso_db_table = 'test'; 
           $soso_id_A3='selected';
        }else{
           $soso_db_table = 'user'; 
           $soso_id_A1='selected';
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


       
       
       
       /**
        * @获取条数
        */
       $sql = "SELECT*FROM`bs_php_log`WHERE`leixing`='$T' AND`$soso_db_table`LIKE CONVERT( _utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  ORDER BY`id`DESC";
       $tmp = $this->db->Bsphp_my_query($sql);
       $zongshu = $this->db->my_rows($tmp);



       /**
        * @计算分页
        */
       //$yueshuls=取整数就小于$zongyue不取整数的了*/
       $zongyue = $zongshu / $shu;
       $yueshuls = intval(strval($zongshu / $shu));
       if ($zongyue > $yueshuls) $zongyue = ++$yueshuls;




       /**
        * @定义页
        * 
        * 不为空状态前后和数据库的开始ID
        */
       if (!empty($FANYE)) {
            $JIAN = $FANYE - 1; //上一页
            $JIA = $FANYE + 1; //下一页
            if ($JIAN <= 0) $JIAN = 1;
            if ($FANYE >= $zongyue) $JIA = $zongyue;
            $lingshi = $FANYE - 1;
            $db_ID = $lingshi * $shu;

       } else {




            /**
             * @ID为空时候定义
             */

            $db_ID = 0; //数据库ID
            $JIAN = 1; //上一页
            $JIA = 2; //下一页
            $FANYE = 1; //当前页
            if ($zongshu <= $shu) $JIA = 1;
       }





       /**
        * @定义前后顶状态
        */
       $ata = null;
       $atb = null;
       if ($FANYE >= $zongyue) $ata = " onclick=\"return alert('前面没有了哦!')\" ";
       if ($FANYE <= 1) $atb = " onclick=\"return alert('前面没有了哦!')\" ";








       /*开始枚举*/
       $sql = "SELECT*FROM`bs_php_log`WHERE`leixing`='$T' AND`$soso_db_table`LIKE CONVERT( _utf8'%$soso%'USING utf8)COLLATE utf8_general_ci  ORDER BY`id`$DESC LIMIT $db_ID,$shu";
	
	   

       $db_array_value = $this->db->Bsphp_my_query($sql);



       /*翻页URL*/
       $url = "index.php?soso_id={$soso_id}&soso={$soso}&DESC={$DESC_id}&soso_ok=%E6%90%9C%E7%B4%A2&m={$_GET['m']}&c={$_GET['c']}&a={$_GET['a']}&t={$_GET['t']}&id=";
       /*载入html模版*/
    


        
        include bs_lib::load_default_path('log/'.$T);
    }


    

}

?>