<!--
<?php
print <<< EOT
-->
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>BSPHP验证系统  by Bsphp</title>
<!--系统CSS-->

 <link rel="stylesheet" href="../statics/default/admin/layui/css/layui.css?t=1515376178709" media="all">
 <script src="../statics/default/admin/layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="../statics/default/admin/skin/style/base.css">
<script src="../statics/default/admin/skin/js/table.js"></script>
<script src="../statics/default/admin/skin/js/pageSystem.js"></script>

</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">新闻文章列表</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>
<!--  搜索  -->
<form name="formsoso"  class="layui-form"  method="get" action="">
<div class="soso">

   <div class="layui-form-item">
    <div class="layui-inline">
      <div class="layui-input-inline" style="width: 150px;">
      
	       <select name='soso_id' id="soso_id"  class="t_aera" style='width:120'>
            <option value="1" >标题</option>

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
  <input name="m" type="hidden" id="m" value="{$_GET['m']}">
  <input name="c" type="hidden" id="c" value="{$_GET['c']}">
  <input name="a" type="hidden" id="a" value="{$_GET['a']}">
  </span></div>
</form>








<!--  内容列表   -->

<form action=""  name="form"  method="post"    onClick="return allcheckbox();"  >
<table class="layui-table"  style="width:98%;margin-left: 10px;"  lay-filter="demoEvent">

<tr align="center" bgcolor="" height="22">
	<th width="68" >&nbsp;选择</th>
	<th width="101" >分类
	<th width="160" >标题
	  </td>
	<th width="162" >时间
	  </td>	  

	<th width="89" >操作</th>
</tr>
			  <!--
EOT;


             while ($array_value= mysqli_fetch_array($db_array_value ))
            {
			
			$array_value[4]=date('Y-m-d H:i', $array_value['news_unix']);

                print <<< EOT
-->





<tr align='center'   height="22" >
	<td>&nbsp;<input type="checkbox" id="id" value="{$array_value[0]}" class="np"></td>
	<td>{$array_value[3]}</td>
	<td align="left">{$array_value[1]}</td>
	<td align="left">{$array_value[4]}</td>


	<td><a href="index.php?m=index&c=admin_news&a=usb&id={$array_value[0]}"  >编辑</a> </td>
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
      
	      <input onClick="checkAll(this, 'id')"  type="checkbox"  onmousedown="checkboxname.disabled=true"  class="np">
	 </div><div class="layui-input-inline" style="width: 150px;">
	<select name="select_class" id="select_class">
      <option>操作的项目..</option>
      <option value="1">删除选择</option>
    </select>
	</div><input name="all" type="hidden" id="all">
	<input name="baocun" type="submit" class="baocun" id="baocun"  value="提交">
</div>
<!--page-->
<div id="pageDiv"></div>	</td>
	</tr>
</table>
</form>

<script language="javascript">
select_set('soso_id',{$soso_id_show});
select_set('DESC',{$DESC_id});
</script>




<script language="javascript">
select_set('soso_id',{$soso_id_show});
select_set('DESC',{$DESC_id});
</script>


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


<!--
EOT;
?>
-->


<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>
</body>
</html>