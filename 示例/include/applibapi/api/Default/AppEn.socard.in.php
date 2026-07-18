<?php


  /*<name>*/
  /***********************接口介绍说明******************************************
  * <appen>liuyan.in</appen>  
  * <appname>用户留言</appname> 
  * <sen>7<sen/>                
  * <seng1>table=>标题</seng>
  * <seng2>qq=>qq</seng>
  * <seng3>leix=>类型</seng>
  * <seng6>txt=>留言内容</seng>
  * <seng6>img=>验证码</seng>
  * <lei>ok</lei>               
  * <info>公告接口</info> 
  * *****************************************************************************
  */
  /*</name>*/

  $daihao = HOST_DAIHAO;
  $card = set_data('cardid');

  



    if ($card == '') {
        $textfield = '请输入激活码';
    } else {


      
        $info = $this->db->my_class_array('bs_php_cardseries', 'car_name', $card,' `car_id`,`car_zhuangtai`,`car_IsLock`,`car_pur_date` ');


        if (!$info) {
            $textfield = "您查询的激活码不存在";

        } else {

            if ($info['car_zhuangtai'] == 1) {
                $textfield = "您查询的授权码被冻结";


            } else {


                if ($info['car_IsLock'] == 1) {
                    $bsphp_pattern_login = $this->db->my_class_array('bs_php_pattern_login', 'L_ic_pwd',$card, ' `L_id`, `L_vip_unix`');


                    $textfield = "授权码已激活:\n\r激活时间:" . $info['car_pur_date'] . "\n\r到期时间:" . date('Y-m-d H:i:s',$bsphp_pattern_login['L_vip_unix']);
                } else {
                    $textfield = '您查询的授权码未激活';
                }
            }

        }
    }




PrInfo($textfield);

?>