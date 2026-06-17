<?php
// ============================================================
//  admin/toggle_event.php
//  Mengubah status aktif/nonaktif sebuah event
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek autentikasi
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Tidak terotorisasi.']);
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
function respondToggle(bool $ok, string $msg, bool $is_ajax): void
{
    if ($is_ajax) {
        echo json_encode(['success' => $ok, 'message' => $msg]);
        exit;
    }
    $_SESSION[$ok ? 'success' : 'error'] = $msg;
    header('Location: events.php');
    exit;
}

// Hanya terima POST (mencegah pemanggilan via link/gambar tersembunyi)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondToggle(false, 'Metode request tidak valid.', $is_ajax);
}

// Verifikasi CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    respondToggle(false, 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.', $is_ajax);
}

// Ambil dan validasi ID dari body POST
$id = intval($_POST['id'] ?? 0);
if (!$id) {
    respondToggle(false, 'ID event tidak valid.', $is_ajax);
}

// Ambil status aktif saat ini
$stmt_get = $conn->prepare('SELECT is_active, name FROM events WHERE id = ?');
$stmt_get->bind_param('i', $id);
$stmt_get->execute();
$event = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$event) {
    respondToggle(false, 'Event tidak ditemukan.', $is_ajax);
}

// Balik status: jika aktif (1) jadi nonaktif (0), dan sebaliknya
$new_status = $event['is_active'] ? 0 : 1;
$keterangan = $new_status ? 'diaktifkan' : 'dinonaktifkan';

// Update status di database
$stmt_upd = $conn->prepare('UPDATE events SET is_active = ? WHERE id = ?');
$stmt_upd->bind_param('ii', $new_status, $id);

if ($stmt_upd->execute()) {
    $stmt_upd->close();
    $conn->close();
    respondToggle(true, "Event \"{$event['name']}\" berhasil {$keterangan}.", $is_ajax);
} else {
    $err = $conn->error;
    $stmt_upd->close();
    $conn->close();
    error_log("toggle_event error: " . $err);
    respondToggle(false, 'Gagal mengubah status event. Silakan coba lagi.', $is_ajax);
}