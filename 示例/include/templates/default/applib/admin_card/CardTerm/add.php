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
<body leftmargin='8' topmargin='8'><!--  标题  -->
<div class="nametable">
  <div id="texttale"  class="texttale">添加充值卡类型</DIV>
  [<a href="javascript:window.location.reload();">+</a>] </div>
  
<!--  快捷  -->
<div id="Shortcut">
  <ul>
    <li><a href="index.php?m=applib&c=admin_card&a=class&daihao={$app_daihao}">返回上层</a></li>
  </ul>
</div>  <form name="form1" action="" class="layui-form"  method="POST" >
<table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">



    <tr > 
      <td width="20%" height="20" align="right" nowrap="nowrap">充值卡软件：</td>
      <td width="80%" height="20">$app_name (代号:$app_daihao) </td>
    </tr>
    <tr >
      <td align="right" nowrap="nowrap">充值卡别名：</td>
      <td><div class="layui-input-inline" style="width: 300px;"><input name="ka_name" type="text"  class="layui-input"id="ka_name" value="{$ka_name}" size="25"> </div>
        <span class="STYLE8">*</span>充值卡名称 如:贵宾卡 、年卡 </td>
    </tr>
    <tr >
      <td align="right" nowrap="nowrap">开头字符：</td>
      <td><div class="layui-input-inline" style="width: 300px;"><input name="ka_ktzf" type="text"  class="layui-input"id="ka_ktzf" value="{$ka_ktzf}" size="10"> </div>
      充值卡制作卡开头字符 如果:Z、j、GG 10位字符内</td>
    </tr>
	    <tr >
      <td align="right" nowrap="nowrap">充值卡期限：</td>
      <td><div class="layui-input-inline" style="width: 300px;"><input name="ka_date" type="text"  class="layui-input"id="ka_date" value="{$ka_date}" size="10"></div>
        
        <div class="layui-input-inline" style="width: 80px;"><select name="lei_type" id="lei_type">
          <option value="0">天</option>
          <option value="1">月</option>
          <option value="2">年</option>
          <option value="3">时</option>
          <option value="4">分</option>
          <option value="5">秒</option>
        </select></div>
        1秒=1点;1分钟=60点</td>
    </tr>
    <tr >
      <td align="right" nowrap="nowrap">价格：</td>
      <td><div class="layui-input-inline" style="width: 300px;"><input name="ka_jiage" type="text"  class="layui-input"id="ka_jiage" value="{$ka_jiage}" size="10"> </div>充值预定销售价格 设置-1 前台充值卡隐藏,禁止购买</td>
    </tr>
	
	
	    <tr >
      <td align="right" nowrap="nowrap">代理价格：</td>
      <td><div class="layui-input-inline" style="width: 300px;"><input name="ka_dai" type="text"  class="layui-input"id="ka_dai" value="{$ka_dai}" size="10"></div>代理商进货价格 设置-1 代理充值卡隐藏,禁止购买</td>
    </tr>
	    <tr >
      <td align="right" nowrap="nowrap">用户分组：</td>
      <td><div class="layui-input-inline" style="width: 300px;"><select name="car_class" id="car_class">
        <option value="0">未分组</option>
        <!--
EOT;

	
	            
             
             while ($array_value = mysqli_fetch_array($db_array_value))
            {
			
		
			
			$array['ic_class']==$array_value[0]?$selected='selected':$selected='null';
			
			
print <<< EOT

-->
        <option value="{$array_value[0]}" {$selected}>{$array_value[1]}</option>
        <!--                  
EOT;
}
print <<< EOT
-->
      </select> </div>
        充值后转到指定分组,相同分组不做修改</td>
    </tr>
	
	    <tr >
      <td align="right" nowrap="nowrap">卡卡号长度：</td>
      <td><div class="layui-input-inline" style="width: 300px;"><input name="lei_cardint" type="text"  class="layui-input" id="lei_cardint" value="{$lei_cardint}"  size="25"/>
充值卡卡串长度设置一般建议35位</td>
	    </tr>
		
	    <tr >
      <td align="right" nowrap="nowrap">卡号字符类型：</td>
      <td><div class="layui-input-inline" style="width: 300px;"><select name="lei_cardset"  id="lei_cardset">
        <option value="0"     > 默认数字+大写+小写组合</option>
        <option value="1"     > 数字加大写字母</option>
        <option value="2"     > 数字加小写字母</option>
        <option value="3"     > 大小写字母</option>
        <option value="4"     > 全部大写字母</option>
        <option value="5"     > 全部小写字母</option>
        <option value="6"     > 全部数字</option>
      </select>
        <span class="STYLE4">充值卡组成 建议默认 </span></td>
	    </tr>
		
	    <tr >
      <td align="right" nowrap="nowrap">密码长度：</td>
      <td><div class="layui-input-inline" style="width: 300px;"><input name="lei_pwdint" type="text"  class="layui-input" id="lei_pwdint" value="{$lei_pwdint}"  size="25"/>
      可设置0(无需密码)</td>
	    </tr>
	
	    <tr >
      <td align="right" nowrap="nowrap">密码字符类型：</td>
      <td><div class="layui-input-inline" style="width: 300px;"><select name="lei_pwdset"  id="lei_pwdset">
        <option value="0"     > 默认数字+大写+小写组合</option>
        <option value="1"     > 数字加大写字母</option>
        <option value="2"     > 数字加小写字母</option>
        <option value="3"     > 大小写字母</option>
        <option value="4"     > 全部大写字母</option>
        <option value="5"     > 全部小写字母</option>
        <option value="6"     > 全部数字</option>
      </select>
        <span class="STYLE4">充值卡组成 建议默认 </span></td>
	    </tr>
        
        
        	
	    <tr >
      <td align="right" nowrap="nowrap">账号同时在线数：</td>
      <td><div class="layui-input-inline" style="width: 300px;"><input placeholder="0=使用系统默认(软件配置>限开设置) -1关闭功能"  name="lei_links_open" type="text"  class="layui-input"id="lei_links_open" value="{$lei_links_open}" size="10">
      </td>
    </tr>
	    <tr >
        
	
	    <tr >
      <td align="right" nowrap="nowrap">机器同时使用量：</td>
      <td><div class="layui-input-inline" style="width: 300px;"><input  placeholder="0=使用系统默认(软件配置>限开设置) -1关闭功能"  name="lei_links" type="text"  class="layui-input"id="lei_links" value="{$lei_links}" size="10">
        </td>
    </tr>
	    <tr >
    <tr> 
      <td height="20" colspan="2" align="center">
      	<div align="center">
      	  <div class="layui-input-inline" style="width: 300px;"><input name="leiadd" type="submit"  class="layui-btn layui-btn-normal"  id="leiadd" style="width:100px" value="添加类型"> 
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
