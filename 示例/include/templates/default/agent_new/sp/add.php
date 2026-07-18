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
            充值卡制作
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
                        <h3 class="box-title">信息</h3>
                    </div><!-- /.box-header -->
                    <!-- form start -->
                    <form id="addCodeForm" name="addCodeForm" role="form" method="post" >
                        <div class="box-body">
                        
                            <div class="form-group">
                                <label for="cate_title">选择套餐</label>
                                <select  class="form-control" name="select">
                                  <option value="0">请选择需要制卡套餐！</option>
                                  
                                  
                                  <?php $i = 0;
while ( $var = mysqli_fetch_array($tmp)) {
    if($var['lei_for']==0) $var['lei_for']=1;
    
    
	

            //一级代理处理模式
            if ($this->user_array['user_daili'] == 1)
            {      }


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

	
	if($this->user_array['user_anget_carid']==''){
	   
         echo  '<option value="'.$var[0].'">软件:'.$app_name[$var[5]].'=>'.$var[1].$var[2].'=>点数'.$var[4].'点=>充值次数:'.$var['lei_for'].'</option>';
       
	}else{
	   
       
       if(strrpos ('###'.$this->user_array['user_anget_carid'],$var[0].",")){
         echo  '<option value="'.$var[0].'">软件:'.$app_name[$var[5]].'=>'.$var[1].$var[2].'=>点数'.$var[4].'点=>充值次数:'.$var['lei_for'].'</option>';

       }else{
          
       }
       
      
	}


   $i++;

      









} 




?>
                                  
                                </select>
                            </div>
                        
                        
                        
                      
                            <div class="form-group">
                                <label for="cate_title">充值卡数量</label>
                                <input name="shu"  class="form-control" id="code_num" value="1" placeholder="要生成充值卡数量"  >
                            </div>
                            
                            <div class="form-group">
                                <label for="cate_title">剩余点数</label>
                                <input name="info"  class="form-control" id="code_num" value="<?php echo $this->user_array[10]; ?>" placeholder="剩余点数"  >
                            </div>
                            
                            
                            <div class="form-group">
                                <label for="cate_title">备注</label>
                                <input name="beizhu"  class="form-control" id="beizhu" value="" placeholder="制卡备注"  >
                            </div>
                            
                            
                        </div><!-- /.box-body -->
                        <div class="box-footer">
                            <input type="submit" onClick="return confirm('你确定制作吗？ 确定将制作充值卡和扣除对应金额.')"id="admin" name="Submit"  value="制卡" class="btn btn-primary" />
                        </div>

                    </form>
                </div>
            </div><!-- /.box -->
        </div>   <!-- /.row -->
    </section><!-- /.content -->
</aside><!-- /.right-side -->

</body>
</html>


