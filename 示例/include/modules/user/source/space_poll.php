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

$tav='';
$url=url();

if(isset($_GET['tav'])) $tav = $_GET['tav'];

if ($tav == 'usb')
{
    $key_id = $_GET['key_id'];
    $arr = @$db->API_query_login('bs_php_key', 'key_id', $key_id);

    if ($arr['key_uid'] != uid)
        die(user_at('KEY 非法操作,请返回！', 'space.php'));
    if ($arr == false)
        echo '<meta http-equiv="refresh" content="0;URL=space.php?do=poll" />';

    if (isset($_POST['add']))
    {
        /*------------------------*/
        $vip_key_usb = trim($_POST['vip_key']); //获取用户更改KEY
        if ($vip_key_usb == '')
            user_at('新更改绑定KEY不能为空,请输入要修改新的KEY！', $url); //KEY空 提示
        $keyid = $_POST['hiddenField']; //KEY_编码ID
        //$usb_date=$arr_app[]
        $arr_key = @$db->API_query_login('bs_php_key', 'key_id', $keyid);
        $array_app = @$db->API_query_login('bs_php_cc', 'cc_daihao', $arr_key['key_daihao']);
        if ($arr_key['key_uid'] != uid)
            die(user_at('KEY 非法操作,请返回！', 'space.php'));
        //print_r($arr);
        $date_j = $array_app['good_jget'];
        if ($arr_key['key'] == $vip_key_usb)
            die(user_at('原KEY和新绑定KEY一样的！,无法修改。', $url)); //KEY一样 提示
        if ($cl_date->compare_date($arr_key['key_vip_date'])!=1)
            die(user_at('到期了！  该KEY已经到期无法更改绑定,请续费！', $url));
        $date = $cl_date->minus_date($arr_key['key_vip_date'], $date_j);
        if ($cl_date->compare_date($date_j))
            die(user_at('对不起,该账号如果解除绑定将到期,无法操作。请先续费！', $url)); //更换后不将到期
            /*检测完毕开始修改*/
            $tmp = $db->API_xiugai_key($arr_key['key_id'], $vip_key_usb, $date);
            if ($tmp)die(user_at('KEY修改成功！  已经从使用时间扣除'.$date_j.'小时<br>KEY新到期时间:'. $date, 'space.php?do=poll')); 
			die(user_at('修改KEY失败！ 500错误！'. $date, $url));

    }

?>
<style type="text/css">
<!--
.k {
	padding-right: 2px;
	padding-bottom: 2px;
}
.STYLE1 {color: #FF0000}
.STYLE2 {color: #2C629E}
-->
</style>
<form id="form2" name="form2" method="post" action="">
<table width="462" border="0">
  <tr>
    <td nowrap="nowrap"><div align="right" class="STYLE2">软件名称:</div></td>
    <td nowrap="nowrap"><span class="STYLE2"><?php

    echo $arr_app[$arr['key_daihao']][1];

?></span></td>
    <td nowrap="nowrap"><span class="STYLE2">
      <input name="hiddenField" type="hidden" value="<?php

    echo $key_id

?>" />
    </span></td>
  </tr>
  <tr>
    <td width="67" nowrap="nowrap"><div align="right" class="STYLE2">改绑扣时:</div></td>
    <td width="114" nowrap="nowrap"><span class="STYLE2"><?php

    echo $arr_app[$arr['key_daihao']][2];

?>小时</span></td>
    <td width="259" nowrap="nowrap"><span class="STYLE2">
      <input type="hidden" name="hiddenField2" />
    </span></td>
  </tr>
  <tr>
    <td nowrap="nowrap"><div align="right" class="STYLE2">原来的KEY:</div></td>
    <td nowrap="nowrap"><span class="STYLE2"><?php

    echo $arr['key'];

?></span></td>
    <td nowrap="nowrap"><span class="STYLE2"></span></td>
  </tr>
  <tr>
    <td nowrap="nowrap"><div align="right" class="STYLE2">新修改KEY:</div></td>
    <td nowrap="nowrap"><input name="vip_key" type="text" id="vip_key" size="50" /></td>
    <td nowrap="nowrap"><span class="STYLE1">*请把头尾空格删除</span></td>
  </tr>
  <tr>
    <th nowrap="nowrap"><div align="right"><span class="STYLE2"></span></div></th>
    <td nowrap="nowrap"><span class="STYLE2">
      <label><br />
        <input name="add" type="submit" id="add"  onclick="return confirm('确定要更改绑定吗？\n\n 更改成功后将扣除:<?php

    echo $arr_app[$arr['key_daihao']][2];

?>小时使用时间')" value="保存更改"/>
        <br />
        </label>
    </span></td>
    <td nowrap="nowrap"><span class="STYLE2"></span></td>
  </tr>
</table>

</form>
<?php

} else
{

    $tmp = $db->API_query_appf('bs_php_key', 0, 500, '`key_uid`="' . uid . '"');

?>

<style type="text/css">
<!--
.STYLE1 {color: #B4C6DB}
.aq {
	padding-left: 5px;
	color: #2C629E;
	padding-right: 5px;
}
-->
</style>

<form id="form1" name="form1" method="post" action="">
<table width="100%" height="60" border="0" cellpadding="0" cellspacing="0" bordercolor="#CCCCCC"  onmouseover="changeto()"  onmouseout="changeback()">
  <tr>
    <td height="28" nowrap="nowrap" bgcolor="#F7F7F7"><div align="center"><span class="STYLE1">&nbsp;</span> </div></td>
    <td width="7%" nowrap="nowrap" bgcolor="#F7F7F7" class="STYLE1"><div align="center"><span class="STYLE1"></span>所属软件</div></td>
    <td width="13%" nowrap="NOWRAP" bgcolor="#F7F7F7" class="STYLE1"><div align="center"><span class="STYLE1"></span>更变扣时</div></td>
    <td width="8%" nowrap="nowrap" bgcolor="#F7F7F7" class="STYLE1"><div align="center"><span class="STYLE1"></span>到期时间 &nbsp; </div></td>
    <td width="54%" nowrap="nowrap" bgcolor="#F7F7F7" class="STYLE1"><div align="center"><span class="STYLE1"></span>绑定KEY</div></td>
    <td width="13%" nowrap="nowrap" bgcolor="#F7F7F7" class="STYLE1"><div align="center"><span class="STYLE1"></span>操作</div></td>
  </tr>
  <?php

    $i = 0;
    while ($gg = @mysqli_fetch_array($tmp))
    {
	if($arr_app[$gg['key_daihao']][3] ==0){
        $i++;

?>
  <tr>
    <td width="5%" height="32" nowrap="nowrap" class="aq"><div align="center"><?php

        echo $i

?></div></td>
    <td nowrap="nowrap" class="aq"><div align="center"><?php

        echo $arr_app[$gg['key_daihao']][1];

?></div></td>
    <td nowrap="nowrap" class="aq"><div align="center"><?php

        echo $arr_app[$gg['key_daihao']][2];

?>小时</div></td>
    <td nowrap="nowrap" class="aq"><div align="center"><?php

        echo $gg['key_vip_date']

?></div></td>
    <td nowrap="nowrap" class="aq"><div align="center"><?php

        echo $gg['key']

?></div></td>
    <td nowrap="nowrap" class="aq"><div align="center"><a href="space.php?do=poll&amp;amp&amp;tav=usb&amp;key_id=<?php

 echo $gg['key_id']

?>">修改</a> <a href="space.php?do=default">续期</a></div></td>
  </tr>
  <?php
}
    }

?>
</table>








<?php

    if ($i == 0)
        echo '你还没有使用过软件呢！ 使用了会看到机器码。';

}

?>

</form>


