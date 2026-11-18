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
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar | Padel Booking</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="logo">🎾</div>
    <h1>Buat Akun</h1>
    <p class="sub">Daftar gratis, langsung booking</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label>Nama lengkap</label>
        <input name="nama" placeholder="Nama kamu" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="nama@email.com" required>
      </div>
      <div class="form-group">
        <label>No. HP</label>
        <input name="no_hp" placeholder="08xxxxxxxxxx">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Minimal 6 karakter" required>
      </div>
      <button type="submit" class="btn">Daftar</button>
    </form>

    <p class="bawah">Sudah punya akun? <a href="login.php">Login</a></p>
  </div>
</div>
</body>
</html>