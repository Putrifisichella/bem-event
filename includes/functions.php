<?php
// includes/functions.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate CSRF token dan simpan di session
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi CSRF token
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Kirim email notifikasi pendaftaran
 */
function sendRegistrationEmail($email, $name, $event_name) {
    $subject = "Konfirmasi Pendaftaran Event BEM Fasilkom Unsika";
    $message = "Halo $name,\n\n";
    $message .= "Terima kasih telah mendaftar pada event \"$event_name\".\n";
    $message .= "Pendaftaran Anda sedang diproses. Silakan cek status secara berkala.\n\n";
    $message .= "Salam,\nBEM Fasilkom Unsika";
    $headers = "From: no-reply@bemfasilkom.unsika.ac.id\r\n";
    // Gunakan mail() atau PHPMailer
    return mail($email, $subject, $message, $headers);
}

/**
 * Rate limiting: maksimal 3 pendaftaran per IP dalam 1 jam
 */
function checkRateLimit($ip, $conn) {
    $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
    $sql = "SELECT COUNT(*) as total FROM registrations WHERE registered_at >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $oneHourAgo);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['total'];
    return $count < 3; // izinkan jika kurang dari 3
}
?>