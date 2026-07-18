<?php
   $aaa =  BSPHP_DIR_SYS.'include/templates/default/agent_new/main';
  // E//XIT;
	include ($aaa.'/user_header.php');
?>
    <?php
	include ($aaa.'/user_nav.php');
?>
<?php
	include ($aaa.'/user_sidebar.php');
?>
<aside class="right-side">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            下级代理权限
            <small>Invite Manage</small>
        </h1>
    </section>
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <!-- left column -->
            <div class="col-md-6">
                <!-- general form elements -->
                <div class="box box-primary">
                    <div class="box-header">
                        <h3 class="box-title">权限分配 账号:<?PHP ECHO $array_user['user_user']; ?></h3>
                    </div><!-- /.box-header -->
                    <!-- form start -->
                    <form id="addCodeForm" name="addCodeForm" role="form" method="post" >
                        <div class="box-body">
                        
                        
                      
  <table >
  
                        
                        
<?php






       while ($array_value = mysqli_fetch_array($db_array_value ))
            {

			$app_array = get_appdaihao_info_array($array_value[5]);
            
            
       if($app_array){
        
        
        
        if($this->user_array['user_anget_carid']==''){
            
          
           
           
           if(strrpos ('#'.$array_user['user_anget_carid'],$array_value[0].",")==TRUE){
            
              $ck = 'checked';
            
           }else{
              $ck = '';
           }
           
           
            
    ECHO "   
  <tr > <td   nowrap>
<input name='cardid' style='width: 30px;'  type='checkbox'  id='cardid' $ck value='{$array_value[0]}' /></td>
       <td  nowrap >{$app_array['app_name']}-{$array_value[1]}</td>
  </tr>";
  
  
  
  
  
  
           
        }else{
            
            
           if(strrpos ('#'.$this->user_array['user_anget_carid'],$array_value[0].",")==TRUE){
            
            
           //echo $array_user['user_anget_carid'];
           if(strrpos ('#'.$array_user['user_anget_carid'],$array_value[0].",")==TRUE){
            
              $ck = 'checked';
            
           }else{
              $ck = '';
           }
            
            
            
            
    ECHO "   
  <tr > <td   nowrap>
<input name='cardid' style='width: 30px;'  type='checkbox'  id='cardid' $ck value='{$array_value[0]}' /></td>
       <td  nowrap >{$app_array['app_name']}-{$array_value[1]}</td>
  </tr>";
  
            }
            
            
            
        }
        
        


}
            }

?>
                            
                            
                            </table>
                            
                            
                            
                      
                            <div class="form-group">
                                <label for="cate_title">备注:</label>
                                <input class="form-control"  name="user_beizhu" value="<?php echo $array_user['user_anget_beizhu']; ?>"  placeholder="用户备注" type="text" id="user_beizhu" size="10" >
                            </div>

                            <div class="form-group">
                                <label for="cate_title">折扣:</label>
                                <input class="form-control"  name="user_Zhe" value="<?PHP if($array_user['user_Zhe']==0){echo 10;}else{ echo $array_user['user_Zhe'];} ?>"  placeholder="折扣不能低您现有折扣" type="text" id="user_Zhe" size="10" >(您当前折扣数值:<?php  if($this->user_array['user_Zhe']==0){ echo 10;}else{ echo $this->user_array['user_Zhe'];} ?>,折扣算法 价格99/10*9.5折扣=卖价)
                            </div>

                            <div class="form-group">
                                <label for="cate_title">重置密码:</label>
                                <input class="form-control"  name="user_is_pwd"  placeholder="留空为不修改" type="text" id="user_is_pwd" size="10" >(不修改留空)
                            </div>  
                            
                        </div><!-- /.box-body -->
                        <div class="box-footer">
                            <input type="submit"   onclick="return checkForm()"    id="admin" name="ok" value="保存" class="btn btn-primary" />
                        </div>
 <input name="user_anget_carid" id="user_anget_carid" value="" type="hidden" />
                    </form>
                </div>
            </div><!-- /.box -->
        </div>   <!-- /.row -->
    </section><!-- /.content -->
</aside><!-- /.right-side -->
<script>
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


