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
            代理列表
            <small>User List</small>
        </h1>
    </section>
    <!-- Main content -->
    <section class="content">
            <div class="row">
                <div class="col-xs-12"> <p>  
                           <!--p> <a class="btn btn-success btn-sm" href="">添加</a> </p-->
                           
                           
                           <form action="" method="get">
                           
                             <input name="m" type="hidden" id="m" value="<?php echo $_GET['m'] ?>">
  <input name="c" type="hidden" id="c" value="<?php echo $_GET['c'] ?>">
  <input name="a" type="hidden" id="a" value="<?php echo $_GET['a'] ?>">
                           
    <input name="act" type="hidden" id="act" value="table">
	<input name="soso_id" type="hidden" id="soso_id" value="1">
     <input name="soso_ok" type="hidden" class="soso_ok" value="搜搜">
            
                <div class="form-group input-group">
                    <input type="text" class="form-control" value="<?php echo $soso; ?>" name="soso" placeholder="输入搜索内容">
                    <span class="input-group-btn">
                        <input class="btn btn-default" type="submit" value="查询">
                    </span>
                </div>
  

                         </form>
                    
                    
                         
                    <div class="box">
                        <div class="box-body table-responsive no-padding">
                            <table id="userTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        
                                        <th>账号</th>
										
										<th>状态</th>
                                        <th>点数</th>
                                        <th>添加时间</th>
                                        <th>最后登录</th>
                                       

                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php 
                                
                                
                                
              while ($array_value= mysqli_fetch_array($db_array_value ))
            {
			$zhuangtai=$array_value['user_IsLock'];
			$zhuangtai==1?$zhuangtai='<span  style="color: #FF9900">冻结</span>':$zhuangtai='正常';
			 
			if($array_value['user_Login_date']=='')$array_value['user_Login_date']='--';
			
			if($array_value['user_re_date']=='')$array_value['user_re_date']='--';
		
			$array_value['user_re_date']=show_Time_Day($array_value['user_re_date']);
		
			$array_value['user_Login_date']=show_Time_Day($array_value['user_Login_date']);
			
			
	
			
			
		
                print <<< EOT

		
              <tr>
               
                <td height="18" nowrap bgcolor="#FFFFFF" class="STYLE2"><div align="center" class="STYLE2 STYLE1" >&nbsp;{$array_value['user_user']}&nbsp;</div></td>
                <td nowrap bgcolor="#FFFFFF"><div align="center" class="STYLE2 STYLE1">&nbsp;{$zhuangtai}&nbsp;</div></td>
                <td nowrap bgcolor="#FFFFFF"><div align="center" class="STYLE2 STYLE1">
                  <div align="center"><span class="STYLE1">&nbsp;{$array_value['user_rmb']} [<a href="index.php?m=agent_new&c=sp&a=money&zhuanguser={$array_value['user_user']}">加/减点</a>]&nbsp;</span></div>
                </div></td>
               
                <td height="18" nowrap bgcolor="#FFFFFF"><div align="center" class="STYLE2 STYLE1">{$array_value['user_re_date']}</div></td>
                <td bgcolor="#FFFFFF"><div align="center" class="STYLE1"><span class="STYLE2 STYLE1">&nbsp;{$array_value['user_Login_date']}&nbsp;</span></div></td>
                  <td>
                                            <a class="btn btn-info btn-sm" href="index.php?m=agent_new&c=sp&a=dailiuser&sdate=0&ok=1&sdate={$array_value['user_IsLock']}&ids={$array_value[0]}">冻结/解除</a>
                                           <a class="btn btn-info btn-sm" onclick="if(confirm('确实要删除吗?')==FALSE){return false;}"  href="index.php?m=agent_new&c=sp&a=dailiuser&delete={$array_value[0]}">删除</a>
                                        
                                        
                                        <a class="btn btn-info btn-sm"   href="index.php?m=agent_new&c=sp&a=app&ids={$array_value[0]}">代理设置</a>
                                        <a class="btn btn-info btn-sm"   href="index.php?m=agent_new&c=sp&a=cardinfo&user={$array_value['user_user']}">查询激活码信息</a>
                                        
                                        </td>
                
                </tr>
                               
                                
                                
                                
EOT;
            }

         
                                
                                
                               
                                
                                
                                
                                
                                
                                
                                
                                
                                 ?>
                                </tbody>
                            </table>
                            
                           <div class="box-footer">
                              
                               <code  class="label btn-info">共<?php echo "共{$zongshu}条纪录，每{$shu}条纪录" ?>个</code> 
                               <code  class="label btn-info">第<?php  echo $FANYE; ?>/<?php echo $zongyue; ?>页</code>
                               
                              
                               <a href="<?php echo $url.$JIAN; ?>"><code  class="label btn-info">上一页</code></a>
                               <a href="<?php echo $url.$JIA; ?>"><code  class="label btn-info">下一页</code></a> 
                       
                          </div>
                        </div><!-- /.box-body -->
                    </div><!-- /.box -->
                </div>
            </div>
    </section><!-- /.content -->
</aside><!-- /.right-side -->
<?php
	include ($aaa.'/user_footer.php');
?>