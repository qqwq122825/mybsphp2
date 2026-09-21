<?php

namespace App\Auth;

use App\Models\Administrator;
use Illuminate\Http\Request;

final class AdminSession
{
    public const KEY = 'admin_session_token';

    public function issue(Request $request, Administrator $administrator): bool
    {
        $token = bin2hex(random_bytes(32));
        // Compare the credentials that were verified: a concurrent password
        // reset/freeze must not be overwritten by an in-flight login.
        $updated = Administrator::query()->whereKey($administrator->getKey())
            ->where('status', 'active')
            ->where('password', $administrator->password)
            ->where('secondary_password', $administrator->secondary_password)
            ->where('secondary_password_required', $administrator->secondary_password_required)
            ->where('username', $administrator->username)
            ->where('role', $administrator->role)
            ->update(['admin_session_token_hash' => hash('sha256', $token)]);
        if ($updated !== 1) {
            return false;
        }
        $request->session()->put(self::KEY, $token);

        return true;
    }

    public function valid(Request $request, Administrator $administrator): bool
    {
        $token = $request->session()->get(self::KEY);
        $hash = $administrator->admin_session_token_hash;

        return $administrator->status === 'active'
            && is_string($token) && $token !== ''
            && is_string($hash) && $hash !== ''
            && hash_equals($hash, hash('sha256', $token));
    }

    public function revoke(Request $request, Administrator $administrator): void
    {
        $token = $request->session()->get(self::KEY);
        if (is_string($token) && $token !== '') {
            // An old browser logging out must not revoke a newer login.
            Administrator::query()->whereKey($administrator->getKey())
                ->where('admin_session_token_hash', hash('sha256', $token))
                ->update(['admin_session_token_hash' => null]);
        }
        $request->session()->forget(self::KEY);
    }
}
