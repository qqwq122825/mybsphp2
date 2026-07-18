
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>代理商管理平台 by Bsphp</title>
<link rel="stylesheet" href="<?php echo get_url_statics() ?>images/table.css" type="text/css" />
<script language="javascript" type="text/javascript" src="<?php echo get_url_statics() ?>images/table.js"></script>
<script type="text/javascript" src="<?php echo get_url_statics() ?>images/popup.js"></script>
<!--
<?php

$get_url_statics=get_url_statics();


print <<< EOT
-->
<style type="text/css">
<!--
.STYLE3 {
	color: #C0DE98;
	font-size: 12px;
}
.STYLE4 {font-size: 12px}
-->
</style>
</head>
<body>
<BR>
<div>
  <form name="form1" method="get" action=""  >
  <label class="sosodiv"> 
    <div align="center" class="sosodiv"><span class="STYLE4"><strong>搜搜字段：</strong></span>
      <select name="soso_id" class="txt" id="soso_id">
        <option value="1">用户账号</option>
      </select>
      <span class="STYLE4">搜搜内容:      </span>
      <input name="soso" type="text" class="txt" id="soso" value="$soso">
      <input name="soso_ok" type="submit" class="soso_ok" value="搜搜">
	  
	  
      <input name="act" type="hidden" id="act" value="table">
      <input name="daihao" type="hidden" id="daihao" value="{$daihao}">
	  
  <input name="m" type="hidden" id="m" value="{$_GET['m']}">
  <input name="c" type="hidden" id="c" value="{$_GET['c']}">
  <input name="a" type="hidden" id="a" value="{$_GET['a']}">
	  
	  
	  
    </div>
    </label>
  </form>
</div>
<form name="form" method="post"  onClick="return allcheckbox();"  action="">
  <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
    <tr>
      <td height="30"><table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="15" height="30"><img src="{$get_url_statics}images/tab_03.gif" width="15" height="30" /></td>
          <td  background="{$get_url_statics}images/tab_05.gif"><img src="{$get_url_statics}images/311.gif" width="16" height="16" /> <span class="STYLE4">我的代理商列表
              <label></label>
              </div><input name="all" type="hidden" id="all">
          </span></td>
          <td width="317" background="{$get_url_statics}images/tab_05.gif"><table border="0" align="right" cellpadding="0" cellspacing="0">
            <tr>
              <td width="52"><table width="88%" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td class="STYLE1"><div align="center"><img src="{$get_url_statics}images/001.gif" width="14" height="14" /></div></td>
                    <td nowrap class="STYLE1"><div align="center"><a href="index.php?m=agent&c=sp&a=useradd"   >添加</a></div></td>
                  </tr>
              </table></td>
            </tr>
          </table></td>
          <td width="14"><img src="{$get_url_statics}images/tab_07.gif" width="14" height="30" /></td>
        </tr>
      </table></td>
    </tr>
    <tr>
      <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="9" background="{$get_url_statics}images/tab_12.gif"></td>
            <td bgcolor="#f3ffe3"><table width="99%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#c0de98" onMouseOver="changeto()"  onmouseout="changeback()">
              <tr>
                <td width="5%" height="26" nowrap background="{$get_url_statics}images/tab_14.gif" class="STYLE1"><div align="center" class="STYLE2">&nbsp;选择&nbsp;</div></td>
                <td height="26" background="{$get_url_statics}images/tab_14.gif" class="STYLE1"><div align="center" class="STYLE2">账号</div></td>
                <td background="{$get_url_statics}images/tab_14.gif" class="STYLE1"><div align="center" class="STYLE2">状态</div></td>
                <td background="{$get_url_statics}images/tab_14.gif" class="STYLE1"><div align="center" class="STYLE2">点数</div></td>
				
                <td width="8%" background="{$get_url_statics}images/tab_14.gif" class="STYLE1"><div align="center" class="STYLE2">折扣</div></td>
                <td height="26" background="{$get_url_statics}images/tab_14.gif" class="STYLE1"><div align="center" class="STYLE2">添加时间</div></td>
                <td nowrap background="{$get_url_statics}images/tab_14.gif" class="STYLE1"><div align="center" class="STYLE2">&nbsp;最后登录&nbsp;</div></td>
                </tr>
			  <!--
EOT;


             while ($array_value= mysqli_fetch_array($db_array_value ))
            {
			$zhuangtai=$array_value['user_IsLock'];
			$zhuangtai==1?$zhuangtai='<span  style="color: #FF9900">冻结</span>':$zhuangtai='正常';
			 
			if($array_value['user_Login_date']=='')$array_value['user_Login_date']='--';
			
			if($array_value['user_re_date']=='')$array_value['user_re_date']='--';
		
			$array_value['user_re_date']=show_Time_Day($array_value['user_re_date']);
		
			$array_value['user_Login_date']=show_Time_Day($array_value['user_Login_date']);
			
			
			if($array_value['car_BaoJi']!=1){
			$url_img='nop';
			}else{
			$url_img='nem';
			}
			
			
		
                print <<< EOT
-->
		
              <tr>
                <td height="18" nowrap bgcolor="#FFFFFF"><div align="center" class="STYLE1">
                    <input name="cm" type="checkbox" class="STYLE2" id="cm" value="{$array_value[0]}" />
                </div></td>
                <td height="18" nowrap bgcolor="#FFFFFF" class="STYLE2"><div align="center" class="STYLE2 STYLE1" >&nbsp;{$array_value['user_user']}&nbsp;</div></td>
                <td nowrap bgcolor="#FFFFFF"><div align="center" class="STYLE2 STYLE1">&nbsp;{$zhuangtai}&nbsp;</div></td>
                <td nowrap bgcolor="#FFFFFF"><div align="center" class="STYLE2 STYLE1">
                  <div align="center"><span class="STYLE1">&nbsp;{$array_value['user_rmb']} [<a href="index.php?m=agent&c=sp&a=money&zhuanguser={$array_value['user_user']}">加/减点</a>]&nbsp;</span></div>
                </div></td>
                <td nowrap bgcolor="#FFFFFF"><div align="center" ><span class="STYLE1"><span class="STYLE2 STYLE1">&nbsp;{$array_value['user_Zhe']} [<a href="index.php?m=agent&c=sp&a=zhe&users={$array_value['user_user']}">设置</a>]&nbsp;</span></span></div></td>
                <td height="18" nowrap bgcolor="#FFFFFF"><div align="center" class="STYLE2 STYLE1">{$array_value['user_re_date']}</div></td>
                <td bgcolor="#FFFFFF"><div align="center" class="STYLE1"><span class="STYLE2 STYLE1">&nbsp;{$array_value['user_Login_date']}&nbsp;</span></div></td>
                </tr>
<!--		  
EOT;
            }

            print <<< EOT
-->
            </table></td>
            <td width="9" background="{$get_url_statics}images/tab_16.gif"></td>
          </tr>
        </table></td>
    </tr>
    <tr>
      <td height="29"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="15" height="29"><img src="{$get_url_statics}images/tab_20.gif" width="15" height="29" /></td>
            <td background="{$get_url_statics}images/tab_21.gif"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="25%" height="29" nowrap="nowrap"><span class="STYLE1">共{$zongshu}条纪录，当前第{$FANYE}/{$zongyue}页，每{$shu}条纪录</span></td>
				 
                  <td width="75%" valign="top" class="STYLE1"><div align="right">
                      <table width="352" height="20" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td width="62" height="22" valign="middle"><div align="right"><img src="{$get_url_statics}images/first.gif" width="37" height="15" border="0" usemap="#Map" /></div></td>
                          <td width="50" height="22" valign="middle"><div align="right"><img src="{$get_url_statics}images/back.gif" width="43" height="15" border="0" usemap="#Map2" /></div></td>
                          <td width="54" height="22" valign="middle"><div align="right"><img src="{$get_url_statics}images/next.gif" width="43" height="15" border="0" usemap="#Map3" /></div></td>
                          <td width="49" height="22" valign="middle"><div align="right"><img src="{$get_url_statics}images/last.gif" width="37" height="15" border="0" usemap="#Map4" /></div></td>
                          <td width="59" height="22" valign="middle"><div align="right" class="STYLE1">转到第</div></td>
                          <td width="25" height="22" valign="middle"><span class="STYLE7">
                            <input name="id" type="text" class="STYLE1" id="id" style="height:15px; width:25px;" size="5" />
                            </span></td>
                          <td width="23" height="22" valign="middle" class="STYLE1">页</td>
                          <td width="30" height="22" valign="middle"><img src="{$get_url_statics}images/go.gif" width="37" height="15" border="0" usemap="#Map5" /></td>
                        </tr>
                      </table>
                    </div></td>
                </tr>
              </table></td>
            <td width="14"><img src="{$get_url_statics}images/tab_22.gif" width="14" height="29" /></td>
          </tr>
        </table></td>
    </tr>
  </table>
</form>





<form name="fan" method="get" action=""   onClick="return fanye();"  style="display:none" >
  <input name="id" type="hidden" id="id">
  
    <input name="act" type="hidden" id="act" value="table">
	<input name="soso_id" type="hidden" id="soso_id" value="$soso_id">
	<input name="soso" type="hidden" id="soso" value="$soso">
	<input name="daihao" type="hidden" id="daihao" value="{$daihao}">
  <label>
  <input name="fan" type="submit" id="submit" value="0" style="display:none">
  
  </label>
</form>

<map name="Map"><area shape="rect" coords="0,0,38,15" href="{$url}1"></map>
<map name="Map2"><area shape="rect" coords="0,0,43,15"   $atb href="$url$JIAN">
</map>
<map name="Map3"><area shape="rect" coords="0,0,43,15"   $ata  href="$url$JIA">
</map>
<map name="Map4"><area shape="rect" coords="0,0,38,15" href="$url$zongyue">
</map>
<map name="Map5"><area shape="rect" coords="0,0,38,15" href="javascript:fan.fan.click();" >
</map>

</body>
</html>
<!--
EOT;
?>
-->