<?php
// admin/toggle_event.php — Toggle Status Aktif Event (JSON)
   
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

function respondToggle(bool $ok, string $msg, bool $isAjax): void
{
    if ($isAjax) { echo json_encode(['success' => $ok, 'message' => $msg]); exit; }
    $_SESSION[$ok ? 'success' : 'error'] = $msg;
    header('Location: events.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
if (!$id) respondToggle(false, 'ID event tidak valid.', $isAjax);

/* ── Ambil status saat ini ── */
$stmtSel = $conn->prepare('SELECT is_active, name FROM events WHERE id = ?');
$stmtSel->bind_param('i', $id);
$stmtSel->execute();
$row = $stmtSel->get_result()->fetch_assoc();
$stmtSel->close();

if (!$row) respondToggle(false, 'Event tidak ditemukan.', $isAjax);

$newStatus = $row['is_active'] ? 0 : 1;
$label     = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

/* ── Update status ── */
$stmtUpd = $conn->prepare('UPDATE events SET is_active = ? WHERE id = ?');
$stmtUpd->bind_param('ii', $newStatus, $id);

if ($stmtUpd->execute()) {
    $stmtUpd->close(); $conn->close();
    respondToggle(true, "Event \"{$row['name']}\" berhasil {$label}.", $isAjax);
} else {
    $err = $conn->error;
    $stmtUpd->close(); $conn->close();
    respondToggle(false, 'Gagal mengubah status event. Silakan coba lagi.', $isAjax);
}