<?php
// ============================================================
//  includes/functions.php  —  Helper Functions
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Composer autoloader (untuk PHPMailer)
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config/mail.php';

// ============================================================
//  CSRF Token
// ============================================================

/**
 * Generate dan simpan CSRF token di session
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi CSRF token dari request
 */
function verifyCsrfToken(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================================
//  Flash Message
// ============================================================

function flash(string $key): string
{
    if (isset($_SESSION[$key])) {
        $msg = htmlspecialchars($_SESSION[$key], ENT_QUOTES, 'UTF-8');
        unset($_SESSION[$key]);
        return $msg;
    }
    return '';
}

// ============================================================
//  Rate Limiting (per IP, maksimal 10 pendaftaran per jam)
//  Catatan: kolom ip_address sudah ada di schema SQL,
//  tidak perlu ALTER TABLE secara dinamis.
// ============================================================

function checkRateLimit(string $ip, mysqli $conn): bool
{
    $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
           FROM registrations
          WHERE ip_address = ? AND registered_at >= ?"
    );
    if (!$stmt) {
        return true; // Jika query gagal, izinkan pendaftaran
    }

    $stmt->bind_param('ss', $ip, $oneHourAgo);
    $stmt->execute();
    $count = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    return $count < 10;
}

// ============================================================
//  Kirim Email Konfirmasi Pendaftaran (PHPMailer)
// ============================================================

/**
 * @param string $toEmail   Alamat email peserta
 * @param string $toName    Nama lengkap peserta
 * @param string $eventName Nama event
 * @param array  $eventData Data event lengkap (opsional)
 * @return bool
 */
function sendRegistrationEmail(
    string $toEmail,
    string $toName,
    string $eventName,
    array  $eventData = []
): bool {

    // Fallback ke mail() bawaan PHP jika PHPMailer tidak tersedia
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $subject = "Konfirmasi Pendaftaran Event – {$eventName}";
        $message = "Halo {$toName},\n\n"
                 . "Terima kasih telah mendaftar pada event \"{$eventName}\".\n\n"
                 . "Salam,\nBEM Fasilkom Unsika";
        $headers = "From: " . MAIL_FROM_EMAIL . "\r\n";
        return @mail($toEmail, $subject, $message, $headers);
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION === 'ssl'
                            ? PHPMailer::ENCRYPTION_SMTPS
                            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

        $mail->isHTML(true);
        $mail->Subject = "✅ Konfirmasi Pendaftaran – {$eventName}";

        $eventDate = !empty($eventData['event_date'])
                     ? date('d M Y', strtotime($eventData['event_date'])) : '-';
        $category  = htmlspecialchars($eventData['category'] ?? '-');
        $type      = ucfirst($eventData['event_type'] ?? '-');

        $year = date('Y');
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Konfirmasi Pendaftaran</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Poppins,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0"
             style="background:#ffffff;border-radius:12px;overflow:hidden;
                    box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:600px;width:100%;">
        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#7aaace,#355872);
                     padding:30px 40px;text-align:center;">
            <h1 style="color:#fff;margin:0;font-size:24px;font-weight:700;">
              🎓 BEM Fasilkom Unsika
            </h1>
            <p style="color:rgba(255,255,255,.85);margin:8px 0 0;font-size:14px;">
              Sistem Pendaftaran Event
            </p>
          </td>
        </tr>
        <!-- Body -->
        <tr>
          <td style="padding:40px;">
            <h2 style="color:#2c3e50;margin:0 0 8px;font-size:20px;">✅ Pendaftaran Berhasil!</h2>
            <p style="color:#555;margin:0 0 24px;font-size:15px;line-height:1.6;">
              Halo <strong>{$toName}</strong>, pendaftaran Anda telah berhasil dikonfirmasi.
            </p>
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#f0f6fc;border-radius:10px;padding:24px;
                          border-left:4px solid #7aaace;">
              <tr><td style="padding-bottom:12px;">
                <p style="margin:0;font-size:13px;color:#888;">Nama Event</p>
                <p style="margin:4px 0 0;font-size:16px;font-weight:600;color:#2c3e50;">{$eventName}</p>
              </td></tr>
              <tr><td>
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td width="50%" style="padding-top:10px;vertical-align:top;">
                      <p style="margin:0;font-size:12px;color:#888;">Kategori</p>
                      <p style="margin:4px 0 0;font-size:14px;color:#2c3e50;">{$category}</p>
                    </td>
                    <td width="50%" style="padding-top:10px;vertical-align:top;">
                      <p style="margin:0;font-size:12px;color:#888;">Tipe</p>
                      <p style="margin:4px 0 0;font-size:14px;color:#2c3e50;">{$type}</p>
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2" style="padding-top:14px;">
                      <p style="margin:0;font-size:12px;color:#888;">Tanggal Penyelenggaraan</p>
                      <p style="margin:4px 0 0;font-size:14px;font-weight:600;color:#2c3e50;">
                        📅 {$eventDate}
                      </p>
                    </td>
                  </tr>
                </table>
              </td></tr>
            </table>
            <p style="color:#555;margin:24px 0 0;font-size:14px;line-height:1.7;">
              Harap simpan email ini sebagai bukti pendaftaran. Jika ada pertanyaan,
              silakan hubungi panitia BEM Fasilkom Unsika.
            </p>
          </td>
        </tr>
        <!-- Footer -->
        <tr>
          <td style="background:#f8f9fa;padding:20px 40px;text-align:center;
                     border-top:1px solid #e9ecef;">
            <p style="color:#aaa;margin:0;font-size:12px;">
              &copy; {$year} BEM Fasilkom Unsika &nbsp;|&nbsp;
              Email ini dikirim otomatis, harap tidak membalas.
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

        $mail->AltBody =
            "Halo {$toName},\n\n"
          . "Pendaftaran Anda pada event \"{$eventName}\" telah berhasil.\n\n"
          . "Detail:\n"
          . "- Kategori               : {$category}\n"
          . "- Tipe                   : {$type}\n"
          . "- Tanggal Penyelenggaraan: {$eventDate}\n\n"
          . "Salam,\nBEM Fasilkom Unsika";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("PHPMailer Error [{$toEmail}]: " . $mail->ErrorInfo);
        return false;
    }
}