
//全选反选
function  clickto(){
source=event.srcElement;
if  (source.tagName=="TR"||source.tagName=="TABLE")
return;
while(source.tagName!="TD")
source=source.parentElement;
source=source.parentElement;
cs  =  source.children;
//alert(cs.length);
if  (cs[1].style.backgroundColor!=clickcolor&&source.id!="nc")
for(i=0;i<cs.length;i++){
	cs[i].style.backgroundColor=clickcolor;
}
else
for(i=0;i<cs.length;i++){
	cs[i].style.backgroundColor="";
}
}
//选择框全选
function checkAll(e, itemName)
{
  var aa = document.getElementsByName(itemName);
  for (var i=0; i<aa.length; i++)
   aa[i].checked = e.checked;
}





//取选择框ID
function allcheckbox()
{
　form.all.value = "";
　if ( !form.cm.length ) // 只有一个复选框，form.cm.length = undefined
　{
　　if ( form.cm.checked )
　　　form.all.value = form.cm.value;

　}
　else
　{
　　for ( i = 0 ; i < form.cm.length ; i++ )
　　{
　　　if ( form.cm[i].checked ) // 复选框中有选中的框
　　　{
        
　　　　form.all.value = form.cm[i].value;
        if(form.all.value!=''){
		form.all.value += ","
		}

　　　　for ( j = i + 1 ; j < form.cm.length ; j++ )
　　　　{
　　　　　if ( form.cm[j].checked )
　　　　　{
          　form.all.value += form.cm[j].value+",";

　　　　　
　　　　　}
　　　　}
　　　　break;


　　　}
     
　　}
   form.all.value +="0";
　}

　return true;
}





//翻页
function fanye()
{
	if (form.id.value=="")
	{
		alert("请输入跳转页数");

		return false;
	}
	
	fan.id.value=form.id.value;

		
}

//颜色
var  highlightcolor='#eafcd5';
//此处clickcolor只能用win系统颜色代码才能成功,如果用#xxxxxx的代码就不行,还没搞清楚为什么:(
var  clickcolor='#51b2f6';
function  changeto(){
source=event.srcElement;
if  (source.tagName=="TR"||source.tagName=="TABLE")
return;
while(source.tagName!="TD")
source=source.parentElement;
source=source.parentElement;
cs  =  source.children;
//alert(cs.length);
if  (cs[1].style.backgroundColor!=highlightcolor&&source.id!="nc"&&cs[1].style.backgroundColor!=clickcolor)
for(i=0;i<cs.length;i++){
	cs[i].style.backgroundColor=highlightcolor;
}
}

function  changeback(){
if  (event.fromElement.contains(event.toElement)||source.contains(event.toElement)||source.id=="nc")
return
if  (event.toElement!=source&&cs[1].style.backgroundColor!=clickcolor)
//source.style.backgroundColor=originalcolor
for(i=0;i<cs.length;i++){
	cs[i].style.backgroundColor="";
}
}


