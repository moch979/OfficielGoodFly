<?php

/**
 * Restore points of everything the admin can edit. Bookings, subscribers and
 * admin accounts are deliberately left out so restoring old content never
 * deletes customer requests.
 */
const SNAPSHOT_KEEP = 30;

/** Parents before children so restores satisfy foreign keys. */
function snapshot_tables(): array
{
    return [
        'settings', 'nav_links', 'hero_slides', 'stats', 'about_points', 'package_categories',
        'packages', 'package_features', 'services', 'calc_tiers', 'cities', 'city_features',
        'guides', 'testimonials', 'footer_links', 'filter_options', 'blog_posts',
    ];
}

function snapshot_payload(): array
{
    $tables = [];
    foreach (snapshot_tables() as $table) {
        $tables[$table] = rows("SELECT * FROM `{$table}`");
    }
    return ['app' => 'goodfly', 'version' => 1, 'created_at' => date('c'), 'tables' => $tables];
}

function create_snapshot(string $label): int
{
    $json = json_encode(snapshot_payload(), JSON_UNESCAPED_UNICODE);
    $admin = current_admin();
    db()->prepare('INSERT INTO snapshots (label, payload, size_bytes, created_by, created_at) VALUES (?, ?, ?, ?, ?)')
        ->execute([str_limit($label, 190), $json, strlen($json), $admin['username'] ?? '', date('Y-m-d H:i:s')]);
    $id = (int) db()->lastInsertId();

    $old = db()->query('SELECT id FROM snapshots ORDER BY id DESC LIMIT 1000 OFFSET ' . SNAPSHOT_KEEP)->fetchAll(PDO::FETCH_COLUMN);
    if ($old) {
        $marks = implode(', ', array_fill(0, count($old), '?'));
        db()->prepare("DELETE FROM snapshots WHERE id IN ($marks)")->execute($old);
    }
    return $id;
}

function table_columns(string $table): array
{
    return array_column(rows("SHOW COLUMNS FROM `{$table}`"), 'Field');
}

function restore_payload(array $payload): void
{
    if (($payload['app'] ?? '') !== 'goodfly' || !is_array($payload['tables'] ?? null)) {
        throw new RuntimeException('الملف ليس نسخة احتياطية من هذا الموقع.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (array_reverse(snapshot_tables()) as $table) {
            if (isset($payload['tables'][$table])) {
                $pdo->exec("DELETE FROM `{$table}`");
            }
        }
        foreach (snapshot_tables() as $table) {
            if (!isset($payload['tables'][$table]) || !is_array($payload['tables'][$table])) {
                continue;
            }
            $columns = table_columns($table);
            foreach ($payload['tables'][$table] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $row = array_intersect_key($row, array_flip($columns));
                if (!$row) {
                    continue;
                }
                $names = implode(', ', array_map(static function ($column) {
                    return "`{$column}`";
                }, array_keys($row)));
                $marks = implode(', ', array_fill(0, count($row), '?'));
                $pdo->prepare("INSERT INTO `{$table}` ({$names}) VALUES ({$marks})")->execute(array_values($row));
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        throw $e;
    }
}

function restore_snapshot(int $id): void
{
    $statement = db()->prepare('SELECT payload FROM snapshots WHERE id = ?');
    $statement->execute([$id]);
    $json = $statement->fetchColumn();
    if ($json === false) {
        throw new RuntimeException('نقطة الاسترجاع غير موجودة.');
    }
    $payload = json_decode((string) $json, true);
    if (!is_array($payload)) {
        throw new RuntimeException('نقطة الاسترجاع تالفة.');
    }
    create_snapshot('تلقائية: قبل الاسترجاع إلى #' . $id);
    restore_payload($payload);
}

/** Full SQL dump of every table, including bookings, for off-site backups. */
function stream_sql_dump(): void
{
    $pdo = db();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "-- Good Fly backup " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";
    foreach ($tables as $table) {
        $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        echo "DROP TABLE IF EXISTS `{$table}`;\n", $create[1], ";\n\n";
        $statement = $pdo->query("SELECT * FROM `{$table}`");
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $values = array_map(static function ($value) use ($pdo) {
                return $value === null ? 'NULL' : $pdo->quote((string) $value);
            }, array_values($row));
            echo "INSERT INTO `{$table}` (`" . implode('`, `', array_keys($row)) . "`) VALUES (" . implode(', ', $values) . ");\n";
        }
        echo "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS = 1;\n";
}
