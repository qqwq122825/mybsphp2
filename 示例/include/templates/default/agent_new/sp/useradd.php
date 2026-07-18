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
            注册代理
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
                                 <input class="form-control"  name="user" type="text" id="heuser" placeholder="代理登录账号" value="">
                            </div>
                        
                        
                        
                      
                            <div class="form-group">
                                <label for="cate_title">密码</label>
                                <input class="form-control"  name="pwd" type="text" placeholder="代理登录密码"  id="money" >
                            </div>
                            
                            <div class="form-group">
                                <label for="cate_title">联系QQ</label>
                                <input name="qq"  class="form-control" id="code_num" value="" placeholder="联系QQ"  >
                            </div>
                            
                             <div class="form-group">
                                <label for="cate_title">联系邮箱</label>
                                <input name="mail"  class="form-control" id="code_num" value="" placeholder="联系邮箱"  >
                            </div> 
                            
                             <div class="form-group">
                                <label for="cate_title">发展下级</label>
                                <select class="form-control"  name="xiaji">
  <option value="0">可发展下级</option>
  <option value="1">不可发展下级</option>
</select>

                            </div> 
                            
                        </div>
                        
                        
                        
                        <!-- /.box-body -->
                        <div class="box-footer">
                            <input type="submit" id="admin" name="ok" value="注册代理" class="btn btn-primary" />
                        </div>

                    </form>
                </div>
            </div><!-- /.box -->
        </div>   <!-- /.row -->
    </section><!-- /.content -->
</aside><!-- /.right-side -->

</body>
</html>

