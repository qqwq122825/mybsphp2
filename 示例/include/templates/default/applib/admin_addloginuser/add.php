<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?> - by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css">
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/table.js"></script>
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">软件添加</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>
<div id="Shortcut">
  <ul>
	      <li><a href="index.php?m=applib&c=admin_app&a=table" > 软件管理</a></li>

  </ul>
</div>
<!--  搜索  -->
<!--  内容列表   -->
<form action=""  name="form"  class="layui-form"  method="post"    onClick="return allcheckbox();"  >
  <table width="460" height="383" border="0" align="center">
    <tr bgcolor="#FFFFFF">
      <td width="24%" height="20" align="right" nowrap>选择软件：</td>
      <td width="76%" height="20" nowrap><label>
      <select name="daihao" id="daihao">
			<?php 

              while ($value= mysqli_fetch_array($db_array_value )){



			   echo "<option value=\"{$value['app_name']}\">{$value['app_name']}</option>";
			
			}
			?>
        </select>
      </label></td>
    </tr>
    <tr bgcolor="#FFFFFF">
      <td align="right" nowrap>添加的ID名：</td>
      <td nowrap><input name="name" type="text" id="name"  placeholder="手动添加登录关系,账号模式添加UID,卡模式随意"  class="layui-input"value="<?php echo $daihao ?>">
         </td>
    </tr>

 
    <tr>
      <td  colspan="2" align="center"><div align="center">
          <p>
            <input name="leiadd" type="submit" class="layui-btn layui-btn-normal"  id="leiadd"  style="width:100px" value="确认添加">
          </p>
       </td>
    </tr>
  </table>
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



<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">Bsphp验证系统 <?php echo BSPHP_VERSION; ?></a>  Bsphp.com <br>
All Rights Reserved </div>
</body>
</html>