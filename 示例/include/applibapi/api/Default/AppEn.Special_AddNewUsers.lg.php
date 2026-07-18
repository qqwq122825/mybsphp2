<?php

/*<name>*/
/***********************接口介绍说明******************************************
* <appen>timeoutupdate.lg</appen>  
* <appname>超时更新</appname> 
* <sen>1<sen/>                
* <seng1>key=>绑定特征</seng>
* <lei>ok</lei>               
* <info>绑定特征</info> 
* *****************************************************************************
*/
/*</name>*/
//oid Login file
//oid links file

$daihao = HOST_DAIHAO;

$users = set_data('users');

if($users==''){
   $users = get_session_value('USER_USER'); 
}

$BSphpSeSsL = set_data('BSphpSeSsL');
$cardid = set_data('cardid');
$cardpwd = set_data('cardpwd');
$key = set_data('key');


$log = LoginCarAddUsers($cardid,$cardpwd,$key,$daihao,$users);



//一切正常
PrInfo($this->user_str_log[$log]);

?>