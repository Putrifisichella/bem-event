<?php
// ============================================================
//  admin/event_edit.php
//  Form edit event yang sudah ada
//  PENTING: csrf_token sudah disertakan di dalam form
// ============================================================

include 'includes/auth.php';
require_once '../config/database.php';

// Ambil ID event dari URL
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: events.php');
    exit;
}

// Ambil data event dari database
$stmt = $conn->prepare('SELECT * FROM events WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

// Jika event tidak ditemukan, kembali ke daftar
if (!$event) {
    $_SESSION['error'] = 'Event tidak ditemukan.';
    header('Location: events.php');
    exit;
}

$page_title = 'Edit Event — ' . htmlspecialchars($event['name']);
$csrf       = generateCsrfToken();

// Daftar kategori
$categories = ['Seminar', 'Workshop', 'Lomba', 'Sosial', 'Pelatihan', 'Lainnya'];

include '../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Breadcrumb navigasi -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="events.php">Manajemen Event</a></li>
                    <li class="breadcrumb-item active">Edit Event</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm">
                <div class="card-header text-white text-center py-3"
                     style="background:linear-gradient(135deg,#92400e,#f59e0b);">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Edit Event
                    </h5>
                </div>

                <div class="card-body p-4">
                    <!--
                        id="eventEditForm" → dipakai script.js untuk submit AJAX
                        action="event_update.php" → file yang memproses update
                    -->
                    <form id="eventEditForm"
                          action="event_update.php"
                          method="POST"
                          enctype="multipart/form-data">

                        <!-- Token keamanan CSRF (WAJIB ada agar update tidak ditolak) -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <!-- ID event yang akan diupdate -->
                        <input type="hidden" name="id" value="<?= $event['id'] ?>">

                        <!-- Nama Event -->
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">
                                <i class="fas fa-tag me-1 text-primary"></i>
                                Nama Event <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= htmlspecialchars($event['name']) ?>"
                                   required maxlength="200">
                        </div>

                        <!-- Deskripsi -->
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">
                                <i class="fas fa-align-left me-1 text-primary"></i>Deskripsi
                            </label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="4"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                        </div>

                        <!-- Tanggal Penyelenggaraan -->
                        <div class="mb-3">
                            <label for="event_date" class="form-label fw-semibold">
                                <i class="fas fa-calendar-day me-1 text-primary"></i>
                                Tanggal Penyelenggaraan <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="event_date" name="event_date"
                                   value="<?= htmlspecialchars($event['event_date'] ?? '') ?>" required>
                        </div>

                        <!-- Gambar / Poster Saat Ini -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-image me-1 text-primary"></i>Gambar Saat Ini
                            </label>
                            <?php if (!empty($event['documentation'])): ?>
                                <div>
                                    <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($event['documentation']) ?>"
                                         class="img-thumbnail mb-2"
                                         style="max-width:200px; max-height:130px; object-fit:cover;"
                                         alt="Gambar event saat ini">
                                </div>
                                <!-- Opsi hapus gambar lama -->
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           id="delete_documentation" name="delete_documentation" value="1">
                                    <label class="form-check-label text-danger small" for="delete_documentation">
                                        <i class="fas fa-trash me-1"></i>Hapus gambar ini
                                    </label>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small">Belum ada gambar untuk event ini.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Upload Gambar Baru -->
                        <div class="mb-3">
                            <label for="documentation" class="form-label fw-semibold">
                                <i class="fas fa-upload me-1 text-primary"></i>
                                Ganti Gambar <span class="text-muted fw-normal">(opsional)</span>
                            </label>
                            <input type="file" class="form-control" id="documentation"
                                   name="documentation" accept="image/*">
                            <div class="form-text">Kosongkan jika tidak ingin mengganti gambar. Maks 2MB.</div>
                            <div id="imgPreview" class="mt-2"></div>
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Tipe Event -->
                            <div class="col-md-6">
                                <label for="event_type" class="form-label fw-semibold">
                                    <i class="fas fa-layer-group me-1 text-primary"></i>
                                    Tipe Event <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="event_type" name="event_type" required>
                                    <option value="umum"
                                        <?= $event['event_type'] === 'umum' ? 'selected' : '' ?>>
                                        Umum (terbuka untuk publik)
                                    </option>
                                    <option value="internal"
                                        <?= $event['event_type'] === 'internal' ? 'selected' : '' ?>>
                                        Internal (khusus mahasiswa Unsika)
                                    </option>
                                </select>
                            </div>

                            <!-- Kuota Peserta -->
                            <div class="col-md-6">
                                <label for="quota" class="form-label fw-semibold">
                                    <i class="fas fa-users me-1 text-primary"></i>
                                    Kuota Peserta <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="quota" name="quota"
                                       min="1" max="9999"
                                       value="<?= $event['quota'] ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Kategori -->
                            <div class="col-md-6">
                                <label for="category" class="form-label fw-semibold">
                                    <i class="fas fa-folder me-1 text-primary"></i>
                                    Kategori <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat ?>"
                                            <?= ($event['category'] === $cat) ? 'selected' : '' ?>>
                                            <?= $cat ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Tanggal Buka -->
                            <div class="col-md-6">
                                <label for="registration_open" class="form-label fw-semibold">
                                    <i class="fas fa-calendar-plus me-1 text-primary"></i>
                                    Pendaftaran Dibuka <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control"
                                       id="registration_open" name="registration_open"
                                       value="<?= htmlspecialchars($event['registration_open']) ?>" required>
                            </div>

                            <!-- Tanggal Tutup -->
                            <div class="col-md-6">
                                <label for="registration_close" class="form-label fw-semibold">
                                    <i class="fas fa-calendar-times me-1 text-primary"></i>
                                    Pendaftaran Ditutup <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control"
                                       id="registration_close" name="registration_close"
                                       value="<?= htmlspecialchars($event['registration_close']) ?>" required>
                            </div>
                        </div>

                        <!-- Checkbox Status Aktif -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox"
                                   id="is_active" name="is_active" value="1"
                                   <?= $event['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                Event aktif (dapat diakses oleh peserta)
                            </label>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="d-flex gap-3 justify-content-end">
                            <a href="events.php" class="btn btn-outline-secondary px-4">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                            <button type="submit" class="btn btn-warning text-white px-5">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Script preview gambar baru -->
<script>
document.getElementById('documentation').addEventListener('change', function () {
    var file    = this.files[0];
    var preview = document.getElementById('imgPreview');
    if (file) {
        var reader = new FileReader();
        reader.onload = function (e) {
            preview.innerHTML =
                '<img src="' + e.target.result + '" class="img-thumbnail mt-1"'
              + ' style="max-width:200px; max-height:130px; object-fit:cover;" alt="Preview">';
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
});
</script>

<?php include '../includes/footer.php'; ?>