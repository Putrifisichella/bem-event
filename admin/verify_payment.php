<?php
include 'includes/auth.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id == 0) {
    $_SESSION['error'] = "ID tidak valid.";
    header('Location: events.php');
    exit;
}

$sql = "UPDATE registrations SET payment_status = 'verified' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION['success'] = "Pembayaran diverifikasi.";
} else {
    $_SESSION['error'] = "Gagal verifikasi.";
}

$stmt->close();
$conn->close();
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?>