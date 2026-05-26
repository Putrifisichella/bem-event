<?php
// ============================================================
//  admin/event_delete.php
//  Menghapus event beserta seluruh data pesertanya
//  (ON DELETE CASCADE di database menangani relasi)
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
function respondDelete(bool $ok, string $msg, bool $is_ajax): void
{
    if ($is_ajax) {
        echo json_encode(['success' => $ok, 'message' => $msg]);
        exit;
    }
    $_SESSION[$ok ? 'success' : 'error'] = $msg;
    header('Location: events.php');
    exit;
}

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondDelete(false, 'Metode request tidak valid.', $is_ajax);
}

// Verifikasi CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    respondDelete(false, 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.', $is_ajax);
}

// Validasi ID
$id = intval($_POST['id'] ?? 0);
if (!$id) {
    respondDelete(false, 'ID event tidak valid.', $is_ajax);
}

// Ambil data event sebelum dihapus (untuk pesan konfirmasi & hapus file)
$stmt_get = $conn->prepare('SELECT name, documentation FROM events WHERE id = ?');
$stmt_get->bind_param('i', $id);
$stmt_get->execute();
$event = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$event) {
    respondDelete(false, 'Event tidak ditemukan.', $is_ajax);
}

// Hapus event dari database
// (data registrations terhapus otomatis karena ON DELETE CASCADE)
$stmt = $conn->prepare('DELETE FROM events WHERE id = ?');
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    // Hapus file gambar jika ada
    if (!empty($event['documentation'])) {
        $file_path = '../uploads/' . $event['documentation'];
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
    }
    $stmt->close();
    $conn->close();
    respondDelete(
        true,
        "Event \"{$event['name']}\" dan seluruh data pesertanya berhasil dihapus.",
        $is_ajax
    );
} else {
    $err = $conn->error;
    $stmt->close();
    $conn->close();
    error_log("event_delete error: " . $err);
    respondDelete(false, 'Gagal menghapus event. Silakan coba lagi.', $is_ajax);
}