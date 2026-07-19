<?php

namespace App\Auth;

enum AdminPermission: string
{
    case DashboardView = 'dashboard.view';
    case OnlineUsersView = 'online_users.view';
    case RiskEventsView = 'risk.view';
    case AuditView = 'audit.view';

    case AdministratorsView = 'administrators.view';
    case AdministratorsCreate = 'administrators.create';
    case AdministratorsUpdate = 'administrators.update';
    case AdministratorsDelete = 'administrators.delete';

    case ApplicationsView = 'applications.view';
    case ApplicationsCreate = 'applications.create';
    case ApplicationsUpdate = 'applications.update';
    case ApplicationsStatusUpdate = 'applications.status.update';
    case ApplicationsProtocolManage = 'applications.protocol.manage';
    case AppAccountsManage = 'app_accounts.manage';

    case CardsView = 'cards.view';
    case CardsSecretView = 'cards.secret.view';
    case CardsDelete = 'cards.delete';
    case CardsRevokeAny = 'cards.revoke_any';
    case CardTypesManage = 'card_types.manage';
    case BatchesView = 'batches.view';
    case BatchesCreate = 'batches.create';
    case CardsImport = 'cards.import';
    case CardsIssue = 'cards.issue';
    case CardsIssueBulk = 'cards.issue.bulk';
    case CardsExport = 'cards.export';
    case CardIssuesView = 'card_issues.view';

    case UsersView = 'users.view';
    case AgentsView = 'agents.view';
    case UsersCreate = 'users.create';
    case UsersManage = 'users.manage';

    case SettingsView = 'settings.view';
    case SettingsAppearanceUpdate = 'settings.appearance.update';
    case PermissionsManage = 'permissions.manage';
    case DevicesUnbind = 'devices.unbind';
    case DevicesRebindApprove = 'devices.rebind_approve';
    case LegacyManage = 'legacy.manage';

    public function module(): string
    {
        return match ($this) {
            self::DashboardView => 'dashboard',
            self::OnlineUsersView => 'online_users',
            self::RiskEventsView => 'risk',
            self::AuditView => 'audit',
            self::AdministratorsView,
            self::AdministratorsCreate,
            self::AdministratorsUpdate,
            self::AdministratorsDelete => 'administrators',
            self::ApplicationsView,
            self::ApplicationsCreate,
            self::ApplicationsUpdate,
            self::ApplicationsStatusUpdate,
            self::ApplicationsProtocolManage => 'applications',
            self::AppAccountsManage => 'app_accounts',
            self::CardsView,
            self::CardsSecretView,
            self::CardsDelete,
            self::CardsRevokeAny,
            self::CardsImport,
            self::CardsIssue,
            self::CardsIssueBulk,
            self::CardsExport => 'cards',
            self::CardTypesManage => 'card_types',
            self::BatchesView,
            self::BatchesCreate => 'batches',
            self::CardIssuesView => 'card_issues',
            self::UsersView,
            self::UsersCreate,
            self::UsersManage => 'users',
            self::AgentsView => 'agents',
            self::SettingsView,
            self::SettingsAppearanceUpdate => 'settings',
            self::PermissionsManage => 'permissions',
            self::DevicesUnbind,
            self::DevicesRebindApprove => 'devices',
            self::LegacyManage => 'legacy',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => '查看管理首页',
            self::OnlineUsersView => '查看在线用户',
            self::RiskEventsView => '查看风险事件',
            self::AuditView => '查看系统审计',
            self::AdministratorsView => '查看后台管理员',
            self::AdministratorsCreate => '创建后台管理员',
            self::AdministratorsUpdate => '修改后台管理员',
            self::AdministratorsDelete => '删除后台管理员',
            self::ApplicationsView => '查看应用',
            self::ApplicationsCreate => '创建应用',
            self::ApplicationsUpdate => '修改应用',
            self::ApplicationsStatusUpdate => '启停应用',
            self::ApplicationsProtocolManage => '管理授权协议',
            self::AppAccountsManage => '管理软件账户',
            self::CardsView => '查看卡密',
            self::CardsSecretView => '查看完整卡密',
            self::CardsDelete => '删除卡密',
            self::CardsRevokeAny => '作废任意卡密',
            self::CardTypesManage => '管理卡种',
            self::BatchesView => '查看制卡批次',
            self::BatchesCreate => '创建制卡批次',
            self::CardsImport => '导入卡密',
            self::CardsIssue => '手动发卡',
            self::CardsIssueBulk => '批量手动发卡',
            self::CardsExport => '导出卡密',
            self::CardIssuesView => '查看发卡记录',
            self::UsersView => '查看用户账户',
            self::AgentsView => '查看代理账户',
            self::UsersCreate => '创建用户账户',
            self::UsersManage => '批量维护账户',
            self::SettingsView => '查看系统设置',
            self::SettingsAppearanceUpdate => '修改个性化设置',
            self::PermissionsManage => '管理角色权限',
            self::DevicesUnbind => '解绑设备',
            self::DevicesRebindApprove => '审批设备换绑',
            self::LegacyManage => '管理 Legacy API',
        };
    }

    public function riskLevel(): string
    {
        return match ($this) {
            self::CardsDelete,
            self::CardsRevokeAny,
            self::AdministratorsDelete,
            self::PermissionsManage,
            self::DevicesUnbind,
            self::DevicesRebindApprove,
            self::ApplicationsStatusUpdate => 'high',
            self::CardsSecretView,
            self::CardsExport,
            self::CardsIssue,
            self::CardsIssueBulk,
            self::ApplicationsProtocolManage,
            self::AppAccountsManage,
            self::AdministratorsCreate,
            self::AdministratorsUpdate => 'sensitive',
            default => 'normal',
        };
    }

    public function agentAssignable(): bool
    {
        return in_array($this, [
            self::DashboardView,
            self::ApplicationsView,
            self::CardsView,
            self::CardTypesManage,
            self::BatchesView,
            self::CardsIssue,
            self::CardIssuesView,
        ], true);
    }
}
