<?php

$set_ok='pay';
include ('../Data/appen.pay.php');


if($taobao_pay['off']==''){

    die( '没有开启网淘宝自助充值');
}

$name_log=null;
$chong_name='完成淘宝自助充值';
if($taobao_pay['lei']=='checked'){

  $name_log='/确定收货后';
  $chong_name='提示发货成功后,需要到淘宝《确认收货》在输入订单号后点击《立即充值验证》按钮完成淘宝自助充值.';
}

$paynit=set_get('payint');
$pay_id=set_get('pay_id');
$url=url();
$act=set_get('act');



?>
<style type="text/css">
<!--
.STYLE1 {font-size: 12px}
.STYLE3 {font-size: 12px; }
.STYLE5 {
	color: #FF0000;
	font-weight: bold;
}
-->
</style>


<form id="form1" name="form1" method="post" action="../Payment/taobao/taobaopay.php">

<table width="762" height="191" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td height="39">&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td height="30"><div align="right"><span class="STYLE1">客服人员:</span></div></td>
    <td><a target="_blank" href="http://www.taobao.com/webww/ww.php?ver=3&amp;touid=<?php echo $taobao_pay['user'] ?>&amp;siteid=cntaobao&amp;status=1&amp;charset=utf-8"><img border="0" src="http://amos.alicdn.com/realonline.aw?v=2&amp;uid=xiaozhou_cn&amp;site=cntaobao&amp;s=1&amp;charset=utf-8" alt="点击与客服联系" /></a></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td width="174" height="44"><div class="STYLE1" align="right">商品地址:</div></td>
    <td width="485" ><a href="http://item.taobao.com/item.htm?id=<?php echo $taobao_pay['payid'] ?>" target="_blank" class="STYLE3">http://item.taobao.com/item.htm?id=<?php echo $taobao_pay['payid'] ?></a>

      <input name="pay_uid" type="hidden" id="pay_uid" value="<?php echo $uid; ?>" />
      <input name="pay_url" type="hidden" id="pay_url" value="space.php?do=taobao&amp;act=pay" /></td>
    <td width="103">&nbsp;</td>
  </tr>
  <tr>
    <td height="21"><h3 align="right" class="STYLE1">淘宝订单号:</h3></td>
    <td><input name="pay_id" type="text"  class="perInputS"  id="pay_id" value="<?php echo $pay_id ?>" size="50" />
    <input type="submit" name="Submit_pay"  id="Submit_pay"   value="立即充值验证" class="submits" /></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td height="15">&nbsp;</td>
    <td><span class="STYLE3">下单付款后<?php echo $name_log ?>,在上边输入订单号后点击《立即充值验证》按钮自助充值！ </span></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  
  <?php if($paynit==0){ ?>
  
  <tr>
    <td colspan="3">&nbsp;&nbsp;&nbsp;
      <div align="center"><span class="STYLE1"> 订单号获取：打开已买宝贝查找获取 连接:http://trade.taobao.com/trade/itemlist/list_bought_items.htm
        </span><br />    
      <img src="image/1.jpg" alt="" width="750" height="150" /></div></td>
    </tr>
  <?php } ?>
  
  
<?php if($paynit==1){ ?>
  <tr>
    <td><div align="right"><img src="image/at.gif" alt="" /></div></td>
    <td class="STYLE5">尊敬用户你充值金额暂时冻结,需要到淘宝《确认收货》在输入订单号后点击《立即充值验证》按钮完成淘宝自助充值</td>
    <td>&nbsp;</td>
  </tr>
  
  <?php } ?>
  
</table>
</form>


  <div class="replyTxt">
			<div class="replyTit"><h3>淘宝自助充值说明</h3></div>


  <p>1.淘宝自助充值需要先到淘宝拍下指定商品并付款,商品数量不限数量,点击《立即进淘宝下单》直达指定商品</p>
  <p>2.拍下后凭订单号到这里输入订单号后点击《立即充值验证》按钮验证充值,验证后系统会自动发货</p>
  <p>3.<?php  echo $chong_name ?></p>
  <p>4.充值期间有疑问可以点击联系我们旺旺客服人员。</p>
  </div>