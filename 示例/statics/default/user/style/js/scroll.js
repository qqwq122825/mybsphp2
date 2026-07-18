
/************向上滚动***************/
function AutoScrollUp(obj,height){
    $(obj).find("div:first").animate({
            marginTop:"-"+height+"px"
    },1000,function(){
            $(this).css({marginTop:"0px"}).find("div:first").appendTo(this);
    });
}

/************向下滚动***************/
function AutoScrollDown(obj){
    $(obj).find("ul:first").animate({
            marginTop:"25px"
    },1000,function(){
            $(this).css({marginTop:"0px"}).find("li:first").appendTo(this);
    });
}

/************向左滚动***************/
function AutoScrollLeft(obj,width){
    $(obj).find("ul:first").animate({
            marginLeft:"-"+width+"px"
    },1000,function(){
            $(this).css({marginLeft:"0px"}).find("li:first").appendTo(this);
    });
}

/************向右滚动***************/
function AutoScrollRight(obj,width){
    $(obj).find("div:first").animate({
            marginLeft:width+"px"
    },1000,function(){
            $(this).css({marginLeft:"0px"}).find("div:first").appendTo(this);
    });
}
