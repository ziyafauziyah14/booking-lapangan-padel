<?php
require 'includes/init.php';

if (!empty($_SESSION['user'])) {
    header('Location: ' . halaman_role($_SESSION['user']['role']));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $q = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $q->execute([$email]);
    $u = $q->fetch();

    if ($u && password_verify($pass, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'   => $u['id'],
            'nama' => $u['nama'],
            'role' => $u['role'],
        ];
        header('Location: ' . halaman_role($u['role']));
        exit;
    }
    $error = 'Email atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | Padel Booking</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="logo">🎾</div>
    <h1>Padel Booking</h1>
    <p class="sub">Masuk untuk booking lapangan</p>

    <?php if (isset($_GET['daftar'])): ?>
      <div class="alert alert-sukses">Akun dibuat, silakan login.</div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="nama@email.com" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Password" required>
      </div>
      <button type="submit" class="btn">Login</button>
    </form>

    <p class="bawah">Belum punya akun? <a href="register.php">Daftar</a></p>
  </div>
</div>
</body>
</html>