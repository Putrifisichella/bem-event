<?php
// ============================================================
//  admin/logout.php
//  Menghapus sesi admin dan redirect ke halaman login
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hapus semua variabel sesi
session_unset();

// Hancurkan sesi sepenuhnya
session_destroy();

// Redirect ke halaman login
header('Location: login.php');
exit;