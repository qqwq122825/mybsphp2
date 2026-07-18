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
.STYLE4 {	font-size: 13px;
	font-weight: bold;
	color: #1F4A65;
}
-->
</style>
</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable"><div id="texttale"  class="texttale">用户帐号批量维护</DIV> [<a href="javascript:window.location.reload();">+</a>]</div>


<!--  内容列表   -->
<form action=""  class="layui-form"  name="form"  method="post"      >
  <table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">
    <tr>
      <th width="113" height="188" scope="col"><table width="112" border="0" align="left" cellpadding="0" cellspacing="0" bordercolor="#C8D5E4">
          <tr>
            <th class="STYLE4" scope="col"><div align="left">1.选择</div></th>
          </tr>
          <tr>
            <th width="108" class="STYLE4" scope="col"><label>
              <select name="select" size="13" >
                <option value="user_all"  >所有用户</option>
                <option value="out_1">状态冻结</option>
                <option value="out_0">状态正常</option>
                <option value="login_not">未登录过用户</option>
                <option value="login_today">今天登陆用户</option>
                <option value="all">全部代理商</option>
              </select>
            </label></th>
          </tr>
          <tr>
            <th nowrap scope="row"><span class="STYLE4">
              <label>
              <input name="checkbox" type="checkbox" value="checkbox" checked>
             
              代理商除外 </label></span></th>
          </tr>
      </table></th>
      <th width="402" scope="col"><table width="418" border="0" align="left" cellpadding="1" cellspacing="1" class="STYLE2">
          <tr>
            <th width="20" scope="col">&nbsp;</th>
            <th width="228" class="STYLE4" scope="col"><div align="left">2.操作</div></th>
            <th width="87" class="STYLE4" scope="col">&nbsp;</th>
          </tr>
          <tr>
            <td nowrap><input type="radio" name="radiobutton" value="1"></td>
            <td nowrap>添加金额
			 <div class="layui-input-inline" style="width: 100px;">
              <input name="text_add" type="text" class="layui-input" placeholder=" 如:1"  id="text_add" size="10"></div>
              元</td>
            <td nowrap>如:10</td>
          </tr>
          <tr>
            <td nowrap><input type="radio" name="radiobutton" value="2"></td>
            <td nowrap>减少金额
			 <div class="layui-input-inline" style="width: 100px;">
              <input name="text_bus" type="text" class="layui-input" placeholder=" 如:1"   id="text_bus" size="10"></div>
              元(注意会负金额)</td>
            <td nowrap>如:200</td>
          </tr>
          <tr>
            <td nowrap><input type="radio" name="radiobutton" value="3"></td>
            <td nowrap>折扣
			 <div class="layui-input-inline" style="width: 100px;">
              <input name="text_zhe" type="text" class="layui-input"  placeholder=" 如:8.8"  id="text_zhe" size="10"></div>
              折</td>
            <td nowrap>如:1.1</td>
          </tr>
          <tr>
            <td nowrap><input type="radio" name="radiobutton" value="4"></td>
            <td nowrap>状态冻结</td>
            <td nowrap>冻结帐号</td>
          </tr>
          <tr>
            <td><input type="radio" name="radiobutton" value="5"></td>
            <td nowrap>状态解冻</td>
            <td nowrap>解除冻结帐号</td>
          </tr>
          <tr>
            <td><input type="radio" name="radiobutton" value="6"></td>
            <td>删除</td>
            <td>删除帐号</td>
          </tr>
          <tr>
            <td><input type="radio" name="radiobutton" value="7"></td>
            <td nowrap>删除-&gt;
			
			      <div class="layui-input-inline" style="width: 100px;">
        <input name="day" type="text"  class="layui-input" placeholder="天未登录" id="day" size="10">
      </div>               </td>
            <td nowrap>如:50</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td><label> <br>
                  <input type="submit" id="admin" class="layui-btn layui-btn-normal" name="ok" value="提交维护">
              </label>
                <br>            </td>
            <td>&nbsp;</td>
          </tr>
      </table></th>
    </tr>
  </table>
  <p align="center">代理商只能操作1级代理商,代理商在开的代理不会操作到的</p>
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
</script>

<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>



</body>
</html>