


<style type="text/css">
table {
	font-family: "新宋体";
	font-size:13px;
	color:#333333;
	border-collapse: collapse;
	line-height: 15px;
	font-weight: normal;
}
table th {
	padding: 8px;
}
table td {
	padding: 8px;
	border-top-width: 1px;
	border-bottom-width: 1px;
	border-top-style: solid;
	border-bottom-style: solid;
	border-top-color: #E9E9E9;
	border-right-color: #E9E9E9;
	border-bottom-color: #E9E9E9;
	border-left-color: #E9E9E9;
}
.STYLE6 {
	font-size: 14px;
	font-weight: bold;
	color: #FF0000;
}
</style>
<?PHP
  print <<< EOT
<SCRIPT language=javascript>
function Checklogin()
{
	if (form1.a.value==0)
	{
		alert("请输入购买数量!");
		form1.a.focus();
		return false;
	}
}
</SCRIPT>
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

EOT;




  if ($action == 'ka') {
    print <<< EOT

<table width="100%" height="59" border="0" cellpadding="0" cellspacing="0" bordercolor="#CCCCCC" onmouseover="changeto()"  onmouseout="changeback()">
  <tr>
    <td width="100" height="28" bgcolor="#F7F7F7">编号</td>
    <td width="102" bgcolor="#F7F7F7"><div align="left"><strong>软件名称</strong></div></td>
    <td width="469" bgcolor="#F7F7F7"><div align="center"></div></td>
    <td width="114" bgcolor="#F7F7F7"><div align="center"><strong>充值卡种类</strong></div></td>
    <td width="58" bgcolor="#F7F7F7">&nbsp;</td>
    <td width="67" bgcolor="#F7F7F7">&nbsp;</td>
  </tr>
			   
EOT;
    $sql = "SELECT`app_daihao`,`app_name`FROM`bs_php_appinfo`";
    $tmp = $this->db->Bsphp_my_query($sql);
    $id = 0;
    while (@$app = mysqli_fetch_array($tmp)) {


      $sql = "SELECT`lei_id`FROM`bs_php_kalei`WHERE`lei_daihao`=".$app[0]." AND `lei_jiage`!='-1'";
      $tmpa = $this->db->Bsphp_my_query($sql);
      $shu = $this->db->my_rows($tmpa);
      $a = '';
      if ($shu == 0) {
        $a = '缺货';
      } else {
        $a = $shu.'种卡';
      }


      $id++;

if($shu>0){

      print <<< EOT
      <label>
  <tr>
    <td height="31" nowrap="nowrap"><span class="STYLE2"><strong>{$id}</strong></td>
    <td nowrap="nowrap" class="STYLE4">{$app[1]}</td>
    <td nowrap="nowrap"><div align="center" class="STYLE3">
      
    </div></td>
    <td nowrap="nowrap"><div align="center" class="STYLE3">
       {$a}
    </div></td>
    <td colspan="2" nowrap="nowrap"><div align="center"><a href="index.php?m=user&c=index&a=space&do=ecshop&action=tb&daihao={$app[0]}">购买</a></div></td>
  </tr>
  </label>
EOT;
}
    }


    echo '</table>
';

    //购买类型和购买数量选择



  } elseif ($action == 'tb') {
    $Submit = set_post('Submit');
    if ($Submit) {
      $picno = set_post('picno');
      $ashu = set_post('a');
      if ($picno == '') $this->alert('请选择购卡买类型!', $url);
      if ((int)$ashu == 0) $this->alert('请输入要购买的数量！', $url);
    }
//   
    $sql = "SELECT*FROM`bs_php_kalei`WHERE`lei_daihao`='$daihao' AND `lei_jiage`!='-1'";
    $tmp = $this->db->Bsphp_my_query($sql);


?>
</p>
<style type="text/css">
<!--
.txt {
	font-size: 12px;
	font-variant: normal;
}
-->
</style>

<form id="form1" name="form1" method="post" action="index.php?m=user&c=index&a=space&do=ecshop&action=ok&daihao=<?php


    echo $daihao


?>" onSubmit="return Checklogin();">
  <table width="100%" height="213" border="0" align="center" cellpadding="0" cellspacing="0"  bordercolor="#CCCCCC" onmouseover="changeto()"  onmouseout="changeback()" >

    <tr>
      <td width="73"   height="28"  nowrap="nowrap" bgcolor="#F7F7F7"  class="th" ><div align="center">选择</div></td>
      <td width="369"  nowrap="nowrap" bgcolor="#F7F7F7"  class="th">名称</td>
      <td width="273"  nowrap="nowrap" bgcolor="#F7F7F7"  class="th">价格</td>
      <td width="220"  nowrap="nowrap" bgcolor="#F7F7F7"  class="th">折扣</td>
    </tr>
    <?php


    while ($good = mysqli_fetch_array($tmp)) {
      if ($User_Inof['user_Zhe'] <= 0) {
        $ZHE = '无折扣';
      } else {
        $ZHE = $User_Inof['user_Zhe'].'折';
      }
      print <<< EOT
   
    <tr>
      <td height="28" nowrap="nowrap" bgcolor="#FFFFFF" ><div align="center">
       <input  type="radio" name="kaid" value="{$good['lei_id']}" /></div></td>
      <td width="249" nowrap="nowrap" bgcolor="#FFFFFF">{$good['lei_name']}</td>
      <td width="200" nowrap="nowrap" bgcolor="#FFFFFF">{$good['lei_jiage']}元</td>
      <td width="160" nowrap="nowrap" bgcolor="#FFFFFF">&nbsp;   {$ZHE}   </td>
    </tr>

EOT;
    }


?>
    <tr>
      <td height="26" colspan="4"  nowrap="NOWRAP" bgcolor="#FFFFFF"   ><p>
          <input name="daihao" type="hidden" id="daihao" value="<?php


    echo $daihao


?>" />
        </p>
        <p><br />
          <br />
          购买数量:<input name="a"  class="test"  type="text" id="a" value="1" size="10" /> 张

         </p>
		 <br /><br />
		   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <input type="submit" class="submit"  name="Submit" value="购买" />
		 </td>
    </tr>
  </table>
</form>
<?php


    //*********************************************************购买提交.
  } elseif ($action == 'ok') {

    $ida = set_post('kaid'); //卡串类型ID
    $shu = set_post('a');
    //$arr_app = $this->db->API_get_arrapp();

    if ((int)$ida == 0) $this->alert('请选择要购买商品', $url);
	if ((int)$shu == 0) $this->alert('请选择要购买数量', $url);
	if ((int)$shu <= 0) $this->alert('购买数量不能小于0', $url);
    $sql = "SELECT*FROM`bs_php_kalei`WHERE`lei_id`='$ida'";
    $arr = $this->db->Bsphp_my_array($sql);
    if (!$arr) $this->alert('你所要购买的充值卡类型不存在', 'space.php?do=ecshop&action=ka');
    $zhe = $User_Inof['user_Zhe'];

    $ka_jiage = $arr['lei_jiage'];
    if($ka_jiage==-1) $this->alert('充值卡不存在', $url);
    
    
    
    $ka_date = $arr['lei_date']; //类的充值卡的 天数
    $ka_name = $arr['lei_name'];
    $daihao = $arr['lei_daihao'];

    /****************获取软件名称*/
    $app_name = $this->db->my_class_array('bs_php_appinfo', 'app_daihao', $daihao, '`app_name`');
    /*开始算价格*/
    if ($zhe > 0) { //判断该账户有折扣
      $zhe = $zhe / 10; //获取百分比
      $jiazhe = $zhe * $ka_jiage; //打折后的实价
      $jia = $ka_jiage - $jiazhe; //打折后的优惠
      $jia = $jia * $shu; //优惠金额
      $shijia = $jiazhe * $shu; //实付款
    } else {
      $shijia = $ka_jiage * $shu; //实付款 没有优惠
      $jia = '0';
    }
    /*开始结算账单*/


    //***************************按钮提交
    $tbaalipay = set_post('tbaalipay');
    if ($tbaalipay) {
      $ail_id = date('YmdHis', HOST_UNIX).rand(1000, 9999); //订单号
      $ka_kate = HOST_DATE;

      $pay_qian_rmb = $User_Inof['user_rmb'];
      $pay_rmb = $pay_qian_rmb - $shijia;
      if ($shijia > $pay_qian_rmb) $this->alert('你的账户余额不足,支付失败请充值！', 'index.php?m=user&c=index&a=space&do=pay&act=pay');
      $pay_zhe = $User_Inof['user_Zhe'];
      /*   数据库字段说明
      `pay_id` ,          订单代号  
      `ali_id` ,   
      `pay_uid` ,          用户UID
      `pay_date` ,         添加时间
      `pay_qian_rmb` ,     购买前账号金额
      `pay_rmb` ,          购买后账号金额
      `pay_ka_shuliang` ,  购买数量
      `pay_zhuangtai` ,    订单状态 0 成功  1=已经付款  2没发卡
      `ka_shijia` ,        打折后价格
      `ka_zhe_jia` ,       优惠价格
      `ali_ka_name` ,      卡类名称
      `ali_ka_jiage` ,     卡的单价
      `ali_ka_zhe` ,       购买折扣
      `ali_ka_date` ,      卡的天数
      `pay_daihao`         所属软件代号
      `pay_app_name`       软件名称
      
      */


      $sql = "INSERT INTO`bs_php_pay_log`VALUES('$ail_id',NULL,'$this->seesion_uid','$ka_kate','$pay_qian_rmb','$pay_rmb','$shu','0','$shijia','$jia','$ka_name','$ka_jiage','$pay_zhe','$ka_date','$daihao','$app_name','{$arr['lei_type']}');";
      if ($this->db->Bsphp_my_query($sql)) {
        $sql = "UPDATE`bs_php_user`SET`user_rmb`='$pay_rmb'WHERE`bs_php_user`.`user_uid`=\"$this->seesion_uid\";";
        if ($this->db->Bsphp_my_query($sql)) {

           //引入文件
            bs_lib::load_modules_common('applib', 'makecard');
  
         
          $ka_tmp = ZhiZuoC($shu, $ida, $user_array['user_user']);


          //*****************支付成功跳转到令卡页面
          if ($ka_tmp) {
            
            
            
       /** ---------------------执行佣金推广开始-----------------*/
        
        //读取邀请配置
        //$COMFIGARR=$this->db->my_class_array('bs_php_user_yaoqing_ini','ini','yaoini',' * ');
        
          //替代之前数据库的   
        $COMFIGARR['you_weizi']=bs_lib::get_configs_value('extension','you_weizi');
        $COMFIGARR['ticheng_oof']=bs_lib::get_configs_value('extension','ticheng_oof');
        $COMFIGARR['ticheng_baifenbi_1']=bs_lib::get_configs_value('extension','ticheng_baifenbi_1');
        $COMFIGARR['ticheng_baifenbi_2']=bs_lib::get_configs_value('extension','ticheng_baifenbi_2');
        $COMFIGARR['ticheng_baifenbi_3']=bs_lib::get_configs_value('extension','ticheng_baifenbi_3');
        $COMFIGARR['ticheng_time']=bs_lib::get_configs_value('extension','ticheng_baifenbi_time');
        
        

        //you_weizi==0 表示在充值时候获得佣金
        
        //频道开放模式
        $Yao_ini_off=$COMFIGARR['ticheng_oof'];
        
        if($COMFIGARR['you_weizi']==1 & $Yao_ini_off!='0'){
            
            //用户RMB金额
            $User_RMB=$shijia;
            //上级用户帐号
            $User_Yao_User = $User_Inof['user_yao_User'];
            

          
          //--------------------3级计算--------------------------------------------------------
          
            //用户提成佣金计算
            $ticheng_baifenbi_1=$COMFIGARR['ticheng_baifenbi_1']/100;
            $ticheng_baifenbi_2=$COMFIGARR['ticheng_baifenbi_2']/100;
            $ticheng_baifenbi_3=$COMFIGARR['ticheng_baifenbi_3']/100;
            
            
            /** 一级代理 */
            if($ticheng_baifenbi_1>0){
                
                //计算分红
                $User_RMB_A=$User_RMB*$ticheng_baifenbi_1;
                //读取上级用户UID
                $User_Arr_A=$this->db->my_class_array('bs_php_user','user_uid',$User_Yao_User,'`user_uid`,`user_user`,`user_yao_User`,`user_daili`,`user_re_date`');
                if($User_Arr_A){
                  
                      //计算一级提成金额
                      $sql_A="UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$User_RMB_A'WHERE`bs_php_user`.`user_user`='{$User_Arr_A['user_user']}';";
                      //计算注册时间与是否可以提成
                      $yoa_User_date=strtotime($User_Arr_A['user_re_date']);
                      $yoa_Today_date=3600*24*$COMFIGARR['ticheng_time'];
                      $yoa_User_date=$yoa_User_date+$yoa_Today_date;
                      //执行SQL
                      //echo date('Y-m-d H:i:s',$yoa_User_date).'-'.$yoa_User_date.'-'.HOST_UNIX.'<BR/>';
                      if($Yao_ini_off=='2' & $User_Arr_A['user_daili']=='1' & $yoa_User_date>HOST_UNIX or $Yao_ini_off=='1' & $yoa_User_date>HOST_UNIX){
                        if($this->db->Bsphp_my_query($sql_A)) bs_lib::addappenlog('yao_money_log',"你的下线《A》级用户消费获得:$User_RMB_A 佣金",$User_Arr_A['user_user']);
                          
                      }  
                }
            }
                             
 
            
            
            /** 二级代理 */
            if($ticheng_baifenbi_2>0){
                //计算分红
                $User_RMB_B=$User_RMB*$ticheng_baifenbi_2;
                //读取上级用户UID
                $User_Arr_B=$this->db->my_class_array('bs_php_user','user_uid',$User_Arr_A['user_yao_User'],'`user_uid`,`user_user`,`user_yao_User`,`user_daili`,`user_re_date`');
                if($User_Arr_B){
                      //计算一级提成金额
                      $sql_B="UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$User_RMB_B'WHERE`bs_php_user`.`user_uid`='{$User_Arr_B['user_uid']}';";
                      //计算注册时间与是否可以提成
                      $yoa_User_date=strtotime($User_Arr_B['user_re_date']);
                      $yoa_Today_date=3600*24*$COMFIGARR['ticheng_time'];
                      $yoa_User_date=$yoa_User_date+$yoa_Today_date;
                      //执行SQL
                      
                      if($Yao_ini_off=='2' & $User_Arr_B['user_daili']=='1' & $yoa_User_date>HOST_UNIX OR $Yao_ini_off=='1' & $yoa_User_date > HOST_UNIX){
                        //echo date('Y-m-d H:i:s',$yoa_User_date).'-'.$yoa_User_date.'-'.HOST_UNIX.'二级代理<BR/>';
                        if($this->db->Bsphp_my_query($sql_B)) bs_lib::addappenlog('yao_money_log',"你的下线《B》级用户消费获得:$User_RMB_B 佣金",$User_Arr_B['user_user']);
                      }  
                }
            }
            
            
            
            
            
            /** 三级代理 */
            if($ticheng_baifenbi_3>0){
                //计算分红
                $User_RMB_C=$User_RMB*$ticheng_baifenbi_3;
                //读取上级用户UID
                $User_Arr_C=$this->db->my_class_array('bs_php_user','user_uid',$User_Arr_B['user_yao_User'],'`user_uid`,`user_user`,`user_yao_User`,`user_daili`,`user_re_date`');
                if($User_Arr_C){
                      //计算一级提成金额
                      $sql_C="UPDATE`bs_php_user`SET`user_rmb`=`user_rmb`+'$User_RMB_C'WHERE`bs_php_user`.`user_uid`='{$User_Arr_C['user_uid']}';";
                      //计算注册时间与是否可以提成
                      $yoa_User_date=strtotime($User_Arr_C['user_re_date']);
                      $yoa_Today_date=3600*24*$COMFIGARR['ticheng_time'];
                      $yoa_User_date=$yoa_User_date+$yoa_Today_date;
                      //执行SQL
                      //echo date('Y-m-d H:i:s',$yoa_User_date).'-'.$yoa_User_date.'-'.HOST_UNIX.'<BR/>';
                      if($Yao_ini_off=='2' & $User_Arr_C['user_daili']=='1'& $yoa_User_date > HOST_UNIX or $Yao_ini_off=='1'& $yoa_User_date>HOST_UNIX){
                        if($this->db->Bsphp_my_query($sql_C)) bs_lib::addappenlog('yao_money_log',"你的下线《C》级用户消费获得:$User_RMB_C 佣金",$User_Arr_C['user_user']);
                      }  
                }
            }

            
        //-------------------3级接收
        
        }//end   

        /** --------------------------------------------------- 邀请推广结束*/
            
        
             
          $this->alert('支付成功,正在为你跳转到取卡页。！', 'index.php?m=user&c=index&a=space&do=ecshop&action=pay&payid='.$ail_id);

         

          } else {
            //***************************设置状态为2 表示没有制作充值卡
            $sql = "UPDATE`bs_php_pay_log`SET`pay_zhuangtai`='2'WHERE`pay_ka_log`.`pay_id`=\"$ail_id\";";
            $this->db->Bsphp_my_query($sql);
            bs_lib::addappenlog('exit_log',"[错误号:80084][严重]用户付款制卡失败,请及时处理",$User);
            $this->alert('跳转失败,请到订单管理查看充值卡', 'index.php?m=user&c=index&a=space&do=ecshop&action=ka');
          }
        
        
        
        } else {
          //*************************设置状态为1 表示支付金额失败
          $sql = "UPDATE`bs_php_pay_log`SET`pay_zhuangtai`='1'WHERE`pay_ka_log`.`pay_id`=\"$ail_id\";";
          $this->db->Bsphp_my_query($sql);
          bs_lib::addappenlog('exit_log',"[错误号:80083]用户购卡付款失败",$User);
          $this->alert('支付金额失败,请重试！', 'index.php?m=user&c=index&a=space&do=ecshop&action=ka');
        }

      } else {
        bs_lib::addappenlog('exit_log',"[错误号:80082]在线购卡下单失败",$User);
        $this->alert('在线下单失败,请重试。！', 'index.php?m=user&c=index&a=space&do=ecshop&action=ka');
      }


    }
    //else{ $this->alert('下单失败！','space.php?do=ecshop&action=ka'); }
    /*开始结算结束*/


    if ($zhe <= 0) $zhe_x = '-';
    else  $zhe_x = $User_Inof['user_Zhe'].'折';


 $ABC=$carsys_arr[$arr['lei_type']];
    print <<< EOT
<form id="form2" name="form2" method="post" action="">

  <table width="100%"  cellpadding="0" cellspacing="0" bordercolor="#DDDDDD">
    <tr   >
      <td   width="100" height="32" nowrap="nowrap" bgcolor="#E8F2FF" class="ta"><div align="center">商品名称</div></td>
      <td   width="120" nowrap="nowrap" bgcolor="#E8F2FF"  class="ta"><div align="center">所属软件</div></td>
      <td   width="65" nowrap="nowrap" bgcolor="#E8F2FF"  class="ta"><div align="center">数量(张)</div></td>
      <td   width="94" nowrap="nowrap" bgcolor="#E8F2FF"  class="ta"><div align="center">时间</div></td>
      <td  width="106" nowrap="nowrap" bgcolor="#E8F2FF"   class="ta"><div align="center">单价(元)</div></td>
      <td   width="70" nowrap="nowrap" bgcolor="#E8F2FF"  class="ta"><div align="center">折扣</div></td>
      <td   width="100" nowrap="nowrap" bgcolor="#E8F2FF"  class="ta"><div align="center">优惠(元)</div></td>
      <td   width="100" nowrap="nowrap" bgcolor="#E8F2FF"  class="ta"><div align="center">总价(元)</div></td>
    </tr>
    <tr>
      <td   height="42" nowrap="nowrap" bgcolor="#F0F0F0"   class="ta"><div align="center">
          {$ka_name}
      &nbsp;</div></td>
      <td nowrap="nowrap"   bgcolor="#F0F0F0"   class="ta"><div align="center">
          {$app_name}
      </div></td>
      <td nowrap="nowrap"   bgcolor="#F0F0F0"   class="ta"><div align="center">
          {$shu}
      </div></td>
      <td nowrap="nowrap"   bgcolor="#F0F0F0"   class="ta"><div align="center">
          {$ka_date}{$ABC}
      </div></td>
      <td nowrap="nowrap"   bgcolor="#F0F0F0"   class="ta"><div align="center">
         {$arr['lei_jiage']}
      </div></td>
      <td nowrap="nowrap"   bgcolor="#F0F0F0"   class="ta"><div align="center">
          {$zhe_x}
      </div></td>
      <td nowrap="nowrap"   bgcolor="#F0F0F0"   class="ta"><div align="center">
         {$jia}
      </div></td>
      <td nowrap="nowrap"   bgcolor="#F0F0F0"   class="ta"><div align="center">
          <label></label>
          <span class="STYLE6">
         {$shijia}
      </span></div></td>
    </tr>

    <tr>
      <td height="25"></td>
      <td height="25">&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td colspan="2"><br />
        实在付款:￥<span class="STYLE6">
       {$shijia}
        </span></td>
    </tr>
    <tr>
      <td  height="25">&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td colspan="2"><br />
        <label>账户余额:￥<span class="STYLE6">
        {$User_Inof['user_rmb']}
        </span></label></td>
    </tr>
    <tr>
      <td border="1" height="25">&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td><br /></td>
      <td><label>
        <input name="tbaalipay" type="submit" id="tbaalipay" class="submit"  value="确认付款" />
        </label></td>
    </tr>
  </table>
  <p> 点击付款后请勿刷新,防址重复提交</P>
  <input name="kaid" type="hidden" id="kaid" value="{$ida}" />
  <input name="a" type="hidden" id="a" value="{$shu}" />
</form>
<p>&nbsp;</p>
EOT;


    //最后定的显示
  } elseif ($action == 'pay') {

    // $arr_app = $this->db->API_get_arrapp();
    //if($arr_app=='')$this->alert('查询失败,URL超时！', 'space.php?do=gg');
    $pay_id = set_get('payid');
    ;

    $arrok = $this->db->my_class_array('bs_php_pay_log', 'pay_id', $pay_id, ' * ');
    ///************************查询定的是否存在
    if (!$arrok) $this->alert('订单查询失败,该订单已经可能不存在了！', 'space.php?do=gg');
    //存在定性是否自己的,别人的话就关闭
    if ($arrok['pay_uid'] != $this->seesion_uid) $this->alert('你查询的订单不存在！', 'space.php?do=gg');
    //获取软件名称
    $app_name = $this->db->my_class_array('bs_php_appinfo', 'app_daihao', $arrok['pay_daihao'], '`app_name`');


print <<< EOT
<table width="100%"  cellpadding="0" cellspacing="0" bordercolor="#DDDDDD">
  <tr   >
    <td class="ta" height="27" bgcolor="#FFFFFF"><div align="center" class="STYLE8">
        <div align="right">订单号:</div>
      </div></td>
    <td colspan="2" bgcolor="#FFFFFF"  class="ta"><div align="left">{$arrok['pay_id']}</div></td>
    <td bgcolor="#FFFFFF"  class="th"><div align="center" class="STYLE8">
        <div align="right">时间:</div>
      </div></td>
    <td colspan="4" bgcolor="#FFFFFF"   class="ta"><div align="left">{$arrok['pay_date']}</div></td>
  </tr>
  <tr   >
    <td class="ta"   width="101" height="32" bgcolor="#E8F2FF"><div align="center">商品名称</div></td>
    <td  class="ta"   width="150" bgcolor="#E8F2FF"><div align="center">所属软件</div></td>
    <td   width="80" bgcolor="#E8F2FF"  class="ta"><div align="center">数量(张)</div></td>
    <td   width="94" bgcolor="#E8F2FF"  class="ta"><div align="center">时间</div></td>
    <td   class="ta"  width="106" bgcolor="#E8F2FF"><div align="center">单价(元)</div></td>
    <td  class="ta"   width="50" bgcolor="#E8F2FF"><div align="center">折扣</div></td>
    <td  class="ta"   width="146" bgcolor="#E8F2FF"><div align="center">优惠(元)</div></td>
    <td  class="ta"   width="100" bgcolor="#E8F2FF"><div align="center">总价(元)</div></td>
  </tr>
  <tr>
    <td   class="ta"   height="42" bgcolor="#F0F0F0"><div align="center">{$arrok['ali_ka_name']}&nbsp;</div></td>
    <td   class="ta"   bgcolor="#F0F0F0"><div align="center">{$app_name}</div></td>
    <td   bgcolor="#F0F0F0"   class="ta"><div align="center">{$arrok['pay_ka_shuliang']}</div></td>
    <td   bgcolor="#F0F0F0"   class="ta"><div align="center">{$arrok['ali_ka_date']}{$carsys_arr[$arrok['pay_type']]}</div></td>
    <td   class="ta"   bgcolor="#F0F0F0"><div align="center">{$arrok['ali_ka_jiage']}</div></td>
    <td   class="ta"   bgcolor="#F0F0F0"><div align="center">{$arrok['ali_ka_zhe']}</div></td>
    <td   class="ta"   bgcolor="#F0F0F0"><div align="center">{$arrok['ka_zhe_jia']}</div></td>
    <td   class="ta"   bgcolor="#F0F0F0"><div align="center">{$arrok['ka_shijia']}</div></td>
  </tr>
  <tr>
    <td><p>&nbsp;</p></td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td height="25" colspan="4">本次交易的卡号密码 </td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td colspan="2"><br /></td>
  </tr>
  <tr>
    <td colspan="8"><br />

EOT;


    $leijia = '';
    $arrok = $this->db->my_class_array('bs_php_pay_log', 'pay_id', $pay_id, ' * ');
    $date = $arrok['pay_date'];
    $sql = "SELECT*FROM`bs_php_cardseries`WHERE`car_reDATE`='$date'AND`car_admin`='{$user_array['user_user']}';";

    $tmp = $this->db->Bsphp_my_query($sql);
    $id = 0;
    while (@$ka = mysqli_fetch_array($tmp)) {
      $id++;
      
      echo '<input  class="test"  size="100" name="textfield'.$id.'" id="textfield'.$id.'" type="text" value="';
      $katxt = '充值软件:'.$app_name.' 时间:'.$ka['car_TianShu'].$carsys_arr[$ka['car_type']].' 卡号:'.$ka['car_name'];
      if($ka['car_pwd']!=NULL)echo ' 密码:'.$ka['car_pwd'];
      $leijia .= $katxt."\n";
      echo $katxt;

      echo '" />';
      
      echo " <input  class=\"submits\"  type=\"submit\" name=\"Submit2\" value=\"复制\" onclick=\"copyCode('textfield$id');return false;\" />";
      echo ' &nbsp;&nbsp; <a href="index.php?m=user&c=index&a=space&do=caradd&ka='.$ka['car_name'].'&mi='.$ka['car_pwd'].'">立即充值</a>';
    }


?>
      <br />
      <br />
	  <br />
      
      <input name="hiddenFieldA" id="hiddenFieldA"  class="test"  type="hidden" value="<?php


    echo $leijia


?>" />
      <input type="submit" name="Submit2"  class="submit"  value="复制全部卡串" onclick="copyCode('hiddenFieldA');return false;" />
      <p>&nbsp;</p>
      

      </td>
  </tr>
</table>
<?PHP


  }


?>
