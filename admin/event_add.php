<?php
// ============================================================
//  admin/event_add.php
//  Form tambah event baru oleh admin
// ============================================================

include 'includes/auth.php';
require_once '../config/database.php';

$page_title = 'Tambah Event — BEM Fasilkom Unsika';
$csrf       = generateCsrfToken();
include '../includes/header.php';

// Daftar kategori yang tersedia
$categories = ['Seminar', 'Workshop', 'Lomba', 'Sosial', 'Pelatihan', 'Lainnya'];
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Breadcrumb navigasi -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="events.php">Manajemen Event</a></li>
                    <li class="breadcrumb-item active">Tambah Event</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm">
                <div class="card-header text-white text-center py-3"
                     style="background:linear-gradient(135deg,#1a2e42,#2563eb);">
                    <h5 class="mb-0" style="color: white;">
                        Tambah Event Baru
                    </h5>
                </div>

                <div class="card-body p-4">
                    <!--
                        id="eventAddForm" → diperlukan oleh script.js untuk submit via AJAX
                        enctype="multipart/form-data" → untuk upload gambar
                    -->
                    <form id="eventAddForm"
                          action="event_save.php"
                          method="POST"
                          enctype="multipart/form-data">

                        <!-- Token keamanan CSRF -->
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                        <!-- Nama Event -->
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">
                                <i class="fas fa-tag me-1 text-primary"></i>
                                Nama Event <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name"
                                   placeholder="Contoh: Seminar Nasional Kecerdasan Buatan 2025"
                                   required maxlength="200">
                        </div>

                        <!-- Deskripsi -->
                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">
                                <i class="fas fa-align-left me-1 text-primary"></i>Deskripsi
                            </label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="4"
                                      placeholder="Jelaskan tujuan, manfaat, dan detail kegiatan event..."></textarea>
                        </div>

                        <!-- Tanggal Penyelenggaraan -->
                        <div class="mb-3">
                            <label for="event_date" class="form-label fw-semibold">
                                <i class="fas fa-calendar-day me-1 text-primary"></i>
                                Tanggal Penyelenggaraan <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="event_date" name="event_date" required>
                        </div>

                        <!-- Upload Gambar / Poster Event -->
                        <div class="mb-3">
                            <label for="documentation" class="form-label fw-semibold">
                                <i class="fas fa-image me-1 text-primary"></i>
                                Gambar/Poster Event <span class="text-muted fw-normal">(opsional)</span>
                            </label>
                            <input type="file" class="form-control" id="documentation"
                                   name="documentation" accept="image/*">
                            <div class="form-text">Format: JPG, PNG, GIF, WebP. Ukuran maksimal: 2MB.</div>
                            <!-- Preview gambar sebelum upload -->
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
                                    <option value="">-- Pilih Tipe --</option>
                                    <option value="umum">Umum (terbuka untuk publik)</option>
                                    <option value="internal">Internal (khusus mahasiswa Unsika)</option>
                                </select>
                            </div>

                            <!-- Kuota Peserta -->
                            <div class="col-md-6">
                                <label for="quota" class="form-label fw-semibold">
                                    <i class="fas fa-users me-1 text-primary"></i>
                                    Kuota Peserta <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="quota" name="quota"
                                       min="1" max="9999" placeholder="Contoh: 100" required>
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
                                        <option value="<?= $cat ?>"><?= $cat ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Tanggal Buka Pendaftaran -->
                            <div class="col-md-6">
                                <label for="registration_open" class="form-label fw-semibold">
                                    <i class="fas fa-calendar-plus me-1 text-primary"></i>
                                    Pendaftaran Dibuka <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control"
                                       id="registration_open" name="registration_open" required>
                            </div>

                            <!-- Tanggal Tutup Pendaftaran -->
                            <div class="col-md-6">
                                <label for="registration_close" class="form-label fw-semibold">
                                    <i class="fas fa-calendar-times me-1 text-primary"></i>
                                    Pendaftaran Ditutup <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control"
                                       id="registration_close" name="registration_close" required>
                            </div>
                        </div>

                        <!-- Checkbox Status Aktif -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox"
                                   id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">
                                Aktifkan event (langsung dapat diakses peserta)
                            </label>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="d-flex gap-3 justify-content-end">
                            <a href="events.php" class="btn btn-outline-secondary px-4">
                                Batal
                            </a>
                            <button type="submit" class="btn btn-primary px-5">
                                Simpan Event
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Script preview gambar sebelum diupload -->
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