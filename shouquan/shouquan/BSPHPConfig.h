#import <Foundation/Foundation.h>

// ============================================================
// 以下三项直接从后台「软件管理 → 软件配置 → 通信安全」复制。
// ============================================================

// 卡串验证软件
#define BSPHP_CARD_APP_CODE @"1001"
#define BSPHP_CARD_APP_NAME @"测试"

// 服务器地址
#define BSPHP_CARD_HOST @"https://yz.jk92.cc/AppEn.php?appid=100001&md5=49a94345b05f9902d73534ff7e1520e7"

// 通信认证 Key
#define BSPHP_CARD_MUTUALKEY @"ec2722714481d934ce7255ff1c150dd1"

// RSA 服务器公钥（客户端只放公钥，服务端私钥不进入 App）
#define BSPHP_CARD_PUBLIC_KEY @"MIIBCgKCAQEAhc/MH+J1mT8zYA9JJirfXPw44OIYtogVXot+PCiEW8FSG1sTbeKNEuPrRnCS6P5SZC5xs53OMUryHmeFlF+6aQgnyVKW5iin8KisrFtj426vRtbhiab2fo9Dczv17Lhz2mERBQCaOia+Q8tWX1RedOchMObp3IpbSpYs6nmoG7QXTa4GMbHQte3XtX02allp/3t07hG1Bxl+BxxwucBY1fkOgegSkKg/B3C+CNbf4XtT024PgTaS5FV/6wnreKxJI7Koqjzd7bxeZRCRMzJlcp3QNisw0LNYcmax59JdcxrPu0vsJx8+DajzF5dYczAR1D/sDB09w4g6vYZDWqvEmQIDAQAB"

// 账户密码验证软件
#define BSPHP_ACCOUNT_APP_CODE @"1002"
#define BSPHP_ACCOUNT_APP_NAME @"账户登录Demo"
#define BSPHP_ACCOUNT_HOST @"http://127.0.0.1:8091/AppEn.php?appid=1002&md5=3fa723394db16c02178b243711b03f3b"
#define BSPHP_ACCOUNT_MUTUALKEY @"51cdf14898e7a7e79db861f684e7bac4"
#define BSPHP_ACCOUNT_PUBLIC_KEY @"MIIBCgKCAQEA5Wsq7vQSxEX3/UsVARHaUa+J3ixM6F0Zts+9whJqyERY8QYo1OqQptFSqKi1CbpO0pNPf39mcPJFJP5zshPYzWXIWZLF8nqXu81ttWhGqLlcEc2pxOvixjP9qDHrhTdYMHnQLtAOwmzNMGqd0ESe+rOvJAkXrNEKFuubLjcCB0rgsJfXH/hrshyqQS15kGt7S79ZhFVPz1NUnhBLOr+1jBiXV3WGNV1yaFVJBeT3H94dwXLkSmQG803AU6K8+rggVxC/GYh2Oql2bKRBI/MIEFxOu5Gv5LYEk4p4iPpgRam/dZPoAl59ktfGrMwrEGeAVAMo7BQ5JF/uuiEzHOPkZwIDAQAB"

// 兼容只接入卡串模式的旧调用示例。
#define BSPHP_APP_CODE BSPHP_CARD_APP_CODE
#define BSPHP_APP_NAME BSPHP_CARD_APP_NAME
#define BSPHP_HOST BSPHP_CARD_HOST
#define BSPHP_MUTUALKEY BSPHP_CARD_MUTUALKEY
#define BSPHP_SERVER_PUBLIC_KEY BSPHP_CARD_PUBLIC_KEY

// 每次请求校验前缀。Demo 会自动追加毫秒时间戳和 UUID，服务端原样返回后再比对。
#define BSPHP_APPSAFECODE @"shouquan-demo"
#define BSPHP_HEARTBEAT_INTERVAL 30.0
