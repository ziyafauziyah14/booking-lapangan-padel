<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require __DIR__ . '/../config.php';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user, $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('Koneksi database gagal. Cek config.php');
}

function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function halaman_role($role) {
    return $role === 'admin' ? 'admin.php' : 'booking.php';
}

function wajib_login() {
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

// Pakai di atas tiap halaman: wajib_role('user') atau wajib_role('admin')
function wajib_role($role) {
    wajib_login();
    if ($_SESSION['user']['role'] !== $role) {
        header('Location: ' . halaman_role($_SESSION['user']['role']));
        exit;
    }
}