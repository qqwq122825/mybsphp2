<?php
defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
class formhtml
{

    var $layout = false; //是否使用表格布局

    var $action; //表单要提交到的URL

    var $method;

    var $enctype = "";

    var $name = "";

    var $id = "";

    var $class = "";


    function form($action, $method = "POST")
    { //通过构造函数初始化成员变量

        $this->action = $action;

        $this->method = $method;

    }


    function form_start()
    {

        $text = "<form action=\"{$this->action}\" method=\"{$this->method}\"";

        if ($this->class !== "")
        {

            $text .= " class=\"{$this->class}\"";

        }

        if ($this->enctype !== "")
        {

            $text .= " enctype=\"{$this->enctype}\"";

        }

        if ($this->id !== "")
        {

            $text .= " id=\"{$this->id}\"";

        }

        if ($this->name !== "")
        {

            $text .= " name=\"{$this->name}\"";

        }

        $text .= ">\n";

        if ($this->layout == true)
        {

            $text .= "<table>\n";

        }

        return $text;

    }


    function form_end()
    {

        if ($this->layout == true)
        {

            $text = "\t</table>\n";

            $text .= "</form>\n";

        } else
        {

            $text = "</form>\n";

        }

        return $text;

    }

    //文本框函数

    function form_text($name, $label_name, $size, $value = "")
    {

        $text = "<input  class=\"layui-form-label\"  type=\"text\" size='$size' name=\"{$name}\" ";

        $text .= "id=\"{$name}\" ";

        if (isset($value))
        {

            $text .= "value=\"{$value}\" ";

        }

        $text .= "/>\n";


        return $text;

    }

    //密码框函数

    function form_passwd($name, $id, $label_name, $label_for, $value = "")
    {

        $text = "<input  class=\"layui-form-label\"  type=\"password\" name=\"{$name}\" ";

        $text .= "id=\"{$id}\" ";

        if (isset($value))
        {

            $text .= "value=\"{$value}\" ";

        }

        $text .= "/>\n";

        $label = $this->form_label($label_name, $label_for);

        $form_item = $this->form_item($label, $text);

        return $form_item;

    }

    //隐藏域函数

    function form_hidden($name, $value = "")
    {

        $text = "<input type=\"hidden\" name=\"{$name}\" id=\"{$name}\" ";


        $text .= "value=\"{$value}\" ";

        $text .= "/>\n";


        return $text;

    }

    //文件域函数

    function form_file($name, $id, $label_name, $label_for, $size = "")
    {

        $text = "<input type=\"file\" name=\"{$name}\" ";

        $text .= "id=\"{$id}\" ";

        if (isset($size))
        {

            $text .= "size=\"{$size}\" ";

        }

        $text .= "/>\n";

        $label = $this->form_label($label_name, $label_for);

        $form_item = $this->form_item($label, $text);

        return $form_item;

    }

    //复选框函数

    function form_checkbox($name, $label = array(), $label_name, $label_for = "")
    {

        $i = 0;

        $text = array();

        foreach ($label as $id => $value)
        {

            $text[$i] = "<input type=\"checkbox\" id=\"{$id}\" name=\"{$name}\" value=\"{$value}\" />";

            $text[$i] .= "<label for=\"{$id}\">{$value}</label>";

            $i++;

        }

        $label = $this->form_label($label_name, $label_for);

        $form_item = $this->form_item($label, $text);

        return $form_item;

    }

    //单选框函数

    function form_radio($name, $label = array(), $path)
    {


        $text = null;

        foreach ($label as $id => $value)
        {


            $a = echo_checkbox_checked($path, $name, $id, true);
            $text .= "<label for=\"{$id}\"> <input type=\"radio\" id=\"{$name}\" " . $a . " name=\"{$name}\" value=\"{$id}\" />{$value}</label> ";


        }


        return $text;

    }

    //下拉菜单函数
    /**
     * 参数1=ID
     * 参数2=名称
     * 参数3=列表
     * 参数4=选择值
     * 参数5=代码名称
     * 参数6=表单名称
     * 表单7=js函数
     * 
     */

    function form_select($id, $name, $options = array(), $selected = false, $label_name, $label_for, $onchange = "")
    {

        if ($onchange !== "")
        {

            $text = "<select id=\"{$id}\" name=\"{$name}\" onchang=\"{$onchange}\">\n";


        } else
        {

            $text = "<select id=\"{$id}\" name=\"{$name}\">\n";

        }


        foreach ($options as $value => $key)
        {

            if ($selected == $value)
            {

                $text .= "\t<option valute=\"{$value}\" selected=\"selected\">{$key}</option>\n";

            } else
            {

                $text .= "\t<option value=\"{$value}\">{$key}</option>\n";

            }

        }

        $text .= "</select>";

        $label = $this->form_label($label_name, $label_for);

        $form_item = $this->form_item($label, $text);

        return $form_item;

    }

    //多选列表函数

    function form_selectmul($id, $name, $size, $options = array(), $label_name, $label_for)
    {

        $text = "<select id=\"{$id}\" name=\"{$name}\" size=\"{$size}\" multiple=\"multiple\">\n";

        foreach ($options as $value => $key)
        {

            $text .= "\t<option value=\"{$value}\">{$key}</option>\n";

        }

        $text .= "</select>\n";

        $label = $this->form_label($label_name, $label_for);

        $form_item = $this->form_item($label, $text);

        return $form_item;

    }

    //按钮函数

    function form_button($name, $value)
    {

        $text = "<button  class=\"layui-btn layui-btn-normal\"  id=\"{$name}\" name=\"{$name}\"  class=\"submit\" type=\"submit\"";


        $text .= ">" . $value;

        $text .= "</button>\n";


        return $text;

    }

    //文本域函数

    function form_textarea($id, $name, $cols, $rows, $label_name, $label_for, $value = "")
    {

        $text = "<textarea id=\"{$id}\" name=\"{$name}\" cols=\"{$cols}\" rows=\"{$rows}\">{$value}</textarea>\n";

        $label = $this->form_label($label_name, $label_for);

        $form_item = $this->form_item($label, $text);

        return $form_item;

    }

    //文字标签函数

    function form_label($text, $for)
    {

        if ($for !== "")
        {

            $label = "<label for=\"{$for}\">{$text}：</label>";

        } else
        {

            $label = $text . "：";

        }

        return $label;

    }


    function form_item($form_label, $form_text)
    {

        switch ($this->layout)
        {

            case true:

                $text = "<tr>\n";

                $text .= "\t<th class=\"label\">";

                $text .= $form_label;

                $text .= "</th>\n";

                $text .= "\t<td>";

                $text .= $form_text;

                $text .= "</td>\n";

                $text .= "</tr>\n";

                break;

            case false:

                $text = $form_label;

                $text .= $form_text;

                break;

        }

        return $text;

    }


    function CreateForm($form_item = array())
    {

        echo $this->form_start();

        foreach ($form_item as $item)
        {

            echo $item;

        }

        echo $this->form_end();

    }

}

?>