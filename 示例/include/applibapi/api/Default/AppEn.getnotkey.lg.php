<?php

/*<name>*/
/***********************接口介绍说明******************************************
 * <appen>getnotkey.lg</appen>  
 * <appname>查询绑定key是否存在</appname> 
 * <sen>1<sen/>                
 * <seng1>key=>查询key</seng>
 * <lei>ok</lei>               
 * <info>完善用户资料</info> 
 * *****************************************************************************
 */
/*</name>*/

  //oid Login file
  $daihao = HOST_DAIHAO;
  $key = set_data('key');

  if(login_key_zhong($daihao,$key)){
    //重覆返回
    PrInfo('10089');
  }else{
    //没有重覆返回
    PrInfo('10809');
  }







?>