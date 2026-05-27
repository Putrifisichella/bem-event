<?php
// ============================================================
//  member_register_process.php
//  Memproses form pendaftaran akun peserta
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: member_register.php');
    exit;
}

$full_name        = trim($_POST['full_name']        ?? '');
$email            = trim($_POST['email']            ?? '');
$password         = $_POST['password']              ?? '';
$password_confirm = $_POST['password_confirm']      ?? '';

// ── Simpan input lama untuk dikembalikan jika gagal ──
$_SESSION['old_name']  = $full_name;
$_SESSION['old_email'] = $email;

// ── Validasi ──
if (empty($full_name) || empty($email) || empty($password) || empty($password_confirm)) {
    $_SESSION['error'] = 'Semua field wajib diisi.';
    header('Location: member_register.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Format email tidak valid.';
    header('Location: member_register.php');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['error'] = 'Password minimal 8 karakter.';
    header('Location: member_register.php');
    exit;
}

if ($password !== $password_confirm) {
    $_SESSION['error'] = 'Konfirmasi password tidak cocok.';
    header('Location: member_register.php');
    exit;
}

// ── Cek email sudah terdaftar ──
$stmt_check = $conn->prepare('SELECT id FROM members WHERE email = ? LIMIT 1');
$stmt_check->bind_param('s', $email);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    $stmt_check->close();
    $_SESSION['error'] = 'Email ini sudah terdaftar. Silakan gunakan email lain atau langsung login.';
    header('Location: member_register.php');
    exit;
}
$stmt_check->close();

// ── Simpan ke database ──
$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare('INSERT INTO members (full_name, email, password) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $full_name, $email, $hashed);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    // Bersihkan input lama
    unset($_SESSION['old_name'], $_SESSION['old_email']);

    $_SESSION['success_register'] = 'Akun berhasil dibuat! Silakan login untuk melanjutkan.';
    header('Location: member_login.php');
    exit;
} else {
    error_log('member_register_process error: ' . $conn->error);
    $stmt->close();
    $conn->close();

    $_SESSION['error'] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
    header('Location: member_register.php');
    exit;
}