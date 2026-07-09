# Tampilan Petugas Safety (K3)

Peran **Safety** ("Karu Safety") membuat laporan harian keselamatan dan kesehatan kerja (K3).

- **Route prefix**: `/report-safety` (nama route `safety.*`, middleware `role:safety`)
- **Controller**: `app/Http/Controllers/ReportSafetyController.php`
- **Layout**: `resources/views/report-safety/layouts/*` (header sederhana tanpa sidebar, sama seperti tampilan Operasional dan Pemeliharaan)

## Menu & Fungsi

### Index — "Laporan Keselamatan (K3)"
`safety.index` — `resources/views/report-safety/index.blade.php`
Subjudul: "Kelola laporan harian keselamatan & kesehatan kerja: draft, riwayat, dan buat laporan baru." Tombol **Buat Laporan K3**, dengan banner pengingat jika ada draft belum selesai. Dua tab:
- **Draft** — laporan K3 harian yang belum selesai.
- **Riwayat Laporan** — riwayat pengajuan laporan dengan status (Draft, Diserahkan, Disetujui/Diarsipkan).

Setiap item menampilkan ID dokumen (`#K3-YYYY-NNN`), nama hari, dan rentang waktu.

### Buat / Edit Laporan
`safety.create` / `safety.edit` — form satu halaman untuk laporan harian K3, mengacu pada data master "Data Lokasi K3" dan "Data Item K3" yang dikelola admin (lokasi dan item pemeriksaan keselamatan).

### Ekspor & Tampilan Laporan
- `safety.pdf` — cetak laporan dalam format PDF.
- Tampilan hanya-baca laporan yang sudah dibuat.

### Alur Persetujuan
Laporan yang diserahkan direview dan ditandatangani oleh Manajer (lihat `manajer.safety.show/approve`), dengan status berjalan dari Draft → Diserahkan → Disetujui/Diarsipkan.

## Ringkasan Fungsi Utama
1. Membuat laporan harian keselamatan & kesehatan kerja (K3).
2. Mengacu pada data lokasi dan item K3 yang dikelola oleh admin.
3. Menyimpan draft dan melanjutkan pengisian laporan.
4. Menyerahkan laporan untuk ditinjau dan disetujui manajer.
5. Mengunduh laporan dalam format PDF.
