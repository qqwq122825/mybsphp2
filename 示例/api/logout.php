<?php
/**
 * 退出登录API
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 引入BsPHP核心文件
require_once '../LibBsphp/Global.Bsphp.Inc.php';

class LogoutAPI {
    private $db;
    private $session;
    
    public function __construct() {
        $this->db = bs_lib::load_libbsphp_class('mysql', 'mysql');
        $this->session = bs_lib::load_libbsphp_class('session', 'session');
    }
    
    /**
     * 处理退出登录请求
     */
    public function handleLogout() {
        try {
            // 记录退出日志
            $this->logLogout();
            
            // 清除会话
            $this->clearSession();
            
            // 清除Cookie
            $this->clearCookies();
            
            return $this->successResponse('退出登录成功');
            
        } catch (Exception $e) {
            error_log('退出登录错误: ' . $e->getMessage());
            return $this->errorResponse('退出登录失败');
        }
    }
    
    /**
     * 记录退出日志
     */
    private function logLogout() {
        try {
            $uid = get_session_value('USER_UID');
            if ($uid) {
                // 创建退出日志表（如果不存在）
                $this->createLogoutLogTable();
                
                $sql = "INSERT INTO `bs_php_logout_log` 
                        (`user_uid`, `ip`, `logout_time`, `user_agent`) 
                        VALUES ('$uid', '" . Bsphp_HOST_IP . "', '" . HOST_DATE . "', 
                                '" . ($_SERVER['HTTP_USER_AGENT'] ?? '') . "')";
                
                $this->db->Bsphp_my_query($sql);
            }
        } catch (Exception $e) {
            error_log('记录退出日志失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 创建退出日志表
     */
    private function createLogoutLogTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `bs_php_logout_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_uid` int(11) NOT NULL,
            `ip` varchar(45) NOT NULL,
            `logout_time` datetime NOT NULL,
            `user_agent` text,
            PRIMARY KEY (`id`),
            KEY `user_uid` (`user_uid`),
            KEY `logout_time` (`logout_time`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
        
        $this->db->Bsphp_my_query($sql);
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
        
        // 销毁会话
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
    
    /**
     * 清除Cookie
     */
    private function clearCookies() {
        // 清除记住我Cookie
        if (isset($_COOKIE['remember_login'])) {
            setcookie('remember_login', '', time() - 3600, '/', '', false, true);
        }
        
        // 清除其他可能的登录相关Cookie
        $cookiesToClear = ['login_token', 'user_session', 'auth_token'];
        foreach ($cookiesToClear as $cookieName) {
            if (isset($_COOKIE[$cookieName])) {
                setcookie($cookieName, '', time() - 3600, '/', '', false, true);
            }
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
    $api = new LogoutAPI();
    echo $api->handleLogout();
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '只支持POST请求'
    ]);
}
?>
