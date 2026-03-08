<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: login.php');
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

if (empty($username) || empty($password)) {
    $_SESSION['error'] = "Username dan password harus diisi.";
    header('Location: login.php');
    exit;
}

$sql = "SELECT id, username, password FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    // Verifikasi password dengan hash
    if (password_verify($password, $user['password'])) {
        // Regenerasi session ID sebelum menyimpan data
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $_SESSION['error'] = "Password salah.";
        header('Location: login.php');
        exit;
    }
} else {
    $_SESSION['error'] = "Username tidak ditemukan.";
    header('Location: login.php');
    exit;
}

$stmt->close();
$conn->close();
?>