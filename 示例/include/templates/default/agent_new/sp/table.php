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
            卡串列表
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
                    <input type="text" class="form-control" value="<?php echo $soso; ?>" name="soso" placeholder="输入激活码搜索">
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
                                        
                                        <th>卡号</th>
										<th>备注</th>
										<th>锁状态</th>
                                        <th>激活状态</th>
                                        
                                        <th>制卡时间</th>
                                        <th>充值时间</th>

                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php 
                                
                                
                                
             while ($array_value= mysqli_fetch_array($db_array_value ))
            {
			$zhuangtai=$array_value['car_IsLock'];
			$zhuangtai==1?$test='<span  style="color: #FF9900">已使用</span>':$test='未使用';
			 
			if($array_value[16]=='0000-00-00 00:00:00'){
			  $array_value[16]='--';
			}else{
			  $array_value[16]=show_Time_Day($array_value[16]);
			
			}
			
			$bbb=$array_value['car_zhuangtai'];
			$array_value['car_zhuangtai']==1?$array_value['car_zhuangtai']='<span  style="color: #FF9900">冻结</span>':$array_value['car_zhuangtai']='正常';
		
			
			(int)$array_value[15]!=0?$array_value[15]='(uid:'.$array_value[15].')':$array_value[15]='';
			
			
			$info_date = $array_value[5];
			$array_value[5]=show_Time_Day($array_value[5]);
			
			if($array_value['car_BaoJi']!=1){
			$url_img='nop';
			}else{
			$url_img='nem';
			}
			
			#{$app_name[$array_value[4]]}
           
            $DELETE = '';
            if(bs_lib::get_configs_value('agents',"car_delete_{$this->Grade}")==1){
               $DELETE = "<a class='btn btn-danger btn-sm' href='index.php?m=agent_new&c=sp&a=table&sdate=0&ok=3&sdate={$bbb}&ids={$array_value[0]}'>删除</a>"; 
            }
            
            $car_sdate = '';
            if(bs_lib::get_configs_value('agents',"car_sdate_{$this->Grade}")==1){
               $car_sdate = "<a class='btn btn-success btn-sm' onclick='if(confirm('你确定要冻结吗? 冻结可能无法解除')==false){return false;}' href='index.php?m=agent_new&c=sp&a=table&sdate=0&ok=1&sdate={$bbb}&ids={$array_value[0]}'>冻结</a>"; 
            }
            
            $car_sdate_no = '';
            if(bs_lib::get_configs_value('agents',"car_sdate_no_{$this->Grade}")==1){
               $car_sdate_no = "<a class='btn btn-warning btn-sm' href='index.php?m=agent_new&c=sp&a=table&sdate=0&ok=2&sdate={$bbb}&ids={$array_value[0]}'>解冻</a>"; 
            }
                print <<< EOT
                                
 
                                
                                
                                                 <tr>
             
                <td nowrap bgcolor="#FFFFFF"><div align="center" class="STYLE2 STYLE1">{$array_value[1]}</div></td>
                <td nowrap bgcolor="#FFFFFF"><div align="center" class="STYLE2 STYLE1">{$array_value['car_agnet_beizhu']}</div></td>
                <td nowrap bgcolor="#FFFFFF"><div align="center" class="STYLE2 STYLE1">&nbsp;{$array_value['car_zhuangtai']}&nbsp;</div></td>
                <td height="18" nowrap bgcolor="#FFFFFF"><div align="center" class="STYLE2 STYLE1">&nbsp;{$test}&nbsp;</div></td>

                <td nowrap bgcolor="#FFFFFF"><div align="center" ><a href="#"></a><span class="STYLE2 STYLE1">&nbsp;{$array_value[5]}&nbsp;(<a href="index.php?m=agent_new&c=sp&a=pushcard&act=table&soso_id=1&daihao={$array_value['car_DaiHao']}&soso_ok=搜搜&type=0&soso={$info_date}" >导出时段<a>)</span></div></td>
                <td nowrap bgcolor="#FFFFFF"><div align="center" class="STYLE1">{$array_value[16]}</div></td>
                
                  <td>
                  
                  $car_sdate
                  $car_sdate_no
                  $DELETE
                  
                  
                  
                  
                  
                  
                  
                                         
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