<?php

namespace App\Console\Commands;

use App\Install\InstallationManager;
use App\Install\ReinstallationTokenManager;
use Illuminate\Console\Command;

final class IssueReinstallationLink extends Command
{
    protected $signature = 'mybsphp:reinstall-link {--ttl=30 : 有效分钟数，范围 1～120}';

    protected $description = '为已安装站点生成一次性重装维护链接';

    public function handle(
        InstallationManager $installation,
        ReinstallationTokenManager $tokens,
    ): int {
        if (! $installation->isInstalled()) {
            $this->error('站点尚未安装，请直接访问 /install。');

            return self::FAILURE;
        }

        $ttl = max(1, min((int) $this->option('ttl'), 120));
        $token = $tokens->issue($ttl);
        $url = rtrim((string) config('app.url'), '/').'/install/reinstall?token='.$token;

        $this->info("一次性重装链接（{$ttl} 分钟内有效）：");
        $this->line($url);
        $this->warn('链接首次成功进入维护 Session 后立即失效。覆盖前请备份数据库。');

        return self::SUCCESS;
    }
}
