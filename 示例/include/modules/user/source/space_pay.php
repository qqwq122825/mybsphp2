<?php

//支付
$path = BSPHP_DIR_SYS . 'include/modules/payment/paycood';
$dir_array = open_list_dir($path);


$count = count($dir_array);
$i = 0;
$html='';
while ($i < $count)
{

    $file = $path . '/' . $dir_array[$i] . '/form_config.php';


    //判断文件是否存在
    if (file_exists($file))
    {

        //引入文件并 判断文件是否符合类型
        $form_array = include ($file);
        if (isset($form_array['pay_config']))
        {
            //PRINT_R($form_array['pay_config']);
            
            if(bs_lib::get_configs_value('pay_'.$form_array['pay_config']['name'],'pay_'.$form_array['pay_config']['name'].'_set')==0){
                
            
            
      $html .= "<label>
        <input name='pay_leixing' type='radio' checked value='{$form_array['pay_config']['name']}'/>
        <img src='{$form_array['pay_config']['url']}' alt='{$form_array['pay_config']['label']}' border='0'  width='100' height='50' /></label>";

          
          }

        }
    }
    
    $i++;
}

?>
