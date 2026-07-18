# MyBsPHP2 主任务清单

更细的 `W阶段-任务号` 计划见 [AI_MASTER_PLAN.md](AI_MASTER_PLAN.md)，当前进度见 [PROJECT_STATUS.md](PROJECT_STATUS.md)。

状态：`TODO` / `DOING` / `REVIEW` / `DONE` / `BLOCKED`

## G0 调研与架构

| 编号 | 状态 | 任务 | 交付物 |
|---|---|---|---|
| G0-01 | DONE | 审阅旧版首次安装与后台入口 | `DEVELOPMENT_SPEC_DRAFT.md` |
| G0-02 | DONE | 审阅 `AppEn.php` 与 `applibapi` 分派 | Legacy 调用链记录 |
| G0-03 | DONE | 审阅 iOS `UIInitializer.mm` | 响应字段契约与错位说明 |
| G0-04 | DONE | 登录现有 BSPHP 后台并整理页面地图 | `UI_RESEARCH.md`、参考截图 |
| G0-05 | DONE | PHP 脚手架决策 | `FRAMEWORK_DECISION.md` |
| G0-06 | DONE | 确认首版功能范围 | `DEVELOPMENT_SPEC_DRAFT.md` |
| G0-07 | DONE | 数据库 ER 图和状态机 | `DATABASE_DESIGN.md` |
| G0-08 | DONE | 完整 API 目录 | `API_CATALOG.md` |
| G0-09 | DONE | 管理员二级密码策略 | `decisions/W0-8_ADMIN_SECONDARY_PASSWORD.md` |
| G0-10 | DONE | 代理层级与余额提卡规则 | `decisions/W0-9_AGENT_RULES.md` |

## G1 脚手架与安装

| 编号 | 状态 | 任务 | 测试 |
|---|---|---|---|
| G1-01 | TODO | 创建 Laravel 正式脚手架 | W0 验收门已通过 |
| G1-02 | TODO | 环境检测页 | Feature + 手机 UI |
| G1-03 | TODO | MySQL 配置与连接测试 | Feature |
| G1-04 | TODO | 站点与动态后台路径 | Feature |
| G1-05 | TODO | 创建超级管理员 | Feature |
| G1-06 | TODO | 安装锁与重复安装保护 | Feature |

## G2 认证与权限

| 编号 | 状态 | 任务 | 测试 |
|---|---|---|---|
| G2-01 | TODO | 管理员登录/退出 | Feature |
| G2-02 | TODO | 登录限流和 Session 轮换 | Security |
| G2-03 | TODO | RBAC 数据模型 | Migration + Unit |
| G2-04 | TODO | 代理权限模板 | Permission |
| G2-05 | TODO | 默认禁止删卡/解绑/账户管理 | Permission |

## G3 应用与手动发卡

| 编号 | 状态 | 任务 | 测试 |
|---|---|---|---|
| G3-01 | TODO | 应用管理 | Feature |
| G3-02 | TODO | 卡种和批次 | Feature |
| G3-03 | TODO | 卡密生成与导入 | Unit + Feature |
| G3-04 | TODO | 单张/批量手动发卡 | Transaction |
| G3-05 | TODO | TXT/CSV 导出 | Feature |
| G3-06 | TODO | 发卡记录 | Feature |

## G4 Legacy 协议

| 编号 | 状态 | 任务 | 测试 |
|---|---|---|---|
| G4-01 | TODO | `/AppEn.php` 网关 | Contract |
| G4-02 | TODO | 请求解包与时间窗口 | Contract |
| G4-03 | TODO | `login.ic` 处理器 | Contract |
| G4-04 | TODO | 固定字段响应器 | Contract |
| G4-05 | TODO | `arr[2]` 真实/空/诱饵配置 | Unit + Contract |
| G4-06 | TODO | 旧 iOS Fixture | End-to-end |

## G5 双业务模式

| 编号 | 状态 | 任务 | 测试 |
|---|---|---|---|
| G5-01 | TODO | 卡密直接登录 | Unit + Feature |
| G5-02 | TODO | 账户注册 | Feature |
| G5-03 | TODO | 账户登录 | Feature |
| G5-04 | TODO | 充值卡增加时长/点数 | Transaction |
| G5-05 | TODO | 充值幂等与并发 | Concurrency |

## G6 风控

| 编号 | 状态 | 任务 | 测试 |
|---|---|---|---|
| G6-01 | TODO | 应用风险策略 | Unit |
| G6-02 | TODO | 短时间多次登录规则 | Unit |
| G6-03 | TODO | 多设备规则 | Unit |
| G6-04 | TODO | Observe/Decoy/Block | Unit + Contract |
| G6-05 | TODO | 风险事件后台 | Feature + UI |

## G7 UI 调研与实现

| 编号 | 状态 | 任务 | 交付物 |
|---|---|---|---|
| G7-01 | DONE | 现有后台页面地图 | `UI_RESEARCH.md` |
| G7-02 | TODO | 登录页视觉规范 | 截图与组件说明 |
| G7-03 | TODO | 桌面后台壳 | UI 测试 |
| G7-04 | TODO | 手机后台壳 | 390px 截图 |
| G7-05 | TODO | 表格/卡片/表单组件 | 组件清单 |
