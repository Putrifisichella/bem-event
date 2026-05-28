<?php
// ============================================================
//  register.php  (DIMODIFIKASI)
//  Peserta wajib login sebelum mendaftar event.
//  Email diambil otomatis dari sesi, tidak perlu diisi ulang.
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

$page_title = 'Form Pendaftaran Event — BEM Fasilkom Unsika';
include 'includes/header.php';
require_once 'config/database.php';

// ── Validasi event_id ──
if (empty($_GET['event_id'])) {
    header('Location: index.php');
    exit;
}
$event_id = intval($_GET['event_id']);

// ── Ambil data event ──
$stmt = $conn->prepare(
    "SELECT * FROM events
      WHERE id = ?
        AND is_active = 1
        AND registration_open  <= CURDATE()
        AND registration_close >= CURDATE()"
);
$stmt->bind_param('i', $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Event tidak ditemukan atau masa pendaftaran sudah berakhir.';
    header('Location: index.php');
    exit;
}
$event = $result->fetch_assoc();
$stmt->close();

// ── Cek kuota ──
$stmt_quota = $conn->prepare('SELECT COUNT(*) AS total FROM registrations WHERE event_id = ?');
$stmt_quota->bind_param('i', $event_id);
$stmt_quota->execute();
$registered = (int) $stmt_quota->get_result()->fetch_assoc()['total'];
$remaining  = $event['quota'] - $registered;
$stmt_quota->close();
$conn->close();

if ($remaining <= 0) {
    $_SESSION['error'] = 'Maaf, kuota pendaftaran untuk event ini sudah penuh.';
    header('Location: index.php');
    exit;
}

// ── Cek apakah peserta sudah login ──
$is_logged_in   = !empty($_SESSION['member_logged_in']) && $_SESSION['member_logged_in'] === true;
$member_email   = $_SESSION['member_email'] ?? '';
$member_name    = $_SESSION['member_name']  ?? '';

// URL untuk redirect balik setelah login
$current_url = 'register.php?event_id=' . $event_id;

$csrf_token = generateCsrfToken();
?>

<div class="container py-2">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">

            <div class="card shadow-sm border-0">
                <div class="card-header py-3 text-white text-center"
                     style="background:linear-gradient(135deg,#1a2e42,#2563eb);">
                    <h4 class="mb-0" style="color: white;">
                        Form Pendaftaran Event
                    </h4>
                </div>

                <div class="card-body p-4">

                    <!-- Info event -->
                    <div class="rounded p-3 mb-4"
                         style="background:#f0f6fc; border:1px solid #c3d9f0;">
                        <h5 class="text-primary fw-bold mb-3">
                            <?= htmlspecialchars($event['name']) ?>
                        </h5>
                        <div class="row g-2 small">
                            <div class="col-sm-6">
                                <strong>Kategori:</strong> <?= htmlspecialchars($event['category']) ?>
                            </div>
                            <div class="col-sm-6">
                                <strong>Tipe:</strong> <?= ucfirst($event['event_type']) ?>
                            </div>
                            <?php if (!empty($event['event_date'])): ?>
                            <div class="col-sm-6">
                                <strong>Tanggal:</strong>
                                <?= date('d M Y', strtotime($event['event_date'])) ?>
                            </div>
                            <?php endif; ?>
                            <div class="col-sm-6">
                                <strong>Sisa kuota:</strong>
                                <span class="fw-bold <?= $remaining <= 10 ? 'text-danger' : 'text-success' ?>">
                                    <?= $remaining ?> tempat
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($event['documentation'])): ?>
                    <div class="mb-4 text-center">
                        <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($event['documentation']) ?>"
                             class="img-fluid rounded shadow-sm"
                             alt="Poster <?= htmlspecialchars($event['name']) ?>"
                             style="max-height:260px; object-fit:cover;">
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (!$is_logged_in): ?>
                    <!-- ── Belum login: tampilkan peringatan ── -->
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-lock fa-2x d-block mb-2"></i>
                        <strong>Kamu belum login!</strong><br>
                        Silakan login atau daftar akun terlebih dahulu untuk mendaftar event ini.
                    </div>
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="login.php?redirect=<?= urlencode($current_url) ?>"
                           class="btn btn-primary px-4">
                            Masuk
                        </a>
                        <a href="member_register.php" class="btn btn-outline-primary px-4">
                            Daftar Akun
                        </a>
                    </div>

                    <?php else: ?>
                    <!-- ── Sudah login: tampilkan form ── -->

                    <!-- Info akun yang sedang login -->
                    <div class="alert alert-info py-2 d-flex align-items-center gap-2 mb-4">
                        <div class="small">
                            Mendaftar sebagai <strong><?= htmlspecialchars($member_name) ?>.</strong>
                            Bukan kamu? <a href="member_logout.php" class="fw-semibold">Ganti akun</a>
                        </div>
                    </div>

                    <form id="registerForm"
                          action="register_process.php"
                          method="POST"
                          data-event-type="<?= htmlspecialchars($event['event_type']) ?>">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <input type="hidden" name="event_id"   value="<?= $event_id ?>">

                        <!-- Nama Lengkap (pre-fill dari akun) -->
                        <div class="mb-3">
                            <label for="full_name" class="form-label fw-semibold">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                   value="<?= htmlspecialchars($member_name) ?>"
                                   placeholder="Nama lengkap" required>
                        </div>

                        <!-- Email (tersembunyi, diambil dari sesi) -->
                        <input type="hidden" name="email" value="<?= htmlspecialchars($member_email) ?>">

                        <!-- Field khusus event UMUM -->
                        <div class="umum-field"
                             <?= ($event['event_type'] !== 'umum') ? 'style="display:none;"' : '' ?>>
                            <div class="mb-3">
                                <label for="institution" class="form-label fw-semibold">
                                    Instansi / Asal <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="institution" name="institution"
                                       placeholder="Nama universitas, perusahaan, atau institusi">
                            </div>
                        </div>

                        <!-- Field khusus event INTERNAL -->
                        <div class="internal-field"
                             <?= ($event['event_type'] !== 'internal') ? 'style="display:none;"' : '' ?>>
                            <div class="mb-3">
                                <label for="npm" class="form-label fw-semibold">
                                    NPM <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="npm" name="npm"
                                       placeholder="Contoh: 2110631170001"
                                       maxlength="13" pattern="[0-9]{13}">
                                <div class="form-text">NPM terdiri dari 13 digit angka.</div>
                            </div>
                            <div class="mb-3">
                                <label for="faculty" class="form-label fw-semibold">
                                    Fakultas <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="faculty" name="faculty"
                                       placeholder="Contoh: Fakultas Ilmu Komputer">
                            </div>
                        </div>

                        <!-- Nomor Telepon -->
                        <div class="mb-4">
                            <label for="phone" class="form-label fw-semibold">
                                Nomor Telepon <span class="text-danger">*</span>
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   placeholder="Contoh: 08123456789"
                                   maxlength="13" pattern="[0-9]{10,13}">
                            <div class="form-text">Konfirmasi akan dikirim ke
                                <strong><?= htmlspecialchars($member_email) ?></strong>.
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <a href="index.php" class="btn btn-outline-secondary w-50">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary w-50">
                                Daftar Sekarang
                            </button>
                        </div>

                    </form>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>