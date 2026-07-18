<?php
/**
 * @模块php代码/对应模板目录对应文件htmlphp代码
 * 
 * 2015.10.17
 * 
 * 小周
 */
$list = (int)set_get('list');
$p = (int)set_get('p');
$act = set_get('act');

$Page_mober=15;
//获取读取数量
$LIMIT_As=$p;
if($LIMIT_As > 0)$LIMIT_As=$LIMIT_As-1;
$LIMIT_A = $LIMIT_As* $Page_mober;

$date = null;
$sql_list = null;
$news_text = null;
$i = 1;
if ($list > 0)
    $sql_list = "AND `news_class`='$list'";
$sql = "SELECT * FROM `plug_bsphp_news`,`plug_bsphp_news_class` WHERE `plug_bsphp_news`.`news_class`=`plug_bsphp_news_class`.`class_id` $sql_list ORDER BY`news_id`DESC LIMIT $LIMIT_A,$Page_mober";

$sql_rows = "SELECT * FROM`plug_bsphp_news_class`, `plug_bsphp_news` WHERE `plug_bsphp_news`.`news_class`=`plug_bsphp_news_class`.`class_id`  $sql_list ORDER BY`news_id`DESC";
$db_array_value = $this->db->Bsphp_my_query($sql);


while ($value = mysqli_fetch_assoc($db_array_value))
{


    $date = date('Y/h/m', $value['news_unix']);

    $news_text .= "<div class=\"list-page\">
						<div class=\"w450\">{$i}.[<a href=\"index.php?m=index&act=$act&list={$value['class_id']}\">{$value['class_name']}</a>] <a href=\"index.php?m=index&act=textinfo&id={$value['news_id']}\">{$value['news_table']} $date</a></div>
						<div class=\"list-date\">$date</div>
					</div>
                    <div class=\"clear\"></div>
                    
                    ";

    $i++;
}

//-------------------------------------------

/**
 * @获取条数<a href='news.htm'>1</a>
 */
$tmp = $this->db->Bsphp_my_query($sql_rows);
$zongshu = $this->db->my_rows($tmp);

/**
 * @计算分页
 */
//$yueshuls=取整数就小于$zongyue不取整数的了*/
$zongyue = $zongshu / $Page_mober;

$yueshuls = intval(strval($zongshu / $Page_mober));

if ($zongyue > $yueshuls)
    $zongyue = ++$yueshuls;


$pg_text = null;
$i = 1;
while ($i <= $zongyue)
{


    $pg_text .= "<a href=\"index.php?m=index&act=$act&list=$list&p=$i\">$i</a>";
    $i++;

}

if ($list > 0)
{
    
    
    $list_array = $this->db->my_class_array('plug_bsphp_news_class', 'class_id', $list, ' * ');
    
   

    $print_html = str_replace("[Bsphp_News_Block_ListName]", $list_array['class_name'], $print_html);
    $print_html = str_replace("[Bsphp_News_Block_ListID]", $list_array['class_id'], $print_html);

} else
{
    $print_html = str_replace("[Bsphp_News_Block_ListName]", '新闻动态', $print_html);
    $print_html = str_replace("[Bsphp_News_Block_ListID]", '0', $print_html);

}


//$print_html 这个是一个模板变量 HOOK
$print_html = str_replace("[Bsphp_News_Block_class]", $news_text, $print_html);
$print_html = str_replace("[Bsphp_News_Block_Page]", $pg_text, $print_html);

?>