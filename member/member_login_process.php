<?php
// ============================================================
//  member_login_process.php
//  Memproses login peserta
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: member_login.php');
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';
$redirect = trim($_POST['redirect'] ?? '');

if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Email dan password wajib diisi.';
    header('Location: member_login.php');
    exit;
}

$stmt = $conn->prepare('SELECT id, full_name, email, password FROM members WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $member = $result->fetch_assoc();

    if (password_verify($password, $member['password'])) {
        // Login berhasil
        session_regenerate_id(true);

        $_SESSION['member_id']        = $member['id'];
        $_SESSION['member_name']      = $member['full_name'];
        $_SESSION['member_email']     = $member['email'];
        $_SESSION['member_logged_in'] = true;

        $stmt->close();
        $conn->close();

        // Arahkan ke halaman asal jika ada, atau ke beranda
        $safe_redirect = '';
        if (!empty($redirect) && strpos($redirect, 'register.php') !== false) {
            $safe_redirect = $redirect;
        }

        header('Location: ' . ($safe_redirect ?: 'index.php'));
        exit;
    }
}

// Login gagal — pesan generik agar tidak bocor info
$_SESSION['error']      = 'Email atau password tidak valid.';
$_SESSION['old_email']  = $email;
$stmt->close();
$conn->close();

header('Location: member_login.php');
exit;