# Catatan Perbaikan Coding Laporan Operasi

Dokumen ini berisi catatan perbaikan coding berdasarkan analisis PDF **Laporan Shift Harian / Laporan Operasi**. Catatan utama berasal dari bagian yang diberi tanda merah pada laporan, terutama pada halaman 1 dan halaman 2.

---

## 1. Tambahkan Field Dermaga pada Form dan Database

Pada halaman 1, bagian:

- **III. Bongkar Bahan Baku**
- **Bongkar / Muat Container**

terdapat catatan merah: **“tambahkan form isian dermaga”**.

Artinya, sistem perlu menyediakan field input **dermaga** pada form terkait.

### Field yang perlu ditambahkan

```text
dermaga
```

### Bagian yang perlu diperbarui

```text
bongkar_bahan_baku
bongkar_muat_container
```

### Komponen coding yang harus dicek

```text
Migration / database
Model fillable
Form input
Controller store / update
Blade tampilan laporan
Blade PDF laporan
```

### Contoh struktur field

```text
nama_kapal
agent
dermaga
kapasitas
jenis
```

Catatan penting: field **dermaga** jangan hanya ditampilkan di PDF, tetapi harus benar-benar tersedia pada form input dan tersimpan ke database.

---

## 2. Format Jam pada Bongkar / Muat Container Dibuat sebagai Rentang Waktu

Pada bagian **Bongkar / Muat Container**, kolom jam masih tampil seperti berikut:

```text
23:00
23:00
```

Sedangkan catatan pada PDF meminta format jam dibuat seperti berikut:

```text
23:00 - 04:00
```

### Rekomendasi perbaikan

Gunakan dua field waktu:

```text
jam_mulai
jam_selesai
```

Lalu pada tampilan PDF ditampilkan menjadi:

```php
{{ $item->jam_mulai }} - {{ $item->jam_selesai }}
```

### Alternatif sederhana

Jika ingin dibuat manual, bisa gunakan satu field:

```text
jam_kerja
```

Contoh isi:

```text
23:00 - 04:00
```

Catatan penting: untuk shift malam, validasi waktu jangan menganggap `04:00` lebih kecil dari `23:00` sebagai kesalahan, karena jam kerja tersebut melewati pergantian hari.

---

## 3. Kolom Ket / Keterangan pada Container Harus Bisa Diisi Manual

Pada halaman 1, bagian **Bongkar / Muat Container**, kolom **Ket** diberi catatan agar dapat diisi manual.

Saat ini contoh isi kolom terlihat seperti:

```text
Empty
Full
```

### Perbaikan yang perlu dilakukan

```text
Tambahkan input keterangan pada setiap baris data container.
Jangan hardcode nilai Empty / Full.
Jangan kunci kolom keterangan jika user ingin mengetik keterangan lain.
```

### Field yang perlu dicek atau ditambahkan

```text
keterangan
```

### Contoh input pada form

```html
<input type="text" name="keterangan[]">
```

Atau jika data tidak berbentuk banyak baris:

```html
<textarea name="keterangan"></textarea>
```

---

## 4. Bagian Bawah Tracking Pengiriman Pupuk Kantong Perlu Dibuat Field Form

Pada halaman 1, bagian bawah tabel **IV. Tracking Pengiriman Pupuk Kantong**, terdapat area yang diberi kotak merah.

Bagian tersebut berisi:

```text
Tally Gudang Kirim
Operator Forklift
FL No
Jam Kerja
Tally Gudang Terima
Driver
TRL No
```

### Perbaikan yang perlu dilakukan

```text
Pastikan semua field tersebut tersedia di form input.
Pastikan semua field tersimpan ke database.
Pastikan semua field ditampilkan kembali pada PDF laporan.
Jika data kosong, tetap tampilkan label dan garis kosong agar layout laporan tetap rapi.
```

### Field yang disarankan

```text
tally_gudang_kirim
operator_forklift
fl_no
jam_kerja_tracking
tally_gudang_terima
driver
trl_no
```

Untuk `jam_kerja_tracking`, format yang disarankan:

```text
23:00 - 04:00
```

---

## 5. Field Jam Kerja pada Bagian Kegiatan Lain Belum Terbaca

Pada halaman 2, bagian **Kegiatan Lain**, terdapat catatan merah:

```text
Isian jam kerja tdk terbaca pada lembar kerja
```

Artinya, kemungkinan data jam kerja sudah diinput, tetapi tidak tampil di PDF. Bisa juga nama field pada form, controller, database, dan blade PDF tidak konsisten.

### Bagian yang perlu dicek

```text
name input di form
request di controller
nama kolom database
fillable di model
pemanggilan variabel di blade PDF
```

### Contoh masalah umum

```php
// Form
name="jam_kerja"

// Controller
$request->jamKerja

// PDF
$kegiatan->jam
```

Contoh di atas tidak konsisten, sehingga data bisa tidak terbaca.

### Rekomendasi nama field

Gunakan satu nama yang konsisten:

```text
jam_kerja
```

Lalu pada PDF:

```php
{{ $kegiatan->jam_kerja }}
```

---

## 6. Kolom Keterangan pada Karyawan OP-7 Harus Diisi Manual

Pada halaman 2, bagian **Karyawan OP-7**, terdapat catatan:

```text
Kolom keterangan buat diisi secara manual
```

Artinya, tabel operator OP-7 perlu mempunyai field **keterangan** yang bisa diisi manual oleh user.

### Struktur data yang perlu dicek

```text
nama
no_forklift
area_kerja
masuk
keluar
keterangan
```

### Perbaikan yang perlu dilakukan

```text
Tambahkan field keterangan pada form OP-7.
Tambahkan kolom keterangan di database jika belum ada.
Tambahkan keterangan pada fillable model.
Pastikan controller menyimpan dan memperbarui keterangan.
Pastikan keterangan tampil pada PDF laporan.
```

Catatan penting: kolom **keterangan** jangan otomatis dikosongkan atau hanya muncul berdasarkan status tertentu. User harus bisa mengetik manual.

---

## 7. Kolom Menggantikan / Ket pada Daftar Pengganti Operator Harus Manual

Pada halaman 2, bagian **Daftar Pengganti Operator Yang Tidak Masuk**, terdapat catatan merah pada kolom:

```text
Menggantikan / Ket
```

### Field yang disarankan

```text
nama_pengganti
no_forklift
area_kerja
masuk
keluar
menggantikan_ket
```

### Perbaikan yang perlu dilakukan

```text
Tambahkan field menggantikan_ket atau keterangan.
Tampilkan input manual pada form.
Simpan data ke database.
Tampilkan kembali data tersebut pada PDF laporan.
```

---

## 8. Cek Urutan Data Alat / Forklift pada Halaman 2

Pada bagian **Keadaan Peralatan dan Kendaraan Operasional**, nomor data terlihat tidak sepenuhnya berurutan. Contohnya, setelah nomor 45 muncul nomor 48, lalu 46, 47, 20, 21, dan seterusnya.

Kemungkinan data diambil berdasarkan `id`, bukan berdasarkan nomor urut laporan.

### Perbaikan yang disarankan

Gunakan pengurutan berdasarkan kolom nomor urut:

```php
$orderBy('no_urut', 'asc')
```

Atau sediakan kolom khusus:

```text
sort_order
```

Tujuannya agar urutan data di PDF tetap sesuai dengan format laporan manual.

---

## 9. Perbaiki Layout PDF agar Bagian Tanda Tangan Tidak Terpisah Sendiri

Pada halaman 3, hanya terdapat bagian tanda tangan:

```text
Mengetahui
Diterima / Melanjutkan
Dilaksanakan / Menyerahkan
```

Jika halaman tanda tangan ini tidak sengaja dibuat terpisah, berarti layout PDF perlu dirapikan agar bagian tanda tangan tidak terdorong ke halaman baru.

### Perbaikan CSS PDF

```css
.signature-section {
    page-break-inside: avoid;
}

.table-section {
    page-break-inside: avoid;
}
```

### Alternatif perbaikan layout

```text
Kecilkan margin halaman.
Kecilkan ukuran font tabel.
Kurangi padding pada cell tabel.
Atur tinggi baris agar lebih hemat ruang.
Pastikan bagian tanda tangan tidak dipisahkan dengan page-break manual.
```

---

## 10. Prioritas Perbaikan

Urutan pengerjaan yang disarankan:

1. Tambahkan field **dermaga** pada Bongkar Bahan Baku dan Bongkar / Muat Container.
2. Ubah format **jam** menjadi rentang waktu seperti `23:00 - 04:00`.
3. Buat kolom **keterangan** bisa diisi manual pada Container, OP-7, dan Pengganti Operator.
4. Perbaiki field **jam kerja kegiatan lain** yang belum muncul di PDF.
5. Perbaiki bagian bawah **Tracking Pengiriman Pupuk Kantong** agar semua data berasal dari form dan database.
6. Rapikan urutan data alat / forklift.
7. Perbaiki page break agar tanda tangan tidak terpisah sendiri.

---

## 11. Checklist Coding

Gunakan checklist berikut saat revisi coding:

```text
[ ] Migration tambah kolom baru
[ ] Model tambahkan fillable
[ ] Form input ditambahkan
[ ] Controller store menerima field baru
[ ] Controller update menerima field baru
[ ] Validasi request diperbarui
[ ] Blade tampilan form diperbarui
[ ] Blade PDF laporan diperbarui
[ ] Data lama tetap aman setelah migration
[ ] Export PDF dites ulang
[ ] Data kosong tetap tampil rapi di laporan
[ ] Format jam lintas hari dites, contoh 23:00 - 04:00
[ ] Kolom keterangan manual dites dengan beberapa isi berbeda
[ ] Urutan data alat / forklift dicek kembali
[ ] Layout tanda tangan dicek setelah export PDF
```

---

## 12. Kesimpulan

Masalah utama pada laporan ini bukan hanya pada tampilan PDF, tetapi juga kemungkinan pada struktur input dan penyimpanan data. Beberapa field penting perlu ditambahkan atau diperbaiki, terutama:

```text
dermaga
jam kerja / rentang jam
keterangan manual
field tambahan tracking pengiriman
jam kerja kegiatan lain
```

Setelah field diperbaiki di database, form, controller, dan template PDF, lakukan pengujian ulang dengan membuat satu laporan baru lalu export ke PDF untuk memastikan semua data tampil sesuai format laporan operasi.
