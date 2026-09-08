# Sistem Pendaftaran Event — BEM Fasilkom Unsika

Aplikasi web berbasis PHP untuk mengelola pendaftaran event/kegiatan BEM Fasilkom Unsika. Mendukung dua peran pengguna: **Admin** (mengelola event) dan **Member/Peserta** (mendaftar event), dengan sistem login terpadu yang otomatis mendeteksi jenis akun.

## Fitur Utama

### Untuk Peserta (Member)
- Registrasi & login akun peserta (password di-hash dengan bcrypt)
- Login terpadu — satu form mendeteksi otomatis apakah login sebagai admin (username) atau member (email)
- Menjelajahi daftar event dengan fitur **pencarian**, **filter kategori**, dan **pagination**
- Mendaftar event (mendukung dua tipe: **Umum** dan **Internal**, dengan field berbeda seperti NPM & Fakultas untuk internal)
- Validasi kuota otomatis dengan proteksi *race condition* (row locking `FOR UPDATE`)
- Cegah pendaftaran ganda berdasarkan email/NPM per event
- Rate limiting pendaftaran (maks. 10 per IP per jam)
- Riwayat & bukti pendaftaran yang bisa dicetak (`my_registrations.php`)
- Countdown real-time masa pendaftaran, kartu event otomatis hilang saat pendaftaran ditutup

### Untuk Admin
- Dashboard dengan statistik (total event, event aktif, total pendaftar)
- CRUD event lengkap (tambah, edit, hapus) dengan upload gambar/poster (validasi MIME & ukuran maks. 2MB)
- Hapus event otomatis menghapus seluruh data peserta terkait (`ON DELETE CASCADE`)
- Toggle status aktif/nonaktif event
- Melihat daftar peserta per event beserta indikator kapasitas kuota
- Export data peserta ke **CSV** (kompatibel Excel, dengan BOM UTF-8)
- Sesi admin otomatis logout setelah 30 menit tidak aktif

### Keamanan
- Proteksi **CSRF token** di seluruh form yang mengubah data
- **Prepared statements** (mysqli) di seluruh query untuk mencegah SQL Injection
- Password di-hash menggunakan `password_hash()` (bcrypt)
- Validasi MIME type asli (bukan hanya ekstensi) untuk upload gambar
- Escaping output dengan `htmlspecialchars()` untuk mencegah XSS
- Rate limiting pendaftaran berbasis IP

## Teknologi

| Kategori | Teknologi |
|---|---|
| Backend | PHP ≥ 8.0, MySQLi |
| Database | MySQL |
| Frontend | Bootstrap 5, jQuery, DataTables, SweetAlert2, Font Awesome |
| Dependency Manager | Composer |

## Struktur Proyek

```
├── admin/                  # Halaman & proses khusus admin
│   ├── includes/auth.php   # Guard autentikasi admin
│   ├── dashboard.php
│   ├── events.php
│   ├── event_add.php / event_save.php
│   ├── event_edit.php / event_update.php
│   ├── event_delete.php
│   ├── toggle_event.php
│   ├── participants.php
│   └── export_csv.php
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── config/
│   └── database.php        # Koneksi database & konfigurasi BASE_URL
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── functions.php        # CSRF token & rate limiting
├── uploads/                 # Folder penyimpanan poster event (di-gitignore)
├── index.php                 # Halaman utama (daftar event)
├── login.php / login_process.php
├── member_register.php / member_register_process.php
├── register.php / register_process.php
├── my_registrations.php
├── logout.php
├── composer.json
└── README.md
```

## Instalasi & Setup

### Prasyarat
- PHP ≥ 8.0 dengan ekstensi `mysqli` dan `fileinfo`
- MySQL / MariaDB
- Composer
- Web server (Apache/Nginx) atau `php -S` untuk pengembangan lokal

### Langkah-langkah

1. **Clone repository**
   ```bash
   git clone <url-repo-anda>
   cd bem-event
   ```

2. **Install dependency**
   ```bash
   composer install
   ```

3. **Buat database**
   Buat database MySQL (contoh nama: `bem_event`) beserta tabel-tabel berikut minimal:
   - `users` (admin: `id`, `username`, `password`)
   - `members` (`id`, `full_name`, `email`, `password`)
   - `events` (`id`, `name`, `description`, `event_date`, `documentation`, `event_type`, `category`, `quota`, `registration_open`, `registration_close`, `is_active`, `created_at`)
   - `registrations` (`id`, `event_id`, `full_name`, `email`, `institution`, `npm`, `faculty`, `phone`, `ip_address`, `registered_at`) — dengan `event_id` sebagai foreign key `ON DELETE CASCADE` ke `events`

4. **Konfigurasi koneksi database**
   Sesuaikan kredensial pada `config/database.php`:
   ```php
   $host = 'localhost';
   $user = 'root';
   $pass = '';
   $db   = 'bem_event';
   ```

5. **Buat folder upload**
   Pastikan folder `uploads/` ada dan dapat ditulis (writable) oleh web server:
   ```bash
   mkdir -p uploads
   chmod 755 uploads
   ```

6. **Jalankan aplikasi**
   ```bash
   php -S localhost:8000
   ```
   Atau arahkan document root web server Anda ke folder proyek ini.

7. **Buat akun admin pertama**
   Tambahkan baris manual ke tabel `users` dengan password yang sudah di-hash, misalnya via PHP:
   ```php
   echo password_hash('password_anda', PASSWORD_BCRYPT);
   ```

## Alur Penggunaan Singkat

1. Peserta mendaftar akun di `member_register.php`, lalu login di `login.php`.
2. Peserta menjelajahi event di `index.php` dan mendaftar melalui `register.php`.
3. Bukti pendaftaran dapat dilihat/dicetak di `my_registrations.php`.
4. Admin login dengan username di halaman login yang sama, lalu diarahkan ke `admin/dashboard.php` untuk mengelola event dan melihat data peserta.
