# Audit Sistem — Sistem Pelaporan KSS

Tanggal audit: 19 Juli 2026
Cakupan: seluruh fitur pada kelima peran (Admin, Manajer, Operasional, Pemeliharaan, Safety), keamanan, konsistensi UI, dan kesehatan kode.

---

## Metodologi

1. **Test suite otomatis** — menjalankan seluruh 151 test (`php artisan test`) sebagai baseline perilaku.
2. **Penelusuran route → controller → view** — memastikan setiap route punya implementasi nyata dan setiap tombol punya tujuan.
3. **Pemindaian pola tombol mati** — mencari `data-confirm` tanpa `data-confirm-submit`/`data-confirm-redirect` (pola bug yang pernah ditemukan pada tombol Ekspor).
4. **Audit keamanan** — rate limiting login, mass assignment, path traversal pada unduhan backup, output Blade tanpa escape (`{!! !!}`), proteksi akun admin terakhir.
5. **Verifikasi aset** — file yang direferensikan view vs yang tercatat di git.

---

## Ringkasan Hasil

| Area | Status |
|---|---|
| Test suite (151 test, 769 assertion) | ✅ Lolos semua (setelah 1 regresi diperbaiki saat audit) |
| Tombol konfirmasi (semua halaman admin) | ✅ Semua punya tujuan (submit/redirect) |
| Rate limiting login (per akun + per IP) | ✅ Ada, dengan pencatatan security event |
| Mass assignment | ✅ Tidak ada `$request->all()`; semua lewat `validate()` |
| Path traversal unduhan backup | ✅ Terjaga (`basename` + tolak `..` + whitelist ekstensi) |
| Proteksi akun admin | ✅ Tidak bisa nonaktifkan diri sendiri / admin aktif terakhir |
| Jadwal otomatis (prune draft + backup) | ✅ Terdaftar di `routes/console.php`, mengikuti pengaturan admin |
| Aset yang direferensikan view | ✅ Semua tercatat di git (commit `b32e8ae`) |
| Output Blade tanpa escape | ✅ Bersih (setelah 1 celah XSS diperbaiki saat audit) |

---

## Temuan yang DIPERBAIKI langsung saat audit

### 1. Regresi: widget Kapasitas Storage mematikan 15 test (500 error) — **DIPERBAIKI**

- **Lokasi:** `app/Http/Controllers/AdminV2Controller.php` → `databaseSizeBytes()`
- **Masalah:** query ukuran database memakai `information_schema.tables` (khusus MySQL/MariaDB). Test suite berjalan di SQLite, sehingga semua halaman yang memuat kartu dashboard admin ber-500. Di produksi MySQL tidak terasa, tapi ini bom waktu bila driver berganti.
- **Perbaikan:** query dibuat sadar-driver (`mysql/mariadb` → information_schema; `sqlite` → `PRAGMA page_count × page_size`; lainnya → 0) dan dibungkus try-catch — widget statistik boleh kurang akurat, tapi tidak boleh mematikan halaman.
- **Verifikasi:** 151/151 test lolos setelah perbaikan.

### 2. Celah stored XSS pada label unit laporan pemeliharaan — **DIPERBAIKI**

- **Lokasi:** `resources/views/pemeliharaan/partials/report-paper.blade.php` → closure `$conditionUnitLabel`
- **Masalah:** `unit_label` (teks bebas ketikan pengguna pemeliharaan) dirender lewat `{!! implode('<br>') !!}` tanpa escape. Halaman ini juga dibuka oleh admin dan manajer, sehingga skrip yang disisipkan pengguna pemeliharaan dapat berjalan di sesi akun yang lebih tinggi.
- **Perbaikan:** setiap cabang closure kini meng-escape dengan `e()` sebelum masuk ke implode. Output raw lain (`$cell` report-ops, `$check` safety, deskripsi log admin) sudah diverifikasi escape di sumber — aman.
- **Verifikasi:** 16 test Maintenance lolos setelah perbaikan.

---

## Temuan lanjutan dan status penerapannya

### A. Manajer tidak punya tombol Ekspor Arsip (paritas fitur) — ✅ DITERAPKAN

Route `manajer.archive.export` + tombol Ekspor di toolbar arsip manajer (unduh langsung tanpa dialog, konsisten dengan tombol unduh manajer lain). Logika ekspor dipindah ke `BuildsDivisionArchive::archiveExportResponse()` sehingga admin dan manajer memakai kode yang sama persis; ekspor manajer juga tercatat di log aktivitas.

### B. Tombol "Restore" backup selalu berakhir pesan gagal — ✅ DITERAPKAN

Tombol kini berbunyi "Minta Restore" dengan dialog yang menjelaskan bahwa sistem hanya mencatat permintaan, dan hasilnya toast sukses ("permintaan dicatat di log aktivitas") — bukan lagi error. Perilaku backend tidak berubah: restore tetap manual oleh admin server.

### C. Tiket bantuan — ✅ DIHAPUS

Atas keputusan pemilik sistem (tidak ada mekanisme tiket): route `admin.help.ticket`, method `storeHelpTicket()`, dan test yang mengujinya dihapus. UI-nya memang sudah tidak pernah ada. Entri log historis bertipe `support` tetap bisa dibaca di halaman Log Aktivitas.

### D. Fallback data dummy di view admin — ⏸ DITUNDA (keputusan pemilik sistem)

Beberapa view admin punya fallback `$stats = $stats ?? [ ...data contoh... ]` (archive, backup, datamaster, index, log). Controller selalu mengirim data asli sehingga fallback tak pernah aktif — tapi bila suatu saat ada jalur render tanpa data, halaman akan menampilkan angka fiktif tanpa tanda apa pun.
**Rekomendasi:** hapus fallback dummy (biarkan error nyata muncul) atau ganti dengan nilai kosong/nol.

### E. Checklist deployment produksi (bukan bug) — ℹ dikonfirmasi pemilik sistem sudah `APP_DEBUG=false` di produksi

Konfigurasi saat ini konfigurasi pengembangan. Sebelum produksi pastikan:
- `APP_DEBUG=false` dan `APP_ENV=production` (sekarang `true`/`local` — stack trace akan bocor ke pengguna bila dibiarkan)
- `APP_URL` diganti dari `http://localhost`
- Cron `schedule:run` terdaftar di server (dibutuhkan backup otomatis & pembersihan draft)
- `php artisan config:cache` + `route:cache` + `view:cache`

---

## Catatan Sehat (tidak perlu tindakan)

- **Alur laporan 3 divisi** (draft → submitted → acknowledged/approved) tercakup test dan konsisten antar divisi.
- **Ekspor arsip & log admin** berfungsi end-to-end dengan filter, pengaman baris kosong, batas 5.000 baris, dan jejak audit.
- **Widget Kapasitas Storage** kini menghitung codebase + dokumen laporan + database, di-cache 5 menit.
- **Middleware peran** menutup semua grup route; test RBAC memverifikasi silang antar peran.
- **Backup terjadwal + retensi** membaca pengaturan admin dari `schedule.json` dengan default aman.
