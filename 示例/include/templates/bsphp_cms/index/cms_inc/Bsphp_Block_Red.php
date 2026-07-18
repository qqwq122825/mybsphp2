<?php
/**
 * @模块php代码/对应模板目录对应文件htmlphp代码
 * 
 * 2015.10.17
 * 
 * 小周
 */
$list=0;//分类
$sql_list=NULL;
$i=1;
if ($list > 0)
    $sql_list = "AND `news_class`='$list'";
$sql = "SELECT * FROM `plug_bsphp_news`,`plug_bsphp_news_class` WHERE `plug_bsphp_news`.`news_class`=`plug_bsphp_news_class`.`class_id` $sql_list order by rand() LIMIT 5";


$db_array_value = $this->db->Bsphp_my_query($sql);

$RED_text=null;
while ($value = mysqli_fetch_assoc($db_array_value))
{


    $date = date('Y/h/m', $value['news_unix']);

    $RED_text .= "<div class=\"w-related\" >{$i}.<a href=\"index.php?m=index&act=textinfo&id={$value['news_id']}\" title=\"{$value['news_table']}\" target=\"_blank\" >{$value['news_table']}</a></div>";

    $i++;
}



$print_html = str_replace("[Bsphp_Red_Block]", $RED_text, $print_html);

?>