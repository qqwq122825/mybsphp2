
var isMobile = false;
var ua = navigator.userAgent;
var ipad = ua.match(/(iPad).*OS\s([\d_]+)/),
isIphone =!ipad && ua.match(/(iPhone\sOS)\s([\d_]+)/),
isAndroid = ua.match(/(Android)\s+([\d.]+)/),
isMobile = isIphone || isAndroid;




var G_json = null;
var G_pay = 1;
var G_qrurl = '';
var G_order = '';
var G_password = '';
var G_shop='';
var G_url_order ='';
var G_url_card ='';


function getinfo(){
	
 
	$.get("index.php?m=index&act=ajax_app",function(result){

		if(result.code==1){

			G_json = result;

			setlist();

	    }else{
			AMUI.dialog.alert({ title: '系统异常', content: '系统连接异常:'+result});
			
		}

    },"json");

}


function setlist(){

$("#shop_list").empty();
for (v in G_json.list){
$("#shop_list").append("<option value='"+G_json.list[v].key+"'>"+G_json.list[v].v+"</option>"); //为Select追加一个Option(下拉项)
}
$("#shop_list").prepend("<option value='0' selected >全部商品</option>"); //为Select插入一个Option(第一个位置)



$("#shop_info").empty();
for (v in G_json.shop){
$("#shop_info").append("<option value='"+G_json.shop[v].id+"'>"+G_json.shop[v].name+' 价格:'+G_json.shop[v].money+"</option>"); //为Select追加一个Option(下拉项)
}
$("#shop_info").prepend("<option value='0' selected >请选择购买商品</option>"); //为Select插入一个Option(第一个位置)
	
}


$("#shop_list").change(function(){//code...




var list = $("#shop_list").val();


$("#shop_info").empty();
for (v in G_json.shop){
    
    if(G_json.shop[v].list==list || list==0){
      $("#shop_info").append("<option value='"+G_json.shop[v].id+"'>"+G_json.shop[v].name+' 价格:'+G_json.shop[v].money+"</option>"); //为Select追加一个Option(下拉项)
    }
}
if(list==0){
$("#shop_info").prepend("<option value='0' selected >请选择购买商品</option>"); //为Select插入一个Option(第一个位置)
}


}); //为Select添加事件，当选择其中一项时触发


$("#zfb").click(function(){
    setpay(1);
})

$("#qq").click(function(){
    setpay(3);
})

$("#wx").click(function(){
    setpay(2);
})
$("#z_zfb").click(function(){
    setpay(4);
    //alert(1);
})









  



$("#okpay").click(function(){
   
var list = $("#shop_list").val();
var shop = $("#shop_info").val();
var mail = $("#mail").val();
if(shop==0){
    AMUI.dialog.alert({ title: '下单提示', content: '请选择您需要购买的商品', onConfirm: function() { console.log('close'); } });
    return false;
}
if(mail=='' && show_user == 1){
    AMUI.dialog.alert({ title: '下单提示', content: '请输入充值ID信息', onConfirm: function() { console.log('close'); } });
    return false;
    
    
  

	$.get("index.php?m=index&act=is_user&shopid="+shop+"&user="+mail+"&type="+show_user,function(result){

		if(result.code==1){

          if(result.is==0){
              AMUI.dialog.alert({ title: '下单提示', content: '您输入的【'+mail+'】不存在!', onConfirm: function() { console.log('close'); } });
              return false;
          }


	    }else{
			AMUI.dialog.alert({ title: '系统异常', content: '系统连接异常:'+result});
			
		}

    },"json");

    
    
    
    
}


  
  
if(G_pay==0){
    AMUI.dialog.alert({ title: '下单提示', content: '请选择一个支付方式', onConfirm: function() { console.log('close'); } });
    return false;
}

  if(G_pay==1){
    paytype='alipay';
  }
  if(G_pay==2){
    paytype='wx';
  }
  if(G_pay==3){
    paytype='qq';
  }
  if(G_pay==4){
    paytype='z_zfb';
  }
  
  
  

	$.get("index.php?m=index&act=downorder&shopid="+shop+"&user="+mail+"&type="+show_user,function(result){

		if(result.code==1){

          if(result.is==0){
              AMUI.dialog.alert({ title: '下单提示', content: '您输入的【'+mail+'】不存在!', onConfirm: function() { console.log('close'); } });
              return false;
          }
          

          if(result.sdate==1){
              window.location.href='index.php?m=index&act=pay&order='+result.order;
              return false;
          }
          
          
          if(result.sdate==0){
              AMUI.dialog.alert({ title: '下单提示', content: result.msg, onConfirm: function() { console.log('close'); } });
              return false;
          }
          
          


	    }else{
			AMUI.dialog.alert({ title: '系统异常', content: '系统连接异常:'+result});
			
		}

    },"json");

})







function showpay (url){
    
    
	$.get(url,function(result){
		
		
		if(result.sdate==1){
			
			$("#qrs").html("<img src='"+result.Oder_Qr+"' width='200px' height='200px' >");
            $("#Oder_Payid").html(result.Oder_Payid);
            
            $("#shop_name").html(result.shop_name);
            //$("#shop_moeny").html(result.shop_moeny+"元");
            $("#paymoney").html("<p >支付<em style='color:red;font-size:36px;'>"+result.shop_moeny+"</em>元</p>");
            
            if(isMobile){
            
            if(result.Oder_buylist==1){
               $("#buylist").html('<img id="zfb" class="payimgs"   width="130" height="50" src="http://api.wbox.top/assets/images/zfb.png"/>'); 
               $("#paytozfb").show();
               
            }else if(result.Oder_buylist==3){
               $("#buylist").html('<img id="zfb" class="payimgs"   width="130" height="50" src="http://api.wbox.top/assets/images/qq.png"/>'); 
               $("#paytoqq").show();
            }else if(result.Oder_buylist==2){
               $("#buylist").html('<img id="zfb" class="payimgs"   width="130" height="50" src="http://api.wbox.top/assets/images/wx.png"/>'); 
               $("#paytowx").show();
            }
            
            
            
            }
            
            G_qrurl = result.Oder_Qr_url;
            G_shop = result.Order_shopid;
            G_order = result.Oder_Payid;
            G_password = result.Oder_password;
            G_url_order =result.Oder_Sdateurl;
            G_url_card =result.Oder_GetCarUrl;
            
            $("#shop_time").html(result.Oder_DownDate);
            $("#shop_out").html(result.Oder_DownDate_Out);
            
            GetOrder();
            

	    }else{
			AMUI.dialog.alert({ title: '下单提示', content: '系统连接异常:'+result});
			
		}
		

	   
    },"json");

    
   // setTimeout('showpay()',3000);
}

function setpay (type){
    
    
    if(type==1){
        G_pay=1;
        $("#zfb").removeClass("gray");
        $("#qq").addClass("gray");
        $("#wx").addClass("gray");
        $("#z_zfb").addClass("gray");
    }
    
    if(type==3){
        G_pay=3;
        $("#zfb").addClass("gray");
        $("#qq").removeClass("gray");
        $("#wx").addClass("gray");
        $("#z_zfb").addClass("gray");
    }
    if(type==2){
        G_pay=2;
        $("#zfb").addClass("gray");
        $("#qq").addClass("gray");
        $("#wx").removeClass("gray");
        $("#z_zfb").addClass("gray");
    }
    
    
    if(type==4){
        G_pay=4;
        $("#z_zfb").removeClass("gray");
        $("#zfb").addClass("gray");
        $("#qq").addClass("gray");
        $("#wx").addClass("gray");
    }
}



//获取url中的参数
function getUrlParam(name) {
 var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
 var r = window.location.search.substr(1).match(reg); //匹配目标参数
 if (r != null) return unescape(r[2]); return null; //返回参数值
}


/*
cookie.set(name, value, expires, path, domain, secure) - 设置 cookie；
cookie.get(name) - 获取 cookie；
cookie.unset(name, path, domain, secure) - 删除 cookie
*/





