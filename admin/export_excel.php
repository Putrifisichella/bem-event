<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
if ($event_id == 0) {
    die('ID event tidak valid.');
}

// Ambil data event
$sql_event = "SELECT * FROM events WHERE id = ?";
$stmt_event = $conn->prepare($sql_event);
$stmt_event->bind_param("i", $event_id);
$stmt_event->execute();
$event_result = $stmt_event->get_result();

if ($event_result->num_rows == 0) {
    die('Event tidak ditemukan.');
}
$event = $event_result->fetch_assoc();

// Ambil data peserta
$sql_participants = "SELECT * FROM registrations WHERE event_id = ? ORDER BY registered_at ASC";
$stmt_participants = $conn->prepare($sql_participants);
$stmt_participants->bind_param("i", $event_id);
$stmt_participants->execute();
$participants = $stmt_participants->get_result();

// Set header untuk download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="peserta_' . $event['name'] . '_' . date('Ymd') . '.csv"');

// Buat output buffer
$output = fopen('php://output', 'w');

// Tulis BOM untuk UTF-8 (agar karakter khusus terbaca di Excel)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header kolom
$header = ['No', 'Nama Lengkap'];
if ($event['event_type'] == 'umum') {
    $header[] = 'Instansi';
} else {
    $header[] = 'NPM';
    $header[] = 'Fakultas';
}
$header[] = 'Nomor Telepon';
$header[] = 'Waktu Daftar';
fputcsv($output, $header);

// Tulis data
$no = 1;
while ($participant = $participants->fetch_assoc()) {
    $row = [
        $no++,
        $participant['full_name']
    ];
    
    if ($event['event_type'] == 'umum') {
        $row[] = $participant['institution'];
    } else {
        $row[] = $participant['npm'];
        $row[] = $participant['faculty'];
    }
    
    $row[] = $participant['phone'];
    $row[] = date('d/m/Y H:i', strtotime($participant['registered_at']));
    
    fputcsv($output, $row);
}

fclose($output);
$stmt_event->close();
$stmt_participants->close();
$conn->close();
exit;
?>