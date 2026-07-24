<?php

namespace App\Http\Controllers;

use App\Install\InstallationManager;
use App\Support\OperationalMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SiteController extends Controller
{
    public function __invoke(InstallationManager $installation, OperationalMode $operationalMode): View|RedirectResponse
    {
        if (! $installation->isInstalled()) {
            return redirect()->route('install.show');
        }

        return view('site.home', [
            'appName' => $installation->applicationName(),
            'isProduction' => ! $operationalMode->isDebug(),
        ]);
    }
}
