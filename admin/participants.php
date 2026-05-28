<?php
// ============================================================
//  admin/participants.php
//  Menampilkan daftar peserta yang terdaftar pada suatu event
//  Fitur: tabel DataTables, export CSV, info kuota
// ============================================================

include 'includes/auth.php';
require_once '../config/database.php';

// Validasi parameter event_id
$event_id = intval($_GET['event_id'] ?? 0);
if ($event_id <= 0) {
    header('Location: events.php');
    exit;
}

// Ambil data event
$stmt_ev = $conn->prepare('SELECT * FROM events WHERE id = ?');
$stmt_ev->bind_param('i', $event_id);
$stmt_ev->execute();
$event_result = $stmt_ev->get_result();

if ($event_result->num_rows === 0) {
    $_SESSION['error'] = 'Event tidak ditemukan.';
    header('Location: events.php');
    exit;
}
$event = $event_result->fetch_assoc();
$stmt_ev->close();

// Ambil semua data peserta event ini
$stmt_p = $conn->prepare(
    'SELECT * FROM registrations WHERE event_id = ? ORDER BY registered_at ASC'
);
$stmt_p->bind_param('i', $event_id);
$stmt_p->execute();
$participants  = $stmt_p->get_result();
$total_peserta = $participants->num_rows;

$page_title = 'Peserta: ' . htmlspecialchars($event['name']);
include '../includes/header.php';

// Hitung persentase kuota terisi
$pct = ($event['quota'] > 0)
       ? min(100, round($total_peserta / $event['quota'] * 100))
       : 0;
?>

<div class="container">

    <!-- ── Judul & Navigasi ── -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-primary mb-1 fs-4">
                Data Peserta
            </h2>
            <p class="text-muted small mb-0">
                Event: <strong><?= htmlspecialchars($event['name']) ?></strong>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="events.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Kembali
            </a>
            <?php if ($total_peserta > 0): ?>
            <a href="export_csv.php?event_id=<?= $event_id ?>"
            class="btn btn-success btn-sm">
                <i class="fas fa-file-csv me-1"></i>Export CSV
            </a>
            <?php else: ?>
            <button class="btn btn-success btn-sm" disabled title="Belum ada peserta">
                <i class="fas fa-file-csv me-1"></i>Export CSV
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Info Ringkasan Event ── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header text-white fw-bold"
             style="background:linear-gradient(135deg,#1a2e42,#2563eb);">
             Informasi Event
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <small class="text-muted d-block">Kategori</small>
                    <strong><?= htmlspecialchars($event['category'] ?? '-') ?></strong>
                </div>
                <div class="col-6 col-md-3">
                    <small class="text-muted d-block">Tipe Event</small>
                    <span class="badge"
                          style="background:<?= $event['event_type'] === 'umum' ? '#dbeafe' : '#ede9fe' ?>;
                                 color:<?= $event['event_type'] === 'umum' ? '#1d4ed8' : '#6d28d9' ?>;">
                        <?= ucfirst($event['event_type']) ?>
                    </span>
                </div>
                <div class="col-6 col-md-3">
                    <small class="text-muted d-block">Kuota</small>
                    <strong><?= $event['quota'] ?> peserta</strong>
                </div>
                <div class="col-6 col-md-3">
                    <small class="text-muted d-block">Terdaftar</small>
                    <strong class="<?= $total_peserta >= $event['quota'] ? 'text-danger' : 'text-success' ?>">
                        <?= $total_peserta ?> peserta
                    </strong>
                </div>

                <!-- Progress bar kuota -->
                <div class="col-12">
                    <small class="text-muted">Kapasitas terisi: <?= $pct ?>%</small>
                    <div class="progress mt-1" style="height:8px; border-radius:9999px;">
                        <div class="progress-bar
                            <?= $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success') ?>"
                             style="width:<?= $pct ?>%; border-radius:9999px;"
                             role="progressbar">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Flash Messages ── -->
    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>
        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- ── Tabel Peserta ── -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center fw-bold">
            <span>
                Daftar Peserta Terdaftar
            </span>
            <span class="badge bg-primary"><?= $total_peserta ?> orang</span>
        </div>
        <div class="card-body p-0 p-md-3">
            <?php if ($total_peserta > 0): ?>
            <div class="table-responsive">
                <table id="dataTable"
                       class="table table-bordered table-hover table-sm w-100 align-middle">
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
                        <?php
                        $no = 1;
                        while ($p = $participants->fetch_assoc()):
                        ?>
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
            <!-- Tampilan jika belum ada peserta -->
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted d-block mb-3 opacity-25"></i>
                <h6 class="text-muted">Belum ada peserta yang mendaftar</h6>
                <p class="text-muted small mb-0">
                    Data peserta akan muncul di sini setelah ada yang mendaftar.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php
$stmt_p->close();
$conn->close();
include '../includes/footer.php';
?>