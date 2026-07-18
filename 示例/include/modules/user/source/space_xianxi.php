<?PHP

/**
 * 调用修改密码API
 * 
 * 由于需要刷新所有了个跳转
 */

if(set_post('Submit')){
$qq=set_post('qq');
$mail=set_post('mail');
$dh=set_post('dh');
$LOG=UserAddInfo($uid,$qq,$mail,$dh);
$name=$User_array_log[$LOG];
if($LOG==1017){

user_at( '个人信息修改成功!','space.php?do=feed');;
}
}else{
    
   $name=NULL;  
   /**
 * @读取用户数据库
 */
$userarr=$db->my_class_array('bs_php_user','user_uid',$uid,'`user_qq`,`user_Mobile`,`user_email`');
$qq=$userarr['user_qq'];
$mail=$userarr['user_email'];
$dh=$userarr['user_Mobile'];
}






print <<< EOT
<form id="form1" name="form1" method="post" action="space.php?do=info">
<table width="530" border="0" align="center" cellspacing="2">
  <tr>
    <td width="158" height="22"><div align="right"></div></td>
    <td width="332">信息完善</td>
    <td width="26">&nbsp;</td>
  </tr>
  <tr>
    <td height="22"><div align="right">腾讯QQ:</div></td>
    <td><label>
      <input name="qq" type="text" id="qq" value="{$qq}" size="15" />
    *</label> 
    QQ号码 </td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td><div align="right">邮箱:</div></td>
    <td><input name="mail" type="text" id="mail" value="{$mail}" size="25" />
    *  </td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td><div align="right">联系电话:</div></td>
    <td><input name="dh" type="text" id="dh" value="{$dh}" size="25" />
    * 11位数</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td height="34">&nbsp;</td>
    <td><label>
      <input type="submit" name="Submit" value="提交修改" />  {$name}
    </label></td>
    <td>&nbsp;</td>
  </tr>
</table>

</form>
EOT;
?>