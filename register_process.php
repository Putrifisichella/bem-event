<?php
// ============================================================
//  register_process.php
//  Memproses data pendaftaran yang dikirim dari form register.php
//  Mengembalikan JSON untuk request AJAX, atau redirect untuk non-AJAX
// ============================================================

// Mulai sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

// ============================================================
//  Deteksi apakah request berasal dari AJAX
//  Script.js mengirim header X-Requested-With saat submit form
// ============================================================
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Set header JSON untuk AJAX
if ($is_ajax) {
    header('Content-Type: application/json; charset=utf-8');
}

/**
 * Fungsi pembantu untuk mengirim respons lalu menghentikan eksekusi.
 * Untuk AJAX: mengirim JSON
 * Untuk non-AJAX: redirect dengan flash message di sesi
 */
function respond(bool $success, string $message, bool $is_ajax, int $event_id = 0): void
{
    if ($is_ajax) {
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }

    // Fallback non-AJAX
    $_SESSION[$success ? 'success' : 'error'] = $message;
    $redirect = $success
                ? 'index.php'
                : 'register.php?event_id=' . $event_id;
    header('Location: ' . $redirect);
    exit;
}

// ============================================================
//  1. Validasi metode request
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Metode request tidak valid.', $is_ajax);
}

// ============================================================
//  2. Verifikasi CSRF token
// ============================================================
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    respond(false, 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.', $is_ajax);
}

// ============================================================
//  3. Ambil dan sanitasi input dari form
// ============================================================
$event_id   = intval($_POST['event_id']    ?? 0);
$full_name  = trim($_POST['full_name']     ?? '');
$email      = trim($_POST['email']         ?? '');
$phone      = trim($_POST['phone']         ?? '');

// ============================================================
//  4. Validasi input dasar
// ============================================================
if (!$event_id) {
    respond(false, 'ID event tidak valid.', $is_ajax);
}

if (empty($full_name)) {
    respond(false, 'Nama lengkap wajib diisi.', $is_ajax, $event_id);
}

if (empty($email)) {
    respond(false, 'Alamat email wajib diisi.', $is_ajax, $event_id);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Format email tidak valid. Contoh: nama@email.com', $is_ajax, $event_id);
}

if (empty($phone)) {
    respond(false, 'Nomor telepon wajib diisi.', $is_ajax, $event_id);
}

if (!preg_match('/^[0-9]{10,13}$/', $phone)) {
    respond(false, 'Nomor telepon harus 10–13 digit angka tanpa spasi atau tanda baca.', $is_ajax, $event_id);
}

// ============================================================
//  5. Ambil data event dari database
// ============================================================
$stmt_event = $conn->prepare(
    "SELECT * FROM events
      WHERE id = ?
        AND is_active = 1
        AND registration_open  <= CURDATE()
        AND registration_close >= CURDATE()"
);
$stmt_event->bind_param('i', $event_id);
$stmt_event->execute();
$event = $stmt_event->get_result()->fetch_assoc();
$stmt_event->close();

if (!$event) {
    respond(false, 'Event tidak ditemukan atau masa pendaftaran sudah berakhir.', $is_ajax);
}

$event_type = $event['event_type'];

// ============================================================
//  6. Cek rate limiting (maks 10 pendaftaran per IP per jam)
// ============================================================
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!checkRateLimit($ip, $conn)) {
    respond(false, 'Terlalu banyak percobaan pendaftaran dari perangkat Anda. Coba lagi dalam 1 jam.', $is_ajax);
}

// ============================================================
//  7. Validasi field spesifik berdasarkan tipe event
// ============================================================
$institution = null;
$npm         = null;
$faculty     = null;

if ($event_type === 'umum') {
    // Event umum: wajib mengisi instansi/asal
    $institution = trim($_POST['institution'] ?? '');
    if (empty($institution)) {
        respond(false, 'Nama instansi/asal wajib diisi untuk event terbuka umum.', $is_ajax, $event_id);
    }
} else {
    // Event internal: wajib mengisi NPM dan fakultas
    $npm     = trim($_POST['npm']     ?? '');
    $faculty = trim($_POST['faculty'] ?? '');

    if (empty($npm)) {
        respond(false, 'NPM wajib diisi untuk event internal.', $is_ajax, $event_id);
    }
    if (!preg_match('/^[0-9]{13}$/', $npm)) {
        respond(false, 'NPM harus tepat 13 digit angka.', $is_ajax, $event_id);
    }
    if (empty($faculty)) {
        respond(false, 'Fakultas wajib diisi untuk event internal.', $is_ajax, $event_id);
    }
}

// ============================================================
//  8. Cek kuota, duplikasi, dan simpan dalam satu transaksi
//     FOR UPDATE digunakan agar tidak terjadi race condition
//     (dua pendaftar sekaligus mengisi sisa 1 kuota)
// ============================================================
$conn->begin_transaction();

try {
    // Kunci baris dan hitung pendaftar saat ini
    $stmt_count = $conn->prepare(
        'SELECT COUNT(*) AS total FROM registrations WHERE event_id = ? FOR UPDATE'
    );
    $stmt_count->bind_param('i', $event_id);
    $stmt_count->execute();
    $current_registered = (int) $stmt_count->get_result()->fetch_assoc()['total'];
    $stmt_count->close();

    // Cek apakah kuota masih tersedia
    if ($current_registered >= (int) $event['quota']) {
        $conn->rollback();
        respond(false, 'Maaf, kuota pendaftaran untuk event ini baru saja habis.', $is_ajax);
    }

    // Cek duplikasi pendaftaran
    if ($event_type === 'internal') {
        // Cek berdasarkan NPM untuk event internal
        $stmt_dup = $conn->prepare(
            'SELECT id FROM registrations WHERE event_id = ? AND npm = ? LIMIT 1'
        );
        $stmt_dup->bind_param('is', $event_id, $npm);
    } else {
        // Cek berdasarkan email untuk event umum
        $stmt_dup = $conn->prepare(
            'SELECT id FROM registrations WHERE event_id = ? AND email = ? LIMIT 1'
        );
        $stmt_dup->bind_param('is', $event_id, $email);
    }

    $stmt_dup->execute();
    $stmt_dup->store_result();

    if ($stmt_dup->num_rows > 0) {
        $stmt_dup->close();
        $conn->rollback();
        respond(false, 'Anda sudah terdaftar pada event ini sebelumnya.', $is_ajax);
    }
    $stmt_dup->close();

    // Simpan data pendaftaran
    $stmt_insert = $conn->prepare(
        "INSERT INTO registrations
            (event_id, full_name, email, institution, npm, faculty, phone, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt_insert->bind_param(
        'isssssss',
        $event_id, $full_name, $email,
        $institution, $npm, $faculty,
        $phone, $ip
    );

    if (!$stmt_insert->execute()) {
        throw new RuntimeException('Gagal menyimpan data: ' . $stmt_insert->error);
    }
    $stmt_insert->close();

    // Commit transaksi
    $conn->commit();

    // Kirim email konfirmasi (kegagalan email tidak membatalkan pendaftaran)
    sendRegistrationEmail($email, $full_name, $event['name'], $event);

    respond(
        true,
        "Pendaftaran berhasil! Email konfirmasi telah dikirim ke <strong>{$email}</strong>.",
        $is_ajax
    );

} catch (RuntimeException $e) {
    // Batalkan semua perubahan jika terjadi error
    $conn->rollback();
    error_log('register_process error: ' . $e->getMessage());
    respond(false, 'Terjadi kesalahan sistem. Silakan coba lagi.', $is_ajax, $event_id);
}