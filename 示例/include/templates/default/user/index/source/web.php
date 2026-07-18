
<style type="text/css">
<!--
.STYLE6 {	font-size: 18px;
	color: #FF0000;
	font-weight: bold;
}
.ta {
	font-size: 12px;
	font-weight: normal;
	border-bottom-width: thin;
	border-bottom-style: solid;
	border-bottom-color: #E9E9E9;
}
.STYLE7 {color: #FF0000}
-->
</style>
<?php 

if($action=='pay'){
$date=HOST_UNIX-2592000;  //查询一个月内的
$date=date('Y-m-d H:i:s',$date);
$sql="SELECT*FROM`bs_php_pay_log`WHERE`pay_uid`=\"$uid\" ORDER BY`pay_date`DESC LIMIT $sqlid , 10";
$arr = $this->db->Bsphp_my_query($sql);




?>

<table width="100%"  cellpadding="0" cellspacing="0" bordercolor="#DDDDDD">
  <tr   >
    <td class="ta"   width="266" height="38" bgcolor="#F7F7F7"><div align="center">商品名称</div></td>
    <td   width="119" nowrap="nowrap" bgcolor="#F7F7F7"  class="ta"><div align="center">数量</div></td>
    <td   class="ta"  width="103" bgcolor="#F7F7F7"><div align="center">单价(元)</div></td>
    <td  class="ta"   width="105" bgcolor="#F7F7F7"><div align="center">折扣</div></td>
    <td   width="187" bgcolor="#F7F7F7"  class="ta"><div align="center">总价(元)</div></td>
    <td   width="305" bgcolor="#F7F7F7"  class="ta"><div align="center">状态</div></td>
    <td  class="ta"   width="320" bgcolor="#F7F7F7"><div align="center">订单信息</div></td>
  </tr>
   <?php

    while ($good = mysqli_fetch_array($arr))
    {

?>
  <tr>
    <td   class="ta"   height="42"><div align="center">
      <?php

    echo $good['ali_ka_name'].'<br><p>'.$good['pay_app_name'];

?>
    &nbsp;</div></td>
    <td height="42" nowrap="nowrap"   class="ta"><div align="center">
      <p>
        <?php

    echo $good['pay_ka_shuliang'].'张×'.$good['ali_ka_date'];

?>

        天<br />
      </p>
    </div></td>
    <td height="42"   class="ta"><div align="center">
      <?php

    echo $good['ali_ka_jiage'];

?>
    </div></td>
    <td height="42"   class="ta"><div align="center">
      <?php

        echo $good['ali_ka_zhe']

?>
    </div></td>
    <td height="42"   class="ta"><div align="center">
   
        <?php

        echo $good['ka_shijia']

?>
    </div></td>
    <td height="42"   class="ta"><div align="center">
        <label></label>
       
        <p>
          <?php if( $good['pay_zhuangtai']==2){
echo '领卡';
}elseif( $good['pay_zhuangtai']==2){
echo '交易关闭';
}else{
echo'交易成功';
echo'</p>
        <p><a href="index.php?m=user&c=index&a=space&do=ecshop&action=pay&payid=';

        echo $good['pay_id']; echo

'"><span class="STYLE7">查看充值卡</span></a></p>';
}

?>
    </div>
    </td>
    <td height="42"   class="ta"><div align="center">
      <label></label>
     
        <p align="left">
          订单号:<?php

        echo $good['pay_id']

?>
        </p>
        <p align="left">订单时间:
          <?php

        echo $good['pay_date']

?>
</p>
    </div></td>
  </tr>
 <?php $x=1 ; } if($x==NULL)  NotRen($url_v); ?>
</table>

<p>&nbsp;</p>
<p>
  <?php }elseif($action=='ail'){   //平台订单 
  
$date=HOST_UNIX-2592000;  //查询一个月的
$date=date('Y-m-d H:i:s',$date);
$sql="SELECT* FROM`bs_php_rmb_pay_log`WHERE`pay_uid`='$uid' ORDER BY`id`DESC LIMIT $sqlid , 10 ";
$arr = $this->db->Bsphp_my_query($sql);



?>

<table width="100%"  cellpadding="0" cellspacing="0" bordercolor="#DDDDDD">
  <tr   >
    <td class="ta"   width="23%"  height="38" bgcolor="#F7F7F7"><div align="center">订单号</div></td>
    <td   width="20%" nowrap="nowrap" bgcolor="#F7F7F7"  class="ta"><div align="center">充值金额</div></td>
    <td   class="ta"  width="20%" bgcolor="#F7F7F7"><div align="center">时间</div></td>
    <td  class="ta"   width="20%" bgcolor="#F7F7F7"><div align="center"> 充值方式 </div></td>
    <td  class="ta"   width="28%" bgcolor="#F7F7F7"><div align="center"> 充值状态 </div></td>
  </tr>
   <?php
    $pay_zhuangtai=array(0=>'完成充值',1 =>'等待确认/付款');
    
    while (@$good = mysqli_fetch_array($arr))
    {
        
        
        $fangshi=$good['pay_lei'];
        

        
        
	
	


?>
  <tr>
    <td   class="ta" height="35" ><div align="center">

      <?php

    echo $good['pay_id'];

?>
    &nbsp;</div></td>
    <td height="35" nowrap="nowrap"   class="ta"><div align="center">
      <p>
        <?php

    echo $good['pay_rbm'];

?>元<br />
      </p>
    </div></td>
    <td height="35" class="ta"><div align="center">
      <?php

    echo $good['pay_date'];

?>
    </div></td>
    
    <td height="35" class="ta"><div align="center">
      <?php

        echo $fangshi;

?>
    </div></td>
    
    <td height="35" class="ta"><div align="center">
      <?php

        echo $pay_zhuangtai[ $good['pay_zhuangtai']];

?>
    </div></td>
    
  </tr>
 <?php $x=1 ; } if($x==NULL)  NotRen($url_v); ?>
</table>

<p>&nbsp;</p>
<p>
  
  
  
  
  

  
  
  
  <?php }elseif($action=='login'){
  
$sql="SELECT*FROM `bs_php_log`WHERE`leixing`=\"user_login_log\" AND `user`LIKE\"{$user_array['user_user']}\" ORDER BY `id`DESC  LIMIT $sqlid , 10";

$arr = $this->db->Bsphp_my_query($sql);
  
  
  ?>
  
  
  
  
  
  
  
  
<table width="100%"  cellpadding="0" cellspacing="0" bordercolor="#DDDDDD">
  <tr   >
    <td class="ta"   width="12%"  height="38" bgcolor="#F7F7F7"><div align="center">编号</div></td>
    <td   width="15%" nowrap="nowrap" bgcolor="#F7F7F7"  class="ta"><div align="center">登录状态</div></td>
    <td   class="ta"  width="18%" bgcolor="#F7F7F7"><div align="center">时间</div></td>
    <td   width="55%" bgcolor="#F7F7F7"  class="ta"><div align="center">IP/所在地区</div></td>
  </tr>
  <?php

    while (@$good = mysqli_fetch_array($arr))
    {
        



?>
  <tr>
    <td height="35" class="ta"><div align="center">
      <?php

    echo $good[0];

?>
    &nbsp;</div></td>
    <td height="35" nowrap="nowrap"   class="ta"><div align="center">
    
        <?php

    echo $good[4];

?>
        <br />
      
    </div></td>
    <td height="35" class="ta"><div align="center">
      <?php

    echo date('Y-m-d H:i:s',$good[2]);

?>
</div></td>
    <td height="35" class="ta"><div align="center">
        <?php

    echo ip($good[3]);

?>
    </div></td>
  </tr>
  <?php $x=1 ; } if($x==NULL)  NotRen($url_v); ?>
</table>
  
  
  
  <p class="pc">如果有异常登陆请及时修改密码已免造成不必要损失,<a class="urla" href="space.php?do=pwd">点修改密码</a></p><br />
  <?php
  }elseif($action=='yao'){
  $sql="SELECT*FROM `bs_php_log`WHERE`leixing`=\"yao_registration_log\" AND `user`LIKE\"{$user_array['user_user']}\" ORDER BY `id`DESC  LIMIT $sqlid , 10";


$arr = $this->db->Bsphp_my_query($sql);
  
  
  ?>
  
  
  
  
  
  
  
  
<table width="100%"  cellpadding="0" cellspacing="0" bordercolor="#DDDDDD">
  <tr   >
    <td class="ta"   width="12%"  height="38" bgcolor="#F7F7F7"><div align="center">编号</div></td>
    <td   width="21%" nowrap="nowrap" bgcolor="#F7F7F7"  class="ta"><div align="center">邀请信息</div></td>
    <td   class="ta"  width="23%" bgcolor="#F7F7F7"><div align="center">时间</div></td>
    <td   width="44%" bgcolor="#F7F7F7"  class="ta"><div align="center">被邀请用户所在地区/IP</div></td>
  </tr>
  <?php

    while (@$good = mysqli_fetch_array($arr))
    {
        



?>
  <tr>
    <td height="35" class="ta"><div align="center">
      <?php

    echo $good[0];

?>
    &nbsp;</div></td>
    <td height="35" nowrap="nowrap"   class="ta">
      
      <div align="left">
        <?php

    echo $good[4];

?>
        <br />
        
    </div></td><td height="35" class="ta"><div align="center">
      <?php

    echo date('Y-m-d H:i',$good[2]);

?>
</div></td>
    <td height="35" class="ta"><div align="center">
        <?php

    echo ip($good[3]);

?>
    </div></td>
  </tr>
  <?php $x=1 ; } if($x==NULL)  NotRen($url_v); ?>
</table>
  <P>
  <BR/>
  
  
  
  
  
  <?php
  
  }elseif($action=='yon'){
  
 
  
    $sql="SELECT*FROM `bs_php_log`WHERE`leixing`=\"yao_money_log\" AND `user`LIKE\"{$user_array['user_user']}\" ORDER BY `id`DESC  LIMIT $sqlid , 10";


$arr = $this->db->Bsphp_my_query($sql);
  
  
  ?>
  
  
  
  
  
  
  
  
<table width="100%"  cellpadding="0" cellspacing="0" bordercolor="#DDDDDD">
  <tr   >
    <td class="ta"   width="12%"  height="38" bgcolor="#F7F7F7"><div align="center">编号</div></td>
    <td   width="21%" nowrap="nowrap" bgcolor="#F7F7F7"  class="ta"><div align="center">佣金获得信息</div></td>
    <td   class="ta"  width="23%" bgcolor="#F7F7F7"><div align="center">时间</div></td>
    <td   width="44%" bgcolor="#F7F7F7"  class="ta"><div align="center">被邀请用户所在地区/IP</div></td>
  </tr>
  <?php

    while (@$good = mysqli_fetch_array($arr))
    {
        



?>
  <tr>
    <td height="35" class="ta"><div align="center">
      <?php

    echo $good[0];

?>
    &nbsp;</div></td>
    <td height="35" nowrap="nowrap"   class="ta">
      
      <div align="left">
        <?php

    echo $good[4];

?>
        <br />
        
    </div></td><td height="35" class="ta"><div align="center">
      <?php

    echo date('Y-m-d H:i',$good[2]);

?>
</div></td>
    <td height="35" class="ta"><div align="center">
        <?php

    echo ip($good[3]);

?>
    </div></td>
  </tr>
  <?php $x=1 ; } if($x==NULL)  NotRen($url_v); ?>
</table>
 <br />
  <P>

  
  
  
   
  
  
  
  
    <?php
  }else{ ?>
 

<meta http-equiv="refresh" content="0;URL=space.php?do=web&action=login" />


<?php } 

if($action!='pa'){


print <<< EOT
  <p class="pc">
  <input type="submit"  class="submits" name="Submitshang" value="上一页" onclick="window.location.href='index.php?m=user&c=index&a=space&do=web&action={$action}&fid=$shang'" />
  <input type="submit" class="submits"  name="Submit2" value="下一页" onclick="window.location.href='index.php?m=user&c=index&a=space&do=web&action={$action}&fid=$xia'" />
  当前第{$xia}页
  </p>
  
  
  
  
  
  <div class="replyTxt">
			<div class="replyTit"><h3>用户操作记录日志</h3></div>
			&nbsp;&nbsp;&nbsp;&nbsp;
  <input type="submit"  class="submit"  name="Submit" value="购卡记录"  onclick="window.location.href='index.php?m=user&c=index&a=space&do=web&action=pay'" />
  <input type="submit"  class="submit" name="Submit2" value="平台充值记录" onclick="window.location.href='index.php?m=user&c=index&a=space&do=web&action=ail'" />
  <input type="submit" class="submit"  name="Submit2" value="登录日志" onclick="window.location.href='index.php?m=user&c=index&a=space&do=web&action=login'" />
  <input type="submit" class="submit"  name="Submit22" value="邀请日志" onclick="window.location.href='index.php?m=user&c=index&a=space&do=web&action=yao'" />
  <input type="submit" class="submit"  name="Submit22" value="佣金提成记录" onclick="window.location.href='index.php?m=user&c=index&a=space&do=web&action=yon'" />
  <p> 查询项目-日志信息不定时清理,查询的记录只能是近期记录</p>
  </div>
  
  
EOT;
//当前第几刚号下一页就是加1 所以直接用他来显示了
 if($xia==1)echo '<script type="text/JavaScript"> document.all("Submitshang").disabled=true; </script>';

}










?>