<?php

defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
/**
 * @数据库操作
 * 文件名称:mysql.calss.php
 * 功能:mysql 数据库操作类
 * 时间:2015.1.4
 * 官网:www.bsphp.com
 *
 * 载入位置:lib.class.php
 */


/**
 * @MYSQL数据库类
 *
 */
class mysql
{

    private $rc;

    private  $APP_Mysql_link;


    /**calss  类初始化 服务器 用户名 密码 表名
     *参数可空,如果空用在函数内部调用该类
     *
     */

    function __construct()
    {



           $this->connect();


    }


    /** 连接数据库*/
    function connect()
    {


        //新版本解决
        $array = explode(":", DBHOST);
        if (isset($array[1])) {
            $user = $array[0];
            $port = $array[1];
        } else {
            $user = DBHOST;
            $port = '3306';
        }



        $this->APP_Mysql_link = mysqli_connect($user, DBUSER, DBPASS, DBTABLE, $port) or die(bs_lib::Alerts('MySQL数据连接失败', "MySQL数据库的地址(数据库IP/地址)/账号或者密码是否正确  <BR/> 解决方案:http://bbs.bsphp.com/thread-1205-1-1.html <BR/>错误信息: <BR/>" . mysql_error()));

        
        mysqli_query($this->APP_Mysql_link, "set names 'utf8'");


    }


    /** 类的退出关闭数据库*/
    function __destruct()
    {
         
    }

    /** 类的退出关闭数据库 写道session关闭*/
    function destruct()
    {
        #@mysqli_close($this->APP_Mysql_link);
    }
    /**
     * @执行SQL语句
     *全系统的SQL语句都会经过这里来执行SQ;语句
     *数据库前缓在这里替换然后再继续执行SQL语句
     *
     */
    function Bsphp_my_query($sql)
    {


        //判断数据库是否连接
       //mysql::connect();


        $sql = str_replace('bs_php_', DBQIANHUAN, $sql);
        //ECHO $sql;
        $this->rc = mysqli_query($this->APP_Mysql_link, $sql);
        if ($this->rc) {
            return $this->rc;
        } else {
            //记录错误
            mysql::SQLaddlog($sql);
            return false;
        }
    }


    /**
     * 记录错误
     */
    function SQLaddlog($sql)
    {


        //判断文件是否存在,不存在就建一个
        $name = BSPHP_DIR_SYS . 'Data/log/mysql_db.php';
        if (@file_exists($name)) {
        } else {
            @file_put_contents($name, '<?php exit; ?>');
        }

        //打开文件
        $handle = @fopen($name, 'a');
        if (!$handle) {
            return false;
        }

        if (@fwrite($handle, "<?php exit; ?>\n" . $sql . "\n") === false) {
            //写不入记录
            return false;
        }

        //关闭
        @fclose($handle);
        return true;


    }


    /**
     * @取执行查询返回数量
     *
     * @return int
     *
     */
    function my_rc_rows()
    {
        return mysqli_num_rows($this->rc);
    }


    /**
     *
     * 取SQL执行返回表项目数量
     *
     */
    function my_rows($tmp)
    {
        if ($tmp) {
            return mysqli_num_rows($tmp);
        } else {
            return 0;
        }

    }


    /** 查询SQL返回数组  包含SQL语句*/
    function Bsphp_my_array($sql)
    {
        $tmp = $this->Bsphp_my_query($sql);
        if (!$tmp)
            return false;
        $A = mysqli_fetch_array($tmp);
        if (!is_array($A))
            return false;
        return $A;

    }

    /** 查询SQL转换数组 不执行SQL语句*/
    function my_tmp_array($tmp)
    {
        $A = mysqli_fetch_assoc($tmp);
        if (!is_array($A))
            return false;
        return $A;
    }


    /**
     * @检测数据库字段是否已经存在了
     * 原理：
     * 先查询数据库判断是否是数组,然后在数组中搜搜查找字段是否存在
     *
     *
     * 参数1=数据库表格
     * 参数2=数据库字段名
     * 参数2=查询数据
     *
     * 不存在返回 false
     */
    function Theexistenceof($table, $class, $dade)
    {
        $sql = "SELECT`$class`FROM`$table`WHERE CONCAT_WS(\"-\",`$class`)=\"$dade\"";

        $tmp = $this->Bsphp_my_query($sql);
        if (!$tmp)
            return false;
        $array = $this->my_tmp_array($tmp);
        if (!is_array($array))
            return false; //判断是否是数组
        if (in_array($dade, $array) == true)
            return true; //数组内寻找文本
        return false;
    }


    /**
     * @查询一个字段并且返回数组
     *
     * 参数1=数据表明
     * 参数2=字段名
     * 参数3=查询数据
     * 参数4=要返回字段  如果 `字段`,`字段`,`字段`
     *
     *
     * 备注:
     *
     * 如果参数4只有一个字段就会直接返回那个字段值
     * 函数内会用,号判断是否只是一个字段
     *
     * 如果查询失败,或者不存在返回 return
     */
    function my_class_array($table, $class, $date, $class_id)
    {
        $sql = "SELECT{$class_id}FROM`$table`WHERE`$class`='$date'";

        $arr = $this->Bsphp_my_array($sql);


        if (strpos($class_id, ','))
            return $arr; //寻找有,表示都个
        if (strpos($class_id, '*'))
            return $arr; //寻找有,表示都个
        if (is_array($arr))
            return $arr[0]; //只有个个直接返回
        return false;
    }


    /**
     *@都数据删除
     *
     * 参数1=数据表
     * 参数2=字段名
     * 参数3=删除数据id
     *
     * 备注:参数3格式= ID,ID,ID   11,56,55,5
     *
     * 一次性删除都个数据以 id作为引导
     */
    function my_in_delete($table, $class, $arr_id)
    {
        $sql = "delete from`$table`where`$class`in($arr_id);";
        $tmp = $this->Bsphp_my_query($sql);
        return $tmp;
    }


    /**
     * @批量in设置值
     *
     * 参数1=数据表
     * 参数2=字段名
     * 参数3=设置ID  备注:参数3格式= ID,ID,ID   11,56,55,5
     *
     * 参数4=要设置字段
     * 参数5=设置数据
     *
     */
    function my_in_SETdata($table, $class, $arr_id, $data_class, $data)
    {
        $sql = "UPDATE`$table`SET`$data_class`='$data'WHERE`$table`.`$class`in($arr_id);";
        $tmp = $this->Bsphp_my_query($sql);
        return $tmp;
    }
    function my_insert_id()
    {
        $oid = mysqli_insert_id($this->rc);
        return $oid;
    }
 
}

?>