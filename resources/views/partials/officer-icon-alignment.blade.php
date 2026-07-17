{{--
    Normalisasi baseline Flaticon UIcons untuk seluruh tampilan petugas.

    UIcons merender glyph pada pseudo-element ::before. Tanpa normalisasi,
    kotak <i> tetap mengikuti line-height teks Poppins dan banyak komponen lama
    menambahkan top: 1-3px pada wrapper maupun <i>, sehingga offset menumpuk.
    Partial ini harus dimuat setelah @stack('styles') agar hasilnya konsisten
    pada Operasi, Pemeliharaan, dan Safety.
--}}
<style>
    body.officer-report-shell i.fi {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        line-height: 1 !important;
        vertical-align: middle;
    }

    body.officer-report-shell i.fi::before {
        display: block;
        line-height: 1 !important;
    }

    /* Hanya ikon inline/flex yang dinetralkan. Ikon input dan dropdown tetap
       memakai position:absolute + top:50% dari komponen masing-masing. */
    body.officer-report-shell i.fi:not(.input-icon):not(.tbl-icon-dropdown) {
        position: relative !important;
        top: 0 !important;
    }

    /* Hapus offset positif pada wrapper ikon lama (icon-tab, icon-new, dst.). */
    body.officer-report-shell [class^="icon-"],
    body.officer-report-shell [class*=" icon-"],
    body.officer-report-shell .btn-form .icon,
    body.officer-report-shell span.icon,
    body.officer-report-shell div.icon {
        top: 0 !important;
    }

    /* Glyph kecil pada field tabel memiliki massa visual di bawah baseline.
       Koreksi dua piksel dipakai untuk nama unit, BBM, jam masuk/pulang,
       lokasi, dan ikon field tabel lain tanpa mengubah tinggi baris. */
    body.officer-report-shell .table-input-wrapper i.fi {
        top: -2px !important;
    }

    /* Pada island sticky, tinggi tombol lebih ringkas sehingga glyph disket
       membutuhkan koreksi tersendiri agar pusat visualnya sejajar dengan teks. */
    body.officer-report-shell .content-header.is-sticky .btn-new i.fi,
    body.officer-report-shell .content-header.is-sticky .btn-draft-save i.fi {
        top: -2px !important;
    }

    /* Pusat geometris glyph UIcons sedikit lebih rendah dari pusat tombol.
       -2px terlihat terlalu tinggi dan 0px terlalu rendah, sehingga koreksi
       optik -1px dipakai konsisten pada dashboard dan form. */
    body.officer-report-shell .btn-theme .icon-container {
        transform: translateY(-1px) !important;
    }

    /* Bentuk bulan memiliki pusat optik lebih tinggi daripada matahari. */
    body.officer-report-shell .btn-theme #themeIcon.fi-rr-moon {
        top: 1px !important;
    }

    /* Matahari juga memerlukan koreksi optik kecil agar tepat di tengah tombol. */
    body.officer-report-shell .btn-theme #themeIcon.fi-rr-sun {
        top: 1px !important;
    }
</style>
