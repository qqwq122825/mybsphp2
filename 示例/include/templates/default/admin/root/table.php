<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?> - by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css?11">
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
<div class="nametable"><div id="texttale"  class="texttale">系统管理员</div>[<a href="javascript:window.location.reload();">+</a>]</div>
<!--  快捷  -->
<div id="Shortcut">


  <ul>
    <li><a href="javascript:" onClick="top.showiframe('no','添加管理账号','index.php?m=admin&c=root&a=add',600,530);">添加管理员</a></li>
   
  </ul>
</div>
<!--  搜索  -->
<form name="formsoso"  class="layui-form"  method="get" action="">
<div class="soso" >


	
   <div class="layui-form-item">
    <div class="layui-inline">
      <div class="layui-input-inline" style="width: 100px;">
      
	      <select name='soso_id' id="soso_id"  class="t_aera" style='width:120'>
        <option value="1" selected >管理员账号</option>
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
    
	   



      

  </label>
   <span class="sosodiv">
  <input name="m" type="hidden" id="m" value="{$_GET['m']}">
  <input name="c" type="hidden" id="c" value="{$_GET['c']}">
  <input name="a" type="hidden" id="a" value="{$_GET['a']}">
  </span></div>
</form>






<!--  内容列表   -->

<form action=""   class="layui-form" name="form"  lay-filter="baocun"   method="post"  >
<table class="layui-table"  style="overflow: scroll;word-break: keep-all;width:98%;margin-left: 10px;">
 <thead>
<tr align="center" bgcolor="">
	<th width="56" >&nbsp;选择</th>
	<th width="110" >管理员账号
	<th width="82" >账号状态
	  </td>
	<th width="84" >登录次数
	  </td>	  
	<th width="153" >最后活动时间
	  </td>
	<th width="133" nowrap="nowrap" >最后登录IP
	  </td>
	<th width="351" nowrap="nowrap" >权限</th>

	<th width="58" >操作</th>
</tr>
 </thead>
			  <!--
EOT;

             while ($value= mysqli_fetch_assoc($db_array_value))
            {
               // print_R($value);
			$value['Admin_IsLock']=$value['Admin_IsLock'];
			$value['Admin_IsLock']==1?$value['Admin_IsLock']='冻结':$value['Admin_IsLock']='正常';
			$value['Admin_AdminUserName']==$this->admin_global_array['Admin_AdminUserName']?$checkboxname='name="checkboxname"  onMouseDown="this.disabled=true"':$checkboxname='id="id"';
            $value['Admin_Permission']=$this->IsLockfunction($value['Admin_Permission']);
			(int)$value['Admin_LoGDaTe']>0?$value['Admin_LoGDaTe']=show_Time_Day($value['Admin_LoGDaTe']):$value['Admin_LoGDaTe']='从未登录';
			$value['Admin_LoGinIP']=$this->ip->show(getip($value['Admin_LoGinIP']));
		    
print <<< EOT
-->
<tr align='center'  onMouseMove="javascript:this.bgColor='#ECEFF4';" onMouseOut="javascript:this.bgColor='#FFFFFF';" >
	<td>&nbsp;<input type="checkbox" {$checkboxname}  name="id"  id="id"   value="{$value['Admin_ID']}" lay-skin="primary" ></td>
	<td>{$value['Admin_AdminUserName']}</td>
	<td align="left"><span class="STYLE2 STYLE1">{$value['Admin_IsLock']}</span></td>
	<td align="left">{$value['Admin_LoGinNum']}</td>
	<td>{$value['Admin_LoGDaTe']}</td>
	<td nowrap="nowrap">{$value['Admin_LoGinIP']}</td>
	<td nowrap="nowrap">操作权限:{$value['Admin_Permission']['G']}</td>

	<td><a href="javascript:" onClick="top.showiframe('no','管理账号编辑','index.php?m=admin&c=root&a=usb&id={$value['Admin_ID']}',600,530);"   >编辑</a> </td>
</tr>

<!--		  
EOT;
            }

            print <<< EOT
-->

<tr align='center' >
	<td colspan="10" bgcolor="#ECEFF4">
	<div class="anzuo">


	
	


	

	
	  
      <div class="layui-input-inline" style="width: 50px;">
      
	      <input  type="checkbox" lay-filter="allchoose" lay-skin="primary" >
	 </div><div class="layui-input-inline" style="width: 150px;">
	

     
	 
      
	      	<select name="select_class"  id="select_class">
      <option>批量操作的项目..</option>
      <option value="2">解封选择账号</option>
      <option value="3">冻结选择账号</option>
      <option>---------</option>
      <option value="1">删除选择账号</option>
    </select>
	   </div>
	  
      <div class="layui-input-inline" style="width: 100px;">
        <input name="baocun" type="submit" class="layui-btn layui-btn-normal" id="baocun"  value="提交">
      </div>

    






	

  
	</div><input name="all" type="hidden" id="all">
	
</div>
<!--page-->
<div id="pageDiv"></div>	</td>
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
<script language="javascript">
select_set('soso_id',0);
select_set('DESC',0);
</script>

<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a> Bsphp.com <br>
  All Rights Reserved </div>

</body>
</html>

