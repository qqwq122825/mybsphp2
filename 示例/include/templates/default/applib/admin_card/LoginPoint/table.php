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
 $psass_txt='';
if($mosho_ziduan_s=='login'){
 $psass_txt='<th width="*">密码</th>';

}

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
<div class="nametable"><div id="texttale"  class="texttale">卡串列表</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>
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

<form action=""   class="layui-form" name="form"  lay-filter="baocun"   method="post"  >
<table class="layui-table"  style="overflow: scroll;word-break: keep-all;width:98%;margin-left: 10px;">

 <thead>
<tr align="center" bgcolor="" height="52">
                <th width="*" >选择</th>
                <th width="*" >卡号</th>
				<th width="*" >密码</th>
                <th width="*">状态</th>
                <th width="*">剩充次数</th>
				<th width="*">充值限制</th>
                <th width="*">转至分组</th>
				<th width="*">账号多开</th>
                <th width="*">机器用量</th>
				<th width="*">余额充值</th>
				<th width="*">是否重计时</th>
                <th width="*" >使用状态</th>
                <th width="*" >充值点数</th>
                <th width="*" >销售价格</th>
                <th width="*" >代理价格</th>
                <th width="*" >制卡管理/UID</th>
                <th width="*">制作时间</th>
                <th width="*" >充值时间</th>
                <th width="*">充值用户</th>
                <th width="*"></th>
                <th width="*"></th>
                <th width="*">标记</th>
</tr></thead>
			  <!--
EOT;


        while ($value= mysqli_fetch_array($db_array_value ))
            {
			$value['car_IsLock']==1?$test='<span  style="color: #FF9900">已使用</span>':$test='未使用';
			$value['car_zhuangtai']==1?$zhuangtai='<span  style="color: #FF9900">冻结</span>':$zhuangtai='正常';

			if($value[16]=='0000-00-00 00:00:00')$value[16]='';
		
			
			(int)$value[15]!=0?$value[15]='(uid:'.$value[15].')':$value[15]='';
			
			

			$value['car_BaoJi']==1?$url_img='nem':$url_img='nop';
			
			$value[5]=show_Time_Day($value[5]);
			
            if($value['car_pwd']=='')$value['car_pwd']='无';
			
			$value['car_weight']==1?$value['car_weight']='是':$value['car_weight']='否';
			
						
			if($value['car_links_open']==0)$value['car_links_open']='默认';
            if($value['car_links']==0)$value['car_links']='默认';
			
			$urls=get_url_statics();
		
                print <<< EOT
-->





<tr align='center'   height="22" >
	<td>&nbsp;<input type="checkbox" id="id" name="id" value="{$value[0]}" lay-skin="primary"> </td>
           <td height="18" >{$value['car_name']}</td>
		   <td height="18" >{$value['car_pwd']}&nbsp;</td>
                <td>{$zhuangtai}</td>
                <td>{$value['car_for']}次</td>
				<td>{$this->card_oid_array_name[$value['lei_for_id']]}</td>
                <td>{$class_array[$value['car_class']]}</td>
                				<td>{$value['car_links_open']}</td>
				<td>{$value['car_links']}</td>
				<td>{$value['car_money']}元</td>
				<td>{$value['car_weight']}</td>
                <td>{$test}</td>
                <td>{$value[6]}点</td>
                <td>{$value[11]}</td>
                <td>{$value[12]}</td>
                <td>{$value[8]}</td>
                <td>{$value[5]}&nbsp;</td>
                <td>{$value[16]}&nbsp;</td>
                <td>{$value[17]}{$value[15]}&nbsp;</td>
                <td></td>
                <td></td>
                <td><a href="index.php?m=applib&c=admin_card&a=table&daihao={$daihao}&biaojiid={$value[0]}&biaoji={$url_img}"><img src="{$urls}images/{$url_img}.jpg" width="20" height="20" border="0" /></a></td>
</tr>

<!--		  
EOT;
            }

            print <<< EOT
-->

<tr align='center' height="22" >
	<td colspan="22" bgcolor="#ECEFF4">
	<div class="anzuo">


	
	      <div class="layui-input-inline" style="width: 50px;">
      
	      <input   type="checkbox"   lay-filter="allchoose" lay-skin="primary" >
	 </div><div class="layui-input-inline" style="width: 150px;">
	<select name="select_class" id="select_class">
      <option>操作的项目..</option>
      <option value="4">设已使用</option>
      <option value="5">设未使用</option>
      <option value="2">标记</option>
      <option value="3">取消标记</option>
      <option value="6">冻结</option>
      <option value="7">解冻</option>
      <option value="8">批量处理</option>
      <option>---------</option>
      <option value="1">删除选择卡串</option>
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
</script><script language="javascript">
select_set('soso_id',{$soso_id_show});
select_set('DESC',{$DESC_id});
</script>

<!--
EOT;
?>
-->


<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>

</body>
</html>
