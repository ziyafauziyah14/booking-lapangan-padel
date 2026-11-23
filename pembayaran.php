<?php
require 'includes/init.php';
wajib_role('user');

$user = $_SESSION['user'];
$error = '';
$sukses = '';
$metode_pembayaran = [
    'transfer_bank' => 'Transfer Bank',
    'e_wallet' => 'E-Wallet',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_input = $_POST['booking_id'] ?? '';
    $booking_id = is_scalar($booking_input) ? (int) $booking_input : 0;
    $metode = $_POST['metode'] ?? '';

    if ($booking_id < 1 || !is_string($metode) || !isset($metode_pembayaran[$metode])) {
        $error = 'Booking dan metode pembayaran wajib dipilih.';
    } else {
        $pdo->beginTransaction();

        $q = $pdo->prepare("
            SELECT id, total_harga
            FROM bookings
            WHERE id = ? AND user_id = ? AND status = 'pending'
            FOR UPDATE
        ");
        $q->execute([$booking_id, $user['id']]);
        $booking = $q->fetch();

        if (!$booking) {
            $pdo->rollBack();
            $error = 'Booking tidak ditemukan atau tidak dapat dibayar.';
        } else {
            $q = $pdo->prepare("
                SELECT id
                FROM payments
                WHERE booking_id = ? AND status IN ('pending', 'success')
                LIMIT 1
            ");
            $q->execute([$booking_id]);

            if ($q->fetch()) {
                $pdo->rollBack();
                $error = 'Pembayaran untuk booking ini sudah dikirim atau berhasil.';
            } else {
                $q = $pdo->prepare("
                    INSERT INTO payments (booking_id, jumlah, metode, status)
                    VALUES (?, ?, ?, 'pending')
                ");
                $q->execute([
                    $booking['id'],
                    $booking['total_harga'],
                    $metode_pembayaran[$metode],
                ]);
                $pdo->commit();
                $sukses = 'Permintaan pembayaran berhasil dikirim dan menunggu verifikasi admin.';
            }
        }
    }
}

$q = $pdo->prepare("
    SELECT
        id,
        kode_booking,
        tanggal,
        jam_mulai,
        jam_selesai,
        total_harga
    FROM bookings
    WHERE user_id = ?
      AND status = 'pending'
      AND NOT EXISTS (
          SELECT 1
          FROM payments
          WHERE payments.booking_id = bookings.id
            AND payments.status IN ('pending', 'success')
      )
    ORDER BY created_at DESC
");
$q->execute([$user['id']]);
$booking_belum_dibayar = $q->fetchAll();

$q = $pdo->prepare("
    SELECT
        bookings.kode_booking,
        bookings.tanggal,
        bookings.total_harga,
        bookings.status AS status_booking,
        (
            SELECT payments.status
            FROM payments
            WHERE payments.booking_id = bookings.id
            ORDER BY payments.id DESC
            LIMIT 1
        ) AS status_pembayaran,
        (
            SELECT payments.metode
            FROM payments
            WHERE payments.booking_id = bookings.id
            ORDER BY payments.id DESC
            LIMIT 1
        ) AS metode_pembayaran
    FROM bookings
    WHERE bookings.user_id = ?
    ORDER BY bookings.created_at DESC
");
$q->execute([$user['id']]);
$riwayat = $q->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pembayaran | Padel Booking</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="navbar">
  <span class="brand">🎾 Padel Booking | Pembayaran</span>
  <span>Halo, <?= e($user['nama']) ?> <a href="booking.php">Booking</a> <a href="logout.php">Logout</a></span>
</div>

<div class="container">
  <div class="card">
    <h2>Pembayaran Booking</h2>
    <p>Pilih booking yang belum dibayar dan metode pembayaran. Pembayaran akan berstatus menunggu verifikasi admin.</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($sukses): ?>
      <div class="alert alert-sukses"><?= e($sukses) ?></div>
    <?php endif; ?>

    <?php if ($booking_belum_dibayar): ?>
      <form method="post">
        <div class="form-group">
          <label for="booking_id">Booking</label>
          <select id="booking_id" name="booking_id" required>
            <option value="">-- Pilih Booking --</option>
            <?php foreach ($booking_belum_dibayar as $booking): ?>
              <option value="<?= e($booking['id']) ?>">
                <?= e($booking['kode_booking']) ?> -
                <?= e($booking['tanggal']) ?> -
                Rp<?= e(number_format((int) $booking['total_harga'], 0, ',', '.')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="metode">Metode Pembayaran</label>
          <select id="metode" name="metode" required>
            <option value="">-- Pilih Metode --</option>
            <?php foreach ($metode_pembayaran as $nilai => $label): ?>
              <option value="<?= e($nilai) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit" class="btn">Kirim Pembayaran</button>
      </form>
    <?php else: ?>
      <p>Tidak ada booking yang menunggu pembayaran.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Riwayat Booking dan Pembayaran</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Kode Booking</th>
            <th>Tanggal</th>
            <th>Total</th>
            <th>Status Booking</th>
            <th>Status Pembayaran</th>
            <th>Metode</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$riwayat): ?>
            <tr><td colspan="6">Belum ada riwayat booking.</td></tr>
          <?php else: ?>
            <?php foreach ($riwayat as $item): ?>
              <tr>
                <td><?= e($item['kode_booking']) ?></td>
                <td><?= e($item['tanggal']) ?></td>
                <td>Rp<?= e(number_format((int) $item['total_harga'], 0, ',', '.')) ?></td>
                <td><span class="badge badge-<?= e($item['status_booking']) ?>"><?= e(ucfirst($item['status_booking'])) ?></span></td>
                <td>
                  <?php if ($item['status_pembayaran']): ?>
                    <span class="badge"><?= e(ucfirst($item['status_pembayaran'])) ?></span>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                <td><?= e($item['metode_pembayaran'] ?: '-') ?></td>
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
