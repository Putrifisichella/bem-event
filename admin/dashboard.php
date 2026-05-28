<?php
// ============================================================
//  admin/dashboard.php
//  Halaman utama panel admin: menampilkan statistik dan
//  ringkasan event terbaru
// ============================================================

// Cek autentikasi admin
include 'includes/auth.php';
require_once '../config/database.php';

$page_title = 'Dashboard Admin — BEM Fasilkom Unsika';
include '../includes/header.php';

// --- Ambil data statistik ---
$total_events        = (int) $conn->query("SELECT COUNT(*) AS c FROM events")->fetch_assoc()['c'];
$active_events       = (int) $conn->query("SELECT COUNT(*) AS c FROM events WHERE is_active = 1")->fetch_assoc()['c'];
$total_registrations = (int) $conn->query("SELECT COUNT(*) AS c FROM registrations")->fetch_assoc()['c'];

// --- Ambil 6 event terbaru beserta jumlah pendaftarnya ---
$latest_events = $conn->query(
    "SELECT e.*,
        (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registered
     FROM events e
     ORDER BY e.created_at DESC
     LIMIT 6"
);

$today = date('Y-m-d');
?>

<div class="container">

    <!-- ── Judul Halaman ── -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-primary mb-1">
                </i>Dashboard Admin
            </h2>
            <p class="text-muted mb-0 small">
                <i class="fas fa-calendar me-1"></i><?= date('l, d F Y') ?>
            </p>
        </div>
        <a href="event_add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i>Tambah Event Baru
        </a>
    </div>

    <!-- Sapaan admin -->
    <p class="lead mb-4">
        Selamat datang, <strong class="text-primary"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></strong>!
    </p>

    <!-- ── Kartu Statistik ── -->
    <div class="row g-3 mb-5">

        <!-- Total Event -->
        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100"
                 style="border-left:4px solid #2563eb !important;">
                <div class="card-body d-flex justify-content-between align-items-center p-4">
                    <div>
                        <p class="text-muted mb-1 small fw-semibold text-uppercase">Total Event</p>
                        <h3 class="fw-bold mb-0"><?= $total_events ?></h3>
                    </div>
                    <div style="font-size:2.2rem; color:#2563eb; opacity:.4;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Aktif -->
        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100"
                 style="border-left:4px solid #10b981 !important;">
                <div class="card-body d-flex justify-content-between align-items-center p-4">
                    <div>
                        <p class="text-muted mb-1 small fw-semibold text-uppercase">Event Aktif</p>
                        <h3 class="fw-bold mb-0"><?= $active_events ?></h3>
                    </div>
                    <div style="font-size:2.2rem; color:#10b981; opacity:.4;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Pendaftar -->
        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100"
                 style="border-left:4px solid #f59e0b !important;">
                <div class="card-body d-flex justify-content-between align-items-center p-4">
                    <div>
                        <p class="text-muted mb-1 small fw-semibold text-uppercase">Total Pendaftar</p>
                        <h3 class="fw-bold mb-0"><?= $total_registrations ?></h3>
                    </div>
                    <div style="font-size:2.2rem; color:#f59e0b; opacity:.4;">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ── Aksi Cepat & Event Terbaru ── -->
    <div class="row g-4">

        <!-- Aksi Cepat -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold border-bottom">
                    <i class="fas fa-bolt me-2 text-warning"></i>Aksi Cepat
                </div>
                <div class="card-body d-grid gap-3">
                    <a href="event_add.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Event Baru
                    </a>
                    <a href="events.php" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i>Kelola Semua Event
                    </a>
                </div>
            </div>
        </div>

        <!-- Event Terbaru -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold border-bottom">
                    <i class="fas fa-clock me-2 text-info"></i>Event Terbaru
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if ($latest_events && $latest_events->num_rows > 0):
                            while ($ev = $latest_events->fetch_assoc()):
                                $sisa = $ev['quota'] - (int) $ev['registered'];
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <div>
                                <i class="fas fa-calendar-day text-primary me-2"></i>
                                <span class="fw-semibold"><?= htmlspecialchars($ev['name']) ?></span>

                                <!-- Badge status event -->
                                <?php if (!$ev['is_active']): ?>
                                    <span class="badge bg-secondary ms-2" style="font-size:.68rem;">Nonaktif</span>
                                <?php elseif ($sisa <= 0): ?>
                                    <span class="badge bg-danger ms-2" style="font-size:.68rem;">Penuh</span>
                                <?php elseif ($today > $ev['registration_close']): ?>
                                    <span class="badge bg-warning ms-2" style="font-size:.68rem;">Ditutup</span>
                                <?php else: ?>
                                    <span class="badge bg-success ms-2" style="font-size:.68rem;">Aktif</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <?= date('d M Y', strtotime($ev['created_at'])) ?>
                            </small>
                        </li>
                        <?php endwhile; else: ?>
                        <li class="list-group-item text-center text-muted py-5">
                            <i class="fas fa-inbox fa-2x d-block mb-2 opacity-25"></i>
                            Belum ada event yang dibuat.
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
$conn->close();
include '../includes/footer.php';
?>