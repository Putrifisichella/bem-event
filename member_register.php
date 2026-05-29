<?php
// ============================================================
//  member_register.php
//  Halaman form pendaftaran akun peserta
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['member_logged_in']) && $_SESSION['member_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$page_title = 'Daftar Akun — BEM Fasilkom Unsika';
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white text-center py-4"
                     style="background:linear-gradient(135deg,#1a2e42,#2563eb);">
                    Daftar Akun
                </div>
                <div class="card-body p-4">

                    <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger py-2 alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form action="member_register_process.php" method="POST" autocomplete="off">

                        <div class="mb-3">
                            <label for="full_name" class="form-label fw-semibold">
                                <i class="fas fa-user me-1 text-primary"></i>Nama Lengkap
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                   placeholder="Masukkan nama lengkap" required
                                   value="<?php echo htmlspecialchars($_SESSION['old_name'] ?? ''); unset($_SESSION['old_name']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">
                                <i class="fas fa-envelope me-1 text-primary"></i>Email
                                <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="contoh@email.com" required
                                   value="<?php echo htmlspecialchars($_SESSION['old_email'] ?? ''); unset($_SESSION['old_email']); ?>">
                            <div class="form-text">Email ini digunakan untuk login dan konfirmasi event.</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">
                                <i class="fas fa-lock me-1 text-primary"></i>Password
                                <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control" id="password" name="password"
                                     placeholder="Minimal 8 karakter" required minlength="8">
                        </div>

                        <div class="mb-4">
                            <label for="password_confirm" class="form-label fw-semibold">
                                <i class="fas fa-lock me-1 text-primary"></i>Konfirmasi Password
                                <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control" id="password_confirm"
                                   name="password_confirm" placeholder="Ulangi password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            Daftar Sekarang
                        </button>

                    </form>

                    <hr class="my-3">
                    <p class="text-center text-muted small mb-0">
                        Sudah punya akun?
                        <a href="login.php" class="fw-semibold text-primary text-decoration-none">Masuk di sini</a>
                    </p>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>