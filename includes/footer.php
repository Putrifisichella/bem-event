<?php
// ============================================================
//  includes/footer.php
//  Template bagian bawah halaman (footer + script JS)
//  Di-include di akhir setiap halaman
// ============================================================
?>

</main><!-- /main -->

<!-- ================================================================
     FOOTER
================================================================ -->
<footer style="background:#1a2e42; border-top:1px solid rgba(255,255,255,.1); padding:16px 0; margin-top:40px;">
    <div class="container">
        <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">

            <!-- Logo dan nama di footer -->
            <div class="d-flex align-items-center gap-2">
                <img src="<?= BASE_URL ?>assets/img/logo-bem.png"
                     alt="Logo BEM"
                     style="height:22px; border-radius:4px; opacity:.85;">
                <span class="fw-bold text-white small">BEM Fasilkom Unsika</span>
            </div>

            <!-- Copyright -->
            <p class="mb-0 text-white-50 small">
                &copy; <?= date('Y') ?> BEM Fasilkom Unsika &mdash; Sistem Pendaftaran Event
            </p>

        </div>
    </div>
</footer>


<!-- ================================================================
     SCRIPT JAVASCRIPT
     Urutan pemuatan penting: jQuery → Bootstrap → DataTables → SweetAlert → Custom
================================================================ -->

<!-- Bootstrap 5 JS (termasuk Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery (diperlukan oleh DataTables dan script kustom) -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<!-- SweetAlert2 (popup alert cantik) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Script kustom project -->
<script src="<?= BASE_URL ?>assets/js/script.js"></script>

</body>
</html>