<?php
//  admin/includes/auth.php  —  Auth Guard untuk halaman admin
//  Disertakan di bagian paling atas setiap halaman admin.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout: 30 menit tidak aktif
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: login.php?expired=1');
    exit;
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}