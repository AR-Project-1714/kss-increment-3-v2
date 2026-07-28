# Tampilan Manajer

Peran **Manajer** bertugas meninjau dan menyetujui (menandatangani secara digital) laporan yang masuk dari ketiga divisi lapangan (Operasional, Pemeliharaan, Safety/K3). Manajer tidak membuat atau mengedit laporan — murni sebagai peninjau/pemberi persetujuan.

- **Route prefix**: `/manajer` (middleware `role:manajer`)
- **Controller**: `app/Http/Controllers/ManajerController.php`
- **Layout**: `resources/views/manajer/layouts/*` (sidebar + navbar)

## Menu & Fungsi

### Dashboard
`manajer.index` — `resources/views/manajer/index.blade.php`
Subjudul: "Ringkasan performa dan aktivitas pelaporan dari ketiga divisi." Panel **Laporan Masuk** dengan tab (Semua / Operasional / Pemeliharaan / Safety-K3) beserta badge jumlah laporan menunggu. Tiap kartu laporan menampilkan kategori, shift/hari, waktu diterima, ID dokumen, regu pengirim/penerima, dan dua aksi:
- **Baca Laporan** — membuka detail laporan.
- **Tanda Tangani** — modal persetujuan menampilkan tanda tangan digital manajer, setelah dikonfirmasi status laporan berubah menjadi "Diarsipkan" dan tidak bisa diedit lagi oleh petugas pengirim.

Ini adalah alur kerja inti manajer: menyetujui laporan dari ketiga divisi melalui satu dashboard.

### Kinerja Operasi
`manajer.performa` — `resources/views/manajer/performa.blade.php` (URL tetap `/manajer/performa`)
Ringkasan produktivitas divisi operasi dengan filter periode (preset Bulan Ini / Bulan Lalu / 3 Bulan atau rentang tanggal bebas), regu, dan shift. Seluruh angkanya diturunkan dari laporan harian yang sudah masuk — tidak ada tabel data tersendiri.

Isi halaman:
- **Empat kartu KPI** — tonase ditangani, kapal dilayani, tonase per shift, rasio kerusakan, masing-masing dengan perbandingan periode setara dan sparkline 6 bulan.
- **Grafik** — tren tonase 6 bulan, komposisi kegiatan, tonase per shift, rasio kerusakan, perbandingan regu, beban kerja, peringkat lembur, dan daftar kapal dilayani.
- **Ekspor** — `manajer.performa.export` menghasilkan Excel enam sheet mengikuti filter yang sedang aktif.

Rincian per jenis kegiatan tidak ada di sini melainkan di menu **Rincian Kegiatan**, supaya satu halaman tidak menampung ringkasan divisi sekaligus bedah lima kegiatan.

### Rincian Kegiatan
`manajer.kegiatan` — `resources/views/manajer/kegiatan.blade.php`
Rincian lima jenis kegiatan operasi: pemuatan pupuk kantong, pemuatan urea curah, bongkar bahan baku, bongkar/muat container, dan trucking pengiriman pupuk kantong. Toolbar filternya sama persis dengan Kinerja Operasi (`manajer.partials.performance-toolbar`), dan filter aktif ikut terbawa lewat query string saat berpindah antar kedua menu itu.

Isi halaman:
- **Lima kartu kegiatan** — capaian periode berjalan, perubahan terhadap periode setara, dan sparkline 6 bulan. Angkanya diambil dari matriks agregat yang sama dengan kartu KPI, jadi tidak menambah query.
- **Tab rincian** — satu panel per kegiatan berisi metrik khas kegiatan, tren 6 bulan, peringkat regu, komposisi tambahan (jenis bahan baku / tujuan trucking), dan tabel rincian. Isi panel diambil lewat `manajer.kegiatan.panel` saat tabnya dibuka, bukan ikut dirender bersama halaman.

Panel rincian menyembunyikan apa yang kosong: kolom tabel yang tak pernah terisi dibuang, metrik tanpa nilai dilewati, dan blok tren/peringkat yang nol tidak dirender. Trucking dibatasi 10 rit terbaru karena barisnya per rit, kegiatan lain 50 baris.

**Satuan tidak seragam:** container dicatat dalam **Teus**, kegiatan lain dalam **Ton**. Karena itu container tidak ikut dijumlahkan ke Total Tonase maupun ke donut Komposisi Kegiatan — angkanya berdiri sendiri di kartu kegiatan dan panel rinciannya. Penandanya ada di `OperationalPerformanceService::activityCatalog()` lewat `countsToTonnage`.

> Asal setiap angka pada kedua menu di atas — tabel dan kolom sumbernya, rumusnya, mekanisme cache, serta jebakan yang sering ditanyakan — dibahas terpisah di [Kinerja Operasi & Rincian Kegiatan](kinerja-dan-rincian-kegiatan.md).

### Arsip Laporan
`manajer.archive` — `resources/views/manajer/archive.blade.php`
"Riwayat Laporan" — daftar laporan yang berstatus diserahkan, ditandatangani, dan diarsipkan, dilengkapi pencarian. Manajer dapat mengunduh atau menghapus laporan dari sini.

### Pusat Bantuan
`manajer.bantuan` — `resources/views/manajer/bantuan.blade.php`
Panduan penggunaan: ringkasan sistem, alur laporan, cara menandatangani laporan, penjelasan status laporan, cara pencarian/filter arsip, dan kontak bantuan jika ada kendala.

## Ringkasan Fungsi Utama
1. Meninjau laporan yang masuk dari divisi Operasional, Pemeliharaan, dan Safety/K3.
2. Menandatangani (menyetujui) laporan secara digital sehingga berstatus diarsipkan.
3. Menelusuri arsip/riwayat seluruh laporan yang sudah disetujui.
4. Tidak memiliki akses membuat/mengedit laporan — perannya murni sebagai approver (pemisahan tugas dari petugas pembuat laporan).
