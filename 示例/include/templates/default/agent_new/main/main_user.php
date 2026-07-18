<?php
/**
 * Created by PhpStorm.
 * User: John
 * Date: 1/12/15
 * Time: 20:31
 */

defined('BASEPATH') OR exit('No direct script access allowed');
?><!-- Right side column. Contains the navbar and content of the page -->
<aside class="right-side">
    <!-- Content Header (Page header) -->
    <style type="text/css">
<!--
.linkss {
	background-color: #006633;
	clear: left;
	float: right;
	height: 100px;
	width: 100px;
	display: block;
	visibility: visible;
	z-index: 10;
}
-->
    </style>
    
    <section class="content-header">
        <h1>
            我的用户
            <small>Node List</small>
        </h1>
    </section>
    <!-- Main content -->
    <section class="content">
        <!-- START PROGRESS BARS -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-solid">
                    <div class="box-header">
                      
                        <h3 class="box-title">列表</h3>
                    </div><!-- /.box-header -->
                    <div class="box-body">
                        <div class="callout callout-info">
                          
                        
                        
                        
                         <p>  <form action="" method="post">
                            <h4>快速注册用户</h4>
                            
                                <input name="login" type="hidden" id="login" value="1" />
                                <input name="uid" type="hidden" id="login" value="<?php echo $uid ?>" />
                                <input   id="username" name="username" value=""  placeholder="用户名" >

								<input   id="email" name="email" value=""  placeholder="邮箱,可空为默认" >

								<input  id="password" name="password" value=""  placeholder="密码,可空默认123654" >

                                <input type="submit" name="action2" value="确认注册" class="btn-info" /><br /> 
                                <br />
                           <?php
	echo $return_info;
?>

                           </div>
<br />


                       
                         </form> </p>         
                  
                        
                         <p>  <form action="" method="post">
                            <h4>用户查询</h4>
                            <div class="form-group">
                                
                                <input  id="soso" name="soso" value=""  placeholder="请输入用户端口/用户名" > <input type="submit" name="action" value="确认查询" class=" btn-info" />
                                
                                
                            </div>
                            

                          </div> 
                         </form> </p>
                         <table id="userTable" class="table table-bordered table-hover">
  <tr>
    <th height="25"  scope="col">用户名</th>
    <th height="25"  scope="col">端口</th>
    <th height="25"  scope="col">密码</th>
    <th height="25"  scope="col">状态</th>
    <th height="25"  scope="col">使用套餐</th>
    <th height="25"  scope="col">套餐时间</th>
    <th height="25"  scope="col">最后使用</th>
    <th height="25"  scope="col">注册时间</th>

    <th   scope="col">操作</th>
  </tr>



                        
                        <?php  if ($result) { foreach ($result as $node):
                        
                        
                        
                        
                        
                        
           
            if ($node->viptime > time())
            {
                $viptime = '<span class="label label-success">到期时间:' . date('Y-m-d H:i:s', $node->viptime) . '</span>';
               
            } else
            {
                $viptime = '<span class="label label-danger">到期时间:' . date('Y-m-d H:i:s', $node->viptime) . '</span>';
            }
            
            
            $remain = $node->c_all_datetime - $node->c_out_datetime;
            if ($node->c_out_datetime < $node->c_all_datetime)
            {
					
               $alltime = '<span class="label label-success"> 剩余时间:' . floor(($remain / 86400)) . '天' . floor(($remain % 86400 / 3600)) . '小时' . floor($remain % 3600 / 60) . '分钟' .floor($remain % 60) . '秒</span>';
					
                
            } else
            {
                $alltime = '<span class="label label-danger">剩余时间:用完了</span>';
            }
            
            
            
            $size = $node->all_size - $node->out_size;
            if ($node->out_size < $node->all_size)
            {
              
                $allsize = '<span class="label label-success"> 剩余流量:' . human_file_size($size) . '</span>';
                
            } else
            {
                $allsize = '<span class="label label-danger">剩余流量:用完了</span>';
            }

                     
                        
                        
                        
                        
                         ?>
                        
                          
                        
    <tr>
    <td><code ><?php echo $node->user_name; ?></code></td>
    <td><code ><?php echo $node->port; ?></code></td>
    <td><code ><?php echo $node->passwd; ?></code></td>
    <td><?php if($node->switch==1) echo '<code  class="label label-success" >正常</code>'; else echo '<code  class="label label-danger" >到期 </code>'; ?></td>
    <td><code  class="label label-success" ><?php  
                       
                        if($node->plan=='A'){
                          echo '流量套餐';  
                        }elseif($node->plan=='B'){
                           echo '时间套餐';   
                        }elseif($node->plan=='C'){
                           echo '计时套餐';   
                        }
                         
                         ?>
                         
            
                         
                         
                         
                         </code></td>
    <td>      
    
                        <?php if($node->plan=='B'){ echo '<span class="label label-success">当前使用</span>';}else{ echo '<span class="label label-danger">没有使用</span>';}?> 
                        <?php echo $viptime; ?>
                        </p>
                        <p><span class="label label-info"> 计时套餐</span> 
                        <?php if($node->plan=='C'){ echo '<span class="label label-success">当前使用</span>';}else{ echo '<span class="label label-danger">没有使用</span>';} ?> 
                        <?php echo $alltime; ?>
                        </p>
          
                        
                        <p><span class="label label-info"> 流量套餐</span> 
                        <?php if($node->plan=='A'){ echo '<span class="label label-success">当前使用</span>';}else{echo '<span class="label label-danger">没有使用</span>';} ?>
                        <?php echo $allsize; ?>
                         
                         </code></td>
    <td><code  ><?php   if($node->t==0) echo '--'; else echo  date("Y-m-d H:i:s", $node->t); ?></code></td>
    <td><code  ><?php echo $node->reg_date; ?></code></td>

  
    <td><code ><a href="buy.jsp?c_username=<?php echo $node->user_name; ?>">给Ta续费</code></td>
  </tr>
                        

                            
                        <?php endforeach; } ?>
                        
                        </table>
                          <div class="box-footer">
                              <?php if($rows<$show){ $down = 1;}else{ if(($rows%$show)>0) $down =  round($rows/$show+1); else  $down =  $rows/$show;} ?>
                               <code  class="label btn-info">邀请<?php echo $rows ?>人</code> 
                               <code  class="label btn-info">第<?php  echo $p+1; ?>/<?php echo $down ?>页</code>
                               
                               <a href="?p=<?php if($p<=0) echo $p; else $p+1; ?>"><code  class="label btn-info">上一页</code> </a> 
                               <a href="?p=<?php if(($p+1)>=$down) echo $p; else echo $p+ 1; ?>"><code  class="label btn-info">下一页</code></a> 
                       
                          </div>
                        
                    </div><!-- /.box-body -->
                </div><!-- /.box -->
            </div><!-- /.col (left) -->


        
        <!-- END PROGRESS BARS -->
    </section><!-- /.content -->
</aside><!-- /.right-side -->
