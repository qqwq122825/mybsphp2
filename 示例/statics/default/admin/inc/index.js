var Layer='';
var iLayerMaxNum=10000;
var a;
document.onmouseup=me;
document.onmousemove=ms;
var b;
var c;
function Move(Object,event){
	Layer=Object.id;
	if(document.all){
		document.getElementById(Layer).setCapture();
		b=event.x-document.getElementById(Layer).style.pixelLeft;
		c=event.y-document.getElementById(Layer).style.pixelTop;
	}else if(window.captureEvents){
		window.captureEvents(Event.MOUSEMOVE|Event.MOUSEUP);
		b=event.layerX;
		c=event.layerY+110;
	};
	if(Layer!="Layer"+a){
		document.getElementById(Layer).style.zIndex=iLayerMaxNum;
		iLayerMaxNum=iLayerMaxNum+1;
	}
}
function ms(d){
	if(Layer!=''){
		if(document.all){
			document.getElementById(Layer).style.left=event.x-b;
			document.getElementById(Layer).style.top=event.y-c;
		}else if(window.captureEvents){
			document.getElementById(Layer).style.left=(d.clientX-b)+"px";
			document.getElementById(Layer).style.top=(d.clientY-c)+"px";
		}
	}
}
function me(d){
	if(Layer!=''){
		if(document.all){
			document.getElementById(Layer).releaseCapture();
			Layer='';
		}else if(window.captureEvents){
			window.captureEvents(Event.MOUSEMOVE|Event.MOUSEUP);Layer='';
		}
	}
}
function Close(n){
	var e='Layer'+n;											
	document.getElementById(e).style.display='none';
}