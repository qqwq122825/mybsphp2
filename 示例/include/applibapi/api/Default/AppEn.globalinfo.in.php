<?php

/*<name>*/
/***********************接口介绍说明******************************************
 * <appen>globalinfo.in</appen>  
 * <appname>取软件配置信息段</appname> 
 * <sen>0<sen/>                
 * <seng1>info=>接收内容</seng>
 * <lei>ok</lei>               
 * <info>取软件版本号</info> 
 * *****************************************************************************
 */
/*</name>*/
$info=set_data('info');//信息
/**
 * 信息字段：
GLOBAL_WEBURL=取Web浏览地址
GLOBAL_URL=预设URL地址 
GLOBAL_LOGICA=逻辑提示A
GLOBAL_LOGICINFOA=逻辑提示A内容
GLOBAL_LOGICB=逻辑提示B
GLOBAL_LOGICINFOB=逻辑提示B内容
GLOBAL_BSPHPDATE=Bsphp服务器系统时间
GLOBAL_BSPHPUNIX=Bsphp服务器系统时间UNIX时间
GLOBAL_V=版本号
GLOBAL_INTERNET=网络通信验证 正常返回1,错误返回服务器代码
GLOBAL_GG=取软件公告
GLOBAL_MIAOSHU=取软件公告
GLOBAL_SENDDATE=注册赠送时间 单位:秒
GLOBAL_TURN=转绑定扣除时间 单位:秒
GLOBAL_LINKS=软件最大同时连接数
GLOBAL_CHARGESET=是否免费模式,免费模式 = 1

*/
$info=str_replace('GLOBAL_WEBURL',$Global_DB_AppDaTa_Array['app_WEB_URL'],$info);
$info=str_replace('GLOBAL_URL',$Global_DB_AppDaTa_Array['app_URL'],$info);
$info=str_replace('GLOBAL_LOGICA',$Global_DB_AppDaTa_Array['app_LogicA'],$info);
$info=str_replace('GLOBAL_LOGICINFOA',$Global_DB_AppDaTa_Array['app_LogicinfoA'],$info);
$info=str_replace('GLOBAL_LOGICB',$Global_DB_AppDaTa_Array['app_LogicB'],$info);
$info=str_replace('GLOBAL_LOGICINFOB',$Global_DB_AppDaTa_Array['app_LogicinfoB'],$info);
$info=str_replace('GLOBAL_BSPHPDATE',HOST_DATE,$info);
$info=str_replace('GLOBAL_BSPHPUNIX',HOST_UNIX,$info);
$info=str_replace('GLOBAL_V',$Global_DB_AppDaTa_Array['app_v'],$info);
$info=str_replace('GLOBAL_INTERNET','1',$info);
$info=str_replace('GLOBAL_GG',$Global_DB_AppDaTa_Array['app_gg'],$info);
$info=str_replace('GLOBAL_MIAOSHU',$Global_DB_AppDaTa_Array['app_miaoshu'],$info);
$info=str_replace('GLOBAL_SENDDATE',$Global_DB_AppDaTa_Array['app_re_date'],$info);
$info=str_replace('GLOBAL_TURN',$Global_DB_AppDaTa_Array['app_zhuang_date'],$info);
$info=str_replace('GLOBAL_LINKS',$Global_DB_AppDaTa_Array['app_links'],$info);
$info=str_replace('GLOBAL_CHARGESET',$Global_DB_AppDaTa_Array['app_chargeset'],$info);



PrInfo($info);
?>