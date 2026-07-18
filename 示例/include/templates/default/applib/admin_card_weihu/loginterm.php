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
<div class="nametable"><div id="texttale"  class="texttale">登录限时模式维护</DIV>[<a href="javascript:window.location.reload();">+</a>]</div>



<!--  内容列表   -->
<form action=""  class="layui-form"   name="form"  method="post">
  <table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">
    <tr>
      <td>1.维护方式-&gt;<span class="STYLE3">1.通 过 ID：
          <label>
          <input name="lei_x" type="radio" onClick="SETradio();" value="1" <?PHP if($allid!=NULL) ECHO 'checked' ?> >
          </label>
1.通过ID</span></td>
      <td><label></label></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td width="*"><div align="right"><span class="STYLE3">索引ID:</span></div></td>
      <td width="*"><span class="STYLE3">
	  <div class="layui-input-inline" style="width: 300px;">
        <input name="text_id" type="text" class="layui-input" placeholder="1,2,3,4,5"  id="text_id" value="<?php echo $allid ?>">
		</div>
      </span></td>
      <td width="*">如:45,454,56,12313,1451</td>
    </tr>
    <tr>
      <td>1.维护方式-&gt;<span class="STYLE3">2.通过节选：
          <div class="layui-input-inline" style="width: 300px;"> <input name="lei_x"  type="radio"  onClick="SETradio();"  value="2" <?PHP if($allid==NULL) ECHO 'checked' ?>>
2.通过节选</div></span></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td><div align="right">选择软件:</div></td>
      <td>
	   <div class="layui-input-inline" style="width: 300px;">
	  <select name="appall" id="appall" >
        <option value="all">模式全部软件</option>
        <?php


     while ($value= mysqli_fetch_array($tmp))
            {
               ECHO '<option value="'.$value[0].'" >'.$value[1].'('.$value[0].')'.'</option>';

            }

  






?>
            </select></div></td>
      <td>选择维护软件</td>
    </tr>
    <tr>
      <td><div align="right"><span class="STYLE3">维护结果</span>:</div></td>
      <td><div class="layui-input-inline" style="width: 300px;">
          <select name="leiall"   class="select"  onclick="SETradio();"  id="leiall">
            <option value="1">全部用户</option>
            <option value="2">没到期用</option>
            <option value="3">已经到期</option>
            <option value="4">X天前到期用户*</option>
            <option value="5">用户UID*</option>
            <option value="6">绑定特征*</option>
            <option value="7">到期时间*</option>
            <option value="8">添加时间*</option>
          </select>
      </div>
          <span class="STYLE3">
          <div class="layui-input-inline" style="width: 300px;"></div>
          </span></td>
      <td>维护类型选择</td>
    </tr>
    <tr>
      <td><div align="right"><span class="STYLE3">逻辑选择</span>:</div></td>
      <td>
	   <div class="layui-input-inline" style="width: 300px;">
	     <select name="select">
          <option value="0">等于内容=</option>
          <option value="1">大于内容&gt;</option>
          <option value="2">小于内容&lt;</option>
          <option value="3">不等于内容!=</option>
        </select></div>
        <span class="STYLE3">
        <div class="layui-input-inline" style="width: 300px;"> <input name="leiall_test" class="layui-input"  type="text" id="leiall_test" value="Null" size="15"></div>
        </span></td>
      <td>有*号需要选择</td>
    </tr>
    <tr>
      <td height="21"><div align="left">2.设置值选择：</div></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
 <tr>
                            <td  >&nbsp;</td>
                            <td  ><div align="left">2.操作功能</div></td>
                            <td >&nbsp;</td>
                          </tr>
                          <tr>
                            <td nowrap><div align="right">
                              <input type="radio" name="radiobutton" value="1">
                            </div></td>
                            <td nowrap>添加时间
                              <div class="layui-input-inline" style="width: 300px;"> <input name="day1"  placeholder="1"  class="layui-input"  type="text" id="day1" size="10"></div>
                              秒/点</td>
                            <td nowrap>如:30 </td>
                          </tr>
                          <tr>
                            <td nowrap><div align="right">
                              <input type="radio" name="radiobutton" value="2">
                            </div></td>
                            <td nowrap>减少时间
                               <div class="layui-input-inline" style="width: 300px;"><input name="day2" placeholder="1"  class="layui-input"  type="text" id="day2" size="10"></DIV>
                              秒/点</td>
                            <td nowrap>如:2</td>
                          </tr>
                          <tr>
                            <td><div align="right">
                                <input type="radio" name="radiobutton" value="7">
                            </div></td>
                            <td nowrap>冻结账号</td>
                            <td nowrap>冻结指定软件登录</td>
                          </tr>
                          <tr>
                            <td><div align="right">
                                <input type="radio" name="radiobutton" value="8">
                            </div></td>
                            <td nowrap>解除冻结</td>
                            <td nowrap>解除指定软件登录</td>
                          </tr>
                          <tr>
                            <td><div align="right">
                              <input type="radio" name="radiobutton" value="4">
                            </div></td>
                            <td nowrap>到期时间=
                              <div class="layui-input-inline" style="width: 300px;"> <input name="date_txt" class="layui-input" placeholder="1516348596"   type="text" id="date_txt" size="15"></div></td>
                            <td nowrap>扣点就是点,登录模式是时间戳</td>
                          </tr>
                          <tr>
                            <td><div align="right">
                              <input type="radio" name="radiobutton" value="5">
                            </div></td>
                            <td nowrap>绑定特征=
                              <div class="layui-input-inline" style="width: 300px;"><input name="key" placeholder="A1-A3-AR" class="layui-input"  type="text" id="key" value="Null" size="15"></div></td>
                            <td nowrap>如:-X6454848</td>
                          </tr>
                          <tr>
                            <td><div align="right">
                              <input type="radio" name="radiobutton" value="6">
                            </div></td>
                            <td nowrap>删除</td>
                            <td nowrap>删除(谨慎操作)</td>
                          </tr>
                          <tr>
                            <td>&nbsp;</td>
                            <td><label>  <br>
                              </label>
                                时间格式:<br>
                                2012-05-06 02:50:06 <br>
                                <label></label></td>
                            <td>&nbsp;</td>
                          </tr>
    <tr>
      <td height="21"><div align="right"></div></td>
      <td><input type="submit" id="admin" name="ok"  class="layui-btn layui-btn-normal"  value="提交维护"></td>
      <td>(谨慎操作建议先备份数据库)</td>
    </tr>
  </table>
  <p>&nbsp;</p>
</form>





<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>

<script language="javascript">
/*
   window.onload = SETradio;
  
  
  
  
  function SETradio() {
  
  

var v=document.getElementsByName('lei_x');
//alert(v.item(0).checked);
if(v.item(0).checked==true){

      form.appall.disabled=true;
	  form.leiall.disabled=true;
	  form.select.disabled=true;
	  form.leiall_test.disabled=true;
      form.text_id.disabled=false;




}else{

      form.text_id.disabled=true;
   
      form.appall.disabled=false;
	  form.leiall.disabled=false;

	  
	  
	  var leiall = document.getElementById('leiall');
	  if(leiall.value >= 4){
	  
	  form.select.disabled=false;
	  form.leiall_test.disabled=false;
	  if(leiall.value == 4){
	  
	  form.select.disabled=true;
	  }
	  
	  }else{
	  
	  form.select.disabled=true;
	  form.leiall_test.disabled=true;
	  
	  
	  }
	  

}


  }
   
   
   */


</script>

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

</body>
</html>