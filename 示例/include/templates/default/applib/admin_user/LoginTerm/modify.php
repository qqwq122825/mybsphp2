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
</head>
<body leftmargin='8' topmargin='8'><!--  标题  -->

<div class="nametable"><div id="texttale"  class="texttale">3编辑软件用户信息</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>
<!--  快捷  -->
<div id="Shortcut">
  <ul>
	      <li><a href="index.php?m=applib&c=admin_user&a=table&daihao={$app_daihao}">  返回</a></li>



  </ul>
</div>
<form action=""   class="layui-form"   name="form"  method="post"    onClick="return allcheckbox();"  >
<table class="layui-table"  style="width:98%;margin-left: 10px;"  lay-filter="demoEvent">

    <tr bgcolor="#FFFFFF"> 
      <td width="24%" height="20" align="right" nowrap>UID：</td>
      <td width="76%" height="20" nowrap>{$array[1]}
        <input name="daihao" type="hidden" id="daihao" value="{$daihao}">
      <input name="tid" type="hidden" id="tid" value="{$array[0]}"></td>
    </tr>
    <tr bgcolor="#FFFFFF">
      <td align="right" nowrap>到期时间：</td>
      <td nowrap><input  class="layui-form-label" name="date" type="text" id="date" value="{$array['L_vip_unix']}"  onFocus="showcalendar(event, this);if(this.value=='0000-00-00')this.value=''" onClick="showcalendar(event, this);"  size="32">
        * 到期时间 时间格式:2009-06-30 11:37:13<span class="at"></span></td>
    </tr>

    <tr bgcolor="#FFFFFF">
      <td align="right" nowrap>绑定特征：</td>
      <td nowrap ><input class="layui-form-label"  name="key" type="text" id="key" value="{$array[6]}" size="32">
        * <span >绑定特征 用自己定义绑定特征</span></td>


    </tr>
        <tr bgcolor="#FFFFFF">
      <td align="right" nowrap>账号同时在线数：</td>
      <td nowrap ><input class="layui-form-label"  name="L_links_open" type="text" id="L_links_open" value="{$array['L_links_open']}" size="20">
        * <span >0=使用系统默认(软件配置>限开设置) -1当前账号关闭功能</span></td>


    </tr>
    <tr bgcolor="#FFFFFF">
      <td align="right" nowrap>机器同时使用量：</td>
      <td nowrap ><input class="layui-form-label"  name="L_links" type="text" id="L_links" value="{$array['L_links']}" size="20">
        * <span >0=使用系统默认(软件配置>限开设置) -1当前账号关闭功能</span></td>


    </tr>
        <tr bgcolor="#FFFFFF">
      <td align="right" nowrap>备注：</td>
      <td nowrap ><input class="layui-form-label"  name="L_beizhu" type="text" id="L_beizhu" value="{$array['L_beizhu']}" size="32">
        * <span >备注后台可见</span></td>
    </tr>  
    <tr bgcolor="#FFFFFF">
      <td align="right" nowrap>用户分组：</td>
      <td nowrap>
	  
	  <div class="layui-input-inline" style="width: 150px;">
        <select name="class_id">
          <option value="0">不分组</option>
          <!--
EOT;
    /*******查询卡类列表*****/
    $sql="SELECT*FROM`bs_php_userclass`WHERE`class_daihao`='$app_daihao'";
    $db_array_value=$this->db->Bsphp_my_query($sql);
	
	            
             
             while ($array_value = mysqli_fetch_array($db_array_value))
            {
			
		
			
			$array['L_class']==$array_value[0]?$selected='selected':$selected='null';
			
			
                print <<< EOT
-->
          <option value="{$array_value[0]}" {$selected}>{$array_value[1]}</option>
          <!--                  
EOT;
}
print <<< EOT
-->
        </select>
	  
	  </div>
	  
	  </td>
    </tr>
	    <tr bgcolor="#FFFFFF">
      <td align="right" nowrap>&nbsp;</td>
      <td nowrap>{$atname}</td>
    </tr>
    <tr> 
      <td height="20" colspan="2" align="center">
      	<div align="center">
      	  <input name="leiadd" type="submit"   class="layui-btn layui-btn-normal"  id="leiadd"  style="width:100px" value="保存">
      </div></td>
    </tr>
 
<script language="javascript">
function insertLabel(strLabel){
	stafrm.zzz.focus();
	var str = document.selection.createRange();
	str.text = strLabel
}
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
<div id="foot">Copyright 2009-2018 <a href="http://www.bsphp.com" target="_blank">Bsphp验证系统 <?php echo BSPHP_VERSION; ?></a>  Bsphp.com <br>
All Rights Reserved </div>

</body>
</html>