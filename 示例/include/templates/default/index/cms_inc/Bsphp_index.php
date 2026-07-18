<?php
/**
 * @模板php代码/对应模板目录对应文件htmlphp代码
 * 
 * 2015.10.17
 * 
 * 小周
 * 
 * $print_html可以拦截模板HOOK
 * 
 * //$print_html 这个是一个模板变量 HOOK
$print_html = str_replace("[Bsphp_News_Block_class]", $news_text, $print_html);
$print_html = str_replace("[Bsphp_News_Block_Page]", $pg_text, $print_html);
 */






$print_html = str_replace("[buy_gg]", bs_lib::get_configs_value('buychong','buy_gg'), $print_html);

$print_html = str_replace("[buy_bz_lv]", bs_lib::get_configs_value('buychong','buy_bz_lv'), $print_html);
$print_html = str_replace("[buy_bz_test]", bs_lib::get_configs_value('buychong','buy_bz_test'), $print_html);
$print_html = str_replace("[buy_qq]", bs_lib::get_configs_value('buychong','buy_qq'), $print_html);
$print_html = str_replace("[buy_bsphp]", bs_lib::get_configs_value('buychong','buy_bsphp'), $print_html);

$print_html = str_replace("[buy_mode]", bs_lib::get_configs_value('buychong','buy_mode'), $print_html);

$print_html = str_replace("[z_zfb_off]", bs_lib::get_configs_value('buychong','z_zfb_off'), $print_html);
$print_html = str_replace("[wbox_zfb]", bs_lib::get_configs_value('buychong','wbox_zfb'), $print_html);
$print_html = str_replace("[wbox_wx]", bs_lib::get_configs_value('buychong','wbox_wx'), $print_html);
$print_html = str_replace("[wbox_qq]", bs_lib::get_configs_value('buychong','wbox_qq'), $print_html);
?>