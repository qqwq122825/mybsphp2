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
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">API匿名转换工具</DIV> [<a href="javascript:window.location.reload();">+</a>]</div>
<p>
  <!--  搜索  -->
<!--  快捷  -->
<div id="Shortcut">
  <ul>
	      <li><a href="index.php?m=applib&c=admin_app&a=table">软件列表</a></li>



  </ul>
</div>
  <!--  内容列表   -->
</p>
<form action=""  name="form"  method="post"   class="layui-form"   onClick="return allcheckbox();"  >
  <textarea  placeholder="原内容"  style="width:98%;margin-left: 10px;font-family: sans-serif;" name="dedetext" cols="160" rows="20" style="width:100%;height:300px;"><?PHP echo $dedetext ?></textarea>
  <BR/><BR/>
  <label><div style="margin-left: 10px;font-family: sans-serif;"  class="layui-input-inline" style="width: 450px;">
  <select name="daihao">
    <option value="0">请选择软件API加密密码</option>
	
	<?PHP 
  
	
     while ($value= mysqli_fetch_array($tmp))
            {
			
	
			if($value['app_daihao']==$daihao){
			   $selected="selected='selected'";
			   
			   
			
			   
			}else{
			   $selected='';
			
			}
			
			 
			if($value['app_api_pwd']=='' or $value['app_api_pwd']=='0'){
			
			  
			}ELSE{
			   echo "<option {$selected} value=\"{$value['app_daihao']}\">{$value['app_name']}->{$value['app_daihao']}->密码{$value['app_api_pwd']}</option>";
			}
			
                

            }
	
	
	
	 ?>
	
	
	
  </select></div>
  </label>
  <input id="admin" type="submit"   class="layui-btn layui-btn-normal"  name="deapi" value="↑加密API到别名↓">
  <input id="admin" type="submit"   class="layui-btn layui-btn-normal"   name="enapi" value="↑别名解密到API↓">
  说明:请在上边输入框输入含API解密代码块如:E,DELPHI,C++等代码,系统将自动替换成已经加密/解密代码,密码为:别名接口密码 建议所有软件都设置同一个密码,搞混调试起来比较麻烦
  <BR/>
  <BR/>  
  <textarea  placeholder="结果"  style="width:98%;margin-left: 10px;font-family: sans-serif;" name="enentext" cols="160" rows="20" style="width:100%;height:300px;"><?php ECHO $enentext ?></textarea>
</form>



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
