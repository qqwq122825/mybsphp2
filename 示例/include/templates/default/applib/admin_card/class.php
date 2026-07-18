<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?> - by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css">
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/table.js"></script>
<!--
<?php
print <<< EOT
-->
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">卡串管理 - {$app_name}</DIV>
[<a href="javascript:window.location.reload();">+</a>]</div>



<!--  快捷  -->
<div id="Shortcut">
  <ul>    
    <li><a href="index.php?m=applib&c=admin_app&a=appini&id={$app_daihao}" > 配置软件</a></li>
    <li><a href="index.php?m=applib&c=admin_user&a=table&daihao={$app_daihao}"  > 用户列表</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=table&daihao={$app_daihao}">卡串列表</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=make&daihao={$app_daihao}"> 制卡</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=class&daihao={$app_daihao}">卡串类型 </a></li>
    <li><a href="index.php?m=applib&c=admin_grouping&a=table&daihao={$app_daihao}">用户分组 </a></li>
          <li>|</li>
          <li><a href="index.php?m=applib&c=admin_card&a=add&daihao={$app_daihao}"  >添加新类型</a></li>
  </ul>
</div>

<!--  内容列表   -->
<form action=""   class="layui-form" name="form"  lay-filter="baocun"   method="post"  >
<table class="layui-table"  style="overflow: scroll;word-break: keep-all;width:98%;margin-left: 10px;">
 <thead>
<tr align="center" bgcolor="" height="52">
                  <th  width="*">ID</th>
                    <th  width="*">软件名称</th>
                    <th    width="*">代号</th>
                    <th   width="*">卡串名称</th>
                    <th   width="*">循环充值次数</th>
                    <th   width="*">循环充值限制</th>
                    <th   width="*">余额充值</th>
                    <th   width="*">期限</th>
					<th   width="*">转至用户组</th>
					<th   width="*">转至是否重计时间</th>
					<th   width="*">卡号长度</th>
					<th   width="*">卡号组成</th>
					<th   width="*">密码长度</th>
					<th   width="*">密码组成</th>
					<th   width="*">价格</th>
                    <th   width="*">代理价格</th>
                    <th   width="*"></th>
</tr> </thead>
			  <!--
EOT;


       while ($array_value = mysqli_fetch_array($db_array_value ))
            {

			if($array_value['lei_for']==0 )$array_value['lei_for']=1;
			
		$array_value['lei_weight']==1?$array_value['lei_weight']='是':$array_value['lei_weight']='否';
                print <<< EOT
-->





<tr align='center'   height="22" >

                    <td>{$array_value[0]}</td>
                    <td>{$app_name}</td>
                    <td>{$array_value[5]}</td>
                    <td>{$array_value[1]}</td>
					<td>{$array_value['lei_for']}次</td>
					<td>{$this->card_oid_array_name[$array_value['lei_for_id']]}</td>
					<td>{$array_value['lei_money']}</td>
					<td>{$array_value[2]}{$carsys_arr[$array_value['lei_type']]}</td>
					<td>{$class_array[$array_value['lei_class']]}</td>
					<td>{$array_value['lei_weight']}</td>
					<td>{$array_value['lei_cardint']}位</td>
					<td>{$this->card_array_name[$array_value['lei_cardset']]}</td>
					<td>{$array_value['lei_pwdint']}位</td>
					<td>{$this->card_array_name[$array_value['lei_pwdset']]}</td>
					<td>{$array_value[3]}元</td>
                    <td>{$array_value[4]}元</td>
                    <td nowrap="nowrap"><a href="javascript:"  onClick="top.showiframe('no','属性编辑 ID:{$array_value[0]}','index.php?m=applib&c=admin_card&a=modify&daihao={$app_daihao}&id={$array_value[0]}',700,620);"   >修改</a> | <a href="index.php?m=applib&c=admin_card&a=class&daihao={$app_daihao}&delete={$array_value[0]}"  onclick="return confirm('你确认要删除吗？')">删除</a></td>
</tr>

<!--		  
EOT;
            }

            print <<< EOT
-->
</table>
</form>


<!--
EOT;
?>
-->
<script>
layui.use(['form', 'layedit', 'laydate'], function(){
  var form = layui.form
  ,layer = layui.layer
  ,layedit = layui.layedit
  ,laydate = layui.laydate;
  
  //日期
  laydate.render({
    elem: '#date'
  });
  laydate.render({
    elem: '#date1'
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
</script>

<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>

</body>
</html>
