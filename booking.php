<?php
require 'includes/init.php';
wajib_login();

$user = $_SESSION['user'];
$error = '';
$sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $court_id = (int) ($_POST['court_id'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';

    // Validasi input dasar
    if (!$court_id || !$tanggal || !$jam_mulai || !$jam_selesai) {
        $error = 'Semua data booking wajib diisi.';
    } elseif ($jam_selesai <= $jam_mulai) {
        $error = 'Jam selesai harus lebih besar dari jam mulai.';
    } elseif ($jam_mulai < '06:00' || $jam_selesai > '22:00') {
        $error = 'Booking hanya tersedia pukul 06:00 sampai 22:00.';
    } elseif ($tanggal < date('Y-m-d')) {
        $error = 'Tanggal booking tidak boleh sebelum hari ini.';
    } else {

        // Cek lapangan
        $q = $pdo->prepare("
            SELECT *
            FROM courts
            WHERE id = ? AND status = 'aktif'
        ");
        $q->execute([$court_id]);
        $court = $q->fetch();

        if (!$court) {
            $error = 'Lapangan tidak ditemukan atau sedang tidak aktif.';
        } else {

            // Cek apakah jadwal bentrok dengan booking lain
            $q = $pdo->prepare("
                SELECT id
                FROM bookings
                WHERE court_id = ?
                  AND tanggal = ?
                  AND status NOT IN ('cancelled', 'expired')
                  AND jam_mulai < ?
                  AND jam_selesai > ?
                LIMIT 1
            ");
            $q->execute([
                $court_id,
                $tanggal,
                $jam_selesai,
                $jam_mulai
            ]);

            if ($q->fetch()) {
                $error = 'Jadwal tersebut sudah dibooking. Silakan pilih jam lain.';
            } else {

                // Hitung harga per jam berdasarkan pricing_rules
                $mulai = new DateTime($jam_mulai);
                $selesai = new DateTime($jam_selesai);

                $total_harga = 0;
                $waktu = clone $mulai;

                while ($waktu < $selesai) {
                    $jam_awal = $waktu->format('H:i:s');

                    $next = clone $waktu;
                    $next->modify('+1 hour');

                    if ($next > $selesai) {
                        $next = clone $selesai;
                    }

                    $jam_akhir = $next->format('H:i:s');

                    $hari = date('N', strtotime($tanggal));
                    $tipe_hari = ($hari >= 6) ? 'weekend' : 'weekday';

                    $q = $pdo->prepare("
                        SELECT harga_per_jam
                        FROM pricing_rules
                        WHERE tipe_hari = ?
                          AND jam_mulai <= ?
                          AND jam_selesai >= ?
                        LIMIT 1
                    ");
                    $q->execute([
                        $tipe_hari,
                        $jam_awal,
                        $jam_akhir
                    ]);

                    $harga = $q->fetch();

                    if (!$harga) {
                        $error = 'Harga untuk jam booking tersebut tidak tersedia.';
                        break;
                    }

                    $total_harga += (int) $harga['harga_per_jam'];

                    $waktu = $next;
                }

                if (!$error) {
                    // Buat kode booking
                    $kode_booking = 'PDL-' . date('YmdHis') . '-' . random_int(100, 999);

                    // Simpan booking
                    $q = $pdo->prepare("
                        INSERT INTO bookings
                        (
                            kode_booking,
                            user_id,
                            court_id,
                            tanggal,
                            jam_mulai,
                            jam_selesai,
                            total_harga,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                    ");

                    $q->execute([
                        $kode_booking,
                        $user['id'],
                        $court_id,
                        $tanggal,
                        $jam_mulai,
                        $jam_selesai,
                        $total_harga
                    ]);

                    $sukses = "Booking berhasil dibuat dengan kode $kode_booking.";
                }
            }
        }
    }
}

// Ambil daftar lapangan aktif
$q = $pdo->query("
    SELECT id, nama, tipe
    FROM courts
    WHERE status = 'aktif'
    ORDER BY id
");
$courts = $q->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Lapangan | Padel Booking</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="auth-wrap">
    <div class="auth-card">

        <div class="logo">🎾</div>

        <h1>Booking Lapangan</h1>

        <p class="sub">
            Halo, <?= e($user['nama']) ?>!
            Silakan pilih jadwal bermain.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($sukses): ?>
            <div class="alert alert-sukses">
                <?= e($sukses) ?>
            </div>
        <?php endif; ?>

        <form method="post">

            <div class="form-group">
                <label>Lapangan</label>

                <select name="court_id" required>
                    <option value="">-- Pilih Lapangan --</option>

                    <?php foreach ($courts as $court): ?>
                        <option value="<?= $court['id'] ?>">
                            <?= e($court['nama']) ?>
                            - <?= e(ucfirst($court['tipe'])) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group">
                <label>Tanggal</label>

                <input
                    type="date"
                    name="tanggal"
                    min="<?= date('Y-m-d') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Jam Mulai</label>

                <select name="jam_mulai" required>
                    <option value="">-- Pilih Jam Mulai --</option>

                    <?php for ($jam = 6; $jam <= 21; $jam++): ?>
                        <option value="<?= sprintf('%02d:00', $jam) ?>">
                            <?= sprintf('%02d:00', $jam) ?>
                        </option>
                    <?php endfor; ?>

                </select>
            </div>

            <div class="form-group">
                <label>Jam Selesai</label>

                <select name="jam_selesai" required>
                    <option value="">-- Pilih Jam Selesai --</option>

                    <?php for ($jam = 7; $jam <= 22; $jam++): ?>
                        <option value="<?= sprintf('%02d:00', $jam) ?>">
                            <?= sprintf('%02d:00', $jam) ?>
                        </option>
                    <?php endfor; ?>

                </select>
            </div>

            <button type="submit" class="btn">
                Booking Sekarang
            </button>

        </form>

        <p class="bawah">
            <a href="logout.php">Logout</a>
        </p>

    </div>
</div>

</body>
</html>