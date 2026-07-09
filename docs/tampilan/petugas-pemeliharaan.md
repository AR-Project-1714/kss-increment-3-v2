# Tampilan Petugas Pemeliharaan

Peran **Pemeliharaan** ("Kasi Pemeliharaan") membuat laporan harian pemeliharaan unit, termasuk pencatatan waktu kerja/absensi tim pemeliharaan.

- **Route prefix**: `/pemeliharaan` (middleware `role:pemeliharaan`)
- **Controller**: `app/Http/Controllers/ReportMaintenanceController.php`
- **Layout**: `resources/views/pemeliharaan/layouts/*` (header sederhana tanpa sidebar, sama seperti tampilan Operasional)

## Menu & Fungsi

### Index — "Laporan Pemeliharaan"
`pemeliharaan.index` — `resources/views/pemeliharaan/index.blade.php`
Subjudul: "Kelola laporan harian unit pemeliharaan: draft, riwayat, dan buat laporan baru." Tombol **Buat Laporan Pemeliharaan**, dengan banner pengingat jika ada draft belum selesai. Dua tab:
- **Draft** — laporan pemeliharaan harian yang belum selesai, dapat dilanjutkan via modal.
- **Riwayat Laporan** — riwayat pengajuan laporan dengan status (Draft, Diserahkan, Disetujui/Diarsipkan).

Setiap item menampilkan ID dokumen (`#MNT-YYYY-NNN`), nama hari, rentang waktu kerja (jam masuk-keluar dari data absensi), dan status.

### Buat / Edit Laporan
`pemeliharaan.create` / `pemeliharaan.edit` — form satu halaman (bukan wizard bertahap seperti Operasional) untuk laporan pemeliharaan harian, termasuk pencatatan absensi/waktu kerja tim.

### Ekspor & Tampilan Laporan
- `pemeliharaan.pdf` — cetak laporan dalam format PDF.
- Tampilan hanya-baca laporan yang sudah dibuat.

### Alur Persetujuan
Laporan yang diserahkan akan direview dan ditandatangani oleh Manajer (lihat `manajer.pemeliharaan.show/approve`), dengan status berjalan dari Draft → Diserahkan → Disetujui/Diarsipkan.

## Ringkasan Fungsi Utama
1. Membuat laporan harian pemeliharaan unit dalam satu form.
2. Mencatat absensi/waktu kerja tim pemeliharaan.
3. Menyimpan draft dan melanjutkan pengisian laporan.
4. Menyerahkan laporan untuk ditinjau dan disetujui manajer.
5. Mengunduh laporan dalam format PDF.
