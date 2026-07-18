<div class="wrapper row-offcanvas row-offcanvas-left">
    <!-- Left side column. contains the logo and sidebar -->
    <aside class="left-side sidebar-offcanvas">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel">
                <div class="pull-left image">
                    <!--img src="" class="img-circle" alt="User Image" /-->
                </div>
                <div class="pull-left info">
                    <p><?php echo $this->user_array[1]; ?>(UID:<?php echo $this->user_array[0]; ?>)</p>

<a href="index.php?m=agent_new&c=sp&a=password"><i class="fa fa-circle text-success"></i> 修改密码 </a> | <a href="index.php?m=agent_new&c=index&a=loginout" class="fa fa-circle text-success">退出</a>
</div>
</div>

<!-- sidebar menu: : style can be found in sidebar.less -->
<ul class="sidebar-menu">

    <li>
        <a href="index.php?m=agent_new&c=main">
            <i class="glyphicon glyphicon-book"></i> <span>主页</span>
        </a>
    </li>
    
    <li>
        <a href="index.php?m=agent_new&c=sp&a=add">
            <i class="glyphicon glyphicon-screenshot"></i> <span>生成激活码</span>
        </a>
    </li>
    
    <li >
        <a href="index.php?m=agent_new&c=sp&a=table&sdate=0">
            <i class="glyphicon glyphicon-align-justify"></i> <span>激活码列表</span>
        </a>
    </li>
    <li >
        <a href="index.php?m=agent_new&c=sp&a=pushcard">
            <i class="glyphicon glyphicon-search"></i> <span>导出激活码</span>
        </a>
    </li>
    
    <li >
        <a href="index.php?m=agent_new&c=sp&a=money">
            <i class="glyphicon glyphicon-fast-forward"></i> <span>转赠点</span>
        </a>
    </li>
    
   <?php
    
    if($this->user_array['user_daili'] !== 3){
    ?>
    <li >
        <a href="index.php?m=agent_new&c=sp&a=dailiuser">
            <i class="glyphicon glyphicon-user"></i> <span>代理管理</span>
        </a>
    </li>

    <li >
        <a href="index.php?m=agent_new&c=sp&a=useradd">
            <i class="glyphicon glyphicon-plus"></i> <span>添加代理</span>
        </a>
    </li>
    
    
    <?php
	}
?>
    <li >
        <a href="index.php?m=agent_new&c=sp&a=cardinfo">
            <i class="glyphicon glyphicon-heart"></i> <span>充值卡统计</span>
        </a>
    </li>
    
    <li >
        <a href="index.php?m=agent_new&c=sp&a=socard">
            <i class="glyphicon glyphicon-pencil"></i> <span>批量查询</span>
        </a>
    </li>
    

    
    <!---
    <li >
        <a href="index.php?m=agent_new&c=sp&a=cardinfobeizhu">
            <i class="glyphicon glyphicon-pencil"></i> <span>备注统计</span>
        </a>
    </li>
    -->
    
</ul>
</section>
<!-- /.sidebar -->
</aside>
