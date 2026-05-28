<?php
// ============================================================
//  member_register_process.php
//  Memproses form pendaftaran akun peserta
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: member_register.php');
    exit;
}

require_once 'config/database.php';

$full_name        = trim($_POST['full_name']        ?? '');
$email            = trim($_POST['email']            ?? '');
$password         = $_POST['password']              ?? '';
$password_confirm = $_POST['password_confirm']      ?? '';

$_SESSION['old_name']  = $full_name;
$_SESSION['old_email'] = $email;

function failBack(string $msg): void {
    $_SESSION['error'] = $msg;
    header('Location: member_register.php');
    exit;
}

if (empty($full_name) || empty($email) || empty($password) || empty($password_confirm)) {
    failBack('Semua field wajib diisi.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    failBack('Format email tidak valid.');
}

if (strlen($password) < 8) {
    failBack('Password minimal 8 karakter.');
}

if ($password !== $password_confirm) {
    failBack('Konfirmasi password tidak cocok.');
}

$stmt_check = $conn->prepare('SELECT id FROM members WHERE email = ? LIMIT 1');
$stmt_check->bind_param('s', $email);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    $stmt_check->close();
    failBack('Email ini sudah terdaftar. Silakan login.');
}
$stmt_check->close();

$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare('INSERT INTO members (full_name, email, password) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $full_name, $email, $hashed);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    unset($_SESSION['old_name'], $_SESSION['old_email']);
    $_SESSION['success_register'] = 'Akun berhasil dibuat! Silakan login.';
    header('Location: login.php');
    exit;
} else {
    error_log('Register error: ' . $conn->error);
    $stmt->close();
    $conn->close();
    failBack('Terjadi kesalahan sistem. Coba lagi.');
}