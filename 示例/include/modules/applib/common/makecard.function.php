<?php


  /**
   * 充值卡制作文档
   */


  
  

    


  /**
   * *******************************
   * 获取随机数
   * 
   * 参数1=随机数长度
   * 参数2= 返回卡串组合
   */
  function gte_test_arr($int,$leixing) {
    
      /**********取随机数字数组**************
	            <option value="0"     > 默认数字+大写+小写组合</option>
          <option value="1"     > 数字加大写字母</option>
		  <option value="2"     > 数字加小写字母</option>
		  <option value="3"     > 大小写字母</option>
		  <option value="4"     > 全部大写字母</option>
		  <option value="5"     > 全部小写字母</option>
		  <option value="6"     > 全部数字</option>
		     */
	  if($leixing==1){
	    $check_list = array('Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'I', 'P', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'Z', 'X', 'C', 'V', 'B', 'N', 'M', 1, 2, 3, 4, 5, 6, 7,8, 9, 0);
	  
	  
	  }ELSEIF($leixing==2){
	    $check_list = array('q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', 'l', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'z', 'z', 'x', 'c', 'v', 'b', 'n', 'm', 1, 2, 3, 4, 5, 6, 7,8, 9, 0);
	  
	  }ELSEIF($leixing==3){
	    $check_list = array('q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', 'l', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'z', 'z', 'x', 'c', 'v', 'b', 'n', 'm', 'Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'I', 'P', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'Z', 'X', 'C', 'V', 'B', 'N', 'M');
	  
	  }ELSEIF($leixing==4){
	    $check_list = array('Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'I', 'P', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'Z', 'X', 'C', 'V', 'B', 'N', 'M');
	  
	  }ELSEIF($leixing==5){
	    $check_list = array('q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', 'l', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'z', 'z', 'x', 'c', 'v', 'b', 'n', 'm');
	  
	  }ELSEIF($leixing==6){
	    $check_list = array(1, 2, 3, 4, 5, 6, 7,8, 9, 0);
	  
	  }else{
        $check_list = array('q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', 'l', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'z', 'z', 'x', 'c', 'v', 'b', 'n', 'm', 'Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'I', 'P', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'Z', 'X', 'C', 'V', 'B', 'N', 'M', 1, 2, 3, 4, 5, 6, 7,8, 9, 0);
	  }
	  
	  

    
    $test = '';
    shuffle($check_list); //打乱
    $ki = 0;
    while ($ki < $int) {
      $test .= $check_list[mt_rand(0, count($check_list)-1)];
      $ki++;
    }
    return $test;

  }
  
  
  
  
  /**
   * 制作充值卡
   * 
   * 参数1=制作数量
   * 参数2=制作类型数ID  数据库:kalei 
   * 参数3=制作用户UID  或者管理员用户名称
   * 参数4=是否要返回制作返回列表  留空自己获取系统时间
   * 
   * 返回制作时间
   */
  function ZhiZuoC($shu,$lieixng,$uid_user,$date='',$rmb_daili=-10,$car_admin_beizhu='',$car_agnet_beizhu=''){
      
      
      $db=bs_lib::load_libbsphp_class('mysql', 'mysql');

    /********查询卡类属性*** ***/
    $lei_array=$db->my_class_array('bs_php_kalei','lei_id',$lieixng,' * ');
    
    $app_moshi=$db->my_class_array('bs_php_appinfo','app_daihao',$lei_array[5],'`app_MoShi`');

    
    //获取相关属性赋值变量
    $ktzf=$lei_array['lei_ktzf']; 
    $daihao=$lei_array['lei_daihao'];
    
    
    
    
    
    if($date==''){
        $date=HOST_DATE;
    }
    $tianshu=$lei_array[2];
    $rmb=$lei_array[3];
    if($rmb_daili==-10) $rmb_daili=$lei_array[4];
    /////////////////////////////////////////////////////
    $id=1;
    $SQL_TMP='';
    $tset='';
    if((int)$lei_array['lei_for']==0) $lei_array['lei_for']=1;
    if((int)$lei_array['lei_cardint']==0)$lei_array['lei_cardint']=25;
    while ($id <= $shu)
    {//循环开始
    

      $ka=gte_test_arr($lei_array['lei_cardint'],$lei_array['lei_cardset']);//随机生成充值卡号
      $ka=$ktzf.$ka;
      $mi=gte_test_arr($lei_array['lei_pwdint'],$lei_array['lei_pwdset']);//随机生成密码

    $rmb_daili = (int)$rmb_daili;
    if((int)$lei_array['lei_for']<=0) $lei_array['lei_for']=1;
    $SQL_TMP.="(NULL,'$ka','$mi','0','$daihao','$date','$tianshu','$lieixng','$uid_user','0','0','$rmb','$rmb_daili','0','0','0','0000-00-00 00:00:00','','{$lei_array['lei_type']}','{$lei_array['lei_weight']}','{$lei_array['lei_class']}','{$lei_array['lei_for']}','{$lei_array['lei_for_id']}','','','{$lei_array['lei_money']}','{$lei_array['lei_links_open']}','{$lei_array['lei_links']}','$car_admin_beizhu','$car_agnet_beizhu')";
    if($id<$shu){
        $SQL_TMP.=',';
    }
    
	$id++;
   }//循环结束
  
  /**
   * SQL语句完成
   */
  $sql="INSERT INTO`bs_php_cardseries` (`car_id`, `car_name`, `car_pwd`, `car_IsLock`, `car_DaiHao`, `car_reDATE`, `car_TianShu`, `car_Lei`, `car_admin`, `car_BaoJi`, `car_JiFen`, `car_Rmb`, `car_DaoLi_Rmb`, `car_RuKu`, `car_zhuangtai`, `car_chong_uid`, `car_pur_date`, `car_cong_user`, `car_type`, `car_weight`, `car_class`, `car_for`, `lei_for_id`, `car_for_oid`, `car_div`, `car_money`,`car_links_open`,`car_links`,`car_admin_beizhu`,`car_agnet_beizhu`) VALUES $SQL_TMP;";
  $tpm=$db->Bsphp_my_query($sql);
  if($tpm){
   return $date;
  }
    return 0;
  }
  
  
  
  //读取制作卡列表 用时间
  function kaliebiao ($data,$mingcheng){
   $db=bs_lib::load_libbsphp_class('mysql', 'mysql');
   $GLOBALS_LANGS = bs_lib::load_langs_array('applib','admin_card');
        
    $sql="SELECT*FROM`bs_php_cardseries`WHERE`car_reDATE`='$data'";
    $db_array_value=$db->Bsphp_my_query($sql);
    $tset='';
    if($db_array_value){
     while ($array_value = mysqli_fetch_array($db_array_value))
            {
               if($array_value['car_pwd']==''){
                 $tset.='卡:'.$array_value['car_name'].'  时间'.$array_value['car_TianShu'].$GLOBALS_LANGS[$array_value['car_type']].'  软件名称:'.$mingcheng."\n";

               }else{
                 $tset.='卡:'.$array_value['car_name'].'  密:'.$array_value['car_pwd'].'  时间'.$array_value['car_TianShu'].$GLOBALS_LANGS[$array_value['car_type']].'  名称:'.$mingcheng."\n";

               }
              
            }
           return $tset;
     }
    
	return false;
}
  
  
  
  
  //读取制作卡列表 用时间还需要制作者UID
  function kaliebiao_USER ($data,$mingcheng,$uid){
    $db=bs_lib::load_libbsphp_class('mysql', 'mysql');
        
    $sql="SELECT*FROM`bs_php_cardseries`WHERE`car_reDATE`='$data'AND`car_admin`='$uid'";
    $db_array_value=$db->Bsphp_my_query($sql);
    $tset='';
    if($db_array_value){
     while ($array_value = mysqli_fetch_array($db_array_value))
            {
               $tset.='卡:'.$array_value['car_name'].'  密:'.$array_value['car_pwd'].'  时间'.$array_value['car_TianShu'].'天  软件名称:'.$mingcheng."\n";

            }
           return $tset;
     }
    
	return false;
}
  
  
  
  
  


?>