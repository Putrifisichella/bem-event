<?php
// ============================================================
//  admin/export_csv.php
//  Mengekspor data peserta suatu event ke file CSV
//  File CSV bisa langsung dibuka di Microsoft Excel
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek autentikasi
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
$_SESSION['last_activity'] = time();

require_once '../config/database.php';

// Validasi event_id
$event_id = intval($_GET['event_id'] ?? 0);
if ($event_id <= 0) {
    header('Location: events.php');
    exit;
}

// Ambil data event
$stmt_ev = $conn->prepare('SELECT * FROM events WHERE id = ?');
$stmt_ev->bind_param('i', $event_id);
$stmt_ev->execute();
$event_result = $stmt_ev->get_result();

if ($event_result->num_rows === 0) {
    $_SESSION['error'] = 'Event tidak ditemukan.';
    header('Location: events.php');
    exit;
}
$event = $event_result->fetch_assoc();
$stmt_ev->close();

// Ambil semua data peserta
$stmt_p = $conn->prepare(
    'SELECT * FROM registrations WHERE event_id = ? ORDER BY registered_at ASC'
);
$stmt_p->bind_param('i', $event_id);
$stmt_p->execute();
$participants = $stmt_p->get_result();

// ── Buat nama file CSV yang aman (tanpa karakter spesial) ──
$safe_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $event['name']);
$safe_name = mb_substr($safe_name, 0, 50);
$filename  = 'peserta_' . $safe_name . '_' . date('Ymd') . '.csv';

// ── Set header HTTP agar browser langsung mengunduh file ──
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Buka output buffer sebagai stream CSV
$output = fopen('php://output', 'w');

// BOM UTF-8: agar karakter huruf Indonesia terbaca benar di Excel
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// ── Baris informasi event ──
fputcsv($output, ['DAFTAR PESERTA EVENT']);
fputcsv($output, ['Nama Event',     $event['name']]);
fputcsv($output, ['Kategori',       $event['category'] ?? '-']);
fputcsv($output, ['Tipe',           ucfirst($event['event_type'])]);
fputcsv($output, ['Kuota',          $event['quota']]);
fputcsv($output, ['Total Peserta',  $participants->num_rows]);
fputcsv($output, ['Tanggal Export', date('d/m/Y H:i:s')]);
fputcsv($output, []); // Baris kosong pemisah

// ── Header kolom tabel ──
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

// ── Data peserta baris per baris ──
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
$stmt_p->close();
$conn->close();
exit;