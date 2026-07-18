<?php
$aaa =  BSPHP_DIR_SYS.'include/templates/default/agent_new/main';
// E//XIT;
include ($aaa.'/user_header.php');
?>
<?php
include ($aaa.'/user_nav.php');
?>
<?php
include ($aaa.'/user_sidebar.php');
?>


    <aside class="right-side">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <h1>
                充值卡统计
                <small>User List</small>
            </h1>
        </section>
        <!-- Main content -->
        <section class="content">
            <div class="row">
                <div class="col-xs-12"> <p>
                        <!--p> <a class="btn btn-success btn-sm" href="">添加</a> </p-->


                    <form action="" method="get">

                        <input name="m" type="hidden" id="m" value="<?php echo $_GET['m'] ?>">
                        <input name="c" type="hidden" id="c" value="<?php echo $_GET['c'] ?>">
                        <input name="a" type="hidden" id="a" value="<?php echo $_GET['a'] ?>">

                        <input name="act" type="hidden" id="act" value="table">
                        <input name="soso_id" type="hidden" id="soso_id" value="1">

                        <div class="form-group input-group" style="width: 300px;">
                            <select name="daihao" id="daihao" style="width: 300px;height: 28px;">

                              <?PHP



                                while ($value= mysqli_fetch_array($db_array_value_app )){



                                echo "<option value=\"{$value['app_daihao']}\">{$value['app_name']}</option>";

                                }

                              ?>



                            </select>
                        </div>


                        <input name="soso_ok" type="hidden" class="soso_ok" value="搜搜">

                        <div class="form-group input-group" style="width: 300px;">
                            <input type="text" class="form-control" value="<?php  if(set_get('user')!=='') $soso=set_get('user'); echo $soso; ?>" name="soso" placeholder="查询代理账号">
                        </div>

                        <div class="form-group input-group date" style="width: 300px;">
                        
                            <input type="text"   onClick="laydate()" class="form-control" value="<?php echo $date1; ?>" name="date1" placeholder="激活/制卡开始时间 2018-05-06">
                        </div>
                        <div class="form-group  input-group" style="width: 300px;">
                            <input type="text"    onClick="laydate()" class="form-control" value="<?php echo $date2; ?>" name="date2" placeholder="激活/制卡结束时间 2018-05-07">
                        </div>
                        <div class="form-group input-group" style="width: 300px;">
                        <input class="btn btn-default" type="submit" value="查询">

                        </div>
                        
            
                        
                    </form>



                    <div class="box">
                        <div class="box-body table-responsive no-padding">
                            <table id="userTable" class="table table-bordered table-hover">
                                <thead>
                                <tr>

                                    <th>卡类ID</th>

                                    <th>卡类名称</th>
                                    <th>软件名称</th>
                                    <th>总卡数</th>
                                    <th>已激活</th>
                                    <th>未激活</th>
                                    <th>已冻结</th>

                                </tr>
                                </thead>
                                <tbody>
                                <?php

if($daihao>0){

                                while ($value = mysqli_fetch_array($db_array_value ))
                                {


                                    //$soso = set_post('soso');
                                    //if($soso==''){
                                     //   $soso_sql = " and `car_admin` = '{$this->user_array[0]}' ";
                                   // }


                                    $app_array = get_appdaihao_info_array($value['car_DaiHao']);
                                    $lei_array = $this->db->my_class_array('bs_php_kalei','lei_id',$value['car_Lei'],' * ');


                                    $sql = "select count(*)as'hangshu' from`bs_php_cardseries`WHERE  `car_pur_date` > '$date1  00:00:00' AND `car_pur_date` < '$date2 23:59:59' AND `car_Lei`={$value['car_Lei']}  and  `car_zhuangtai`= 0  and `car_IsLock`=1 $soso_sql and `car_DaiHao`='{$value['car_DaiHao']}'";
//echo $sql;
                                    $tmp_arr = $this->db->Bsphp_my_array($sql);
                                    $zongshu_a = (int)$tmp_arr['hangshu']; //全部

                                    $sql = "select count(*)as'hangshu' from`bs_php_cardseries`WHERE  `car_reDATE` > '$date1  00:00:00' AND `car_reDATE` < '$date2 23:59:59' AND  `car_Lei`={$value['car_Lei']} and  `car_zhuangtai`= 0  and  `car_IsLock`=0 $soso_sql and `car_DaiHao`='{$value['car_DaiHao']}'";
                                    //echo $sql;
                                    
                                    $tmp_arr = $this->db->Bsphp_my_array($sql);
                                    $zongshu_b = (int)$tmp_arr['hangshu']; //未使用


                                    $sql = "select count(*)as'hangshu' from`bs_php_cardseries`WHERE  `car_reDATE` > '$date1  00:00:00' AND `car_reDATE` < '$date2 23:59:59' AND  `car_Lei`={$value['car_Lei']} and  `car_zhuangtai`= 1 $soso_sql and `car_DaiHao`='{$value['car_DaiHao']}'";
                                    $tmp_arr = $this->db->Bsphp_my_array($sql);
                                    $zongshu_c = (int)$tmp_arr['hangshu']; //冻结
                                    print <<< EOT



<tr align='center'   height="22" >
           <td height="18" >{$value['car_Lei']}</td>
           <td height="18" >{$lei_array['lei_name']}</td>
		   <td height="18" >{$app_array['app_name']}&nbsp;</td>
                <td>{$value['row']}张</td>
                <td>{$zongshu_a}张</td>
                <td>{$zongshu_b}张</td>
                <td>{$zongshu_c}张</td>
</tr>

EOT;
                                }
}
                                ?>
                                </tbody>
                            </table>


                        </div><!-- /.box-body -->
                    </div><!-- /.box -->
                </div>
            </div>
        </section><!-- /.content -->
    </aside><!-- /.right-side -->
    <script src="/statics/default/agent_new/laydate/laydate.js"></script>
<script>
;!function(){
laydate({
   elem: '#demo'
})
}();
</script>
    

<?php
include ($aaa.'/user_footer.php');
?>
