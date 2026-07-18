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
<title>充值卡类型添加</title>
<link rel="stylesheet" type="text/css" href="skin/style/base.css">
<script src="skin/js/table.js"></script>
</head>
<body leftmargin='8' topmargin='8'>
<!--  标题  -->
<div class="nametable">
  <div id="texttale"  class="texttale">添加充值卡类型</DIV>
  [<a href="javascript:window.location.reload();">+</a>] </div>
  
<!--  快捷  -->
<div id="Shortcut">
  <ul>
    <li><a href="index.php?m=applib&c=admin_card&a=table&daihao={$app_daihao}">返回上层</a></li>
  </ul>
</div><form name="form1" action="" class="layui-form"  method="POST" >
<table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">

  

    <tr > 
      <td width="20%" height="20" align="right">卡串：</td>
      <td width="80%" height="20">{$value['car_name']}</td>
    </tr>
    <tr >
      <td align="right">密码：</td>
      <td>{$value['car_pwd']}</td>
    </tr>
    <tr >
      <td align="right">状态：</td>
      <td>{$zhuangtai}</td>
    </tr>
	    <tr >
      <td align="right">充值限制：</td>
      <td>{$this->card_oid_array_name[$value['lei_for_id']]}</td>
    </tr>
    <tr >
      <td align="right">转直分组：</td>
      <td>{$class_array[$value['car_class']]}</td>
    </tr>
	
	
	    <tr >
      <td align="right">账号多开：</td>
      <td>{$value['car_links_open']}</td>
    </tr>
	
	    <tr >
      <td align="right">机器用量：</td>
      <td>{$value['car_links']}</td>
    </tr>
	
	    <tr >
      <td align="right">是否重计时：</td>
      <td>{$value['car_weight']}</td>
    </tr>
	
	    <tr >
      <td align="right">充值时间：</td>
      <td>{$value[16]}</td>
    </tr>
	    <tr >
      <td align="right">充值账户：</td>
      <td>{$value['car_cong_user']}</td>
    </tr>
	
	    <tr >
      <td align="right">销售价格：</td>
      <td>{$value[11]}</td>
	    </tr>
		
	    <tr >
      <td align="right">代理价格：</td>
      <td>{$value[12]}</td>
	    </tr>
		
	    <tr >
      <td align="right">管理员备注：</td>
      <td><span class="layui-input-inline" style="width: 300px;">
        <input name="car_admin_beizhu" type="text"  class="layui-input" id="car_admin_beizhu" value="{$value['car_admin_beizhu']}"  size="25"/>
      </span></td>
	    </tr>
	
	    <tr >
      <td align="right">代理备注：</td>
      <td><span class="layui-input-inline" style="width: 300px;">
        <input name="car_agnet_beizhu" type="text"  class="layui-input" id="car_agnet_beizhu" value="{$value['car_agnet_beizhu']}"  size="25"/>
        <input name="carid" type="hidden" id="carid" value="{$value['car_id']}">
      </span></td>
	    </tr>
    <tr> 
      <td height="20" colspan="2" align="center">
      	<div align="center">
      	 <input name="leiadd" type="submit"  class="layui-btn layui-btn-normal"  id="leiadd" style="width:100px" value="保存"> 
   	    <span class="STYLE8"></span> </div></td>
    </tr>
 
<script language="javascript">
function insertLabel(strLabel){
	stafrm.zzz.focus();
	var str = document.selection.createRange();
	str.text = strLabel
}
</script>

<script language="javascript">
select_set('lei_type',{$lei_type});

</script>
<!--
EOT;
?>
-->

  
</table> </form>
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
