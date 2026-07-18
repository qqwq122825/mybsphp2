ec.ready(function() {
	ec.time = getValue(document.location,'delayrange');
	var showMsg = function(msg) {
		$("#login_msg").show().html(msg);
	};
	
	
	
	
	var userName = $("#login_userName"),
	 	password = $("#login_password"),
	 	userName_Hidden = $("#login_userName_Hidden"),
		
	 	s = function(obj) {
			$("#login_msg").hide();
			obj.removeClass("error");
			};

	// 绑定验证
	ec.form.validator.bind(userName, {
		type : [ "require", "length" ],
		min : 5,
		max : 50,
		validOnChange : false,
		errorFunction : function(obj, options) {
			switch (options.type) {
				case "require":
					showMsg(rss.login_js_inputaccount);
					break;
				case "emailormobile":
					showMsg(rss.login_js_accountlimit);
					break;
				case "length":
					showMsg(rss.login_js_accountlength);
					break;
			};
			
			obj.addClass("error").focus();
		},
		successFunction : s
	});
	
	ec.form.validator.bind(userName_Hidden, {
		type : [ "require", "length" ],
		min : 5,
		max : 50,
		validOnChange : false,
		errorFunction : function(obj, options) {
			switch (options.type) {
				case "require":
					showMsg(rss.login_js_inputaccount);
					break;
				case "emailormobile":
					showMsg(rss.login_js_accountlimit);
					break;
				case "length":
					showMsg(rss.login_js_accountlength);
					break;
			};
			
			obj.addClass("error").focus();
		},
		successFunction : s
	});

	ec.form.validator.bind(password, {
		type : [ "require", "length" ],
		trim : false,
		validOnChange : false,
		min : 5,
		max : 15,
		errorFunction : function(obj , options) {
			switch(options.type)
			{
				case "require":
					showMsg(rss.common_js_inputpwd);
					break;
				case "length":
					showMsg(rss.common_js_pwdlimit);
					break;
			}
			
			obj.addClass("error");
		},
		successFunction : s
	});

	// 显示验证码
	$("#ct-randomCode").show();
	
	var randomCode = $("#randomCode");


	ec.ui.hover(randomCode);
	ec.form.input.label(randomCode , rss.common_js_ignorecase);

	
	//读取cookie
	userName.val(ec.util.cookie.get("customer_email") || "");

	ec.form.input
		.label(userName , rss.login_js_hwaccount)
		.label(password , rss.login_js_password);

	ec.ui
		.hover(userName)
		.hover(password);
	userName.focus();

	ec.trackCPS("login view");
});

ec.form.validator.register("emailormobile", function(b, a) {
	if (a.allowEmpty && ec.util.isEmpty(b)) {
		return true;
	}
	if (b.length < 11) {
		return false;
	}
	return /^(1[3458])[0-9]{9}$|^\s*([A-Za-z0-9_-]+(\.\w+)*@(\w+\.)+\w+)\s*$/.test(b);
});

ec.form.validator.register("chinaLang", function(b, a) {
	if (a.allowEmpty && ec.util.isEmpty(b)) {
		return true;
	}
	if (b.length != 4) {
		return false;
	}
	else
	{
		return true;
	}
});

$("#randomCode").bind("keyup",function(evt){
	
	if(evt.which==13){
		$("#btnLogin").click();
		return;
	}
	
	var randomCode= $(this);
	var randomCodeImg=$('#randomCodeImg');
	var randomCodeError=$("#randomCodeError");
	if(typeof(randomCode[0]) !=  "undefined" && randomCode.val().length==4)
	{
		var thisValue=randomCode.val();
		var parms="?authcode="+thisValue;
		ajaxJSONPForSelf(localInfo.authCodeValidate,parms,function(data){
			if("success"==data.result)
			{
				randomCodeError.removeClass("icon-error");
				randomCodeError.addClass("icon-ok");
				$("#btnLogin").focus();
			}
			else
			{
				chgRandomCode(randomCodeImg[0],localInfo.webssoLoginSessionCode);
				randomCodeError.removeClass("icon-ok");
				randomCodeError.addClass("icon-error");
				randomCode.focus();
				randomCode.val("");
			}
		});
		
	}
	else if(typeof(randomCode[0]) !=  "undefined" && randomCode.val().length!=4)
	{
		randomCodeError.removeClass("icon-error");
		randomCodeError.removeClass("icon-ok");
	}

});

function login(form) {
	if (!ec.form.validator(form, true))
		return false;

	ec.trackCPS("login");
	
	//记住用户名
	if($("#remember_name").attr("checked"))
	{
		$("#login_userName");
		ec.util.cookie.set("customer_email",$.trim($("#login_userName").val()),{
			 expires :30,   //超时时间，单位天，或者可以是Date类型的对象
			 path :"/"
		});
	}else{
		ec.util.cookie.remove("customer_email");
	}
	
	if(ec.time){
		$("#layer").addClass('full');
		$(".load").addClass('loadmc');
		var time = parseInt(Math.floor(Math.random()*3 + 1)) * 1000;
		setTimeout(function(){
			$("#layer").removeClass('full');
			$(".load").removeClass('loadmc');
			ec.time = 0;
			$(".button-login").click();
		},time);
		return false;
	}
	
	var randomCode = $("#randomCode");
	var loginSubmitForm= $("#loginSubmitForm");
	var formObj= $(".login-form-marginTop");
	
	if(typeof(randomCode[0]) !=  "undefined" && randomCode.val().length==4)
	{
		formObj.bind("submit",function(){return true;});
		loginSubmitForm.click();
		return false;
	}
	
}

//提取url中的查询参数
function getValue(url,name){
	var search = url.search,
		parts = (!search)?[]:search.split('&'),
		params = {};
		
	for(var i=0,len=parts.length;i<len;i++){
		var param = parts[i].split('=');
		var pname = param[0];
		if(i == 0){
			pname = pname.split('?')[1];	
		}
		if(name == pname)return param[1];
//		params[param[0]] = param[1];
	};
	return "";
}

function chgRandomCodeForLogin()
{
	var randomCode= $("#randomCode");
	var randomCodeImg=$('#randomCodeImg');
	var randomCodeError=$("#randomCodeError");
	chgRandomCode(randomCodeImg[0],localInfo.webssoLoginSessionCode);
	randomCodeError.removeClass("icon-ok");
	randomCodeError.removeClass("icon-error");
	randomCode.val("");
}


