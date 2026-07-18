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
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>BSPHP验证系统  by Bsphp</title>
<link rel="stylesheet" type="text/css" href="skin/style/base.css">

</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">制卡列表</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>
<p>
  <!--  搜索  -->
<!--  快捷  -->
<div id="Shortcut">
  <ul>
	      <li><a href="index.php?m=applib&c=admin_card&a=make&daihao={$app_daihao}">  返回</a></li>

		  <li><a href="index.php?m=applib&c=admin_card&a=table&daihao={$app_daihao}">卡串列表</a></li>
		  <li><a href="index.php?m=applib&c=admin_card&a=make&daihao={$app_daihao}"> 制卡</a></li>

  </ul>
</div>




<form name="formsoso"  class="layui-form"  method="get" action="">
<div class="soso">
   <div class="layui-form-item">
    <div class="layui-inline">
      <div class="layui-input-inline" style="width: 150px;">
		  <select name="soso_id" class="txt" id="soso_id">
            <option value="1">充值卡号</option>
            <option value="2">制作时间</option>
            <option value="3">充值用户</option>
            <option value="4">制卡人</option>
            <option value="5">状态=&gt;停封</option>
            <option value="6">状态=&gt;正常</option>
            <option value="7">使用=&gt;未使用</option>
            <option value="8">使用=&gt;已使用</option>
            <option value="9">充值时间</option>
          </select>
	 </div>
      <div class="layui-input-inline" style="width: 300px;">
        <input name='soso' type='text' id="soso"  class="layui-input"   placeholder="搜索内容" value='{$soso}' />

      </div>
      <div class="layui-input-inline" style="width: 100px;">
      
	<select name='DESC' id="DESC"  class="t_aera"  >
         <option value="0">正序</option>
         <option value="1">倒序</option>
    </select>

	 </div>
      <div class="layui-input-inline" style="width: 100px;">
         <input name="soso_ok" type="submit" class="layui-btn layui-btn-normal"id="soso_ok" value="搜索">
      </div>
    </div>
  </div>
  
   <span class="sosodiv">
   <input name="act" type="hidden" id="act" value="table">
  </span><span class="sosodiv">
  <input name="daihao" type="hidden" id="daihao" value="{$app_daihao}">
  <input name="m" type="hidden" id="m" value="{$_GET['m']}">
  <input name="c" type="hidden" id="c" value="{$_GET['c']}">
  <input name="a" type="hidden" id="a" value="{$_GET['a']}">
  
  </span></div>
</form>










  <!--  内容列表   -->
</p>
<form action=""  name="form"  method="post"   class="layui-form"     onClick="return allcheckbox();"  >
  <textarea name="textarea" cols="160" rows="60" style="width:100%;height:300px;">{$tmp}</textarea>
</form><script>
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


<!--
EOT;
?>
-->


<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>

</body>
</html>
