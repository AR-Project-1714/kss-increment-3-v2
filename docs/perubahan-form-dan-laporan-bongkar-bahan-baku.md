# Perubahan Form dan Laporan Bongkar Bahan Baku

## Latar Belakang

Perubahan ini dibuat berdasarkan arahan Pak Mustari mengenai pencatatan kegiatan bongkar bahan baku. Bongkar bahan baku menggunakan beberapa jenis kemasan dengan nilai tonase yang berbeda, sehingga data tiap kemasan harus dicatat dan ditampilkan secara terpisah.

Tahap pertama memisahkan dua kemasan yang paling sering dibongkar, yaitu Jumbo Bag dan Bag 50 Kg, sebagai dua kategori tetap. Masukan lanjutan dari lapangan menyebutkan masih ada kemasan lain yang kadang dibongkar, sehingga kemasan kini **dapat dipilih dan ditambah** — tidak lagi dua kategori tetap.

Petugas tetap memasukkan data dalam satuan **Bag**. Sistem melakukan konversi ke satuan **Ton** secara otomatis, lalu menampilkan kedua satuan tersebut pada laporan.

## Katalog Kemasan

Seluruh kemasan didefinisikan pada satu berkas, `app/Support/MaterialPackaging.php`, dan dibaca oleh form, aturan validasi, tampilan laporan, serta rekap kinerja.

| Kode | Kemasan | Konversi |
| --- | --- | ---: |
| `jumbo_1000` | Jumbo Bag 1 Ton | 1 Bag = 1 Ton |
| `jumbo_1500` | Jumbo Bag 1,5 Ton | 1 Bag = 1,5 Ton |
| `bag_50` | Bag 50 Kg | 20 Bag = 1 Ton |
| `bag_25` | Bag 25 Kg | 40 Bag = 1 Ton |

Rumus konversinya:

```text
Ton = Jumlah Bag × Ton per Bag
```

Contoh:

| Kemasan | Jumlah Bag | Hasil Konversi |
| --- | ---: | ---: |
| Jumbo Bag 1 Ton | 200 Bag | 200 Ton |
| Jumbo Bag 1,5 Ton | 200 Bag | 300 Ton |
| Bag 50 Kg | 1.500 Bag | 75 Ton |
| Bag 25 Kg | 800 Bag | 20 Ton |

Menambah kemasan berikutnya cukup dengan menambah satu entri pada katalog tersebut. Tidak ada berkas lain yang perlu disunting.

### Kemasan Tambahan dari Petugas

Kemasan yang belum pernah tercatat tidak boleh menahan laporan sampai rilis berikutnya. Karena itu pilihan terakhir pada dropdown kemasan adalah **Tambah Kemasan Baru**, yang membuka pop-up berisi:

- **Nama Kemasan** — bebas, maksimal 100 karakter.
- **Perbandingan Bag dan Ton** — dua isian, misalnya `25 Bag = 1 Ton` atau `1 Bag = 1,5 Ton`. Hasil konversinya ditampilkan langsung sebelum disimpan.

Kemasan itu tersimpan pada barisnya dengan kode `custom`, nama sesuai isian, dan faktor hasil perbandingan. Ia langsung tersedia pada seluruh kelompok di laporan yang sedang diisi, tetapi **tidak menambah katalog tetap** — laporan berikutnya memulai lagi dari daftar yang sama.

Penjagaannya:

- Faktor dibatasi antara 0,0001 dan 100 Ton per Bag, di form maupun di server.
- Nama yang sama dengan kemasan katalog selalu memakai faktor katalog, sehingga "Bag 50 Kg" tidak dapat didefinisikan ulang dengan tonase lain.
- Nama yang sudah ada pada daftar ditolak dengan saran memilihnya langsung dari dropdown.

Berbeda dengan kemasan katalog, faktor kemasan tambahan memang berasal dari isian petugas — di situlah nilainya ditentukan. Yang tidak berubah: faktor kemasan katalog tetap milik server dan kiriman form untuknya diabaikan.

## Faktor Konversi Disimpan pada Barisnya

Setiap baris bahan baku menyimpan tiga kolom kemasan:

| Kolom | Isi |
| --- | --- |
| `packaging_type` | Label kemasan, dipakai untuk tampilan dan pencarian laporan |
| `packaging_code` | Kode katalog, penentu kemasan |
| `packaging_factor` | Ton per Bag saat laporan disimpan |

Faktor sengaja dibekukan pada barisnya. Selama tonase dihitung ulang dari nama kemasan, menyunting katalog akan diam-diam menggeser angka laporan yang sudah tersimpan. Dengan faktor tersimpan, laporan lama tetap utuh.

Label dan faktor selalu diambil dari katalog di server berdasarkan kode kemasan. Form tidak pernah mengirim faktor, karena faktor yang dapat dikirim browser berarti tonase yang dapat dikarang.

## Perubahan Form Petugas

Form bongkar bahan baku terdiri dari **kelompok kemasan** yang jumlahnya mengikuti muatan kapal:

- Form kosong terbuka dengan dua kelompok bawaan, yaitu **Jumbo Bag 1 Ton** dan **Bag 50 Kg**, dua kemasan yang paling sering dibongkar.
- Kemasan tiap kelompok dipilih lewat dropdown pada judul kelompok, memakai kontrol dropdown yang sama dengan isian lain pada form ini. Pil konversi di sampingnya ikut berubah mengikuti pilihan.
- Tombol **Tambah Kelompok Kemasan** menambah kelompok baru, dan tombol hapus berikon di kepala kelompok — anatominya sama dengan hapus lokasi pada Inspeksi K3 — menghapus kelompok yang tidak terpakai. Minimal satu kelompok harus tersisa.
- Kemasan yang sudah dipakai satu kelompok tetap tampil pada kelompok lain tetapi tidak dapat dipilih, supaya satu kegiatan tidak punya dua kelompok berkemasan sama.
- Pilihan terakhir dropdown, **Tambah Kemasan Baru**, membuka pop-up pendaftaran kemasan di luar katalog.
- Tiap kelompok dapat diciutkan lewat tombol panah atau dengan mengeklik area kosong kepalanya, mengikuti akordeon lokasi pada Inspeksi K3. Seluruh kelompok terbuka saat form dibuka, tiap kelompok berdiri sendiri, dan kelompok yang baru ditambah selalu terbuka.
- Saat diciutkan, kepala kelompok menampilkan jumlah bahan beserta subtotal **Sekarang** dalam Bag dan Ton, sehingga angkanya tetap dapat dipindai tanpa membuka tabelnya. Ringkasan itu hanya muncul dalam keadaan tertutup karena saat terbuka angkanya sudah ada pada baris Subtotal.

Pada setiap kelompok, petugas mengisi:

- Jenis bahan baku
- Sekarang dalam Bag
- Lalu dalam Bag
- Akumulasi dalam Bag

Ketentuan input:

- Kuantitas harus berupa bilangan bulat.
- Nilai tidak boleh negatif.
- Satuan `Bag` ditampilkan sebagai suffix pada input.
- Nilai `Akumulasi` dihitung oleh sistem.
- Satu kegiatan boleh memakai satu kemasan saja, boleh pula seluruh kemasan katalog.

Subtotal pada form ditampilkan dalam format berikut:

```text
2.500 Bag / 125 Ton
```

Jumlah Bag dibuat lebih menonjol, sedangkan hasil konversi Ton ditampilkan sebagai informasi pendamping. Pada layar sempit, pasangan angka dan satuannya turun baris agar jumlah Bag berdigit banyak — yang lazim pada kemasan 25 Kg — tidak terpotong.

Untuk form bongkar/muat kontainer, satuan kuantitas ditampilkan sebagai **Teus**.

## Kesinambungan Antar Regu

Satu kapal dibongkar lintas shift, dan jenis bahan beserta kemasannya tidak berubah di tengah pembongkaran. Karena itu memilih kapal dari saran operasi kapal — baik lewat serah-terima otomatis maupun saat petugas mengetik nama kapalnya — sekaligus menurunkan rincian bahan baku dari laporan terakhir kapal tersebut:

- Kelompok kemasan disusun ulang persis seperti laporan sebelumnya, termasuk kemasan tambahan beserta faktornya.
- Jenis bahan baku tiap baris ikut terisi.
- Kolom **Lalu** diisi akumulasi terakhir (Sekarang + Lalu), sama seperti penerusan muat kantong.
- Kolom **Sekarang** sengaja dikosongkan, karena itulah yang harus diisi regu yang sedang bertugas.

Baris kosong pada laporan sebelumnya tidak ikut diturunkan. Bila kapal belum punya laporan bahan baku sama sekali, form dibiarkan apa adanya.

## Perubahan Tampilan Laporan

Bagian **IV. Bongkar Bahan Baku** pada pratinjau laporan dan PDF mengikuti struktur tabel operasional yang diberikan melalui Excel.

Struktur kolom laporan:

| Jenis | Kemasan | Sekarang |  | Lalu |  | Akumulasi |  |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: |
|  |  | Bag | Ton | Bag | Ton | Bag | Ton |

Setiap baris bahan baku menampilkan nomor urut dalam kelompoknya, jenis bahan baku, jenis kemasan, serta pasangan Bag dan Ton untuk Sekarang, Lalu, dan Akumulasi.

Urutan kelompok pada laporan mengikuti urutan katalog:

1. Jumbo Bag 1 Ton
2. Jumbo Bag 1,5 Ton
3. Bag 50 Kg
4. Bag 25 Kg
5. Kemasan tambahan yang didaftarkan petugas
6. Data lama yang belum mempunyai informasi kemasan

Setelah setiap kelompok kemasan terdapat baris **JUMLAH**. Subtotal antar kemasan tidak digabung karena masing-masing memiliki faktor konversi yang berbeda.

Contoh subtotal:

| Kemasan | Sekarang |  | Lalu |  | Akumulasi |  |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
|  | Bag | Ton | Bag | Ton | Bag | Ton |
| Jumbo Bag 1 Ton | 59 | 59 | 89 | 89 | 148 | 148 |
| Jumbo Bag 1,5 Ton | 49 | 73,50 | 123 | 184,50 | 172 | 258 |
| Bag 50 Kg | 1.757 | 87,85 | 6.150 | 307,50 | 7.907 | 395,35 |
| Bag 25 Kg | 4.090 | 102,25 | 6.135 | 153,38 | 10.225 | 255,63 |

## Gaya Visual Laporan

- Tabel bahan baku menggunakan lebar penuh laporan agar seluruh pasangan kolom Bag dan Ton terbaca.
- Header menggunakan warna abu-abu standar laporan, tanpa warna kuning.
- Baris **JUMLAH** menggunakan latar putih dan teks tebal, tanpa warna kuning.
- Tabel menggunakan dua tingkat header untuk membedakan periode dan satuan.
- Tampilan pratinjau tetap menyesuaikan layar ponsel tanpa menimbulkan scroll horizontal pada dokumen.

## Kompatibilitas Data Lama

Laporan yang dibuat sebelum pemisahan kemasan tidak mempunyai nilai kemasan sama sekali. Untuk data tersebut:

- Nama kemasan ditampilkan sebagai **Kemasan belum dicatat**.
- Kolom Bag ditampilkan sebagai tanda `-`.
- Nilai lama dipertahankan dan ditampilkan pada kolom Ton.

Perlakuan ini mencegah data historis dianggap sebagai jumlah Bag dan dikonversi ulang secara keliru.

Laporan dari tahap pertama sudah memakai label kemasan tetapi belum punya kode dan faktor. Migrasi `2026_08_14_000001_add_packaging_code_to_material_items_table.php` mengisinya: `Jumbo Bag` menjadi `Jumbo Bag 1 Ton` berkode `jumbo_1000` berfaktor 1, dan `Bag 50 Kg` berkode `bag_50` berfaktor 0,05. Karena faktornya sama persis dengan perhitungan sebelumnya, tidak ada angka laporan yang bergeser.

Kiriman lama yang hanya membawa label kemasan tanpa kode tetap diterima, dan labelnya diterjemahkan ke kemasan katalog yang sesuai.

## Dampak pada Rekap Kinerja

Rekap kinerja bongkar bahan baku memakai faktor yang tersimpan pada barisnya:

```text
Tonase = Jumlah Bag × packaging_factor
```

Baris lama tanpa kemasan tetap dianggap sebagai Ton. Nama kemasan tidak lagi muncul dalam kueri rekap, sehingga penambahan kemasan berikutnya tidak menyentuh berkas rekap sama sekali.

Rincian kinerja juga menyediakan informasi jumlah kemasan dalam Bag dan hasil tonasenya, beserta catatan konversi yang dibangun dari katalog.

## Batas yang Diketahui

Ekspor Excel hanya memetakan tiga baris pertama bahan baku ke templat (baris 85–87). Dengan empat kemasan, sisanya terpotong. Batas ini sudah ada sebelum perubahan ini dan belum diubah.

## Berkas Utama yang Diubah

- `app/Support/MaterialPackaging.php`
- `database/migrations/2026_08_13_000001_add_packaging_type_to_material_items_table.php`
- `database/migrations/2026_08_14_000001_add_packaging_code_to_material_items_table.php`
- `resources/views/report-ops/sections/step4-bongkar.blade.php`
- `resources/views/report-ops/partials/report-form.blade.php`
- `resources/views/report-ops/partials/report-paper.blade.php`
- `resources/css/layouts/report-ops.css`
- `app/Http/Controllers/ReportOpsController.php`
- `app/Services/OperationalPerformanceService.php`
- `database/seeders/OperationalReportSeeder.php`
- `tests/Unit/MaterialPackagingTest.php`
- `tests/Feature/MaterialPackagingBackfillTest.php`
- `tests/Feature/UnloadingShipOperationTest.php`
- `tests/Feature/OperationalReportSeederTest.php`

## Verifikasi

Perubahan telah diverifikasi melalui:

- Pengujian penyimpanan beberapa kelompok kemasan dalam satu kegiatan.
- Pengujian kemasan tambahan: penyimpanan beserta faktornya, penolakan faktor di luar batas, penolakan dua kelompok bernama sama, dan nama yang bertabrakan dengan katalog.
- Pengujian penerusan rincian bahan baku ke regu berikutnya: jenis bahan, kemasan katalog maupun tambahan, nilai Lalu sebagai akumulasi terakhir, serta baris kosong yang tidak ikut diturunkan.
- Pengujian laporan yang hanya memakai satu kemasan.
- Pengujian penolakan kelompok kemasan kembar dan kode kemasan di luar katalog.
- Pengujian validasi jumlah Bag sebagai bilangan bulat.
- Pengujian konversi seluruh kemasan katalog.
- Pengujian bahwa faktor kiriman form diabaikan dan faktor katalog yang dipakai.
- Pengujian migrasi pengisian data lama beserta tonasenya yang tidak bergeser.
- Pengujian struktur kolom Bag dan Ton beserta subtotal terpisah pada laporan.
- Pemeriksaan langsung pada aplikasi: pergantian kemasan, penambahan dan penghapusan kelompok, penonaktifan kemasan ganda, penggandaan kegiatan, serta pemulihan draf lama maupun draf berkemasan tidak baku.
- Build aset produksi.
- Pemeriksaan visual pada tampilan desktop dan layar 320 piksel.
