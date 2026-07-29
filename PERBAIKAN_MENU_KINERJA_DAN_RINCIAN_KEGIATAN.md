# Perbaikan Menu Kinerja Operasi dan Rincian Kegiatan

**Dokumen acuan:**  
- Arahan Pak Mustari melalui percakapan WhatsApp.  
- `RANCANGAN_PERFORMA_KEGIATAN.md`  
- `kinerja-dan-rincian-kegiatan.md`

**Tujuan:**  
Menyesuaikan menu **Kinerja Operasi** dan **Rincian Kegiatan** dengan arahan terbaru Pak Mustari tanpa mengubah bagian sistem yang masih berjalan dengan baik.

---

## 1. Ringkasan Arahan Pak Mustari

Pak Mustari memberikan arahan bahwa tabel kegiatan yang telah tersedia digunakan untuk menyusun:

1. Kinerja operasi.
2. Rincian kegiatan.

Penyajian data harus dibuat **terpisah berdasarkan jenis kegiatan** dan tidak digabungkan menjadi satu analisis umum.

Arahan utama yang perlu diterapkan:

- Kinerja Operasi menampilkan realisasi pekerjaan mulai Januari sampai bulan berjalan.
- Analisis pada Kinerja Operasi dibuat terpisah untuk setiap jenis kegiatan.
- Rincian Kegiatan menampilkan data bulan berjalan.
- Analisis pada Rincian Kegiatan juga dibuat terpisah untuk setiap jenis kegiatan.
- Bagian **Kapal Dilayani** pada Kinerja Operasi tidak perlu dibuat.
- Panel atau rincian khusus **Pemuatan Pupuk Kantong** pada menu Rincian Kegiatan tidak perlu dibuat.

---

## 2. Interpretasi Perbaikan

### 2.1 Menu Kinerja Operasi

Menu Kinerja Operasi tetap berfungsi sebagai halaman analisis kinerja divisi operasi, tetapi cakupan periodenya diubah menjadi:

> **Januari sampai bulan berjalan pada tahun yang dipilih.**

Data tidak lagi hanya disajikan sebagai gabungan seluruh kegiatan. Setiap indikator harus dapat dibaca berdasarkan jenis kegiatan.

Analisis yang ditampilkan untuk setiap jenis kegiatan:

1. Tren tonase.
2. Komposisi kegiatan.
3. Tonase per shift.
4. Rasio kerusakan.
5. Perbandingan regu.
6. Beban kerja.
7. Sebaran kegiatan per shift.
8. Karyawan dengan jumlah jam lembur terbanyak.
9. Karyawan yang paling sering lembur.

### 2.2 Menu Rincian Kegiatan

Menu Rincian Kegiatan digunakan untuk melihat kondisi kegiatan pada:

> **Bulan yang sedang berjalan.**

Analisis dibuat terpisah berdasarkan jenis kegiatan dan memuat:

1. Peringkat regu.
2. Beban kerja.
3. Sebaran kegiatan per shift.
4. Karyawan dengan jumlah jam lembur terbanyak.
5. Karyawan yang paling sering lembur.

### 2.3 Bagian yang Dihapus

#### Dari Menu Kinerja Operasi

Hapus atau jangan tampilkan lagi blok:

- Kapal Dilayani.

Penghapusan hanya berlaku pada tampilan dan perhitungan khusus blok tersebut. Data kapal pada laporan harian tidak perlu dihapus dari database.

#### Dari Menu Rincian Kegiatan

Hapus atau jangan tampilkan lagi panel:

- Pemuatan Pupuk Kantong.

Data Pemuatan Pupuk Kantong tetap dapat digunakan pada Kinerja Operasi, Total Tonase, komposisi, atau perhitungan lain apabila masih relevan. Yang dihilangkan hanya panel rincian khusus pada menu Rincian Kegiatan.

---

## 3. Daftar Jenis Kegiatan

Berdasarkan katalog kegiatan yang sudah tersedia, jenis kegiatan operasional adalah:

| No. | Jenis Kegiatan | Satuan | Ditampilkan pada Kinerja Operasi | Ditampilkan pada Rincian Kegiatan |
|---|---|---:|---:|---:|
| 1 | Pemuatan Pupuk Kantong | Ton | Ya | Tidak |
| 2 | Pemuatan Urea Curah | Ton | Ya | Ya |
| 3 | Bongkar Bahan Baku | Ton | Ya | Ya |
| 4 | Bongkar/Muat Container | Teus | Ya, tetapi dipisahkan dari tonase | Ya |
| 5 | Trucking Pengiriman Pupuk Kantong | Ton | Ya | Ya |

Dengan demikian, menu Rincian Kegiatan menampilkan **empat jenis kegiatan**:

1. Pemuatan Urea Curah.
2. Bongkar Bahan Baku.
3. Bongkar/Muat Container.
4. Trucking Pengiriman Pupuk Kantong.

---

## 4. Aturan Data yang Harus Dipertahankan

### 4.1 Status Laporan

Hanya laporan dengan status berikut yang dihitung:

- `submitted`
- `acknowledged`
- `approved`

Laporan berstatus `draft` tidak ikut dalam perhitungan.

### 4.2 Pemisahan Ton dan Teus

- Pemuatan Pupuk Kantong, Pemuatan Urea Curah, Bongkar Bahan Baku, dan Trucking menggunakan satuan **Ton**.
- Bongkar/Muat Container menggunakan satuan **Teus**.
- Nilai Teus tidak boleh dijumlahkan ke Total Tonase.
- Grafik, label, tooltip, tabel, dan ekspor harus menampilkan satuan secara eksplisit.

### 4.3 Nilai Shift

Perhitungan periode harus menggunakan kolom nilai shift saat ini, seperti:

- `qty_loading_current`
- `qty_current`
- `cob`

Kolom nilai sebelumnya atau akumulasi shift sebelumnya tidak boleh dijumlahkan ulang sebagai realisasi periode.

### 4.4 Filter

Filter yang tetap tersedia:

- Tahun atau periode.
- Regu.
- Shift.

Untuk Kinerja Operasi, periode bawaan adalah Januari sampai bulan berjalan.  
Untuk Rincian Kegiatan, periode bawaan adalah awal sampai akhir bulan berjalan.

---

## 5. Rancangan Menu Kinerja Operasi

### 5.1 Toolbar

Toolbar memuat:

- Tahun.
- Regu.
- Shift.
- Tombol Terapkan.
- Tombol Reset.
- Tombol Ekspor.

Rentang tanggal otomatis:

```text
Tanggal awal   : 1 Januari pada tahun terpilih
Tanggal akhir  : tanggal hari ini, apabila tahun terpilih adalah tahun berjalan
Tanggal akhir  : 31 Desember, apabila yang dipilih adalah tahun sebelumnya
```

### 5.2 Ringkasan Utama

Kartu ringkasan dapat tetap memuat indikator utama yang masih relevan, misalnya:

- Total Tonase.
- Tonase per Shift.
- Rasio Kerusakan.
- Total Laporan.

Kartu **Kapal Dilayani** dihapus.

### 5.3 Analisis per Jenis Kegiatan

Setiap kegiatan ditampilkan melalui tab, pilihan kartu, atau panel yang terpisah.

Untuk setiap kegiatan, tampilkan:

#### A. Tren Tonase atau Volume

- Menampilkan data Januari sampai bulan berjalan.
- Untuk kegiatan bersatuan Ton, gunakan label Ton.
- Untuk Container, gunakan label Teus.

#### B. Komposisi Kegiatan

- Komposisi hanya membandingkan nilai dengan satuan yang sama.
- Container tidak boleh dicampurkan ke diagram komposisi Ton.
- Apabila komposisi ditampilkan di dalam panel satu kegiatan, komposisi dapat berupa subjenis, tujuan, atau data lain yang memang tersedia.

#### C. Tonase atau Volume per Shift

- Dikelompokkan berdasarkan shift.
- Container tetap menggunakan Teus.
- Penamaan shift yang berbeda perlu dinormalisasi agar tidak memecah kelompok yang sama.

#### D. Rasio Kerusakan

- Ditampilkan hanya pada kegiatan yang memiliki data kerusakan dan dasar perhitungan yang valid.
- Jangan menampilkan `0%` apabila tidak ada dasar perhitungan.
- Gunakan tanda `–` atau keterangan “Belum ada dasar perhitungan”.

#### E. Perbandingan Regu

- Membandingkan seluruh regu untuk kegiatan aktif.
- Blok ini boleh mengabaikan filter regu agar fungsi perbandingan tetap berjalan.
- Filter shift dan periode tetap diterapkan.

#### F. Beban Kerja

Tampilkan sekurang-kurangnya:

- Rata-rata personil per shift.
- Jumlah jam lembur.
- Jumlah entri lembur.
- Jumlah relief atau pengganti.
- Ketepatan waktu pelaporan.

#### G. Sebaran per Shift

Tampilkan distribusi kegiatan atau laporan berdasarkan:

- Pagi.
- Sore.
- Malam.

#### H. Jam Lembur Terbanyak

Peringkat karyawan berdasarkan total durasi lembur.

#### I. Paling Sering Lembur

Peringkat karyawan berdasarkan jumlah entri atau frekuensi lembur.

---

## 6. Rancangan Menu Rincian Kegiatan

### 6.1 Periode

Periode bawaan:

```text
Tanggal awal  : tanggal 1 bulan berjalan
Tanggal akhir : tanggal hari ini
```

Apabila sistem mengizinkan bulan lain dipilih, rentang mengikuti awal dan akhir bulan yang dipilih.

### 6.2 Kegiatan yang Ditampilkan

Menu hanya memuat empat tab atau panel:

1. Pemuatan Urea Curah.
2. Bongkar Bahan Baku.
3. Bongkar/Muat Container.
4. Trucking Pengiriman Pupuk Kantong.

Tab **Pemuatan Pupuk Kantong** dihapus dari halaman ini.

### 6.3 Isi Tiap Panel

Setiap panel memuat:

- Peringkat regu.
- Beban kerja.
- Sebaran per shift.
- Jam lembur terbanyak.
- Karyawan yang paling sering lembur.

Tabel detail kapal atau rit boleh dipertahankan hanya apabila masih dibutuhkan oleh pengguna dan tidak bertentangan dengan arahan Pak Mustari. Fokus utama revisi tetap pada lima indikator di atas.

### 6.4 Kondisi Tanpa Data

- Blok yang seluruh nilainya kosong tidak perlu ditampilkan.
- Jangan membuat grafik berisi nilai nol seluruhnya.
- Tampilkan pesan yang jelas, misalnya:

> Belum ada data kegiatan pada bulan berjalan.

---

## 7. Perubahan Teknis yang Disarankan

### 7.1 Service

Berkas utama:

```text
app/Services/OperationalPerformanceService.php
```

Perubahan:

1. Tambahkan metode untuk menghasilkan rentang Januari sampai bulan berjalan.
2. Tambahkan metode untuk menghasilkan rentang bulan berjalan.
3. Pastikan katalog kegiatan dapat membedakan:
   - kegiatan untuk Kinerja Operasi;
   - kegiatan untuk Rincian Kegiatan.
4. Tambahkan penanda katalog, misalnya:

```php
'showOnPerformance' => true,
'showOnActivityDetail' => false,
```

Untuk Pemuatan Pupuk Kantong:

```php
'showOnPerformance' => true,
'showOnActivityDetail' => false,
```

5. Hapus pemanggilan query khusus Kapal Dilayani dari payload Kinerja Operasi apabila sudah tidak dipakai di tempat lain.
6. Gunakan agregasi per kegiatan, regu, dan shift agar jumlah query tidak bertambah secara berlebihan.
7. Pertahankan pemisahan `Ton` dan `Teus`.

### 7.2 Controller

Berkas:

```text
app/Http/Controllers/ManajerController.php
```

Perubahan:

1. Tetapkan periode default Kinerja Operasi menjadi Januari sampai bulan berjalan.
2. Tetapkan periode default Rincian Kegiatan menjadi bulan berjalan.
3. Validasi kunci panel Rincian Kegiatan agar Pemuatan Pupuk Kantong tidak dapat diakses melalui URL langsung.
4. Sesuaikan kunci cache agar memasukkan:
   - jenis halaman;
   - rentang tanggal;
   - regu;
   - shift;
   - jenis kegiatan.

### 7.3 View

Berkas yang kemungkinan terpengaruh:

```text
resources/views/manajer/performa.blade.php
resources/views/manajer/kegiatan.blade.php
resources/views/manajer/partials/performance-toolbar.blade.php
resources/views/manajer/partials/activity-strip.blade.php
resources/views/manajer/partials/activity-detail.blade.php
```

Perubahan:

- Hapus kartu, grafik, dan tabel Kapal Dilayani dari Kinerja Operasi.
- Ubah label periode Kinerja Operasi menjadi “Januari sampai bulan berjalan”.
- Hapus tab atau kartu Pemuatan Pupuk Kantong dari Rincian Kegiatan.
- Tampilkan empat kegiatan yang masih diizinkan.
- Pastikan setiap panel menunjukkan satuan yang benar.
- Jangan merender blok kosong.

### 7.4 JavaScript

Berkas:

```text
public/js/components/charts.js
```

Perubahan:

- Sesuaikan daftar tab yang dapat dimuat.
- Jangan meminta panel Pemuatan Pupuk Kantong.
- Pertahankan lazy loading panel.
- Hindari permintaan ulang ketika tab yang sama dibuka kembali.
- Bersihkan instance grafik sebelum mengganti isi panel apabila diperlukan.

### 7.5 Ekspor Excel

Berkas:

```text
app/Services/PerformanceExportService.php
```

Perubahan:

- Hapus bagian Kapal Dilayani dari ekspor Kinerja Operasi.
- Sesuaikan sheet Rincian Kegiatan agar tidak memuat Pemuatan Pupuk Kantong.
- Pastikan periode pada ekspor sama dengan periode yang tampil di halaman.
- Pertahankan label Ton dan Teus secara eksplisit.

---

## 8. Batasan Perbaikan

Perbaikan ini tidak boleh:

1. Mengubah form input Laporan Operasi.
2. Menghapus data kegiatan lama.
3. Mengubah struktur database tanpa kebutuhan yang benar-benar terbukti.
4. Mengubah PDF laporan harian.
5. Mengubah ekspor laporan harian.
6. Menggabungkan Ton dan Teus.
7. Mengganggu halaman Arsip atau modul lain.
8. Menghapus Pemuatan Pupuk Kantong dari perhitungan Kinerja Operasi.
9. Mengubah sistem otorisasi manajer yang sudah berjalan.
10. Menurunkan performa halaman secara signifikan.

---

## 9. Kriteria Penerimaan

Perbaikan dianggap selesai apabila:

- [x] Kinerja Operasi menggunakan periode Januari sampai bulan berjalan. — preset `tahun-berjalan`, TC-MGR-14
- [x] Rincian Kegiatan menggunakan periode bulan berjalan. — TC-MGR-15
- [x] Analisis Kinerja Operasi dapat dibaca terpisah untuk setiap jenis kegiatan. — TC-MGR-11
- [x] Kapal Dilayani tidak lagi tampil pada Kinerja Operasi. — TC-MGR-16
- [~] ~~Pemuatan Pupuk Kantong tidak lagi tampil pada Rincian Kegiatan.~~ **Dibatalkan 28 Juli 2026** atas permintaan langsung: tabnya dikembalikan. Mekanismenya tetap ada di katalog (`showOnActivityDetail`), tinggal diubah bila arahannya berubah lagi. — TC-MGR-10
- [x] Pemuatan Pupuk Kantong tetap dihitung pada Kinerja Operasi apabila datanya memenuhi status. — TC-MGR-18
- [x] Container tetap menggunakan satuan Teus dan tidak masuk Total Tonase. — TC-MGR-18, TC-MGR-19
- [x] Perbandingan regu, beban kerja, sebaran shift, jam lembur, dan frekuensi lembur tersedia sesuai arahan.
- [x] Filter regu dan shift tetap berfungsi. — TC-MGR-20
- [x] Ekspor mengikuti data dan periode halaman. — TC-MGR-22
- [~] Endpoint panel hanya melayani kunci yang ditandai tampil pada menunya; Pemuatan Pupuk Kantong kini termasuk yang diizinkan. — TC-MGR-17
- [x] Blok tanpa data tidak menghasilkan grafik kosong. — TC-MGR-21
- [x] Seluruh pengujian yang masih relevan lulus. — 201 tes lulus
- [x] Tidak ada migrasi database apabila tidak diperlukan. — tidak ada migrasi baru
- [x] Tidak ada perubahan pada form dan laporan harian.

### Tambahan 28 Juli 2026

- [x] **Ringkasan Kegiatan** di Kinerja Operasi mengikuti bentuk rekap yang dipaparkan ke manajemen: bulan berjalan · sebelumnya · akumulasi, dengan pencacah kapal/rit. — TC-MGR-24
- [x] **Ekspor** mendapat sheet pertama "Kinerja Operasional" mengikuti berkas contoh `Performa Operasional.xlsx`. — TC-MGR-08b
- [x] Kontainer dipecah **Bongkar (Empty)** dan **Muat (Full)** pada rekap, memakai `container_items.status` yang sudah terisi — tanpa migrasi dan tanpa menyentuh form.

---

## 10. Skenario Pengujian

### TC-01 — Periode Kinerja Operasi

**Langkah:** Buka Kinerja Operasi tanpa filter tanggal.  
**Hasil:** Data dihitung dari 1 Januari sampai tanggal hari ini.

### TC-02 — Periode Rincian Kegiatan

**Langkah:** Buka Rincian Kegiatan tanpa filter tanggal.  
**Hasil:** Data dihitung dari tanggal 1 sampai tanggal hari ini pada bulan berjalan.

### TC-03 — Kapal Dilayani Dihapus

**Langkah:** Buka Kinerja Operasi.  
**Hasil:** Tidak ada kartu, tabel, atau grafik Kapal Dilayani.

### TC-04 — Pemuatan Pupuk Kantong Dihapus dari Rincian

**Langkah:** Buka Rincian Kegiatan.  
**Hasil:** Hanya empat kegiatan yang ditampilkan.

### TC-05 — Akses URL Langsung

**Langkah:** Akses endpoint panel Pemuatan Pupuk Kantong secara langsung.  
**Hasil:** Sistem mengembalikan 404 atau respons validasi yang sesuai.

### TC-06 — Pemuatan Pupuk Kantong Tetap Masuk Kinerja

**Langkah:** Masukkan laporan Pemuatan Pupuk Kantong berstatus dihitung.  
**Hasil:** Nilainya tetap masuk ke Kinerja Operasi dan Total Tonase.

### TC-07 — Pemisahan Ton dan Teus

**Langkah:** Tambahkan data Container.  
**Hasil:** Nilai Container bertambah pada metrik Teus, tetapi Total Tonase tidak berubah.

### TC-08 — Filter Regu

**Langkah:** Pilih satu regu.  
**Hasil:** Indikator operasional mengikuti regu tersebut, sedangkan blok Perbandingan Regu tetap dapat menampilkan seluruh regu.

### TC-09 — Filter Shift

**Langkah:** Pilih satu shift.  
**Hasil:** Nilai kegiatan mengikuti shift yang dipilih.

### TC-10 — Data Kosong

**Langkah:** Pilih periode tanpa data.  
**Hasil:** Sistem menampilkan pesan kosong yang jelas dan tidak membuat grafik nol.

### TC-11 — Ekspor

**Langkah:** Terapkan filter, lalu ekspor.  
**Hasil:** Rentang, regu, shift, jenis kegiatan, serta satuan sama dengan halaman.

### TC-12 — Performa

**Langkah:** Ukur query halaman sebelum dan setelah perubahan.  
**Hasil:** Jumlah query tidak meningkat secara signifikan dan tidak kembali ke pola query berulang per regu atau shift.

---

## 11. Catatan Penting

Arahan “Pemuatan Pupuk Kantong tidak perlu dibuat” diterapkan pada **panel Rincian Kegiatan**, bukan menghapus sumber data tersebut dari keseluruhan sistem.

Arahan “Kapal Dilayani tidak perlu dibuat” diterapkan pada **blok analisis Kinerja Operasi**, bukan menghapus data kapal dari laporan harian.

Apabila Pak Mustari menghendaki penghapusan yang lebih luas, perubahan tersebut perlu dikonfirmasi sebelum data atau perhitungan lain diubah.
