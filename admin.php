<?php
require 'includes/init.php';
wajib_role('admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin | Padel Booking</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="navbar">
  <span class="brand">🎾 Padel Booking | Admin</span>
  <span>Halo, <?= e($_SESSION['user']['nama']) ?> <a href="logout.php">Logout</a></span>
</div>

<div class="container">
  <div class="card">
    <h2>Dashboard Admin</h2>
    <p>Halaman ini dikerjakan Orang 3 (tabel users, bookings, payments).</p>
  </div>
</div>
</body>
  ad
</html>
