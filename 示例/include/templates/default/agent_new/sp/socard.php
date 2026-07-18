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
                批量查询统计
                <small>User List</small>
            </h1>
        </section>
        <!-- Main content -->
        <section class="content">
            <div class="row">
                <div class="col-xs-12"> <p>
                        <!--p> <a class="btn btn-success btn-sm" href="">添加</a> </p-->


                    <form action="" method="post">

                        <input name="act" type="hidden" id="act" value="table">
                        <input name="soso_id" type="hidden" id="soso_id" value="1">

                        <div class="form-group input-group" style="width: 300px;"></div>


                        <input name="soso_ok" type="hidden" class="soso_ok" value="搜搜">

                        <div class="form-group input-group" style="width: 99%;">
                          <textarea  style="width:100%; height:200px;" name="sosotxt" class="form-control" placeholder="批量查询充值卡 
XXXXXXXXXXXX
XXXXXXXXXXXX
XXXXXXXXXXXX

一行一个激活码
"><?php echo set_post('sosotxt'); ?></textarea>
                        </div>

                       
                       
                        <div class="form-group input-group" style="width: 300px;">
                        <input class="btn btn-default" type="submit" value="查询"> </div>
                        
                        
                        <div class="form-group input-group" style="width: 99%;">
                          <textarea  style="width:100%; height:200px;" name="" class="form-control" placeholder=""><?php echo $jieguo; ?></textarea>
                        </div>
                       
                        
            
                        
                    </form>



              

                        </div><!-- /.box-body -->
                    </div><!-- /.box -->
                </div>
            </div>
        </section><!-- /.content -->
    </aside><!-- /.right-side -->
    <script src="/statics/default/agent_new/laydate/laydate.js"></script>
<script>
;!function(){
laydate({
   elem: '#demo'
})
}();
</script>
    

<?php
include ($aaa.'/user_footer.php');
?>
