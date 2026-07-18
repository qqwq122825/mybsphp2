<?php
/**
 * login.ic —— 卡密登录接口（随机盐版，UTC 对齐）
 * 返回：
 * 01|xx|<encryptedLogin>||<daySig>|1|<vipDate>|1|<bindKey>||
 * 其中 <encryptedLogin> 为 5 字节：[1字节salt | 4字节密文] → 10 位十六进制
 */

/* ===== 常量 ===== */
define('MASTER_KEY', 123456789);   // 勿泄漏，需与客户端一致
define('kLCG',       1103515245);  // ANSI C rand() multiplier
define('kMixVal',    0xA5A5A5A5);  // XOR mask

/* ===== PHP 5.4 兼容：random_int polyfill（仅用于盐） ===== */
if (!function_exists('random_int')) {
    function random_int($min, $max) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $b = openssl_random_pseudo_bytes(4);
            $n = (ord($b[0]) << 24) | (ord($b[1]) << 16) | (ord($b[2]) << 8) | ord($b[3]);
            $n = $n & 0x7FFFFFFF;
            $range = $max - $min + 1;
            return $min + ($n % $range);
        }
        return mt_rand($min, $max);
    }
}

/* ===== 工具 ===== */
function u32($x) { return $x & 0xFFFFFFFF; }

/** 解析 32 位地址：支持 "0x..." 或 十进制 */
function parse_addr32($v) {
    if (is_string($v)) {
        $s = trim($v);
        if (stripos($s, '0x') === 0) {
            $dec = base_convert(substr($s, 2), 16, 10); // 避免 32 位 PHP 的浮点坑
            return u32((int)$dec);
        }
        return u32((int)$s);
    }
    return u32((int)$v);
}

/** UTC（与 daySig 一致）下的每日签名 */
function today_sig_fixed8() {
    $ymd = (int)gmdate('Ymd'); // UTC
    $mix = u32( ($ymd + MASTER_KEY) * kLCG );
    $mix ^= kMixVal;
    $b36 = strtoupper(base_convert((string)$mix, 10, 36));
    if (strlen($b36) < 8) {
        $pad = chr(65 + ($mix & 0xF)); // A..P
        $b36 = str_pad($b36, 8, $pad, STR_PAD_LEFT);
    }
    return substr($b36, 0, 8);
}

/** 随机盐的增强 XOR 加密（key 仅依赖 ymd/master/salt；与客户端一致） */
function enhanced_xor_encrypt($address) {
    $address = parse_addr32($address);
    $ymd  = (int)gmdate('Ymd');     // UTC，与客户端保持一致
    $salt = random_int(0, 255);     // 随机盐：导致密文每天每次都可能不同

    // Key 派生（32 位环绕）
    $key  = u32( ($ymd + MASTER_KEY) * kLCG );
    $key ^= kMixVal;
    $key ^= u32($salt * 0x9E3779B1);
    $key  = u32($key);

    // 地址拆字节（大端）
    $b = array(
        ($address >> 24) & 0xFF,
        ($address >> 16) & 0xFF,
        ($address >>  8) & 0xFF,
        ($address      ) & 0xFF
    );

    // 三层异或
    $enc = array();
    for ($i = 0; $i < 4; $i++) {
        $x  = $b[$i];
        $x ^= (($key >> (8 * $i))       & 0xFF);      // 第一层
        $x ^= (($key >> (8 * (3 - $i))) & 0xFF);      // 第二层
        $x ^= (($key >> (16 + $i * 4))  & 0xFF);      // 第三层
        $enc[] = $x & 0xFF;
    }

    // 输出：[salt][enc0..enc3] → 10 hex
    array_unshift($enc, $salt & 0xFF);
    $hex = '';
    foreach ($enc as $byte) {
        $hex .= strtoupper(str_pad(dechex($byte & 0xFF), 2, '0', STR_PAD_LEFT));
    }
    return $hex;
}

/* ========= 业务逻辑（保持你原有流程） ========== */
$icid    = set_data('icid');
$icpwd   = set_data('icpwd');
$key     = set_data('key');
$maxoror = set_data('maxoror');
$daihao  = HOST_DAIHAO;

if ($Global_DB_AppDaTa_Array['app_MoShi'] !== 'CardTerm') {
    PrInfo($this->user_str_log[1119]);
}

$log = CarLogin($icid, $icpwd, $key, $daihao);
if ($log == 1069) $log = CarLogin($icid, $icpwd, $key, $daihao);
$log = CarLogin($icid, $icpwd, $key, $daihao);

if ($log == 1081) {
    $linkLog = Login_links_mx($icid, $daihao, $maxoror, $icid);
    if ($linkLog != 5047) PrInfo($this->appen_str_log[$linkLog]);

    $carinfo = GetCardInfo($icid, $icpwd, $daihao);
    if ($carinfo == 1083 || $carinfo == 1084) {
        PrInfo($this->user_str_log[$carinfo]);
    }

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

    $vipDate = date('Y-m-d H:i:s', $carinfo['L_vip_unix']);
    $bindKey = $carinfo['L_key_info'];
    $daySig  = today_sig_fixed8(); // UTC

    // 取待加密地址（如 "0xD890840"）
    $login_info   = $Global_DB_AppDaTa_Array['app_logininfo'];
    $addressValue = parse_addr32($login_info);

    // 加密（随机盐）
    $encryptedLogin = enhanced_xor_encrypt($addressValue);

    // 返回（协议未变）
    PrInfo("01|xx|{$encryptedLogin}||{$daySig}|1|{$vipDate}|1|{$bindKey}||");

} else {
    PrInfo($this->user_str_log[$log]);
}