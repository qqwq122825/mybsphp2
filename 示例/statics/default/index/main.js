/*function cHeight(){
e=document.getElementById("wrap-left");
if(e.offsetHeight<564){
	e.style.height=564 +"px";
}
}
window.onload=function(){cHeight();}
*/
function searchUrl()
{
	window.location.href='http://'+location.hostname+'/search/'+document.getElementById("get_keywords").value;
}
function searchUrl2()
{
	window.location.href='http://'+location.hostname+'/search/'+document.getElementById("get_keywords2").value;
}
function searchFocus(obj)
{
	if(obj.value==obj.defaultValue) obj.value=''; 
}
function searchHover(obj)
{
	if(obj.value=='') obj.value=obj.defaultValue;
}


function CheckData(strTitle,strInput,strType,blNeed)    //js正则判断
{
	switch (strType)
	{
		case "pname":
			var pattern = /^.{2,200}$/;
			var msg = "\n\n格式要求: 2-200个字符    ";
		break;
		case "email":
			var pattern = /^[a-zA-Z0-9_\-\.]+@([a-zA-Z0-9\-]+\.)+[a-zA-Z]{2,5}$/;
			var msg = "";
			if (strInput != "")
			{
				var msg = "\n\n- 您填写的电子邮箱是 " + strInput;
					msg += "   \n\n- 请仔细核对拼写是否正确。  ";
			}
			else
			{
				var msg = "\n\n- 电子邮箱不能为空！";
			}
		break;
		case "telnum":
			var pattern = /^1[0-9]{10}$|^106[0-9]{9,12}$/;
			var msg = "";
		break;
		case "postcode":
			var pattern = /^[0-9]{6}$/;
			var msg = "\n\n格式要求: 6位数字    ";
		break;
		case "number":
			var pattern = /^[1-9]{1}[0-9]{0,4}$/;
			var msg = "\n\n格式要求: 数字    ";
		break;
	}

	strInput = strInput.replace(/(^\s*)|(\s*$)/g,"");
	if ((strInput == "") && blNeed)
	{
		alert ("请您填写" + strTitle + "。  "+ msg);
		return false;
	}

	if (strInput != "")
	{
		var result = strInput.match(pattern);
		if (result == null)
		{
			alert ("请您填写有效的" + strTitle + "。  " + msg);
			return false;
		}
	}
	return true;
}



function jsaction() //表单验证
{
	if (!CheckData("注册数量",document.getElementById('ordernum').value,"number",true))
	{
		getFocus('ordernum');
		return false;
	}

	if (!CheckData("注册姓名",document.getElementById('regname').value,"pname",true))
	{
		getFocus('regname');
		return false;
	}

	if (!CheckData("电子邮箱",document.getElementById('regmail').value,"email",true))
	{
		getFocus('regmail');
		return false;
	}

	if (document.getElementById('nomail').checked)
	{
		if (!CheckData("手机号码",document.getElementById('usertelnum').value,"telnum",true))
		{
			getFocus('usertelnum');
			return false;
		}
	}

	if (document.getElementById('getbill').checked || document.getElementById('getcd').checked)
	{
		if (document.getElementById('getbill').checked)
		{
			if (document.getElementById('invoicehead').value == "")
			{
				alert ("请填写发票抬头。");
				getFocus('invoicehead');
				return false;
			}
		}

		if (!CheckData("真实姓名",document.getElementById('username').value,"pname",true))
		{
			getFocus('username');
			return false;
		}

		if (document.getElementById('tel').value == "")
		{
			alert ("请填写您的联系电话。");
			getFocus('tel');
			return false;
		}

		if (document.getElementById('add').value == "")
		{
			alert ("请填写您的联系地址。");
			getFocus('add');
			return false;
		}

		if (!CheckData("邮政编码",document.getElementById('post').value,"postcode",true))
		{
			getFocus('post');
			return false;
		}
	}
}

function CheckData2(strTitle,strInput,strType,blNeed)    //js正则判断
    {
        switch (strType)
        {
            case "pname":
                var pattern = /^.{2,200}$/;
                var msg = "\n\n格式要求: 2-200个字符    ";
            break;
            case "email":
                var pattern = /^[a-zA-Z0-9_\-\.]+@([a-zA-Z0-9\-]+\.)+[a-zA-Z]{2,5}$/;
                var msg = "";
                if (strInput != "")
                {
                    var msg = "\n\n- 您填写的电子邮箱是 " + strInput;
                        msg += "   \n\n- 请仔细核对拼写是否正确。  ";
                }
                else
                {
                    var msg = "\n\n- 电子邮箱不能为空！";
                }
            break;
			case "msn":
                var pattern = /^[a-zA-Z0-9_\-\.]+@([a-zA-Z0-9\-]+\.)+[a-zA-Z]{2,5}$/;
                var msg = "";
                if (strInput != "")
                {
                    var msg = "\n\n- 您填写的MSN是 " + strInput;
                        msg += "   \n\n- 请仔细核对拼写是否正确。  ";
                }
            break;
            case "phone":
                var pattern = /^1[0-9]{10}$|^106[0-9]{9,12}$/;
                var msg = "\n\n格式如下：138********";
            break;
            case "tel":
                var pattern =/^[0-9]{3,4}-[0-9]{7,8}$/;
                var msg = "\n\n格式如下：000-0000000/0000-00000000";
            break;
            case "postcode":
                var pattern = /^[0-9]{6}$/;
                var msg = "\n\n格式要求: 6位数字    ";
            break;
            case "qq":
                var pattern = /^[1-9][0-9]{4,11}$/;
                var msg = "\n\n格式要求：5位或12位数字";
            break;
        }

        strInput = strInput.replace(/(^\s*)|(\s*$)/g,"");
        if ((strInput == "") && blNeed)
        {
            alert ("请您填写" + strTitle + "。  "+ msg);
            return false;
        }

        if (strInput != "")
        {
            var result = strInput.match(pattern);
            if (result == null)
            {
                alert ("请您填写有效的" + strTitle + "。  " + msg);
                return false;
            }
        }
        return true;
    }


	
	
function  getFocus(thisId)  //得到焦点
{
	document.getElementById(thisId).focus();
}

function  showWhichOrder(getid)       //选择哪种支付方式
{
		if('wy'==getid)
		{
			document.getElementById('orderForm').action = 'bank.php';
			//document.getElementById('pay_method').value="bank";
		}
		else if('zfb'==getid)
		{
			document.getElementById('orderForm').action = 'alipay.php';
			//document.getElementById('pay_method').value="taobao";
		}
		if('hk'==getid)
		{
			document.getElementById('zhucexinxi').style.display='none';	
			document.getElementById('huikuan').style.display='block';
		}else{
			document.getElementById('zhucexinxi').style.display='block';
			document.getElementById('huikuan').style.display='none';	
		}
}

//购买表
function showTel()
{
	if (document.getElementById('nomail').checked)
	{
		document.getElementById('tmpmail').value=document.getElementById('regmail').value;
		document.getElementById('regmail').value="support@youngzsoft.net";
		showit('usertel',true);
		showit('getPollCodeFont',false);
	}
	else
	{
		document.getElementById('regmail').value=document.getElementById('tmpmail').value;
		showit('usertel',false);
		showit('getPollCodeFont',true);
	}
}
function  showit(thisId,isShow)     //是否显现
{
	if(isShow)
	{
		document.getElementById(thisId).style.display="";
	}
	else
	{
		document.getElementById(thisId).style.display="none";
	}
}

function  getFocus(thisId)  //得到焦点
{
	document.getElementById(thisId).focus();
}

function showcd()       //光盘
{

	if (document.getElementById('getcd').checked || document.getElementById('getbill').checked)
	{
		showit('addinfo1',true);
		showit('addinfo2',true);
		showit('addinfo3',true);
		showit('addinfo4',true);
	}
	else
	{
		showit('addinfo1',false);
		showit('addinfo2',false);
		showit('addinfo3',false);
		showit('addinfo4',false);
	}
}

function showbill()     //发票
{

	if (document.getElementById('getbill').checked)
	{
		showit('addinfo1',true);
		showit('addinfo2',true);
		showit('addinfo3',true);
		showit('addinfo4',true);
		showit('invoice',true);
		showit('voicevalue',true);
	}
	else
	{
		showit('invoice',false);
		showit('voicevalue',false);
		if (!document.getElementById('getcd').checked)
		{
			showit('addinfo1',false);
			showit('addinfo2',false);
			showit('addinfo3',false);
			showit('addinfo4',false);
		}
	}
}



//--------wiki js

function getCookie(c_name)
{
	if (document.cookie.length>0)
	{
		c_start=document.cookie.indexOf(c_name + "=")
		if (c_start!=-1)
		{
			c_start=c_start + c_name.length+1 
			c_end=document.cookie.indexOf(";",c_start)
			if (c_end==-1) c_end=document.cookie.length
			return unescape(document.cookie.substring(c_start,c_end))
		} 
	}
	return ""
}

function setCookie(c_name,value,expiredays)
{
	var exdate=new Date()
	exdate.setDate(exdate.getDate()+expiredays)
	document.cookie=c_name+ "=" +escape(value)+	((expiredays==null) ? "" : ";expires="+exdate.toGMTString())
}

function searchUrl()
{
	window.location.href='http://'+location.hostname+'/search/'+document.getElementById("get_keywords").value;
}
function searchUrl2()
{
	window.location.href='http://'+location.hostname+'/search/'+document.getElementById("get_keywords2").value;
}
function searchFocus(obj)
{
	if(obj.value==obj.defaultValue) obj.value=''; 
}
function searchHover(obj)
{
	if(obj.value=='') obj.value=obj.defaultValue;
}
function cHeight()
{
	e=document.getElementById("content");
	if(e.offsetHeight<754){
		e.style.height=754 +"px";
	}
}
function showsubmenu(sid)
{
	for (i = 1; i <= 100; i ++)
	{
		if(document.getElementById("menuTitle" + i))
		{
			//eval("menuTitle" + i + ".className=\"selected-menu-entry\";");
			//eval("faqanswer" + i + ".className=\"wiki-right-hide\";");
			document.getElementById("menuTitle" + i).className = "selected-menu-entry";
			document.getElementById("faqanswer" + i).className = "wiki-right-hide";
		}
		else
			break;
	}

	// eval("menuTitle" + sid + ".className=\"wiki-class\";");
	document.getElementById("menuTitle" + sid).className = "wiki-class";
	// eval("faqanswer" + sid + ".className=\"wiki-right\";");
	document.getElementById("faqanswer" + sid).className = "wiki-right";
	setCookie("faq_selected_menu", sid, 365);
}
function showsubmenu_ex()
{
	id = getCookie("faq_selected_menu");
	if (id == null || id== "")
		id = 1;
	showsubmenu(id);
}
function init_pay()
{
	id = getCookie("pay_useCompanyName");
	if(id == null || id == "" || id == "0")
	{
		document.getElementById("useCompanyName").style.display="none";
		document.getElementById("checkEndUsername0").checked=true;
		document.getElementById("checkEndUsername1").checked=false;
	}
	else
	{
		document.getElementById("useCompanyName").style.display="table-row";
		document.getElementById("checkEndUsername0").checked=false;
		document.getElementById("checkEndUsername1").checked=true;
	}
	
if(0)
{	
	document.getElementById("payPaypal").style.display="block";
}
else
{
	id = getCookie("pay_payCreditCard");
	if(id == null || id == "" || id == "0")
		document.getElementById("payCreditCard").style.display="none";
	else
	{
		document.getElementById("payCreditCard").style.display="block";
		document.getElementById("paymentMethod_cc").selected = "selected";
	}

	// when all id is null, display credit card method
	if(id == null || id == "")
	{
		document.getElementById("payCreditCard").style.display="block";
		document.getElementById("paymentMethod_cc").selected = "selected";
	}

	id = getCookie("pay_payPaypal");
	if(id == null || id == "" || id == "0")
		document.getElementById("payPaypal").style.display="none";
	else
	{
		document.getElementById("payPaypal").style.display="block";
		document.getElementById("paymentMethod_paypal").selected = "selected";
	}

	id = getCookie("pay_ccName");
	if(id == null || id == "" || id == "0")
		document.getElementById("ccName").style.display="none";
	else
	{
		document.getElementById("ccName").style.display="block";
		document.getElementById("checkCCName").checked = true;
	}
}
}
