# Panduan Pengguna — Sistem Laporan KSS

Dokumen ini menjelaskan cara menggunakan aplikasi untuk setiap peran:
**Admin**, **Manajer**, **Karu Operasi** (Operasional), **Kasi Pemeliharaan**, dan **Karu Safety (K3)**.

> Catatan: tampilan dapat sedikit berbeda tergantung pembaruan, tetapi alur dan menu utama mengikuti panduan ini.

---

## Daftar Isi

1. [Masuk ke Aplikasi](#1-masuk-ke-aplikasi)
2. [Navigasi Umum](#2-navigasi-umum)
3. [Peran & Hak Akses](#3-peran--hak-akses)
4. [Status & Alur Laporan](#4-status--alur-laporan)
5. [Panduan Admin](#5-panduan-admin)
6. [Panduan Manajer](#6-panduan-manajer)
7. [Panduan Karu Operasi (Operasional)](#7-panduan-karu-operasi-operasional)
8. [Panduan Kasi Pemeliharaan](#8-panduan-kasi-pemeliharaan)
9. [Panduan Karu Safety (K3)](#9-panduan-karu-safety-k3)
10. [Tips & Pemecahan Masalah](#10-tips--pemecahan-masalah)

---

## 1. Masuk ke Aplikasi

1. Buka alamat aplikasi di browser.
2. Pada halaman **Masuk**, isi **Username** dan **Password** yang diberikan admin.
3. Klik **Masuk**.

Setelah berhasil, Anda otomatis diarahkan ke halaman sesuai peran:

| Peran | Halaman awal |
|---|---|
| Admin | Dashboard Sistem |
| Manajer | Dashboard Manajer |
| Karu Operasi | Halaman Laporan Operasional |
| Kasi Pemeliharaan | Halaman Laporan Pemeliharaan |
| Karu Safety | Halaman Laporan K3/Safety |

> Akun **nonaktif** tidak dapat masuk. Jika tidak bisa login, hubungi admin untuk memeriksa status akun dan password.

**Keluar (Logout):** tekan tombol **Logout** di bagian bawah sidebar kiri.

---

## 2. Navigasi Umum

Bagian ini berlaku untuk semua peran.

- **Sidebar kiri** — menu utama. Tombol panah (« / ») pada navbar untuk menciutkan/melebarkan sidebar. Di layar HP, tombol menu membuka/menutup sidebar.
- **Mode terang/gelap** — tombol di pojok kanan atas navbar untuk berganti tema. Pilihan tersimpan otomatis.
- **Notifikasi (toast)** — pesan keberhasilan/kegagalan muncul sejenak di atas layar setelah suatu tindakan.
- **Pusat Bantuan** — Admin dan Manajer memiliki menu Pusat Bantuan berisi panduan singkat di dalam aplikasi (gunakan kolom pencarian dan tab untuk berpindah topik).
- **Tabel di layar kecil** — tabel dan tab dapat digeser ke samping (horizontal) bila tidak muat.

---

## 3. Peran & Hak Akses

Setiap akun hanya bisa membuka halaman miliknya sendiri.

| Peran | Akses utama |
|---|---|
| **Admin** | Seluruh data sistem: arsip 3 divisi, log aktivitas, kelola pengguna, data master, dan backup. **Tidak mengisi laporan.** |
| **Manajer** | Meninjau & menandatangani laporan masuk dari 3 divisi (Operasional, Pemeliharaan, Safety), serta mengelola arsip. |
| **Karu Operasi** | Membuat & mengelola laporan operasional shift, melakukan serah terima antar regu, dan menandatangani laporan masuk dari regu lain. |
| **Kasi Pemeliharaan** | Membuat & mengelola laporan pemeliharaan. |
| **Karu Safety (K3)** | Membuat & mengelola laporan K3/safety. |

---

## 4. Status & Alur Laporan

Status menjelaskan posisi laporan dalam proses.

| Status sistem | Tampilan | Arti |
|---|---|---|
| `draft` | **Draft** | Tersimpan oleh petugas, belum dikirim. Tidak tampil di arsip/dashboard manajer. |
| `submitted` | **Diserahkan** | Sudah dikirim petugas; menunggu proses berikutnya. |
| `acknowledged` | **Diterima** | *Khusus Operasional:* sudah diterima & ditandatangani regu tujuan; siap ditandatangani manajer. |
| `approved` | **Diarsipkan** | Sudah ditandatangani manajer dan resmi masuk Arsip. |

**Alur per divisi:**

- **Operasional:** `Draft → Diserahkan → Diterima (regu tujuan) → Diarsipkan (manajer)`
- **Pemeliharaan & Safety:** `Draft → Diserahkan → Diarsipkan (manajer)` — **tanpa** tahap "Diterima".

> **Penyimpanan otomatis:** laporan yang sedang diisi akan otomatis tersimpan sebagai **Draft** bila Anda keluar/sesi terputus, sehingga bisa dilanjutkan kemudian.

---

## 5. Panduan Admin

Admin menjaga data dan sistem, bukan mengisi laporan.

### 5.1 Dashboard Sistem
Ringkasan kondisi sistem melalui empat kartu:

- **Total Pengguna Aktif** — jumlah akun aktif yang bisa login.
- **Kapasitas Storage Terpakai** — persentase pemakaian penyimpanan backup terhadap kapasitas 30 GB.
- **Status Backup Terakhir** — hasil backup paling baru ("Belum Ada" bila belum pernah dibuat).
- **Kejadian Keamanan Hari Ini** — jumlah peristiwa keamanan hari ini (idealnya 0).

Di bawahnya ada ringkasan **aktivitas terbaru** (cuplikan dari Log Aktivitas).

### 5.2 Arsip Laporan
Kumpulan laporan dari ketiga divisi dalam satu tempat.

1. **Cari** lewat kolom pencarian (ID dokumen, tanggal, regu, nama kapal, karyawan, atau isi laporan).
2. **Persempit** dengan filter Tanggal, Divisi, Regu, Shift, dan Status; atur urutan Terbaru/Terlama; tombol **Reset** mengembalikan ke awal.
3. **Lihat** (pratinjau), **Unduh** (PDF), atau **Hapus** laporan.

> ⚠️ **Hapus bersifat permanen** dan tercatat di Log Aktivitas. Unduh dulu PDF bila masih diperlukan.

### 5.3 Log Aktivitas
Rekam jejak tindakan penting. Jenis aktivitas: **Update** (tambah/ubah data), **Delete** (hapus data), **Backup** (buat/unduh/hapus/permintaan restore), dan **Security** (kejadian keamanan).
Saring dengan pencarian (deskripsi/IP/nama) serta filter Tanggal, Role, dan Jenis (menampilkan hingga 60 entri terbaru).

### 5.4 Kelola Pengguna
Membuat & merawat akun untuk manajer dan petugas tiap divisi.

1. Klik **Tambah Pengguna** atau ikon edit pada baris pengguna.
2. Isi: **Nama**, **Username** (unik), **Email** (opsional — dibuat otomatis bila kosong), **Role**, **Regu/Group**, dan **Status**. **Password** minimal 6 karakter (saat edit, kosongkan bila tidak ingin mengganti).
3. **Tanda tangan** (opsional): unggah file **PNG** maksimal **2 MB**; dipakai pada laporan yang memerlukan paraf pengguna tersebut.

- **Aktif/Nonaktif** — gunakan toggle status untuk menonaktifkan akun tanpa menghapusnya (akun nonaktif tidak bisa login).
- ⚠️ Demi keamanan, **akun admin yang sedang dipakai tidak bisa dinonaktifkan/dihapus sendiri** — minta admin lain bila perlu.

### 5.5 Data Master
Data acuan yang dipakai berulang saat petugas mengisi laporan. Enam tab (lewat submenu sidebar atau tab di halaman):

- **Data Karyawan** — NPK, nama, regu, jabatan, divisi, waktu kerja. Menjadi pilihan nama di laporan.
- **Data Unit** — kendaraan/alat berat. Nama unit dibentuk dari Tipe + Nomor unit; kategori "Masuk Cek Unit Operasional" menentukan unit yang muncul pada cek harian operasional.
- **Data Truck** — daftar truck beserta plat dan keterangan.
- **Data Inventaris** — barang/perlengkapan beserta kategori dan stok.
- **Data Lokasi K3** & **Data Item K3** — lokasi dan item pemeriksaan untuk laporan Safety; bisa diaktif/nonaktifkan.

Tiap tab punya pencarian dan filter cepat (mis. Regu/Divisi/Jabatan untuk Karyawan; Tipe/Kategori untuk Unit).

> Perubahan data master **tidak mengubah laporan lama** yang sudah dibuat. Tetap hati-hati menghapus data yang masih sering dipakai agar pilihan pada laporan baru tidak hilang.

### 5.6 Manajemen Backup
Mengamankan data dan meringankan penyimpanan server. Bedakan tiga hal:

- **Backup Manual** — membuat cadangan saat itu juga (cocok sebelum perubahan besar).
- **Jadwal Backup** — atur Frekuensi (Harian/Mingguan/Bulanan), Jam, Retensi (14–90 hari), dan Target penyimpanan.
- **Backup Tahunan** — mengarsipkan **seluruh laporan tahun sebelumnya** ke satu file ZIP, lalu **menghapusnya dari sistem** untuk menghemat penyimpanan.

> ⚠️ **Backup tahunan menghapus laporan dari database** setelah arsip ZIP dibuat & diverifikasi. **Selalu unduh file ZIP-nya** lalu simpan ke penyimpanan lokal Anda. Fitur ini hanya aktif bila sudah masuk tahun baru dan masih ada laporan tahun sebelumnya.
>
> ⚠️ **Restore tidak otomatis.** Menekan Restore hanya mencatat permintaan ke log; pemulihan data dilakukan **manual** oleh admin server (unduh file lalu impor ke database).

---

## 6. Panduan Manajer

Manajer meninjau laporan masuk, memberi tanda tangan final, dan mengelola arsip dari tiga divisi.

### 6.1 Dashboard Manajer
Berisi kartu ringkasan dan daftar **laporan masuk** dari tiga divisi. Gunakan **tab divisi** (Semua, Operasional, Pemeliharaan, Safety) untuk menyaring laporan yang menunggu tanda tangan. Angka pada tab menunjukkan jumlah laporan masuk.

### 6.2 Menandatangani Laporan
1. Pada dashboard, pilih tab divisi dan temukan laporan yang menunggu.
2. Klik **Lihat** untuk meninjau pratinjau laporan dan tanda tangan sebelumnya.
3. Klik tombol **tanda tangan** untuk mengesahkan — sistem mencatat Anda sebagai penyetuju beserta waktunya.
4. Status berubah menjadi **Diarsipkan**, laporan pindah ke menu **Arsip Laporan**, dan PDF finalnya disiapkan.

### 6.3 Arsip Laporan
Menampilkan laporan yang sudah ditandatangani/diarsipkan. Anda dapat **mencari**, **meninjau**, **mengunduh**, atau **menghapus** arsip. Tersedia kolom pencarian dan filter (tanggal, divisi, regu, shift, status) serta urutan Terbaru/Terlama.

### 6.4 Batas Akses
Akun manajer hanya diarahkan ke halaman manajer. Halaman petugas (operasional, pemeliharaan, safety) tidak dapat diakses.

> Jika laporan **belum muncul** di dashboard: untuk Operasional biasanya belum ditandatangani regu penerima; untuk Pemeliharaan/Safety biasanya laporan masih draft dan belum diserahkan petugas.

---

## 7. Panduan Karu Operasi (Operasional)

Karu Operasi membuat laporan operasi harian per shift, menyerahkannya ke regu penerima, sekaligus menerima & menandatangani laporan dari regu lain.

### 7.1 Halaman Utama
Terdiri dari tiga tab:

- **Laporan Masuk** — laporan dari regu lain yang dikirim ke regu Anda untuk ditinjau & ditandatangani.
- **Draft** — laporan yang belum diserahkan (bisa dilanjutkan atau dihapus).
- **Riwayat Laporan** — laporan yang sudah Anda buat/serahkan.

Tombol **Buat Laporan** untuk membuat laporan baru.

### 7.2 Membuat Laporan Operasional
Form terdiri dari enam langkah (gunakan tombol **Lanjut**/**Kembali**, atau tab di atas form):

1. **Info Umum** — tanggal, shift, regu pembuat, dan regu penerima (tujuan serah terima).
2. **Muat Kantong** — kegiatan muat barang dalam kantong.
3. **Muat Curah** — kegiatan muat curah.
4. **Bongkar** — kegiatan bongkar.
5. **Gudang & Turba** — kegiatan gudang dan turun barang.
6. **Cek Unit** — pemeriksaan unit operasional.

Pada akhir pengisian:
- **Simpan Sebagai Draft** — menyimpan tanpa mengirim (status **Draft**).
- **Serahkan** — mengirim laporan ke **regu penerima** (status **Diserahkan**).

### 7.3 Menerima & Menandatangani Laporan Masuk
1. Buka tab **Laporan Masuk**.
2. Tinjau laporan dari regu pengirim.
3. **Tanda tangani** untuk menerima — status berubah menjadi **Diterima**, lalu laporan diteruskan ke manajer untuk pengesahan akhir.

### 7.4 Mengelola Draft & Riwayat
- **Lanjutkan Draft** — membuka kembali draft dengan data terakhir tersimpan.
- **Hapus Draft** — menghapus draft (tidak bisa dilanjutkan lagi).
- **Riwayat** — lihat dan **unduh** laporan dalam bentuk **PDF** atau **Excel**.

> Laporan yang sedang diisi akan otomatis tersimpan sebagai **Draft** bila sesi terputus.

---

## 8. Panduan Kasi Pemeliharaan

Kasi Pemeliharaan membuat laporan pemeliharaan dan menyerahkannya langsung ke manajer.

### 8.1 Halaman Utama
Berisi daftar laporan pemeliharaan (termasuk **Draft**) dan tombol **Buat Laporan**.

### 8.2 Membuat Laporan Pemeliharaan
Form terdiri dari lima langkah:

1. **Info Umum** — informasi tanggal dan data umum laporan.
2. **Pekerjaan Utama** — daftar pekerjaan pemeliharaan utama.
3. **Pekerjaan Prioritas** — pekerjaan yang diprioritaskan.
4. **Kondisi Unit** — kondisi unit/alat saat ini.
5. **Daftar Hadir** — kehadiran karyawan.

Pada akhir pengisian:
- **Simpan Sebagai Draft** — status **Draft**.
- **Serahkan** — status **Diserahkan**; laporan **langsung muncul di dashboard manajer** untuk ditandatangani (tanpa tahap "Diterima").

### 8.3 Setelah Diserahkan
Laporan yang sudah ditandatangani manajer berstatus **Diarsipkan**. Anda dapat mengunduh **PDF** laporan.

> Laporan yang sedang diisi otomatis tersimpan sebagai **Draft** bila sesi terputus.

---

## 9. Panduan Karu Safety (K3)

Karu Safety membuat laporan K3/safety dan menyerahkannya langsung ke manajer.

### 9.1 Halaman Utama
Berisi riwayat laporan K3 (termasuk **Draft**) dan tombol **Buat Laporan**.

### 9.2 Membuat Laporan K3
Form terdiri dari empat langkah:

1. **Info Umum** — tanggal dan data umum laporan.
2. **Inspeksi K3** — pemeriksaan item K3 per lokasi (QTY, kondisi, dan rekomendasi). Daftar lokasi & item berasal dari Data Master (Lokasi K3 & Item K3).
3. **Kegiatan** — kegiatan K3 yang dilakukan.
4. **Kejadian** — pencatatan kejadian/insiden bila ada.

Pada akhir pengisian:
- **Simpan Sebagai Draft** — status **Draft**.
- **Serahkan** — status **Diserahkan**; laporan **langsung muncul di dashboard manajer** untuk ditandatangani (tanpa tahap "Diterima").

### 9.3 Setelah Diserahkan
Laporan yang sudah ditandatangani manajer berstatus **Diarsipkan**. Anda dapat mengunduh **PDF** laporan.

> Bila item/lokasi inspeksi yang dibutuhkan tidak ada di form, minta admin menambahkannya pada Data Master (Lokasi K3 / Item K3).

---

## 10. Tips & Pemecahan Masalah

| Kendala | Yang perlu dicek |
|---|---|
| Tidak bisa login | Pastikan username/password benar dan akun berstatus **aktif** (hubungi admin). |
| Laporan tidak muncul di dashboard manajer | Operasional: belum ditandatangani regu penerima. Pemeliharaan/Safety: masih draft/belum diserahkan. |
| Area tanda tangan kosong | File tanda tangan akun belum diunggah — minta admin melengkapinya di Kelola Pengguna (format **PNG**, maks **2 MB**). |
| Unduhan PDF terasa lama saat pertama | PDF dibuat dari isi laporan; proses berikutnya memakai file yang sudah tersimpan sehingga lebih cepat. |
| Pilihan karyawan/unit/item tidak ada | Minta admin menambah/memperbarui **Data Master** terkait. |
| Akses dari HP | Gunakan tombol menu di navbar untuk membuka/menutup sidebar; tabel dan tab bisa digeser horizontal. |
| Data terhapus tidak sengaja | Penghapusan bersifat permanen; pemulihan hanya mungkin lewat backup oleh admin server. |

---

*Dokumen panduan untuk Sistem Laporan KSS.*
