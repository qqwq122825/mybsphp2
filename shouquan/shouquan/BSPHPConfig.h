#import <Foundation/Foundation.h>

// ============================================================
// 以下三项直接从后台「软件管理 → 软件配置 → 通信安全」复制。
// ============================================================

// 卡串验证软件
#define BSPHP_CARD_APP_CODE @"1001"
#define BSPHP_CARD_APP_NAME @"测试"

// 服务器地址
#define BSPHP_CARD_HOST @"http://127.0.0.1:8091/AppEn.php?appid=1001&md5=ca257abd34534b41cf89066b7e59ae17"

// 通信认证 Key
#define BSPHP_CARD_MUTUALKEY @"93e002a9176bc4be5802bd392db52c3a"

// RSA 服务器公钥（客户端只放公钥，服务端私钥不进入 App）
#define BSPHP_CARD_PUBLIC_KEY @"-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAufPuW8K2qkNsnMC0G3zddn2UF6xgoK1P9LIzsZOOcBUli6qWFHbD\nIbjhbnv8ZhL0zVTJ6kTIfBNfCBJCttoolAG24BdgAg91hnTAI1c9J073SQLdFMJV\nObamrHGNC1Irnu7sOVtYuo3JBE7BvRgjw26ghdvj1jUWlTPTArnwagsVxJ3pIIdd\nkjjxgj7Mk4z3p6vALR0O/ibPd+7QuBZ3VjI7ctqMHcyDcom6GePsVo//x9DsaE+i\npbiSVkzhsyqSSJlmo0J8wD4d2b6+YWC9GiCJ+lya/Wn5ItCEitnNUIF0p715d43b\nvtMioycJQEdnrnt1MLPUDdlEE3IRE7YtHwIDAQAB\n-----END RSA PUBLIC KEY-----\n"

// 账户密码验证软件
#define BSPHP_ACCOUNT_APP_CODE @"1002"
#define BSPHP_ACCOUNT_APP_NAME @"账户登录Demo"
#define BSPHP_ACCOUNT_HOST @"http://127.0.0.1:8091/AppEn.php?appid=1002&md5=3fa723394db16c02178b243711b03f3b"
#define BSPHP_ACCOUNT_MUTUALKEY @"51cdf14898e7a7e79db861f684e7bac4"
#define BSPHP_ACCOUNT_PUBLIC_KEY @"-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEA5Wsq7vQSxEX3/UsVARHaUa+J3ixM6F0Zts+9whJqyERY8QYo1OqQ\nptFSqKi1CbpO0pNPf39mcPJFJP5zshPYzWXIWZLF8nqXu81ttWhGqLlcEc2pxOvi\nxjP9qDHrhTdYMHnQLtAOwmzNMGqd0ESe+rOvJAkXrNEKFuubLjcCB0rgsJfXH/hr\nshyqQS15kGt7S79ZhFVPz1NUnhBLOr+1jBiXV3WGNV1yaFVJBeT3H94dwXLkSmQG\n803AU6K8+rggVxC/GYh2Oql2bKRBI/MIEFxOu5Gv5LYEk4p4iPpgRam/dZPoAl59\nktfGrMwrEGeAVAMo7BQ5JF/uuiEzHOPkZwIDAQAB\n-----END RSA PUBLIC KEY-----\n"

// 兼容只接入卡串模式的旧调用示例。
#define BSPHP_APP_CODE BSPHP_CARD_APP_CODE
#define BSPHP_APP_NAME BSPHP_CARD_APP_NAME
#define BSPHP_HOST BSPHP_CARD_HOST
#define BSPHP_MUTUALKEY BSPHP_CARD_MUTUALKEY
#define BSPHP_SERVER_PUBLIC_KEY BSPHP_CARD_PUBLIC_KEY

// 每次请求校验前缀。Demo 会自动追加毫秒时间戳和 UUID，服务端原样返回后再比对。
#define BSPHP_APPSAFECODE @"shouquan-demo"
#define BSPHP_HEARTBEAT_INTERVAL 30.0
