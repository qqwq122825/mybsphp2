<?php
	include ('user_header.php');
?>
    <?php
	include ('user_nav.php');
?>
<?php
	include ('user_sidebar.php');
?>

<aside class="right-side">
    <!-- Content Header (Page header) -->
    
    <section class="content-header">
        <h1>
            商家信息
            <small>User Panel</small>
        </h1>
    </section>
    <!-- Main content -->
    <section class="content">

	
	
	<!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->

   <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
      <div class="row">
	 
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-aqua">
            <div class="inner">
              <h2>剩余:<?php echo $this->user_array[10]  ?>点</h2>

              <p>账号余点</p>
            </div>
            <div class="icon">
              <i class="ion ion-bag"></i>
            </div>
            <a href="index.php?m=agent_new&c=sp&a=table&sdate=0" class="small-box-footer"> <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
		
        <!-- ./col -->
	
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green">
            <div class="inner">
              <h2>卡库存:<?php 
			
			
	$sql = "select count(*)as'hangshu'from`bs_php_cardseries`WHERE`car_admin`='{$this->user_array['user_uid']}'";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    echo (int)$tmp_arr['hangshu'];
			
			$a = $tmp_arr['hangshu'];
			
			 ?> 张<sup style="font-size: 20px"></sup></h2>

              <p>卡信息</p>
            </div>
            <div class="icon">
              <i class="ion ion-stats-bars"></i>
            </div>
            <a href="index.php?m=agent_new&c=sp&a=table&sdate=0" class="small-box-footer"> <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
	
        <!-- ./col -->
		
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-yellow">
            <div class="inner">
              <h2>激活使用<?php 
			
			
	$sql = "select count(*)as'hangshu'from`bs_php_cardseries`WHERE`car_admin`='{$this->user_array['user_uid']}'AND`car_IsLock`='1'  and  `car_zhuangtai`= 0  ";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    echo (int)$tmp_arr['hangshu'];
			
			
			
			 ?> 张</h2>

              <p>卡信息</p>
            </div>
            <div class="icon">
              <i class="ion ion-person-add"></i>
            </div>
            <a href="index.php?m=agent_new&c=sp&a=table&sdate=0" class="small-box-footer"> <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
		
        <!-- ./col -->
		
	
    
    
    
        <div class="col-lg-3 col-xs-6">
		
          <!-- small box -->
          <div class="small-box bg-red">
            <div class="inner">
              <h2>未使用<?php 
			
			
	$sql = "select count(*)as'hangshu'from`bs_php_cardseries`WHERE`car_admin`='{$this->user_array['user_uid']}'AND`car_IsLock`='0'  and  `car_zhuangtai`= 0  ";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    echo (int)$tmp_arr['hangshu'];
			
			
			
			 ?>张</h2>

              <p>卡信息</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
           <a href="index.php?m=agent_new&c=sp&a=table&sdate=0" class="small-box-footer">  <i class="fa fa-arrow-circle-right"></i></a> 
          </div>
        </div>
	
    
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-yellow">
            <div class="inner">
              <h2>已冻结<?php 
			
			
	$sql = "select count(*)as'hangshu'from`bs_php_cardseries`WHERE`car_admin`='{$this->user_array['user_uid']}' AND `car_zhuangtai`= 1  ";
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    echo (int)$tmp_arr['hangshu'];
			
			
			
			 ?> 张</h2>

              <p>卡信息</p>
            </div>
            <div class="icon">
              <i class="ion ion-person-add"></i>
            </div>
            <a href="index.php?m=agent_new&c=sp&a=table&sdate=0" class="small-box-footer"> <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
		
        
        
        
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green">
            <div class="inner">
              <h2>今天制卡<?php 
			
	$date = date('Y-m-d');
	$sql = "select count(*)as'hangshu'from`bs_php_cardseries`WHERE`car_admin`='{$this->user_array['user_uid']}'AND`car_IsLock`='1'  and  `car_zhuangtai`= 0 And `car_reDATE` > '$date' ";
   // ECHO $sql;
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    echo (int)$tmp_arr['hangshu'];
			
			
			
			 ?> 张</h2>

              <p>卡信息</p>
            </div>
            <div class="icon">
              <i class="ion ion-person-add"></i>
            </div>
            <a href="index.php?m=agent_new&c=sp&a=table&sdate=0" class="small-box-footer"> <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green">
            <div class="inner">
              <h2>今天激活<?php 
			
	$date = date('Y-m-d');
	$sql = "select count(*)as'hangshu'from`bs_php_cardseries`WHERE`car_admin`='{$this->user_array['user_uid']}'AND`car_IsLock`='1'  and  `car_zhuangtai`= 0 And `car_pur_date` > '$date' ";
   // ECHO $sql;
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    echo (int)$tmp_arr['hangshu'];
			
			
			
			 ?> 张</h2>

              <p>卡信息</p>
            </div>
            <div class="icon">
              <i class="ion ion-person-add"></i>
            </div>
            <a href="index.php?m=agent_new&c=sp&a=table&sdate=0" class="small-box-footer"> <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-yellow">
            <div class="inner">
              <h2>今天冻结<?php 
			
	$date = date('Y-m-d');
	$sql = "select count(*)as'hangshu'from`bs_php_cardseries`WHERE`car_admin`='{$this->user_array['user_uid']}' AND `car_zhuangtai`= 1 And `car_reDATE` > '$date' ";
   // ECHO $sql;
    $tmp_arr = $this->db->Bsphp_my_array($sql);
    echo (int)$tmp_arr['hangshu'];
			
			
			
			 ?> 张</h2>

              <p>卡信息</p>
            </div>
            <div class="icon">
              <i class="ion ion-person-add"></i>
            </div>
            <a href="index.php?m=agent_new&c=sp&a=table&sdate=0" class="small-box-footer"> <i class="fa fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <!-- ./col -->
      </div>
      <!-- /.row -->

			




        
        
        
        <!-- END PROGRESS BARS -->
    </section><!-- /.content -->
</aside><!-- /.right-side -->
<?php
	include ('user_footer.php');
?>