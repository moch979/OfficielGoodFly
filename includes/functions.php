<?php

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config.php';
    }
    return $config;
}

function app_debug(): bool
{
    return !empty(app_config()['debug']);
}

function project_root(): string
{
    return dirname(__DIR__);
}

function storage_path(string $relative = ''): string
{
    $dir = project_root() . '/storage';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (!is_file($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "Require all denied\n");
    }
    return $relative === '' ? $dir : $dir . '/' . ltrim($relative, '/');
}

function log_error(string $message): void
{
    $logs = storage_path('logs');
    if (!is_dir($logs)) {
        @mkdir($logs, 0775, true);
    }
    @file_put_contents($logs . '/app-' . date('Y-m') . '.log', '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function wants_json(): bool
{
    return defined('GF_JSON') && GF_JSON;
}

function register_error_handling(): void
{
    error_reporting(E_ALL);
    ini_set('display_errors', app_debug() ? '1' : '0');
    ini_set('log_errors', '1');

    set_exception_handler(static function (Throwable $e): void {
        log_error(get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        if (!headers_sent()) {
            http_response_code(500);
        }
        if (wants_json()) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['ok' => false, 'message' => t('error_generic')], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (app_debug()) {
            echo '<pre>' . h($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
            return;
        }
        echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>Good Fly</title></head>';
        echo '<body style="font-family:Tahoma,sans-serif;background:#F4F8FC;color:#1D3C78;padding:40px;text-align:center">';
        echo '<h1>' . h(t('error_title')) . '</h1><p>' . h(t('error_generic')) . '</p></body></html>';
    });
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = app_config();
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['name'], $config['charset']);

    try {
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    } catch (PDOException $e) {
        log_error('DB connect: ' . $e->getMessage());
        install_required('تعذر الاتصال بقاعدة البيانات. تحقق من بيانات الاتصال أو ثبّت الموقع من صفحة التثبيت.');
    }

    return $pdo;
}

function boot(): void
{
    try {
        db()->query('SELECT 1 FROM settings LIMIT 1');
    } catch (PDOException $e) {
        install_required('جداول الموقع غير موجودة بعد.');
    }

    run_migrations();
    load_settings();

    if (!$GLOBALS['SETTINGS']) {
        install_required('قاعدة البيانات فارغة.');
    }
}

function install_required(string $message): void
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message . PHP_EOL . 'شغّل: php install.php' . PHP_EOL);
        exit(1);
    }
    if (wants_json()) {
        json_response(['ok' => false, 'message' => $message], 503);
    }

    $install = base_path() . '/install.php';
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>تثبيت Good Fly</title></head><body style="font-family:Tahoma,sans-serif;background:#F4F8FC;color:#1D3C78;padding:40px">';
    echo '<h1>الموقع غير مربوط بقاعدة البيانات بعد</h1>';
    echo '<p>' . h($message) . '</p>';
    echo '<p><a href="' . h($install) . '">فتح صفحة التثبيت</a></p>';
    echo '</body></html>';
    exit;
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
    return clean_rich_text(s($key, $default));
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
    foreach (['/admin', '/api'] as $suffix) {
        if (substr($dir, -strlen($suffix)) === $suffix) {
            $dir = substr($dir, 0, -strlen($suffix));
        }
    }
    return $dir === '/' ? '' : $dir;
}

function request_origin(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host)) {
        $host = 'localhost';
    }
    return ($https ? 'https' : 'http') . '://' . $host;
}

function is_https(): bool
{
    return strpos(request_origin(), 'https://') === 0;
}

/** Absolute site root without trailing slash, e.g. https://goodfly.ma */
function site_url(): string
{
    $configured = rtrim(trim(s('site_url')), '/');
    if ($configured !== '' && preg_match('#^https?://#i', $configured)) {
        return $configured;
    }
    return request_origin() . base_path();
}

function asset_url(string $path): string
{
    $path = trim($path);
    if ($path === '' || preg_match('#^(https?:)?//#i', $path) || strpos($path, 'data:') === 0) {
        return $path;
    }
    return base_path() . '/' . ltrim($path, '/');
}

function absolute_url(string $path): string
{
    $url = asset_url($path);
    if ($url === '' || preg_match('#^https?://#i', $url)) {
        return $url;
    }
    if (strpos($url, '//') === 0) {
        return 'https:' . $url;
    }
    return request_origin() . $url;
}

/** Keeps admin-entered links to schemes that cannot run script. */
function safe_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '#';
    }
    if (preg_match('#^(https?:|mailto:|tel:|\#|/|\./|\.\./)#i', $url)) {
        return $url;
    }
    if (!preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url)) {
        return $url;
    }
    return '#';
}

function json_for_script($data): string
{
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
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

function csrf_valid(): bool
{
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $known = $_SESSION['csrf'] ?? '';
    return is_string($sent) && is_string($known) && $known !== '' && hash_equals($known, $sent);
}

function csrf_verify(): void
{
    if (!csrf_valid()) {
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

const ADMIN_IDLE_SECONDS = 4 * 3600;

function current_admin(): ?array
{
    static $cached = false;
    if ($cached !== false) {
        return $cached;
    }
    if (empty($_SESSION['admin_id'])) {
        return $cached = null;
    }
    $last = (int) ($_SESSION['admin_seen'] ?? 0);
    if ($last > 0 && time() - $last > ADMIN_IDLE_SECONDS) {
        unset($_SESSION['admin_id'], $_SESSION['admin_seen']);
        return $cached = null;
    }
    $_SESSION['admin_seen'] = time();
    $statement = db()->prepare('SELECT id, username FROM admins WHERE id = ?');
    $statement->execute([(int) $_SESSION['admin_id']]);
    $admin = $statement->fetch();
    return $cached = ($admin ?: null);
}

function require_admin(): array
{
    $admin = current_admin();
    if (!$admin) {
        if (wants_json()) {
            json_response(['ok' => false, 'message' => 'يلزم تسجيل الدخول من جديد.'], 401);
        }
        redirect('login.php');
    }
    return $admin;
}

const UPLOAD_MAX_BYTES = 8 * 1024 * 1024;
const UPLOAD_MAX_WIDTH = 2000;

function save_upload(string $inputName): ?string
{
    if (empty($_FILES[$inputName]) || !is_array($_FILES[$inputName]) || is_array($_FILES[$inputName]['name'] ?? null)) {
        return null;
    }
    return save_uploaded_file($_FILES[$inputName]);
}

/** Saves every file of a multiple upload input such as name="file_gallery[]". */
function save_uploads(string $inputName): array
{
    $files = $_FILES[$inputName] ?? null;
    if (!is_array($files) || !is_array($files['name'] ?? null)) {
        return [];
    }
    $saved = [];
    foreach (array_keys($files['name']) as $index) {
        $path = save_uploaded_file([
            'name' => $files['name'][$index],
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ]);
        if ($path !== null) {
            $saved[] = $path;
        }
    }
    return $saved;
}

function save_uploaded_file(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || ($file['name'] ?? '') === '') {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('تعذر رفع الصورة.');
    }
    if (($file['size'] ?? 0) > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('حجم الصورة يجب ألا يتجاوز 8 ميغابايت.');
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

    $base = date('YmdHis') . '-' . bin2hex(random_bytes(4));
    $keepsTransparency = in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_GIF], true);
    if (image_tools_available() && !$keepsTransparency) {
        $target = $directory . '/' . $base . '.jpg';
        if (image_resize_to_jpeg($file['tmp_name'], $target, UPLOAD_MAX_WIDTH, 80)) {
            return 'uploads/' . $base . '.jpg';
        }
    }

    $filename = $base . '.' . $allowed[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('تعذر حفظ الصورة.');
    }
    return 'uploads/' . $filename;
}

function clean_rich_text(string $html): string
{
    $html = strip_tags($html, '<strong><b><em><i><u><br><span><a><p><ul><ol><li>');
    $html = preg_replace('#\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)#i', '', $html) ?? $html;
    $html = preg_replace('#\s(style|class|id)\s*=\s*(".*?"|\'.*?\'|[^\s>]+)#i', '', $html) ?? $html;
    $html = preg_replace_callback('#\shref\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))#i', static function ($m) {
        $url = $m[2] !== '' ? $m[2] : ($m[3] !== '' ? $m[3] : ($m[4] ?? ''));
        return ' href="' . h(safe_url(html_entity_decode($url, ENT_QUOTES, 'UTF-8'))) . '"';
    }, $html) ?? $html;
    return trim($html);
}

function wa_number(): string
{
    return preg_replace('/\D+/', '', s('wa_number')) ?? '';
}

function wa_link(string $message): string
{
    return 'https://wa.me/' . wa_number() . '?text=' . rawurlencode($message);
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function str_limit(string $value, int $max): string
{
    return mb_substr($value, 0, $max);
}

/** Latin URL fragment: "Good Fly – Umrah 2027" → "good-fly-umrah-2027". */
function slugify(string $text, int $max = 80): string
{
    $text = strtolower(trim($text));
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = strtolower($converted);
        }
    }
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim(substr(trim($text, '-'), 0, $max), '-');
}
