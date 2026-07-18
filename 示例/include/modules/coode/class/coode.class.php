<?php


    error_reporting(E_ALL);
    ini_set("display_errors", "On"); //显示所有错误信息*/
	//验证码类
class coode {
 public $charset = '';//随机因子
 public $charset_1 = true;//0-9
 public $charset_a = false;//a-m
 public $charset_A = true;//A-M
 
 
 
 public $code;//验证码
 public $codelen = 4;//验证码长度
 public $width = 130;//宽度
 public $height = 45;//高度
 public $img;//图形资源句柄
 public $font ='';//指定的字体
 public $fontsize = 20;//指定字体大小
 public $fontcolor;//指定字体颜色变量
 
 public $createLine_a = 1;//线条
 public $createLine_b = 1;//雪花
 public $text_r = 1;//打乱文字角度
 public $back_color='#E4EDF7';//指定背景颜色  -1随机背景颜色
 
  public $fontcolor_text='-1';//指定文字颜色 -1随机字体颜色 #EF3F22
 
 
 //构造方法初始化
 public function __construct() {
      $this->font = BSPHP_DIR_SYS.'Data/font/elephant.ttf';//指定的字体

 }
 //生成随机码
 private function createCode() {
    
    
  if($this->charset_1==false and $this->charset_a==false and $this->charset_A==false ){
    $this->charset='1234567890abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ';
  }else{
    if($this->charset_1==true)  $this->charset.='23456789';
    if($this->charset_a==true)  $this->charset.='abcdefghkmnprstuvwxyz';
    if($this->charset_A==true)  $this->charset.='ABCDEFGHKMNPRSTUVWXYZ';
    
    
  }
    
    
    
  $_len = strlen($this->charset)-1;
  for ($i=0;$i<$this->codelen;$i++) {
   $this->code .= $this->charset[mt_rand(0,$_len)];
  }
 }
 //生成背景
 private function createBg() {
  $this->img = imagecreatetruecolor($this->width, $this->height);
  
  if($this->back_color!=-1){
    $b=$this->hex2rgb($this->back_color);
    $color = imagecolorallocate($this->img, $b['red'], $b['green'], $b['blue']);
  }else{
   $color = imagecolorallocate($this->img, mt_rand(157,255), mt_rand(157,255), mt_rand(157,255)); 
  }
  
  
  imagefilledrectangle($this->img,0,$this->height,$this->width,0,$color);
 }
 //生成文字
 private function createFont() {
  $_x = $this->width / $this->codelen;
  for ($i=0;$i<$this->codelen;$i++) {
  


  if($this->fontcolor_text!=-1){
    $b=$this->hex2rgb($this->fontcolor_text);
    $this->fontcolor = imagecolorallocate($this->img,$b['red'],$b['green'],$b['blue']);
    
  }else{
    
    $this->fontcolor = imagecolorallocate($this->img,mt_rand(0,156),mt_rand(0,156),mt_rand(0,156));
    
  }
  




   if($this->text_r==1){
     imagettftext($this->img,$this->fontsize,mt_rand(-30,30),$_x*$i+mt_rand(1,5),$this->height / 1.4,$this->fontcolor,$this->font,$this->code[$i]);
   }else{
       imagettftext($this->img,$this->fontsize,0,$_x*$i+3,$this->height / 1.4,$this->fontcolor,$this->font,$this->code[$i]);
    
   }
   
   
   
  
   
  }
 }
 //生成线条、雪花
 private function createLine() {
    
if($this->createLine_a==TRUE){
  //线条
  for ($i=0;$i<6;$i++) {
   $color = imagecolorallocate($this->img,mt_rand(0,156),mt_rand(0,156),mt_rand(0,156));
   imageline($this->img,mt_rand(0,$this->width),mt_rand(0,$this->height),mt_rand(0,$this->width),mt_rand(0,$this->height),$color);
  }
  
 } 
if($this->createLine_b==TRUE){
  //雪花
  for ($i=0;$i<100;$i++) {
   $color = imagecolorallocate($this->img,mt_rand(200,255),mt_rand(200,255),mt_rand(200,255));
   imagestring($this->img,mt_rand(1,5),mt_rand(0,$this->width),mt_rand(0,$this->height),'*',$color);
  }
  
  }
 }
 //输出
 private function outPut() {
  Header("Content-type: image/jpeg"); 
  //ob_end_clean(); //清理之前输出缓存
  imagejpeg ($this->img);
  imagedestroy($this->img);
 }
 //对外生成
 public function doimg() {
  $this->createBg();//生成背景
  $this->createCode(); //生成随机码
  $this->createLine();//生成线条、雪花
  $this->createFont();//生成文字
  $this->outPut();
 }
 
 //获取验证码
 public function getCode() {
  return strtolower($this->code);
 }
 
 
 
 
    function hex2rgb($colour)
    {
        if ($colour[0] == '#')
        {
            $colour = substr($colour, 1);
        }
        if (strlen($colour) == 6)
        {
            list($r, $g, $b) = array(
                $colour[0] . $colour[1],
                $colour[2] . $colour[3],
                $colour[4] . $colour[5]);
        } elseif (strlen($colour) == 3)
        {
            list($r, $g, $b) = array(
                $colour[0] . $colour[0],
                $colour[1] . $colour[1],
                $colour[2] . $colour[2]);
        } else
        {
            return false;
        }
        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);
        return array(
            'red' => $r,
            'green' => $g,
            'blue' => $b);
    }
 
 
 
}
?>