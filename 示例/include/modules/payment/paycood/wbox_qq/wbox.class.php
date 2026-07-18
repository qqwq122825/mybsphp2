<?php
/**
 * @Wbox 在线支付发起
 * 
 * By:wbox.top
 */


class wbox
{
    
    


    #网关地址,勿修改.
    public $url = "http://api.wbox.top/payapi/pay.do";


    #商户PID Wbox网站获取
    public $pid = "";

    #商户密钥 Wbox网站获取
    public $key = "";

    #支付金额,分为单位
    public $total_fee = "";

    #支付结果跳转地址
    public $run_url = "";
    
    #支付结果通知地址
    public $notify_url = "";
    
    #商品标题
    public $litle = "";

    #商户订单编号,回调返回
    public $orderid = "";

    #附加数据1 回调时候传回
    public $body1 = "";

    #附加数据2 回调时候传回
    public $body2 = "";

    #商户编码,默认UTF8 
    public $unicode = "UTF8"; #GBK/UTF8 默认UTF8

    #支付方式, 1=支付宝 2=微信 3=QQ
    public $paylist = "1";

    #返回显示方式 JSON/XML
    public $output = "JSON";


    #计算加密算法
    public function getsign()
    {


        #验证算法
        $data = "&pid=$this->pid&total_fee=$this->total_fee&run_url=$this->run_url&notify_url=$this->notify_url&litle=$this->litle&orderid=$this->orderid&body1=$this->body1&body2=$this->body2&unicode=$this->unicode&paylist=$this->paylist&output=$this->output&key=$this->key";
        //echo $data.'<br />';

        $signTXT = md5($data);


        return ($signTXT);
    }


    #支付页面
    public function GetWboxPayInfo()
    {

        $data = $this->payhttp();
        $request_post = $this->request_post($this->url, $data);
        return ($request_post);
    }


    #支付页面
    public function payhttp()
    {

        $sign = $this->getsign();
        $this->run_url = urlencode($this->run_url);
        $data = "&pid=$this->pid&total_fee=$this->total_fee&run_url=$this->run_url&notify_url=$this->notify_url&litle=$this->litle&orderid=$this->orderid&body1=$this->body1&body2=$this->body2&unicode=$this->unicode&paylist=$this->paylist&output=$this->output";
        $data .= "&sign=$sign";


        return ($data);
    }


    function request_post($url = '', $param = '')
    {

        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); //抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0); //设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch); //运行curl
        curl_close($ch);

        return $data;
    }


}

























?>