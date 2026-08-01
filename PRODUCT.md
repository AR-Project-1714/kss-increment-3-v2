# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Aplikasi internal KSS dengan lima role: `admin`, `manajer`, `operasional`, `pemeliharaan`, dan `safety`.

Petugas lapangan (`operasional`, `pemeliharaan`, `safety`) mengisi laporan harian/shift dari lokasi kerja pelabuhan. Manajer membaca, menyetujui, dan menarik ringkasan kinerja. Admin mengelola pengguna, data master, backup, log aktivitas, dan infrastruktur.

Halaman **Billing Cloud** dilayani dua tingkat pembacaan sekaligus (dikonfirmasi):

- **Admin IT internal** — pembacaan operasional harian: apakah saldo IDCloudHost masih aman sehingga layanan tidak mati.
- **Manajemen** — pembacaan sesekali untuk keputusan anggaran dan waktu top-up.

## Product Purpose

Menggantikan pelaporan shift harian operasional pelabuhan/bongkar muat yang sebelumnya manual menjadi sistem digital terstruktur: pengisian laporan multi-step, tanda tangan digital regu penerima, approval manajer, arsip, dan export PDF/Excel. Berhasil ketika satu shift dapat menyerahkan laporan lengkap tanpa kertas dan manajer dapat menyetujuinya di hari yang sama.

## Operating Context

- Alur laporan Operasional berbasis **shift** dengan serah-terima antar regu: `draft → submitted → acknowledged → approved`.
- Alur Pemeliharaan dan Safety berbasis **hari (Non Shift)** tanpa serah-terima: `draft → submitted → approved`.
- Approval final hanya milik role `manajer`.
- Laporan berakhir sebagai dokumen PDF/Excel yang diarsipkan — output cetak adalah bagian nyata dari pemakaian, bukan sekadar layar.
- Billing Cloud membaca data IDCloudHost (kredit, laporan pemakaian, invoice top-up, riwayat saldo) dan dapat menampilkan snapshot terakhir ketika sumber sedang tidak tersedia.

## Capabilities and Constraints

Tiga increment selesai: modul Operasional, Pemeliharaan, dan Safety/K3, di atas fondasi arsitektur dan design system yang sama.

Stack: PHP 8.3+, Laravel 13, Blade, Eloquent, Vite 8, Tailwind CSS 4, Bootstrap 5.3 (CDN), Flatpickr, Flaticon UICons 2.6.0, Google Font Poppins. SQLite untuk dev, MySQL/MariaDB/PostgreSQL siap produksi. DomPDF dan PhpSpreadsheet untuk export.

Batasan desain yang dikonfirmasi mengikat:

- **Token & dark mode** — seluruh warna lewat CSS variable yang sudah ada (`--blue-main`, `--smooth-border`, `--black-secondary`, dan seterusnya) dan wajib benar pada `body.dark-mode`. Tidak ada hex hardcoded.
- **Tanpa dependensi baru** — hanya Blade + CSS pada file/layout yang ada. Tidak menambah library JS/CSS; tetap Poppins + Flaticon UICons.
- **Reuse komponen kartu** — metrik memakai anatomi kartu yang sudah ada (`.kpi-card` / `.stat-card`) alih-alih pola khusus per halaman.
- **Responsif & aksesibel** — tetap terbaca di layar kecil; progressbar, tab, dan tabel mempertahankan semantik ARIA yang benar.

## Brand Commitments

Nama sistem: **Sistem Laporan Operasional KSS**. Akun billing yang tampil: Kaltim Satria Samudera.

Bahasa antarmuka dan komentar kode adalah Bahasa Indonesia.

## Evidence on Hand

- `README.md`, `DOKUMENTASI.md` — dokumentasi produk dan teknis.
- `PERANCANGAN_MODUL_PEMELIHARAAN.md`, `PERANCANGAN_MODUL_SAFETY.md` — perancangan per modul.
- `KEAMANAN_SISTEM.md` — catatan keamanan.
- Design system incumbent hidup di `resources/css/layouts/*.css` dan `resources/css/components/*.css` (token pada `admin.css`, kartu metrik pada `charts.css`). Belum ada DESIGN.md.

Data billing berasal dari API IDCloudHost yang nyata; angka contoh apa pun tidak boleh dikarang.

## Product Principles

1. **Laporan adalah dokumen, bukan sekadar layar** — apa pun yang tampil harus tetap benar ketika dicetak atau diarsipkan.
2. **Satu design system lintas modul** — increment baru memakai kembali role, layout, dan komponen yang ada, bukan membuat dialek visual sendiri.
3. **Terbaca di dua tingkat** — permukaan yang sama harus melayani pembacaan operasional harian dan pembacaan manajemen sesekali.
4. **Angka menuntun tindakan** — metrik ditampilkan agar pengguna tahu apa yang harus dilakukan, bukan sekadar melihat nilainya.
5. **Jujur saat data tidak lengkap** — status tidak tersedia, snapshot lama, dan galat parsial dinyatakan terbuka, tidak disamarkan.

## Accessibility & Inclusion

Dark mode adalah kebutuhan nyata, bukan opsi kosmetik. Kontras teks dan komponen harus memadai pada kedua tema, dan kontrol interaktif mempertahankan focus state serta semantik ARIA.
