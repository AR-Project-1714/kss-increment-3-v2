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
