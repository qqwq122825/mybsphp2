<?php


  /*<name>*/
  /***********************接口介绍说明******************************************
  * <appen>date.in</appen>  
  * <appname>取系统时间</appname> 
  * <sen>1<sen/>                
  * <seng>m=>格式</seng>
  * <lei>ok</lei>               
  * <info>取系统时间</info> 
  * *****************************************************************************
  */
  /*</name>*/

    $m=set_data('m');

    if($m=='') $m='Y-m-d H:i:s';
  	PrInfo(date($m,HOST_UNIX));
		




?>