



//选择框全选
function checkAll(e, itemName)
{
  //var aa = document.getElementById(itemName);
  var aa = document.getElementsByName(itemName);
  for (var i=0; i<aa.length; i++)
   aa[i].checked = e.checked;
}



//取选择框ID
function allcheckbox()
{
　form.all.value = "";
　if ( !form.id.length ) // 只有一个复选框，form.id.length = undefined
　{
　　if ( form.id.checked )
　　　form.all.value = form.id.value;

　}
　else
　{
　　for ( i = 0 ; i < form.id.length ; i++ )
　　{
　　　if ( form.id[i].checked ) // 复选框中有选中的框
　　　{
        
　　　　form.all.value = form.id[i].value;
        if(form.all.value!=''){
		form.all.value += ","
		}

　　　　for ( j = i + 1 ; j < form.id.length ; j++ )
　　　　{
　　　　　if ( form.id[j].checked )
　　　　　{
          　form.all.value += form.id[j].value+",";

　　　　　
　　　　　}
　　　　}
　　　　break;


　　　}
     
　　}
   form.all.value +="0";
　}

　return true;
}




//设置选中其中一个的id
function select_set(idocumentd,id){
var title = document.getElementById(idocumentd);title.options[id].selected = true;
}


function select_value(id,value) {
    
var cityName; 
var city = document.getElementById(id); 
//获取选中的城市名称 
for(i=0;i<city.length;i++){
    
    
    if(city[i].value==value){
        
      city[i].selected = true;
 
    }
  } 
}

function bsphpfaq(obj, id) {
    
	var aa = document.getElementById(id);

	if(!aa) {
		return;
	}
 
	aa.style.display = '';
 	if(!obj.onmouseout) {
		obj.onmouseout = function () {
			aa.style.display = 'none';
		}
	} 
}



//颜色
var  highlightcolor='#ECEFF4';
//此处clickcolor只能用win系统颜色代码才能成功,如果用#xxxxxx的代码就不行,还没搞清楚为什么:(
var  clickcolor='#ECEFF4';
function  changeto(){
	

	
source=event.srcElement;


if  (source.tagName=="tr"||source.tagName=="table")
return 0;




while(source.tagName!="TD")
source=source.parentElement;
source=source.parentElement;
cs  =  source.children;

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




