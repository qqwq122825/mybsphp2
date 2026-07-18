var agt = navigator.userAgent.toLowerCase();
var is_ie = ((agt.indexOf("msie") != -1) && (agt.indexOf("opera") == -1));
var is_gecko= (navigator.product == "Gecko");

var guides={
	'xt' : {
		'xt_1' : {
			'xt_1_1' : ['系统信息设置','index.php?m=admin&c=config&a=sys',''],         
			'xt_1_2' : ['Session设置','index.php?m=admin&c=config&a=session',''],
            'xt_1_4' : ['验证码设置','index.php?m=admin&c=config&a=code',''],
            'xt_1_6' : ['用户注册设置','index.php?m=admin&c=config&a=re_user',''],
            'xt_1_7' : ['邮件设置','index.php?m=admin&c=config&a=mail',''],
			'xt_1_8' : ['邀请推广设置','index.php?m=admin&c=config&a=extension',''] , 
            'xt_1_9' : ['在线支付设置','index.php?m=payment&c=config&a=payl_list',''] , 
            'xt_1_10' : ['模板语言设置',' index.php?m=admin&c=config&a=template','']} , 
           
			
		'xt_2' : {
			'xt_2_1' : ['后台系统管理员','index.php?m=admin&c=root&a=table','']},    	
            
            
		'xtuser_2' : {
			'xtuser_2_1' : ['用户反馈信息','index.php?m=user&c=admin_liuyan&a=table',''],
            'xtuser_2_2' : ['用户用户说说','index.php?m=user&c=admin_shuoshuo&a=index',''],
            'xtuser_2_3' : ['当前在线账号','index.php?m=links&c=admin_links&a=table','']
            
            
            },    
            
             
		'xt_3' : {
			'xt_3_1' : ['管理登录日志','index.php?m=admin&c=log&a=table&t=admin_login_log',''], 
			'xt_3_2' : ['用户登录日志','index.php?m=admin&c=log&a=table&t=user_login_log',''], 
			'xt_3_3' : ['邀请推广日志','index.php?m=admin&c=log&a=table&t=yao_registration_log',''], 
			'xt_3_4' : ['佣金提成日志','index.php?m=admin&c=log&a=table&t=yao_money_log',''], 
			'xt_3_5' : ['代理制卡日志','index.php?m=admin&c=log&a=table&t=agent_ka_log',''], 
			'xt_3_6' : ['反破解日志','index.php?m=admin&c=log&a=table&t=od_po_log',''], 
			'xt_3_7' : ['余额扣除日志','index.php?m=admin&c=log&a=table&t=money_buy_log',''], 
			'xt_3_8' : ['执行失败[SQL]','index.php?m=admin&c=log&a=table&t=exit_log','']}      	 
			
			},
			

			
			
			
			
			
	'yh' : {
		'yh_1' : {
			'yh_1_1' : ['登录账号','index.php?m=user&c=admin_user&a=table',''],
            'yh_1_2' : ['账号维护','index.php?m=user&c=admin_weihu&a=user','']},

		 'yh_2' : {
			'yh_2_1' : ['余额充值订单','index.php?m=payment&c=admin_paychong&a=table',''],
			'yh_2_2' : ['平台购卡订单','index.php?m=payment&c=admin_payinfo&a=table','']} 
			

			
			
			},
				  
			 
			 
			 
	'rj' : {
		'rj_1' : {
			'rj_1_1' : ['软件管理','index.php?m=applib&c=admin_app&a=table',''],         
			'rj_1_2' : ['添加软件','index.php?m=applib&c=admin_app&a=add','']},
			
			
		 'rj_3' : {
			'rj_3_1' : ['充值卡串维护','index.php?m=applib&c=admin_card_weihu&a=card',''],
            'rj_3_4' : ['软件用户维护','index.php?m=applib&c=admin_card_weihu&a=loginterm','']}
			}, 
		
	'qt' : {
		
		'qt_1' : {
			'qt_1_1' : ['前台模板','index.php?m=index&c=admin_style&a=config','']},
			
		'qt_2' : {
			'qt_2_1' : ['新闻文章列表','index.php?m=index&c=admin_news&a=news',''],
			'qt_2_2' : ['新闻文章添加','index.php?m=index&c=admin_news&a=add',''],
			'qt_2_3' : ['栏目列表','index.php?m=index&c=admin_class&a=class',''],
			'qt_2_4' : ['栏目添加','index.php?m=index&c=admin_class&a=add','']}

			},
			
			
		
	'gj' : {
		'gj_' : {
			'gj_2' : ['php版本信息','index.php?m=admin&c=tools&a=phpinfo',''],
			'gj_3' : ['系统自动升级','index.php?m=upgrade&c=admin_upgrade&a=index',''],
            'gj_4' : ['API别名工具','index.php?m=applib&c=admin_enpwd&a=index','']
            }},	
            
   'system' : {
		'system_1' : {
			'system_1_1' : ['系统地图','#',''],
			'system_1_2' : ['修改密码','#','']
			}},
			
			
			
	'common' : {},
	'cj' : {}

}


var common_diy={'diy' : {
			'diy_1' : ['软件管理2','Admin.MoShi.php?act=appbiao',''],
			'diy_2' : ['用户列表','Admin.User.php?act=table',''],
			'diy_3' : ['余额充值订单','Admin.paychong.php?act=table',''],
			'diy_4' : ['新闻文章添加','plug/news/Admin.news.php?act=add',''],
			'diy_5' : ['后台系统管理员','Admin.root.php?act=table','']
			}
	
}





    //插件
	guides['cj']=new Object(bsphp_chajiang);
    //快捷菜单
	guides['common']=new Object(common_diy);
	
	
	//alert(guides['common']['diy']['diy_1'][0]);

var titles={
	'xt_1' : '系统设置',
	'xt_2' : '系统管理员',
	'xt_3' : '系统日志记录',
	'yh_1' : '用户管理',
	'yh_2' : '电子商务',
	'rj_1' : '我的软件',
	'rj_2' : '通信安全配置',	
	'rj_3' : '批量维护',
	'qt_1' : 'CMS设置',	
	'qt_2' : 'CMS内容管理',	
	'qt_3' : '静态内容',	
	'gj_' : '数据库维护',	
	'diy' : '常用选项',
    'xtuser_2' : '辅助功能',
    

	'system_1' : '快捷操作'

	}
	
	
	titles['cj_']='插件列表';

//注意：下边参数必须是成套出现
//var cate   = 'glmk'; //默认显示主导航
//var action = 'glmk_1'; //默认显示方位
//var type   = 'glmk_1_1';//下级别导航明确 

var cate   = 'xt';
var action = 'xt_1';
var type   = '';

function uptopguide(id){
	var obj = document.getElementById('topguide-entry');
	var objs=obj.getElementsByTagName('li');
	for(var i=0;i<objs.length;i++){
		objs[i].className = objs[i].id==id ? 'li1' : '';
	}
}
function showguide(id){

	var obj = document.getElementById('showmenu');

	var guide = guides[id];
	var html  = '<dl>';

	for(i in guide){
		var subs = guide[i];
		html += '<dd class=rl>';
		for(j in subs){
			var sub = subs[j];
				if(sub[2] == ''){
				html += '<a href="javascript:" onclick="return initguide(\''+id+'\',\''+j+'\')"><span class=ah>'+sub[0]+'</span></a>';
				} else {
					if(sub[2] == 'm'){
						html += '<a href="javascript:" onclick="return initguide2(\''+id+'\',\''+j+'\')"><span class=ah>'+sub[0]+'</span></a>';
					} else {
						html += '<a href='+sub[1]+' target='+sub[2]+'>'+sub[0]+'</a>';
					}				
				}
			
		}
		html += '</dd>';
	}
	obj.innerHTML = html + '</dl>';
	
	var obj1  = document.getElementById(id);
	var left  = findPosX(obj1) + ietruebody().scrollLeft;
	var top   = findPosY(obj1) + ietruebody().scrollTop + 24;

	obj.style.display = "";
	obj.style.top	= top + 'px';
	obj.style.left	= left + 'px';
	
	addEvent(document,"mouseout",doc_mouseout);
}
function closeguide(){
	var obj = document.getElementById('showmenu');
	obj.style.display = "none";
	uptopguide(cate);
	removeEvent(document,"mouseout",doc_mouseout);
}
function upleft(t){
	var obj  = document.getElementById('left');
	var objs = obj.getElementsByTagName('a');
	for(var i=0;i<objs.length;i++){
		objs[i].className = objs[i].id==t ? 'a1' : '';
	}
}
function showleft(id,t,url){
	cate = id;
	var obj = document.getElementById('left');
	var html = '<dl>';
	var guide = guides[id];	
	url = typeof url != 'undefined' ? url : '';
	type = typeof t != 'undefined' ? t : '';
	for(i in guide){
		var subs = guide[i];
		html += '<dt>' + titles[i] + '</dt><dd>';
		for(j in subs){
			var sub = subs[j];
			
			if(sub[2] == ''){
				html += '<a id="'+j+'" href="javascript:" onclick="return initguide(\''+id +'\',\''+j+'\')">'+sub[0]+'</a>';
				} else {
					if(sub[2] == 'm'){
						html += '<a id="'+j+'" href="javascript:" onclick="return initguide2(\''+id +'\',\''+j+'\')">'+sub[0]+'</a>';
					
					} else {
						html += '<a id="'+j+'" href='+sub[1]+' target='+sub[2]+'>'+sub[0]+'</a>';
					}				
				}
			if(url == ''){
				if(type == ''){
					url = sub[1];
					type = j;
				} else if(j == type){
					url = sub[1];
				}
				action = i;
			}
		}
		html += '</dd>';
	}
	obj.innerHTML = html + '</dl>';
	uptopguide(cate);
	upleft(type);
	parent.main.location = url;
	return false;
}
function showleft2(id,t,url){
	cate = id;
	var obj = document.getElementById('left');
	//var html = '<dl>';
	var guide = guides[id];	
	url = typeof url != 'undefined' ? url : '';
	type = typeof t != 'undefined' ? t : '';
	for(i in guide){
		var subs = guide[i];
		//html += '<dt>' + titles[i] + '</dt><dd>';
		for(j in subs){
			var sub = subs[j];
			//html += '<a id="'+j+'" href="javascript:" onclick="return initguide(\''+id +'\',\''+j+'\')">'+sub[0]+'</a>';
			if(url == ''){
				if(type == ''){
					url = sub[1];
					type = j;
				} else if(j == type){
					url = sub[1];
				}
				action = i;
			}
		}
		//html += '</dd>';
	}
		//html += '<dt>'+type+'</dt><dd>';
		
		//html += '<iframe name="player" src="'+type+'_tree.htm" frameborder="0"  style="height:96%;width:132;z-index:1"></iframe>';
		//html += '</dd></dl>';
	obj.innerHTML = '<iframe name="tree" src="'+type+'_tree.htm" frameborder="0"  style="height:99.9%;width:134;z-index:1"></iframe>';
	uptopguide(cate);
	upleft(type);
	parent.main.location = url;
	return false;
}
function showtitle(){
	var obj = document.getElementById('guide');
	var guide = guides[cate];
	var html = '';
	if(action && type){
		html += '<h1><span class="fr1">' + titles[action] + ' &raquo; ' + guide[action][type][0] + '</span></h1>';
	} else {
		html += '<h1><span class="fr1">开始页</span></h1>';
	}
	obj.innerHTML = html;
}
function initguide(id,t,url){
	showleft(id,t,url);
	showtitle();
	return false;
}
function initguide2(id,t,url){
	showleft2(id,t,url);
	showtitle();
	return false;
}
function showmenu(){
	var obj = document.getElementById('menu');
	//top.main.showselect('hidden');
	if(!IsElement('menubg')){
		var html = '<div id="menu2" class="inner">';
		for(i in guides){
			if(i=='common') continue;
			
			
			
			var guide = guides[i];
			html += "<dl>";
			for(j in guide){
				html += "<dt><h3>" + titles[j] + "</h3></dt><dd>";
				var subs = guide[j];
				for(k in subs){
					var sub = subs[k];
					if(sub[2] == ''){
					html += '<a href="javascript:" onclick="return toguide(\''+i+'\',\''+k+'\')">'+sub[0]+'</a>';
					} else {
					if(sub[2] == 'm'){
					html += '<a href="javascript:" onclick="return toguide2(\''+i+'\',\''+k+'\')">'+sub[0]+'</a>';
					} else {
					html += '<a href='+sub[1]+' target='+sub[2]+'>'+sub[0]+'</a>';
					}
					}
				}
				html += "</dd>";
			}
			html += '</dl>';
		}
		html += '<div><a class="fr" style="color:#ff8800; position:absolute;right:1%;top:1%; cursor:pointer;" onclick="closemenu();">关闭</a></div></div>';
		obj.innerHTML = html;
		var obj2 = document.createElement("div");
		obj2.id = "menubg";
		obj.parentNode.insertBefore(obj2,obj);
	} else {
		var obj2 = document.getElementById('menubg');
		obj2.style.display = "";
	}
	obj.style.display = "";
	addEvent(document,"mousedown",doc_mousedown);
}
function closemenu(){
	var obj = document.getElementById('menu');
	obj.style.display = "none";
	var obj2 = document.getElementById('menubg');
	obj2.style.display = "none";
	removeEvent(document,"mousedown",doc_mousedown);
	//top.main.showselect('');
}
function toguide(id,t){
	closemenu();
	initguide(id,t);
	return false;
}
function toguide2(id,t){
	closemenu();
	initguide2(id,t);
	return false;
}
function doc_mousedown(e){
	var e = is_ie ? event: e;
	obj	= document.getElementById("menu");
	_x	= is_ie ? e.x : e.pageX;
	_y	= is_ie ? e.y + ietruebody().scrollTop : e.pageY;
	_x1 = obj.offsetLeft;
	_x2 = obj.offsetLeft + obj.offsetWidth;
	_y1 = obj.offsetTop - 20;
	_y2 = obj.offsetTop + obj.offsetHeight;

	if(_x<_x1 || _x>_x2 || _y<_y1 || _y>_y2){
	   closemenu();
	}
}
function doc_mouseout(e){
	var e = is_ie ? event: e;
	obj	= document.getElementById("showmenu");
	_x	= is_ie ? e.x : e.pageX;
	_y	= is_ie ? e.y + ietruebody().scrollTop : e.pageY;
	_x1 = obj.offsetLeft + 2;
	_x2 = obj.offsetLeft + obj.offsetWidth;
	_y1 = obj.offsetTop - 20;
	_y2 = obj.offsetTop + obj.offsetHeight;

	if(_x<_x1 || _x>_x2 || _y<_y1 || _y>_y2){
		closeguide();
	}
}
function IsElement(id){
	return document.getElementById(id)!=null ? true : false;
}
function addEvent(el,evname,func){
	if(is_ie){
		el.attachEvent("on" + evname,func);
	} else{
		el.addEventListener(evname,func,true);
	}
};
function removeEvent(el,evname,func){
	if(is_ie){
		el.detachEvent("on" + evname,func);
	} else{
		el.removeEventListener(evname,func,true);
	}
};
function findPosX(obj){
	var curleft = 0;
	if (obj.offsetParent){
		while (obj.offsetParent){
			curleft += obj.offsetLeft
			obj = obj.offsetParent;
		}
	}
	else if (obj.x)
		curleft += obj.x;
	return curleft - ietruebody().scrollLeft;
}
function findPosY(obj){
	var curtop = 0;
	if (obj.offsetParent){
		while (obj.offsetParent){
			curtop += obj.offsetTop
			obj = obj.offsetParent;
		}
	} else if (obj.y){
		curtop += obj.y;
	}
	return curtop - ietruebody().scrollTop;;
}
function ietruebody(){
	return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body;
}
initguide(cate,type,'index.php?m=admin&c=tools&a=info');