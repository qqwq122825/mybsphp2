<?php
const BSPHP_SET = 'NOTMODE';
require_once ('../LibBsphp/Global.Bsphp.Inc.php');


$textfield = set_post('code');
$ok = set_post('ok');
if ($ok) {

    if ($textfield == '') {
        $textfield = '请输入激活码';
    } else {


        $db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $info = $db->my_class_array('bs_php_cardseries', 'car_name', $textfield,
            ' `car_id`,`car_zhuangtai`,`car_IsLock`,`car_pur_date` ');


        if (!$info) {
            $textfield = "您查询的激活码不存在<BR/>";

        } else {

            if ($info['car_zhuangtai'] == 1) {
                $textfield = "您查询的授权码被冻结";


            } else {


                if ($info['car_IsLock'] == 1) {
                    $bsphp_pattern_login = $db->my_class_array('bs_php_pattern_login', 'L_User_uid',
                        $textfield, ' `L_id`, `L_vip_unix`');


                    $textfield = '授权码已激活:<BR/>激活时间:' . $info['car_pur_date'] . "<BR/>到期时间:" . date('Y-m-d H:i:s',
                        $bsphp_pattern_login['L_vip_unix']);
                } else {
                    $textfield = '您查询的授权码未激活';
                }
            }

        }
    }


}



?>
<!DOCTYPE html>
<html lang="zh-CN">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=0.5.5,maximum-scale=1.0,user-scalable=0" />
    <title><?php
echo bs_lib::get_configs_value('sys', 'name');
?>激活码码查询</title>
   
    <link rel="stylesheet" href="css/bootstrap.min.css"  crossorigin="anonymous">
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"  crossorigin="anonymous"></script>
    <script src="js/icheck.min.js"></script>
    <link rel="stylesheet" href="css/mobile.css?122343" >

  </head>


  <body class="m_b">
    <div class="logo_w">
        <div class="logo" style="font-size: 35px;"><?php
echo bs_lib::get_configs_value('sys', 'name');
?>激活码码查询</div>
    </div>
    <div style="height:70px;"></div>
    <div class="login_body">

      <form class="form" action=""  method="post">

      <div class="m-item m-fore1">
        <label for="username" class="login-label name-label"></label>
        <input type="text" name="code" id="code" class="n_ys" value="<?php echo
set_post('code'); ?>"  placeholder="请输入激活码/充值卡查询">
      </div>


         
      <div class="login_body_f" style="padding-top:30px;">
        <button type="submit" value="ok" name="ok" class="btn-register">查询</button>
        
      </div>

      </form>

    </div>


<?php
if (!$textfield == '') {
    echo '<div class="error_text row">
    
    <span class="error_b error_nr">' . $textfield . '<?php
	echo 
?></span>';
}
?>


    
</div>
<style>
.mobile_modal .modal-dialog{
    width: 600px;
}
.mobile_modal .modal-title{
    font-size:28px;
}
.mobile_modal .modal-body{
    font-size:28px;
}
</style>

 </body>
</html>