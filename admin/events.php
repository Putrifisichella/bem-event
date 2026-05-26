<?php
// ============================================================
//  admin/events.php
//  Halaman daftar semua event dengan tabel manajemen:
//  lihat peserta, edit, hapus, dan toggle status aktif
// ============================================================

include 'includes/auth.php';
require_once '../config/database.php';

$page_title = 'Manajemen Event — BEM Fasilkom Unsika';
include '../includes/header.php';

// Ambil semua event beserta jumlah pendaftar
$result = $conn->query(
    "SELECT e.*, COUNT(r.id) AS registered
     FROM events e
     LEFT JOIN registrations r ON r.event_id = e.id
     GROUP BY e.id
     ORDER BY e.created_at DESC"
);

$today = date('Y-m-d');

// Generate CSRF token untuk form delete & toggle
$csrf = generateCsrfToken();
?>

<div class="container-fluid px-3 px-md-4">

    <!-- ── Judul Halaman ── -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-primary mb-1 fs-4">
                <i class="fas fa-calendar-alt me-2"></i>Manajemen Event
            </h2>
            <p class="text-muted small mb-0">
                Kelola semua event pendaftaran BEM Fasilkom Unsika
            </p>
        </div>
        <a href="event_add.php" class="btn btn-primary btn-sm px-3">
            <i class="fas fa-plus-circle me-1"></i>Tambah Event
        </a>
    </div>

    <!-- ── Flash Messages ── -->
    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>
        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- ── Tabel Event ── -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">
                <i class="fas fa-list me-2 text-primary"></i>Daftar Semua Event
            </span>
            <span class="badge bg-primary">
                <?= $result ? $result->num_rows : 0 ?> event
            </span>
        </div>
        <div class="card-body p-0 p-md-3">
            <div class="table-responsive">
                <table id="dataTable" class="table table-hover table-bordered table-sm w-100 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:46px;">No</th>
                            <th>Nama Event</th>
                            <th class="text-center" style="width:85px;">Tipe</th>
                            <th class="text-center" style="width:70px;">Kuota</th>
                            <th class="text-center" style="width:70px;">Daftar</th>
                            <th class="text-center" style="width:70px;">Sisa</th>
                            <th class="text-center d-none d-lg-table-cell" style="width:100px;">Tgl Event</th>
                            <th class="text-center" style="width:110px;">Status</th>
                            <th class="text-center" style="width:220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        while ($event = $result->fetch_assoc()):
                            $registered = (int) $event['registered'];
                            $remaining  = $event['quota'] - $registered;

                            // Tentukan status event berdasarkan kondisi
                            if (!$event['is_active']) {
                                $status = 'nonaktif';
                            } elseif ($today < $event['registration_open']) {
                                $status = 'belum_buka';
                            } elseif ($today > $event['registration_close']) {
                                $status = 'ditutup';
                            } elseif ($remaining <= 0) {
                                $status = 'penuh';
                            } else {
                                $status = 'aktif';
                            }

                            // Peta label dan warna badge status
                            $status_map = [
                                'nonaktif'   => ['secondary', 'Nonaktif'],
                                'belum_buka' => ['info',      'Belum Buka'],
                                'ditutup'    => ['warning',   'Ditutup'],
                                'penuh'      => ['danger',    'Kuota Penuh'],
                                'aktif'      => ['success',   'Aktif'],
                            ];
                            [$badge_color, $badge_label] = $status_map[$status];
                        ?>
                        <tr>
                            <!-- Nomor urut -->
                            <td class="text-center text-muted small"><?= $no++ ?></td>

                            <!-- Nama event & tanggal (ditampilkan di mobile) -->
                            <td>
                                <span class="fw-semibold"><?= htmlspecialchars($event['name']) ?></span>
                                <div class="d-lg-none text-muted small mt-1">
                                    <i class="fas fa-calendar-day me-1"></i>
                                    <?= !empty($event['event_date'])
                                        ? date('d/m/Y', strtotime($event['event_date']))
                                        : '-' ?>
                                </div>
                            </td>

                            <!-- Tipe event -->
                            <td class="text-center">
                                <span class="badge"
                                      style="background:<?= $event['event_type'] === 'umum' ? '#dbeafe' : '#ede9fe' ?>;
                                             color:<?= $event['event_type'] === 'umum' ? '#1d4ed8' : '#6d28d9' ?>;
                                             font-size:.7rem;">
                                    <?= ucfirst($event['event_type']) ?>
                                </span>
                            </td>

                            <!-- Kuota, Terdaftar, Sisa -->
                            <td class="text-center"><?= $event['quota'] ?></td>
                            <td class="text-center"><?= $registered ?></td>
                            <td class="text-center">
                                <?php if ($remaining > 0): ?>
                                    <span class="badge bg-success" style="font-size:.7rem;"><?= $remaining ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger" style="font-size:.7rem;">Penuh</span>
                                <?php endif; ?>
                            </td>

                            <!-- Tanggal event (hanya desktop) -->
                            <td class="text-center d-none d-lg-table-cell small text-muted">
                                <?= !empty($event['event_date'])
                                    ? date('d/m/Y', strtotime($event['event_date']))
                                    : '-' ?>
                            </td>

                            <!-- Badge status -->
                            <td class="text-center">
                                <span class="badge bg-<?= $badge_color ?>" style="font-size:.7rem;">
                                    <?= $badge_label ?>
                                </span>
                            </td>

                            <!-- Tombol Aksi -->
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1 flex-wrap">

                                    <!-- Lihat Peserta -->
                                    <a href="participants.php?event_id=<?= $event['id'] ?>"
                                       class="btn btn-info btn-sm text-white"
                                       title="Lihat Peserta"
                                       data-bs-toggle="tooltip">
                                        <i class="fas fa-users"></i>
                                        <span class="d-none d-xl-inline ms-1">Peserta</span>
                                    </a>

                                    <!-- Edit Event -->
                                    <a href="event_edit.php?id=<?= $event['id'] ?>"
                                       class="btn btn-warning btn-sm text-white"
                                       title="Edit Event"
                                       data-bs-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                        <span class="d-none d-xl-inline ms-1">Edit</span>
                                    </a>

                                    <!-- Hapus Event (via AJAX dengan konfirmasi) -->
                                    <form action="event_delete.php" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                        <button type="button"
                                                class="btn btn-danger btn-sm btn-delete"
                                                title="Hapus Event"
                                                data-bs-toggle="tooltip">
                                            <i class="fas fa-trash"></i>
                                            <span class="d-none d-xl-inline ms-1">Hapus</span>
                                        </button>
                                    </form>

                                    <!-- Toggle Aktif/Nonaktif (via AJAX) -->
                                    <?php $is_deactivatable = in_array($status, ['aktif', 'penuh', 'belum_buka']); ?>
                                    <a href="toggle_event.php?id=<?= $event['id'] ?>"
                                       class="btn btn-sm btn-toggle
                                              <?= $is_deactivatable ? 'btn-secondary' : 'btn-success' ?>"
                                       title="<?= $is_deactivatable ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                       data-bs-toggle="tooltip">
                                        <i class="fas <?= $is_deactivatable ? 'fa-ban' : 'fa-check' ?>"></i>
                                        <span class="d-none d-xl-inline ms-1">
                                            <?= $is_deactivatable ? 'Off' : 'On' ?>
                                        </span>
                                    </a>

                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php
$conn->close();
include '../includes/footer.php';
?>