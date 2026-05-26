<?php
// ============================================================
//  includes/header.php
//  Template bagian atas (head + navbar) yang di-include
//  di semua halaman publik maupun admin.
//  Variabel $page_title diset sebelum include file ini.
// ============================================================

// Mulai sesi jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan BASE_URL sudah didefinisikan
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'BEM Fasilkom Unsika — Sistem Pendaftaran Event') ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome (ikon) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- DataTables (tabel admin) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <!-- SweetAlert2 (popup notifikasi) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS Kustom Project -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<!-- ================================================================
     NAVBAR (Navigation Bar)
     Menampilkan menu navigasi di atas setiap halaman
================================================================ -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top"
     style="background:#1a2e42; box-shadow:0 2px 10px rgba(0,0,0,.15);">
    <div class="container">

        <!-- Logo dan nama organisasi -->
        <a class="navbar-brand d-flex align-items-center gap-2"
           href="<?= BASE_URL ?>index.php">
            <img src="<?= BASE_URL ?>assets/img/logo-bem.png"
                 alt="Logo BEM" style="height:30px;">
            <span class="fw-bold">BEM Fasilkom Unsika</span>
        </a>

        <!-- Tombol hamburger untuk tampilan mobile -->
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarMenu"
                aria-controls="navbarMenu" aria-expanded="false"
                aria-label="Buka/tutup menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu navigasi -->
        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center">

                <!-- Link Beranda -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>index.php">
                        <i class="fas fa-home me-1"></i>Beranda
                    </a>
                </li>

                <!-- Tampilkan menu admin jika sudah login -->
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/events.php">
                            <i class="fas fa-calendar-alt me-1"></i>Kelola Event
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-warning" href="<?= BASE_URL ?>admin/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>
                            Logout (<?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>)
                        </a>
                    </li>
                <?php else: ?>
                    <!-- Link Login Admin jika belum login -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>admin/login.php">
                            <i class="fas fa-lock me-1"></i>Admin
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

    </div>
</nav>

<!-- Konten utama halaman dimulai di sini -->
<main class="py-4">