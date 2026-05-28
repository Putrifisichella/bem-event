<?php
// ============================================================
//  includes/header.php  
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

$is_admin  = !empty($_SESSION['admin_logged_in'])  && $_SESSION['admin_logged_in']  === true;
$is_member = !empty($_SESSION['member_logged_in']) && $_SESSION['member_logged_in'] === true;

// Inisial avatar member
$member_initial = '';
if ($is_member && !empty($_SESSION['member_name'])) {
    $words = explode(' ', trim($_SESSION['member_name']));
    $member_initial = mb_strtoupper(mb_substr($words[0], 0, 1));
    if (count($words) > 1) {
        $member_initial .= mb_strtoupper(mb_substr(end($words), 0, 1));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'BEM Fasilkom Unsika — Sistem Pendaftaran Event') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<!-- ================================================================
     NAVBAR
================================================================ -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top"
     style="background:#1a2e42; box-shadow:0 1px 0 rgba(255,255,255,.06);">
    <div class="container">

        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>index.php">
            <img src="<?= BASE_URL ?>assets/img/logo-bem.png"
                 alt="Logo BEM" style="height:28px;">
            <span class="fw-bold" style="font-size:.95rem; letter-spacing:-.01em;">
                BEM Fasilkom Unsika
            </span>
        </a>

        <!-- Hamburger -->
        <button class="navbar-toggler border-0 p-1" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarMenu"
                aria-controls="navbarMenu" aria-expanded="false"
                aria-label="Buka/tutup menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">

                <!-- Beranda (selalu tampil) -->
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded-2"
                       style="font-size:.875rem; color:rgba(255,255,255,.75);"
                       href="<?= BASE_URL ?>index.php">
                       Beranda
                    </a>
                </li>

                <?php if ($is_admin): ?>
                <!-- ── ADMIN: menu manajemen ── -->
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 rounded-2"
                       style="font-size:.875rem; color:rgba(255,255,255,.75);"
                       href="<?= BASE_URL ?>admin/dashboard.php">
                       Dashboard
                    </a>
                </li>

                <!-- Dropdown admin -->
                <li class="nav-item dropdown ms-lg-1">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 px-3 py-2 rounded-2"
                       style="font-size:.875rem; background:rgba(255,255,255,.07);"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <!-- Avatar admin: ikon shield amber -->
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                              style="width:28px;height:28px;background:#f59e0b;font-size:.7rem;font-weight:700;flex-shrink:0;">
                            <i class="fas fa-shield-alt text-white" style="font-size:.65rem;"></i>
                        </span>
                        <span><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-1"
                        style="min-width:200px; border-radius:10px !important; overflow:hidden;">
                        <li>
                            <div class="px-3 py-2" style="background:#f8f9fa; border-bottom:1px solid #eee;">
                                <p class="mb-0 fw-semibold text-dark" style="font-size:.82rem;">
                                    <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>
                                </p>
                                <p class="mb-0 text-muted" style="font-size:.75rem;">Administrator</p>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item py-2 text-danger" style="font-size:.85rem;"
                               href="<?= BASE_URL ?>logout.php">
                                Keluar
                            </a>
                        </li>
                    </ul>
                </li>

                <?php elseif ($is_member): ?>
                <!-- ── MEMBER: dropdown profil ── -->
                <li class="nav-item dropdown ms-lg-1">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 px-3 py-2 rounded-2"
                       style="font-size:.875rem; background:rgba(255,255,255,.07);"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <!-- Avatar inisial -->
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                              style="width:28px;height:28px;background:#2563eb;
                                     font-size:.65rem;font-weight:700;color:#fff;flex-shrink:0;
                                     letter-spacing:.02em;">
                            <?= htmlspecialchars($member_initial) ?>
                        </span>
                        <span class="d-none d-sm-inline">
                            <?= htmlspecialchars(explode(' ', trim($_SESSION['member_name'] ?? 'Akun'))[0]) ?>
                        </span>
                        
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-1"
                        style="min-width:220px; border-radius:10px !important; overflow:hidden;">
                        <!-- Info akun -->
                        <li>
                            <div class="px-3 py-2" style="background:#f8f9fa; border-bottom:1px solid #eee;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                                          style="width:34px;height:34px;background:#dbeafe;
                                                 font-size:.75rem;font-weight:700;color:#1d4ed8;flex-shrink:0;">
                                        <?= htmlspecialchars($member_initial) ?>
                                    </span>
                                    <div class="overflow-hidden">
                                        <p class="mb-0 fw-semibold text-dark text-truncate"
                                           style="font-size:.82rem; max-width:150px;">
                                            <?= htmlspecialchars($_SESSION['member_name'] ?? '') ?>
                                        </p>
                                        <p class="mb-0 text-muted text-truncate"
                                           style="font-size:.73rem; max-width:150px;">
                                            <?= htmlspecialchars($_SESSION['member_email'] ?? '') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item py-2 text-danger" style="font-size:.85rem;"
                               href="<?= BASE_URL ?>logout.php">
                                <i class="fas fa-sign-out-alt me-2" style="width:16px;"></i>
                                Keluar
                            </a>
                        </li>
                    </ul>
                </li>

                <?php else: ?>
                <!-- ── TAMU: tombol login & daftar ── -->
                <li class="nav-item ms-lg-1">
                    <a class="nav-link px-3 py-2 rounded-2"
                       style="font-size:.875rem; color:rgba(255,255,255,.75);
                              border:1px solid rgba(255,255,255,.2);"
                       href="<?= BASE_URL ?>login.php">
                       Masuk
                    </a>
                </li>
                <li class="nav-item ms-lg-1">
                    <a class="btn btn-sm px-3 py-2 fw-semibold"
                       style="background:#fff; color:#1a2e42; font-size:.875rem;
                              border-radius:8px !important; letter-spacing:-.01em;"
                       href="<?= BASE_URL ?>member_register.php">
                       Daftar
                    </a>
                </li>
                <?php endif; ?>

            </ul>
        </div>

    </div>
</nav>

<main class="py-4">