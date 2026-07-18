<?php
/************************************************
 * 编辑软件用户信息（含 Layui 日期时间 + 快捷增加天数）
 ************************************************/

// 示例：系统名称
$sys_name = bs_lib::get_configs_value("sys","name");

// 原始到期值
$vip_raw = isset($array['L_vip_unix']) ? $array['L_vip_unix'] : '';
$vip_display = '';
if ($vip_raw === '0000-00-00 00:00:00' || $vip_raw === '0000-00-00' || $vip_raw === '' ) {
    $vip_display = '';
} else {
    if (ctype_digit((string)$vip_raw)) {
        if (strlen($vip_raw) == 13) {
            $vip_raw = intval($vip_raw / 1000);
        }
        $vip_display = date('Y-m-d H:i:s', intval($vip_raw));
    } else {
        $vip_display = $vip_raw;
    }
}

// 查询用户分组
$sql = "SELECT * FROM `bs_php_userclass` WHERE `class_daihao`='" . addslashes($app_daihao) . "'";
$db_array_value = $this->db->Bsphp_my_query($sql);

$atname = isset($atname) ? $atname : '';

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<title><?php echo htmlspecialchars($sys_name); ?> - by BSPHP</title>

<link rel="stylesheet" href="<?php echo get_url_statics(); ?>layui/css/layui.css">
<link rel="stylesheet" type="text/css" href="<?php echo get_url_statics(); ?>skin/style/base.css">

<script src="<?php echo get_url_statics(); ?>layui/layui.js"></script>
<script src="<?php echo get_url_statics(); ?>skin/js/table.js"></script>
<script src="<?php echo get_url_statics(); ?>skin/js/pageSystem.js"></script>

<style>
    body{padding:8px;}
    .nametable{font-size:16px;margin-bottom:10px;}
    .texttale{display:inline-block;font-weight:bold;}
    #Shortcut ul{list-style:none;padding:0;margin:0 0 10px 0;}
    #Shortcut li{display:inline-block;margin-right:12px;}
    .layui-form-label{height:auto;line-height:normal;}
    #foot{margin-top:40px;text-align:center;color:#777;font-size:12px;}
    .at{color:#f60;padding-left:8px;}
    .quick-add-group button{min-width:60px;}
</style>
</head>
<body>

<div class="nametable">
  <div id="texttale" class="texttale">编辑软件用户信息</div>
  [<a href="javascript:window.location.reload();">+</a>]
</div>

<div id="Shortcut">
  <ul>
    <li><a href="index.php?m=applib&c=admin_user&a=table&daihao=<?php echo urlencode($app_daihao); ?>">返回</a></li>
  </ul>
</div>

<form action="" class="layui-form" name="form" method="post">
<table class="layui-table" style="width:98%;margin-left:10px;" lay-filter="demoEvent">
    <tr>
      <td width="24%" align="right">卡串名称：</td>
      <td width="76%">
        <?php echo htmlspecialchars(isset($array[1]) ? $array[1] : ''); ?>
        <input name="daihao" type="hidden" id="daihao" value="<?php echo htmlspecialchars($daihao); ?>">
        <input name="tid" type="hidden" id="tid" value="<?php echo htmlspecialchars(isset($array[0]) ? $array[0] : ''); ?>">
      </td>
    </tr>

    <!-- 到期时间 + 快捷按钮 -->
    <tr>
      <td align="right">到期时间：</td>
      <td>
        <div style="display:flex;flex-direction:column;gap:6px;max-width:920px;">
          <div>
            <input class="layui-input" name="date" type="text" id="date"
                   placeholder="选择到期时间 (yyyy-MM-dd HH:mm:ss)"
                   value="<?php echo htmlspecialchars($vip_display); ?>" autocomplete="off"
                   style="width:240px;display:inline-block;">
            <button type="button" class="layui-btn layui-btn-sm" id="btnNow">现在</button>
            <button type="button" class="layui-btn layui-btn-sm layui-btn-danger" id="btnClear">清空</button>
            <span class="at">格式: 2009-06-30 11:37:13</span>
          </div>

          <div class="quick-add-group" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
            <span style="color:#666;">快捷增加：</span>
            <?php
              $preset_days = [1,2,3,4,7,15,30,90,180,365];
              foreach($preset_days as $d){
                  echo '<button type="button" class="layui-btn layui-btn-primary layui-btn-sm quick-add" data-days="'.$d.'">+'.$d.'天</button>';
              }
            ?>
            <div style="display:flex;align-items:center;gap:4px;margin-left:12px;">
              <input type="number" id="customDays" placeholder="天数" class="layui-input"
                     style="width:80px;height:30px;line-height:30px;">
              <button type="button" class="layui-btn layui-btn-sm" id="btnAddCustom">+自定义天</button>
            </div>
          </div>
        </div>
      </td>
    </tr>

    <tr>
      <td align="right">绑定特征：</td>
      <td>
        <input class="layui-input" name="key" type="text" id="key"
               value="<?php echo htmlspecialchars(isset($array[6]) ? $array[6] : ''); ?>" style="width:240px;">
        <span class="at">自定义绑定特征</span>
      </td>
    </tr>

    <tr>
      <td align="right">账号同时在线数：</td>
      <td>
        <input class="layui-input" name="L_links_open" type="text" id="L_links_open"
               value="<?php echo htmlspecialchars(isset($array['L_links_open']) ? $array['L_links_open'] : ''); ?>" style="width:140px;">
        <span class="at">0=系统默认；-1=关闭</span>
      </td>
    </tr>

    <tr>
      <td align="right">机器同时使用量：</td>
      <td>
        <input class="layui-input" name="L_links" type="text" id="L_links"
               value="<?php echo htmlspecialchars(isset($array['L_links']) ? $array['L_links'] : ''); ?>" style="width:140px;">
        <span class="at">0=系统默认；-1=关闭</span>
      </td>
    </tr>

    <tr>
      <td align="right">备注：</td>
      <td>
        <input class="layui-input" name="L_beizhu" type="text" id="L_beizhu"
               value="<?php echo htmlspecialchars(isset($array['L_beizhu']) ? $array['L_beizhu'] : ''); ?>" style="width:300px;">
        <span class="at">后台可见</span>
      </td>
    </tr>

    <tr>
      <td align="right">用户分组：</td>
      <td>
        <div class="layui-input-inline" style="width:180px;">
          <select name="class_id">
            <option value="0">不分组</option>
            <?php
            if ($db_array_value) {
                while ($array_value = mysqli_fetch_array($db_array_value)) {
                    $cid   = $array_value[0];
                    $cname = $array_value[1];
                    $sel   = (isset($array['L_class']) && $array['L_class'] == $cid) ? 'selected' : '';
                    echo '<option value="'.htmlspecialchars($cid).'" '.$sel.'>'.htmlspecialchars($cname).'</option>';
                }
            }
            ?>
          </select>
        </div>
      </td>
    </tr>

    <tr>
      <td align="right">&nbsp;</td>
      <td><?php echo $atname; ?></td>
    </tr>

    <tr>
      <td colspan="2" align="center">
        <button type="submit" name="leiadd" value="1" class="layui-btn layui-btn-normal" style="width:120px;">保存</button>
      </td>
    </tr>
</table>
</form>

<div id="foot">
  Copyright 2009-2018
  <a href="http://www.bsphp.com" target="_blank">Bsphp验证系统 <?php echo htmlspecialchars(BSPHP_VERSION); ?></a>
  Bsphp.com<br>All Rights Reserved
</div>

<script>
layui.use(['form','layedit','laydate'], function(){
    var form = layui.form,
        layedit = layui.layedit,
        laydate = layui.laydate;

    // 到期时间（日期+时间）
    laydate.render({
        elem: '#date',
        type: 'datetime',
        format: 'yyyy-MM-dd HH:mm:ss',
        trigger: 'click'
    });

    // 如果你还有富文本：
    if (document.getElementById('LAY_demo_editor')) {
        layedit.build('LAY_demo_editor');
    }
});
</script>

<!-- 快捷增加日期脚本 -->
<script>
(function(){
    function pad(n){return n<10?('0'+n):n;}
    function format(dt){
        return dt.getFullYear()+'-'+pad(dt.getMonth()+1)+'-'+pad(dt.getDate())+' '+
               pad(dt.getHours())+':'+pad(dt.getMinutes())+':'+pad(dt.getSeconds());
    }
    function parseCurrent(){
        var v = document.getElementById('date').value.trim();
        if(!v) return null;
        // 兼容：替换 T / 统一分隔符
        v = v.replace(/-/g,'/');
        var d = new Date(v);
        if(isNaN(d.getTime())) return null;
        return d;
    }
    function setDate(dt){
        document.getElementById('date').value = format(dt);
    }
    function addDays(days){
        days = parseInt(days,10);
        if(isNaN(days)) return;
        var base = parseCurrent();
        if(!base) base = new Date();
        base.setDate(base.getDate() + days);
        setDate(base);
    }
    // 绑定快捷按钮
    Array.prototype.forEach.call(document.querySelectorAll('.quick-add'), function(btn){
        btn.addEventListener('click', function(){
            addDays(this.getAttribute('data-days'));
        });
    });
    // 自定义天数
    document.getElementById('btnAddCustom').addEventListener('click', function(){
        var val = document.getElementById('customDays').value;
        if(!val){
            if(window.layer && layer.msg) layer.msg('请输入天数'); else alert('请输入天数');
            return;
        }
        addDays(val);
    });
    // 现在
    document.getElementById('btnNow').addEventListener('click', function(){
        setDate(new Date());
    });
    // 清空
    document.getElementById('btnClear').addEventListener('click', function(){
        document.getElementById('date').value = '';
    });
})();
</script>

</body>
</html>