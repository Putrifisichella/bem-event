<?php
// ============================================================
//  register.php
//  Halaman form pendaftaran peserta untuk satu event tertentu.
//  Menampilkan informasi event dan form isian peserta.
// ============================================================

$page_title = 'Form Pendaftaran Event — BEM Fasilkom Unsika';
include 'includes/header.php';
require_once 'config/database.php';

// Validasi parameter event_id dari URL
if (empty($_GET['event_id'])) {
    header('Location: index.php');
    exit;
}

$event_id = intval($_GET['event_id']);

// --- Ambil data event: hanya yang aktif dan masih dalam masa pendaftaran ---
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

// --- Cek sisa kuota ---
$stmt_quota = $conn->prepare('SELECT COUNT(*) AS total FROM registrations WHERE event_id = ?');
$stmt_quota->bind_param('i', $event_id);
$stmt_quota->execute();
$registered = (int) $stmt_quota->get_result()->fetch_assoc()['total'];
$remaining  = $event['quota'] - $registered;
$stmt_quota->close();
$conn->close();

// Redirect jika kuota sudah penuh
if ($remaining <= 0) {
    $_SESSION['error'] = 'Maaf, kuota pendaftaran untuk event ini sudah penuh.';
    header('Location: index.php');
    exit;
}

// Buat token CSRF untuk keamanan form
$csrf_token = generateCsrfToken();
?>

<div class="container py-2">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">

            <div class="card shadow-sm border-0">

                <!-- Header kartu -->
                <div class="card-header py-3 text-white text-center"
                     style="background:linear-gradient(135deg,#1a2e42,#2563eb);">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Form Pendaftaran Event
                    </h4>
                </div>

                <div class="card-body p-4">

                    <!-- Info ringkasan event -->
                    <div class="rounded p-3 mb-4"
                         style="background:#f0f6fc; border:1px solid #c3d9f0;">
                        <h5 class="text-primary fw-bold mb-3">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <?= htmlspecialchars($event['name']) ?>
                        </h5>
                        <div class="row g-2 small">
                            <div class="col-sm-6">
                                <i class="fas fa-tag me-1 text-primary"></i>
                                <strong>Kategori:</strong> <?= htmlspecialchars($event['category']) ?>
                            </div>
                            <div class="col-sm-6">
                                <i class="fas fa-users me-1 text-primary"></i>
                                <strong>Tipe:</strong> <?= ucfirst($event['event_type']) ?>
                            </div>
                            <?php if (!empty($event['event_date'])): ?>
                            <div class="col-sm-6">
                                <i class="fas fa-calendar-day me-1 text-primary"></i>
                                <strong>Tanggal:</strong>
                                <?= date('d M Y', strtotime($event['event_date'])) ?>
                            </div>
                            <?php endif; ?>
                            <div class="col-sm-6">
                                <i class="fas fa-chair me-1 text-primary"></i>
                                <strong>Sisa kuota:</strong>
                                <span class="fw-bold <?= $remaining <= 10 ? 'text-danger' : 'text-success' ?>">
                                    <?= $remaining ?> tempat
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Gambar event jika ada -->
                    <?php if (!empty($event['documentation'])): ?>
                    <div class="mb-4 text-center">
                        <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($event['documentation']) ?>"
                             class="img-fluid rounded shadow-sm"
                             alt="Poster <?= htmlspecialchars($event['name']) ?>"
                             style="max-height:260px; object-fit:cover;">
                    </div>
                    <?php endif; ?>

                    <!-- Flash message error (fallback untuk non-AJAX) -->
                    <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- ================================================================
                         FORM PENDAFTARAN
                         - data-event-type  : dibaca JS untuk validasi field dinamis
                         - action           : dikirim ke register_process.php
                    ================================================================ -->
                    <form id="registerForm"
                          action="register_process.php"
                          method="POST"
                          data-event-type="<?= htmlspecialchars($event['event_type']) ?>">

                        <!-- Token keamanan CSRF -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <!-- ID event yang didaftarkan -->
                        <input type="hidden" name="event_id" value="<?= $event_id ?>">

                        <!-- Nama Lengkap -->
                        <div class="mb-3">
                            <label for="full_name" class="form-label fw-semibold">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                   placeholder="Masukkan nama lengkap Anda"
                                   required autocomplete="name">
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="contoh@email.com"
                                   required autocomplete="email">
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Email konfirmasi akan dikirim ke alamat ini.
                            </div>
                        </div>

                        <!-- Field khusus event UMUM: Instansi -->
                        <div class="umum-field"
                             <?= ($event['event_type'] !== 'umum') ? 'style="display:none;"' : '' ?>>
                            <div class="mb-3">
                                <label for="institution" class="form-label fw-semibold">
                                    Instansi / Asal <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="institution" name="institution"
                                       placeholder="Nama universitas, perusahaan, atau institusi Anda">
                            </div>
                        </div>

                        <!-- Field khusus event INTERNAL: NPM & Fakultas -->
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
                                   maxlength="13" pattern="[0-9]{10,13}"
                                   autocomplete="tel">
                            <div class="form-text">Masukkan 10–13 digit angka tanpa spasi atau tanda baca.</div>
                        </div>

                        <!-- Tombol aksi -->
                        <div class="d-flex gap-3">
                            <a href="index.php" class="btn btn-outline-secondary w-50">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary w-50">
                                <i class="fas fa-paper-plane me-2"></i>Daftar Sekarang
                            </button>
                        </div>

                    </form>
                </div><!-- /card-body -->
            </div><!-- /card -->

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>