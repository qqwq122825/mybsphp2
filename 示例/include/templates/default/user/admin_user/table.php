<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?>- by BSPHP</title>
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
<div class="nametable"><div id="texttale"  class="texttale">用户列表</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>
<!--  快捷  -->
<div id="Shortcut">
  <ul>
    <li><a href="javascript:" onClick="top.showiframe('no','添加用户','index.php?m=user&c=admin_user&a=add',600,280);">添加用户</a></li>
   
  </ul>
</div>
<!--  搜索  -->
<form name="formsoso"  class="layui-form"  method="get" action="">
<div class="soso">

   <div class="layui-form-item">
    <div class="layui-inline">
      <div class="layui-input-inline" style="width: 150px;">
      
	       <select name='soso_id' id="soso_id"  class="t_aera" style='width:120'>
            <option value="1" >登陆账号</option>
            <option value="2" >用户UID</option>
            <option value="3" >代理商列表</option>
            <option value="4" >用户电话</option>
            <option value="5" >折扣</option>
            <option value="6" >邀请人</option>
            <option value="7">用户状态=&gt;冻结</option>
            <option value="8">用户状态=&gt;正常</option>
            <option value="9" >用户QQ</option>
            <option value="10">用户邮箱</option>
            <option value="11" >用户密保问题</option>
            <option value="12" >用户密保答案</option>
            <option value="13" >用户登录IP</option>
            <option value="15" >金额大于N用户</option>
            <option value="16" >登录次数大于N用户</option>
            <option value="17" >用户积分大于</option>
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
<form action=""   class="layui-form" name="form"  lay-filter="baocun"   method="post"  >


<table class="layui-table"  style="overflow: scroll;word-break: keep-all;width:98%;margin-left: 10px;">
 <thead>
<tr align="center" bgcolor="">
	<th width="50" nowrap >&nbsp;选择</th>
	<th width="68" nowrap >UID</td>
	<th width="68" nowrap >用户名</td>
	<th width="68"  nowrap="nowrap" >是否代理</td>	  
	<th width="68" nowrap >状态</td>
	<th width="68" nowrap >登录次数</td>
	<th width="68" nowrap >RMB</th>
	<th width="68" nowrap >最后活动</th>
	<th width="68" nowrap >注册时间</th>
    <th width="68" nowrap >邀请人/上级</th>
     <th width="68" nowrap >折</th>
	<th width="68" nowrap >操作</th>
</tr> </thead>
			  <!--
EOT;
$array_s=array('0','一','二','三','四');

            while ($value= mysqli_fetch_array($db_array_value ))
            {
			$zhuangtai=$value['user_IsLock'];
            if($zhuangtai==1){
                $test='冻结';
            }elseif($zhuangtai==-2){
                $test='等待管理审核';
            }elseif($zhuangtai==-3){
                $test='等待邮箱验证';
            }else{
                $test='正常';
            }
            
            
            
		
			
	
			
			
			
			
			
			$zaix=show_Time_Day(date('Y-m-d H:i:s', $value['user_CaoShi']));
			if($value['user_CaoShi']==0)$zaix='没记录';
			$value[13]=show_Time_Day($value[13]);
			
			if($value[17]==0)  $value[17]='';
			if($value[18]==0)  $value[18]='';
			
			$intdate=$value['user_DenJi'];
			$intdate=$this->user->GetDenJiArray($intdate,'name');

			

			 if($value[23]==0){
			    $value[23]='普通用户';
			 }else{
			    
                
                $value[23] =$array_s[$value[23]].'级代理商';
			 }
			
			
			
		
                print <<< EOT
-->





<tr align='center'   >
	<td>&nbsp;<input type="checkbox" id="id"  name="id"  value="{$value[0]}" lay-skin="primary" ></td>
	<td>{$value[0]}</td>
	<td align="left">{$value[1]}</td>
	<td align="left">{$value[23]}</td>
	<td>{$test}</td>
	<td>{$value[20]}</td>
	<td>{$value[10]}</td>
	<td>{$zaix}</td>
	<td>{$value[13]}</td>
	<td> {$value['user_yao_User']}  </td>
    <td>{$value['user_Zhe']}</td>
	<td><a href="index.php?m=user&c=admin_user&a=modify&id={$value[0]}"  >编辑</a> </td>
</tr>

<!--		  
EOT;
            }
            

            print <<< EOT
-->

<tr align='center' height="23" >
	<td colspan="12" bgcolor="#ECEFF4">
	<div class="anzuo">


	
	      <div class="layui-input-inline" style="width: 50px;">
      
	       <input type="checkbox" name="allchoose" lay-skin="primary" lay-filter="allchoose">
	 </div><div class="layui-input-inline" style="width: 150px;">
<select name="select_class"   class="t_aera"  id="select_class">

      <option>操作的项目..</option>
      <option value="2">解封选择账号</option>
      <option value="3">冻结选择账号</option>
      <option value="4">设为代理商</option>
      <option value="5">取消代理商</option>
      <option>---------</option>
      <option value="1">删除选择账号</option>
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

<script language="javascript">
select_set('soso_id',{$soso_id_show});
select_set('DESC',{$DESC_id});
</script>


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


<div id="foot">Copyright 2009-2015 <a href="http://www.bsphp.com" target="_blank">Bsphp验证系统 <?php echo BSPHP_VERSION; ?></a>  Bsphp.com <br>
All Rights Reserved </div>

</body>
</html>