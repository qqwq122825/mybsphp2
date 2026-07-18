<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?PHP ECHO bs_lib::get_configs_value("sys","name") ?>- by BSPHP</title>
<!--系统CSS-->
<link rel="stylesheet" href="<?php echo get_url_statics() ?>layui/css/layui.css?t=1515376178709" media="all">
<script src="<?php echo get_url_statics() ?>layui/layui.js?t=1515376178709"></script>
<link rel="stylesheet" type="text/css" href="<?PHP ECHO get_url_statics(); ?>skin/style/base.css">


</head>
<!--
<?php
print <<< EOT
-->
<body leftmargin='8' topmargin='8'>
<!--  标题  -->
<div class="nametable">
  <div id="texttale"  class="texttale">编辑用户</DIV>
  [<a href="javascript:window.location.reload();">+</a>]</div>
<!--  快捷  -->
<div id="Shortcut">
  <ul>
    <li><a href="index.php?m=user&c=admin_user&a=table">返回上层</a></li>
  </ul>
</div>
<form name="form1"   class="layui-form"  action="" method="POST"  >
  <input type="hidden" name="do" value="pics" />

  
  
  
  
  
  
  
  
  
  <table class="layui-table"  style="width:98%;margin-left: 10px;" lay-filter="demoEvent">
  <tr bgcolor="#FFFFFF">
    <td width="129" align="right" nowrap><div align="right">UID：</div></td>
    <td width="295" nowrap>{$array[0]}
      <input name="user_uid" type="hidden" id="user_uid" value="$array[0]">
      <label></label></td>
    <td width="146" align="right" nowrap><div align="right">通讯QQ：</div></td>
    <td width="464" nowrap bgcolor="#FFFFFF"><input name="user_qq" type="text"  class="layui-form-label" id="user_qq" value="{$array['user_qq']}" size="20">    </td>
  </tr>
  <tr bgcolor="#FFFFFF">
    <td align="right" nowrap><div align="right">账号：</div></td>
    <td nowrap><input name="user_user" type="text"  class="layui-form-label" id="user_user" value="{$array['user_user']}" size="20"></td>
    <td align="right" nowrap><div align="right">联系邮箱：</div></td>
    <td nowrap><input name="user_email" type="text"  class="layui-form-label" id="user_email" value="{$array['user_email']}" size="20">
      <label></label></td>
  </tr>
  <tr bgcolor="#FFFFFF">
    <td align="right" nowrap><div align="right">备注：</div></td>
    <td nowrap><input name="user_beizhu" type="text"  class="layui-form-label" placeholder="管理给用户备注信息" id="user_qq2" value="{$array['user_beizhu']}" size="50"></td>
    <td align="right" nowrap><div align="right">邮箱是否认证:</div></td>
    <td nowrap><input name="user_mail_ok" type="checkbox" id="user_mail_ok" value="1" {$user_mail_ok_}>
已认证 </td>
  </tr>
  <tr bgcolor="#FFFFFF">
    <td align="right" nowrap><div align="right">密码：</div></td>
    <td nowrap><input name="user_pwd" type="text"  class="layui-form-label" id="user_pwd" value="不修改留空忽略" size="20">
      *</td>
    <td align="right" nowrap><div align="right">联系电话:</div></td>
    <td nowrap><input name="user_Mobile" type="text"  class="layui-form-label" id="user_Mobile" value="{$array['user_Mobile']}" size="20"></td>
  </tr>
  <tr bgcolor="#FFFFFF">
    <td align="right" nowrap><div align="right">积分：</div></td>
    <td nowrap><input name="user_jifen" type="text"  class="layui-form-label" id="user_jifen" value="{$array['user_jifen']}" size="20">
      &nbsp;</td>
    <td align="right" nowrap><div align="right">邀请人账号:</div></td>
    <td nowrap><input name="user_yao_User" type="text"  class="layui-form-label" id="user_yao_User" value="{$array['user_yao_User']}" size="20"></td>
  </tr>
  <tr bgcolor="#FFFFFF">
    <td align="right" nowrap><div align="right">RMB：</div></td>
    <td nowrap><input name="user_rmb" type="text"  class="layui-form-label" id="user_rmb" value="{$array['user_rmb']}" size="20"></td>
    <td align="right" nowrap><div align="right">该用户已经邀请:</div></td>
    <td nowrap><input name="user_yao_Shu" type="text"  class="layui-form-label" id="user_yao_Shu" value="{$array['user_yao_Shu']}" size="20">
      人注册</td>
  </tr>
  <tr bgcolor="#FFFFFF">
    <td align="right" nowrap><div align="right">密保问题：</div></td>
    <td nowrap><input name="user_mibao_wenti" type="text"  class="layui-form-label" id="user_mibao_wenti" value="{$array['user_mibao_wenti']}" size="20">
      &nbsp;</td>
    <td align="right" nowrap><div align="right">注册时间：</div></td>
    <td nowrap>{$array['user_re_date']}</td>
  </tr>
  <tr bgcolor="#FFFFFF">
    <td align="right" nowrap><div align="right">密保答案：</div></td>
    <td nowrap><input name="user_mibao_daan" type="text"  class="layui-form-label" id="user_mibao_daan" value="{$array['user_mibao_daan']}" size="20">
      &nbsp;</td>
    <td align="right" nowrap><div align="right">注册IP：</div></td>
    <td nowrap>{$array['user_re_ip']}</td>
  </tr>
  <tr bgcolor="#FFFFFF">
    <td align="right" nowrap><div align="right">状态：</div></td>
    <td nowrap><select name="user_IsLock" size="1" id="user_IsLock">
        <option value="0" {$arr_user_IsLock[0]} >正常</option>
        <option value="1"   {$arr_user_IsLock[1]} >冻结</option>
      </select>
      &nbsp;</td>
    <td align="right" nowrap><div align="right">最后登录时间：</div></td>
    <td nowrap>{$array['user_Login_date']}</td>
  </tr>
  <tr bgcolor="#FFFFFF">
    <td align="right" nowrap><div align="right">购卡折扣：</div></td>
    <td nowrap><input name="user_Zhe" type="text"  class="layui-form-label" id="user_Zhe" value="{$array['user_Zhe']}" size="10">
      如:8.8 </td>
    <td align="right" nowrap><div align="right">最后登录IP：</div></td>
    <td nowrap>{$array['user_Login_ip']}</td>
  </tr>
  <tr bgcolor="#FFFFFF">
    <td align="right" nowrap><div align="right">代理商：</div></td>
    <td nowrap>{$checked_daili}</td>
    <td align="right" nowrap><div align="right">禁止改密:</div></td>
    <td nowrap><select name="user_is_pwd" size="1" id="user_is_pwd">
        <option value="0" {$arr_user_is_pwd[0]} >正常</option>
        <option value="1" {$arr_user_is_pwd[1]} >禁止</option>
    </select></td>
  </tr>
  <tr bgcolor="#FFFFFF">
    <td align="right" nowrap><div align="right">代理制卡权限:</div></td>
    <td >
    
    <input name="user_anget_carid" id="user_anget_carid" value="" type="hidden" />
EOT;
       while ($array_value = mysqli_fetch_array($db_array_value ))
            {

			$app_array = get_appdaihao_info_array($array_value[5]);
            
            
       if($app_array){
        
        
         if(strrpos ('#'.$array['user_anget_carid'],$array_value[0])==FALSE){
ECHO "<input type='checkbox' name='cardid' id='cardid' value='{$array_value[0]}' lay-skin='primary' title='{$app_array['app_name']}-{$array_value[1]}' ><br />";
}else{
    ECHO "<input type='checkbox' name='cardid' id='cardid' value='{$array_value[0]}' lay-skin='primary' title='{$app_array['app_name']}-{$array_value[1]}' checked><br />";
}

}
            }

            print <<< EOT
 
  
      
      

 

    </td>
    <td align="right" nowrap><div align="right"></div></td>
    <td nowrap>&nbsp;</td>
  </tr>
  <tr>
    <td colspan="4" align="center"><div align="center">
        <input name="baocun" type="submit"  onclick="return checkForm()"   class="layui-btn layui-btn-normal" id="baocun" style="width:100px" value="保存修改">
      </div></td>
  </tr>

<!--
EOT;
?>
-->

<tr>
  <td colspan="4">&nbsp;</td>
</tr>
</table>

</form>


<script>
layui.use(['form', 'layedit', 'laydate'], function(){
  var form = layui.form
  ,layer = layui.layer
  ,layedit = layui.layedit
  ,laydate = layui.laydate;
  
  //日期
  laydate.render({
    elem: '#date'
  });
  laydate.render({
    elem: '#date1'
  });
  
  //创建一个编辑器
  var editIndex = layedit.build('LAY_demo_editor');
 
  //自定义验证规则
  form.verify({
    title: function(value){
      if(value.length < 5){
        return '标题至少得5个字符啊';
      }
    }
    ,pass: [/(.+){6,12}$/, '密码必须6到12位']
    ,content: function(value){
      layedit.sync(editIndex);
    }
  });
  
  //监听指定开关
  form.on('switch(switchTest)', function(data){
    layer.msg('开关checked：'+ (this.checked ? 'true' : 'false'), {
      offset: '6px'
    });
    layer.tips('温馨提示：请注意开关状态的文字可以随意定义，而不仅仅是ON|OFF', data.othis);
  });
  

  
  


});



function checkForm(){
    

    ids ='';
    
     var cardid = document.getElementsByName("cardid");
        for( var i=0;i<cardid.length;i++)
        {
            
          //  alert(cardid[i].checked);
             
            if(cardid[i].checked==true)
            {
               
                ids=ids+cardid[i].value+',';
            }
            else
            {
              
            }
        }
 
    
    
    ids=ids+'0';
  
    user_anget_carid.value=ids;
    $("#user_anget_carid").val(ids);
    

}
</script>
</body>
</html>
