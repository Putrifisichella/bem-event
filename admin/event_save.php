<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: events.php');
    exit;
}

$name = trim($_POST['name']);
$description = trim($_POST['description']);
$event_type = $_POST['event_type'];
$quota = intval($_POST['quota']);
$registration_open = $_POST['registration_open'];
$registration_close = $_POST['registration_close'];
$is_active = isset($_POST['is_active']) ? 1 : 0;
$category = $_POST['category'];
$price = floatval($_POST['price']);

// Validasi
if (empty($name) || empty($event_type) || empty($quota) || empty($registration_open) || empty($registration_close)) {
    $_SESSION['error'] = "Semua field wajib diisi.";
    header('Location: event_add.php');
    exit;
}

if ($quota < 1) {
    $_SESSION['error'] = "Kuota harus minimal 1.";
    header('Location: event_add.php');
    exit;
}

if (strtotime($registration_open) > strtotime($registration_close)) {
    $_SESSION['error'] = "Tanggal buka tidak boleh lebih dari tanggal tutup.";
    header('Location: event_add.php');
    exit;
}

// Proses upload file dokumentasi
$documentation = null;
if (isset($_FILES['documentation']) && $_FILES['documentation']['error'] == 0) {
    $target_dir = "../uploads/";
    // Buat folder jika belum ada
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $file_extension = pathinfo($_FILES['documentation']['name'], PATHINFO_EXTENSION);
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array(strtolower($file_extension), $allowed_ext)) {
        $_SESSION['error'] = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.";
        header('Location: event_add.php');
        exit;
    }
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    if (move_uploaded_file($_FILES['documentation']['tmp_name'], $target_file)) {
        $documentation = $new_filename;
    } else {
        $_SESSION['error'] = "Gagal mengupload gambar.";
        header('Location: event_add.php');
        exit;
    }
}

// Query INSERT dengan kolom documentation
$sql = "INSERT INTO events (name, description, documentation, event_type, category, quota, price, registration_open, registration_close, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssidssi", $name, $description, $documentation, $event_type, $category, $quota, $price, $registration_open, $registration_close, $is_active);

if ($stmt->execute()) {
    $_SESSION['success'] = "Event berhasil ditambahkan.";
    header('Location: events.php');
} else {
    $_SESSION['error'] = "Gagal menambahkan event: " . $conn->error;
    header('Location: event_add.php');
}

$stmt->close();
$conn->close();
?>