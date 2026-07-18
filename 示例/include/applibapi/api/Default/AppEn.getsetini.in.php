<?php

/*<name>*/
/***********************接口介绍说明******************************************
 * <appen>setini.in</appen>  
 * <appname>配置表</appname> 
 * <sen>0<sen/>                
 * <seng1>参数=>接收内容</seng>
 * <lei>ok</lei>               
 * <info>系统提供URL地址</info> 
 * *****************************************************************************
 
 
 CREATE TABLE IF NOT EXISTS `plug_bsphp_setini` (
  `setini_id` int(11) NOT NULL AUTO_INCREMENT,
  `setini_name` varchar(255) NOT NULL,
  `setini_data1` varchar(255) NOT NULL,
  `setini_data2` varchar(255) NOT NULL,
  `setini_data3` varchar(255) NOT NULL,
  PRIMARY KEY (`setini_id`),
  UNIQUE KEY `setini_name` (`setini_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='漫游配置表' AUTO_INCREMENT=2 ;


 */
/*</name>*/
    $setname = set_data('setname'); //键明
    $date1 = set_data('date1');
    $date2 = set_data('date2');
    $date3 = set_data('date3');

    
    //判断是否库是否已经存在
    $sql="SELECT * FROM `plug_bsphp_setini` WHERE `setini_name` LIKE '$setname' LIMIT 1";
    $array=$this->db->Bsphp_my_query($sql);
    IF($array){
      
       PrInfo($array['date1'].'|'.$array['date2'].'|'.$array['date3']);
       
     //新插入数据  
    }else{
      PrInfo(-1);
    }
    
//执行完成返回1

?>