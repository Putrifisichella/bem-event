<?php
// ============================================================
//  admin/event_update.php
//  Memproses data dari form edit event (event_edit.php)
//  Mengembalikan JSON untuk AJAX, atau redirect untuk non-AJAX
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek autentikasi
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid.', 'expired' => true]);
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
function respondUpdate(bool $ok, string $msg, bool $is_ajax, int $id = 0): void
{
    if ($is_ajax) {
        echo json_encode(['success' => $ok, 'message' => $msg]);
        exit;
    }
    $_SESSION[$ok ? 'success' : 'error'] = $msg;
    header('Location: ' . ($ok ? 'events.php' : "event_edit.php?id={$id}"));
    exit;
}

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondUpdate(false, 'Metode request tidak valid.', $is_ajax);
}

// Verifikasi CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    respondUpdate(false, 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.', $is_ajax);
}

// ── Ambil input ──
$id                 = intval($_POST['id']               ?? 0);
$name               = trim($_POST['name']               ?? '');
$description        = trim($_POST['description']        ?? '');
$event_date         = trim($_POST['event_date']         ?? '');
$event_type         = trim($_POST['event_type']         ?? '');
$quota              = (int) ($_POST['quota']            ?? 0);
$registration_open  = trim($_POST['registration_open']  ?? '');
$registration_close = trim($_POST['registration_close'] ?? '');
$is_active          = isset($_POST['is_active'])        ? 1 : 0;
$category           = trim($_POST['category']           ?? '');
$delete_doc         = (int) ($_POST['delete_documentation'] ?? 0);

if (!$id) {
    respondUpdate(false, 'ID event tidak valid.', $is_ajax);
}

// ── Validasi field wajib ──
if (empty($name) || empty($event_type) || empty($category)
    || empty($event_date) || empty($registration_open) || empty($registration_close)) {
    respondUpdate(false, 'Semua field bertanda bintang (*) wajib diisi.', $is_ajax, $id);
}

if ($quota < 1) {
    respondUpdate(false, 'Kuota peserta minimal 1 orang.', $is_ajax, $id);
}

if (strtotime($registration_open) > strtotime($registration_close)) {
    respondUpdate(false, 'Tanggal pembukaan tidak boleh setelah tanggal penutupan.', $is_ajax, $id);
}

// ── Ambil data lama untuk keperluan file gambar ──
$stmt_old = $conn->prepare('SELECT documentation FROM events WHERE id = ?');
$stmt_old->bind_param('i', $id);
$stmt_old->execute();
$old_data = $stmt_old->get_result()->fetch_assoc();
$stmt_old->close();

if (!$old_data) {
    respondUpdate(false, 'Event tidak ditemukan.', $is_ajax, $id);
}

$documentation  = $old_data['documentation']; // Default: tetap pakai gambar lama
$upload_dir     = '../uploads/';

// ── Proses gambar: upload baru ──
if (isset($_FILES['documentation']) && $_FILES['documentation']['error'] === UPLOAD_ERR_OK) {

    $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowed_ext  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['documentation']['tmp_name']);

    if (!in_array($mime, $allowed_mime)) {
        respondUpdate(false, 'Format gambar tidak didukung.', $is_ajax, $id);
    }
    if ($_FILES['documentation']['size'] > 2 * 1024 * 1024) {
        respondUpdate(false, 'Ukuran gambar maksimal 2MB.', $is_ajax, $id);
    }

    $ext = strtolower(pathinfo($_FILES['documentation']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext)) {
        respondUpdate(false, 'Ekstensi file tidak valid.', $is_ajax, $id);
    }

    $filename = 'ev_' . uniqid('', true) . '.' . $ext;

    if (move_uploaded_file($_FILES['documentation']['tmp_name'], $upload_dir . $filename)) {
        // Hapus file gambar lama jika ada
        if (!empty($old_data['documentation'])
            && file_exists($upload_dir . $old_data['documentation'])) {
            @unlink($upload_dir . $old_data['documentation']);
        }
        $documentation = $filename;
    } else {
        respondUpdate(false, 'Gagal mengupload gambar baru.', $is_ajax, $id);
    }

} elseif ($delete_doc === 1) {
    // ── Proses gambar: hapus tanpa ganti ──
    if (!empty($old_data['documentation'])
        && file_exists($upload_dir . $old_data['documentation'])) {
        @unlink($upload_dir . $old_data['documentation']);
    }
    $documentation = null;
}

// ── Update data di database ──
$sql  = "UPDATE events
            SET name = ?, description = ?, event_date = ?, documentation = ?,
                event_type = ?, category = ?, quota = ?,
                registration_open = ?, registration_close = ?, is_active = ?
          WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'ssssssissii',
    $name, $description, $event_date, $documentation,
    $event_type, $category, $quota,
    $registration_open, $registration_close, $is_active,
    $id
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    respondUpdate(true, "Event \"{$name}\" berhasil diperbarui.", $is_ajax, $id);
} else {
    $err = $conn->error;
    $stmt->close();
    $conn->close();
    error_log("event_update error: " . $err);
    respondUpdate(false, 'Gagal memperbarui event. Silakan coba lagi.', $is_ajax, $id);
}