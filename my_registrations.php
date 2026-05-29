<?php
// ============================================================
//  my_registrations.php
//  Riwayat pendaftaran event — berfungsi sebagai bukti pendaftaran
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['member_logged_in']) || $_SESSION['member_logged_in'] !== true) {
    header('Location: login.php?redirect=' . urlencode('my_registrations.php'));
    exit;
}

$page_title = 'Riwayat Pendaftaran — BEM Fasilkom Unsika';
include 'includes/header.php';
require_once 'config/database.php';

$member_email = $_SESSION['member_email'] ?? '';
$today        = date('Y-m-d');

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
        e.description,
        (SELECT COUNT(*) FROM registrations rc WHERE rc.event_id = e.id) AS total_registered
     FROM registrations r
     JOIN events e ON e.id = r.event_id
     WHERE r.email = ?
     ORDER BY r.registered_at DESC"
);
$stmt->bind_param('s', $member_email);
$stmt->execute();
$result     = $stmt->get_result();
$total_regs = $result->num_rows;
$stmt->close();
$conn->close();

function getEventStatus(array $ev, string $today): array {
    if (!$ev['is_active']) {
        return ['label' => 'Nonaktif',    'color' => 'secondary', 'icon' => 'fa-ban'];
    }
    if ($today < $ev['event_date']) {
        return ['label' => 'Akan Datang', 'color' => 'info',      'icon' => 'fa-clock'];
    }
    if ($today === $ev['event_date']) {
        return ['label' => 'Berlangsung', 'color' => 'success',   'icon' => 'fa-circle-play'];
    }
    return     ['label' => 'Selesai',     'color' => 'secondary', 'icon' => 'fa-circle-check'];
}
?>

<div class="container py-2">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-primary mb-1 fs-4">Riwayat Pendaftaran</h2>
            <p class="text-muted small mb-0">
                Halaman ini berfungsi sebagai bukti pendaftaran event Anda
            </p>
        </div>
    </div>

    <?php if ($total_regs === 0): ?>
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

    <!-- Statistik -->
    <div class="row g-3 mb-4">
        <?php
        $result->data_seek(0);
        $cnt_upcoming = 0; $cnt_done = 0;
        while ($row = $result->fetch_assoc()) {
            $st = getEventStatus($row, $today);
            if (in_array($st['label'], ['Akan Datang', 'Berlangsung'])) $cnt_upcoming++;
            elseif ($st['label'] === 'Selesai') $cnt_done++;
        }
        $result->data_seek(0);
        ?>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center py-3"
                 style="border-top:3px solid #2563eb !important;">
                <div class="fw-bold fs-3 text-primary"><?= $total_regs ?></div>
                <div class="text-muted small">Total</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center py-3"
                 style="border-top:3px solid #10b981 !important;">
                <div class="fw-bold fs-3" style="color:#10b981;"><?= $cnt_upcoming ?></div>
                <div class="text-muted small">Aktif / Akan Datang</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center py-3"
                 style="border-top:3px solid #94a3b8 !important;">
                <div class="fw-bold fs-3 text-secondary"><?= $cnt_done ?></div>
                <div class="text-muted small">Selesai</div>
            </div>
        </div>
    </div>

    <!-- Daftar Kartu Riwayat / Bukti Pendaftaran -->
    <div class="d-flex flex-column gap-3">
        <?php while ($reg = $result->fetch_assoc()):
            $status     = getEventStatus($reg, $today);
            $event_date = !empty($reg['event_date'])
                          ? date('d M Y', strtotime($reg['event_date'])) : '-';
            $reg_date   = date('d M Y, H:i', strtotime($reg['registered_at']));
            $reg_code   = 'BEM-' . strtoupper(substr(md5($reg['reg_id'] . $reg['email']), 0, 8));
        ?>
        <div class="card border-0 shadow-sm overflow-hidden registration-card" id="bukti-<?= $reg['reg_id'] ?>">

            <!-- Header kartu bukti -->
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"
                 style="background:linear-gradient(135deg,#1a2e42,#2563eb); color:#fff;">
                <div>
                    <div class="fw-bold" style="font-size:.85rem; opacity:.7; letter-spacing:.05em;">
                        BUKTI PENDAFTARAN
                    </div>
                    <div class="fw-bold" style="font-size:1rem;">
                        <?= htmlspecialchars($reg['event_name']) ?>
                    </div>
                </div>
                <div class="text-end">
                    <div style="font-size:.7rem; opacity:.75;">Kode Pendaftaran</div>
                    <div class="fw-bold" style="font-size:.95rem; font-family:monospace; letter-spacing:.08em;">
                        <?= $reg_code ?>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-4">

                    <!-- Kolom kiri: info peserta -->
                    <div class="col-md-6">
                        <h6 class="fw-bold text-primary mb-3" style="font-size:.8rem; text-transform:uppercase; letter-spacing:.05em;">
                            <i class="fas fa-user me-2"></i>Data Peserta
                        </h6>
                        <table class="table table-sm table-borderless mb-0" style="font-size:.85rem;">
                            <tbody>
                                <tr>
                                    <td class="text-muted ps-0" style="width:40%;">Nama Lengkap</td>
                                    <td class="fw-semibold"><?= htmlspecialchars($reg['full_name']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-0">Email</td>
                                    <td><?= htmlspecialchars($reg['email']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-0">Telepon</td>
                                    <td><?= htmlspecialchars($reg['phone']) ?></td>
                                </tr>
                                <?php if ($reg['event_type'] === 'internal'): ?>
                                <tr>
                                    <td class="text-muted ps-0">NPM</td>
                                    <td><code><?= htmlspecialchars($reg['npm'] ?? '-') ?></code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-0">Fakultas</td>
                                    <td><?= htmlspecialchars($reg['faculty'] ?? '-') ?></td>
                                </tr>
                                <?php else: ?>
                                <tr>
                                    <td class="text-muted ps-0">Instansi</td>
                                    <td><?= htmlspecialchars($reg['institution'] ?? '-') ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td class="text-muted ps-0">Tgl Daftar</td>
                                    <td><?= $reg_date ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Kolom kanan: info event -->
                    <div class="col-md-6">
                        <h6 class="fw-bold text-primary mb-3" style="font-size:.8rem; text-transform:uppercase; letter-spacing:.05em;">
                            <i class="fas fa-calendar-alt me-2"></i>Detail Event
                        </h6>

                        <?php if (!empty($reg['documentation'])): ?>
                        <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($reg['documentation']) ?>"
                             class="img-fluid rounded mb-3"
                             style="max-height:120px; width:100%; object-fit:cover;"
                             alt="Poster event">
                        <?php endif; ?>

                        <table class="table table-sm table-borderless mb-0" style="font-size:.85rem;">
                            <tbody>
                                <tr>
                                    <td class="text-muted ps-0" style="width:40%;">Kategori</td>
                                    <td><?= htmlspecialchars($reg['category']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-0">Tipe</td>
                                    <td><?= ucfirst($reg['event_type']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-0">Tanggal</td>
                                    <td class="fw-semibold"><?= $event_date ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted ps-0">Status Event</td>
                                    <td>
                                        <span class="badge bg-<?= $status['color'] ?>" style="font-size:.68rem;">
                                            <i class="fas <?= $status['icon'] ?> me-1"></i>
                                            <?= $status['label'] ?>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

                <!-- Footer bukti -->
                <hr class="my-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <p class="text-muted mb-0" style="font-size:.75rem;">
                        <i class="fas fa-info-circle me-1"></i>
                        Simpan halaman ini atau cetak sebagai bukti pendaftaran Anda.
                        Tunjukkan kode <strong><?= $reg_code ?></strong> kepada panitia saat acara berlangsung.
                    </p>
                    <button onclick="cetakBukti('bukti-<?= $reg['reg_id'] ?>')"
                            class="btn btn-outline-primary btn-sm no-print">
                        <i class="fas fa-print me-1"></i>Cetak Bukti Ini
                    </button>
                </div>

            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php endif; ?>

</div>

<!-- CSS tambahan untuk tampilan cetak -->
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
<?php for ($i = 1; $i <= $total_regs; $i++): ?>
.registration-card:nth-child(<?= $i ?>) { animation-delay: <?= $i * 0.06 ?>s; }
<?php endfor; ?>

@media print {
    nav, footer, .no-print { display: none !important; }
    body { background: white !important; font-size: 12pt; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; page-break-inside: avoid; }
    .card-header { background: #1a2e42 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .container { max-width: 100% !important; }
    .registration-card { margin-bottom: 20px; }
}
</style>

<!-- Script cetak per kartu -->
<script>
function cetakBukti(elementId) {
    var konten  = document.getElementById(elementId).outerHTML;
    var jendela = window.open('', '_blank');
    jendela.document.write(`
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Bukti Pendaftaran</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                body { padding: 20px; font-family: Arial, sans-serif; }
                .card-header {
                    background: linear-gradient(135deg,#1a2e42,#2563eb) !important;
                    color: white !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                    padding: 16px 20px;
                }
                .card-header * { color: white !important; }
                .no-print { display: none !important; }
                .badge { font-size: .75rem; padding: .3em .6em; }
                @media print {
                    body { padding: 0; }
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body>
            <div class="mb-3 text-center">
                <h5 style="color:#1a2e42;">BEM Fasilkom Unsika — Bukti Pendaftaran Event</h5>
                <p style="font-size:.8rem; color:#666;">Dicetak pada: ${new Date().toLocaleString('id-ID')}</p>
                <hr>
            </div>
            ${konten}
            <script>window.onload = function() { window.print(); }<\/script>
        </body>
        </html>
    `);
    jendela.document.close();
}
</script>

<?php include 'includes/footer.php'; ?>