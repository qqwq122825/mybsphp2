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
<!--翻页-->
<script language="javascript">
   var psys;
   window.onload = function() {
    psys = new PageSystem({$zongshu}, "pageDiv", 10, showMsg,{$FANYE}); //总记录数, 分页系统容器，每组10页,回调,位置
    psys.init();
	
	
	   
  }
  
  function showMsg(currentPage, pageSize) {
	location="{$url}"+currentPage;
  }
   

   
</script>
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->

<div class="nametable"><div id="texttale"  class="texttale">用户列表</DIV>[<a href="index.php?m=applib&c=admin_user&a=table&daihao={$app_daihao}">+</a>]</div>
<!--  快捷  -->
<div id="Shortcut">
  <ul>
    <li><a href="index.php?m=applib&c=admin_app&a=appini&id={$app_daihao}" > 配置软件</a></li>
    <li><a href="index.php?m=applib&c=admin_user&a=table&daihao={$app_daihao}"  > 用户列表</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=table&daihao={$app_daihao}">卡串列表</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=make&daihao={$app_daihao}"> 制卡</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=class&daihao={$app_daihao}">卡串类型 </a></li>
    <li><a href="index.php?m=applib&c=admin_grouping&a=table&daihao={$app_daihao}">用户分组 </a></li>
  </ul>
</div>
<!--  搜索  -->

<form name="formsoso"  class="layui-form"  method="get" action="">
<div class="soso">


  

   <div class="layui-form-item">
    <div class="layui-inline">
      <div class="layui-input-inline" style="width: 150px;">
      
		<select name="soso_id" class="txt" id="soso_id">
          <option value="1" >UID</option>
          <option value="2" >用户名</option>
          <option value="3" >是否到期=&gt;到期</option>
          <option value="4" >是否到期=&gt;在用</option>
          <option value="5" >绑定特征</option>
          <option value="6" >添加时间</option>
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
  </span>
   <input name="daihao" type="hidden" id="daihao" value="{$app_daihao}">
     <input name="m" type="hidden" id="m" value="{$_GET['m']}">
  <input name="c" type="hidden" id="c" value="{$_GET['c']}">
  <input name="a" type="hidden" id="a" value="{$_GET['a']}">
   
   
</div>
</form>






<!--  内容列表   -->

<form action=""   class="layui-form" name="form"  lay-filter="baocun"   method="post"  >
<table class="layui-table"  style="overflow: scroll;word-break: keep-all;width:98%;margin-left: 10px;">

 <thead>
<tr align="center" bgcolor="" height="52">

                <th width="*" nowrap="nowrap" >&nbsp;选择</th>
                
                <th width="*" nowrap="nowrap" >所属用户名</th>
				<th width="*" nowrap="nowrap" >状态</th>
                <th width="*" nowrap="nowrap">账号多开</th>
                <th width="*" nowrap="nowrap">机器用量</th>
                
                
				<th width="*" nowrap="nowrap" >软件用户组</th>
                <th width="*" nowrap="nowrap" >剩余点数</th>
            
                <th width="*" nowrap="nowrap" >绑定特征</th>
				<th width="*" nowrap="nowrap" >标注(接口标)</th>
                <th width="*" nowrap="nowrap" >添加时间</th>
                <th width="*" nowrap="nowrap" >登录时间</th>
                <th width="*" nowrap="nowrap">编辑</th>
</tr></thead>
			  <!--
EOT;


            while ($array_value= @mysqli_fetch_array($db_array_value ))
            {
			
			//print_R($array_value);
                
        
			$vip_Day=NULL;
			
			
			$array_value[3]=show_Time_Day($array_value[3]);
            $array_value[8]=show_Time_Day($array_value[8]);
			
			$array_value['L_IsLock']==1?$array_value['L_IsLock']='冻结登录该软件':$array_value['L_IsLock']='正常';
			
			
			$user_class=$class_array[$array_value['L_class']];
			

			
		            if($array_value['L_links_open']==0)$array_value['L_links_open']='默认';
            if($array_value['L_links']==0)$array_value['L_links']='默认';
			
			
           print <<< EOT
-->





<tr align='center'   height="22" >
	            <td nowrap="nowrap">&nbsp;<input type="checkbox" id="id" name="id"  value="{$array_value[0]}"  lay-skin="primary"></td>
                
                <td nowrap="nowrap" ><a href="index.php?soso_id=1&DESC=0&soso_ok=%E6%90%9C%E7%B4%A2&m=user&c=admin_user&a=table&soso={$array_value['L_ic_pwd']}">{$array_value['L_ic_name']}</a></td>
				<td nowrap="nowrap" >{$array_value['L_IsLock']}</td>
                <td nowrap="nowrap" >{$array_value['L_links_open']}</td>
                <td nowrap="nowrap" >{$array_value['L_links']}</td>
				<td nowrap="nowrap" >{$user_class}</td>
                <td nowrap="nowrap" >{$array_value['L_vip_unix']}点</td>
                
                <td nowrap="nowrap" >{$array_value[6]}</td>
				<td nowrap="nowrap" >{$array_value['L_links_info']}</td>
                <td nowrap="nowrap" >{$array_value[3]}</td>
                <td nowrap="nowrap" >{$array_value[8]}</td>
               <td nowrap="nowrap" ><a href="index.php?m=applib&c=admin_user&a=modify&daihao={$app_daihao}&id={$array_value[0]}"   >编辑</a></td>
</tr>

<!--		  
EOT;
            }

            print <<< EOT
-->

<tr align='center' height="22" >
	<td colspan="13" bgcolor="#ECEFF4">
	<div class="anzuo">
	<!--通过接口跳转载php文档-->
	
	 <div class="layui-input-inline" style="width: 50px;">
	<input   lay-filter="allchoose" lay-skin="primary"  type="checkbox" >
	</div>
	
	 <div class="layui-input-inline" style="width: 150px;">
	<select name="select_class" id="select_class">
      <option selected>操作的项目..</option>
      <option value="2">批量操作</option>
      <option>-------------</option>
      <option value="1">删除选择</option>
    </select>
	
	
	
	
	</div><input name="all" type="hidden" id="all">
	      <div class="layui-input-inline" style="width: 100px;">
        <input name="baocun" type="submit" class="layui-btn layui-btn-normal" id="baocun"  value="提交">
      </div>
</div>
<!--page-->
<div id="pageDiv"></div>	</td>
	</tr>
</table>
</form>


<script>
layui.use(['form', 'layedit', 'laydate'], function(){
  var form = layui.form
  ,layer = layui.layer
  ,layedit = layui.layedit
  ,$=layui.jquery
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
  
  //全选/取消
  form.on('checkbox(allchoose)', function(data){
    
    var bsphpallid = $('input[name="id"]');
    bsphpallid.each(function(index,item){
        item.checked = data.elem.checked;
    });

    form.render('checkbox');
    
  });
  
  
  //得到选择ID
  form.on('submit(baocun)', function(data){
    var ids = '';
  
    var bsphpallid = $('input[name="id"]');
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
<!--
EOT;
?>
-->

<div id="foot">Copyright 2009-2018 <a href="http://www.bsphp.com" target="_blank">Bsphp验证系统 <?php echo BSPHP_VERSION; ?></a>  Bsphp.com <br>
All Rights Reserved </div>

</body>
</html>
