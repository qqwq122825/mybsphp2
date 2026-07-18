<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>登录</title>
<!-- Tell the browser to be responsive to screen width -->
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
<!-- Bootstrap 3.3.6 -->
<link rel="stylesheet" href="/statics/default/applib/bootstrap/css/bootstrap.min.css">
<!-- Ionicons -->
<link rel="stylesheet" href="/statics/default/applib/css/ionicons.min.css">
<!-- Theme style -->
<link rel="stylesheet" href="/statics/default/applib/css/AdminLTE.min.css">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="">
  <div class=".col-xs-6">
    <!-- general form elements -->
    <div class="box box-primary">
      <div class="box-header with-border">
        <h3 class="box-title">登录</h3>
      </div>
      <!-- /.box-header -->
      <!-- form start -->
      <form id="form1" name="form1" method="post" action="">
        <div class="box-body">
          <div class="form-group">
            <label for="exampleInputPassword1">用户名</label>
            <div class="input-group">
              <div class="input-group-addon"><i class="glyphicon glyphicon-user"></i></div>
              <input class="form-control" name="user" value="<?php echo $user; ?>" type="test" placeholder="用户名">
            </div>
          </div>
          <div class="form-group">
            <label for="exampleInputPassword1">登录密码</label>
            <div class="input-group">
              <div class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></div>
              <input class="form-control" name="pwd"  value="<?php echo $pwd; ?>" type="test" placeholder="密码">
            </div>
          </div>
          
          <?php if(	bs_lib::get_configs_value('code','coode_login') == 1){ ?>
          <div class="form-group">
            <label for="exampleInputPassword1">验证码</label>
            <div class="input-group">
              <div class="input-group-addon"><i class="glyphicon glyphicon-eye-open"></i></div>
              <input class="form-control" name="code" type="test" placeholder="输入验证码">
            </div>
          </div>
          
          
          
          <div class="form-group">
           
         
              <img class="cimg"  onclick="this.src='index.php?m=coode&time='+new Date().getTime()";  src="index.php?m=coode&time='+new Date().getTime()" alt="验证码" width="160" height="50" border="0" />           
            
          </div>
          
          <?php } ?>
          
          <!-- /.box-body -->
          <div class="box-body">
            <h4><?php echo $log_name; ?></h4>
          </div>
          <div class="box-footer">
            <button type="submit"  value="1" name="login" class="btn btn-primary btn-lg btn-block">登录</button>
          </div>
        </div>
      </form>
      <!-- /.box -->
    </div>
  </div>
</div>
</body>
</html>
