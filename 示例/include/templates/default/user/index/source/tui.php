<style type="text/css">
<!--
.STYLE1 {font-size: 13px}
-->
</style>
<p>&nbsp;</p>
<table width="800" border="0" align="center">
  <tr>
    <td class="STYLE1"> <span class="STYLE1">推广连接1</span>
      <input name="textfield" id="textfield" type="text"   class="test" value="<?php echo bs_lib::get_configs_value('sys','url').'index.php?u='.$User_Inof['user_uid'] ?>" size="50" />
<input type="submit" name="Submit"  class="submits"  value="复制连接" onclick="copyCode('textfield');return false;" />
<a class="urla" href="<?php echo bs_lib::get_configs_value('sys','url').'index.php?u='.$User_Inof['user_uid'] ?>">打开</a>
<br/><br/>
推广连接2
<input name="textfield2" id="textfield2" type="text" class="test"  value="<?php echo bs_lib::get_configs_value('sys','url').'user/index.php?m=user&c=index&a=registration&u='.$User_Inof['user_uid'] ?>" size="50" />
  

  <input type="submit"  class="submits"  name="Submit2"  value="复制连接" onclick="copyCode('textfield2');return false;" />
  
  <a   class="urla" href="<?php echo bs_lib::get_configs_value('sys','url').'user/index.php?m=user&c=index&a=registration&u='.$User_Inof['user_uid'] ?>">打开</a>
<br/>

<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
  

<?PHP

print <<< EOT

  <p class="pc" >说明:</p>
<p  class="pc" >只要把自己的邀请链接发送给他人注册就可以获得{$COMFIGARR['yaoqing_jifen']}积分或者打开链浏的览器Cookie不被清理情况下7天内注册的用户都属于你邀请来的并获得{$COMFIGARR['yaoqing_jifen']}积分,</p>
<p class="pc" >每天最都邀请人数{$COMFIGARR['yaoqing_today_RenShu']}人。</p>

  <br/><br/>
邀请提成3层提说明

<p class="pc" >系统邀请获利等级为3层获利。如果你下面有一个你邀请来用户>(A用户)>A用户要邀请来一个用户(B用户)>B用户要邀请来一个用(C用户)</p>
<p class="pc" >(也就是你邀请来用户)->A用户消费,在{$COMFIGARR['ticheng_time']}天内充值,你将获得他充值的{$COMFIGARR['ticheng_baifenbi_1']}百分提成</p>
<p class="pc" >(也就是你邀请来用户在邀请的用户)->B用户消费,在{$COMFIGARR['ticheng_time']}天内充值,你将获得他充值的{$COMFIGARR['ticheng_baifenbi_2']}百分提成</p>
<p class="pc" >(也就是你邀请来用户在邀请的用户在邀请用户)->C用户消费,在{$COMFIGARR['ticheng_time']}天内充值,你将获得他充值的{$COMFIGARR['ticheng_baifenbi_3']}百分提成</p>



<br/><br/>
<p class="pc" >请自觉遵守平台规则,一经发现违规或者恶意刷分等行为,严重影响平台运作一经查证立即封号处理。</p>

EOT;
?></td>
  </tr>
</table>

<?php
	/*
'**************************************************************************************************
' 软件名称: 彩虹网络验证系统 Www.BsPhp.Com

' 官方网站: http://Www.BsPhp.Com
' 官方论坛：http://bbs.qicaihong.cc
' 版权所有: 七彩虹工作室   bu:小周
' 郑重声明:
'    1、任何个人或组织不得在未经授权的情况下删除、修改、拷贝本软件及其他副本上一切关于版权的信息；
'    2、作者保留此软件的法律追究权利
'**************************************************************************************************
*/


?>
<script type="text/javascript">
function copyCode(id){
 var testCode=document.getElementById(id).value;
 if(copy2Clipboard(testCode)!=false){
  alert("生成的代码已经复制到粘贴板，你可以使用Ctrl+V 贴到需要的地方去了哦！  ");
 }
}
copy2Clipboard=function(txt){
 if(window.clipboardData){
  window.clipboardData.clearData();
  window.clipboardData.setData("Text",txt);
 }
 else if(navigator.userAgent.indexOf("Opera")!=-1){
  window.location=txt;
 }
 else if(window.netscape){
  try{
   netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
  }
  catch(e){
   alert("您的firefox安全限制限制您进行剪贴板操作，请打开’about:config’将signed.applets.codebase_principal_support’设置为true’之后重试，相对路径为firefox根目录/greprefs/all.js");
   return false;
  }
  var clip=Components.classes['@mozilla.org/widget/clipboard;1'].createInstance(Components.interfaces.nsIClipboard);
  if(!clip)return;
  var trans=Components.classes['@mozilla.org/widget/transferable;1'].createInstance(Components.interfaces.nsITransferable);
  if(!trans)return;
  trans.addDataFlavor('text/unicode');
  var str=new Object();
  var len=new Object();
  var str=Components.classes["@mozilla.org/supports-string;1"].createInstance(Components.interfaces.nsISupportsString);
  var copytext=txt;str.data=copytext;
  trans.setTransferData("text/unicode",str,copytext.length*2);
  var clipid=Components.interfaces.nsIClipboard;
  if(!clip)return false;
  clip.setData(trans,null,clipid.kGlobalClipboard);
 }
}
</script>


