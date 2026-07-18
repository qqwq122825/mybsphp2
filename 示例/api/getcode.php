 <?php
 header('Access-Control-Allow-Origin: *');  // 如果只允许特定域名，可以使用 'http://127.0.0.1:8848'

// 其他 CORS 相关头部
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');  // 如果需要允许携带凭证（如 Cookie）

//  $baseUrl = 'http://faka.mtx123.asia';  // 定义URL变量
//  $baseUrl = 'http://18.136.102.134';  // 定义URL变量
 
  $baseUrl = 'http://38u.me';  // 定义URL变量

 
if (isset($_GET['orderNo'])) {
    $orderNo = $_GET['orderNo'];
    // $url = 'https://api.fakamiao.shop/card-core/order/get';
     $url = 'https://gateway.fakamiao.com/card-core/order/get';
    
   

    $data = array('orderNo' => $orderNo);
    $options = array( 
        'http' => array(
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
        ), 
    );

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result !== false) {
       
        
        $response = json_decode($result, true);
       
        if (isset($response['data']['orderStatus']) && $response['data']['orderStatus'] == 0) {
            
            
            
              echo json_encode(['code' => 0, 'msg' => '订单状态正常', 'qrUrl' => $baseUrl . '/pay.php?orderNo=' . $orderNo]);
        } else {
            echo json_encode(['msg' => '支付超时']);
        }
    } else {
        echo json_encode(['msg' => '请求失败']);
    }
} else {
    echo json_encode(['msg' => '缺少订单号']);
}
?>
