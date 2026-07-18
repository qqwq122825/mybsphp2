<?php


/*<name>*/
/***********************鎺ュ彛浠嬬粛璇存槑******************************************
* <appen>gg.lg</appen>  
* <appname>鑾峰彇杞欢鐨勫叕鍛?/appname> 
* <sen>0<sen/>                
* <seng1>鍙傛暟=>鎺ユ敹鍐呭</seng>
* <lei>ok</lei>               
* <info>鑾峰彇杞欢鍏憡涓嶉渶瑕佷换浣曢獙璇?/info> 
* *****************************************************************************
*/
/*</name>*/


$uid = get_session_value('USER_UID');
$daihao = HOST_DAIHAO;


//登录连接数功能集代码
//links_chaoshi_login();
//登陆状态
$log = $this->user->UserLoginInfo();
if ($log != 1047)
    PrInfo($this->user_str_log[$log]);

//获取用户信息
$arr = Login_UserInfo($uid, $daihao);

PrInfo(date('Y-m-d H:i:s', $arr['L_vip_unix']));


$date = set_data('datess');
$uid = set_data('uid');
$month = set_data('month');
$day = set_data('day');
$goodsid = set_data('goodsid');
$content = set_data('content');
$list = set_data('list');


if (empty($date)) {
    PrInfo('invalid date');
}
if (empty($uid)) {
    PrInfo('invalid uid');
}
if (empty($month)) {
    PrInfo('invalid month');
}
if (empty($day)) {
    PrInfo('invalid day');
}
if (empty($goodsid)) {
    PrInfo('invalid goodsid');
}
if (empty($content)) {
    PrInfo('invalid content');
}
if (empty($list)) {
    $list = 'list.txt';
}

/*
如果不放在根目录，修改如下路径
$path = createDir( '../' . $date . '/' . $uid . '/' . $month . '/' . $day );
*/
$path = createDir($date . '/' . $uid . '/' . $month . '/' . $day);
$noteFilename = $path . '/' . $goodsid . '.txt';
$listFilename = $path . '/' . $list;
$noteFilenameLength = file_put_contents($noteFilename, $content . PHP_EOL);
$listFilenameLength = file_put_contents($listFilename, $goodsid . ';',
    FILE_APPEND);
PrInfo($noteFilenameLength . ',' . $listFilenameLength);


function createDir($path)
{
    $a = explode('/', $path);
    $n = count($a);

    if (is_dir($path)) {
        $filesCount = getFilesCount($path);
        if ($filesCount > 31998) {
            $endDirName = intval($a[$n - 1]);
            $endDirName++;
            $old_path = $path;
            $new_path = substr($old_path, 0, strlen($old_path) - strlen($endDirName)) . $endDirName;
            return createDir($new_path);
        }
        return $path;
    }
    $a = explode('/', $path);
    $n = count($a);
    for ($i = 0; $i < $n; $i++) {
        $s .= $a[$i] . '/';
        $dir = substr($s, 0, strlen($s) - 1);
        if (is_dir($dir))
            continue;

        if (!mkdir($dir)) {
            $endDirName = intval($a[$n - 1]);
            $endDirName++;
            $old_path = $path;
            $new_path = substr($old_path, 0, strlen($old_path) - strlen($endDirName)) . $endDirName;
            return createDir($new_path);
        }
    }
    return $path;
}
function getFilesCount($path)
{
    $handle = opendir($path);
    $i = 0;
    while (false !== $file = (readdir($handle))) {
        if ($file == '.' || '..' == $file)
            continue;
        $i++;
    }
    return $i;
}
function createFile($path, $content)
{
    $content_length = file_put_contents($filename, $content);
    if ($content_length < 100) {
        exit('文件创建失败');
    }
    return '/' . $filename;
}
?>