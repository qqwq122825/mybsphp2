<?php
/**
 * 现代化登录API接口
 * 兼容BsPHP数据库结构
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 引入BsPHP核心文件
require_once '../LibBsphp/Global.Bsphp.Inc.php';

class ModernLoginAPI {
    private $db;
    private $session;
    private $config;
    
    public function __construct() {
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
        $this->config = bs_lib::get_configs_value('user');
    }
    
    /**
     * 处理登录请求
     */
    public function handleLogin() {
        try {
            // 获取POST数据
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                return $this->errorResponse('无效的请求数据');
            }
            
            $username = trim($input['username'] ?? '');
            $password = $input['password'] ?? '';
            $captcha = trim($input['captcha'] ?? '');
            $rememberMe = $input['rememberMe'] ?? false;
            
            // 验证输入
            $validation = $this->validateInput($username, $password, $captcha);
            if (!$validation['valid']) {
                return $this->errorResponse($validation['message']);
            }
            
            // 检查登录尝试次数
            if ($this->isAccountLocked($username)) {
                return $this->errorResponse('账户已被锁定，请稍后再试');
            }
            
            // 执行登录
            $loginResult = $this->performLogin($username, $password, $rememberMe);
            
            if ($loginResult['success']) {
                // 记录登录成功
                $this->logLoginAttempt($username, true);
                
                return $this->successResponse('登录成功', [
                    'redirect' => $this->getRedirectUrl(),
                    'userInfo' => $loginResult['userInfo']
                ]);
            } else {
                // 记录登录失败
                $this->logLoginAttempt($username, false);
                
                return $this->errorResponse($loginResult['message']);
            }
            
        } catch (Exception $e) {
            error_log('登录错误: ' . $e->getMessage());
            return $this->errorResponse('系统错误，请稍后重试');
        }
    }
    
    /**
     * 验证输入数据
     */
    private function validateInput($username, $password, $captcha) {
        // 验证用户名
        if (empty($username)) {
            return ['valid' => false, 'message' => '请输入用户名'];
        }
        
        if (strlen($username) < 2 || strlen($username) > 20) {
            return ['valid' => false, 'message' => '用户名长度必须在2-20个字符之间'];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]+$/u', $username)) {
            return ['valid' => false, 'message' => '用户名只能包含字母、数字、下划线和中文'];
        }
        
        // 验证密码
        if (empty($password)) {
            return ['valid' => false, 'message' => '请输入密码'];
        }
        
        if (strlen($password) < 6 || strlen($password) > 20) {
            return ['valid' => false, 'message' => '密码长度必须在6-20个字符之间'];
        }
        
        // 验证验证码（这里简化处理，实际应该从session中获取）
        if (empty($captcha)) {
            return ['valid' => false, 'message' => '请输入验证码'];
        }
        
        if (strlen($captcha) !== 4) {
            return ['valid' => false, 'message' => '验证码必须是4位'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * 执行登录逻辑
     */
    private function performLogin($username, $password, $rememberMe) {
        try {
            // 查询用户信息
            $sql = "SELECT `user_uid`, `user_user`, `user_pwd`, `user_IsLock`, `user_LoGinNum`, 
                           `user_Login_ip`, `user_Login_date`, `user_daili`, `user_email`, `user_qq`
                    FROM `bs_php_user` 
                    WHERE `user_user` LIKE CONVERT(_utf8'$username'USING utf8)COLLATE utf8_general_ci";
            
            $user = $this->db->Bsphp_my_array($sql);
            
            if (!$user) {
                return ['success' => false, 'message' => '用户名或密码错误'];
            }
            
            // 检查账户状态
            if ($user['user_IsLock'] != 0) {
                return ['success' => false, 'message' => '账户已被锁定，请联系管理员'];
            }
            
            // 检查代理权限
            if ($user['user_daili'] > 0 && BSPHP_SET !== 'AGENT') {
                return ['success' => false, 'message' => '代理账户无法在此处登录'];
            }
            
            // 验证密码
            $hashedPassword = $this->hashPassword($password);
            if (strtolower($user['user_user']) !== strtolower($username) || $hashedPassword !== $user['user_pwd']) {
                return ['success' => false, 'message' => '用户名或密码错误'];
            }
            
            // 更新登录信息
            $this->updateLoginInfo($user['user_uid']);
            
            // 设置会话
            $this->setUserSession($user, $rememberMe);
            
            return [
                'success' => true,
                'userInfo' => [
                    'uid' => $user['user_uid'],
                    'username' => $user['user_user'],
                    'email' => $user['user_email'],
                    'qq' => $user['user_qq'],
                    'isAgent' => $user['user_daili'] > 0
                ]
            ];
            
        } catch (Exception $e) {
            error_log('登录执行错误: ' . $e->getMessage());
            return ['success' => false, 'message' => '登录失败，请稍后重试'];
        }
    }
    
    /**
     * 密码哈希处理（兼容BsPHP的md6加密）
     */
    private function hashPassword($password) {
        // 使用BsPHP的md6函数
        if (function_exists('password_md6')) {
            return password_md6($password);
        } elseif (function_exists('md6')) {
            return md6($password);
        } else {
            // 备用方案
            return md5($password);
        }
    }
    
    /**
     * 更新登录信息
     */
    private function updateLoginInfo($uid) {
        $date = HOST_DATE;
        $ip = Bsphp_HOST_IP;
        $unix = HOST_UNIX;
        
        $sql = "UPDATE `bs_php_user` 
                SET `user_DenJi_tmp`='$unix', `user_Login_ip`='$ip', `user_Login_date`='$date', 
                    `user_CaoShi`='$unix', `user_LoGinNum`=`user_LoGinNum`+1 
                WHERE `user_uid`='$uid'";
        
        $this->db->Bsphp_my_query($sql);
    }
    
    /**
     * 设置用户会话
     */
    private function setUserSession($user, $rememberMe) {
        $uid = $user['user_uid'];
        $cookies_pwd = $this->hashPassword($user['user_pwd']);
        $cookies_ip = $user['user_Login_ip'];
        $cookies_DATE = $user['user_Login_date'];
        $LoGinNum = 'ALL';
        
        // 生成MD7验证码
        $cookies_md7 = $this->generateMD7($uid, $cookies_pwd, $cookies_DATE, $cookies_ip, $LoGinNum);
        
        // 设置会话变量
        set_session_value("USER_UID", $uid);
        set_session_value('USER_YSE', $cookies_pwd);
        set_session_value('USER_DATE', $cookies_DATE);
        set_session_value('USER_IP', $cookies_ip);
        set_session_value('USER_MD7', $cookies_md7);
        
        // 如果选择记住我，设置Cookie
        if ($rememberMe) {
            $this->setRememberMeCookie($uid, $cookies_md7);
        }
    }
    
    /**
     * 生成MD7验证码（兼容BsPHP）
     */
    private function generateMD7($uid, $pwd, $date, $ip, $loginNum) {
        if (function_exists('cookies_md7')) {
            return cookies_md7($uid . $pwd . $date . $ip . $loginNum);
        } else {
            // 备用方案
            return md5($uid . $pwd . $date . $ip . $loginNum);
        }
    }
    
    /**
     * 设置记住我Cookie
     */
    private function setRememberMeCookie($uid, $md7) {
        $cookieValue = base64_encode($uid . '|' . $md7);
        setcookie('remember_login', $cookieValue, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    }
    
    /**
     * 检查账户是否被锁定
     */
    private function isAccountLocked($username) {
        // 检查最近15分钟内的失败尝试
        $lockTime = date('Y-m-d H:i:s', time() - (15 * 60));
        
        $sql = "SELECT COUNT(*) as attempts 
                FROM `bs_php_login_log` 
                WHERE `username` = '$username' 
                AND `success` = 0 
                AND `login_time` > '$lockTime'";
        
        $result = $this->db->Bsphp_my_array($sql);
        
        return ($result['attempts'] ?? 0) >= 5;
    }
    
    /**
     * 记录登录尝试
     */
    private function logLoginAttempt($username, $success) {
        try {
            // 创建登录日志表（如果不存在）
            $this->createLoginLogTable();
            
            $sql = "INSERT INTO `bs_php_login_log` 
                    (`username`, `ip`, `success`, `login_time`, `user_agent`) 
                    VALUES ('$username', '" . Bsphp_HOST_IP . "', " . ($success ? 1 : 0) . ", 
                            '" . HOST_DATE . "', '" . ($_SERVER['HTTP_USER_AGENT'] ?? '') . "')";
            
            $this->db->Bsphp_my_query($sql);
        } catch (Exception $e) {
            error_log('记录登录日志失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 创建登录日志表
     */
    private function createLoginLogTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `bs_php_login_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `ip` varchar(45) NOT NULL,
            `success` tinyint(1) NOT NULL,
            `login_time` datetime NOT NULL,
            `user_agent` text,
            PRIMARY KEY (`id`),
            KEY `username` (`username`),
            KEY `login_time` (`login_time`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
        
        $this->db->Bsphp_my_query($sql);
    }
    
    /**
     * 获取重定向URL
     */
    private function getRedirectUrl() {
        // 根据用户类型和系统设置返回不同的重定向URL
        if (BSPHP_SET === 'ADMIN') {
            return 'admin/dashboard.php';
        } elseif (BSPHP_SET === 'AGENT') {
            return 'agent/dashboard.php';
        } else {
            return 'user/dashboard.php';
        }
    }
    
    /**
     * 成功响应
     */
    private function successResponse($message, $data = []) {
        return json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ]);
    }
    
    /**
     * 错误响应
     */
    private function errorResponse($message, $code = 400) {
        http_response_code($code);
        return json_encode([
            'success' => false,
            'message' => $message,
            'timestamp' => time()
        ]);
    }
}

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api = new ModernLoginAPI();
    echo $api->handleLogin();
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '只支持POST请求'
    ]);
}
?>
