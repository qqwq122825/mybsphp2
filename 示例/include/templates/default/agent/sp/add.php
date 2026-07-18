

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="<?php echo get_url_statics() ?>css/admin_center.css" type="text/css" />


<!--
<?php

$get_url_statics=get_url_statics();
print <<< EOT


-->
<title>制卡</title>
<style type="text/css">
<!--
#form1 textarea {
	height: 300px;
	width: 99%;
}
.STYLE4 {
	font-size: 13px;
	font-weight: bold;
	color: #1F4A65;
}
-->
</style>
</head>
<body>
<label></label>
 <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td height="30"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="15" height="30"><img src="{$get_url_statics}images/main_01.gif" width="15" height="30" /></td>
        <td background="{$get_url_statics}images/main_02.gif"><img src="{$get_url_statics}images/center_ico01.gif" width="16" height="16" /><span class=""><span class="STYLE4">制作充值卡</span></span></td>
        <td width="281" background="{$get_url_statics}images/main_02.gif"><table border="0" align="right" cellpadding="0" cellspacing="0">
        </table></td>
        <td width="14"><img src="{$get_url_statics}images/main_03.gif" width="14" height="30" /></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="9" background="{$get_url_statics}images/main_04.gif">&nbsp;</td>
        <td bgcolor="#f3ffe3"><table width="99%" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#c0de98">
          <tr>
            <td width="100%" height="100%" bgcolor="#FFFFFF" ><form id="form1" name="form1" method="post" action="">
            
				<table width="396" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="59">&nbsp;</td>
                    <td width="201">&nbsp;</td>
                    <td width="136">&nbsp;</td>
                  </tr>
                  <tr>
                    <td nowrap class="STYLE2">制作类型:</td>
                    <td nowrap>                      <select name="select" class="STYLE4" id="select" >
					
                      <option value="0">请选择软件和类型...</option>
					  

EOT;



$i = 0;
while ( $var = mysqli_fetch_array($tmp)) {
    if($var['lei_for']==0) $var['lei_for']=1;
    
    
	

            //一级代理处理模式
            if ($this->user_array['user_daili'] == 1)
            {


                $jiage = $var[4];


                /**
                 * 外来信息
                 */
                $uesr_zhe = $this->user_array[19];

                /**
                 * ==0表示没有折扣
                 * 
                 * 如果有折扣在吧价格计算下
                 * 
                 * $jiage=单价
                 */
                $zheinfo = null;
                if ($uesr_zhe > 0)
                { //==0表示没有折扣

                    $jiage = $jiage / 100 * $uesr_zhe * 10; //单张折扣后价格
           

                }
				
				
				$show_jiage=$jiage;


                //二级代理模式
            } elseif ($this->user_array['user_daili'] == 2)
            {

                //获取1级代理价格
                $user_array_1 = $this->db->my_class_array('bs_php_user', 'user_user', $this->user_array['user_yao_User'], ' * ');
                $uesr_zhe = $user_array_1['user_Zhe'];
				
				$jiage = $var[4];
                if ($uesr_zhe > 0)
                { //==0表示没有折扣
                    $jiage_1 = $jiage / 100 * $uesr_zhe * 10; //单张折扣后价格

                }else{
				  $jiage_1=$jiage;
				
				}
				


                //自己二级商
                //这里是代理价格加减价格
                $uesr_zhe = $this->user_array['user_Zhe'];
                if ($uesr_zhe >= 0)
                {

                    $zhe_lei = 1;
                } else
                {

                    $zhe_lei = 0;

                    //负数变整数
                    $a = 0 - $uesr_zhe;
                    $uesr_zhe = $a;

                }

                //开始计算加减价格多少钱
                if ($uesr_zhe !== 0)
                { //==0表示没有折扣

                    $jiagecha = $jiage_1 / 100 * $uesr_zhe * 10; //单张折扣后价格

                }


                //计算出代理二的单价格
                if ($zhe_lei == 1)
                {
                    $jiage_2 = $jiage_1 + $jiagecha;

                } else
                {
                    $jiage_2 = $jiage_1 - $jiagecha;

                }


                     $show_jiage=$jiage_2;

                //三级代理模式
            } elseif ($this->user_array['user_daili'] == 3)
            {
                
                
                
                
                
                $user_2=$this->user_array['user_yao_User'];//自己
                $array=$this->db->my_class_array('bs_php_user', 'user_user', $user_2, ' * ');
                $user_1=$array['user_yao_User'];

                
                //获取1级代理价格
                $user_array_1 = $this->db->my_class_array('bs_php_user', 'user_user', $user_1, ' * ');
				
                $uesr_zhe = $user_array_1['user_Zhe'];
				$jiage = $var[4];
			
                if ($uesr_zhe > 0)
                { //==0表示没有折扣
                    $jiage_1 = $jiage / 100 * $uesr_zhe * 10; //单张折扣后价格

                }else{
				     $jiage_1=$jiage;
				
				}
				
				
		

                
                
                

                //二级商
                //这里是代理价格加减价格
                $user_array_2 = $this->db->my_class_array('bs_php_user', 'user_user', $user_2, ' * ');
                $uesr_zhe = $user_array_2['user_Zhe'];
				
                if ($uesr_zhe >= 0)
                {
                    $zhe_lei = 1;
                } else
                {
                    $zhe_lei = 0;

                    //负数变整数
                    $a = 0 - $uesr_zhe;
                    $uesr_zhe = $a;
                }

                //开始计算加减价格多少钱
                if ($uesr_zhe !== 0)
                { //==0表示没有折扣

                    $jiagecha = $jiage_1 / 100 * $uesr_zhe * 10; //单张折扣后价格

                }


                //计算出代理二的单价格
                if ($zhe_lei == 1)
                {
                    $jiage_2 = $jiage_1 + $jiagecha;

                } else
                {
                    $jiage_2 = $jiage_1 - $jiagecha;

                }


                //自己三级商
                //这里是代理价格加减价格
                //$user_array_3 = $this->db->my_class_array('bs_php_user', 'user_user', $user_2, ' * ');
                $uesr_zhe = $this->user_array['user_Zhe'];
			   // ALERT($uesr_zhe);
                if ($uesr_zhe >= 0)
                {
                    $zhe_lei = 1;
                } else
                {
                    $zhe_lei = 0;
                    //负数变整数
                    $a = 0 - $uesr_zhe;
                    $uesr_zhe = $a;

                }

                //开始计算加减价格多少钱
                if ($uesr_zhe !== 0)
                { //==0表示没有折扣

                    $jiagecha = $jiage_2 / 100 * $uesr_zhe * 10; //单张折扣后价格

                }else{
				    $jiagecha = 0;
				}


                //计算出代理二的单价格
                if ($zhe_lei == 1)
                {
                    $jiage_3 = $jiage_2 + $jiagecha;

                } else
                {
                    $jiage_3 = $jiage_2 - $jiagecha;

                }
				
				
				$show_jiage= $jiage_3;

            }
	
	
	if($this->user_array['user_anget_carid']==''){
	   
       echo  '<option value="'.$var[0].'">软件:'.$app_name[$var[5]].'=>'.$var[1].$var[2].'=>单价'.$var[4].'元=>充值次数:'.$var['lei_for'].'</option>';
       
	}else{
	   
       
       if(strrpos ('#'.$this->user_array['user_anget_carid'].',',$var[0].",")==FALSE){
        
          
       }else{
           echo  '<option value="'.$var[0].'">软件:'.$var[0]."|".$app_name[$var[5]].'=>'.$var[1].$var[2].'=>单价'.$var[4].'元=>充值次数:'.$var['lei_for'].'</option>';
       }
       
       #ECHO strrchr('#'.$this->user_array['user_anget_carid'],$var[0].",");
	   
       
	}
	
    
	



   $i++;  
}










print <<< EOT

	  
                      </select></td>
                    <td nowrap>&nbsp;</td>
                  </tr>
                  <tr>
                    <td nowrap class="STYLE2">制作数量：</td>
                    <td nowrap>
                  
                        <input name="shu" type="text" id="shu"   size="10">
                        <span class="STYLE2">张</span></div>
                    </td>
                    <td nowrap>&nbsp;</td>
                  </tr>
                  <tr>
                    <td nowrap class="STYLE2">帐号点数:</td>
                    <td nowrap>  <span class="STYLE2">  {$this->user_array[10]} 

                     点</span></td>
                    <td nowrap>&nbsp;</td>
                  </tr>
                  <tr>
                    <td nowrap>&nbsp;</td>
                    <td nowrap>&nbsp;</td>
                    <td nowrap><label></label></td>
                  </tr>
                  <tr>
                    <td nowrap>&nbsp;</td>
                    <td nowrap>&nbsp;</td>
                    <td nowrap><label></label></td>
                  </tr>
                  <tr>
                    <td nowrap>&nbsp;</td>
                    <td nowrap><label>
                      <input type="submit" onClick="return confirm('你确定制作吗？ 确定将制作充值卡和扣除对应金额.')"id="admin" name="Submit" value="制作">
                    </label></td>
                    <td nowrap><label></label></td>
                  </tr>
                </table>
				</label>
                        <p>&nbsp;</p> 
                        <p>&nbsp;</p>
                        <p>&nbsp;</p>
            </form>
            </td>
          </tr>
		  
        </table></td>
        <td width="9" background="{$get_url_statics}images/main_06.gif"></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="29"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="15" height="29"><img src="{$get_url_statics}images/main_07.gif" width="15" height="29" /></td>
        <td width="100%" background="{$get_url_statics}images/main_08.gif">&nbsp;</td>
        <td width="14"><img src="{$get_url_statics}images/main_09.gif" width="14" height="29" /></td>
      </tr>
    </table></td>
  </tr>
</table>
</body>
</html>
<!--
EOT;
?>
-->