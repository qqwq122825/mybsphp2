<?php
/**
 * login.ic  ——  卡密登录接口（每日变签初版）
 * 环境：PHP 5.4
 * 十三哥工作室 © 2025
 * 返回格式：
 * 01|xx|1||<daySig>|1|<vipDate>|1|<bindKey>||
 */

/* ===== 服务器私钥：32-bit 整数即可 ===== */
define('MASTER_KEY', 123456789);           // 勿泄漏

/* -------------------------------------------------
 * today_sig_fixed8()
 * 生成「每天唯一、当天固定」的 8 字符 Base-36 串
 * 公式： (MASTER+YYYYMMDD) × 1103515245  ^ 0xA5A5A5A5
 * ------------------------------------------------- */
function today_sig_fixed8()
{
    /* ① 取当日 UTC 整数 YYYYMMDD */
    $ymd = intval(gmdate('Ymd'));                                     //  [oai_citation:0‡php.net](https://www.php.net/manual/en/function.gmdate.php?utm_source=chatgpt.com)

    /* ② 线性混淆：加 + 乘 LCG 常量 + XOR 掩码 */
    $mix = (($ymd + MASTER_KEY) * 1103515245) & 0xFFFFFFFF;           // 常量取自 ANSI C rand()  [oai_citation:1‡维基百科](https://en.wikipedia.org/wiki/Linear_congruential_generator?utm_source=chatgpt.com)
    $mix ^= 0xA5A5A5A5;                                               // Bitwise XOR  [oai_citation:2‡durak.org](https://durak.org/sean/pubs/software/php-5.4.6/language.operators.bitwise.html?utm_source=chatgpt.com)

    /* ③ 转 36 进制（0-9A-Z），再固定 8 位 */
    $b36 = strtoupper(base_convert($mix, 10, 36));                    //  [oai_citation:3‡php.net](https://www.php.net/manual/en/function.base-convert.php?utm_source=chatgpt.com)
    if (strlen($b36) < 8) {                                           // 长度不足左补随机字母
        $pad = chr(65 + ($mix & 0xF));                                // A-P 伪随机
        $b36 = str_pad($b36, 8, $pad, STR_PAD_LEFT);                  //  [oai_citation:4‡php.net](https://www.php.net/manual/en/function.str-pad.php?utm_source=chatgpt.com)
    }
    return substr($b36, 0, 8);                                        // 超过 8 截取
}

/* ========= 原有业务逻辑 ========== */
$icid    = set_data('icid');
$icpwd   = set_data('icpwd');
$key     = set_data('key');
$maxoror = set_data('maxoror');
$daihao  = HOST_DAIHAO;

if ($Global_DB_AppDaTa_Array['app_MoShi'] !== 'CardTerm') {
    PrInfo($this->user_str_log[1119]);
}

/* 登录流程（保持不变） */
$log = CarLogin($icid, $icpwd, $key, $daihao);
if ($log == 1069) $log = CarLogin($icid, $icpwd, $key, $daihao);
$log = CarLogin($icid, $icpwd, $key, $daihao);

if ($log == 1081) {

    /* 建立登录限制 */
    $linkLog = Login_links_mx($icid, $daihao, $maxoror, $icid);
    if ($linkLog != 5047) PrInfo($this->appen_str_log[$linkLog]);

    /* 查询用户信息 */
    $carinfo = GetCardInfo($icid, $icpwd, $daihao);
    if ($carinfo == 1083 || $carinfo == 1084) PrInfo($this->user_str_log[$carinfo]);

    /* 机器绑定校验（原逻辑略） */
    if ($Global_DB_AppDaTa_Array['app_set'] == 1) {
        if ((string)$carinfo['L_key_info'] !== (string)$key) {
            if (empty($carinfo['L_key_info'])) {
                Update_Login_Key($icid, $key);
            } else {
                set_session_value('ic_carid', '');
                set_session_value('ic_pwd',  '');
                set_session_value('USER_UID','');
                PrInfo('非绑定的机器');
            }
        }
    }

    /* ========= 生成每日签名并返回 ========= */
    $vipDate = date('Y-m-d H:i:s', $carinfo['L_vip_unix']);
    $bindKey = $carinfo['L_key_info'];
    $daySig  = today_sig_fixed8();          // ★ 每日变动字段

    /* 第 4 位改成 daySig，其余保持原位 */
    PrInfo("01|xx|1||{$daySig}|1|{$vipDate}|1|{$bindKey}||");

} else {
    PrInfo($this->user_str_log[$log]);
}
?>