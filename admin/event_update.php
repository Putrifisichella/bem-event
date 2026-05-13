<?php
// admin/event_update.php — Perbarui Event (JSON / Redirect)
   
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid. Silakan login kembali.', 'expired' => true]);
    exit;
}
$_SESSION['last_activity'] = time();

require_once '../config/database.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) header('Content-Type: application/json; charset=utf-8');

function respondUpdate(bool $ok, string $msg, bool $isAjax, int $id = 0): void
{
    if ($isAjax) { echo json_encode(['success' => $ok, 'message' => $msg]); exit; }
    $_SESSION[$ok ? 'success' : 'error'] = $msg;
    header('Location: ' . ($ok ? 'events.php' : "event_edit.php?id={$id}"));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondUpdate(false, 'Metode request tidak valid.', $isAjax);

/* ── Verifikasi CSRF ── */
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    respondUpdate(false, 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.', $isAjax);
}

/* ── Input ── */
$id                 = intval($_POST['id']               ?? 0);
$name               = trim($_POST['name']               ?? '');
$description        = trim($_POST['description']        ?? '');
$event_date         = $_POST['event_date']              ?? '';
$event_type         = $_POST['event_type']              ?? '';
$quota              = (int) ($_POST['quota']            ?? 0);
$registration_open  = $_POST['registration_open']       ?? '';
$registration_close = $_POST['registration_close']      ?? '';
$is_active          = isset($_POST['is_active'])        ? 1 : 0;
$category           = $_POST['category']                ?? '';

if (!$id) respondUpdate(false, 'ID event tidak valid.', $isAjax);

/* ── Validasi ── */
if (empty($name) || empty($event_type) || empty($category)
    || empty($event_date) || empty($registration_open) || empty($registration_close)) {
    respondUpdate(false, 'Semua field bertanda wajib (*) harus diisi.', $isAjax, $id);
}
if ($quota < 1) respondUpdate(false, 'Kuota peserta harus minimal 1.', $isAjax, $id);
if (strtotime($registration_open) > strtotime($registration_close)) {
    respondUpdate(false, 'Tanggal buka pendaftaran tidak boleh lebih dari tanggal tutup.', $isAjax, $id);
}

/* ── Ambil data lama ── */
$stmtOld = $conn->prepare('SELECT documentation FROM events WHERE id = ?');
$stmtOld->bind_param('i', $id);
$stmtOld->execute();
$old = $stmtOld->get_result()->fetch_assoc();
$stmtOld->close();

if (!$old) respondUpdate(false, 'Event tidak ditemukan.', $isAjax, $id);

$documentation = $old['documentation'];
$deleteDoc     = !empty($_POST['delete_documentation']) && (int)$_POST['delete_documentation'] === 1;
$uploadNew     = isset($_FILES['documentation']) && $_FILES['documentation']['error'] === UPLOAD_ERR_OK;

/* ── Proses gambar ── */
if ($uploadNew) {
    $targetDir   = '../uploads/';
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['documentation']['tmp_name']);
    if (!in_array($mime, $allowedMime))
        respondUpdate(false, 'Format gambar tidak didukung.', $isAjax, $id);
    if ($_FILES['documentation']['size'] > 2 * 1024 * 1024)
        respondUpdate(false, 'Ukuran gambar maksimal 2MB.', $isAjax, $id);

    $ext = strtolower(pathinfo($_FILES['documentation']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt))
        respondUpdate(false, 'Ekstensi file tidak valid.', $isAjax, $id);

    $filename = 'ev_' . uniqid('', true) . '.' . $ext;
    if (move_uploaded_file($_FILES['documentation']['tmp_name'], $targetDir . $filename)) {
        // Hapus gambar lama
        if (!empty($old['documentation']) && file_exists($targetDir . $old['documentation'])) {
            @unlink($targetDir . $old['documentation']);
        }
        $documentation = $filename;
    } else {
        respondUpdate(false, 'Gagal mengupload gambar baru.', $isAjax, $id);
    }
} elseif ($deleteDoc) {
    if (!empty($old['documentation']) && file_exists('../uploads/' . $old['documentation'])) {
        @unlink('../uploads/' . $old['documentation']);
    }
    $documentation = null;
}

/* ── Update DB ── */
$sql  = "UPDATE events
            SET name=?, description=?, event_date=?, documentation=?,
                event_type=?, category=?, quota=?,
                registration_open=?, registration_close=?, is_active=?
          WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssssssissii',
    $name, $description, $event_date, $documentation,
    $event_type, $category, $quota,
    $registration_open, $registration_close, $is_active,
    $id
);

if ($stmt->execute()) {
    $stmt->close(); $conn->close();
    respondUpdate(true, "Event \"{$name}\" berhasil diperbarui.", $isAjax, $id);
} else {
    $err = $conn->error;
    $stmt->close(); $conn->close();
    error_log("event_update error: " . $err);
    respondUpdate(false, 'Gagal memperbarui event. Silakan coba lagi.', $isAjax, $id);
}