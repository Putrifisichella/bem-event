<?php
// ============================================================
//  admin/login_process.php
//  Memproses data login dari form login.php
//  Menggunakan password_verify() untuk membandingkan hash bcrypt
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Hanya terima metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Ambil input dari form
$username = trim($_POST['username'] ?? '');
$password = $_POST['password']      ?? '';

// Validasi input tidak kosong
if (empty($username) || empty($password)) {
    $_SESSION['error'] = 'Username dan password wajib diisi.';
    header('Location: login.php');
    exit;
}

// Cari user berdasarkan username di database
$stmt = $conn->prepare('SELECT id, username, password FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verifikasi password menggunakan hash bcrypt
    if (password_verify($password, $user['password'])) {

        // Login berhasil: regenerasi ID sesi untuk mencegah session fixation attack
        session_regenerate_id(true);

        // Simpan data login ke sesi
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id']        = $user['id'];
        $_SESSION['admin_username']  = $user['username'];
        $_SESSION['last_activity']   = time();

        $stmt->close();
        $conn->close();

        header('Location: dashboard.php');
        exit;
    }
}

// Login gagal: gunakan pesan generik agar penyerang tidak tahu
// apakah username atau password yang salah (user enumeration prevention)
$_SESSION['error'] = 'Username atau password tidak valid.';
$stmt->close();
$conn->close();

header('Location: login.php');
exit;