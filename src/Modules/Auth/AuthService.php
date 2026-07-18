<?php
declare(strict_types=1);

namespace App\Modules\Auth;

use App\Database\Database;
use App\Support\Env;

final class AuthService
{
    public function authenticateAdministrator(
        string $username,
        string $password,
        string $secondaryPassword = ''
    ): bool
    {
        $statement = Database::connection()->prepare(
            "SELECT DISTINCT
                u.id,
                u.username,
                u.password_hash,
                u.secondary_password_hash,
                u.secondary_password_required,
                u.status
             FROM users u
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE u.username = :username
               AND r.code IN ('super_admin', 'admin')
             LIMIT 1"
        );
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();
        if (!is_array($user) || (int) $user['status'] !== 1) {
            return false;
        }
        if (!password_verify($password, (string) $user['password_hash'])) {
            return false;
        }
        $secondaryPasswordEnabled = Env::get('ADMIN_SECONDARY_PASSWORD_ENABLED', false) === true;
        $secondaryPasswordRequired = (int) $user['secondary_password_required'] === 1;
        if (
            $secondaryPasswordEnabled
            && $secondaryPasswordRequired
            && (
                $secondaryPassword === ''
                || !password_verify(
                    $secondaryPassword,
                    (string) ($user['secondary_password_hash'] ?? '')
                )
            )
        ) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['auth'] = [
            'user_id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'area' => 'admin',
            'authenticated_at' => time(),
        ];
        return true;
    }

    public function isAdministrator(): bool
    {
        return isset($_SESSION['auth']['user_id'])
            && ($_SESSION['auth']['area'] ?? null) === 'admin';
    }

    /** @return array{user_id:int,username:string}|null */
    public function administrator(): ?array
    {
        if (!$this->isAdministrator()) {
            return null;
        }
        return [
            'user_id' => (int) $_SESSION['auth']['user_id'],
            'username' => (string) $_SESSION['auth']['username'],
        ];
    }

    public function logout(): void
    {
        unset($_SESSION['auth']);
        session_regenerate_id(true);
    }
}
