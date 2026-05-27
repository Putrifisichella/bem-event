<?php
// ============================================================
//  admin/includes/auth.php
//  Guard autentikasi untuk semua halaman admin.
//  Di-include di baris paling atas setiap file admin.
//
//  Fungsi:
//  1. Memulai sesi jika belum aktif
//  2. Memeriksa apakah admin sudah login
//  3. Logout otomatis jika sesi tidak aktif > 30 menit
//  4. Redirect ke halaman login jika belum terautentikasi
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Timeout otomatis setelah 30 menit tidak aktif ---
$session_timeout = 30 * 60; // 1800 detik = 30 menit

if (isset($_SESSION['last_activity'])) {
    $idle_time = time() - $_SESSION['last_activity'];

    if ($idle_time > $session_timeout) {
        // Hapus semua data sesi dan redirect ke login
        session_unset();
        session_destroy();
        header('Location: ../login.php?expired=1');
        exit;
    }
}

// Perbarui waktu aktivitas terakhir
$_SESSION['last_activity'] = time();

// --- Cek apakah admin sudah login ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}