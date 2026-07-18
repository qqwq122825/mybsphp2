<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');


bs_lib::load_modules_class('admin', 'admin');

/**
 * @系统配置
 *  
 */
class config extends admin
{

    private $rc, $db, $purconfig, $form;

    function __construct()
    {


        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->purconfig = bs_lib::load_libbsphp_class('purconfig', 'purconfig');


        $this->form = bs_lib::load_libbsphp_class('formhtml', 'formhtml');

    }








    /**
     * @系统支付配置
     * 
     */
    function call_payl_list()
    {
        
        $html=NULL;
        
        //支付
        $path = BSPHP_DIR_SYS . 'include/modules/payment/paycood';
        $dir_array = open_list_dir($path);
        
        
        
        
        $array = set_post();
        if (isset($array['config'])!='')
        {
            $form_array = include ($path.'/'.$array['config'].'/form_config.php');
            $form_array=runarraykey($form_array);
            
            $bool = $this->purconfig->pur_configs($array['config'], $array,$form_array,'pay_');
            if ($bool)
            {
                alert('保存成功！ 接口代号:'.$array['config']);
            } else
            {

                alert('保存失败！');
            }


        }



        // print_R($dir_array);

        $count = count($dir_array);
        $i = 0;
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
                    
                    $html .= '<form action=""  name="form"  method="post">';

                    //处理form 表单成员
                    foreach ($form_array as $k => $v)
                    {

                        //                        $html='      <tr>
                        //        <td>&nbsp;</td>
                        //        <td>&nbsp;</td>
                        //        <td >&nbsp;</td>
                        //        <td >&nbsp;</td>
                        //      </tr>';


                        //echo $v['label'] . '<br/>';
                        if ($v['type'] == 'radio')
                        {
                            $form_radio1 = $this->form->form_radio($k, $v['values'],'pay_'.$form_array['pay_config']['dir']);
                            $html .= "<tr>\n";
                            $html .= " <td>{$v['label']}</td>\n
        <td>{$form_radio1}</td>\n
        <td>{$v['info']}</td>\n
        <td >bs_lib::get_configs_value('pay_{$dir_array[$i]}','{$k}')</td>\n";
                            $html .= "</tr>\n";

                        } elseif ($v['type'] == 'text')
                        {
                            $form_radio1 = $this->form->form_text($k, $v['values'], $v['size'],
                            bs_lib::get_configs_value('pay_'.$form_array['pay_config']['dir'],$k));
                            $html .= "<tr>\n";
                            $html .= " <td>{$v['label']}</td>\n
        <td>{$form_radio1}</td>\n
        <td>{$v['info']}</td>\n
        <td >bs_lib::get_configs_value('pay_{$dir_array[$i]}','{$k}')</td>\n";
                            $html .= "</tr>\n";


                        } elseif ($v['type'] == 'submit')
                        {

                            $form_radio1 = $this->form->form_button($k, $v['values']);
                            $html .= "<tr>\n";
                            $html .= " <td></td>\n
        <td>{$form_radio1}</td>\n
        <td></td>\n
        <td ></td>\n";
                            $html .= "</tr>\n";


                        } elseif ($v['type'] == '-1')
                        {
                            
                            
                            
                            $form_radio1 = $this->form->form_hidden('config',$v['name'] );


                            $html .= "<tr>\n";
                            $html .= " <td>{$v['label']}</td>\n
        <td>{$form_radio1}<img  src='{$v['url']}' width='100' height='35' alt='{$v['label']}' /></td>\n
        <td>{$v['info']}</td>\n
        <td >LOGO Url:{$v['url']}</td>\n";
                            $html .= "</tr>\n";


                        }


                    }
                    
                    
             $html .= "       
                          <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td >&nbsp;</td>
        <td >&nbsp;</td>
      </tr></form>";



                }


            }


            $i++;
        }


        include bs_lib::load_default_path();
    }


}

?>