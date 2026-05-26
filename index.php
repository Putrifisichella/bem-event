<?php
// ============================================================
//  index.php
//  Halaman utama: menampilkan daftar event yang tersedia
//  Fitur: search, filter kategori, pagination
// ============================================================

$page_title = 'Daftar Event — BEM Fasilkom Unsika';
include 'includes/header.php';
require_once 'config/database.php';

$today = date('Y-m-d');

// --- Ambil parameter dari URL ---
$page     = max(1, (int) ($_GET['page']     ?? 1));
$limit    = 6;                                          // Jumlah event per halaman
$offset   = ($page - 1) * $limit;
$search   = trim($_GET['search']   ?? '');
$category = trim($_GET['category'] ?? '');

// ============================================================
//  Bangun klausa WHERE secara dinamis
//  Hanya event yang aktif dan dalam masa pendaftaran yang tampil
// ============================================================
$where  = 'e.is_active = 1 AND e.registration_open <= CURDATE() AND e.registration_close >= CURDATE()';
$types  = '';
$params = [];

// Tambahkan filter pencarian jika ada
if (!empty($search)) {
    $where    .= ' AND (e.name LIKE ? OR e.description LIKE ?)';
    $like      = '%' . $search . '%';
    $types    .= 'ss';
    $params[]  = $like;
    $params[]  = $like;
}

// Tambahkan filter kategori jika dipilih
if (!empty($category)) {
    $where    .= ' AND e.category = ?';
    $types    .= 's';
    $params[]  = $category;
}

// --- Hitung total event untuk pagination ---
$stmt_count = $conn->prepare("SELECT COUNT(*) AS total FROM events e WHERE {$where}");
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_rows  = (int) $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = (int) ceil($total_rows / $limit);
$stmt_count->close();

// --- Query utama: ambil data event beserta jumlah pendaftar ---
$sql = "SELECT e.*,
            (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registered
        FROM events e
        WHERE {$where}
        ORDER BY e.registration_close ASC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types . 'ii', ...[...$params, $limit, $offset]);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$events = $stmt->get_result();

// Batasan karakter untuk preview deskripsi di kartu event
define('DESC_PREVIEW_LENGTH', 100);
?>

<div class="container">

    <!-- ── Header Halaman ── -->
    <div class="row mt-4 mb-4">
        <div class="col text-center">
            <h1 class="fw-bold text-primary">Daftar Event BEM Fasilkom Unsika</h1>
            <p class="text-muted">
                Temukan dan daftarkan diri Anda pada event-event menarik dari BEM Fasilkom Unsika
            </p>
        </div>
    </div>

    <!-- ── Form Search & Filter ── -->
    <div class="row mb-4">
        <div class="col-md-10 mx-auto">
            <form method="GET" action="index.php" class="row g-2 align-items-center">

                <!-- Input pencarian nama event -->
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control"
                           placeholder="🔍 Cari nama event..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>

                <!-- Dropdown filter kategori -->
                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="">Semua Kategori</option>
                        <?php
                        $categories = ['Seminar', 'Workshop', 'Lomba', 'Sosial', 'Pelatihan', 'Lainnya'];
                        foreach ($categories as $cat):
                        ?>
                            <option value="<?= $cat ?>"
                                <?= ($category === $cat) ? 'selected' : '' ?>>
                                <?= $cat ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tombol filter -->
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>

                <!-- Tombol reset filter (hanya muncul jika ada filter aktif) -->
                <?php if (!empty($search) || !empty($category)): ?>
                <div class="col-md-1">
                    <a href="index.php" class="btn btn-outline-secondary w-100" title="Reset filter">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
                <?php endif; ?>

            </form>
        </div>
    </div>

    <!-- ── Daftar Event ── -->
    <div class="row">
        <?php if ($events->num_rows > 0): ?>
            <?php while ($event = $events->fetch_assoc()):

                // Hitung sisa kuota
                $registered = (int) $event['registered'];
                $remaining  = $event['quota'] - $registered;
                $is_full    = $remaining <= 0;

                // Hitung persentase kuota terisi (maks 100%)
                $percent    = ($event['quota'] > 0)
                              ? min(100, round($registered / $event['quota'] * 100))
                              : 100;

                // Persiapkan teks deskripsi (potong jika terlalu panjang)
                $desc       = $event['description'] ?? '';
                $short_desc = mb_strlen($desc) > DESC_PREVIEW_LENGTH
                              ? mb_substr($desc, 0, DESC_PREVIEW_LENGTH)
                              : $desc;
                $has_more   = mb_strlen($desc) > DESC_PREVIEW_LENGTH;
            ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm event-card">

                    <!-- Gambar event atau placeholder -->
                    <div style="position:relative;">
                        <?php if (!empty($event['documentation'])): ?>
                            <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($event['documentation']) ?>"
                                 class="card-img-top"
                                 alt="<?= htmlspecialchars($event['name']) ?>"
                                 style="height:185px; object-fit:cover;">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center bg-light"
                                 style="height:185px;">
                                <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>

                        <!-- Badge kategori di pojok gambar -->
                        <span class="badge bg-info position-absolute"
                              style="top:.6rem; left:.6rem;">
                            <?= htmlspecialchars($event['category']) ?>
                        </span>

                        <!-- Badge tipe event -->
                        <span class="badge position-absolute"
                              style="top:.6rem; right:.6rem;
                                     background:<?= $event['event_type'] === 'umum' ? '#dbeafe' : '#ede9fe' ?>;
                                     color:<?= $event['event_type'] === 'umum' ? '#1d4ed8' : '#6d28d9' ?>;">
                            <?= $event['event_type'] === 'umum' ? 'Umum' : 'Internal' ?>
                        </span>
                    </div>

                    <div class="card-body d-flex flex-column gap-2">

                        <!-- Judul event -->
                        <h5 class="card-title mb-0 fw-bold">
                            <?= htmlspecialchars($event['name']) ?>
                        </h5>

                        <!-- Deskripsi dengan tombol "Selengkapnya" -->
                        <?php if (!empty($desc)): ?>
                        <div class="event-desc-wrap">
                            <p class="event-desc-text small text-muted mb-0"
                               data-full="<?= htmlspecialchars($desc) ?>"
                               data-short="<?= htmlspecialchars($short_desc) ?>">
                                <?= htmlspecialchars($short_desc) ?><?= $has_more ? '…' : '' ?>
                            </p>
                            <?php if ($has_more): ?>
                                <button class="btn-read-more" type="button">
                                    Selengkapnya <i class="fas fa-chevron-down fa-xs ms-1"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Spacer agar tombol daftar selalu di bawah -->
                        <div class="flex-grow-1"></div>

                        <!-- Info tanggal pendaftaran -->
                        <ul class="list-unstyled small text-muted mb-0">
                            <li>
                                <i class="fas fa-calendar me-1 text-primary"></i>
                                <?= date('d M Y', strtotime($event['registration_open'])) ?> &ndash;
                                <?= date('d M Y', strtotime($event['registration_close'])) ?>
                            </li>
                            <li class="mt-1">
                                <i class="fas fa-hourglass-half me-1 text-primary"></i>
                                Sisa:
                                <span class="countdown"
                                      data-closing="<?= $event['registration_close'] ?>">
                                </span>
                            </li>
                        </ul>

                        <!-- Progress bar kuota -->
                        <div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted"><?= $registered ?>/<?= $event['quota'] ?> peserta</span>
                                <span class="fw-semibold <?= $percent >= 90 ? 'text-danger' : 'text-success' ?>">
                                    <?= $percent ?>%
                                </span>
                            </div>
                            <div class="progress" style="height:6px; border-radius:9999px;">
                                <div class="progress-bar
                                    <?= $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-success') ?>"
                                     style="width:<?= $percent ?>%; border-radius:9999px;"
                                     role="progressbar"
                                     aria-valuenow="<?= $percent ?>"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                </div>
                            </div>
                        </div>

                        <!-- Tombol daftar / penuh -->
                        <?php if ($is_full): ?>
                            <button class="btn btn-secondary w-100" disabled>
                                <i class="fas fa-times-circle me-1"></i>Kuota Penuh
                            </button>
                        <?php else: ?>
                            <a href="register.php?event_id=<?= $event['id'] ?>"
                               class="btn btn-primary w-100">
                                <i class="fas fa-edit me-1"></i>Daftar Sekarang
                            </a>
                        <?php endif; ?>

                    </div><!-- /card-body -->
                </div><!-- /card -->
            </div>
            <?php endwhile; ?>

            <!-- ── Pagination ── -->
            <?php if ($total_pages > 1): ?>
            <div class="col-12 mt-2 mb-4">
                <nav aria-label="Navigasi halaman">
                    <ul class="pagination justify-content-center flex-wrap gap-1">

                        <!-- Tombol Previous -->
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link"
                               href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">
                                &laquo;
                            </a>
                        </li>

                        <!-- Nomor halaman -->
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                <a class="page-link"
                                   href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Tombol Next -->
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link"
                               href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">
                                &raquo;
                            </a>
                        </li>

                    </ul>
                </nav>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Tampilan jika tidak ada event -->
            <div class="col-12">
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-calendar-times fa-3x mb-3 d-block opacity-50"></i>
                    <h5 class="fw-bold">Belum Ada Event Tersedia</h5>
                    <p class="mb-0">
                        <?php if (!empty($search) || !empty($category)): ?>
                            Tidak ada event yang cocok dengan pencarian Anda.
                            <a href="index.php" class="fw-bold">Tampilkan semua event</a>
                        <?php else: ?>
                            Pantau terus halaman ini untuk event terbaru!
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div><!-- /row -->

    <?php
    // Tampilkan flash message sukses dari pendaftaran (fallback non-AJAX)
    if (!empty($_SESSION['success'])):
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon              : 'success',
            title             : 'Berhasil!',
            html              : <?= json_encode(htmlspecialchars($_SESSION['success'])) ?>,
            confirmButtonColor: '#2563eb'
        });
    });
    </script>
    <?php
        unset($_SESSION['success']);
    endif;
    ?>

</div><!-- /container -->

<?php
$stmt->close();
$conn->close();
include 'includes/footer.php';
?>