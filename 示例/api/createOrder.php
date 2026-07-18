<?php

// 设置目标 URL
$targetUrl = "https://gateway.fakamiao.com/card-core/order/createOrder";

// 获取请求中的 POST 数据
$postData = file_get_contents("php://input");

// 初始化 cURL 会话
$ch = curl_init($targetUrl);

// 设置 cURL 选项
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // 返回请求结果，而不是直接输出
curl_setopt($ch, CURLOPT_POST, true);             // 设置请求为 POST 方法
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);  // 设置请求体为接收到的 POST 数据
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json', // 设置请求头为 JSON 格式
    'Content-Length: ' . strlen($postData) // 设置请求体长度
));

// 执行 cURL 请求
$response = curl_exec($ch);

// 检查 cURL 请求是否成功
if ($response === false) {
    $error = curl_error($ch);
    // 记录错误并返回
    echo json_encode(['msg' => '请求失败', 'error' => $error]);
    curl_close($ch);
    exit;
}

// 获取 HTTP 状态码
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// 关闭 cURL 会话
curl_close($ch);

// 根据返回的 HTTP 状态码判断请求是否成功
if ($httpCode === 200) {
    // 请求成功，返回原始响应
    echo $response;
} else {
    // 请求失败，返回错误信息
    echo json_encode(['msg' => '请求失败', 'status_code' => $httpCode]);
}

?>
