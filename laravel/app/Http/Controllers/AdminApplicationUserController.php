<?php

namespace App\Http\Controllers;

use App\Install\InstallationManager;
use App\Models\Administrator;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class AdminApplicationUserController extends Controller
{
    public function __construct(
        private readonly InstallationManager $installation,
    ) {}

    public function index(Request $request, string $adminPath, Application $application): View
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $status = trim((string) $request->query('status', ''));

        if ($application->mode === 'card_login') {
            $query = $application->cards()
                ->with('cardType')
                ->where('status', '!=', 'inventory')
                ->latest();

            if ($keyword !== '') {
                $query->where(function ($builder) use ($keyword): void {
                    $builder
                        ->where('code_last4', 'like', '%'.$keyword.'%')
                        ->orWhere('public_id', 'like', '%'.$keyword.'%');
                });
            }
            if ($status !== '') {
                $query->where('status', $status);
            }

            $users = $query->paginate(30)->withQueryString();
            $userMetrics = [
                'total' => $application->cards()->where('status', '!=', 'inventory')->count(),
                'active' => $application->cards()->where('status', 'active')->count(),
                'frozen' => $application->cards()->where('status', 'frozen')->count(),
                'expired' => $application->cards()->where('status', 'expired')->count(),
            ];
        } else {
            $query = $application->accounts()->latest();
            if ($keyword !== '') {
                $query->where('username', 'like', '%'.$keyword.'%');
            }
            if ($status !== '') {
                $query->where('status', $status);
            }

            $users = $query->paginate(30)->withQueryString();
            $userMetrics = [
                'total' => $application->accounts()->count(),
                'active' => $application->accounts()->where('status', 'active')->count(),
                'frozen' => $application->accounts()->where('status', 'frozen')->count(),
                'expired' => $application->accounts()->where('status', 'expired')->count(),
            ];
        }

        return view('admin.applications.users', [
            ...$this->layout($adminPath),
            'application' => $application,
            'users' => $users,
            'userMetrics' => $userMetrics,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function layout(string $adminPath): array
    {
        return [
            'appName' => $this->installation->applicationName(),
            'adminPath' => $adminPath,
            'administrator' => $this->administrator(),
            'databaseHealthy' => true,
        ];
    }

    private function administrator(): Administrator
    {
        $administrator = Auth::guard('admin')->user();
        abort_unless($administrator instanceof Administrator, 401);

        return $administrator;
    }
}
