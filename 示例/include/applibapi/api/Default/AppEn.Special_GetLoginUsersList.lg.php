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
$uid = get_session_value('USER_UID');
$BSphpSeSsL = set_data('BSphpSeSsL');


            $sql = "SELECT * FROM`bs_php_pattern_login` WHERE  `L_User_uid`='$uid' AND`L_daihao`='$daihao'  ORDER BY`L_id`DESC LIMIT 1000";
            $db_array_value = $this->db->Bsphp_my_query($sql);
            $array = array();
            while ($value= mysqli_fetch_array($db_array_value ))
            {
                
                
                $array[]=array(
                   'id'=>$value['L_id'],
                   'User_uid'=>$value['L_User_uid'],
                   're_date'=>$value['L_re_date'],
                   'vip_unix'=>$value['L_vip_unix'],
                   'ic_name'=>$value['L_ic_name'],
                   'links_info'=>$value['L_links_info'],
                   'cardname'=>$value['L_ic_pwd'],
                   
                   
                   
                   
                );
                
                
                
            }
            
            
            
        $array = json_encode($array);

//一切正常
PrInfo($array);

?>