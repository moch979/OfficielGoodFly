<?php

require __DIR__ . '/_init.php';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $current = (string) ($_POST['current_password'] ?? '');
    $next = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    $username = trim((string) ($_POST['username'] ?? ''));
    $statement = db()->prepare('SELECT password_hash FROM admins WHERE id = ?');
    $statement->execute([(int) $admin['id']]);
    $row = $statement->fetch();
    if (!$row || !password_verify($current, $row['password_hash'])) {
        flash_set('err', 'كلمة المرور الحالية غير صحيحة.');
    } elseif ($username === '' || !preg_match('/^[A-Za-z0-9_\.\-]{3,40}$/', $username)) {
        flash_set('err', 'اسم المستخدم يجب أن يكون 3 أحرف إنجليزية على الأقل بلا مسافات.');
    } elseif (strlen($next) < 8) {
        flash_set('err', 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.');
    } elseif ($next !== $confirm) {
        flash_set('err', 'تأكيد كلمة المرور غير مطابق.');
    } else {
        try {
            db()->prepare('UPDATE admins SET username = ?, password_hash = ? WHERE id = ?')->execute([
                $username,
                password_hash($next, PASSWORD_DEFAULT),
                (int) $admin['id'],
            ]);
            db()->prepare("UPDATE settings SET svalue = '1' WHERE skey = 'password_changed'")->execute();
            flash_set('ok', 'تم تحديث الحساب.');
        } catch (PDOException $e) {
            flash_set('err', 'اسم المستخدم مستخدم من قبل.');
        }
    }
    redirect('account.php');
}

admin_header('الحساب وكلمة المرور', 'account.php');
echo '<form class="card" method="post" style="max-width:560px">' . csrf_field();
echo '<label>اسم المستخدم</label><input name="username" required value="' . h($admin['username']) . '">';
echo '<label>كلمة المرور الحالية</label><input type="password" name="current_password" required>';
echo '<label>كلمة المرور الجديدة</label><input type="password" name="new_password" required>';
echo '<label>تأكيد كلمة المرور</label><input type="password" name="confirm_password" required>';
echo '<div style="margin-top:16px"><button class="btn">حفظ الحساب</button></div></form>';
admin_footer();
