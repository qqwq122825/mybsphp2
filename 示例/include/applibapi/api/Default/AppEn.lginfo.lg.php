<?php

/*<name>*/
/***********************接口介绍说明******************************************
 * appen    接口文件名称
 * appname  使用名称
 * sen      参数数量
 * info     介绍
 * seng     擦拭名称
 * lei      是否系统接口
 * <appen>lginfo.lg</appen>  
 * <appname>取得登陆状态信息</appname> 
 * <sen>0<sen/>                
 * <seng1>参数=>接收内容</seng>
 * <lei>ok</lei>               
 * <info>取得用户登陆成功返回信息</info> 
 * *****************************************************************************
 */
/*</name>*/
//oid links file
//links_chaoshi_login();

$log=$this->user->UserLoginInfo();
if($log==1047)PrInfo('1');
PrInfo($this->user_str_log[$log]);
?>