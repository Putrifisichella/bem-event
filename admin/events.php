<?php
include 'includes/auth.php';
require_once '../config/database.php';

$page_title = 'Manajemen Event — BEM Fasilkom Unsika';
include '../includes/header.php';

$sql = "SELECT e.*, COUNT(r.id) AS registered
        FROM events e
        LEFT JOIN registrations r ON r.event_id = e.id
        GROUP BY e.id
        ORDER BY e.created_at DESC";
$result = $conn->query($sql);
$today  = date('Y-m-d');
$csrf   = generateCsrfToken();
?>

<div class="container-fluid fade-in px-3 px-md-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="fw-bold text-primary fs-4 mb-0">
            <i class="fas fa-calendar-alt me-2"></i>Manajemen Event
        </h2>
        <a href="event_add.php" class="btn btn-primary btn-sm px-3">
            <i class="fas fa-plus-circle me-1"></i>Tambah Event
        </a>
    </div>

    <!-- Flash Messages -->
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

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="fas fa-list me-2"></i>Daftar Event</span>
            <span class="badge" style="background:#eff6ff;color:#2563eb;font-size:.72rem;">
                <?= $result ? $result->num_rows : 0 ?> event
            </span>
        </div>
        <div class="card-body p-0 p-md-3">
            <div class="table-responsive">
                <table id="dataTable" class="table table-bordered table-hover table-sm w-100 align-middle">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:46px;">No</th>
                            <th>Nama Event</th>
                            <th class="text-center" style="width:90px;">Tipe</th>
                            <th class="text-center" style="width:70px;">Kuota</th>
                            <th class="text-center" style="width:70px;">Daftar</th>
                            <th class="text-center" style="width:70px;">Sisa</th>
                            <th class="text-center d-none d-lg-table-cell" style="width:95px;">Tgl Event</th>
                            <th class="text-center" style="width:115px;">Status</th>
                            <th class="text-center" style="width:220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($event = $result->fetch_assoc()):
                            $registered = (int) $event['registered'];
                            $remaining  = $event['quota'] - $registered;

                            if (!$event['is_active']) {
                                $statusLabel = 'nonaktif';
                            } elseif ($today < $event['registration_open']) {
                                $statusLabel = 'belum_buka';
                            } elseif ($today > $event['registration_close']) {
                                $statusLabel = 'ditutup';
                            } elseif ($remaining <= 0) {
                                $statusLabel = 'penuh';
                            } else {
                                $statusLabel = 'aktif';
                            }
                        ?>
                        <tr>
                            <td class="text-center text-muted small"><?= $no++ ?></td>

                            <td>
                                <span class="fw-semibold"><?= htmlspecialchars($event['name']) ?></span>
                                <div class="d-lg-none text-muted small mt-1">
                                    <i class="fas fa-calendar-day me-1"></i>
                                    <?= !empty($event['event_date']) ? date('d/m/Y', strtotime($event['event_date'])) : '-' ?>
                                </div>
                            </td>

                            <td class="text-center">
                                <span class="badge <?= $event['event_type'] === 'umum' ? 'bg-info' : 'bg-secondary' ?> badge-sm">
                                    <?= ucfirst($event['event_type']) ?>
                                </span>
                            </td>

                            <td class="text-center"><?= $event['quota'] ?></td>
                            <td class="text-center"><?= $registered ?></td>

                            <td class="text-center">
                                <?php if ($remaining > 0): ?>
                                    <span class="badge bg-success badge-sm"><?= $remaining ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger badge-sm">Penuh</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center d-none d-lg-table-cell small">
                                <?= !empty($event['event_date']) ? date('d/m/Y', strtotime($event['event_date'])) : '-' ?>
                            </td>

                            <td class="text-center">
                                <?php
                                $badgeMap = [
                                    'nonaktif'   => ['bg-secondary', 'Nonaktif'],
                                    'belum_buka' => ['bg-info',      'Belum Buka'],
                                    'ditutup'    => ['bg-warning',   'Ditutup'],
                                    'penuh'      => ['bg-danger',    'Kuota Penuh'],
                                    'aktif'      => ['bg-success',   'Aktif'],
                                ];
                                [$badgeClass, $badgeText] = $badgeMap[$statusLabel];
                                ?>
                                <span class="badge <?= $badgeClass ?> badge-sm"><?= $badgeText ?></span>
                            </td>

                            <td class="text-center">
                                <div class="em-actions">
                                    <!-- Lihat Peserta -->
                                    <a href="participants.php?event_id=<?= $event['id'] ?>"
                                       class="em-btn em-btn-info" title="Lihat Peserta" data-bs-toggle="tooltip">
                                        <i class="fas fa-users"></i><span>Peserta</span>
                                    </a>

                                    <!-- Edit -->
                                    <a href="event_edit.php?id=<?= $event['id'] ?>"
                                       class="em-btn em-btn-warning" title="Edit Event" data-bs-toggle="tooltip">
                                        <i class="fas fa-edit"></i><span>Edit</span>
                                    </a>

                                    <!-- Hapus (AJAX) -->
                                    <form action="event_delete.php" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                        <button type="button" class="em-btn em-btn-danger btn-delete"
                                                title="Hapus Event" data-bs-toggle="tooltip">
                                            <i class="fas fa-trash"></i><span>Hapus</span>
                                        </button>
                                    </form>

                                    <!-- Toggle Status (AJAX) -->
                                    <?php $canDeactivate = in_array($statusLabel, ['aktif', 'penuh', 'belum_buka']); ?>
                                    <a href="toggle_event.php?id=<?= $event['id'] ?>"
                                       class="em-btn <?= $canDeactivate ? 'em-btn-muted' : 'em-btn-success' ?> btn-toggle"
                                       title="<?= $canDeactivate ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                       data-bs-toggle="tooltip">
                                        <i class="fas <?= $canDeactivate ? 'fa-ban' : 'fa-check' ?>"></i>
                                        <span><?= $canDeactivate ? 'Off' : 'On' ?></span>
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