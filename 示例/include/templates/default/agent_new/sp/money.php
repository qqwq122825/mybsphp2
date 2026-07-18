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
            转赠点
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
                                <label for="cate_title">账号</label>
                                 <input class="form-control"  name="heuser" placeholder="转账代理" type="text" id="heuser" value="<?php echo @$_GET['zhuanguser']; ?>">
                            </div>
                        
                        
                        
                      
                            <div class="form-group">
                                <label for="cate_title">点数</label>
                                <input class="form-control"  name="money"  placeholder="这里支持负数-100 收回子代理金额" type="text" id="money" size="10" maxlength="5">
                            </div>
                            
                            <div class="form-group">
                                <label for="cate_title">剩余点数</label>
                                <input name="info"  class="form-control" id="code_num" value="<?php echo $this->user_array[10]; ?>" placeholder="备注信息"  >
                            </div>
                            
                            
                        </div><!-- /.box-body -->
                        <div class="box-footer">
                            <input type="submit" onClick="return confirm('你确定吗.')"id="admin" name="ok" value="转赠" class="btn btn-primary" />
                        </div>

                    </form>
                </div>
            </div><!-- /.box -->
        </div>   <!-- /.row -->
    </section><!-- /.content -->
</aside><!-- /.right-side -->

</body>
</html>


