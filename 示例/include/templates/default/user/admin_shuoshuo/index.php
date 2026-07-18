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


<style type="text/css">
<!--
.STYLE3 {font-size: 12px}
-->
</style>
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">用户说说</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>


<!--  内容列表   -->
<form action=""  name="form"  method="post"      >
 <table class="layui-table"  style="width:95%;margin-left: 10px;" lay-filter="demoEvent">
    <tr>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td><label>删除
          <input name="date"  class="layui-form-label" type="text" id="date" style="border:1px solid #666" onFocus="if(this.value=='0000-00-00') this.value=''" onClick="showcalendar(event, this);" size="28"/>
      </label>
日前发表
<input name="Submit_dele" type="submit"  class="layui-btn layui-btn-normal"  id="Submit_dele" value="提交" /></td>
    </tr>
    <tr>
      <td><label></label>
        <p>时间格式:2013-06-10 21:40:45
        <p>
          <label></label>
        <hr></td>
    </tr>
    <tr>
      <th>用户设置</th>
    </tr>
    <tr>
      <td><label>
      <input name="checkbox" type="checkbox" value="1"  <?php if( $arr['wei_ini_off']==1 ){echo 'checked="checked"';}?>)   />
      <span class="STYLE1">关闭留言</span></label></td>
    </tr>
    <tr>
      <td>屏蔽发表字符 如: <span class="STYLE3">屏蔽字符|屏蔽字符|屏蔽字符|屏蔽字符</span></td>
    </tr>
    <tr>
      <td><textarea name="textarea" cols="50" rows="5"><?php echo $arr['wei_ini_jin']; ?></textarea></td>
    </tr>
    <tr>
      <td><input name="Submit_bao" type="submit"   class="layui-btn layui-btn-normal"   id="Submit_bao" value="保存" /></td>
    </tr>
  </table>
  <p>&nbsp;</p>
</form>




<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>

<script> 
var ie =navigator.appName=="Microsoft Internet Explorer"?true:false;
function $(objID){
return document.getElementById(objID);
}
</script>
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/date.js"></script>

</body>
</html>