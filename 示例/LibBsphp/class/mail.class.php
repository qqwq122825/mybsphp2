<?PHP
defined('BSPHP_SET') or die('Not,This File Not Can in Ie Open');
class mail {
//您的SMTP 服务器供应商，可以是域名或IP地址
Var $smtp;//我用的是qq的smtp的服务器，你可以换成自己的
//SMTP需要要身份验证设值为 1 不需要身份验证值为 0，现在大都数的SMTP服务商都要验证，如不清楚请与你的smtp 服务商联系。
Var $check = 1;
//您的email帐号名称
Var $username;
//您的email密码
Var $password;
//此email 必需是发信服务器上的email
Var $s_from;
Var $Port;

function __construct(){
    
    $this->smtp=bs_lib::get_configs_value('mail','smtp_host');
    $this->username=bs_lib::get_configs_value('mail','send_mail_user');
    $this->password=bs_lib::get_configs_value('mail','send_mail_user_pwd');
    $this->s_from=bs_lib::get_configs_value('mail','send_show_mail');
    $this->Port=bs_lib::get_configs_value('mail','port');
    
}



function smail ( $from, $password, $smtp, $check = 1 ) {
if( preg_match("/^[^\d\-_][\w\-]*[^\-_]@[^\-][a-zA-Z\d\-]+[^\-](\.[^\-][a-zA-Z\d\-]*[^\-])*\.[a-zA-Z]{2,3}/", $from ) ) {
    
$this->username = substr( $from, 0, strpos( $from , "@" ) );
$this->password = $password;
$this->smtp = $smtp ? $smtp : $this->smtp;
$this->check = $check;
$this->s_from = $from;
}

}
function send ($to,$from, $subject, $message ) {
//连接服务器
$fp = fsockopen ( $this->smtp, $this->Port, $errno, $errstr, 60);
if (!$fp ) return "联接服务器失败".__LINE__;
@set_socket_blocking($fp, true );
$lastmessage=fgets($fp,512);
if ( substr($lastmessage,0,3) != 220 ) return "错误信息1:$lastmessage".__LINE__;
//HELO
$yourname = "YOURNAME";
if($this->check == "1") $lastact="EHLO ".$yourname."
";
else $lastact="HELO ".$yourname."
";
fputs($fp, $lastact);
$lastmessage == fgets($fp,512);
if (substr($lastmessage,0,3) != 220 ) return "错误信息2:$lastmessage".__LINE__;
while (true) {
$lastmessage = fgets($fp,512);
if ( (substr($lastmessage,3,1) != "-") or (empty($lastmessage)) )
break;
}
//身份验证
if ($this->check=="1") {
//验证开始
$lastact="AUTH LOGIN"."
";
fputs( $fp, $lastact);
$lastmessage = fgets ($fp,512);
if (substr($lastmessage,0,3) != 334) return "3:$lastmessage".__LINE__;
//用户姓名
$lastact=base64_encode($this->username)."
";
fputs( $fp, $lastact);
$lastmessage = fgets ($fp,512);
if (substr($lastmessage,0,3) != 334) return "4:$lastmessage".__LINE__;
//用户密码
$lastact=base64_encode($this->password)."
";
fputs( $fp, $lastact);
$lastmessage = fgets ($fp,512);
if (substr($lastmessage,0,3) != "235") return "5:$lastmessage".__LINE__;
}
//FROM:
$lastact="MAIL FROM: <". $this->s_from . ">
";
fputs( $fp, $lastact);
$lastmessage = fgets ($fp,512);
if (substr($lastmessage,0,3) != 250) return "6:$lastmessage".__LINE__;
//TO:
$lastact="RCPT TO: <". $to .">
";
fputs( $fp, $lastact);
$lastmessage = fgets ($fp,512);
if (substr($lastmessage,0,3) != 250) return "7:$lastmessage".__LINE__;
//DATA
$lastact="DATA
";
fputs($fp, $lastact);
$lastmessage = fgets ($fp,512);
if (substr($lastmessage,0,3) != 354) return "8:$lastmessage".__LINE__;
//处理Subject头
$head="Subject: $subject
";
$message = $head."
".$message;
//处理From头
$head="From: $from
";
$message = $head.$message;
//处理To头
$head="To: $to
";
$message = $head.$message;
//加上结束串
$message .= "
.
";
//发送信息
fputs($fp, $message);
$lastact="QUIT
";

@fputs($fp,$lastace);
fclose($fp);
return -1;
}



function send_mail ($send_mail_id,$table ,$text){
    if(bs_lib::get_configs_value('mail','set')==0)return '没有开启邮件发送';
    
    
$end = $this->send($send_mail_id,$this->s_from,$table,$text);
return $end;  
}


}









//$smail = new classsmail('smtp.163.com','@163.com','','@163.com',25);
//$end = $smail->send('@qq.com','@163.com','标题','内容');
//
//if( $end ) echo $end;
//else echo "<script>alert('发送成功');</script>";


//function Send_Mail ($shou_mail,$table ,$text){
//	global $global_array_set;
//    
//$smail = new classsmail($global_array_set['ini_mail_host'],$global_array_set['ini_mail_user'],$global_array_set['ini_mail_pwd'],$global_array_set['ini_mail_user'],$global_array_set['ini_mail_host_roet']);
//$end = $smail->send($shou_mail,$global_array_set['ini_mail_post_user'],$table,$text);
//  return $end;  
//}
//
//

?>