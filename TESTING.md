# Rancangan Pengujian Sistem KSS

Dokumen ini berisi rancangan pengujian untuk Sistem Laporan Operasional KSS. Pengujian dibagi menjadi dua metode:

1. **Blackbox Testing** untuk menguji fungsi sistem dari sisi input dan output tanpa melihat kode program.
2. **User Acceptance Testing (UAT)** untuk menilai apakah sistem sudah sesuai kebutuhan pengguna akhir.

Dokumen ini dapat digunakan sebagai bahan Bab Pengujian pada skripsi dan dapat disesuaikan lagi dengan format kampus.

---

## 1. Tujuan Pengujian

Tujuan pengujian pada project ini adalah:

- Memastikan fitur utama berjalan sesuai kebutuhan.
- Memastikan validasi input mencegah data yang salah.
- Memastikan hak akses sesuai role pengguna.
- Memastikan alur laporan dari draft sampai approved berjalan benar.
- Memastikan antarmuka dapat dipahami oleh petugas, manajer, dan admin.
- Memastikan fitur administratif seperti user, data master, backup, dan arsip dapat digunakan.
- Menilai penerimaan pengguna terhadap kemudahan, kejelasan, dan manfaat sistem.

---

## 2. Lingkup Pengujian

Lingkup fitur yang diuji:

| Modul | Fitur yang Diuji |
|---|---|
| Autentikasi | Login, logout, user aktif/nonaktif, redirect role |
| Role dan Akses | Admin, manajer, operasional, pembatasan route |
| Laporan Operasional | Form 7 step, draft, submit, edit, validasi, payload |
| Tanda Tangan | Tanda tangan regu penerima dan status laporan |
| Approval Manajer | Review laporan, approve, arsip, download, hapus |
| Arsip Laporan | Pencarian, filter, pagination, detail, download |
| Admin | Dashboard, user, data master, log, backup, help |
| Export | PDF dan Excel |
| UI/UX | Modal, toast, date-time picker, responsive layout |

---

## 3. Blackbox Testing

Blackbox Testing dilakukan dengan menguji sistem berdasarkan fungsi yang terlihat oleh pengguna. Penguji tidak perlu mengetahui struktur kode, query database, atau proses internal aplikasi.

### 3.1 Aspek Blackbox yang Diuji

#### A. Autentikasi dan Role

Aspek yang dapat diuji:

- Login dengan username dan password benar.
- Login dengan username/password salah.
- Login dengan akun nonaktif.
- Rate limiting berjalan setelah percobaan login gagal berulang.
- Percobaan brute force tercatat dan dapat dilihat admin pada Log Aktivitas.
- Redirect setelah login berdasarkan role.
- Akses halaman yang membutuhkan login.
- Akses admin hanya untuk role admin.
- Akses manajer hanya untuk role manajer/admin sesuai kebutuhan sistem.
- Manajer tidak dapat membuka halaman operasional.
- Logout berjalan tanpa modal konfirmasi.

#### B. Dashboard Operasional

Aspek yang dapat diuji:

- Dashboard operasional tampil setelah user operasional login.
- Tab Laporan Masuk menampilkan laporan yang ditujukan ke regu user.
- Tab Draft menampilkan draft milik user.
- Tab Riwayat menampilkan laporan terkait user.
- Pagination riwayat berjalan.
- Pencarian riwayat dapat menemukan laporan berdasarkan ID, tanggal, shift, regu, dan keyword isi laporan.
- Suggestion pencarian muncul dan dapat dipilih.

#### C. Form Laporan Operasional

Aspek yang dapat diuji:

- Form create laporan dapat dibuka.
- Tanggal Info Umum otomatis terisi tanggal hari ini dan tetap bisa diganti.
- Shift dan jam kerja terisi otomatis sesuai waktu WITA.
- User dapat berpindah antar step.
- Field wajib menampilkan validasi jika kosong.
- Group penerima tidak boleh sama dengan group pengirim.
- Input angka tidak menerima nilai negatif.
- Date-time picker Muat Kantong dan Muat Curah dapat memilih tanggal dan jam 24 jam.
- Tombol Hari Ini dan Hapus pada date-time picker bekerja.
- Menekan Enter dapat lanjut ke input berikutnya sesuai alur input.
- Data tabel dinamis dapat ditambah, diedit, dan dihapus.

#### D. Draft, Submit, Edit, dan Hapus Laporan

Aspek yang dapat diuji:

- Laporan dapat disimpan sebagai draft.
- Draft muncul di tab Draft.
- Draft dapat diedit kembali.
- Laporan draft dapat dihapus dengan modal konfirmasi.
- Laporan dapat disubmit setelah field wajib valid.
- Setelah submit, status berubah menjadi `submitted`.
- Draft lama yang melewati batas waktu dapat dibersihkan sesuai aturan sistem.

#### E. Kapal Aktif dan Data Berkelanjutan

Aspek yang dapat diuji:

- Data kapal aktif muncul sebagai suggestion pada shift berikutnya.
- Kapal yang ditandai selesai tidak muncul lagi sebagai suggestion aktif.
- Data pekerjaan kapal yang belum selesai dapat digunakan kembali.
- Suggestion kapal lama dibersihkan sesuai aturan waktu.

#### F. Tanda Tangan Regu Penerima

Aspek yang dapat diuji:

- Laporan masuk dapat ditandatangani oleh regu penerima.
- User pembuat laporan tidak dapat menandatangani laporannya sendiri.
- User dari regu yang salah tidak dapat menandatangani laporan.
- Setelah ditandatangani, status berubah menjadi `acknowledged`.
- Tanggal/waktu tanda tangan tercatat.
- Modal konfirmasi tanda tangan muncul sebelum eksekusi.

#### G. Approval Manajer

Aspek yang dapat diuji:

- Manajer dapat melihat dashboard manajer.
- Laporan yang sudah ditandatangani regu penerima muncul pada daftar manajer.
- Manajer dapat membuka detail laporan.
- Manajer dapat melakukan approval final.
- Setelah approval, status berubah menjadi `approved`.
- Admin tidak memiliki tombol approve laporan.
- Setelah approve, laporan masuk ke arsip manajer.

#### H. Arsip, Pencarian, Filter, dan Pagination

Aspek yang dapat diuji:

- Arsip laporan menampilkan data laporan.
- Pencarian berdasarkan ID dokumen.
- Pencarian berdasarkan tanggal, termasuk format tanggal berbahasa Indonesia.
- Filter tanggal, regu, shift, dan urutan berjalan.
- Pagination tampil dan berfungsi.
- Tombol lihat laporan membuka detail.
- Tombol download mengunduh PDF.
- Tombol hapus arsip menampilkan modal konfirmasi.

#### I. Export PDF dan Excel

Aspek yang dapat diuji:

- Laporan dapat diexport ke PDF.
- Laporan dapat diexport ke Excel.
- Nama file export sesuai format yang ditentukan.
- Isi dokumen export sesuai data laporan.
- Tombol download memberi feedback/loading.

#### J. Modul Admin

Aspek yang dapat diuji:

- Admin dapat membuka dashboard admin.
- Admin dapat membuka Arsip Laporan.
- Admin dapat membuka Log Aktivitas.
- Admin dapat membuka Kelola Pengguna.
- Admin dapat menambahkan user.
- Admin dapat mengedit user.
- Admin dapat upload tanda tangan PNG user.
- Sistem menolak file tanda tangan selain PNG.
- Admin dapat mengaktifkan/menonaktifkan user melalui toggle.
- Admin dapat menghapus user dengan konfirmasi.
- Admin dapat menambah, mengedit, dan menghapus data master.
- Pencarian data master berjalan otomatis dengan debounce tanpa tombol Cari.
- Admin dapat generate backup manual.
- Admin dapat mengatur jadwal backup.
- Admin dapat download, hapus, dan mencatat restore backup.
- Admin dapat mengirim tiket bantuan.
- Aktivitas admin tercatat di log.

#### K. Feedback UI

Aspek yang dapat diuji:

- Toast sukses muncul setelah aksi berhasil.
- Toast error muncul setelah validasi atau aksi gagal.
- Modal konfirmasi muncul pada aksi sensitif.
- Modal tambah/edit data tampil sesuai kebutuhan.
- Tombol hover/active/focus terlihat jelas.
- Layout tidak rusak pada desktop, tablet, dan mobile.

---

## 4. Contoh Tabel Blackbox Testing

Format tabel yang dapat digunakan:

| Kode | Modul | Skenario | Data Uji | Hasil yang Diharapkan | Status |
|---|---|---|---|---|---|
| BB-01 | Login | Login dengan akun valid | Username dan password benar | User berhasil login dan diarahkan sesuai role | Belum diuji |
| BB-02 | Login | Login dengan password salah | Username benar, password salah | Sistem menampilkan toast error dan tetap di halaman login | Belum diuji |
| BB-03 | Login | Login dengan akun nonaktif | Akun status nonaktif | Sistem menolak login dan menampilkan pesan akun nonaktif | Belum diuji |
| BB-04 | Login | Login gagal berulang melebihi batas | 6 kali password salah pada username/IP sama | Sistem memblokir sementara dan menampilkan pesan terlalu banyak percobaan | Belum diuji |
| BB-05 | Admin Log | Admin melihat percobaan brute force | Filter tipe Keamanan | Log login gagal dan brute force tampil | Belum diuji |
| BB-06 | Role | Manajer membuka `/report-ops` | Akun manajer | Sistem mengarahkan ke dashboard manajer | Belum diuji |
| BB-07 | Role | Operasional membuka `/admin` | Akun operasional | Sistem menolak akses atau memberi response 403 | Belum diuji |
| BB-08 | Operasional | Membuka form laporan | Akun operasional | Form 7 step tampil | Belum diuji |
| BB-09 | Info Umum | Tanggal otomatis | Form create | Tanggal hari ini terisi dan dapat diganti | Belum diuji |
| BB-10 | Info Umum | Group penerima sama dengan pengirim | Group A ke Group A | Sistem menampilkan validasi dan menolak submit final | Belum diuji |
| BB-11 | Validasi | Input angka negatif | Kapasitas `-10` | Sistem menolak atau mengubah nilai menjadi valid | Belum diuji |
| BB-12 | Date-Time | Pilih tanggal dan jam Muat Kantong | Tanggal dan jam valid | Nilai tersimpan dengan format datetime 24 jam | Belum diuji |
| BB-13 | Draft | Simpan laporan sebagai draft | Field minimal draft | Laporan tersimpan dan muncul di tab Draft | Belum diuji |
| BB-14 | Submit | Submit laporan lengkap | Semua field wajib valid | Laporan tersimpan dengan status `submitted` | Belum diuji |
| BB-15 | Edit | Edit draft laporan | Data draft | Perubahan tersimpan dan tampil kembali | Belum diuji |
| BB-16 | Hapus Draft | Hapus draft | Klik hapus dan konfirmasi | Draft terhapus dari daftar | Belum diuji |
| BB-17 | Riwayat | Cari laporan berdasarkan ID | `#OPS-2026-001` | Data laporan terkait tampil | Belum diuji |
| BB-18 | Riwayat | Cari laporan berdasarkan bulan | `Mei 2026` | Laporan bulan Mei 2026 tampil | Belum diuji |
| BB-19 | Signature | Regu penerima tanda tangan | User regu penerima | Status berubah menjadi `acknowledged` | Belum diuji |
| BB-20 | Signature | Pembuat mencoba tanda tangan sendiri | User pembuat laporan | Sistem menolak aksi | Belum diuji |
| BB-21 | Manajer | Manajer approve laporan | Laporan `acknowledged` | Status berubah menjadi `approved` | Belum diuji |
| BB-22 | Admin | Admin membuka arsip | Akun admin | Arsip tampil tanpa tombol approve | Belum diuji |
| BB-23 | Admin User | Tambah user dengan tanda tangan PNG | File PNG valid | User tersimpan dan file masuk `public/signatures` | Belum diuji |
| BB-24 | Admin User | Upload tanda tangan selain PNG | File JPG/PDF | Sistem menolak file | Belum diuji |
| BB-25 | Admin User | Toggle status user | Klik toggle | Status user berubah aktif/nonaktif | Belum diuji |
| BB-26 | Data Master | Cari data master | Keyword karyawan/unit | Hasil berubah otomatis setelah jeda debounce | Belum diuji |
| BB-27 | Data Master | Tambah data master | Data valid | Data tersimpan dan muncul di tabel | Belum diuji |
| BB-28 | Backup | Generate backup manual | Klik generate | File backup dibuat dan toast sukses tampil | Belum diuji |
| BB-29 | Backup | Hapus backup | Klik hapus dan konfirmasi | File backup terhapus | Belum diuji |
| BB-30 | Export | Download PDF laporan | Klik download | File PDF berhasil diunduh | Belum diuji |
| BB-31 | Export | Download Excel laporan | Klik export Excel | File Excel berhasil diunduh | Belum diuji |
| BB-32 | UI | Modal konfirmasi hapus | Klik tombol hapus | Modal muncul sebelum data dihapus | Belum diuji |
| BB-33 | UI | Toast sukses/error | Aksi sukses/gagal | Toast tampil sesuai kondisi | Belum diuji |
| BB-34 | Responsive | Buka sistem di layar kecil | Mobile/tablet | Layout tetap rapi dan dapat digunakan | Belum diuji |
| BB-35 | Info Umum | Pilih shift kedua | Dropdown shift | Opsi tampil sebagai "Shift Sore" dan tersimpan sebagai Sore | Belum diuji |
| BB-36 | Validasi | Input angka desimal satuan ton | Kapasitas `2000.5` | Sistem menerima nilai desimal tanpa error validasi | Belum diuji |
| BB-37 | Karyawan OP.7 | Operator OP.7 ditandai tidak masuk | Keterangan Sakit/Cuti/Tidak Masuk | Baris pengganti otomatis muncul dengan No.Forklift, Area, Masuk, Keluar terisi otomatis | Belum diuji |
| BB-38 | Data Inventaris | Tambah inventaris dengan Jumlah | Nama + Jumlah | Data tersimpan dan Jumlah menjadi qty default pada laporan | Belum diuji |
| BB-39 | Backup Tahunan | Arsipkan laporan tahun lalu | Sudah masuk tahun baru, ada laporan tahun sebelumnya | ZIP `Laporan_Harian_KSS_Tahun_xxxx.zip` dibuat dan laporan tahun tsb dihapus dari sistem | Belum diuji |
| BB-40 | UI Manajer | Konfirmasi TTD dan Download | Klik tombol | Spinner loading tampil selama proses berjalan | Belum diuji |

Kolom **Status** dapat diisi dengan `Berhasil`, `Gagal`, atau `Perlu Perbaikan` setelah pengujian dilakukan.

---

## 5. User Acceptance Testing (UAT)

User Acceptance Testing dilakukan untuk mengetahui apakah sistem sudah diterima oleh pengguna akhir. UAT pada project ini dapat melibatkan tiga kelompok pengguna:

| Kelompok Pengguna | Fokus Pengujian |
|---|---|
| Petugas Operasional | Membuat laporan, menyimpan draft, submit laporan, tanda tangan laporan masuk, mencari riwayat |
| Manajer | Melihat dashboard, meninjau laporan, approve laporan, mencari arsip, download laporan |
| Admin | Mengelola user, data master, arsip, backup, log aktivitas, dan pusat bantuan |

### 5.1 Aspek UAT yang Diuji

#### A. Kesesuaian Fungsi

Pertanyaan utama:

- Apakah fitur yang tersedia sesuai dengan kebutuhan kerja pengguna?
- Apakah alur laporan sesuai dengan proses operasional sebenarnya?
- Apakah pembagian hak akses admin, manajer, dan petugas sudah sesuai?

Aspek project:

- Form laporan 7 step.
- Draft dan submit laporan.
- Tanda tangan regu penerima.
- Approval manajer.
- Kelola user dan data master oleh admin.

#### B. Kemudahan Penggunaan

Pertanyaan utama:

- Apakah menu mudah ditemukan?
- Apakah form mudah dipahami?
- Apakah tombol aksi mudah dikenali?
- Apakah modal dan toast membantu pengguna?

Aspek project:

- Sidebar dan navigasi tab.
- Modal tambah/edit/konfirmasi.
- Toast sukses/error.
- Date-time picker.
- Search dan filter.

#### C. Kecepatan dan Efisiensi

Pertanyaan utama:

- Apakah sistem mempercepat pembuatan laporan?
- Apakah pencarian laporan lebih cepat dibanding proses manual?
- Apakah auto-fill dan data master membantu mengurangi pengetikan ulang?

Aspek project:

- Auto-fill shift dan jam kerja.
- Auto-fill karyawan berdasarkan regu.
- Suggestion kapal aktif.
- Pencarian riwayat dan arsip.
- Debounce search data master.

#### D. Kejelasan Informasi

Pertanyaan utama:

- Apakah status laporan mudah dipahami?
- Apakah tabel arsip mudah dibaca?
- Apakah pesan error/sukses jelas?
- Apakah dokumen PDF/Excel sesuai kebutuhan arsip?

Aspek project:

- Badge status laporan.
- Tabel arsip dan pagination.
- Toast message.
- Export PDF dan Excel.
- Detail laporan.

#### E. Keamanan dan Kontrol Akses

Pertanyaan utama:

- Apakah pengguna hanya dapat mengakses fitur sesuai rolenya?
- Apakah aksi penting membutuhkan konfirmasi?
- Apakah status user aktif/nonaktif membantu kontrol akses?

Aspek project:

- Login dan role.
- Pembatasan akses admin/manajer/operasional.
- Modal konfirmasi aksi hapus/restore/approval.
- Toggle status user.
- Admin tidak dapat approve laporan.

#### F. Tampilan dan Kenyamanan

Pertanyaan utama:

- Apakah tampilan sistem nyaman digunakan?
- Apakah layout rapi di laptop dan perangkat mobile?
- Apakah ukuran input, modal, dan tabel sesuai?

Aspek project:

- Responsive layout.
- Sidebar.
- Tabel arsip.
- Modal/popup.
- Date-time picker.
- Konsistensi icon, warna, dan tombol.

---

## 6. Skenario Tugas UAT

Skenario tugas berikut dapat diberikan kepada responden saat UAT.

### 6.1 Petugas Operasional

| Kode | Tugas UAT | Kriteria Berhasil |
|---|---|---|
| UAT-OP-01 | Login sebagai petugas operasional | Petugas berhasil masuk ke dashboard operasional |
| UAT-OP-02 | Membuat laporan baru sampai step terakhir | Petugas dapat mengisi form tanpa kebingungan besar |
| UAT-OP-03 | Menyimpan laporan sebagai draft | Draft muncul di tab Draft |
| UAT-OP-04 | Mengedit draft laporan | Data draft dapat diperbarui |
| UAT-OP-05 | Submit laporan ke regu penerima | Status laporan menjadi `submitted` |
| UAT-OP-06 | Mencari laporan pada riwayat | Laporan yang dicari muncul |
| UAT-OP-07 | Menandatangani laporan masuk sebagai regu penerima | Status laporan menjadi `acknowledged` |
| UAT-OP-08 | Download laporan PDF/Excel | File berhasil diunduh |

### 6.2 Manajer

| Kode | Tugas UAT | Kriteria Berhasil |
|---|---|---|
| UAT-MJ-01 | Login sebagai manajer | Manajer masuk ke dashboard manajer |
| UAT-MJ-02 | Melihat daftar laporan masuk | Laporan yang perlu ditinjau tampil |
| UAT-MJ-03 | Membuka detail laporan | Detail laporan dapat dibaca |
| UAT-MJ-04 | Menyetujui laporan | Status laporan menjadi `approved` |
| UAT-MJ-05 | Mencari dan memfilter arsip | Arsip tampil sesuai keyword/filter |
| UAT-MJ-06 | Download PDF arsip | File PDF berhasil diunduh |
| UAT-MJ-07 | Membuka pusat bantuan | Informasi bantuan dapat dibaca |

### 6.3 Admin

| Kode | Tugas UAT | Kriteria Berhasil |
|---|---|---|
| UAT-AD-01 | Login sebagai admin | Admin masuk ke dashboard admin |
| UAT-AD-02 | Menambahkan user baru | User tersimpan dan tampil di tabel |
| UAT-AD-03 | Upload tanda tangan PNG user | Preview/file tanda tangan tersimpan |
| UAT-AD-04 | Menonaktifkan user lewat toggle | Status user berubah |
| UAT-AD-05 | Menambah data master | Data master baru tampil di tabel |
| UAT-AD-06 | Mencari data master | Hasil pencarian muncul otomatis |
| UAT-AD-07 | Membuka arsip admin | Arsip tampil dan tidak ada tombol approve |
| UAT-AD-08 | Generate backup manual | File backup dibuat |
| UAT-AD-09 | Membuka log aktivitas | Aktivitas admin dapat dilihat |
| UAT-AD-10 | Mengirim tiket bantuan | Tiket bantuan tercatat |

---

## 7. Contoh Kuesioner UAT

Gunakan skala Likert 1 sampai 5:

| Nilai | Keterangan |
|---|---|
| 1 | Sangat Tidak Setuju |
| 2 | Tidak Setuju |
| 3 | Cukup Setuju |
| 4 | Setuju |
| 5 | Sangat Setuju |

### 7.1 Pernyataan UAT untuk Semua Pengguna

| Kode | Pernyataan | Skor 1-5 |
|---|---|---|
| UAT-Q01 | Sistem mudah dipahami oleh pengguna. | |
| UAT-Q02 | Tampilan menu dan tombol mudah dikenali. | |
| UAT-Q03 | Pesan sukses dan error pada sistem mudah dipahami. | |
| UAT-Q04 | Sistem membantu mempercepat proses kerja dibanding cara manual. | |
| UAT-Q05 | Pencarian dan filter data membantu menemukan informasi. | |
| UAT-Q06 | Tampilan tabel, status, dan badge mudah dibaca. | |
| UAT-Q07 | Modal konfirmasi membantu mencegah kesalahan aksi. | |
| UAT-Q08 | Sistem terasa konsisten pada setiap halaman. | |
| UAT-Q09 | Sistem dapat digunakan dengan nyaman pada perangkat yang digunakan. | |
| UAT-Q10 | Secara keseluruhan, sistem layak digunakan dalam proses kerja. | |

### 7.2 Pernyataan UAT untuk Petugas Operasional

| Kode | Pernyataan | Skor 1-5 |
|---|---|---|
| UAT-OP-Q01 | Form laporan 7 step memudahkan pengisian laporan. | |
| UAT-OP-Q02 | Fitur draft membantu menyimpan laporan yang belum selesai. | |
| UAT-OP-Q03 | Date-time picker memudahkan input tanggal dan jam operasional. | |
| UAT-OP-Q04 | Auto-fill shift, jam kerja, dan data regu membantu mempercepat pengisian. | |
| UAT-OP-Q05 | Fitur tanda tangan laporan masuk mudah digunakan. | |

### 7.3 Pernyataan UAT untuk Manajer

| Kode | Pernyataan | Skor 1-5 |
|---|---|---|
| UAT-MJ-Q01 | Dashboard manajer memberikan ringkasan laporan yang jelas. | |
| UAT-MJ-Q02 | Detail laporan cukup jelas untuk proses pemeriksaan. | |
| UAT-MJ-Q03 | Proses approval laporan mudah dilakukan. | |
| UAT-MJ-Q04 | Arsip laporan memudahkan pencarian laporan lama. | |
| UAT-MJ-Q05 | Download PDF arsip sesuai kebutuhan dokumentasi. | |

### 7.4 Pernyataan UAT untuk Admin

| Kode | Pernyataan | Skor 1-5 |
|---|---|---|
| UAT-AD-Q01 | Fitur kelola pengguna mudah digunakan. | |
| UAT-AD-Q02 | Toggle status user memudahkan aktivasi/nonaktivasi akun. | |
| UAT-AD-Q03 | Upload tanda tangan PNG mudah dipahami. | |
| UAT-AD-Q04 | Fitur data master memudahkan pengelolaan data referensi. | |
| UAT-AD-Q05 | Fitur backup dan log aktivitas membantu kontrol administrasi sistem. | |

---

## 8. Perhitungan Hasil UAT

Rumus persentase kelayakan:

```text
Persentase = (Total Skor Diperoleh / Skor Maksimal) x 100%
```

Keterangan:

- Total Skor Diperoleh = jumlah seluruh skor responden.
- Skor Maksimal = jumlah responden x jumlah pertanyaan x skor tertinggi.
- Skor tertinggi pada skala Likert = 5.

Contoh:

```text
Jumlah responden = 10
Jumlah pertanyaan = 10
Skor tertinggi = 5
Skor maksimal = 10 x 10 x 5 = 500
Total skor diperoleh = 430
Persentase = (430 / 500) x 100% = 86%
```

Kategori hasil:

| Persentase | Kategori |
|---|---|
| 0% - 20% | Sangat Tidak Layak |
| 21% - 40% | Tidak Layak |
| 41% - 60% | Cukup Layak |
| 61% - 80% | Layak |
| 81% - 100% | Sangat Layak |

Kriteria penerimaan yang disarankan:

- Sistem dianggap diterima jika hasil UAT minimal berada pada kategori **Layak**.
- Target ideal project ini adalah minimal **81%** atau kategori **Sangat Layak**.
- Jika ada aspek dengan nilai rendah, aspek tersebut menjadi dasar perbaikan sistem.

---

## 9. Format Rekap Hasil Pengujian

### 9.1 Rekap Blackbox

| Total Skenario | Berhasil | Gagal | Persentase Berhasil | Kesimpulan |
|---|---|---|---|---|
| 40 | 0 | 0 | 0% | Belum diuji |

Rumus:

```text
Persentase Berhasil = (Jumlah Skenario Berhasil / Total Skenario) x 100%
```

### 9.2 Rekap UAT

| Kelompok Responden | Jumlah Responden | Total Skor | Skor Maksimal | Persentase | Kategori |
|---|---:|---:|---:|---:|---|
| Petugas Operasional | 0 | 0 | 0 | 0% | Belum diuji |
| Manajer | 0 | 0 | 0 | 0% | Belum diuji |
| Admin | 0 | 0 | 0 | 0% | Belum diuji |
| Keseluruhan | 0 | 0 | 0 | 0% | Belum diuji |

---

## 10. Contoh Kesimpulan Pengujian

Contoh narasi jika hasil baik:

> Berdasarkan hasil Blackbox Testing, seluruh fungsi utama sistem berjalan sesuai dengan hasil yang diharapkan. Pengujian mencakup autentikasi, pengelolaan laporan operasional, tanda tangan digital, approval manajer, arsip laporan, export dokumen, pengelolaan user, data master, backup, dan feedback antarmuka. Selain itu, hasil User Acceptance Testing menunjukkan bahwa sistem diterima oleh pengguna karena memudahkan proses pencatatan, pencarian, persetujuan, dan pengarsipan laporan.

Contoh narasi jika ada perbaikan:

> Berdasarkan hasil pengujian, sebagian besar fungsi sistem telah berjalan sesuai kebutuhan. Namun, terdapat beberapa aspek yang perlu diperbaiki, seperti tampilan pada ukuran layar tertentu atau kejelasan pesan validasi pada beberapa form. Hasil tersebut digunakan sebagai dasar penyempurnaan sistem sebelum digunakan secara penuh.
