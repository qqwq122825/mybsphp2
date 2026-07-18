<?php
/**
 * 会话检查API
 * 检查用户是否已登录
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 引入BsPHP核心文件
require_once '../LibBsphp/Global.Bsphp.Inc.php';

class SessionCheckAPI {
    private $db;
    private $session;
    
    public function __construct() {
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
    }
    
    /**
     * 检查用户登录状态
     */
    public function checkSession() {
        try {
            // 检查会话变量
            $uid = get_session_value('USER_UID');
            $user_yse = get_session_value('USER_YSE');
            $user_date = get_session_value('USER_DATE');
            $user_ip = get_session_value('USER_IP');
            $user_md7 = get_session_value('USER_MD7');
            
            if (!$uid || !$user_yse || !$user_date || !$user_ip || !$user_md7) {
                // 尝试从Cookie恢复会话
                if ($this->restoreFromCookie()) {
                    $uid = get_session_value('USER_UID');
                } else {
                    return $this->errorResponse('未登录');
                }
            }
            
            // 验证会话有效性
            if (!$this->validateSession($uid, $user_yse, $user_date, $user_ip, $user_md7)) {
                $this->clearSession();
                return $this->errorResponse('会话已过期');
            }
            
            // 获取用户信息
            $userInfo = $this->getUserInfo($uid);
            if (!$userInfo) {
                $this->clearSession();
                return $this->errorResponse('用户不存在');
            }
            
            return $this->successResponse('已登录', [
                'userInfo' => $userInfo,
                'redirect' => $this->getRedirectUrl()
            ]);
            
        } catch (Exception $e) {
            error_log('会话检查错误: ' . $e->getMessage());
            return $this->errorResponse('会话检查失败');
        }
    }
    
    /**
     * 从Cookie恢复会话
     */
    private function restoreFromCookie() {
        if (!isset($_COOKIE['remember_login'])) {
            return false;
        }
        
        try {
            $cookieValue = base64_decode($_COOKIE['remember_login']);
            $parts = explode('|', $cookieValue);
            
            if (count($parts) !== 2) {
                return false;
            }
            
            $uid = $parts[0];
            $md7 = $parts[1];
            
            // 查询用户信息
            $sql = "SELECT `user_uid`, `user_user`, `user_pwd`, `user_Login_ip`, `user_Login_date` 
                    FROM `bs_php_user` 
                    WHERE `user_uid` = '$uid' AND `user_IsLock` = 0";
            
            $user = $this->db->Bsphp_my_array($sql);
            
            if (!$user) {
                return false;
            }
            
            // 验证MD7
            $cookies_pwd = $this->hashPassword($user['user_pwd']);
            $expected_md7 = $this->generateMD7($uid, $cookies_pwd, $user['user_Login_date'], $user['user_Login_ip'], 'ALL');
            
            if ($md7 !== $expected_md7) {
                return false;
            }
            
            // 恢复会话
            set_session_value("USER_UID", $uid);
            set_session_value('USER_YSE', $cookies_pwd);
            set_session_value('USER_DATE', $user['user_Login_date']);
            set_session_value('USER_IP', $user['user_Login_ip']);
            set_session_value('USER_MD7', $md7);
            
            return true;
            
        } catch (Exception $e) {
            error_log('Cookie恢复会话错误: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 验证会话有效性
     */
    private function validateSession($uid, $user_yse, $user_date, $user_ip, $user_md7) {
        try {
            // 检查IP是否匹配
            if ($user_ip !== Bsphp_HOST_IP) {
                return false;
            }
            
            // 检查会话超时（24小时）
            $loginTime = strtotime($user_date);
            if (time() - $loginTime > 24 * 60 * 60) {
                return false;
            }
            
            // 验证MD7
            $expected_md7 = $this->generateMD7($uid, $user_yse, $user_date, $user_ip, 'ALL');
            if ($user_md7 !== $expected_md7) {
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('会话验证错误: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取用户信息
     */
    private function getUserInfo($uid) {
        $sql = "SELECT `user_uid`, `user_user`, `user_email`, `user_qq`, `user_Mobile`, 
                       `user_daili`, `user_rmb`, `user_jifen`, `user_Login_date`, `user_Login_ip`
                FROM `bs_php_user` 
                WHERE `user_uid` = '$uid'";
        
        return $this->db->Bsphp_my_array($sql);
    }
    
    /**
     * 清除会话
     */
    private function clearSession() {
        // 清除会话变量
        unset($_SESSION['USER_UID']);
        unset($_SESSION['USER_YSE']);
        unset($_SESSION['USER_DATE']);
        unset($_SESSION['USER_IP']);
        unset($_SESSION['USER_MD7']);
        
        // 清除Cookie
        if (isset($_COOKIE['remember_login'])) {
            setcookie('remember_login', '', time() - 3600, '/', '', false, true);
        }
    }
    
    /**
     * 密码哈希处理
     */
    private function hashPassword($password) {
        if (function_exists('password_md6')) {
            return password_md6($password);
        } elseif (function_exists('md6')) {
            return md6($password);
        } else {
            return md5($password);
        }
    }
    
    /**
     * 生成MD7验证码
     */
    private function generateMD7($uid, $pwd, $date, $ip, $loginNum) {
        if (function_exists('cookies_md7')) {
            return cookies_md7($uid . $pwd . $date . $ip . $loginNum);
        } else {
            return md5($uid . $pwd . $date . $ip . $loginNum);
        }
    }
    
    /**
     * 获取重定向URL
     */
    private function getRedirectUrl() {
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
            'loggedIn' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ]);
    }
    
    /**
     * 错误响应
     */
    private function errorResponse($message) {
        return json_encode([
            'success' => false,
            'loggedIn' => false,
            'message' => $message,
            'timestamp' => time()
        ]);
    }
}

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $api = new SessionCheckAPI();
    echo $api->checkSession();
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'loggedIn' => false,
        'message' => '只支持GET请求'
    ]);
}
?>
