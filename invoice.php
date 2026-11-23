<?php
require 'includes/init.php';
wajib_role('user');

$user = $_SESSION['user'];
$error = '';
$kode_booking = $_GET['kode_booking'] ?? '';

if (!is_string($kode_booking)) {
    $kode_booking = '';
    $error = 'Kode booking tidak valid.';
} else {
    $kode_booking = trim($kode_booking);
}

$invoice = null;
$riwayat = [];

if ($kode_booking !== '' && !$error) {
    $q = $pdo->prepare("
        SELECT
            bookings.kode_booking,
            bookings.tanggal,
            bookings.jam_mulai,
            bookings.jam_selesai,
            bookings.total_harga,
            bookings.status AS status_booking,
            bookings.created_at,
            users.nama AS nama_user,
            users.email,
            courts.nama AS nama_lapangan,
            payments.jumlah AS jumlah_pembayaran,
            payments.metode AS metode_pembayaran,
            payments.status AS status_pembayaran,
            payments.paid_at
        FROM bookings
        JOIN users ON users.id = bookings.user_id
        JOIN courts ON courts.id = bookings.court_id
        LEFT JOIN payments ON payments.id = (
            SELECT pembayaran_terbaru.id
            FROM payments AS pembayaran_terbaru
            WHERE pembayaran_terbaru.booking_id = bookings.id
            ORDER BY pembayaran_terbaru.id DESC
            LIMIT 1
        )
        WHERE bookings.kode_booking = ? AND bookings.user_id = ?
        LIMIT 1
    ");
    $q->execute([$kode_booking, $user['id']]);
    $invoice = $q->fetch();

    if (!$invoice) {
        $error = 'Invoice tidak ditemukan.';
    }
} elseif (!$error) {
    $q = $pdo->prepare("
        SELECT kode_booking, tanggal, total_harga, status
        FROM bookings
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $q->execute([$user['id']]);
    $riwayat = $q->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Invoice | Padel Booking</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="navbar">
  <span class="brand">🎾 Padel Booking | Invoice</span>
  <span>Halo, <?= e($user['nama']) ?> <a href="booking.php">Booking</a> <a href="pembayaran.php">Pembayaran</a> <a href="logout.php">Logout</a></span>
</div>

<div class="container">
  <?php if ($error): ?>
    <div class="card">
      <div class="alert alert-error"><?= e($error) ?></div>
      <a href="invoice.php">Kembali ke riwayat invoice</a>
    </div>
  <?php elseif ($invoice): ?>
    <div class="card">
      <h2>Invoice Booking</h2>
      <div class="table-wrap">
        <table>
          <tbody>
            <tr><th>Kode Invoice</th><td><?= e($invoice['kode_booking']) ?></td></tr>
            <tr><th>Tanggal Dibuat</th><td><?= e($invoice['created_at']) ?></td></tr>
            <tr><th>Nama</th><td><?= e($invoice['nama_user']) ?></td></tr>
            <tr><th>Email</th><td><?= e($invoice['email']) ?></td></tr>
            <tr><th>Lapangan</th><td><?= e($invoice['nama_lapangan']) ?></td></tr>
            <tr><th>Tanggal Booking</th><td><?= e($invoice['tanggal']) ?></td></tr>
            <tr>
              <th>Waktu</th>
              <td><?= e(substr($invoice['jam_mulai'], 0, 5)) ?>–<?= e(substr($invoice['jam_selesai'], 0, 5)) ?></td>
            </tr>
            <tr>
              <th>Total</th>
              <td><strong>Rp<?= e(number_format((int) $invoice['total_harga'], 0, ',', '.')) ?></strong></td>
            </tr>
            <tr>
              <th>Jumlah Pembayaran</th>
              <td>
                <?= $invoice['jumlah_pembayaran'] !== null
                    ? 'Rp' . e(number_format((int) $invoice['jumlah_pembayaran'], 0, ',', '.'))
                    : '-' ?>
              </td>
            </tr>
            <tr>
              <th>Status Booking</th>
              <td><span class="badge badge-<?= e($invoice['status_booking']) ?>"><?= e(ucfirst($invoice['status_booking'])) ?></span></td>
            </tr>
            <tr>
              <th>Status Pembayaran</th>
              <td>
                <?php if ($invoice['status_pembayaran']): ?>
                  <span class="badge"><?= e(ucfirst($invoice['status_pembayaran'])) ?></span>
                <?php else: ?>
                  Belum ada pembayaran
                <?php endif; ?>
              </td>
            </tr>
            <tr><th>Metode Pembayaran</th><td><?= e($invoice['metode_pembayaran'] ?: '-') ?></td></tr>
            <tr><th>Waktu Pembayaran</th><td><?= e($invoice['paid_at'] ?: '-') ?></td></tr>
          </tbody>
        </table>
      </div>
      <p class="bawah">
        <a href="invoice.php">Riwayat Invoice</a>
        <button type="button" class="btn btn-kecil" onclick="window.print()">Cetak Invoice</button>
      </p>
    </div>
  <?php else: ?>
    <div class="card">
      <h2>Riwayat Invoice</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Kode Booking</th>
              <th>Tanggal Booking</th>
              <th>Total</th>
              <th>Status</th>
              <th>Invoice</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$riwayat): ?>
              <tr><td colspan="5">Belum ada booking.</td></tr>
            <?php else: ?>
              <?php foreach ($riwayat as $item): ?>
                <tr>
                  <td><?= e($item['kode_booking']) ?></td>
                  <td><?= e($item['tanggal']) ?></td>
                  <td>Rp<?= e(number_format((int) $item['total_harga'], 0, ',', '.')) ?></td>
                  <td><span class="badge badge-<?= e($item['status']) ?>"><?= e(ucfirst($item['status'])) ?></span></td>
                  <td><a href="invoice.php?kode_booking=<?= urlencode($item['kode_booking']) ?>">Lihat Invoice</a></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
