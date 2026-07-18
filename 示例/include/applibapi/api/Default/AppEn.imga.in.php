<?php


  /*<name>*/
  /***********************接口介绍说明******************************************
  * <appen>imga.in</appen>  
  * <appname>验证码</appname> 
  * <sen>0<sen/>                
  * <seng1>table=>标题</seng>
  * <lei>ok</lei>               
  * <info>返回验证码</info> 
  * *****************************************************************************
  */
  /*</name>*/

 
  /**
   * 设置验证码
   */
   //$arr = explode('/', $_SERVER['REQUEST_URI']);
     $url= bs_lib::get_configs_value('sys','url').'/index.php?m=coode&sessl=';
  	PrInfo($url);		
		
  
  
  




?>