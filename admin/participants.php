<?php
// admin/participants.php — Daftar Peserta per Event
   
include 'includes/auth.php';
require_once '../config/database.php';

$event_id = intval($_GET['event_id'] ?? 0);
if ($event_id <= 0) {
    header('Location: events.php');
    exit;
}

/* ── Ambil data event ── */
$stmtEv = $conn->prepare('SELECT * FROM events WHERE id = ?');
$stmtEv->bind_param('i', $event_id);
$stmtEv->execute();
$eventResult = $stmtEv->get_result();

if ($eventResult->num_rows === 0) {
    $_SESSION['error'] = 'Event tidak ditemukan.';
    header('Location: events.php');
    exit;
}
$event = $eventResult->fetch_assoc();
$stmtEv->close();

/* ── Ambil daftar peserta ── */
$stmtP = $conn->prepare(
    'SELECT * FROM registrations WHERE event_id = ? ORDER BY registered_at ASC'
);
$stmtP->bind_param('i', $event_id);
$stmtP->execute();
$participants = $stmtP->get_result();
$totalPeserta = $participants->num_rows;

$page_title = 'Peserta: ' . htmlspecialchars($event['name']);
include '../includes/header.php';
?>

<div class="container fade-in">

    <!-- ── Header & Navigasi ─────────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-primary mb-1">
                <i class="fas fa-users me-2"></i>Data Peserta
            </h2>
            <p class="text-muted mb-0 small">
                Event: <strong><?= htmlspecialchars($event['name']) ?></strong>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="events.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
            <a href="export_csv.php?event_id=<?= $event_id ?>" class="btn btn-success btn-sm">
                <i class="fas fa-file-csv me-1"></i>Export CSV
            </a>
        </div>
    </div>

    <!-- ── Info Event ────────────────────────────────────────── -->
    <div class="card mb-4">
        <div class="card-header" style="background:linear-gradient(135deg,#1a2e42,#2563eb);color:#fff;">
            <h6 class="mb-0 fw-bold">
                <i class="fas fa-info-circle me-2"></i>Informasi Event
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-6 col-md-3">
                    <small class="text-muted d-block">Kategori</small>
                    <strong><?= htmlspecialchars($event['category'] ?? '-') ?></strong>
                </div>
                <div class="col-sm-6 col-md-3">
                    <small class="text-muted d-block">Tipe</small>
                    <span class="badge <?= $event['event_type'] === 'umum' ? 'bg-info text-dark' : 'bg-secondary' ?>">
                        <?= ucfirst($event['event_type']) ?>
                    </span>
                </div>
                <div class="col-sm-6 col-md-3">
                    <small class="text-muted d-block">Kuota</small>
                    <strong><?= $event['quota'] ?> peserta</strong>
                </div>
                <div class="col-sm-6 col-md-3">
                    <small class="text-muted d-block">Terdaftar</small>
                    <strong class="<?= $totalPeserta >= $event['quota'] ? 'text-danger' : 'text-success' ?>">
                        <?= $totalPeserta ?> peserta
                    </strong>
                </div>
                <div class="col-12">
                    <!-- Progress bar kuota -->
                    <?php $pct = $event['quota'] > 0 ? min(100, round($totalPeserta / $event['quota'] * 100)) : 0; ?>
                    <small class="text-muted">Terisi <?= $pct ?>%</small>
                    <div class="progress mt-1" style="height:8px;border-radius:9999px;">
                        <div class="progress-bar <?= $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success') ?>"
                             style="width:<?= $pct ?>%;border-radius:9999px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Flash Messages ───────────────────────────────────── -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ── Tabel Peserta ─────────────────────────────────────── -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2"></i>Daftar Peserta Terdaftar</span>
            <span class="badge bg-primary"><?= $totalPeserta ?> orang</span>
        </div>
        <div class="card-body">
            <?php if ($totalPeserta > 0): ?>
                <div class="table-responsive">
                    <table id="dataTable" class="table table-bordered table-hover table-sm w-100 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:50px;">No</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <?php if ($event['event_type'] === 'umum'): ?>
                                    <th>Instansi</th>
                                <?php else: ?>
                                    <th>NPM</th>
                                    <th>Fakultas</th>
                                <?php endif; ?>
                                <th>Telepon</th>
                                <th class="text-center">Waktu Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($p = $participants->fetch_assoc()): ?>
                            <tr>
                                <td class="text-center text-muted"><?= $no++ ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($p['full_name']) ?></td>
                                <td>
                                    <a href="mailto:<?= htmlspecialchars($p['email']) ?>"
                                       class="text-decoration-none small">
                                        <?= htmlspecialchars($p['email']) ?>
                                    </a>
                                </td>
                                <?php if ($event['event_type'] === 'umum'): ?>
                                    <td><?= htmlspecialchars($p['institution'] ?? '-') ?></td>
                                <?php else: ?>
                                    <td><code><?= htmlspecialchars($p['npm'] ?? '-') ?></code></td>
                                    <td><?= htmlspecialchars($p['faculty'] ?? '-') ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($p['phone']) ?></td>
                                <td class="text-center small text-muted">
                                    <?= date('d/m/Y H:i', strtotime($p['registered_at'])) ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted opacity-25 mb-3 d-block"></i>
                    <h6 class="text-muted">Belum ada peserta yang mendaftar</h6>
                    <p class="text-muted small">Data peserta akan muncul di sini setelah ada yang mendaftar.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php
$stmtP->close();
$conn->close();
include '../includes/footer.php';
?>