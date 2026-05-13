<?php
include 'includes/auth.php';
require_once '../config/database.php';

$page_title = 'Dashboard Admin — BEM Fasilkom Unsika';
include '../includes/header.php';

$total_events         = (int) $conn->query("SELECT COUNT(*) AS c FROM events")->fetch_assoc()['c'];
$active_events        = (int) $conn->query("SELECT COUNT(*) AS c FROM events WHERE is_active = 1")->fetch_assoc()['c'];
$total_registrations  = (int) $conn->query("SELECT COUNT(*) AS c FROM registrations")->fetch_assoc()['c'];

$today         = date('Y-m-d');
$latest_events = $conn->query("
    SELECT e.*, (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registered
    FROM events e ORDER BY e.created_at DESC LIMIT 6
");
?>

<div class="container fade-in">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-primary mb-1">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard Admin
            </h2>
            <p class="text-muted mb-0 small">
                <i class="fas fa-calendar me-1"></i><?= date('l, d F Y') ?>
            </p>
        </div>
        <a href="event_add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i>Tambah Event
        </a>
    </div>

    <p class="lead mb-4">
        Selamat datang,
        <strong class="text-primary"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></strong>!
    </p>

    <!-- Statistik Cards -->
    <div class="row g-3 mb-5">
        <div class="col-sm-6 col-xl-4">
            <div class="dashboard-stat-card">
                <div class="stat-content">
                    <p class="text-muted mb-1 small">Total Event</p>
                    <h3 class="fw-bold"><?= $total_events ?></h3>
                </div>
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="dashboard-stat-card" style="border-left-color:#10b981;">
                <div class="stat-content">
                    <p class="text-muted mb-1 small">Event Aktif</p>
                    <h3 class="fw-bold"><?= $active_events ?></h3>
                </div>
                <div class="stat-icon" style="color:#10b981;"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="dashboard-stat-card" style="border-left-color:#f59e0b;">
                <div class="stat-content">
                    <p class="text-muted mb-1 small">Total Pendaftar</p>
                    <h3 class="fw-bold"><?= $total_registrations ?></h3>
                </div>
                <div class="stat-icon" style="color:#f59e0b;"><i class="fas fa-users"></i></div>
            </div>
        </div>
    </div>

    <!-- Aksi Cepat & Event Terbaru -->
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><i class="fas fa-bolt me-2 text-warning"></i>Aksi Cepat</div>
                <div class="card-body d-grid gap-3">
                    <a href="events.php" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i>Kelola Semua Event
                    </a>
                    <a href="event_add.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Event Baru
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header"><i class="fas fa-clock me-2 text-info"></i>Event Terbaru</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if ($latest_events && $latest_events->num_rows > 0):
                            while ($ev = $latest_events->fetch_assoc()):
                                $rem = $ev['quota'] - (int)$ev['registered'];
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <i class="fas fa-calendar-day text-primary me-2"></i>
                                <span class="fw-semibold"><?= htmlspecialchars($ev['name']) ?></span>
                                <?php if (!$ev['is_active']): ?>
                                    <span class="badge bg-secondary ms-2 badge-sm">Nonaktif</span>
                                <?php elseif ($rem <= 0): ?>
                                    <span class="badge bg-danger ms-2 badge-sm">Kuota Penuh</span>
                                <?php elseif ($today > $ev['registration_close']): ?>
                                    <span class="badge bg-warning ms-2 badge-sm">Ditutup</span>
                                <?php else: ?>
                                    <span class="badge bg-success ms-2 badge-sm">Aktif</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><?= date('d M Y', strtotime($ev['created_at'])) ?></small>
                        </li>
                        <?php endwhile; else: ?>
                        <li class="list-group-item text-muted text-center py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                            Belum ada event.
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