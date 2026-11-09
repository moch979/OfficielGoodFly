<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Cache-Control: no-store');
if (current_admin()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (login_locked($username)) {
        $error = 'محاولات كثيرة خاطئة. انتظر 15 دقيقة ثم حاول مجدداً.';
    } else {
        $statement = db()->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
        $statement->execute([$username]);
        $admin = $statement->fetch();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            login_succeeded($username);
            if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
            }
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_seen'] = time();
            redirect('index.php');
        }
        login_failed($username);
        usleep(400000);
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>دخول الإدارة | Good Fly</title>
    <link rel="icon" href="../favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0F2350;font-family:Tajawal,Tahoma,sans-serif;color:#1D3C78}
        form{width:min(420px,92vw);background:#F4F8FC;border-radius:24px;padding:28px;border:1px solid #F2C14E}
        h1{margin:0 0 8px;color:#0F2350} p{color:#3F4E66}
        label{display:block;font-weight:700}
        input{width:100%;padding:12px;border-radius:12px;border:1px solid #d9e1ec;margin:8px 0 14px;font-family:inherit;box-sizing:border-box}
        button{width:100%;border:0;border-radius:999px;padding:12px;font-weight:800;background:linear-gradient(135deg,#F2C14E,#E3A93A);cursor:pointer;font-family:inherit}
        .err{background:#fdecec;padding:10px 12px;border-radius:10px;margin-bottom:10px}
    </style>
</head>
<body>
    <form method="post">
        <?= csrf_field() ?>
        <?php if (s('logo_image') !== ''): ?>
        <img src="<?= h(asset_url(s('logo_image'))) ?>" alt="<?= e('logo_alt', 'Good Fly Travel') ?>" style="height:72px;display:block;margin:0 auto 8px">
        <?php else: ?>
        <h1>Good Fly</h1>
        <?php endif; ?>
        <p>لوحة التحكم — غيّر أي تفصيل في الموقع من هنا.</p>
        <?php if ($error !== ''): ?><div class="err" role="alert"><?= h($error) ?></div><?php endif; ?>
        <label for="username">اسم المستخدم</label>
        <input id="username" name="username" required autofocus autocomplete="username" maxlength="40">
        <label for="password">كلمة المرور</label>
        <input id="password" type="password" name="password" required autocomplete="current-password">
        <button type="submit">دخول</button>
    </form>
</body>
</html>
