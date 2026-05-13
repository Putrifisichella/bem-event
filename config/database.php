<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'bem_event';

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    // Eror tercatat ke log server, bukan ke pengguna
    error_log("Koneksi gagal: " . $conn->connect_error);
    http_response_code(503);
    die("Layanan sementara tidak tersedia. Silakan coba beberapa saat lagi.");
}

// ── BASE_URL: deteksi otomatis ──────────────────────────
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $protocol . '://' . $httpHost . '/bem-event/');
}

// Load helper functions
require_once __DIR__ . '/../includes/functions.php';
?>