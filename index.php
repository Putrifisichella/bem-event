<?php
$page_title = 'Daftar Event BEM Fasilkom Unsika';
include 'includes/header.php';
require_once 'config/database.php';

$today = date('Y-m-d');

// ===== PAGINATION =====
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6; // jumlah event per halaman
$offset = ($page - 1) * $limit;

// ===== SEARCH & FILTER =====
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// ===== QUERY UTAMA DENGAN SUBQUERY UNTUK JUMLAH PENDAFTAR =====
$sql = "SELECT e.*, 
        (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) as registered 
        FROM events e 
        WHERE e.registration_open <= ? AND e.registration_close >= ?";

$params = [$today, $today];
$types = "ss";

if (!empty($search)) {
    $sql .= " AND (e.name LIKE ? OR e.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($category)) {
    $sql .= " AND e.category = ?";
    $params[] = $category;
    $types .= "s";
}

$sql .= " ORDER BY e.registration_close ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// ===== HITUNG TOTAL DATA UNTUK PAGINATION =====
$count_sql = "SELECT COUNT(*) as total FROM events e WHERE e.registration_open <= ? AND e.registration_close >= ?";
$count_params = [$today, $today];
$count_types = "ss";

if (!empty($search)) {
    $count_sql .= " AND (e.name LIKE ? OR e.description LIKE ?)";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= "ss";
}
if (!empty($category)) {
    $count_sql .= " AND e.category = ?";
    $count_params[] = $category;
    $count_types .= "s";
}

$stmt_count = $conn->prepare($count_sql);
$stmt_count->bind_param($count_types, ...$count_params);
$stmt_count->execute();
$total_result = $stmt_count->get_result();
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
?>

<div class="container">
    <div class="row mb-4">
        <div class="col text-center">
            <h1 class="display-4">Selamat Datang di Sistem Pendaftaran Event</h1>
            <p class="lead">BEM Fakultas Ilmu Komputer Universitas Singaperbangsa Karawang</p>
        </div>
    </div>

    <!-- Form Pencarian dan Filter -->
    <div class="row mb-4">
        <div class="col-md-8 mx-auto">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Cari event..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="">Semua Kategori</option>
                        <option value="Seminar" <?php echo $category=='Seminar'?'selected':''; ?>>Seminar</option>
                        <option value="Workshop" <?php echo $category=='Workshop'?'selected':''; ?>>Workshop</option>
                        <option value="Lomba" <?php echo $category=='Lomba'?'selected':''; ?>>Lomba</option>
                        <option value="Sosial" <?php echo $category=='Sosial'?'selected':''; ?>>Sosial</option>
                        <option value="Pelatihan" <?php echo $category=='Pelatihan'?'selected':''; ?>>Pelatihan</option>
                        <option value="Lainnya" <?php echo $category=='Lainnya'?'selected':''; ?>>Lainnya</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($event = $result->fetch_assoc()): 
                $registered = $event['registered']; // dari subquery
                $remaining = $event['quota'] - $registered;
                $is_full = ($remaining <= 0);
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card event-card h-100">
                        <?php if (!empty($event['documentation'])): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/<?php echo $event['documentation']; ?>" 
                                 class="card-img-top" alt="Dokumentasi" style="height: 180px; object-fit: cover;">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($event['name']); ?></h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($event['description'], 0, 100))) . '...'; ?></p>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="fas fa-tag me-1"></i> Kategori: <?php echo htmlspecialchars($event['category']); ?><br>
                                    <i class="fas fa-money-bill me-1"></i> Biaya: <?php echo $event['price'] > 0 ? 'Rp ' . number_format($event['price'], 0, ',', '.') : 'Gratis'; ?><br>
                                    <i class="fas fa-users me-1"></i> Kuota: <?php echo $event['quota']; ?> peserta<br>
                                    <i class="fas fa-user-check me-1"></i> Terdaftar: <?php echo $registered; ?><br>
                                    <i class="fas fa-calendar me-1"></i> Pendaftaran: <?php echo date('d/m/Y', strtotime($event['registration_open'])); ?> - <?php echo date('d/m/Y', strtotime($event['registration_close'])); ?><br>
                                    <i class="fas fa-hourglass-half me-1"></i> Sisa waktu: <span class="countdown" data-closing="<?php echo $event['registration_close']; ?>"></span>
                                </small>
                            </p>
                            <?php if ($is_full): ?>
                                <button class="btn btn-secondary w-100" disabled><i class="fas fa-times-circle me-2"></i>Kuota Penuh</button>
                            <?php else: ?>
                                <a href="register.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary w-100">Daftar Sekarang</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <!-- Tampilkan Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="col-12 mt-4">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="col">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i> Belum ada event yang tersedia saat ini.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$stmt->close();
$stmt_count->close();
$conn->close();
include 'includes/footer.php';
?>