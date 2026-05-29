<?php
// ============================================================
//  includes/functions.php
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
//  CSRF TOKEN
// ============================================================

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================================
//  RATE LIMITING
// ============================================================

function checkRateLimit(string $ip, mysqli $conn): bool
{
    $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
           FROM registrations
          WHERE ip_address = ?
            AND registered_at >= ?"
    );

    if (!$stmt) {
        error_log('Rate limit query error: ' . $conn->error);
        return true;
    }

    $stmt->bind_param('ss', $ip, $one_hour_ago);
    $stmt->execute();
    $count = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    return $count < 10;
}