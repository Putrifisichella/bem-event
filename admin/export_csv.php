<?php
//  admin/export_csv.php — Export Data Peserta ke File CSV
   
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
$_SESSION['last_activity'] = time();

require_once '../config/database.php';

$event_id = intval($_GET['event_id'] ?? 0);
if ($event_id <= 0) {
    header('Location: events.php');
    exit;
}

/* ── Ambil data event ── */
$stmtEv = $conn->prepare('SELECT * FROM events WHERE id = ?');
$stmtEv->bind_param('i', $event_id);
$stmtEv->execute();
$eventResult = $stmtEv->get_result();

if ($eventResult->num_rows === 0) {
    $_SESSION['error'] = 'Event tidak ditemukan.';
    header('Location: events.php');
    exit;
}
$event = $eventResult->fetch_assoc();
$stmtEv->close();

/* ── Ambil data peserta ── */
$stmtP = $conn->prepare(
    'SELECT * FROM registrations WHERE event_id = ? ORDER BY registered_at ASC'
);
$stmtP->bind_param('i', $event_id);
$stmtP->execute();
$participants = $stmtP->get_result();

/* ── Nama file CSV yang aman ── */
$safeEventName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $event['name']);
$safeEventName = mb_substr($safeEventName, 0, 50);
$filename      = 'peserta_' . $safeEventName . '_' . date('Ymd') . '.csv';

/* ── Set header HTTP untuk download ── */
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

/* ── Output ke browser ── */
$output = fopen('php://output', 'w');

// BOM UTF-8 agar karakter khusus (huruf Indonesia) terbaca benar di Excel
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

/* ── Baris Judul (metadata) ── */
fputcsv($output, ['DAFTAR PESERTA EVENT']);
fputcsv($output, ['Nama Event', $event['name']]);
fputcsv($output, ['Kategori',   $event['category'] ?? '-']);
fputcsv($output, ['Tipe',       ucfirst($event['event_type'])]);
fputcsv($output, ['Kuota',      $event['quota']]);
fputcsv($output, ['Total Peserta', $participants->num_rows]);
fputcsv($output, ['Tanggal Export', date('d/m/Y H:i:s')]);
fputcsv($output, []); // Baris kosong pemisah

/* ── Header kolom ── */
$header = ['No', 'Nama Lengkap', 'Email'];

if ($event['event_type'] === 'umum') {
    $header[] = 'Instansi';
} else {
    $header[] = 'NPM';
    $header[] = 'Fakultas';
}

$header[] = 'Nomor Telepon';
$header[] = 'Waktu Pendaftaran';

fputcsv($output, $header);

/* ── Data peserta ── */
$no = 1;
while ($p = $participants->fetch_assoc()) {
    $row = [
        $no++,
        $p['full_name'],
        $p['email'],
    ];

    if ($event['event_type'] === 'umum') {
        $row[] = $p['institution'] ?? '-';
    } else {
        $row[] = $p['npm']     ?? '-';
        $row[] = $p['faculty'] ?? '-';
    }

    $row[] = $p['phone'];
    $row[] = date('d/m/Y H:i:s', strtotime($p['registered_at']));

    fputcsv($output, $row);
}

fclose($output);
$stmtP->close();
$conn->close();
exit;