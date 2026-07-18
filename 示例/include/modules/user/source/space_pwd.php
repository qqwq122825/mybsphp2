<?PHP

/**
 * 调用修改密码API
 * 
 * 由于需要刷新所有了个跳转
 */
$pwd = set_post('pwd');
if ($pwd)
{
    $pwd1 = set_post('pwd1');
    $pwd2 = set_post('pwd2');
    $LOG = $this->user->UserModifyPassWord($this->seesion_uid, $pwd, $pwd1, $pwd2);
    $name = $this->user_str_log[$LOG];
    if ($LOG == 1031)
    {
        alert2('密码修改成功,请重新登陆系统。');
        location('index.php?m=user&c=index&a=space');
    } else
    {

    }
} else
{

    $name = '请输入旧密码！';
}

?>