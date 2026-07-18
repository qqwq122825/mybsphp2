<?php
/**
 * @模块php代码/对应模板目录对应文件htmlphp代码
 * 
 * 2015.10.17
 * 
 * 小周
 */
    $id=set_get('id');
    
    
    $news_array = $this->db->my_class_array('plug_bsphp_news', 'news_id', $id, ' * ');
    
    $news_class_array = $this->db->my_class_array('plug_bsphp_news_class', 'class_id', $news_array['news_class'], ' * ');
    
    
    $news_test=base64_decode($news_array['news_test']);
    $news_test=stripslashes ($news_test);
    
    $print_html = str_replace("[Bsphp_TextInfo_Table]", $news_array['news_table'], $print_html);
    $print_html = str_replace("[Bsphp_TextInfo_ID]", $news_array['news_id'], $print_html);
    $print_html = str_replace("[Bsphp_TextInfo_Text]", $news_test, $print_html);
    
    $print_html = str_replace("[Bsphp_TextInfo_Class]", $news_array['news_class'], $print_html);
    $print_html = str_replace("[Bsphp_TextInfo_Date]", date('Y-m-d H:i:s', $news_array['news_unix']), $print_html);
     $print_html = str_replace("[Bsphp_TextInfo_Class_Name]", $news_class_array['class_name'], $print_html);
    
?>