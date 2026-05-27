<?php
// ============================================================
//  login_process.php
//  Mendeteksi otomatis: admin (username) atau member (email)
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password']         ?? '';
$redirect   = trim($_POST['redirect']    ?? '');

if (empty($identifier) || empty($password)) {
    $_SESSION['error']          = 'Email/username dan password wajib diisi.';
    $_SESSION['old_identifier'] = $identifier;
    header('Location: login.php');
    exit;
}

// ── Langkah 1: Cek tabel admin (by username) ──────────────────────────────
$stmt_admin = $conn->prepare(
    'SELECT id, username, password FROM users WHERE username = ? LIMIT 1'
);
$stmt_admin->bind_param('s', $identifier);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();

if ($result_admin->num_rows === 1) {
    $admin = $result_admin->fetch_assoc();

    if (password_verify($password, $admin['password'])) {
        session_regenerate_id(true);

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id']        = $admin['id'];
        $_SESSION['admin_username']  = $admin['username'];
        $_SESSION['last_activity']   = time();

        $stmt_admin->close();
        $conn->close();

        header('Location: admin/dashboard.php');
        exit;
    }
}
$stmt_admin->close();

// ── Langkah 2: Cek tabel member (by email) ────────────────────────────────
if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
    $stmt_member = $conn->prepare(
        'SELECT id, full_name, email, password FROM members WHERE email = ? LIMIT 1'
    );
    $stmt_member->bind_param('s', $identifier);
    $stmt_member->execute();
    $result_member = $stmt_member->get_result();

    if ($result_member->num_rows === 1) {
        $member = $result_member->fetch_assoc();

        if (password_verify($password, $member['password'])) {
            session_regenerate_id(true);

            $_SESSION['member_logged_in'] = true;
            $_SESSION['member_id']        = $member['id'];
            $_SESSION['member_name']      = $member['full_name'];
            $_SESSION['member_email']     = $member['email'];

            $stmt_member->close();
            $conn->close();

            // Kembalikan ke halaman asal jika ada
            $safe_redirect = '';
            if (!empty($redirect) && strpos($redirect, 'register.php') !== false) {
                $safe_redirect = $redirect;
            }

            header('Location: ' . ($safe_redirect ?: 'index.php'));
            exit;
        }
    }
    $stmt_member->close();
}

$conn->close();

// ── Login gagal — pesan generik ───────────────────────────────────────────
$_SESSION['error']          = 'Email/username atau password tidak valid.';
$_SESSION['old_identifier'] = $identifier;
header('Location: login.php');
exit;