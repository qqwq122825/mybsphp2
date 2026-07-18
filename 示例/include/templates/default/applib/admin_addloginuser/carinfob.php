<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?> - by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css">
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/table.js"></script>
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/pageSystem.js"></script>
<!--
<?php


print <<< EOT
-->


</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">代理充值卡合计</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>

<!--  搜索  -->

<form name="formsoso"  class="layui-form"  method="get" action="">
<div class="soso">
   <div class="layui-form-item">
    <div class="layui-inline">
    
    
    
      <div class="layui-input-inline" style="width: 150px;">
		        <select name="daihao" id="daihao">
                
EOT;



        while ($value= mysqli_fetch_array($db_array_value_app )){
			 
          

			   echo "<option value=\"{$value['app_daihao']}\">{$value['app_name']}</option>";
			
			}
            
print <<< EOT
			
        </select>
	 </div>
    
      <div class="layui-input-inline" style="width: 150px;">
		  <select name="soso_id" class="txt" id="soso_id">
            <option value="1">代理商账号</option>

          </select>
	 </div>

      <div class="layui-input-inline" style="width: 100px;">
        <input name='soso' type='text' id="soso2"  class="layui-input"   placeholder="指定上级代理" value='{$soso2}' />
      </div>

      <div class="layui-input-inline" style="width: 100px;">
        <input name='soso' type='text' id="soso"  class="layui-input"   placeholder="代理商账号" value='{$soso}' />
      </div>


      <div class="layui-input-inline" style="width: 120px;">
        <input type="text" name="date1" id="date1" lay-verify="date" value="{$date1}" placeholder="激活开始时间" autocomplete="off" class="layui-input">
      </div>


      <div class="layui-input-inline" style="width: 120px;">
        <input type="text" name="date2" id="date2" lay-verify="date" value="{$date2}"  placeholder="激活结束时间" autocomplete="off" class="layui-input">
      </div>


      <div class="layui-input-inline" style="width: 100px;">
         <input name="soso_ok" type="submit" class="layui-btn layui-btn-normal"id="soso_ok" value="查询">
      </div>
    </div>
  </div>
  
   <span class="sosodiv">
   <input name="act" type="hidden" id="act" value="table">
  </span><span class="sosodiv">

  <input name="m" type="hidden" id="m" value="{$_GET['m']}">
  <input name="c" type="hidden" id="c" value="{$_GET['c']}">
  <input name="a" type="hidden" id="a" value="{$_GET['a']}">
  
  </span></div>
</form>






<!--  内容列表   -->

<form action=""   class="layui-form"   name="form"  method="post"    onClick="return allcheckbox();"  >

EOT;
$HOST_DATE=HOST_DATE;

if($daihao>0){

if($soso2==''){
    $soso2_sql= "";
}else{

    $sql = "SELECT * FROM  `bsphp_user`WHERE  `user_user` ='$soso2'     LIMIT 0 , 3000";
    $uids_arr = $this->db->Bsphp_my_array($sql);
    $soso2_sql= "and `user_user` =  '{$uids_arr['user_uid']}' ";
}


   if($soso==''){
       $soso_sql= "";
   }else{
       $soso_sql= "and `user_user` =  '$soso' ";
   }


    $sql = "SELECT * FROM  `bsphp_user`WHERE  `user_daili`  > 0 $soso_sql  $soso2_sql     LIMIT 0 , 3000";
    $db_array_value_user = $this->db->Bsphp_my_query($sql);
    while ($value_user = mysqli_fetch_array($db_array_value_user )){

        $car_admin = $value_user['user_uid'];






//$array = explode($soso,"|")

   // ECHO $array[0];



print <<< EOT
<table class="layui-table"  style="width:98%;margin-left: 10px;"  lay-filter="demoEvent">
<thead>
<tr align="center" bgcolor="" height="52">
                <th width="*" >账号</th>
                <th width="*" >{$value_user['user_user']}</th>
                <th width="*" >UID</th>
                <th width="*" >{$value_user['user_uid']}</th>
                <th width="*" >余额</th>
                <th width="*" >{$value_user['user_rmb']}元</th>
                <th width="*" >{$HOST_DATE}</th>
</tr>

<tr align="center" bgcolor="" height="52">
                <th width="*" >卡类ID</th>
                <th width="*" >卡类名称</th>
                <th width="*" >软件名称</th>
                <th width="*" >总数据量</th>
				<th width="*" >已激活</th>
                <th width="*">未激活</th>
                <th width="*">已冻结</th>

</tr>

</thead>
			  <!--
EOT;


/*开始枚举*/
$sql = "SELECT *, COUNT( * ) AS  `row` ,  `car_Lei`
FROM  `bs_php_cardseries`

where `car_DaiHao` = '$daihao' AND `car_reDATE` > '$date1 00:00:00' AND `car_reDATE` < '$date2 24:00:00' AND   `car_admin` = '$car_admin'
GROUP BY  `car_Lei`
ORDER BY  `car_Lei`
LIMIT 1000";

       $db_array_value = $this->db->Bsphp_my_query($sql);

        while ($value= mysqli_fetch_array($db_array_value ))
            {


                $soso = set_post('soso');


                $soso_sql = " and `car_admin` = '$car_admin' ";
                $app_array = get_appdaihao_info_array($value['car_DaiHao']);
                $lei_array = $this->db->my_class_array('bs_php_kalei','lei_id',$value['car_Lei'],' * ');
                
  
    $sql = "select count(*)as'hangshu' from`bs_php_cardseries`WHERE  `car_pur_date` > '$date1 00:00:00' AND `car_pur_date` < '$date2 24:00:00' AND `car_Lei`={$value['car_Lei']} and `car_IsLock`=1 $soso_sql and  `car_zhuangtai`=0  and `car_DaiHao`='{$value['car_DaiHao']}'";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    $zongshu_a = (int)$tmp_arr['hangshu']; //用户总数

    $sql = "select count(*)as'hangshu' from`bs_php_cardseries`WHERE  `car_reDATE` > '$date1 00:00:00' AND `car_reDATE` < '$date2 24:00:00' AND  `car_Lei`={$value['car_Lei']} and  `car_IsLock`=0 $soso_sql and  `car_zhuangtai`=0  and `car_DaiHao`='{$value['car_DaiHao']}'";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    $zongshu_b = (int)$tmp_arr['hangshu']; //用户总数
    
    
    $sql = "select count(*)as'hangshu' from`bs_php_cardseries`WHERE  `car_reDATE` > '$date1 00:00:00' AND `car_reDATE` < '$date2 24:00:00' AND  `car_Lei`={$value['car_Lei']} and  `car_zhuangtai`=1 $soso_sql and `car_DaiHao`='{$value['car_DaiHao']}'";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    $zongshu_c = (int)$tmp_arr['hangshu']; //用户总数	
                print <<< EOT
-->


<tr align='center'   height="22" >
           <td height="18" >{$value['car_Lei']}</td>
           <td height="18" >{$lei_array['lei_name']}</td>
		   <td height="18" >{$app_array['app_name']}&nbsp;</td>
                <td>{$value['row']}张</td>
                <td>{$zongshu_a}张</td>
                <td>{$zongshu_b}张</td>
                <td>{$zongshu_c}张</td>
</tr>

<!--		  
EOT;
            }
            print <<< EOT
-->
</table>
<BR/><BR/>
EOT;
    }}
print <<< EOT


<p> </p>






</form>

<script>
layui.use(['form', 'layedit', 'laydate'], function(){
  var form = layui.form
  ,layer = layui.layer
  ,layedit = layui.layedit
  ,laydate = layui.laydate;
  
  //日期
  laydate.render({
    elem: '#date1'
  });
  laydate.render({
    elem: '#date2'
  });
  
  //创建一个编辑器
  var editIndex = layedit.build('LAY_demo_editor');
 
  //自定义验证规则
  form.verify({
    title: function(value){
      if(value.length < 5){
        return '标题至少得5个字符啊';
      }
    }
    ,pass: [/(.+){6,12}$/, '密码必须6到12位']
    ,content: function(value){
      layedit.sync(editIndex);
    }
  });
  
  //监听指定开关
  form.on('switch(switchTest)', function(data){
    layer.msg('开关checked：'+ (this.checked ? 'true' : 'false'), {
      offset: '6px'
    });
    layer.tips('温馨提示：请注意开关状态的文字可以随意定义，而不仅仅是ON|OFF', data.othis)
  });
  
  //监听提交
  form.on('submit(demo1)', function(data){
    layer.alert(JSON.stringify(data.field), {
      title: '最终的提交信息'
    })
    return false;
  });
  
  
});
</script><script language="javascript">
select_set('soso_id',0);
select_set('DESC',0);
</script>

<!--
EOT;
?>
-->


<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>

</body>
</html>
