<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?>- by BSPHP</title>
<!--系统CSS-->

 <link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
 <script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css">
<script src="<?PHP ECHO get_url_statics(); ?>skin/js/table.js"></script>

</head>
<body leftmargin="8" topmargin="8" >
<!--  标题  -->
<div class="nametable">
  <div id="texttale"  class="texttale"><?php echo $array['app_name'] ?>-软件配置</DIV>
  [<a href="javascript:window.location.reload();">+</a>]</div>
<!--  快捷  -->
<div id="Shortcut">
  <ul>
    <li><a href="index.php?m=applib&c=admin_app&a=appini&id=<?php echo $app_daihao ?>" > 配置软件</a></li>
    <li><a href="index.php?m=applib&c=admin_user&a=table&daihao=<?php echo $app_daihao ?>"  > 用户列表</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=table&daihao=<?php echo $app_daihao ?>">卡串列表</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=make&daihao=<?php echo $app_daihao ?>"> 制卡</a></li>
    <li><a href="index.php?m=applib&c=admin_card&a=class&daihao=<?php echo $app_daihao ?>">卡串类型 </a></li>
    <li><a href="index.php?m=applib&c=admin_grouping&a=table&daihao=<?php echo $app_daihao ?>">用户分组 </a></li>
  </ul>
</div>
<!--  搜索 faqN12 -->
<!--  内容列表   -->
<form action=""  name="form"  method="post"  class="layui-form"   >

 

<div style="width:98%;margin-left: 10px; "   class="layui-tab layui-tab-card">
  <ul class="layui-tab-title">

    <li class="layui-this">基本配置</li>
    <li>通信安全</li>
    <li>限开机制</li>
    <li>高级选项</li>
	<li>API别名</li>
  </ul>
  <div class="layui-tab-content" >
    <div class="layui-tab-item layui-show">
	<table class="layui-table"  lay-filter="demoEvent">
	<thead>
    <tr>
      <th width="14%" height="35" nowrap="nowrap" >配置名称</th>
      <th width="29%" >表单填写</th>
      <th width="57%" height="26" ><span >参考</span></th>
    </tr>
	</thead>
    <tr onMouseOver="bsphpfaq(this, 'faqN1201')" >
      <td height="30" nowrap="nowrap"  ><div align="left">软件代号:<a id="faqN1201"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1201"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><label> <?php echo $id; ?>
        <input name="app_daihao" type="hidden" id="app_daihao" value="<?php echo $id ?>">
        </label></td>
      <td height="30"  >软件数据编号</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1202')" >
      <td height="30" nowrap="nowrap"  ><div align="left">通信认证Key:<a id="faqN1202"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1202"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><?php echo $md5s; ?></td>
      <td height="30"  >数据包交互串</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1203')" >
      <td height="30" nowrap="nowrap"  ><div align="left">服务器地址:<a id="faqN1203"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1203"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><?php echo $Bsphp_Host; ?>AppEn.php?appid=<?php echo $id; ?>&amp;md5=<?php echo $md5; ?></td>
      <td height="30"  >当前软件服务器发包接收地址</td>
    </tr>
    <tr>
      <td height="30" nowrap="nowrap"  >&nbsp;</td>
      <td height="30"  >&nbsp;</td>
      <td height="30"  >&nbsp;</td>
    </tr>
    <tr  onmouseover="bsphpfaq(this, 'faqN1212')" >
      <td height="30" nowrap="nowrap"  ><div align="left">别名:<a id="faqN1212"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1212"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><div class="layui-input-inline" style="width: 300px;">
<input name="app_name" type="text" class="layui-input"  id="app_name" value="<?php echo $array['app_name'] ?>"></div></td>
      <td height="30"  >软件显示名称</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1213')" >
      <td height="30" nowrap="nowrap"  >开关:<a id="faqN1213"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1213"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
      <td height="30"  ><label><div class="layui-input-inline" style="width: 300px;">
        <select name="app_off" id="app_off">
          <option value="0" >正常开放软件使用</option>
          <option value="1" >关闭软件</option>
        </select></div></td>
      <td height="30"  >软件开关</td>
    </tr>
    <tr  onmouseover="bsphpfaq(this, 'faqN1214')">
      <td height="30" nowrap="nowrap"  >关闭提示:<a id="faqN1214"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1214"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
      <td height="30"  ><div class="layui-input-inline" style="width: 300px;"><input name="app_off_name" type="text" class="layui-input"  id="app_off_name" value="<?php echo $array['app_off_name'] ?>" size="40"></div></td>
      <td height="30"  >软件关闭提示文字</td>
    </tr>
    <tr  onmouseover="bsphpfaq(this, 'faqN1221')"  {$ck_array[2]}>
      <td height="30" nowrap="nowrap"  ><div align="left"><span ></span>是否绑定模式:<a id="faqN1221"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1221"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><label><div class="layui-input-inline" style="width: 300px;">
        <select name="app_set" id="app_set">
          <option value="0"  >免验证绑定特征</option>
          <option value="1" >验证绑定特征</option>
        </select></div>
      </label></td>
      <td height="30"  >设为需要验证绑定特征,帐号登录,取验证数据 都将验证绑定KEY参数是否与绑定一致 &amp;key= </td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1222')"  {$ck_array[9]}>
      <td height="30" nowrap="nowrap"  ><div align="left">是否可以重复绑定:<a id="faqN1222"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1222"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><div class="layui-input-inline" style="width: 300px;">
	  <select name="app_key_zhong" id="app_key_zhong">
          <option value="0"  >可重复绑定特征串</option>
          <option value="1">禁止重复特征串绑定</option>
      </select></div></td>
      <td height="30"  >是否可以绑定重复特征串(游戏账号、机器码)用验证特征</td>
    </tr>
    <tr  onmouseover="bsphpfaq(this, 'faqN1215')">
      <td height="30" nowrap="nowrap"  >收费模式:<a id="faqN1215"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1215"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
      <td height="30"  ><label><div class="layui-input-inline" style="width: 300px;">
        <select name="app_chargeset" id="app_chargeset">
          <option value="0" >收费模式</option>
          <option value="1" >免费模式</option>
        </select></div>
        </label></td>
      <td height="30"  >如果是免费模式,用户可以直接凭过期账号登录软件</td>
    </tr>
    <tr  onmouseover="bsphpfaq(this, 'faqN1216')">
      <td height="30" nowrap="nowrap"  ><div align="left">版本号:<a id="faqN1216"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1216"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><div class="layui-input-inline" style="width: 300px;"><input name="app_v" placeholder="用反馈版本是否更新" type="text" class="layui-input"  id="app_v" value="<?php echo $array['app_v'] ?>" size="15"></div></td>
      <td height="30"  >软件版本号,用作判断是否有新版本</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1217')" >
      <td height="30" nowrap="nowrap"  ><div align="left">首次使用送:<a id="faqN1217"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1217"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  >
	  
	  <div class="layui-input-inline" style="width: 300px;"><input name="app_re_date" type="text" class="layui-input"  id="app_re_date" value="<?php echo $array['app_re_date'] ?>" size="15"></div>
        秒</td>
      <td height="30"  >用户首次注册赠送秒数,如果不送设置0 ;1小时=3600秒</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1218')">
      <td height="30" nowrap="nowrap"  ><div align="left">转绑定扣:<a id="faqN1218"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1218"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><div class="layui-input-inline" style="width: 300px;"><input name="app_zhuang_date" type="text" class="layui-input"  id="app_zhuang_date" value="<?php echo $array['app_zhuang_date'] ?>" size="15"></div>
        秒</td>
      <td height="30"  >用户转绑定时候扣除相对应秒数。;1小时=3600秒</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1219')">
      <td height="30" nowrap="nowrap"  ><div align="left">软件网页地址:<a id="faqN1219"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1219"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><label>
        <div class="layui-input-inline" style="width: 300px;"><input name="app_WEB_URL" type="text" class="layui-input"  id="app_WEB_URL" value="<?php echo $array['app_WEB_URL'] ?>" size="40"></div>
        </label></td>
      <td height="30"  >网页浏览地址用于在网页中浏览的软件主页或者其他 <span class="STYLE1">地址不为空前下载台为显示改软件</span></td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1220')">
      <td height="30" nowrap="nowrap"  ><div align="left">URL地址:<a id="faqN1220"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1220"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><div class="layui-input-inline" style="width: 300px;"><input name="app_URL" type="text" class="layui-input"  id="app_URL" value="<?php echo $array['app_URL'] ?>" size="40"></div>      </td>
      <td height="30"  >提供给软件更新的地址用来下载压缩包,需要先在系统配置设置好系统地址</td>
    </tr>
    <tr  onmouseover="bsphpfaq(this, 'faqN1223')">
      <td height="30" nowrap="nowrap"  ><div align="left">验证数据:<a id="faqN1223"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1223"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><label>
        <textarea  class="layui-textarea" name="app_logininfo" cols="40" rows="10" id="app_logininfo"><?php echo $array['app_logininfo'] ?></textarea>
        </label></td>
      <td height="30"  class="STYLE4 STYLE7"><p>注意:这里的信息之有在登录成功并且账号不过期才会传输出去</p>
        <p>超大字符数据</p></td>
    </tr>
    <tr>
      <td height="30" nowrap bgcolor="#F3F3F3" ><div align="left"></div></td>
      <td height="30" bgcolor="#F3F3F3" >&nbsp;</td>
      <td height="30" bgcolor="#F3F3F3" >&nbsp;</td>
    </tr>
    <tr  onmouseover="bsphpfaq(this, 'faqN1224')">
      <td height="30" nowrap bgcolor="#FCFCFC" ><div align="left">逻辑真假A:<a id="faqN1224"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1224"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30" bgcolor="#FCFCFC" ><p>
          <label>
          <input name="app_LogicA" title="假(0)" type="radio" <?php echo_checkbox_checked('-1',$array['app_LogicA'],0); ?> value="0" >          </label>
          
          <label>&nbsp;&nbsp;&nbsp;
          <input type="radio"  title="真(1)"  name="app_LogicA" <?php echo_checkbox_checked('-1',$array['app_LogicA'],1); ?> value="1">          </label>
          </p></td>
      <td height="30" bgcolor="#FCFCFC" >设置软件一个真假判断 用这里来控制 </td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1225')">
      <td height="30" nowrap bgcolor="#FCFCFC" ><div align="left">逻辑真假A提示:<a id="faqN1225"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1225"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30" bgcolor="#FCFCFC" ><label>
       <textarea  class="layui-textarea" name="app_LogicinfoA" cols="40" rows="5" id="app_LogicinfoA"><?php echo $array['app_LogicinfoA'] ?></textarea>
        </label></td>
      <td height="30" bgcolor="#FCFCFC" >传输内容 500字符 </td>
    </tr>
    <tr>
      <td height="30" nowrap bgcolor="#F3F3F3" ><div align="left"></div></td>
      <td height="30" bgcolor="#F3F3F3" >&nbsp;</td>
      <td height="30" bgcolor="#F3F3F3" >&nbsp;</td>
    </tr>
    <tr  onmouseover="bsphpfaq(this, 'faqN1226')">
      <td height="30" nowrap="nowrap"  ><div align="left">逻辑真假B:<a id="faqN1226"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1226"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><p>
          <label>
          <input type="radio" title="假(0)"  name="app_LogicB" <?php echo_checkbox_checked('-1',$array['app_LogicB'],0); ?>  value="0">          </label>
          
          <label>&nbsp;&nbsp;&nbsp;
          <input type="radio"  title="真(1)"     name="app_LogicB" <?php echo_checkbox_checked('-1',$array['app_LogicB'],1); ?>  value="1">          </label>
          </p></td>
      <td height="30"  >设置软件一个真假判断 用这里来控制 </td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1227')">
      <td height="30" nowrap="nowrap"  ><div align="left">逻辑真假B提示:<a id="faqN1227"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1227"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><label>
        
          <textarea  class="layui-textarea" name="app_LogicinfoB" cols="40" rows="5" id="app_LogicinfoB"><?php echo $array['app_LogicinfoB'] ?></textarea>
   
        </label></td>
      <td height="30"  >传输内容 500字符 </td>
    </tr>
    <tr>
      <td height="30" nowrap bgcolor="#F3F3F3" ><div align="left"></div></td>
      <td height="30" bgcolor="#F3F3F3" >&nbsp;</td>
      <td height="30" bgcolor="#F3F3F3" >&nbsp;</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1229')">
      <td height="30" nowrap="nowrap"  ><div align="left">软件描述介绍:<a id="faqN1229"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1229"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><br>
       <textarea  class="layui-textarea" name="app_miaoshu" cols="40" rows="5" id="app_miaoshu"><?php echo $array['app_miaoshu'] ?></textarea>
        <br>
        <br></td>
      <td height="30"  >如果描述,需要时候调用吧</td>
    </tr>
    <tr  onmouseover="bsphpfaq(this, 'faqN1230')">
      <td height="30" nowrap="nowrap"  ><div align="left">软件公告:<a id="faqN1230"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1230"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><p> <br>
          <textarea  class="layui-textarea" name="app_gg" cols="40" rows="5" id="app_gg"><?php echo $array['app_gg'] ?></textarea>
          <br>
      
		

		
		
		</td>
      <td height="30"  >软件已经公告提示</td>
    </tr>
    <tr>
      <td height="30" colspan="3"  ><label><br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input id="appenconfig" type="submit"  name="appenconfig" class="layui-btn layui-btn-normal"  value="保存修改">
        <br>
        <br>
        </label></td>
    </tr>
  </table>
  
  
  </div>
    <div class="layui-tab-item"> 
	
	<table class="layui-table"  lay-filter="demoEvent">
   <thead> 
   <tr>
      <th width="15%" height="35" >配置名称</th>
      <th width="32%" nowrap="nowrap" >表单填写</th>
      <th width="58%" height="26" ><span >参考</span></th>
    </tr>
	</thead>
    <tr onMouseOver="bsphpfaq(this, 'faqN1204')" >
      <td height="30" nowrap  class="STYLE2">接收方式：<a id="faqN1204"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1204"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
      <td height="30" nowrap="nowrap"  class="STYLE2"><label>
        <input name="get_post" type="radio" value="0"  title="GET"  <?php echo_checkbox_checked('-1',$array['app_get'],0); ?> >
        </label>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input name="get_post" type="radio" value="1"  title="POST"   <?php echo_checkbox_checked('-1',$array['app_get'],1); ?>  >
        </td>
      <td height="30"  class="STYLE4">接收软件发来数据包GET和POST 2选1,建议POST这样比GET更加安全</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1205')" >
      <td height="30" nowrap  class="STYLE2">数据包超时:<a id="faqN1205"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1205"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a>
      <td height="30" nowrap="nowrap"  class="STYLE2"><div class="layui-input-inline" style="width: 300px;">
<input name="chaoshi" type="text" class="layui-input" id="chaoshi" value="<?php echo $array['app_chaoshi'] ?>" size="15"/></div>
        秒</td>
      <td height="30"  class="STYLE4"><span class="at">设0关闭超时</span> 1分=60秒 自己计算下吧 建议10分钟-20分钟内超时,防止破解者发送旧封包欺骗服务器</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1206')" >
      <td height="30" nowrap  class="STYLE2">数据加密密码:<a id="faqN1206"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1206"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a>
      <td height="30" nowrap="nowrap"  class="STYLE2"><div class="layui-input-inline" style="width: 300px;">
<input name="password" type="text" class="layui-input"   id="password" value="<?php echo $array['app_pwd']?>" size="32"/></div>
        <span class="at">*</span></td>
      <td height="30"  class="STYLE4"> 接收数据时候加密码的密码,设置通信密码,从而加密数据让破解者无法得知封包内容</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1207')" >
      <td height="30" nowrap  class="STYLE2">反破解封号:<a id="faqN1207"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1207"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a>
      <td height="30" nowrap="nowrap"  class="STYLE2"><label>
        <input type="radio" name="app_breaking"  title="关闭"  value="0" <?php echo_checkbox_checked('-1',$array['app_breaking'],0); ?> >
        </label>
        &nbsp;&nbsp;&nbsp;&nbsp;
        <input type="radio" name="app_breaking"  title="开启"  value="1" <?php echo_checkbox_checked('-1',$array['app_breaking'],1); ?> >
        </td>
      <td height="30"  class="STYLE4">反破解封号时候,软件配置哪里一定要设置好软件MD5,否则对比出错后将记录和封号！反破解日志可查询</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1206')" >
      <td height="30" nowrap  class="STYLE2">API别名加密密码:<a id="faqN1206"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1206"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a>
      <td height="30" nowrap="nowrap"  class="STYLE2"><div class="layui-input-inline" style="width: 300px;">
<input name="app_api_pwd" type="text" class="layui-input" placeholder="防止软件被山寨,需要阅读相关说明"  id="app_api_pwd" value="<?php echo $array['app_api_pwd']; ?>" size="32"/></DIV>
        <span class="at">*</span></td>
      <td height="30"  class="STYLE4">加密API名称进行防止HOST截包,建议所有软件统一这样就不用改所有API了  留空或者0关闭</td>
    </tr>
    <tr>
      <td height="30" nowrap bgcolor="#F3F3F3" ><div align="left"></div></td>
      <td height="30" bgcolor="#F3F3F3" >&nbsp;</td>
      <td height="30" bgcolor="#F3F3F3" >&nbsp;</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1228')"  {$ck_array[15]}>
      <td height="30" nowrap="nowrap"  ><div align="left">软件MD5:<a id="faqN1228"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1228"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><textarea name="app_md5" cols="40" rows="5" id="app_md5"><?php echo $array['app_md5'] ?></textarea></td>
      <td height="30"  ><p>软件传输时候会验证查询 MD5否则将禁止数据返回<br>
          <span class="STYLE7">留空默认关闭MD5验证,只要将MD5贴进去就可以,格式随意！</span><br>
          如:<br>
          程序MD5|描述<br>
          程序MD5|描述</p>
        <p><br>
        </p></td>
    </tr>
	
  <tr onMouseOver="bsphpfaq(this, 'faqN1228')"  {$ck_array[15]}>
      <td height="30" nowrap="nowrap"  ><div align="left">API验证码:<a id="faqN1228"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1228"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30"  ><textarea name="app_coode" cols="40" rows="5" id="app_coode"><?php echo $array['app_coode'] ?></textarea></td>
      <td height="30"  >用|符号隔开 如果:liuyan.in|registration.lg|backto.lg|liuyan.in
	  <BR>请把需要使用验证码API接口输入框内,提交参数时时添加验证码&coode=验证码参数即可
	  <BR> 连接地址：<?php echo 

bs_lib::get_configs_value('sys','url'); ?>index.php?m=coode&sessl=
	  
	  </td>
    </tr>
	
    <tr>
      <td height="30" colspan="3"  ><label><br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input id="appenconfig" type="submit"  name="appenconfig" class="layui-btn layui-btn-normal"  value="保存修改">
        <br>
        <br>
        </label></td>
    </tr>
  </table></div>
    <div class="layui-tab-item">
	<table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">
   <thead> <tr>
      <th width="10%" height="35" >配置名称</th>
      <th width="32%" >表单填写</th>
      <th width="58%" height="26" ><span >参考</span></th>
    </tr></thead>
    <tr onMouseOver="bsphpfaq(this, 'faqN1208')"  {$ck_array[3]}>
      <td height="30" nowrap="nowrap" class="STYLE1"  ><div align="left">账号多开设置:<a id="faqN1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30" nowrap="nowrap"  >

		<div class="layui-input-inline" style="width: 300px;">
		 <input name="app_links_open" type="text" class="layui-input" placeholder="账号在线数量"  id="app_links_open" value="<?php echo $array['app_links_open'] ?>" size="15"></div>同时在线
		 
        </td>
      <td height="30"  >填写3表示只能同时在线3登录量 -1关闭功能,软件用户有设置,忽略这里的</td>
    </tr>
    
    <tr  onmouseover="bsphpfaq(this, 'faqN1215')">
      <td height="30" nowrap="nowrap"  >账号多开到上限:<a id="faqN1215"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1215"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></td>
      <td height="30" nowrap="nowrap"  ><label>
	  <div class="layui-input-inline" style="width: 300px;">
        <select name="links_open_off" id="links_open_off">
          <option value="0" >直接提示用户</option>
          <option value="1" >登录最早的注销</option>
        </select>
		</DIV>
        </label></td>
      <td height="30"  >当用户登录到上限之后,处理方式</td>
    </tr>
    
    
    
    <tr onMouseOver="bsphpfaq(this, 'faqN1209')"  {$ck_array[3]}>
      <td nowrap="nowrap" class="STYLE1">多开机器量:<a id="faqN1209"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1209"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a>
      <td nowrap="nowrap"><label>
	  <div class="layui-input-inline" style="width: 300px;">
        <input name="app_links" type="text" class="layui-input"  placeholder="最多几台机器使用"  id="app_links" value="<?php echo $array['app_links'] ?>" size="15"></DIV>台机器使用
        </label></td>
      <td height="30" >填写2表示只能在2台机器上使用 -1关闭功能 ,软件用户有设置,忽略这里的</td>
    </tr>
    

    
    <tr  onmouseover="bsphpfaq(this, 'faqN1210')"  {$ck_array[3]}>
      <td nowrap="nowrap" class="STYLE1">心跳包时间:<a id="faqN1210"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1210"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a>
      <td nowrap="nowrap"><label>
			<div class="layui-input-inline" style="width: 300px;"><input name="app_links_chaoshi" type="text" class="layui-input"  id="app_links_chaoshi" value="<?php echo $array['app_links_chaoshi'] ?>" size="10"></div>
        秒,建议30分钟</label></td>
      <td height="30" >如果心跳包30分钟,客户端在心跳设置一般时间15分钟心跳一次！,超出设定时间账号会退出登录！</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1202')" >
      <td height="30" nowrap="nowrap"  >&maxoror=</td>
      <td height="30"  >限制机器量,登录验证时参数不能空且不能与他人机器码重复 不然多开机器量填写-1 关闭</td>
      <td height="30"  >说明:限制是从服务器限制的,所以把电脑炸了也没用！</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1202')" >
      <td height="30" nowrap="nowrap"  >说明</td>
      <td height="30"  >http://bbs.bsphp.com/thread-7806-1-1.html</td>
      <td height="30"  ></td>
    </tr>
    
    
    
    <tr>
      <td height="30" colspan="3"  ><label><br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input id="appenconfig" type="submit"  name="appenconfig" class="layui-btn layui-btn-normal"  value="保存修改">
        <br>
        <br>
        </label></td>
    </tr>
  </table></div>
    <div class="layui-tab-item"><table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">
	<thead>
    <tr>
      <th width="10%" height="35" >配置名称</th>
      <th width="32%" nowrap="nowrap" >表单填写</th>
      <th width="58%" height="26" ><span >参考</span></th>
    </tr></thead>
    <tr onMouseOver="bsphpfaq(this, 'faqN1208')"  {$ck_array[3]}>
      <td height="30" nowrap="nowrap" class="STYLE1"  ><div align="left">第三方替代库:<a id="faqN1208"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1208"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a></div></td>
      <td height="30" nowrap="nowrap"  ><label>
        <select name="app_api_dir" id="app_api_dir">
		<option value="-1">使用系统默认API库</option>
		    <?php 
			foreach ($api_array as $value) {
			
			$moshi_array=get_moshiset_array($value);
			if(@$moshi_array['really']==2){
			   echo "<option value=\"$value\">{$moshi_array['name']}->{$value}</option>";
			
			}

			   
			}
			?>
		

          
        </select>
        </label></td>
      <td height="30"  >通过选择另外一个第三方修改API接口调用取代系统默认的,这样就可以不修改系统接口,避免升级替换麻烦！如果第三方库没有就会选择返回选择默认的！</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1209')"  {$ck_array[3]}>
      <td nowrap="nowrap" class="STYLE1">接收到数解密方式:<a id="faqN1209"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1209"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a>
      <td nowrap="nowrap"><select name="app_get_encryption" id="app_get_encryption">
	   <option value="notfun">明文接收</option>
          		    <?php 
			foreach ($encryption_array as $value) {
               $value=str_replace('.php', '', $value);
			   echo "<option value=\"$value\">$value</option>";

			}
			?>
        </select></td>
      <td height="30" >这里是接收到客户端发送来的密文数据,这里选择客户端发送时所用加密方式 文件位置:include\applibapi\encryption,可第三方开发！</td>
    </tr>
    <tr  onmouseover="bsphpfaq(this, 'faqN1210')"  {$ck_array[3]}>
      <td nowrap="nowrap" class="STYLE1">返回数据加密方式:<a id="faqN1210"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1210"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a>
      <td nowrap="nowrap"><select name="app_show_encryption" id="app_show_encryption">
	 <option value="notfun">明文输出</option>
          		    <?php 
			foreach ($encryption_array as $value) {
               $value=str_replace('.php', '', $value);
			   echo "<option value=\"$value\">$value</option>";

			}
			?>
        </select></td>
      <td height="30" >这里是服务器返回给客户端数据是采用加密的方式,返回显示加密封包客户端解密 文件位置:include\applibapi\encryption,可第三方开发！</td>
    </tr>
	
    <tr  onmouseover="bsphpfaq(this, 'faqN1210')"  {$ck_array[3]}>
      <td nowrap="nowrap" class="STYLE1">数据输出格式处理文件:<a id="faqN1210"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1210"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a>
      <td nowrap="nowrap"><select name="app_output" id="app_output">
	     <option value="notfun">不添加格式</option>
          		    <?php 
			foreach ($output_array as $value) {
               $value=str_replace('.php', '', $value);
			   echo "<option value=\"$value\">$value</option>";

			}
			?>
        </select></td>
      <td height="30" >用做处理输出格式如果josn xml等格式 位置:include\applibapi\output,可第三方开发！</td>
    </tr>
	
    <tr  onmouseover="bsphpfaq(this, 'faqN1210')"  {$ck_array[3]}>
      <td nowrap="nowrap" class="STYLE1">语言:<a id="faqN1210"   href="http://vip.bsphp.com/Admin.faq.php?id=faqN1210"  title="点击查看解释"  class="faq"target="_blank" style="display:none">&nbsp;&nbsp;&nbsp;</a>
      <td nowrap="nowrap"><select name="app_langs" id="app_langs">
	     <option value="zh-cn">默认zh-cn</option>
          		    <?php 
			foreach ($dir_array  as $value) {
               $value=str_replace('.php', '', $value);
			   echo "<option value=\"$value\">$value</option>";

			}
			?>
        </select></td>
      <td height="30" >软件用途功能不同,提示字符串自然也不相同,所以这个功能就要派上用场了！</td>
    </tr>
    <tr onMouseOver="bsphpfaq(this, 'faqN1202')" >
      <td height="30" nowrap="nowrap"  >&nbsp;</td>
      <td height="30" nowrap="nowrap"  >&nbsp;</td>
      <td height="30"  >&nbsp;</td>
    </tr>
    <tr>
      <td height="30" colspan="3"  ><label><br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input id="appenconfig" type="submit"  name="appenconfig" class="layui-btn layui-btn-normal"  value="保存修改">
        <br>
        <br>
        </label></td>
    </tr>
  </table></div>
 <div class="layui-tab-item"> <table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">
 <thead>
   <tr>
      <th width="*" height="26" class="STYLE2">API名称</th>
	  <th width="*" height="26" class="STYLE2">API路径</th>
      <th width="*"  height="26"  class="STYLE2">加密别名</th>
      
	   <th width="*" height="26" class="STYLE2">解密测试</th>
	   <th width="*" height="26" class="STYLE2">详细</th>
    </tr></thead>
    <?php
	
    if($apipwd == '' or $apipwd == '0'){

      echo '<tr> <th width="*" height="26" class="STYLE2">你没有开启API别名使用，请先输入别名接口的密码！ 请到:通信安全->API别名加密密码:输入密码开启</th><td></td><td></td><td></td><td></td></tr>';
    }else{
	  dir_copys($fis_Url,$apipwd);
	}
    
    
    
    
?>

  </table>
  
  <strong style="width:200px;margin-left: 10px;">别名有效防止被HOST,或者劫持AppEn.php<BR/>
          调用方法:<BR/>
          将引擎中:<BR/>
          通常:&api=接口名称   backto.lg<BR/>
          加密:&api=加密别名  qD0XhvSc4xat <BR/>      
          </strong>
  
  </div>
  </div>
</div>










</form>
<script language="javascript">
select_set('app_chargeset',<?php echo $array['app_chargeset'] ?>);
select_set('app_off',<?php echo $array['app_off'] ?>);

select_set('app_set',<?php echo $array['app_set'] ?>);
select_set('app_key_zhong',<?php echo $array['app_key_zhong'] ?>);



select_value('app_langs',"<?php echo $array['app_langs'] ?>");



select_value('app_api_dir','<?php echo $array['app_api_dir'] ?>');
select_value('app_get_encryption','<?php echo $array['app_get_encryption'] ?>');
select_value('app_show_encryption','<?php echo $array['app_show_encryption'] ?>');
select_value('app_output','<?php echo $array['app_output'] ?>');
</script>

<script>
layui.use('element', function(){
  var $ = layui.jquery
  ,element = layui.element; //Tab的切换功能，切换事件监听等，需要依赖element模块
  
  //触发事件
  var active = {
    tabAdd: function(){
      //新增一个Tab项
      element.tabAdd('demo', {
        title: '新选项'+ (Math.random()*1000|0) //用于演示
        ,content: '内容'+ (Math.random()*1000|0)
        ,id: new Date().getTime() //实际使用一般是规定好的id，这里以时间戳模拟下
      })
    }
    ,tabDelete: function(othis){
      //删除指定Tab项
      element.tabDelete('demo', '44'); //删除：“商品管理”
      
      
      othis.addClass('layui-btn-disabled');
    }
    ,tabChange: function(){
      //切换到指定Tab项
      element.tabChange('demo', '22'); //切换到：用户管理
    }
  };
  
  $('.site-demo-active').on('click', function(){
    var othis = $(this), type = othis.data('type');
    active[type] ? active[type].call(this, othis) : '';
  });
  
  //Hash地址的定位
  var layid = location.hash.replace(/^#test=/, '');
  element.tabChange('test', layid);
  
  element.on('tab(test)', function(elem){
    location.hash = 'test='+ $(this).attr('lay-id');
  });
  
});
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

<div id="foot">Copyright &copy;2009-2018 <a href="http://www.bsphp.com" target="_blank">BsphpCms <?php echo BSPHP_VERSION; ?></a>   Bsphp.com <br>
All Rights Reserved </div>
</body>
</html>
