<?php
//  admin/event_delete.php — Hapus Event (JSON / Redirect)

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Tidak terotorisasi.']);
    exit;
}
$_SESSION['last_activity'] = time();

require_once '../config/database.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) header('Content-Type: application/json; charset=utf-8');

function respondDelete(bool $ok, string $msg, bool $isAjax): void
{
    if ($isAjax) { echo json_encode(['success' => $ok, 'message' => $msg]); exit; }
    $_SESSION[$ok ? 'success' : 'error'] = $msg;
    header('Location: events.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondDelete(false, 'Metode request tidak valid.', $isAjax);

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    respondDelete(false, 'Token keamanan tidak valid. Silakan muat ulang halaman.', $isAjax);
}

$id = intval($_POST['id'] ?? 0);
if (!$id) respondDelete(false, 'ID event tidak valid.', $isAjax);

/* ── Ambil nama event ── */
$stmtName = $conn->prepare('SELECT name, documentation FROM events WHERE id = ?');
$stmtName->bind_param('i', $id);
$stmtName->execute();
$eventRow = $stmtName->get_result()->fetch_assoc();
$stmtName->close();

if (!$eventRow) respondDelete(false, 'Event tidak ditemukan.', $isAjax);

/* ── Hapus event (registrations ikut terhapus via ON DELETE CASCADE) ── */
$stmt = $conn->prepare('DELETE FROM events WHERE id = ?');
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    // Hapus file gambar jika ada
    if (!empty($eventRow['documentation'])) {
        $filePath = '../uploads/' . $eventRow['documentation'];
        if (file_exists($filePath)) @unlink($filePath);
    }
    $stmt->close(); $conn->close();
    respondDelete(true, "Event \"{$eventRow['name']}\" berhasil dihapus beserta seluruh data peserta.", $isAjax);
} else {
    $err = $conn->error;
    $stmt->close(); $conn->close();
    error_log("event_delete error: " . $err);
    respondDelete(false, 'Gagal menghapus event. Silakan coba lagi.', $isAjax);
}