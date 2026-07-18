<style type="text/css">
<!--
#form1 textarea {
	width: 550px;
	height: 90px;
	background-color: #F0F0F0;
	border: thin solid #CCCCCC;
}
#form1 .img {
	margin-left: 20px;
}
.STYLE1 {
	color: #B3B3B3;
	font-size: 12px;
	font-weight: normal;
}
.TXT {
	font-size: 12px;
	color: #0099FF;
}
-->
</style>

<form id="form1" name="form1" method="post" action="">
<table width="748" border="0" align="center" cellspacing="0">
  <tr>
    <td width="596"><p>&nbsp;</p></td>
    <td width="148">&nbsp;</td>
  </tr>
  <tr>
    <td><textarea name="textarea"></textarea></td>
    <td> 
      <input name="submit" class="submit" type="submit" value="提交"/>
 </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
  
    <td ><?php
$sql="SELECT*FROM`bs_php_shuoshuo`ORDER BY `wei_id` DESC LIMIT 0,30";
$tmp = $this->db->Bsphp_my_query($sql);
$i=0;
while ($weishuo = @mysqli_fetch_array($tmp)) {
    echo '<p><img name="" src="'.get_url_statics() .'images/shuo.gif" width="20" height="20" alt="" />' .
        $weishuo['wei_uid'] . '说:</p> <p>' . base64_decode($weishuo['wei_name']) . '</p><br><span class="STYLE1">' . $weishuo['wei_date'] .
        ' &nbsp;&nbsp;&nbsp;&nbsp;   来自:' . $weishuo['wei_ip'].'<span>' .
        '<br><img name="" src="'.get_url_statics() .'images/hr.jpg"  alt="" /><br>';
$i++;
}
if($i==0){
    echo '还没有会员发表过信息 ~！~';
}
?>

  </td>
  </tr>
  </table>
 
</form>
