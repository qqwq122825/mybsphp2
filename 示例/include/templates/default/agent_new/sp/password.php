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
            修改密码
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
                        <h3 class="box-title">账号:<?php echo $this->user_array[1] ?></h3>
                    </div><!-- /.box-header -->
                    <!-- form start -->
                    <form id="addCodeForm" name="addCodeForm" role="form" method="post" >
                        <div class="box-body">
                        
                            <div class="form-group">
                                <label for="cate_title">UID</label>
                                 <input class="form-control"  name="user" type="text" id="heuser" value="<?php echo $this->user_array[0] ?>">
                            </div>
                        
                        
                        
                      
                            <div class="form-group">
                                <label for="cate_title">原密码</label>
                                <input class="form-control"  name="password" type="text"  placeholder="原密码"  id="password"  >
                            </div>
                            
                            <div class="form-group">
                                <label for="cate_title">新密码</label>
                                <input name="pwd1"  class="form-control" id="code_num" value="" placeholder="新密码"  >
                            </div>
                            
                                       <div class="form-group">
                                <label for="cate_title">再次输入新密码</label>
                                <input name="pwd2"  class="form-control" id="code_num" value="" placeholder="再次输入新密码"  >
                            </div> 
                        </div><!-- /.box-body -->
                        <div class="box-footer">
                            <input type="submit" id="admin" name="ok" value="确认修改" class="btn btn-primary" />
                        </div>

                    </form>
                </div>
            </div><!-- /.box -->
        </div>   <!-- /.row -->
    </section><!-- /.content -->
</aside><!-- /.right-side -->

</body>
</html>

