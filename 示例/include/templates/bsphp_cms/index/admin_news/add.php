<!--
<?php
$URL = '../statics/test/';
print <<< EOT
-->
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf8">
<title>BSPHP验证系统  by Bsphp</title>
<!--系统CSS-->

 <link rel="stylesheet" href="../statics/default/admin/layui/css/layui.css?t=1515376178709" media="all">
 <script src="../statics/default/admin/layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="../statics/default/admin/skin/style/base.css">
<script src="../statics/default/admin/skin/js/table.js"></script>
<script src="../statics/default/admin/skin/js/pageSystem.js"></script>

<style type="text/css">
<!--
#form1 textarea {
	height: 500px;
	width: 99%;
}
.STYLE3 {font-size: 12px}
.STYLE4 {
	font-size: 13px;
	font-weight: bold;
	color: #1F4A65;
}
.STYLE5 {color: #FF0000}
-->
</style>
</head>
<body leftmargin="8" topmargin="8" >
	<link rel="stylesheet" href="{$URL}themes/default/default.css" />
	<link rel="stylesheet" href="{$URL}plugins/code/prettify.css" />
	<script charset=gbk src="{$URL}kindeditor.js"></script>
	<script charset=gbk src="{$URL}lang/zh_CN.js"></script>
	<script charset=gbk src="{$URL}plugins/code/prettify.js"></script>
	<script>
		KindEditor.ready(function(K) {
			var editor1 = K.create('textarea[name="news_test"]', {
				cssPath : '{$URL}/plugins/code/prettify.css',
				uploadJson : '{$URL}/upload_json.php',
				fileManagerJson : '{$URL}/file_manager_json.php',
				allowFileManager : true,
				afterCreate : function() {
					var self = this;
					K.ctrl(document, 13, function() {
						self.sync();
						K('form[name=example]')[0].submit();
					});
					K.ctrl(self.edit.doc, 13, function() {
						self.sync();
						K('form[name=example]')[0].submit();
					});
				}
			});
			prettyPrint();
		});
		
		
		

	</script>
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">新闻文章添加</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>

<p>
  <!--  内容列表   -->
</p>
<form action=""   class="layui-form" name="form"  method="post"    onClick="return allcheckbox();"  >

  
 <table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">
                  <tr>
                    <td width="59">&nbsp;</td>
                    <td width="201">&nbsp;</td>
                    <td width="136">&nbsp;</td>
                  </tr>
                  <tr>
                    <td height="20" nowrap class="STYLE2"><div align="right">标题:</div></td>
                    <td nowrap><label>
                      <input name="news_table"   class="layui-form-label"  type="text" id="news_table" value="{$array[1]}" size="50">
                      <span class="STYLE5"> {$table_not} </span></label></td>
                    <td nowrap>&nbsp;</td>
                  </tr>
                  <tr>
                    <td nowrap><div align="right">栏目:</div></td>
                    <td nowrap><label><div class="layui-input-inline" style="width: 150px;">
                      <select name="news_class" id="news_class">
                        

                              
EOT;

					 while ($array_value = mysqli_fetch_array($db_array_value))
					 
					 {
$selected='';
if($array[3]==$array_value[0]) $selected='selected';
print <<< EOT


					
 <option value="{$array_value[0]}" {$selected} >{$array_value[1]}</option>
                        
EOT;
					 }
					 

print <<< EOT
                    
           
           
                      </select></div>
                    </label></td>
                    <td nowrap><label></label></td>
                  </tr>
                  <tr>
                    <td nowrap>&nbsp;</td>
                    <td nowrap>&nbsp;</td>
                    <td nowrap><label></label></td>
                  </tr>
                  <tr>
                    <td nowrap><div align="right">内容:</div></td>
                    <td nowrap><textarea name="news_test" id="news_test" style="width:700px;height:300px;visibility:hidden;">{$array[2]}</textarea>
                        <span class="STYLE5"> {$content1_not}</span></td>
                    <td nowrap><label></label></td>
                  </tr>
                  <tr>
                    <td nowrap>&nbsp;</td>
                    <td nowrap>&nbsp;</td>
                    <td nowrap><label></label></td>
                  </tr>
                  <tr>
                    <td nowrap>&nbsp;</td>
                    <td nowrap><label>
                      <input type="submit" id="admin" name="Submit" class="layui-btn layui-btn-normal" value="添加新内容">
                      <!--onClick="return confirm('你确定制作吗？ 确定将制作充值卡和扣除对应金额.')"-->
                    </label></td>
                    <td nowrap><label></label></td>
                  </tr>
                </table>
				</label>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
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




<!--
EOT;
?>
-->


<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>
</body>
</html>