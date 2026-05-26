/* ============================================================
   assets/js/script.js
   Script utama project BEM Fasilkom Unsika
   Berisi:
   1. Inisialisasi Bootstrap Tooltips
   2. Inisialisasi DataTables (tabel admin)
   3. Countdown timer real-time
   4. Toggle "Selengkapnya" pada deskripsi event
   5. Submit form pendaftaran peserta (AJAX)
   6. Submit form tambah event (AJAX)
   7. Submit form edit event (AJAX)
   8. Hapus event dengan konfirmasi SweetAlert (AJAX)
   9. Toggle status aktif/nonaktif event (AJAX)
   ============================================================ */

$(document).ready(function () {

    /* ─────────────────────────────────────────────────────────
       1. Bootstrap Tooltips
       Aktifkan semua elemen yang memiliki atribut data-bs-toggle="tooltip"
    ───────────────────────────────────────────────────────── */
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });


    /* ─────────────────────────────────────────────────────────
       2. DataTables — tabel admin dengan fitur search & pagination
    ───────────────────────────────────────────────────────── */
    if ($('#dataTable').length) {
        var table = $('#dataTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
            },
            responsive  : true,
            pageLength  : 25,
            order       : [],   // Tidak ada urutan default
            initComplete: function () {
                _fixDtLayout(); // Perbaiki tampilan setelah inisialisasi
            }
        });

        // Perbaiki layout setiap kali panjang halaman berubah
        table.on('length.dt', function () {
            _fixDtLayout();
        });
    }

    /**
     * Memperbaiki tampilan dropdown "Show N entries" DataTables
     * agar sesuai dengan desain sistem
     */
    function _fixDtLayout() {
        $('.dataTables_length label').css({
            'display'       : 'flex',
            'align-items'   : 'center',
            'flex-direction': 'row',
            'gap'           : '6px',
            'white-space'   : 'nowrap',
            'font-size'     : '14px'
        });
        $('.dataTables_length select').css({
            'display'   : 'inline-block',
            'width'     : 'auto',
            'min-width' : '60px'
        });
    }


    /* ─────────────────────────────────────────────────────────
       3. Countdown Timer
       Menampilkan sisa waktu pendaftaran secara real-time
       di setiap kartu event (update setiap 1 detik)
    ───────────────────────────────────────────────────────── */
    function updateCountdowns() {
        $('.countdown').each(function () {
            var closing_date = $(this).data('closing');
            var now          = Date.now();
            // Anggap event tutup pukul 23:59:59
            var closing_ms   = new Date(closing_date + 'T23:59:59').getTime();
            var remaining    = closing_ms - now;

            if (remaining < 0) {
                // Pendaftaran sudah ditutup: hilangkan kartu dari halaman
                var $col = $(this).closest('[class*="col-"]');
                if ($col.length && !$col.data('removing')) {
                    $col.data('removing', true);
                    $col.fadeOut(800, function () { $(this).remove(); });
                }
                return;
            }

            var days    = Math.floor(remaining / 86400000);
            var hours   = Math.floor((remaining % 86400000) / 3600000);
            var minutes = Math.floor((remaining % 3600000) / 60000);
            var seconds = Math.floor((remaining % 60000) / 1000);
            var urgent  = remaining < 86400000; // Kurang dari 24 jam = mendesak

            $(this).html(
                '<span class="countdown-chip' + (urgent ? ' urgent' : '') + '">'
              + '<i class="fas fa-clock fa-xs me-1"></i>'
              + days + 'h ' + hours + 'j ' + minutes + 'm ' + seconds + 'd'
              + '</span>'
            );
        });
    }

    // Jalankan langsung dan ulangi setiap 1 detik
    updateCountdowns();
    setInterval(updateCountdowns, 1000);


    /* ─────────────────────────────────────────────────────────
       4. Tombol "Selengkapnya" / "Lebih sedikit" pada deskripsi
    ───────────────────────────────────────────────────────── */
    $(document).on('click', '.btn-read-more', function () {
        var $btn      = $(this);
        var $text     = $btn.prev('.event-desc-text');
        var expanded  = $btn.hasClass('expanded');

        if (expanded) {
            // Lipat kembali ke teks pendek
            $text.text($text.data('short') + '\u2026');
            $btn.removeClass('expanded')
                .html('Selengkapnya <i class="fas fa-chevron-down fa-xs ms-1"></i>');
        } else {
            // Tampilkan teks penuh
            $text.text($text.data('full'));
            $btn.addClass('expanded')
                .html('Lebih sedikit <i class="fas fa-chevron-up fa-xs ms-1"></i>');
        }
    });


    /* ─────────────────────────────────────────────────────────
       5. Form Pendaftaran Peserta — AJAX Submit (register.php)
    ───────────────────────────────────────────────────────── */
    $('#registerForm').on('submit', function (e) {
        e.preventDefault(); // Cegah reload halaman

        var $form       = $(this);
        var $btn        = $form.find('[type="submit"]');
        var btn_ori     = $btn.html();
        var event_type  = $form.data('eventType');

        // ── Validasi sisi klien sebelum kirim ke server ──
        var full_name = $('#full_name').val().trim();
        var email     = $('#email').val().trim();
        var phone     = $('#phone').val().trim();

        if (!full_name) {
            return _swalError('Nama Belum Diisi', 'Nama lengkap wajib diisi.');
        }
        if (!email) {
            return _swalError('Email Belum Diisi', 'Alamat email wajib diisi.');
        }
        if (!phone) {
            return _swalError('Telepon Belum Diisi', 'Nomor telepon wajib diisi.');
        }
        if (event_type === 'internal' && !$('#npm').val().trim()) {
            return _swalError('NPM Diperlukan', 'NPM wajib diisi untuk event internal.');
        }
        if (event_type === 'umum' && !$('#institution').val().trim()) {
            return _swalError('Instansi Diperlukan', 'Nama instansi wajib diisi untuk event umum.');
        }

        // ── Tampilkan loading state ──
        $btn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-2"></i>Memproses...');

        // ── Kirim data via AJAX ──
        $.ajax({
            url     : $form.attr('action'),
            method  : 'POST',
            data    : $form.serialize(),
            dataType: 'json',
            success : function (res) {
                if (res.success) {
                    // Pendaftaran berhasil: tampilkan popup sukses
                    Swal.fire({
                        icon             : 'success',
                        title            : '🎉 Pendaftaran Berhasil!',
                        html             : res.message,
                        confirmButtonText: 'Kembali ke Beranda',
                        confirmButtonColor: '#2563eb',
                        allowOutsideClick: false
                    }).then(function () {
                        window.location.href = 'index.php';
                    });
                } else {
                    // Pendaftaran gagal: tampilkan pesan error
                    _swalError('Pendaftaran Gagal', res.message);
                    $btn.prop('disabled', false).html(btn_ori);
                }
            },
            error: function () {
                _swalError('Koneksi Gagal', 'Terjadi kesalahan jaringan. Periksa koneksi Anda dan coba lagi.');
                $btn.prop('disabled', false).html(btn_ori);
            }
        });
    });


    /* ─────────────────────────────────────────────────────────
       6. Form Tambah Event — AJAX Submit (event_add.php)
    ───────────────────────────────────────────────────────── */
    $('#eventAddForm').on('submit', function (e) {
        e.preventDefault();
        _submitAdminForm($(this), 'Menyimpan event...', 'Event Berhasil Ditambahkan!', 'events.php');
    });


    /* ─────────────────────────────────────────────────────────
       7. Form Edit Event — AJAX Submit (event_edit.php)
    ───────────────────────────────────────────────────────── */
    $('#eventEditForm').on('submit', function (e) {
        e.preventDefault();
        _submitAdminForm($(this), 'Menyimpan perubahan...', 'Event Berhasil Diperbarui!', 'events.php');
    });


    /* ─────────────────────────────────────────────────────────
       8. Hapus Event — AJAX dengan Konfirmasi SweetAlert
       Tombol .btn-delete ada di dalam form dengan action event_delete.php
    ───────────────────────────────────────────────────────── */
    $(document).on('click', '.btn-delete', function (e) {
        e.preventDefault();

        var $btn       = $(this);
        var $form      = $btn.closest('form');
        // Ambil nama event dari baris tabel yang sama
        var event_name = $btn.closest('tr').find('td:nth-child(2) .fw-semibold').text().trim()
                      || 'event ini';

        Swal.fire({
            title            : '⚠️ Hapus Event?',
            html             : 'Event <strong>"' + _escHtml(event_name) + '"</strong> akan dihapus '
                             + 'beserta seluruh data pesertanya.<br>'
                             + '<small class="text-danger fw-semibold">Tindakan ini tidak dapat dibatalkan!</small>',
            icon             : 'warning',
            showCancelButton : true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor : '#6b7280',
            confirmButtonText : '<i class="fas fa-trash me-1"></i>Ya, Hapus!',
            cancelButtonText  : 'Batal',
            reverseButtons    : true
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url     : $form.attr('action'),
                method  : 'POST',
                data    : $form.serialize(),
                dataType: 'json',
                success : function (res) {
                    if (res.success) {
                        Swal.fire({
                            icon             : 'success',
                            title            : 'Berhasil Dihapus!',
                            text             : res.message,
                            timer            : 2000,
                            showConfirmButton: false
                        }).then(function () {
                            location.reload();
                        });
                    } else {
                        _swalError('Gagal Menghapus', res.message);
                    }
                },
                error: function () {
                    _swalError('Koneksi Gagal', 'Terjadi kesalahan jaringan. Silakan coba lagi.');
                }
            });
        });
    });


    /* ─────────────────────────────────────────────────────────
       9. Toggle Status Event — AJAX dengan Konfirmasi
       Link .btn-toggle mengarah ke toggle_event.php?id=X
    ───────────────────────────────────────────────────────── */
    $(document).on('click', '.btn-toggle', function (e) {
        e.preventDefault();

        var $btn       = $(this);
        var href       = $btn.attr('href');
        // Tentukan apakah event saat ini aktif berdasarkan class tombol
        var is_active  = $btn.hasClass('btn-secondary');
        var action_txt = is_active ? 'Nonaktifkan' : 'Aktifkan';
        var desc_txt   = is_active
                         ? 'Event akan disembunyikan dari halaman publik.'
                         : 'Event akan tampil dan dapat didaftarkan peserta.';

        Swal.fire({
            title            : action_txt + ' Event?',
            text             : desc_txt,
            icon             : 'question',
            showCancelButton : true,
            confirmButtonColor: is_active ? '#ef4444' : '#10b981',
            cancelButtonColor : '#6b7280',
            confirmButtonText : 'Ya, ' + action_txt + '!',
            cancelButtonText  : 'Batal',
            reverseButtons    : true
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url     : href,
                method  : 'GET',
                dataType: 'json',
                success : function (res) {
                    if (res.success) {
                        Swal.fire({
                            icon             : 'success',
                            title            : 'Berhasil!',
                            text             : res.message,
                            timer            : 1800,
                            showConfirmButton: false
                        }).then(function () {
                            location.reload();
                        });
                    } else {
                        _swalError('Gagal', res.message);
                    }
                },
                error: function () {
                    _swalError('Koneksi Gagal', 'Terjadi kesalahan jaringan. Silakan coba lagi.');
                }
            });
        });
    });


    /* ═══════════════════════════════════════════════════════
       FUNGSI PEMBANTU (Private)
    ═══════════════════════════════════════════════════════ */

    /**
     * Tampilkan SweetAlert error
     * @param {string} title  - Judul popup
     * @param {string} text   - Pesan error
     */
    function _swalError(title, text) {
        return Swal.fire({
            icon             : 'error',
            title            : title,
            text             : text,
            confirmButtonColor: '#ef4444'
        });
    }

    /**
     * Submit form admin (add/edit event) via AJAX dengan FormData
     * Mendukung upload file gambar
     * @param {jQuery} $form         - Elemen form jQuery
     * @param {string} loading_text  - Teks tombol saat loading
     * @param {string} success_title - Judul popup sukses
     * @param {string} redirect_url  - URL tujuan setelah sukses
     */
    function _submitAdminForm($form, loading_text, success_title, redirect_url) {
        var $btn    = $form.find('[type="submit"]');
        var btn_ori = $btn.html();

        // Tampilkan loading
        $btn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-2"></i>' + loading_text);

        $.ajax({
            url        : $form.attr('action'),
            method     : 'POST',
            // FormData diperlukan untuk mengirim file upload
            data       : new FormData($form[0]),
            processData: false,     // Jangan proses FormData menjadi string
            contentType: false,     // Biarkan browser set content-type (multipart)
            dataType   : 'json',
            success    : function (res) {
                if (res.success) {
                    Swal.fire({
                        icon             : 'success',
                        title            : success_title,
                        text             : res.message || '',
                        timer            : 2200,
                        showConfirmButton: false
                    }).then(function () {
                        window.location.href = redirect_url;
                    });
                } else {
                    _swalError('Gagal!', res.message);
                    $btn.prop('disabled', false).html(btn_ori);
                }
            },
            error: function () {
                _swalError('Koneksi Gagal', 'Terjadi kesalahan. Silakan coba lagi.');
                $btn.prop('disabled', false).html(btn_ori);
            }
        });
    }

    /**
     * Escape karakter HTML untuk mencegah XSS pada innerHTML
     * @param {string} str - String yang akan di-escape
     * @returns {string}
     */
    function _escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

}); // end $(document).ready