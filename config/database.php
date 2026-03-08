<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'bem_event';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

define('BASE_URL', 'http://localhost/bem-event/');

// Sertakan file functions
require_once __DIR__ . '/../includes/functions.php';
?>