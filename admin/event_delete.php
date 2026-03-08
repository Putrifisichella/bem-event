<?php
session_start();
require_once '../config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id == 0) {
    $_SESSION['error'] = "ID event tidak valid.";
    header('Location: events.php');
    exit;
}

// Hapus event (registrasi akan terhapus otomatis karena ON DELETE CASCADE)
$sql = "DELETE FROM events WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Event berhasil dihapus.";
} else {
    $_SESSION['error'] = "Gagal menghapus event: " . $conn->error;
}

$stmt->close();
$conn->close();
header('Location: events.php');
exit;
?>