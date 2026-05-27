<?php
// ============================================================
//  login.php
//  Login terpadu: sistem mendeteksi otomatis apakah
//  credential milik admin (by username) atau member (by email)
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

// Sudah login sebagai admin → dashboard
if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin/dashboard.php');
    exit;
}
// Sudah login sebagai member → beranda
if (!empty($_SESSION['member_logged_in']) && $_SESSION['member_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$page_title = 'Masuk — BEM Fasilkom Unsika';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">

            <!-- Notifikasi sukses daftar -->
            <?php if (!empty($_SESSION['success_register'])): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-3 py-2">
                <i class="fas fa-check-circle"></i>
                <span class="small"><?= htmlspecialchars($_SESSION['success_register']); unset($_SESSION['success_register']); ?></span>
            </div>
            <?php endif; ?>

            <!-- Notifikasi sesi habis -->
            <?php if (isset($_GET['expired'])): ?>
            <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 py-2">
                <i class="fas fa-clock"></i>
                <span class="small">Sesi Anda telah habis. Silakan masuk kembali.</span>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">

                <!-- Header kartu -->
                <div class="card-header text-white text-center py-4 border-0"
                     style="background:#1a2e42; border-radius:12px 12px 0 0 !important;">
                    <div class="mx-auto mb-2 d-flex align-items-center justify-content-center"
                         style="width:48px;height:48px;background:rgba(255,255,255,.1);border-radius:50%;">
                        <i class="fas fa-user" style="font-size:1.2rem;"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">Masuk ke Akun</h5>
                    <p class="mb-0 small opacity-75 mt-1">BEM Fasilkom Unsika</p>
                </div>

                <div class="card-body p-4">

                    <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger py-2 d-flex align-items-center gap-2 alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle flex-shrink-0"></i>
                        <span class="small"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                        <button type="button" class="btn-close btn-sm ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form action="login_process.php" method="POST" autocomplete="off">

                        <!-- Hidden redirect target -->
                        <input type="hidden" name="redirect"
                               value="<?= htmlspecialchars($_GET['redirect'] ?? '') ?>">

                        <!-- Email / Username -->
                        <div class="mb-3">
                            <label for="identifier" class="form-label fw-semibold" style="font-size:.85rem;">
                                Email atau Username
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-user text-muted" style="font-size:.85rem;"></i>
                                </span>
                                <input type="text"
                                       class="form-control border-start-0 ps-0"
                                       id="identifier" name="identifier"
                                       placeholder="Masukkan email atau username"
                                       required autofocus
                                       value="<?= htmlspecialchars($_SESSION['old_identifier'] ?? ''); unset($_SESSION['old_identifier']); ?>">
                            </div>
                            <div class="form-text" style="font-size:.78rem;">
                                Member gunakan <em>email</em> · Admin gunakan <em>username</em>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold" style="font-size:.85rem;">
                                Password
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-lock text-muted" style="font-size:.85rem;"></i>
                                </span>
                                <input type="password"
                                       class="form-control border-start-0 ps-0"
                                       id="loginPassword" name="password"
                                       placeholder="Masukkan password" required>
                                <button class="btn btn-outline-secondary border-start-0"
                                        type="button" id="toggleLoginPwd" title="Tampilkan password">
                                    <i class="fas fa-eye" id="loginEyeIcon" style="font-size:.85rem;"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="fas fa-sign-in-alt me-2"></i>Masuk
                        </button>

                    </form>

                    <hr class="my-3">

                    <p class="text-center text-muted mb-0" style="font-size:.85rem;">
                        Belum punya akun?
                        <a href="member_register.php" class="fw-semibold text-primary text-decoration-none">
                            Daftar di sini
                        </a>
                    </p>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.getElementById('toggleLoginPwd').addEventListener('click', function () {
    const pwd  = document.getElementById('loginPassword');
    const icon = document.getElementById('loginEyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pwd.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
});
</script>

<?php include 'includes/footer.php'; ?>