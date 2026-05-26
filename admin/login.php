<?php
// ============================================================
//  admin/login.php
//  Halaman form login untuk admin panel
// ============================================================

// Mulai sesi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, langsung redirect ke dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Login Admin — BEM Fasilkom Unsika';
include '../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">

            <!-- Notifikasi sesi habis -->
            <?php if (isset($_GET['expired'])): ?>
            <div class="alert alert-warning text-center mb-3">
                <i class="fas fa-clock me-2"></i>
                Sesi Anda telah habis. Silakan login kembali.
            </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mt-4">

                <!-- Header kartu login -->
                <div class="card-header text-white text-center py-4"
                     style="background:linear-gradient(135deg,#1a2e42,#2563eb);">
                    <i class="fas fa-shield-alt fa-2x mb-2 d-block"></i>
                    <h5 class="mb-0 fw-bold">Login Admin</h5>
                    <small class="opacity-75">BEM Fasilkom Unsika</small>
                </div>

                <div class="card-body p-4">

                    <!-- Tampilkan pesan error jika ada -->
                    <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger py-2 alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Form login -->
                    <form action="login_process.php" method="POST" autocomplete="off">

                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold">
                                <i class="fas fa-user me-1 text-primary"></i>Username
                            </label>
                            <input type="text" class="form-control" id="username" name="username"
                                   placeholder="Masukkan username"
                                   required autofocus autocomplete="username">
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">
                                <i class="fas fa-lock me-1 text-primary"></i>Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Masukkan password"
                                       required autocomplete="current-password">
                                <!-- Tombol lihat/sembunyikan password -->
                                <button class="btn btn-outline-secondary" type="button"
                                        id="togglePassword" title="Tampilkan/sembunyikan password">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Tombol masuk -->
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            <i class="fas fa-sign-in-alt me-2"></i>Masuk
                        </button>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- Script toggle visibilitas password -->
<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    var pwd  = document.getElementById('password');
    var icon = document.getElementById('eyeIcon');

    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pwd.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
});
</script>

<?php include '../includes/footer.php'; ?>