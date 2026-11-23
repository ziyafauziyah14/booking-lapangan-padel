<?php
require 'includes/init.php';
wajib_role('admin');

$ringkasan = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM users WHERE role = 'user') AS total_users,
        (SELECT COUNT(*) FROM bookings) AS total_bookings,
        (SELECT COUNT(*) FROM payments WHERE status = 'pending') AS pembayaran_pending
")->fetch();

$users = $pdo->query("
    SELECT id, nama, email, no_hp, created_at
    FROM users
    WHERE role = 'user'
    ORDER BY created_at DESC
")->fetchAll();

$bookings = $pdo->query("
    SELECT
        bookings.kode_booking,
        users.nama AS nama_user,
        courts.nama AS nama_lapangan,
        bookings.tanggal,
        bookings.jam_mulai,
        bookings.jam_selesai,
        bookings.total_harga,
        bookings.status
    FROM bookings
    JOIN users ON users.id = bookings.user_id
    JOIN courts ON courts.id = bookings.court_id
    ORDER BY bookings.created_at DESC
")->fetchAll();

$payments = $pdo->query("
    SELECT
        bookings.kode_booking,
        users.nama AS nama_user,
        payments.jumlah,
        payments.metode,
        payments.status,
        payments.paid_at
    FROM payments
    JOIN bookings ON bookings.id = payments.booking_id
    JOIN users ON users.id = bookings.user_id
    ORDER BY payments.id DESC
")->fetchAll();
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
    <p>Total pengguna: <strong><?= e($ringkasan['total_users']) ?></strong></p>
    <p>Total booking: <strong><?= e($ringkasan['total_bookings']) ?></strong></p>
    <p>Pembayaran menunggu: <strong><?= e($ringkasan['pembayaran_pending']) ?></strong></p>
  </div>

  <div class="card">
    <h2>Data Pengguna</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Nama</th>
            <th>Email</th>
            <th>No. HP</th>
            <th>Tanggal Daftar</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$users): ?>
            <tr><td colspan="4">Belum ada pengguna terdaftar.</td></tr>
          <?php else: ?>
            <?php foreach ($users as $user): ?>
              <tr>
                <td><?= e($user['nama']) ?></td>
                <td><?= e($user['email']) ?></td>
                <td><?= e($user['no_hp'] ?: '-') ?></td>
                <td><?= e($user['created_at'] ?: '-') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h2>Data Booking</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Kode</th>
            <th>Pengguna</th>
            <th>Lapangan</th>
            <th>Tanggal</th>
            <th>Jam</th>
            <th>Total</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$bookings): ?>
            <tr><td colspan="7">Belum ada booking.</td></tr>
          <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
              <tr>
                <td><?= e($booking['kode_booking']) ?></td>
                <td><?= e($booking['nama_user']) ?></td>
                <td><?= e($booking['nama_lapangan']) ?></td>
                <td><?= e($booking['tanggal']) ?></td>
                <td><?= e(substr($booking['jam_mulai'], 0, 5)) ?>–<?= e(substr($booking['jam_selesai'], 0, 5)) ?></td>
                <td>Rp<?= e(number_format((int) $booking['total_harga'], 0, ',', '.')) ?></td>
                <td><span class="badge badge-<?= e($booking['status']) ?>"><?= e(ucfirst($booking['status'])) ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h2>Data Pembayaran</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Kode Booking</th>
            <th>Pengguna</th>
            <th>Jumlah</th>
            <th>Metode</th>
            <th>Status</th>
            <th>Waktu Bayar</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$payments): ?>
            <tr><td colspan="6">Belum ada data pembayaran.</td></tr>
          <?php else: ?>
            <?php foreach ($payments as $payment): ?>
              <tr>
                <td><?= e($payment['kode_booking']) ?></td>
                <td><?= e($payment['nama_user']) ?></td>
                <td>Rp<?= e(number_format((int) $payment['jumlah'], 0, ',', '.')) ?></td>
                <td><?= e($payment['metode'] ?: '-') ?></td>
                <td><span class="badge"><?= e(ucfirst($payment['status'])) ?></span></td>
                <td><?= e($payment['paid_at'] ?: '-') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
