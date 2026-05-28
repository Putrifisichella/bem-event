<?php
// ============================================================
//  includes/functions.php
//  Berisi fungsi-fungsi pembantu yang digunakan di seluruh project:
//  - Manajemen sesi
//  - CSRF token (keamanan form)
//  - Rate limiting (pembatasan pendaftaran per IP)
//  - Pengiriman email konfirmasi
// ============================================================

// Pastikan sesi sudah dimulai sebelum menggunakan session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Muat autoloader Composer (untuk PHPMailer)
$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
}

// Muat namespace PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Muat konfigurasi email
require_once __DIR__ . '/../config/mail.php';


// ============================================================
//  CSRF TOKEN
//  CSRF (Cross-Site Request Forgery) adalah serangan di mana
//  penyerang mencoba mengirim request palsu atas nama pengguna.
//  Token ini mencegah hal tersebut.
// ============================================================

/**
 * Membuat token CSRF baru dan menyimpannya di sesi.
 * Token ini disematkan di setiap form sebagai hidden field.
 *
 * @return string Token CSRF dalam format heksadesimal
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // random_bytes menghasilkan 32 byte data acak yang aman secara kriptografi
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Memverifikasi token CSRF dari request dengan token di sesi.
 * Menggunakan hash_equals agar tidak rentan terhadap timing attack.
 *
 * @param string|null $token Token yang dikirim via form
 * @return bool true jika valid, false jika tidak
 */
function verifyCsrfToken(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}


// ============================================================
//  RATE LIMITING
//  Membatasi jumlah pendaftaran dari satu IP address
//  agar tidak disalahgunakan (spam/bot)
// ============================================================

/**
 * Memeriksa apakah IP tertentu sudah melebihi batas pendaftaran.
 * Batas: maksimal 10 kali pendaftaran per jam per IP.
 *
 * @param string $ip   Alamat IP pengguna
 * @param mysqli $conn Koneksi database yang aktif
 * @return bool true jika masih diizinkan, false jika melebihi batas
 */
function checkRateLimit(string $ip, mysqli $conn): bool
{
    // Hitung 1 jam yang lalu dalam format datetime MySQL
    $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
           FROM registrations
          WHERE ip_address = ?
            AND registered_at >= ?"
    );

    // Jika query gagal disiapkan, izinkan saja agar tidak memblokir pengguna normal
    if (!$stmt) {
        error_log('Rate limit query error: ' . $conn->error);
        return true;
    }

    $stmt->bind_param('ss', $ip, $one_hour_ago);
    $stmt->execute();
    $count = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Kembalikan true jika belum melebihi batas 10 kali
    return $count < 10;
}


// ============================================================
//  KIRIM EMAIL KONFIRMASI
//  Dikirim ke peserta setelah berhasil mendaftar event
// ============================================================

/**
 * Mengirim email konfirmasi pendaftaran menggunakan PHPMailer.
 * Jika PHPMailer tidak tersedia, akan fallback ke fungsi mail() bawaan PHP.
 * Error pengiriman email tidak menggagalkan proses pendaftaran.
 *
 * @param string $toEmail    Alamat email peserta
 * @param string $toName     Nama lengkap peserta
 * @param string $eventName  Nama event yang didaftarkan
 * @param array  $eventData  Data lengkap event dari database
 * @return bool true jika berhasil dikirim, false jika gagal
 */
function sendRegistrationEmail(
    string $toEmail,
    string $toName,
    string $eventName,
    array  $eventData = []
): bool {

    // --- Fallback: gunakan mail() bawaan PHP jika PHPMailer tidak tersedia ---
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $subject = "Konfirmasi Pendaftaran Event – {$eventName}";
        $message = "Halo {$toName},\n\n"
                 . "Pendaftaran Anda pada event \"{$eventName}\" telah berhasil.\n\n"
                 . "Salam,\nBEM Fasilkom Unsika";
        $headers = "From: " . MAIL_FROM_EMAIL . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        return @mail($toEmail, $subject, $message, $headers);
    }

    // --- Gunakan PHPMailer untuk pengiriman SMTP ---
    $mail = new PHPMailer(true); // true = aktifkan exception

    try {
        // Konfigurasi server SMTP
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = (MAIL_ENCRYPTION === 'ssl')
                            ? PHPMailer::ENCRYPTION_SMTPS
                            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // Pengaturan alamat email
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

        // Persiapkan data untuk isi email
        $event_date = !empty($eventData['event_date'])
                      ? date('d M Y', strtotime($eventData['event_date']))
                      : '-';
        $category   = htmlspecialchars($eventData['category']   ?? '-');
        $event_type = ucfirst($eventData['event_type']          ?? '-');
        $year       = date('Y');
        $safe_name  = htmlspecialchars($toName);
        $safe_event = htmlspecialchars($eventName);

        // Subjek email
        $mail->Subject = "✅ Konfirmasi Pendaftaran – {$eventName}";

        // Isi email dalam format HTML
        $mail->isHTML(true);
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konfirmasi Pendaftaran</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:12px;overflow:hidden;
                    box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:600px;width:100%;">

        <!-- Header biru -->
        <tr>
          <td style="background:linear-gradient(135deg,#1a2e42,#2563eb);
                     padding:32px 40px;text-align:center;">
            <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">
              BEM Fasilkom Unsika
            </h1>
            <p style="color:rgba(255,255,255,.8);margin:8px 0 0;font-size:14px;">
              Sistem Pendaftaran Event
            </p>
          </td>
        </tr>

        <!-- Isi email -->
        <tr>
          <td style="padding:36px 40px;">
            <h2 style="color:#1a2e42;margin:0 0 8px;font-size:20px;">
              ✅ Pendaftaran Berhasil!
            </h2>
            <p style="color:#555;margin:0 0 24px;font-size:15px;line-height:1.6;">
              Halo <strong>{$safe_name}</strong>,
              pendaftaran Anda telah berhasil dikonfirmasi. Berikut rinciannya:
            </p>

            <!-- Kotak detail event -->
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#f0f6fc;border-radius:10px;padding:24px;
                          border-left:4px solid #2563eb;">
              <tr><td style="padding-bottom:14px;">
                <p style="margin:0;font-size:12px;color:#888;">Nama Event</p>
                <p style="margin:4px 0 0;font-size:17px;font-weight:700;color:#1a2e42;">
                  {$safe_event}
                </p>
              </td></tr>
              <tr><td>
                <table width="100%">
                  <tr>
                    <td width="33%" style="padding-top:10px;vertical-align:top;">
                      <p style="margin:0;font-size:12px;color:#888;">Kategori</p>
                      <p style="margin:4px 0 0;font-size:14px;color:#333;">{$category}</p>
                    </td>
                    <td width="33%" style="padding-top:10px;vertical-align:top;">
                      <p style="margin:0;font-size:12px;color:#888;">Tanggal</p>
                      <p style="margin:4px 0 0;font-size:14px;font-weight:600;color:#1a2e42;">
                        {$event_date}
                      </p>
                    </td>
                  </tr>
                </table>
              </td></tr>
            </table>

            <p style="color:#555;margin:24px 0 0;font-size:14px;line-height:1.7;">
              Simpan email ini sebagai bukti pendaftaran Anda. Jika ada pertanyaan,
              hubungi panitia BEM Fasilkom Unsika.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f8f9fa;padding:18px 40px;text-align:center;
                     border-top:1px solid #e9ecef;">
            <p style="color:#aaa;margin:0;font-size:12px;">
              &copy; {$year} BEM Fasilkom Unsika &nbsp;|&nbsp;
              Email ini dikirim otomatis, mohon tidak membalas.
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

        // Versi teks polos untuk email client yang tidak mendukung HTML
        $mail->AltBody =
            "Halo {$toName},\n\n"
          . "Pendaftaran Anda pada event \"{$eventName}\" telah berhasil.\n\n"
          . "Detail Event:\n"
          . "- Kategori : {$category}\n"
          . "- Tipe     : {$event_type}\n"
          . "- Tanggal  : {$event_date}\n\n"
          . "Salam,\nBEM Fasilkom Unsika";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Catat error ke log server, tidak ditampilkan ke pengguna
        error_log("PHPMailer Error [{$toEmail}]: " . $mail->ErrorInfo);
        return false;
    }
}