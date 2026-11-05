<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (current_admin()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $statement = db()->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
    $statement->execute([$username]);
    $admin = $statement->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        redirect('index.php');
    }
    $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>دخول الإدارة | Good Fly</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#102924;font-family:Tajawal,Tahoma,sans-serif;color:#183B35}
        form{width:min(420px,92vw);background:#F7F5EF;border-radius:24px;padding:28px;border:1px solid #D8C39B}
        h1{margin:0 0 8px;color:#102924} p{color:#405650}
        input{width:100%;padding:12px;border-radius:12px;border:1px solid #e6e1d6;margin:8px 0 14px;font-family:inherit}
        button{width:100%;border:0;border-radius:999px;padding:12px;font-weight:800;background:linear-gradient(135deg,#D8C39B,#C4A976);cursor:pointer}
        .err{background:#fdecec;padding:10px 12px;border-radius:10px}
    </style>
</head>
<body>
    <form method="post">
        <?= csrf_field() ?>
        <h1>Good Fly</h1>
        <p>لوحة التحكم — غيّر أي تفصيل في الموقع من هنا.</p>
        <?php if ($error !== ''): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
        <label>اسم المستخدم</label>
        <input name="username" required autofocus>
        <label>كلمة المرور</label>
        <input type="password" name="password" required>
        <button type="submit">دخول</button>
    </form>
</body>
</html>
