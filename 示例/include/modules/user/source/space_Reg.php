<?PHP
$re=$_GET['id'];
if($re!=''){

setcookie ('user',$re,time()+9999,$_SERVER['HTTP_HOST']);
}


elseif($id=='re'){
$sql="
INSERT INTO `appenqicaihong`.`bs_php_vip` (
`vip_id` ,
`vip_uid` ,
`vip_pass` ,
`vip_mail` ,
`vip_qq` ,
`vip_uid_date` ,
`vip_zhuce_ip` ,
`vip_re_uid` 
)
VALUES (
NULL , 'xiaozhou', '1230',  '944463782@qq.com', '944463782', now(), '127.0.0.1','admin'
);
"

}


?>
<!--meta是编码格式 -->

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<form id="form1" name="form1" method="post" action="">
  <label></label>
  <table width="200" border="0" cellspacing="0">
   <tr>
     <td nowrap="nowrap"><div align="right">账号:</div></td>
     <td><div align="left">
       <input name="user" type="text" id="user"  style="width: 170px;"/>
     </div></td>
     <td nowrap>*6-15位
       <label>
       <input type="submit" name="Submit" value="检测">
      </label></td>
   </tr>
   <tr>
     <td><div align="right">密码:</div></td>
     <td><div align="left">
       <input name="pwd1" type="password" id="pwd1"  style="width: 170px;"/>
     </div></td>
     <td>*6-12位</td>
   </tr>
   <tr>
     <td nowrap="nowrap"><div align="right">再次输入密码:</div></td>
     <td><div align="left">
       <input name="pwd2" type="password" id="pwd2"  style="width: 170px;"/>
     </div></td>
     <td>*6-12位</td>
   </tr>
   <tr>
     <td><div align="right">联系QQ:</div></td>
     <td><div align="left">
       <input name="qq" type="text" id="qq"  style="width: 170px;"/>
     </div></td>
     <td>&nbsp;</td>
   </tr>
   <tr>
     <td><div align="right">联系邮箱:</div></td>
     <td><div align="left">
       <input name="mail" type="text" id="mail" style="width: 170px;" />
     </div></td>
     <td>&nbsp;</td>
   </tr>
   <tr>
     <td>&nbsp;</td>
     <td><div align="left">
          <input name="ok" type="submit" id="ok" value="注册" /> 
       *必填 </div></td>
     <td>&nbsp;</td>
   </tr>
 </table>
 <p>&nbsp;</p>
  <p>&nbsp;</p>
  <p>&nbsp;</p>
  <p>&nbsp;</p>
  <p>
    <label></label>
  </p>
</form>