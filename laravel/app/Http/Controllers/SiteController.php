<?php

namespace App\Http\Controllers;

use App\Install\InstallationManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SiteController extends Controller
{
    public function __invoke(InstallationManager $installation): View|RedirectResponse
    {
        if (! $installation->isInstalled()) {
            return redirect()->route('install.show');
        }

        if ($installation->adminPath() === 'admin') {
            return redirect('/admin');
        }

        return view('site.home', [
            'appName' => $installation->applicationName(),
        ]);
    }
}
