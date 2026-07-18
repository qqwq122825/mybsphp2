<?php

/*<name>*/
/**
 * **********************接口介绍说明******************************************
 * <appen>appbadpush.in</appen>  
 * <appname>破解后天提交日志</appname> 
 * <sen>3<sen/>                
 * <seng1>table=>内容</seng>
 * <lei>ok</lei>               
 * <info>用户登陆使用</info> 
 * *****************************************************************************
 */
/*</name>*/

//oid Login file
//oid links file

$daihao = HOST_DAIHAO;
$teble = set_data('table');
$uid = get_session_value('USER_UID');


bs_lib::addappenlog('od_po_log', $teble,$uid);

PrInfo(1);

?>