<?php
// ============================================================
//  login.php
//  Login terpadu: sistem mendeteksi otomatis apakah
//  credential milik admin (by username) atau member (by email)
//  Tampilan disesuaikan dengan gaya modern & minimalis
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

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">

            <!-- Notifikasi sukses daftar -->
            <?php if (!empty($_SESSION['success_register'])): ?>
            <div class="alert alert-success text-center mb-3 py-2">
                <i class="fas fa-check-circle me-2"></i>
                <span class="small"><?= htmlspecialchars($_SESSION['success_register']); unset($_SESSION['success_register']); ?></span>
            </div>
            <?php endif; ?>

            <!-- Notifikasi sesi habis -->
            <?php if (isset($_GET['expired'])): ?>
            <div class="alert alert-warning text-center mb-3 py-2">
                <i class="fas fa-clock me-2"></i>
                <span class="small">Sesi Anda telah habis. Silakan masuk kembali.</span>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <!-- Header kartu dengan gaya Gradient Baru -->
                <div class="card-header text-white text-center py-4"
                     style="background: linear-gradient(135deg, #1a2e42, #2563eb);">
                    <i class="fas fa-sign-in-alt fa-2x mb-2 d-block"></i>
                    <h5 class="mb-0 fw-bold">Masuk Akun</h5>
                    <small class="opacity-75">BEM Fasilkom Unsika</small>
                </div>

                <div class="card-body p-4">

                    <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger py-2 alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <span class="small"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form action="login_process.php" method="POST" autocomplete="off">

                        <!-- Redirect target setelah login -->
                        <input type="hidden" name="redirect"
                               value="<?= htmlspecialchars($_GET['redirect'] ?? '') ?>">

                        <!-- Email / Username -->
                        <div class="mb-3">
                            <label for="identifier" class="form-label fw-semibold">
                                <i class="fas fa-user me-1 text-primary"></i>Email atau Username
                            </label>
                            <input type="text" class="form-control" id="identifier" name="identifier"
                                   placeholder="Masukkan email atau username" required autofocus
                                   value="<?= htmlspecialchars($_SESSION['old_identifier'] ?? ($_SESSION['old_email'] ?? '')); unset($_SESSION['old_identifier'], $_SESSION['old_email']); ?>">
                            <div class="form-text small" style="font-size: .78rem;">
                                Member gunakan <em>email</em> · Admin gunakan <em>username</em>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">
                                <i class="fas fa-lock me-1 text-primary"></i>Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Masukkan password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            <i class="fas fa-sign-in-alt me-2"></i>Masuk
                        </button>

                    </form>

                    <hr class="my-3">
                    <p class="text-center text-muted small mb-0">
                        Belum punya akun?
                        <a href="member_register.php" class="fw-semibold text-primary text-decoration-none">Daftar di sini</a>
                    </p>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.getElementById('togglePwd').addEventListener('click', function () {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
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