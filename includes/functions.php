<?php

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config.php';
    }
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = app_config();
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['name'],
        $config['charset']
    );

    try {
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        install_required('تعذر الاتصال بقاعدة البيانات. ثبّت الموقع أولاً من صفحة التثبيت.');
    }

    return $pdo;
}

function boot(): void
{
    try {
        $rows = db()->query('SELECT skey, svalue FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        install_required('جداول الموقع غير موجودة بعد.');
    }

    if (!$rows) {
        install_required('قاعدة البيانات فارغة.');
    }

    $GLOBALS['SETTINGS'] = $rows;
    ensure_design_settings();
}

function install_required(string $message): void
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message . PHP_EOL . "شغّل: php install.php" . PHP_EOL);
        exit(1);
    }

    $install = base_path() . '/install.php';
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>تثبيت Good Fly</title></head><body style="font-family:Tahoma,sans-serif;background:#F7F5EF;color:#183B35;padding:40px">';
    echo '<h1>الموقع غير مربوط بقاعدة البيانات بعد</h1>';
    echo '<p>' . h($message) . '</p>';
    echo '<p><a href="' . h($install) . '">فتح صفحة التثبيت</a></p>';
    echo '</body></html>';
    exit;
}

function s(string $key, string $default = ''): string
{
    if (!isset($GLOBALS['SETTINGS'][$key]) || $GLOBALS['SETTINGS'][$key] === null) {
        return $default;
    }
    return (string) $GLOBALS['SETTINGS'][$key];
}

function flag(string $key, bool $default = true): bool
{
    $value = s($key, $default ? '1' : '0');
    return $value === '1' || $value === 'true' || $value === 'on';
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function e(string $key, string $default = ''): string
{
    return h(s($key, $default));
}

function raw_html(string $key, string $default = ''): string
{
    $html = s($key, $default);
    $html = preg_replace('#<\s*script\b[^>]*>.*?<\s*/\s*script\s*>#is', '', $html) ?? $html;
    $html = preg_replace('#\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)#i', '', $html) ?? $html;
    return $html;
}

function nl(string $key, string $default = ''): string
{
    return nl2br(h(s($key, $default)));
}

function rows(string $sql, array $params = []): array
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function base_path(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = rtrim(dirname($script), '/.');
    if (substr($dir, -6) === '/admin') {
        $dir = substr($dir, 0, -6);
    }
    if (substr($dir, -4) === '/api') {
        $dir = substr($dir, 0, -4);
    }
    return $dir === '/' ? '' : $dir;
}

function asset_url(string $path): string
{
    $path = trim($path);
    if ($path === '' || preg_match('#^(https?:)?//#i', $path) || strpos($path, 'data:') === 0) {
        return $path;
    }
    return base_path() . '/' . ltrim($path, '/');
}

function project_root(): string
{
    return dirname(__DIR__);
}

function json_for_script($data): string
{
    $json = json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    return $json === false ? 'null' : $json;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = $_POST['csrf'] ?? '';
    $known = $_SESSION['csrf'] ?? '';
    if (!is_string($sent) || !is_string($known) || $known === '' || !hash_equals($known, $sent)) {
        http_response_code(400);
        exit('انتهت صلاحية النموذج. أعد تحميل الصفحة ثم حاول مرة أخرى.');
    }
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_take(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    $statement = db()->prepare('SELECT id, username FROM admins WHERE id = ?');
    $statement->execute([(int) $_SESSION['admin_id']]);
    $admin = $statement->fetch();
    return $admin ?: null;
}

function require_admin(): array
{
    $admin = current_admin();
    if (!$admin) {
        redirect('login.php');
    }
    return $admin;
}

function save_upload(string $inputName): ?string
{
    if (empty($_FILES[$inputName]) || !is_array($_FILES[$inputName])) {
        return null;
    }

    $file = $_FILES[$inputName];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || ($file['name'] ?? '') === '') {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('تعذر رفع الصورة.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('حجم الصورة يجب ألا يتجاوز 5 ميغابايت.');
    }

    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($allowed[$info[2]])) {
        throw new RuntimeException('الملف يجب أن يكون صورة JPG أو PNG أو WEBP أو GIF.');
    }

    $directory = project_root() . '/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('تعذر إنشاء مجلد الصور.');
    }

    $filename = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$info[2]];
    $target = $directory . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('تعذر حفظ الصورة.');
    }

    return 'uploads/' . $filename;
}

function clean_rich_text(string $html): string
{
    $html = preg_replace('#<\s*script\b[^>]*>.*?<\s*/\s*script\s*>#is', '', $html) ?? $html;
    $html = preg_replace('#\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)#i', '', $html) ?? $html;
    return trim($html);
}

function wa_link(string $message): string
{
    $number = preg_replace('/\D+/', '', s('wa_number'));
    return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
}

function rate_limit(string $bucket, int $max, int $windowSeconds): bool
{
    $now = time();
    $key = 'rate_' . $bucket;
    $times = $_SESSION[$key] ?? [];
    if (!is_array($times)) {
        $times = [];
    }
    $times = array_values(array_filter($times, static function ($time) use ($now, $windowSeconds) {
        return is_int($time) && ($now - $time) < $windowSeconds;
    }));
    if (count($times) >= $max) {
        $_SESSION[$key] = $times;
        return false;
    }
    $times[] = $now;
    $_SESSION[$key] = $times;
    return true;
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
