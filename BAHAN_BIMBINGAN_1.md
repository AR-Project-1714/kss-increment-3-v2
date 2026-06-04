# Bahan Bimbingan Skripsi — Pertemuan Ke-1

**Nama / NIM** : Muhammad Arobi / _(isi NIM)_
**Program Studi** : _(isi prodi)_
**Dosen Pembimbing** : _(isi nama dosen)_
**Tanggal Bimbingan** : _(isi tanggal)_

**Judul (usulan)** : Sistem Laporan Operasional KSS — Aplikasi Web Pelaporan Shift Harian Kegiatan Operasional & Pemeliharaan Pelabuhan/Bongkar Muat

> Dokumen ini adalah ringkasan progres untuk bimbingan pertama. Tujuannya: melaporkan apa yang sudah dikerjakan, menunjukkan hasil yang bisa didemokan, lalu meminta arahan untuk langkah berikutnya.

---

## 1. Latar Belakang Singkat (yang akan saya sampaikan)

- Proses pelaporan shift harian di unit operasional sebelumnya dilakukan **manual** (kertas), sehingga sulit diarsip, dicari, dan didistribusikan antar regu.
- Saya membangun aplikasi web internal yang **menggantikan dokumen fisik** menjadi laporan digital terstruktur: pengisian multi-step, serah-terima antar regu, tanda tangan digital, persetujuan manajer, hingga ekspor PDF/Excel.
- Kebutuhan divalidasi langsung ke lapangan (Manajer — Pak Mustari) dan dari analisis dokumen yang benar-benar dipakai di lapangan.

---

## 2. Rumusan Masalah & Tujuan (draf — minta validasi dosen)

**Rumusan masalah (draf):**
1. Bagaimana merancang sistem informasi pelaporan operasional harian yang menggantikan proses manual?
2. Bagaimana menerapkan alur serah-terima dan persetujuan laporan secara digital?
3. Bagaimana mengembangkan sistem secara bertahap (inkremental) agar dapat menampung modul unit kerja yang berbeda karakteristik datanya?

**Tujuan (draf):**
- Menghasilkan aplikasi web pelaporan operasional & pemeliharaan yang terstruktur, dapat diarsip, dan dapat dicari.
- Menerapkan alur status laporan berbasis peran (operasional → manajer → admin).

> **Yang ingin saya tanyakan:** apakah rumusan masalah & tujuan ini sudah sesuai arah, atau perlu dipersempit/diperjelas?

---

## 3. Metode Pengembangan: Model Inkremental

Sistem dibangun dengan **model pengembangan inkremental** — setiap _increment_ menambahkan satu modul fungsional utuh di atas fondasi arsitektur yang sama. Sampai saat ini **dua increment telah selesai dibangun**:

| Increment | Lingkup | Status |
|---|---|---|
| **Increment 1** | Fondasi sistem + **Modul Operasional** + area **Manajer** + area **Admin** | ✅ Selesai |
| **Increment 2** | **Modul Pemeliharaan** (Unit Kerja Pemeliharaan & Peralatan) | ✅ Selesai |

> **Yang ingin saya tanyakan:** apakah model inkremental ini tepat untuk diangkat sebagai metode di Bab 3? Dan berapa increment yang sebaiknya diselesaikan untuk skripsi (cukup 2, atau perlu modul Safety sebagai increment ke-3)?

---

## 4. Apa yang Sudah Selesai Dibangun

### 4.1 Increment 1 — Modul Operasional, Manajer, Admin

**Modul Operasional (petugas regu):**
- Login berbasis _username_ dengan 5 peran: `admin`, `manajer`, `operasional`, `pemeliharaan`, `safety`.
- Form laporan shift harian **multi-step (7 langkah)**: Info Umum, Muat Kantong, Muat Curah, Bongkar, Tracking, Cek Unit, Karyawan.
- Alur status **3 tahap**: `draft → submitted → acknowledged → approved`, dengan **serah-terima antar regu** dan **tanda tangan digital**.
- Fitur bantu: auto-fill shift/jam kerja (WITA), auto-fill karyawan & forklift per regu, simpan draft, pencarian riwayat (termasuk pencarian tanggal Bahasa Indonesia), ekspor **PDF & Excel**.

**Area Manajer:**
- Dashboard statistik, daftar laporan masuk per divisi, **persetujuan final**, arsip + pencarian, pusat bantuan, tampilan _mobile responsive_.

**Area Admin:**
- Dashboard sistem, kelola pengguna (termasuk upload tanda tangan & toggle status), **data master**, manajemen backup, log aktivitas (termasuk pemantauan keamanan login), pusat bantuan.

### 4.2 Increment 2 — Modul Pemeliharaan

- Mendigitalkan dokumen fisik **"Laporan Harian Unit Kerja Pemeliharaan dan Peralatan"** beserta Daftar Hadirnya.
- **Beda karakteristik dengan Operasional**: berbasis **hari (Non Shift)**, **tanpa serah-terima**, alur **2 tahap** `draft → submitted → approved`.
- Form **6 seksi**: Info Umum, Pekerjaan Utama, Pekerjaan Prioritas, Kondisi Unit (penghitung Ready/Rusak otomatis), Daftar Hadir, Pengesahan.
- Persetujuan dilakukan manajer (tab Pemeliharaan), dan arsipnya juga tampil di area admin.
- Master armada & roster **disatukan** ke data master yang sama dengan Operasional agar admin cukup mengelola satu set data.

> Rincian teknis tersedia di [`DOKUMENTASI.md`](DOKUMENTASI.md) dan rancangan modul pemeliharaan di [`PERANCANGAN_MODUL_PEMELIHARAAN.md`](PERANCANGAN_MODUL_PEMELIHARAAN.md).

---

## 5. Teknologi yang Digunakan

- **Backend**: PHP 8.3, Laravel, Eloquent ORM, DomPDF (PDF), PhpSpreadsheet (Excel).
- **Frontend**: Blade, Tailwind CSS, Bootstrap (grid), Vite.
- **Basis data**: SQLite (pengembangan), siap MySQL/MariaDB (produksi).
- **Arsitektur**: pola **MVC**, berbasis peran (_role-based access_).

---

## 6. Bukti / Hasil yang Bisa Ditunjukkan saat Bimbingan

- ▶️ **Demo aplikasi langsung**: login tiap peran (operasional, manajer, admin, pemeliharaan), alur buat → kirim → tanda tangan → approve → arsip.
- 📄 **Contoh keluaran**: laporan operasional & pemeliharaan dalam format **PDF**.
- 🧪 **Pengujian otomatis**: `php artisan test` lulus **23 tests / 182 assertions** (fitur alur utama).
- 📚 **Dokumentasi teknis** yang sudah saya tulis: `DOKUMENTASI.md`, `PERANCANGAN_MODUL_PEMELIHARAAN.md`, `LANDASAN_TEORI_SKRIPSI.md`.

---

## 7. Poin Diskusi & Pertanyaan untuk Dosen Pembimbing

Daftar ini sengaja saya siapkan agar bimbingan terarah:

1. **Judul & ruang lingkup** — apakah judul dan batasan masalah sudah sesuai?
2. **Metode** — apakah model inkremental layak dijadikan metode pengembangan di Bab 3? Bagaimana cara memformalkannya (per increment = satu siklus analisis–rancang–bangun–uji)?
3. **Jumlah increment** — cukup 2 modul (Operasional + Pemeliharaan), atau perlu menambah modul Safety?
4. **Pengujian** — apakah _automated test_ sudah memadai, atau perlu **UAT/black-box testing** bersama pengguna nyata (kuesioner/skenario uji)?
5. **Validasi kebutuhan** — validasi sudah dilakukan ke Manajer; apakah perlu tambahan ke Kasi/Karu pemeliharaan & petugas operasional?
6. **Sistematika penulisan** — konfirmasi kerangka Bab 1–5 dan apa yang menjadi fokus penilaian.
7. **Asumsi yang masih terbuka** (dari rancangan modul pemeliharaan) yang ingin saya konfirmasikan, mis. penyederhanaan **3 → 2 tanda tangan** pada versi digital, apakah perlu dijelaskan eksplisit di bab perancangan.

---

## 8. Rencana Langkah Selanjutnya (usulan saya)

- [ ] Menyelesaikan penulisan **Bab 1–3** (Pendahuluan, Landasan Teori, Analisis & Perancangan).
- [ ] Menyusun **instrumen pengujian** (skenario uji / kuesioner UAT).
- [ ] (Opsional) Menyiapkan **Increment 3 — Modul Safety** bila disetujui.
- [ ] Merapikan diagram (use case, ERD, alur status) untuk dokumentasi skripsi.

---

## 9. Catatan Hasil Bimbingan (diisi saat/ setelah pertemuan)

| No | Arahan / Masukan Dosen | Tindak Lanjut | Target |
|---|---|---|---|
| 1 | | | |
| 2 | | | |
| 3 | | | |

**Kesimpulan bimbingan ke-1:**
_(diisi setelah pertemuan — mis. judul disetujui, lanjut Bab 1, dst.)_

**Tanda tangan dosen pembimbing:** ____________________
