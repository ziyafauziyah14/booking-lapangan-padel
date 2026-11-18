<?php
require 'includes/init.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($nama === '' || $email === '' || $pass === '') {
        $error = 'Nama, email, dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $cek = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $cek->execute([$email]);
        if ($cek->fetch()) {
            $error = 'Email sudah terdaftar.';
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO users (nama, email, no_hp, password_hash, role)
                 VALUES (?, ?, ?, ?, "user")'
            );
            $ins->execute([$nama, $email, $no_hp, password_hash($pass, PASSWORD_DEFAULT)]);
            header('Location: login.php?daftar=sukses');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Daftar</title></head>
<body>
<h1>Daftar Akun</h1>
<?php if ($error): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>
<form method="post">
    <input name="nama" placeholder="Nama lengkap" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input name="no_hp" placeholder="No. HP"><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Daftar</button>
</form>
<p>Sudah punya akun? <a href="login.php">Login</a></p>
</body>
</html>