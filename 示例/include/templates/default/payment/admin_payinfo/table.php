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
<div class="nametable"><div id="texttale"  class="texttale">平台购卡订单</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>
<!--  搜索  -->
<form name="formsoso"  class="layui-form"   method="get" action="">
<div class="soso">


  
   <div class="layui-form-item">
    <div class="layui-inline">
      <div class="layui-input-inline" style="width: 150px;">
      
		  <select name="soso_id" class="txt" id="soso_id">
            <option value="0">订单号</option>
            <option value="1">用户UID</option>
            <option value="2">充值状态(状态)</option>
            <option value="3">商品名称</option>
            <option value="4">所属软件</option>
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

<form action=""  name="form"  method="post"    class="layui-form"    lay-filter="baocun"   >
 <table class="layui-table"  style="overflow: scroll;word-break: keep-all;width:98%;margin-left: 10px;">
 <thead>
    <tr align="center" bgcolor="" height="54">
      <th width="*" >&nbsp;选择</th>
      <th width="*" >订单号</th>
      <th width="*" nowrap="nowrap" >时间</th>
      <th width="*" nowrap="nowrap" >UID</th>
      <th width="*" >商品名称</th>
      <th width="*" >所属软件</th>
      <th width="*" >数量(张)</th>
      <th width="*" >时间(天)</th>
      <th width="*" >单价(元)</th>
      <th width="*" >折</th>
      <th width="*" >优惠(元)</th>
      <th width="*" >总价(元)</th>
      <th width="*" >购买前金额</th>
      <th width="*" >购买后金额</th>
      <th width="*" >状态</th>
    </tr></thead>
    <!--
EOT;


             while ($value= mysqli_fetch_array($db_array_value))
            {
			if($value['pay_zhuangtai']==1){
			  $zhuangtai='未付款关闭(状态'.$value['pay_zhuangtai'].')';
			}elseif($value['pay_zhuangtai']==2){
			  $zhuangtai='未发卡(状态'.$value['pay_zhuangtai'].')';
			}else{
			  $zhuangtai='交易成功(状态'.$value['pay_zhuangtai'].')';
			}
			
			
			
		
                print <<< EOT
-->
    <tr align='center'   height="22" >
      <td>&nbsp;<input type="checkbox" id="id"  name="id"  value="{$value['ali_id']}" lay-skin="primary" >
          </td>
      <td>{$value[0]}</td>
      <td nowrap="nowrap">{$value[3]}</td>
      <td nowrap="nowrap">{$value[2]}</td>
      <td>{$value[10]}</td>
      <td>{$value[15]}</td>
      <td>{$value[6]}</td>
      <td>{$value[13]}</td>
      <td>{$value[11]}</td>
      <td width="20">{$value[12]} </td>
      <td>{$value[9]} </td>
      <td>{$value[8]} </td>
      <td>{$value[4]} </td>
      <td>{$value[5]} </td>
	  <td>{$zhuangtai} </td>
	  
	  
    </tr>
    <!--		  
EOT;
            }

            print <<< EOT
-->
    <tr align='center' height="22" >
      <td colspan="22" bgcolor="#ECEFF4"><div class="anzuo">
                <div class="layui-input-inline" style="width: 50px;">
      
	       <input type="checkbox" name="allchoose" lay-skin="primary" lay-filter="allchoose">
	 </div><div class="layui-input-inline" style="width: 150px;">
          <select name="select_class" id="select_class">
            <option>操作的项目..</option>
            <option value="1">删除选择订单</option>
          </select>
          </div><input name="all" type="hidden" id="all">
        	<div class="layui-input-inline" style="width: 100px;">
        <input name="baocun" type="submit" class="layui-btn layui-btn-normal" id="baocun"  value="提交">
      </div>

        </div>
          <!--page-->
          <div id="pageDiv"></div></td>
    </tr>
  </table>
</form>


<script language="javascript">
select_set('soso_id',{$soso_id});
select_set('DESC',{$DESC_id});
</script>

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
<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>





</body>
</html>
