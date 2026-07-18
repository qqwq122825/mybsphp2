<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?> - by BSPHP</title>
<!--系统CSS-->
  <meta name="renderer" content="webkit">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css">



<!--
<?php
$urls=get_url_statics();
print <<< EOT
-->
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">软件列表</div>[<a href="javascript:window.location.reload();">+</a>]</div>
<!--  快捷  -->
<div id="Shortcut">
  <ul>
	      <li><a href="index.php?m=applib&c=admin_app&a=add" > 添加新软件</a></li>

  </ul>
</div>
<!--  搜索  -->
<!--  内容列表   -->


<form  class="layui-form"   lay-filter="baocun"  method="post" action="">

<table class="layui-table"  style="overflow: scroll;word-break: keep-all;width:98%;margin-left: 10px;" >
 <thead>
<tr align="center" bgcolor="" height="52">
	<th width="62" >&nbsp;选择</th>
	<th width="195" >软件名
	<th width="195" >软件代号</th>
	<th width="232" >软件模式</th>  
	<th width="551" >操作</th>
</tr> </thead>
			  <!--
EOT;


              while ($array_value = mysqli_fetch_array($db_array_value))
            {

			$moshi_array=get_moshiset_array($array_value['app_MoShi']);
			if(@$moshi_array['really']==1){
			   $moshi= $moshi_array['name'];
			
			}else{
			  $moshi= '无法识别模式';
			}
			
		
                print <<< EOT
-->





<tr align='center'   height="22" id=ul>
	<td>&nbsp;<input name="ids" lay-skin="primary"  type="checkbox"  value="{$array_value[0]}" ></td>
	<td><a href="index.php?m=applib&c=admin_app&a=appini&id={$array_value[0]}">{$array_value['app_name']}</a></td>
	<td align="left">{$array_value['app_daihao']}</td>
	<td align="left">{$moshi}</td>
	<td nowrap>
	
	

		 
		 
<div class="layui-btn-group">
  <button onClick=" window.location.href='index.php?m=applib&c=admin_app&a=appini&id={$array_value[0]}'; return false;" class="layui-btn layui-btn-primary"><i class="layui-icon">&#xe608;</i> 配置软件</button>
  <button onClick="window.location.href='index.php?m=applib&c=admin_user&a=table&daihao={$array_value[0]}'; return false;" class="layui-btn layui-btn-primary"><i class="layui-icon">&#xe68e;</i>用户列表</button>
  <button onClick="window.location.href='index.php?m=applib&c=admin_grouping&a=table&daihao={$array_value[0]}'; return false;" class="layui-btn layui-btn-primary"><i class="layui-icon">&#xe630;</i>用户分组</button>
  <button onClick="window.location.href='index.php?m=applib&c=admin_card&a=make&daihao={$array_value[0]}'; return false;" class="layui-btn layui-btn-primary"> <i class="layui-icon">&#xe654;</i>制卡</button>
  
<button onClick="window.location.href='index.php?m=applib&c=admin_card&a=table&daihao={$array_value[0]}'; return false;" class="layui-btn layui-btn-primary"> <i class="layui-icon">&#xe655;</i>充值卡列表</button>
  
  <button onClick="window.location.href='index.php?m=applib&c=admin_card&a=class&daihao={$array_value[0]}'; return false;" class="layui-btn layui-btn-primary"><i class="layui-icon">&#xe648;</i>卡串类型</button>
  <button onClick="window.location.href='index.php?m=applib&c=admin_card&a=makeshow&daihao={$array_value[0]}'; return false;" class="layui-btn layui-btn-primary"><i class="layui-icon">&#x1002;</i>导出卡串</button>
</div>
		 
		 
		 </td>
</tr>

<!--		  
EOT;
            }

            print <<< EOT
-->

<tr align='center' height="22" >
	<td colspan="10" bgcolor="#ECEFF4">
	<div class="anzuo">
	      <div class="layui-input-inline" style="width: 50px;">
      
	 <input type="checkbox" name="allchoose" lay-skin="primary" lay-filter="allchoose">
	 </div><div class="layui-input-inline" style="width: 150px;">
	<select name="select_class" id="select_class">
      <option>操作的项目..</option>
      <option>---------</option>
      <option value="1" >删除选择软件</option>
    </select>
	
	
	<input type="hidden" name="all" name="all" value="输入手机号码或邮箱" />
	
	
	</div>
	
	      <div class="layui-input-inline" style="width: 100px;">
        <input name="Submit_class" type="submit" class="layui-btn layui-btn-normal" id="Submit_class"   value="提交">
      </div>
</div>
	</td>
	</tr>
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
  ,laydate = layui.laydate
  ,$=layui.jquery

 ;
  
  
  
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
    alert(1);
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
  
  

  //全选/取消
  form.on('checkbox(allchoose)', function(data){
    
    var bsphpallid = $('input[name="ids"]');
    bsphpallid.each(function(index,item){
        item.checked = data.elem.checked;
    });

    form.render('checkbox');
    
  });
  
  
  //得到选择ID
  form.on('submit(baocun)', function(data){
    var ids = '';
  
    var bsphpallid = $('input[name="ids"]');
    bsphpallid.each(function(index,item){
        
        
       if(item.checked == true){
        
        ids=ids+item.value+',';
        
       }
    });
    ids=ids+'0';

    
    $('input[name="all"]').val(ids);

  });
  
  
  
});


  
</script>

<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>
</body>
</html>
