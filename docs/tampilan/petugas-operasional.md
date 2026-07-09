# Tampilan Petugas Operasional

Peran **Operasional** (nama lama: "petugas") adalah petugas lapangan yang membuat laporan harian operasional pelabuhan/gudang: muat kantong, muat curah, bongkar, gudang/turba, cek unit, dan absensi karyawan.

- **Route**: `/report-ops/*` (tanpa prefix role di URL, middleware `role:except,admin,manajer,pemeliharaan,safety` — peran default selain 4 peran lain)
- **Controller**: `app/Http/Controllers/ReportOpsController.php`
- **Layout**: `resources/views/report-ops/layouts/*` (header sederhana, navigasi via tab dalam halaman, tanpa sidebar)

## Menu & Fungsi

### Index — "Laporan Operasional"
`report-ops.index` — `resources/views/report-ops/index.blade.php`
Tombol **Buat Laporan Operasional**. Empat tab:
- **Laporan Masuk** — laporan yang diterima dari regu/shift lain.
- **Draft** — laporan harian belum selesai (auto-save), bisa dilanjutkan via modal "Lanjutkan Draft".
- **Riwayat Laporan** — riwayat laporan yang sudah dibuat/diserahkan petugas, dilengkapi pencarian.
- **Laporan Diterima** — laporan hasil serah terima dari shift lain, dilengkapi pencarian.

Setiap baris laporan menampilkan ID dokumen (`#OPS-YYYY-NNN`), badge shift (Pagi/Sore/Malam), regu pengirim/penerima, dan status (Draft, Diserahkan, Diterima, Diarsipkan). Aksi: lihat, edit (jika masih draft/diserahkan dan milik sendiri atau admin), hapus, tanda tangani, ekspor PDF/Excel.

### Buat / Edit Laporan
`report-ops.create` / `report-ops.edit` — form wizard 7 langkah (`resources/views/report-ops/sections/`):
1. **Info Umum** — tanggal, shift, regu, info kapal.
2. **Muat Kantong** — pemuatan kargo berkantong.
3. **Muat Curah** — pemuatan kargo curah.
4. **Bongkar** — kegiatan bongkar muat, termasuk data petugas yang bertugas.
5. **Gudang/Turba** — kegiatan pergudangan/turun barang.
6. **Cek Unit** — checklist inspeksi unit/truk (mengacu ke Data Truck/Unit dari admin).
7. **Karyawan** — absensi/roster karyawan (mengacu ke Data Karyawan dari admin).

Laporan bisa disimpan sebagai draft atau diserahkan (submit), lalu ditandatangani secara digital sebelum diserahterimakan ke shift berikutnya.

### Ekspor & Tampilan Laporan
- `report-ops.pdf` — cetak laporan dalam format PDF (kertas laporan resmi).
- `report-ops.excel` — ekspor ke Excel.
- `report-ops.show` — tampilan laporan siap cetak/hanya-baca.

## Ringkasan Fungsi Utama
1. Membuat laporan harian operasional melalui wizard 7 langkah.
2. Menyimpan draft otomatis dan melanjutkannya nanti.
3. Serah terima laporan antar shift/regu (kirim & terima).
4. Menandatangani laporan secara digital sebelum diserahkan ke manajer.
5. Mengunduh laporan dalam format PDF/Excel.
