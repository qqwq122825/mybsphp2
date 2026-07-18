<?php
declare(strict_types=1);

namespace App\Database;

use PDO;
use Throwable;

final class MigrationRunner
{
    public function __construct(private readonly PDO $pdo, private readonly string $directory)
    {
    }

    /** @return list<string> */
    public function run(): array
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                migration VARCHAR(190) PRIMARY KEY,
                executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $completed = $this->pdo->query('SELECT migration FROM schema_migrations')
            ->fetchAll(PDO::FETCH_COLUMN);
        $executed = [];
        foreach (glob(rtrim($this->directory, '/') . '/*.sql') ?: [] as $file) {
            $migration = basename($file);
            if (in_array($migration, $completed, true)) {
                continue;
            }
            try {
                $this->pdo->exec((string) file_get_contents($file));
                $statement = $this->pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
                $statement->execute([$migration]);
                $executed[] = $migration;
            } catch (Throwable $exception) {
                throw new \RuntimeException("迁移 {$migration} 执行失败：{$exception->getMessage()}", 0, $exception);
            }
        }
        return $executed;
    }
}

