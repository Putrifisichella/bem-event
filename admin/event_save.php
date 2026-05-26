<?php
// ============================================================
//  admin/event_save.php
//  Memproses data dari form tambah event (event_add.php)
//  Mengembalikan JSON untuk AJAX, atau redirect untuk non-AJAX
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek autentikasi
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid. Silakan login kembali.', 'expired' => true]);
    exit;
}
$_SESSION['last_activity'] = time();

require_once '../config/database.php';

// Deteksi AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($is_ajax) {
    header('Content-Type: application/json; charset=utf-8');
}

/**
 * Kirim respons dan hentikan eksekusi
 */
function respondSave(bool $ok, string $msg, bool $is_ajax): void
{
    if ($is_ajax) {
        echo json_encode(['success' => $ok, 'message' => $msg]);
        exit;
    }
    $_SESSION[$ok ? 'success' : 'error'] = $msg;
    header('Location: ' . ($ok ? 'events.php' : 'event_add.php'));
    exit;
}

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondSave(false, 'Metode request tidak valid.', $is_ajax);
}

// Verifikasi CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    respondSave(false, 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.', $is_ajax);
}

// ── Ambil input ──
$name               = trim($_POST['name']               ?? '');
$description        = trim($_POST['description']        ?? '');
$event_date         = trim($_POST['event_date']         ?? '');
$event_type         = trim($_POST['event_type']         ?? '');
$quota              = (int) ($_POST['quota']            ?? 0);
$registration_open  = trim($_POST['registration_open']  ?? '');
$registration_close = trim($_POST['registration_close'] ?? '');
$is_active          = isset($_POST['is_active'])        ? 1 : 0;
$category           = trim($_POST['category']           ?? '');

// ── Validasi field wajib ──
if (empty($name) || empty($event_type) || empty($category)
    || empty($event_date) || empty($registration_open) || empty($registration_close)) {
    respondSave(false, 'Semua field bertanda bintang (*) wajib diisi.', $is_ajax);
}

if ($quota < 1) {
    respondSave(false, 'Kuota peserta minimal 1 orang.', $is_ajax);
}

if (strtotime($registration_open) > strtotime($registration_close)) {
    respondSave(false, 'Tanggal pembukaan pendaftaran tidak boleh setelah tanggal penutupan.', $is_ajax);
}

// ── Proses upload gambar ──
$documentation = null;

if (isset($_FILES['documentation']) && $_FILES['documentation']['error'] === UPLOAD_ERR_OK) {

    $upload_dir  = '../uploads/';
    $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowed_ext  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // Buat folder uploads jika belum ada
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Validasi tipe MIME file (lebih aman daripada cek ekstensi saja)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['documentation']['tmp_name']);

    if (!in_array($mime, $allowed_mime)) {
        respondSave(false, 'Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.', $is_ajax);
    }

    // Validasi ukuran file (maks 2MB)
    if ($_FILES['documentation']['size'] > 2 * 1024 * 1024) {
        respondSave(false, 'Ukuran gambar terlalu besar. Maksimal 2MB.', $is_ajax);
    }

    // Validasi ekstensi
    $ext = strtolower(pathinfo($_FILES['documentation']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext)) {
        respondSave(false, 'Ekstensi file tidak valid.', $is_ajax);
    }

    // Buat nama file unik agar tidak bertabrakan
    $filename = 'ev_' . uniqid('', true) . '.' . $ext;

    if (!move_uploaded_file($_FILES['documentation']['tmp_name'], $upload_dir . $filename)) {
        respondSave(false, 'Gagal mengupload gambar. Periksa izin folder uploads/.', $is_ajax);
    }

    $documentation = $filename;
}

// ── Simpan ke database ──
$sql  = "INSERT INTO events
            (name, description, event_date, documentation, event_type,
             category, quota, registration_open, registration_close, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'ssssssissi',
    $name, $description, $event_date, $documentation,
    $event_type, $category, $quota,
    $registration_open, $registration_close, $is_active
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    respondSave(true, "Event \"{$name}\" berhasil ditambahkan.", $is_ajax);
} else {
    $err = $conn->error;
    $stmt->close();
    $conn->close();
    error_log("event_save error: " . $err);
    respondSave(false, 'Gagal menyimpan event. Silakan coba lagi.', $is_ajax);
}