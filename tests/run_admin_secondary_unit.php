<?php
declare(strict_types=1);

use App\Modules\Install\InstallerService;

require dirname(__DIR__) . '/vendor/autoload.php';

$installer = new InstallerService(dirname(__DIR__));

$valid = [
    'username' => 'admin',
    'password' => 'abc123',
    'password_confirmation' => 'abc123',
    'secondary_password' => 'xyz789',
    'secondary_password_confirmation' => 'xyz789',
];
$installer->validateAdministrator($valid);

$expectFailure = static function (array $values, string $message) use ($installer): void {
    try {
        $installer->validateAdministrator($values);
    } catch (RuntimeException $exception) {
        if (!str_contains($exception->getMessage(), $message)) {
            throw new RuntimeException('Unexpected validation message: ' . $exception->getMessage());
        }
        return;
    }
    throw new RuntimeException('Expected validation failure: ' . $message);
};

$expectFailure(
    array_replace($valid, ['password' => '12345', 'password_confirmation' => '12345']),
    '管理员密码至少6位'
);
$expectFailure(
    array_replace($valid, ['secondary_password' => '12345', 'secondary_password_confirmation' => '12345']),
    '管理员二级密码至少6位'
);
$expectFailure(
    array_replace($valid, ['secondary_password_confirmation' => 'different']),
    '两次输入的管理员二级密码不一致'
);

echo "Admin secondary password validation tests passed.\n";
