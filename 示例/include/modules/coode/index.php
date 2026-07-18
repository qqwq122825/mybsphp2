<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
/**
 * @验证码  全局调用
 * 
 */
 

 
 

class index 
{
    public   $imges_array, $sessiona,$coode;

    function __construct()
    {
        //开启session
         $this->sessiona = bs_lib::load_libbsphp_class('session', 'session');
         
        

        // print_R($this->imges_array);

    //$this->doimg();
    }


    /**
     * @显示验证码
     */
    function call_index()
    {
        
     
        
        
     $this->coode = bs_lib::load_modules_class('coode', 'coode');
     $this->imges_array = bs_lib::get_configs_value('code');  


     $this->coode->charset_1=$this->imges_array['charset_1'];
     $this->coode->charset_a=$this->imges_array['charset_a'];
     $this->coode->charset_A=$this->imges_array['charset_aa'];
     $this->coode->fontsize=$this->imges_array['fontsize'];
     $this->coode->codelen=$this->imges_array['codelen'];
     $this->coode->width=$this->imges_array['width'];
     $this->coode->height=$this->imges_array['height'];
     $this->coode->font=BSPHP_DIR_SYS.'Data/font/'.$this->imges_array['font'];
     
     if(!file_exists($this->coode->font)) $this->coode->font=BSPHP_DIR_SYS.'Data/font/elephant.ttf';//指定的字体
     
     $this->coode->back_color=$this->imges_array['back_color'];
     $this->coode->fontcolor_text=$this->imges_array['fontcolor_text'];
     $this->coode->text_r=$this->imges_array['text_r'];
     $this->coode->createLine_a=$this->imges_array['createLine_a'];
     $this->coode->createLine_b=$this->imges_array['createLine_b'];
     //显示验证码
     $this->coode->doimg();
     
     $sessl=set_get('sessl');
     
     //ob_end_clean();
     if($sessl!=''){
         $this->sessiona->session_appen_open($sessl);
     }else{
        
        $this->sessiona->session_open();
     }
     
     //Header("Content-type: image/jpeg"); 
     
     
     //设置验证码
     $coodes=$this->coode->getCode();
     set_session_value('coode_imges_md5',md5($coodes));
     set_session_value('coode_imges',$coodes);


    }




}

?>