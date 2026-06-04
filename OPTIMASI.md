# Catatan Optimasi Program Pelaporan Harian PT KSS

> Dokumen ini menjelaskan perbaikan & optimasi yang dilakukan pada aplikasi
> (lanjutan dari Increment 1 — refactor). Ditulis dengan bahasa sederhana
> supaya mudah dipahami, termasuk oleh yang bukan programmer.
>
> **Tanggal:** 29 Mei 2026
> **Konteks:** aplikasi internal, dipakai maksimal ~15 orang, di-deploy di VPS.

---

## Ringkasan Singkat

Yang dikerjakan dibagi jadi 5 area besar:

1. **Mencegah kehilangan data** saat backup tahunan.
2. **Membersihkan kode kembar (duplikat)** supaya gampang dirawat.
3. **Membuat fitur backup otomatis benar-benar jalan** (sebelumnya cuma tampilan).
4. **Perbaikan kecil** yang bikin aplikasi lebih rapi & hemat.
5. **Menyamakan fitur pencarian Admin** dengan fitur pencarian Manajer.

**Hasil terukur:**

| Indikator | Sebelum | Sesudah |
|---|---|---|
| Test otomatis | 25 (2 di antaranya contoh kosong bawaan) | **25 test berisi, 246 pemeriksaan, semua lulus** |
| File form `create` & `edit` | ~2.960 baris **masing-masing** (hampir kembar 100%) | ~10 & ~21 baris (isi dipindah ke 1 berkas bersama) |
| `ManajerController` | 746 baris | ~370 baris |
| Backup otomatis terjadwal | Cuma tampilan, tidak jalan | **Benar-benar berjalan** |
| Pencarian arsip Admin | Dangkal | **Sedalam pencarian Manajer + saran otomatis** |

> Catatan: setiap perubahan diuji dengan rangkaian test otomatis. Semua tetap
> hijau (lulus) di tiap langkah, jadi tidak ada fitur lama yang rusak.

---

## Daftar Isi

1. [Mencegah kehilangan data saat backup tahunan](#1-mencegah-kehilangan-data-saat-backup-tahunan)
2. [Membersihkan kode kembar (duplikat)](#2-membersihkan-kode-kembar-duplikat)
3. [Backup otomatis yang benar-benar berjalan](#3-backup-otomatis-yang-benar-benar-berjalan)
4. [Perbaikan kecil tapi penting](#4-perbaikan-kecil-tapi-penting)
5. [Pencarian Admin disamakan dengan Manajer](#5-pencarian-admin-disamakan-dengan-manajer)
6. [Yang perlu dilakukan saat deploy](#6-yang-perlu-dilakukan-saat-deploy)
7. [Yang sengaja belum dikerjakan (jujur)](#7-yang-sengaja-belum-dikerjakan-jujur)

---

## 1. Mencegah kehilangan data saat backup tahunan

**Masalahnya:**
Fitur "Backup Tahunan" bekerja seperti ini: semua laporan tahun lalu dibungkus
ke satu file ZIP, **lalu dihapus dari database** supaya server lebih ringan.
Bahayanya, dulu penghapusan langsung dilakukan tanpa memastikan file ZIP-nya
benar-benar jadi dan tidak rusak. Kalau pas proses bungkus ada gangguan
(misal disk penuh), file arsip bisa rusak — tapi laporannya **terlanjur
dihapus**. Datanya hilang permanen.

**Solusinya:**
Sekarang, setelah ZIP dibuat, sistem **membuka ulang dan memeriksa** apakah
file arsip valid dan berisi. Baru kalau lolos pemeriksaan, laporan dihapus dari
database. Kalau arsip gagal/rusak, laporan **tidak jadi dihapus** dan admin
diberi pesan untuk mencoba lagi.

**Manfaatnya:** mustahil kehilangan data laporan gara-gara arsip yang gagal.

*(Berkas terkait: `AdminV2Controller.php` bagian `annualBackup`.)*

---

## 2. Membersihkan kode kembar (duplikat)

Bayangkan punya 3 fotokopi resep masakan yang sama. Kalau resep perlu diubah,
Anda harus mengubah ketiganya — dan gampang lupa salah satu, sehingga jadi
beda-beda. Itulah masalah "kode kembar".

### a. Logika yang sama di banyak tempat

**Masalahnya:** logika pencarian laporan (termasuk pengurai kata kunci tanggal
berbahasa Indonesia seperti "24 mei", ~80 baris) dan label-label tampilan
(shift, status, nomor dokumen) **disalin mentah-mentah** di 3 file controller
(Operasional, Manajer, Admin). Total ratusan baris kembar.

**Solusinya:** semua logika kembar itu dipindah ke 2 "kotak alat bersama"
(disebut *trait*):
- `ResolvesReportMeta` — label shift/status, nomor dokumen, nama file, dll.
- `SearchesReports` — mesin pencarian + pengurai tanggal.

Ketiga controller sekarang **memakai kotak alat yang sama**.

**Manfaatnya:** kalau ada perubahan logika, cukup ubah **satu tempat**, dan
ketiga halaman otomatis ikut benar. Tidak ada lagi risiko "diperbaiki di satu
halaman, lupa di halaman lain".

### b. Form Buat & Edit yang hampir 100% sama

**Masalahnya:** file form **Buat laporan** (`create`) dan **Edit laporan**
(`edit`) masing-masing ~2.960 baris, dan isinya **hampir identik** — beda cuma
~39 baris (judul, tombol, alamat tujuan simpan). Lebih parah: keduanya sudah
mulai "melenceng" satu sama lain (ada aturan tampilan & teks yang kebetulan
diperbaiki di satu file tapi tidak di file satunya, plus ada teks yang rusak
encoding-nya).

**Solusinya:** isi form dipindah ke **satu berkas bersama**
(`partials/report-form.blade.php`). File `create` dan `edit` sekarang tinggal
beberapa baris yang cuma mengatur perbedaannya (judul, tombol, tujuan simpan).

**Manfaatnya:**
- Ubah form sekali, otomatis berlaku untuk Buat **dan** Edit.
- Perbedaan tak sengaja & teks rusak otomatis hilang karena sumbernya satu.
- ~3.000 baris kode kembar lenyap.

*(Halaman Edit yang tadinya tidak punya test, kini diberi test supaya aman.)*

---

## 3. Backup otomatis yang benar-benar berjalan

**Masalahnya:** di halaman Backup ada pengaturan jadwal ("Harian / Mingguan /
Bulanan", jam, masa simpan). Tapi itu **cuma tampilan** — tidak ada yang
benar-benar menjalankannya. Tombol "Restore" juga hanya mencatat permintaan,
tidak melakukan apa-apa. Jadi pengguna bisa salah mengira backup berjalan
otomatis padahal tidak.

**Solusinya:**

1. **Backup otomatis sekarang nyata.** Dibuatkan perintah `backup:run` dan
   penjadwal yang membaca pengaturan admin (frekuensi & jam). Jadi kalau admin
   set "Harian jam 02:00", backup beneran dibuat tiap hari jam 02:00.

2. **Masa simpan (retensi) berfungsi.** Misalnya retensi "30 Hari" → backup
   otomatis yang lebih tua dari 30 hari dihapus sendiri, supaya storage tidak
   menumpuk. (Backup manual & arsip tahunan tidak ikut terhapus — itu urusan
   admin.)

3. **Pembersihan otomatis terjadwal.** Draft laporan & saran kapal yang
   kadaluarsa kini juga dibersihkan terjadwal tiap hari, bukan cuma saat ada
   orang membuka halaman.

4. **Restore dibuat jujur.** Karena file backup hanya berisi sebagian data
   (mustahil memulihkan semuanya dengan aman secara otomatis), tombol Restore
   sekarang memberi pesan jelas: pemulihan harus dilakukan manual oleh admin
   server. Tidak lagi seolah-olah otomatis.

**Manfaatnya:** fitur backup jadi sesuai kenyataan — yang otomatis benar-benar
otomatis, yang manual dijelaskan apa adanya.

> ⚠️ **Penting:** agar penjadwal berjalan di VPS, perlu dipasang **satu baris
> cron** (lihat [bagian 6](#6-yang-perlu-dilakukan-saat-deploy)).

*(Berkas terkait: `app/Services/SystemBackupService.php`,
`app/Console/Commands/RunScheduledBackup.php`,
`app/Console/Commands/PruneStaleReports.php`, `routes/console.php`.)*

---

## 4. Perbaikan kecil tapi penting

| Yang diperbaiki | Penjelasan sederhana |
|---|---|
| **Pencarian saran lebih hemat** | Saat menampilkan saran pencarian, dulu sistem ikut memuat banyak data anak laporan yang **tidak pernah dipakai**. Sekarang dimuat seperlunya saja → lebih cepat & ringan. |
| **Data kontainer "bandel" diperbaiki** | Ada baris data bongkar kontainer yang bisa gagal tersimpan karena salah nama kolom pengecekan (`type` padahal seharusnya `status`). Sudah diluruskan. |
| **Label dashboard dibuat akurat** | Kartu "Login Gagal Hari Ini" sebenarnya menghitung **semua** kejadian keamanan (bukan cuma login gagal). Diganti jadi "Kejadian Keamanan Hari Ini" agar tidak menyesatkan. |
| **Bersih-bersih file contoh** | Dua file test contoh bawaan Laravel yang kosong dihapus. |

---

## 5. Pencarian Admin disamakan dengan Manajer

**Masalahnya:** fitur pencarian di halaman **Arsip Laporan Admin** masih
seadanya — hanya bisa mencari berdasarkan shift, regu, status, tanggal, dan
nama pembuat. Sementara halaman **Manajer** sudah jauh lebih pintar.

**Solusinya:** pencarian Admin dibuat **sama persis seperti Manajer**:

1. **Pencarian lebih dalam.** Sekarang bisa mencari sampai ke **isi laporan**:
   nama kapal, agen, dermaga, kegiatan bongkar, data turba, unit, nama
   karyawan, dan seterusnya — bukan cuma judul/tanggal.

2. **Paham kata kunci tanggal Indonesia.** Ketik "mei", "24 apr", atau
   "21 mei 2026" → sistem mengerti dan mencari tanggal yang sesuai.

3. **Saran otomatis (autocomplete).** Saat mengetik, muncul dropdown berisi
   saran laporan teratas. Tinggal klik untuk langsung membuka pencariannya.

4. **Filter instan di halaman.** Mengetik langsung menyaring baris yang sedang
   tampil; tekan Enter untuk mencari ke **seluruh arsip** (lintas halaman).

5. **Tombol bersihkan (✕)** untuk mengosongkan pencarian dengan cepat.

**Manfaatnya:** admin bisa menemukan laporan secepat dan sefleksibel manajer,
tanpa harus ingat persis judul atau tanggalnya.

*(Berkas terkait: `AdminV2Controller.php` — fungsi `archiveSuggestions`;
`resources/views/admin/archive.blade.php`; rute baru
`admin.archive.suggestions`.)*

---

## 6. Yang perlu dilakukan saat deploy

Beberapa hal yang **wajib** disiapkan di server VPS agar semua fitur berjalan:

1. **Pasang cron untuk penjadwal** (agar backup & pembersihan otomatis jalan).
   Tambahkan satu baris ini di crontab server:

   ```
   * * * * * cd /path-ke-aplikasi && php artisan schedule:run >> /dev/null 2>&1
   ```

2. **Saat siap produksi**, ubah pengaturan di file `.env`:
   - `APP_DEBUG=false`
   - `APP_ENV=production`

   Ini penting untuk keamanan (agar detail error tidak bocor ke pengguna).
   *(Belum dikerjakan sekarang karena deploy masih lama.)*

3. Jalankan optimasi cache Laravel saat deploy:
   ```
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

## 7. Yang sengaja belum dikerjakan (jujur)

Supaya transparan, ada beberapa hal yang **sengaja ditunda** dengan alasan:

- **Menghapus kolom database yang mubazir** (`user_id` di tabel laporan, yang
  isinya selalu sama dengan `created_by`). Manfaatnya kecil, tapi menyentuh
  banyak file & test. Ditunda agar tidak menambah risiko tanpa untung berarti.

- **Mode "ketat" deteksi pemborosan query** (mencegah lazy-loading). Bisa
  memunculkan error di halaman yang belum diuji dan mengganggu proses
  pengembangan yang sedang berjalan. Bisa diaktifkan nanti kalau diminta.

- **Mengubah `APP_DEBUG`** → ditunda sampai mendekati deploy (lihat bagian 6).

---

## Penutup

Semua perubahan di atas tidak menambah fitur baru yang rumit, melainkan membuat
aplikasi yang sudah ada jadi **lebih aman (anti kehilangan data), lebih mudah
dirawat (tidak ada kode kembar), lebih jujur (fitur sesuai kenyataan), dan lebih
nyaman dipakai (pencarian admin setara manajer)** — sambil memastikan tidak ada
fungsi lama yang rusak lewat 25 test otomatis yang semuanya lulus.
