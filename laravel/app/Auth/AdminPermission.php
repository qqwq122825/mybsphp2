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
    case ApplicationsProtocolManage = 'applications.protocol.manage';
    case AppAccountsManage = 'app_accounts.manage';

    case CardsView = 'cards.view';
    case CardTypesManage = 'card_types.manage';
    case CardsImport = 'cards.import';
    case CardsIssue = 'cards.issue';
    case CardIssuesView = 'card_issues.view';

    case UsersView = 'users.view';
    case AgentsView = 'agents.view';
    case UsersCreate = 'users.create';
    case UsersManage = 'users.manage';

    case SettingsView = 'settings.view';
    case SettingsAppearanceUpdate = 'settings.appearance.update';
    case PermissionsManage = 'permissions.manage';
    case DevicesUnbind = 'devices.unbind';
    case LegacyManage = 'legacy.manage';
}
