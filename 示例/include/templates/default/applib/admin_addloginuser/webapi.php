<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?> - by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css">
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/table.js"></script>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>BSPHP验证系统  by Bsphp</title>
<link rel="stylesheet" type="text/css" href="skin/style/base.css">

</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">WEB页面API</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>
<p>


<form action=""   class="layui-form"   name="form"  method="post"    onClick="return allcheckbox();"  >
<table class="layui-table"  style="width:98%;margin-left: 10px;"  lay-filter="demoEvent">

 <thead>
<tr align="center" bgcolor="" height="52">
                <th width="*" >名称</th>
                <th width="*" >URL</th>


</tr></thead>






<tr align='center'   height="22" >
           <td height="18" >登录模式用户管理</td>
           <td height="18" >http://<?php echo $_SERVER['HTTP_HOST']; ?>/index.php?m=applib&c=appweb&a=links_login&daihao=[软件代号:可空]&BSphpSeSsL=[验证串:可空]&user=[用户帐户]&pwd=[用户密码]&login=YES</td>

</tr>

<tr align='center'   height="22" >
           <td height="18" >卡模式用户在线管理</td>
           <td height="18" >http://<?php echo $_SERVER['HTTP_HOST']; ?>/index.php?m=applib&c=appweb&a=links_car&daihao=[软件代号]&BSphpSeSsL=[验证串:可空]&car_id=[卡用户帐户]&car_pwd=[用户密码:可空]&login=YES</td>

</tr>

<tr align='center'   height="22" >
           <td height="18" >添加网页接口充值</td>
           <td height="18" >http://<?php echo $_SERVER['HTTP_HOST']; ?>/index.php?m=applib&c=appweb&a=Recharge&user=[账号]&&ka=[卡号]&mi=[密码]</td>

</tr>

<tr align='center'   height="22" >
           <td height="18" >添加网页接口新闻/公告</td>
           <td height="18" >http://<?php echo $_SERVER['HTTP_HOST']; ?>/index.php?m=applib&c=appweb&a=new_list&open_new=[是否新窗口打开:_blank]</td>

</tr>
<tr align='center'   height="22" >
           <td height="18" >添加网页接口反馈</td>
           <td height="18" >http://<?php echo $_SERVER['HTTP_HOST']; ?>/index.php?m=applib&c=appweb&a=feedback&daihao=[软件代号]&uid=[用户UID:自定义]&table=[名称标题:自定义]&leix=[类型:自定义]</td>

</tr>
</table>
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





<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php  echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>

</body>
</html>
