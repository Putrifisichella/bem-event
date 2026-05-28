<?php
// ============================================================
//  my_registrations.php
//  Riwayat pendaftaran event untuk member yang sudah login
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

// Harus login sebagai member
if (empty($_SESSION['member_logged_in']) || $_SESSION['member_logged_in'] !== true) {
    header('Location: login.php?redirect=' . urlencode('my_registrations.php'));
    exit;
}

$page_title = 'Riwayat Pendaftaran — BEM Fasilkom Unsika';
include 'includes/header.php';
require_once 'config/database.php';

$member_email = $_SESSION['member_email'] ?? '';
$today        = date('Y-m-d');

// --- Ambil semua pendaftaran milik user ini beserta data event ---
$stmt = $conn->prepare(
    "SELECT
        r.id            AS reg_id,
        r.full_name,
        r.email,
        r.phone,
        r.npm,
        r.faculty,
        r.institution,
        r.registered_at,
        e.id            AS event_id,
        e.name          AS event_name,
        e.category,
        e.event_type,
        e.event_date,
        e.registration_open,
        e.registration_close,
        e.quota,
        e.is_active,
        e.documentation,
        (SELECT COUNT(*) FROM registrations rc WHERE rc.event_id = e.id) AS total_registered
     FROM registrations r
     JOIN events e ON e.id = r.event_id
     WHERE r.email = ?
     ORDER BY r.registered_at DESC"
);
$stmt->bind_param('s', $member_email);
$stmt->execute();
$result      = $stmt->get_result();
$total_regs  = $result->num_rows;
$stmt->close();
$conn->close();

// Helper: tentukan status event
function getEventStatus(array $ev, string $today): array {
    if (!$ev['is_active']) {
        return ['label' => 'Nonaktif',        'color' => 'secondary', 'icon' => 'fa-ban'];
    }
    if ($today < $ev['event_date']) {
        return ['label' => 'Akan Datang',     'color' => 'info',      'icon' => 'fa-clock'];
    }
    if ($today === $ev['event_date']) {
        return ['label' => 'Berlangsung',     'color' => 'success',   'icon' => 'fa-circle-play'];
    }
    return     ['label' => 'Selesai',         'color' => 'secondary', 'icon' => 'fa-circle-check'];
}
?>

<div class="container py-2">

    <!-- ── Judul ── -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-primary mb-1 fs-4">
                Riwayat Pendaftaran
            </h2>
            <p class="text-muted small mb-0">
                Semua event yang pernah Anda daftarkan
            </p>
        </div>
    </div>

    <?php if ($total_regs === 0): ?>
    <!-- ── Kosong ── -->
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <div class="mb-3" style="font-size:3.5rem; opacity:.18;">
                <i class="fas fa-calendar-xmark"></i>
            </div>
            <h5 class="fw-bold text-muted mb-2">Belum Ada Pendaftaran</h5>
            <p class="text-muted small mb-4">
                Anda belum mendaftar event apapun.<br>
                Yuk, ikuti event seru dari BEM Fasilkom Unsika!
            </p>
            <a href="index.php" class="btn btn-primary px-4">
                <i class="fas fa-search me-2"></i>Jelajahi Event
            </a>
        </div>
    </div>

    <?php else: ?>

    <!-- ── Counter ── -->
    <div class="row g-3 mb-4">
        <?php
        // Hitung statistik dari result (rewind pointer)
        $result->data_seek(0);
        $cnt_upcoming = 0; $cnt_done = 0; $cnt_all = $total_regs;
        while ($row = $result->fetch_assoc()) {
            $st = getEventStatus($row, $today);
            if (in_array($st['label'], ['Akan Datang', 'Berlangsung'])) $cnt_upcoming++;
            elseif ($st['label'] === 'Selesai') $cnt_done++;
        }
        $result->data_seek(0);
        ?>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center py-3"
                 style="border-top: 3px solid #2563eb !important;">
                <div class="fw-bold fs-3 text-primary"><?= $cnt_all ?></div>
                <div class="text-muted small">Total</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center py-3"
                 style="border-top: 3px solid #10b981 !important;">
                <div class="fw-bold fs-3" style="color:#10b981;"><?= $cnt_upcoming ?></div>
                <div class="text-muted small">Aktif / Akan Datang</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center py-3"
                 style="border-top: 3px solid #94a3b8 !important;">
                <div class="fw-bold fs-3 text-secondary"><?= $cnt_done ?></div>
                <div class="text-muted small">Selesai</div>
            </div>
        </div>
    </div>

    <!-- ── Daftar Kartu Riwayat ── -->
    <div class="d-flex flex-column gap-3">
        <?php while ($reg = $result->fetch_assoc()):
            $status     = getEventStatus($reg, $today);
            $event_date = !empty($reg['event_date'])
                          ? date('d M Y', strtotime($reg['event_date'])) : '-';
            $reg_date   = date('d M Y, H:i', strtotime($reg['registered_at']));
            $sisa_kuota = $reg['quota'] - $reg['total_registered'];
        ?>
        <div class="card border-0 shadow-sm overflow-hidden registration-card">
            <div class="row g-0">

                <!-- Thumbnail event -->
                <div class="col-auto d-none d-sm-flex align-items-stretch">
                    <?php if (!empty($reg['documentation'])): ?>
                        <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($reg['documentation']) ?>"
                             alt="<?= htmlspecialchars($reg['event_name']) ?>"
                             style="width:130px; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center bg-light"
                             style="width:130px;">
                            <i class="fas fa-calendar-alt fa-2x text-muted opacity-25"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Konten utama -->
                <div class="col">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">

                            <!-- Nama event + badge -->
                            <div class="flex-grow-1">
                                <h5 class="fw-bold mb-1" style="font-size:.95rem;">
                                    <?= htmlspecialchars($reg['event_name']) ?>
                                </h5>
                                <div class="d-flex flex-wrap gap-1">
                                    <!-- Kategori -->
                                    <span class="badge bg-info bg-opacity-10 text-info"
                                          style="font-size:.68rem; font-weight:600;">
                                        <?= htmlspecialchars($reg['category']) ?>
                                    </span>
                                    <!-- Tipe -->
                                    <span class="badge"
                                          style="font-size:.68rem; font-weight:600;
                                                 background:<?= $reg['event_type']==='umum' ? '#dbeafe' : '#ede9fe' ?>;
                                                 color:<?= $reg['event_type']==='umum' ? '#1d4ed8' : '#6d28d9' ?>;">
                                        <?= ucfirst($reg['event_type']) ?>
                                    </span>
                                    <!-- Status event -->
                                    <span class="badge bg-<?= $status['color'] ?>"
                                          style="font-size:.68rem;">
                                        <i class="fas <?= $status['icon'] ?> me-1"></i>
                                        <?= $status['label'] ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Nomor urut pendaftaran -->
                            <div class="text-end">
                                <div class="text-muted" style="font-size:.7rem;">ID Daftar</div>
                                <div class="fw-bold text-primary" style="font-size:.8rem;">
                                    #<?= str_pad($reg['reg_id'], 5, '0', STR_PAD_LEFT) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Info detail -->
                        <div class="row g-2 small text-muted mb-3">
                            <div class="col-6 col-md-3">
                                <i class="fas fa-calendar-day me-1 text-primary"></i>
                                <span class="fw-semibold text-dark">Tgl Event</span><br>
                                <span><?= $event_date ?></span>
                            </div>
                            <div class="col-6 col-md-3">
                                <i class="fas fa-clock me-1 text-primary"></i>
                                <span class="fw-semibold text-dark">Tgl Daftar</span><br>
                                <span><?= $reg_date ?></span>
                            </div>
                            <div class="col-6 col-md-3">
                                <i class="fas fa-user me-1 text-primary"></i>
                                <span class="fw-semibold text-dark">Nama</span><br>
                                <span><?= htmlspecialchars($reg['full_name']) ?></span>
                            </div>
                            <div class="col-6 col-md-3">
                                <i class="fas fa-phone me-1 text-primary"></i>
                                <span class="fw-semibold text-dark">Telepon</span><br>
                                <span><?= htmlspecialchars($reg['phone']) ?></span>
                            </div>

                            <?php if ($reg['event_type'] === 'internal'): ?>
                            <div class="col-6 col-md-3">
                                <i class="fas fa-id-card me-1 text-primary"></i>
                                <span class="fw-semibold text-dark">NPM</span><br>
                                <code style="font-size:.78rem;"><?= htmlspecialchars($reg['npm'] ?? '-') ?></code>
                            </div>
                            <div class="col-6 col-md-3">
                                <i class="fas fa-building me-1 text-primary"></i>
                                <span class="fw-semibold text-dark">Fakultas</span><br>
                                <span><?= htmlspecialchars($reg['faculty'] ?? '-') ?></span>
                            </div>
                            <?php else: ?>
                            <div class="col-6 col-md-3">
                                <i class="fas fa-building me-1 text-primary"></i>
                                <span class="fw-semibold text-dark">Instansi</span><br>
                                <span><?= htmlspecialchars($reg['institution'] ?? '-') ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Progress kuota -->
                        <?php
                        $pct = ($reg['quota'] > 0)
                               ? min(100, round($reg['total_registered'] / $reg['quota'] * 100))
                               : 100;
                        ?>
                        <div class="d-flex align-items-center gap-2">
                            <div class="flex-grow-1">
                                <div class="progress" style="height:5px; border-radius:9999px;">
                                    <div class="progress-bar
                                        <?= $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success') ?>"
                                         style="width:<?= $pct ?>%; border-radius:9999px;"
                                         role="progressbar">
                                    </div>
                                </div>
                            </div>
                            <span class="text-muted" style="font-size:.7rem; white-space:nowrap;">
                                <?= $reg['total_registered'] ?>/<?= $reg['quota'] ?> peserta
                                (<?= $pct ?>%)
                            </span>
                        </div>

                    </div>
                </div>

            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php endif; ?>

</div>

<!-- Animasi kartu masuk -->
<style>
.registration-card {
    transition: transform .25s, box-shadow .25s;
    animation: cardFadeIn .4s ease both;
}
.registration-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(13,27,42,.12) !important;
}
@keyframes cardFadeIn {
    from { opacity:0; transform: translateY(14px); }
    to   { opacity:1; transform: translateY(0); }
}
<?php
// Animasi bertahap per kartu
for ($i = 1; $i <= $total_regs; $i++) {
    echo ".registration-card:nth-child({$i}) { animation-delay: " . ($i * 0.06) . "s; }\n";
}
?>
</style>

<?php include 'includes/footer.php'; ?>