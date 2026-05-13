/* ════════════════════════════════════════════════════════════════
   BEM Fasilkom Unsika — Main Script
   ════════════════════════════════════════════════════════════════
   PERBAIKAN KRITIS:
   Sebelumnya semua kode (DataTables, tooltips, AJAX handlers)
   tersarang di dalam fungsi fixDtLength() yang hanya bertugas
   styling dropdown. Sekarang semua berada di level yang benar
   dalam $(document).ready().
   ════════════════════════════════════════════════════════════════ */

$(document).ready(function () {

    /* ─────────────────────────────────────────────────────────────
       HELPER: Perbaiki styling dropdown DataTables "Show N entries"
    ───────────────────────────────────────────────────────────── */
    function fixDtLength() {
        var $label = $('.dataTables_length label');
        $label.css({
            'display'        : 'flex',
            'align-items'    : 'center',
            'flex-direction' : 'row',
            'flex-wrap'      : 'nowrap',
            'gap'            : '6px',
            'white-space'    : 'nowrap',
            'font-size'      : '14px'
        });
        $('.dataTables_length select').css({
            'display'             : 'inline-block',
            'width'               : 'auto',
            'min-width'           : '60px',
            'max-width'           : '80px',
            'padding'             : '4px 24px 4px 8px',
            'border'              : '1.5px solid #e4e8f0',
            'border-radius'       : '8px',
            'font-size'           : '14px',
            'background-color'    : '#fff',
            '-webkit-appearance'  : 'none',
            'appearance'          : 'none',
            'background-image'    : "url(\"data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 20 20'%3E%3Cpath fill='%236b7280' d='M7 7l3 3 3-3'/%3E%3C/svg%3E\")",
            'background-repeat'   : 'no-repeat',
            'background-position' : 'right 6px center',
            'background-size'     : '12px',
            'cursor'              : 'pointer',
            'vertical-align'      : 'middle'
        });
    }

    /* ─────────────────────────────────────────────────────────────
       1. Bootstrap Tooltips
    ───────────────────────────────────────────────────────────── */
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });

    /* ─────────────────────────────────────────────────────────────
       2. DataTables Initialization
    ───────────────────────────────────────────────────────────── */
    if ($('#dataTable').length) {
        $('#dataTable').DataTable({
            language    : { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' },
            responsive  : true,
            pageLength  : 25,
            order       : [],
            initComplete: function () {
                fixDtLength();
            }
        });
        $(document).on('init.dt length.dt', function () {
            fixDtLength();
        });
    }

    /* ─────────────────────────────────────────────────────────────
       3. Countdown Timer (Real-time, update setiap detik)
    ───────────────────────────────────────────────────────────── */
    function updateCountdowns() {
        $('.countdown').each(function () {
            var closingDate = $(this).data('closing');
            var now         = Date.now();
            var closing     = new Date(closingDate + 'T23:59:59').getTime();
            var dist        = closing - now;

            if (dist < 0) {
                // Event baru saja ditutup — hapus card dari tampilan
                var $col = $(this).closest('.col-md-4, .col-md-6, .col-lg-4');
                if ($col.length && !$col.data('expiring')) {
                    $col.data('expiring', true);
                    $col.fadeOut(800, function () { $(this).remove(); });
                }
                return;
            }

            var days    = Math.floor(dist / 86400000);
            var hours   = Math.floor((dist % 86400000) / 3600000);
            var minutes = Math.floor((dist % 3600000) / 60000);
            var seconds = Math.floor((dist % 60000) / 1000);
            var urgent  = dist < 86400000; // < 24 jam = urgent

            $(this).html(
                '<span class="countdown-chip' + (urgent ? ' urgent' : '') + '">' +
                '<i class="fas fa-clock fa-xs me-1"></i>' +
                days + 'h ' + hours + 'j ' + minutes + 'm ' + seconds + 'd' +
                '</span>'
            );
        });
    }

    updateCountdowns();
    setInterval(updateCountdowns, 1000);

    /* ─────────────────────────────────────────────────────────────
       4. Read More — Toggle deskripsi panjang di card event
    ───────────────────────────────────────────────────────────── */
    $(document).on('click', '.btn-read-more', function () {
        var $btn     = $(this);
        var $text    = $btn.prev('.event-desc-text');
        var expanded = $btn.hasClass('expanded');

        if (expanded) {
            $text.text($text.data('short') + '\u2026');
            $btn.removeClass('expanded')
                .html('Selengkapnya <i class="fas fa-chevron-down fa-xs ms-1"></i>');
        } else {
            $text.text($text.data('full'));
            $btn.addClass('expanded')
                .html('Lebih sedikit <i class="fas fa-chevron-up fa-xs ms-1"></i>');
        }
    });

    /* ─────────────────────────────────────────────────────────────
       5. Toggle Field Form Berdasarkan Tipe Event (Register Page)
    ───────────────────────────────────────────────────────────── */
    $('#event_type').on('change', function () {
        var type = $(this).val();
        if (type === 'umum') {
            $('.internal-field').slideUp(220);
            $('.umum-field').slideDown(220);
        } else if (type === 'internal') {
            $('.umum-field').slideUp(220);
            $('.internal-field').slideDown(220);
        }
    });

    /* ─────────────────────────────────────────────────────────────
       6. Form Pendaftaran Peserta — AJAX Submit
    ───────────────────────────────────────────────────────────── */
    $('#registerForm').on('submit', function (e) {
        e.preventDefault();

        var $form        = $(this);
        var $btn         = $form.find('[type="submit"]');
        var originalHtml = $btn.html();
        var eventType    = $form.data('eventType');

        // ── Validasi client-side ──
        var fullName = $('#full_name').val().trim();
        var email    = $('#email').val().trim();
        var phone    = $('#phone').val().trim();

        if (!fullName) return _swalError('Nama Belum Diisi', 'Nama lengkap wajib diisi.');
        if (!email)    return _swalError('Email Belum Diisi', 'Alamat email wajib diisi.');
        if (!phone)    return _swalError('Telepon Belum Diisi', 'Nomor telepon wajib diisi.');

        if (eventType === 'internal' && !$('#npm').val().trim()) {
            return _swalError('NPM Diperlukan', 'NPM wajib diisi untuk event internal.');
        }
        if (eventType === 'umum' && !$('#institution').val().trim()) {
            return _swalError('Instansi Diperlukan', 'Nama instansi wajib diisi untuk event umum.');
        }

        // ── Loading state ──
        $btn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-2"></i>Mendaftarkan…');

        $.ajax({
            url     : $form.attr('action'),
            method  : 'POST',
            data    : $form.serialize(),
            dataType: 'json',
            success : function (res) {
                if (res.success) {
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
                    _swalError('Pendaftaran Gagal', res.message);
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function () {
                _swalError('Koneksi Gagal', 'Terjadi kesalahan jaringan. Periksa koneksi Anda dan coba lagi.');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    /* ─────────────────────────────────────────────────────────────
       7. Admin: Form Tambah Event — AJAX Submit
    ───────────────────────────────────────────────────────────── */
    $('#eventAddForm').on('submit', function (e) {
        e.preventDefault();
        _submitAdminForm(
            $(this),
            'Menyimpan event…',
            'Event Berhasil Ditambahkan!',
            'events.php'
        );
    });

    /* ─────────────────────────────────────────────────────────────
       8. Admin: Form Edit Event — AJAX Submit
    ───────────────────────────────────────────────────────────── */
    $('#eventEditForm').on('submit', function (e) {
        e.preventDefault();
        _submitAdminForm(
            $(this),
            'Memperbarui event…',
            'Event Berhasil Diperbarui!',
            'events.php'
        );
    });

    /* ─────────────────────────────────────────────────────────────
       9. Admin: Hapus Event — AJAX dengan Konfirmasi SweetAlert
    ───────────────────────────────────────────────────────────── */
    $(document).on('click', '.btn-delete', function (e) {
        e.preventDefault();
        var $btn      = $(this);
        var $form     = $btn.closest('form');
        var eventName = $btn.closest('tr').find('.fw-semibold').first().text().trim() || 'event ini';

        Swal.fire({
            title           : '⚠️ Hapus Event?',
            html            : 'Event <strong>"' + _escHtml(eventName) + '"</strong> akan dihapus '
                            + 'beserta seluruh data peserta.<br>'
                            + '<small class="text-danger">Tindakan ini tidak dapat dibatalkan!</small>',
            icon            : 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor : '#6b7280',
            confirmButtonText : '<i class="fas fa-trash me-1"></i> Ya, Hapus!',
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
                            title            : 'Terhapus!',
                            text             : res.message,
                            timer            : 2000,
                            showConfirmButton: false
                        }).then(function () { location.reload(); });
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

    /* ─────────────────────────────────────────────────────────────
       10. Admin: Toggle Status Event — AJAX dengan Konfirmasi
    ───────────────────────────────────────────────────────────── */
    $(document).on('click', '.btn-toggle', function (e) {
        e.preventDefault();
        var href       = $(this).attr('href');
        var isActive   = $(this).hasClass('em-btn-muted');
        var actionText = isActive ? 'Nonaktifkan' : 'Aktifkan';
        var actionDesc = isActive
            ? 'Event akan disembunyikan dari halaman pendaftaran publik.'
            : 'Event akan tampil dan dapat didaftarkan oleh peserta.';

        Swal.fire({
            title           : actionText + ' Event?',
            text            : actionDesc,
            icon            : 'question',
            showCancelButton: true,
            confirmButtonColor: isActive ? '#ef4444' : '#10b981',
            cancelButtonColor : '#6b7280',
            confirmButtonText : 'Ya, ' + actionText + '!',
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
                        }).then(function () { location.reload(); });
                    } else {
                        _swalError('Gagal Mengubah Status', res.message);
                    }
                },
                error: function () {
                    _swalError('Koneksi Gagal', 'Terjadi kesalahan jaringan. Silakan coba lagi.');
                }
            });
        });
    });

    /* ═════════════════════════════════════════════════════════════
       UTILITY HELPERS (private functions)
    ═════════════════════════════════════════════════════════════ */

    /** Tampilkan SweetAlert error */
    function _swalError(title, text) {
        return Swal.fire({
            icon             : 'error',
            title            : title,
            text             : text,
            confirmButtonColor: '#ef4444'
        });
    }

    /**
     * Submit form admin via AJAX dengan dukungan file upload (FormData)
     * Digunakan oleh eventAddForm dan eventEditForm
     */
    function _submitAdminForm($form, loadingText, successTitle, redirectUrl) {
        var $btn         = $form.find('[type="submit"]');
        var originalHtml = $btn.html();

        $btn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-2"></i>' + loadingText);

        $.ajax({
            url        : $form.attr('action'),
            method     : 'POST',
            data       : new FormData($form[0]),
            processData: false,
            contentType: false,
            dataType   : 'json',
            success    : function (res) {
                if (res.success) {
                    Swal.fire({
                        icon             : 'success',
                        title            : successTitle,
                        text             : res.message || '',
                        timer            : 2200,
                        showConfirmButton: false
                    }).then(function () {
                        window.location.href = redirectUrl;
                    });
                } else {
                    _swalError('Gagal!', res.message);
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function () {
                _swalError('Koneksi Gagal', 'Terjadi kesalahan. Silakan coba lagi.');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    }

    /** Escape HTML untuk penggunaan aman dalam innerHTML */
    function _escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

}); // end $(document).ready