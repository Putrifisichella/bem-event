<?php
// ============================================================
//  logout.php
//  Logout untuk semua peran (admin & member)
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

session_unset();
session_destroy();

header('Location: login.php');
exit;