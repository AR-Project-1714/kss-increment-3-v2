# Rancangan: Kemasan Bongkar Bahan Baku yang Dapat Dipilih

Status: **sudah diimplementasikan.** Hasil akhirnya didokumentasikan pada [perubahan-form-dan-laporan-bongkar-bahan-baku.md](perubahan-form-dan-laporan-bongkar-bahan-baku.md); berkas ini disimpan sebagai catatan rancangan beserta keputusan yang diambil.

Keputusan atas pertanyaan pada bagian 13:

1. Bentuk form: kelompok kemasan dinamis dengan pilihan.
2. Label `Jumbo Bag` lama diganti menjadi `Jumbo Bag 1 Ton` oleh migrasi.
3. Katalog cukup di berkas PHP, tanpa tabel master.
4. Batas tiga baris pada ekspor Excel dibiarkan.

Satu hal yang tidak ada pada rancangan ini dan ditemukan saat pemeriksaan layar 320 piksel: subtotal form terpotong karena jumlah Bag kemasan 25 Kg berdigit banyak. Pasangan angka dan satuannya kini turun baris pada layar sempit.

## 1. Permintaan

Masukan lanjutan dari lapangan atas fitur bongkar bahan baku yang sudah berjalan:

> Sebenarnya ada beberapa kemasan yang biasa dibongkar, walaupun 2 kemasan yang sekarang paling sering dipakai. Bisakah kedua jenis kemasan itu diubah atau ditambah saja. Kemasan yang masih kadang ada:
> 1. Kemasan Jumbo Bag 1,5 ton (1 bag = 1,5 ton)
> 2. Kemasan Bag 25 Kg (40 bag = 1 ton)

Artinya dua kategori tetap yang sekarang tidak lagi cukup. Kemasan harus bisa **dipilih** petugas, dan daftarnya harus bisa **bertambah** tanpa membongkar ulang form, laporan, dan rekap kinerja.

## 2. Kondisi Saat Ini

Nama kemasan dan angka konversinya masih tertulis langsung di enam tempat berbeda:

| Berkas | Bentuk hardcode |
| --- | --- |
| [step4-bongkar.blade.php:83](resources/views/report-ops/sections/step4-bongkar.blade.php:83) | Array dua kemasan beserta `factor` di dalam Blade |
| [report-form.blade.php:2077](resources/views/report-ops/partials/report-form.blade.php:2077) | Faktor dibaca dari `data-material-tonnage-factor` |
| [ReportOpsController.php:43](app/Http/Controllers/ReportOpsController.php:43) | Konstanta `MATERIAL_PACKAGE_JUMBO` dan `MATERIAL_PACKAGE_50_KG` |
| [ReportOpsController.php:1358](app/Http/Controllers/ReportOpsController.php:1358) | Aturan "wajib isi kedua kategori" |
| [report-paper.blade.php:23](resources/views/report-ops/partials/report-paper.blade.php:23) | `$materialTons`: bagi 20 bila `Bag 50 Kg` |
| [OperationalPerformanceService.php:335](app/Services/OperationalPerformanceService.php:335) | `CASE WHEN packaging_type = 'Bag 50 Kg' THEN qty / 20.0` |

Konversi juga dihitung ulang dari **nama kemasan** setiap kali laporan dibuka. Selama daftar kemasan hanya dua dan tidak pernah berubah, cara ini aman. Begitu daftarnya bertambah dan suatu saat bisa disunting, cara ini berbahaya: mengubah faktor sebuah kemasan akan diam-diam mengubah angka laporan lama.

## 3. Prinsip Rancangan

1. **Satu sumber kebenaran.** Katalog kemasan didefinisikan di satu berkas PHP, lalu dipakai form, validasi, laporan, dan rekap.
2. **Faktor konversi dibekukan pada baris data.** Nilai ton per bag disimpan bersama baris bahan baku saat laporan disimpan. Laporan lama tidak pernah berubah angkanya walau katalog disunting.
3. **Faktor tidak pernah dipercaya dari browser.** Form hanya mengirim kode kemasan; server yang menerjemahkannya menjadi faktor.
4. **Data lama tetap terbaca.** Baris tanpa kemasan tetap diperlakukan sebagai Ton seperti sekarang.

## 4. Katalog Kemasan

Berkas baru: `app/Support/MaterialPackaging.php` (enum backed-string atau kelas final berisi konstanta).

| Kode | Label | Ton per Bag | Keterangan |
| --- | --- | ---: | --- |
| `jumbo_1000` | Jumbo Bag 1 Ton | 1 | 1 Bag = 1 Ton |
| `jumbo_1500` | Jumbo Bag 1,5 Ton | 1,5 | 1 Bag = 1,5 Ton |
| `bag_50` | Bag 50 Kg | 0,05 | 20 Bag = 1 Ton |
| `bag_25` | Bag 25 Kg | 0,025 | 40 Bag = 1 Ton |

Tiap entri menyimpan: `code`, `label`, `tonPerBag`, `description`, `order`, `default` (dipakai untuk kelompok bawaan pada form baru), dan `active` (kemasan yang dipensiunkan tetap terbaca di laporan lama tetapi hilang dari pilihan form).

Menambah kemasan berikutnya = menambah satu baris di katalog ini. Tidak ada berkas lain yang perlu disentuh.

## 5. Perubahan Skema Data

Migrasi baru pada `material_items`:

| Kolom | Tipe | Isi |
| --- | --- | --- |
| `packaging_code` | `string(40)`, nullable | Kode katalog, contoh `bag_25` |
| `packaging_factor` | `decimal(10,4)`, nullable | Ton per Bag saat laporan disimpan |

Kolom `packaging_type` yang sudah ada **dipertahankan** sebagai label yang tampil dan sebagai kolom pencarian ([SearchesReports.php:136](app/Http/Controllers/Concerns/SearchesReports.php:136)).

Pengisian data lama pada migrasi yang sama:

| Nilai `packaging_type` lama | `packaging_code` | `packaging_factor` | Label baru |
| --- | --- | ---: | --- |
| `Jumbo Bag` | `jumbo_1000` | 1,0000 | `Jumbo Bag 1 Ton` |
| `Bag 50 Kg` | `bag_50` | 0,0500 | tetap |
| kosong / null | tetap null | tetap null | tetap (dibaca sebagai Ton) |

Penggantian label `Jumbo Bag` menjadi `Jumbo Bag 1 Ton` disarankan karena faktornya tidak berubah, sehingga tidak ada angka laporan yang bergeser, sementara laporan lama dan baru menjadi konsisten saat dikelompokkan. Migrasi `down()` mengembalikan label semula dan menghapus dua kolom baru.

`MaterialItem` memakai `$guarded = ['id']`, jadi tidak ada perubahan model.

## 6. Perubahan Form Petugas

### 6.1 Bentuk baru

Kelompok kemasan menjadi **dinamis**, bukan lagi dua seksi terkunci:

- Form baru terbuka dengan dua kelompok bawaan: **Jumbo Bag 1 Ton** dan **Bag 50 Kg** (mempertahankan alur kerja yang sekarang paling sering dipakai).
- Judul tiap kelompok berubah dari teks mati menjadi **pilihan kemasan**. Badge "Kategori tetap" diganti keterangan konversi yang ikut berubah, misalnya `1 Bag = 1,5 Ton`.
- Tombol **Tambah Kemasan** menambah kelompok baru; tombol hapus pada kelompok menghapusnya. Minimal satu kelompok, maksimal sebanyak entri katalog aktif.
- Kemasan yang sudah dipakai kelompok lain dinonaktifkan pada pilihan kelompok berikutnya, supaya satu kegiatan tidak punya dua kelompok dengan kemasan sama.
- Satuan input tetap **Bag**, kolom **Akumulasi** tetap dihitung sistem, subtotal tetap `2.500 Bag / 125 Ton` dengan faktor mengikuti kemasan terpilih.

### 6.2 Isian tersembunyi per baris

Tiap baris mengirim `packaging_code` (baru) dan `packaging_type` (label, untuk pencarian). `packaging_factor` **tidak** dikirim dari browser; nilainya hanya dipakai atribut `data-material-tonnage-factor` untuk menghitung subtotal di layar.

### 6.3 Pekerjaan JavaScript

Pada [report-form.blade.php](resources/views/report-ops/partials/report-form.blade.php):

- `syncMaterialPackageGroup` membaca kemasan dari elemen pilihan, bukan dari `dataset` statis, lalu memperbarui faktor, label, keterangan konversi, dan semua isian tersembunyi di kelompok tersebut.
- Fungsi baru `addMaterialPackageGroup` / `removeMaterialPackageGroup`, memakai template kelompok kosong.
- `reindexMaterialPackageTables` sudah menomori baris lintas kelompok secara berurutan, jadi tidak perlu diubah.
- **Titik risiko utama:** `ensureMaterialPackageRows` memulihkan draf dengan mengandalkan jumlah dan urutan kelompok yang tetap. Fungsi ini harus dirombak agar merekonstruksi kelompok dari `packaging_code` yang tersimpan di draf, lalu menyusun barisnya. Perlu pemeriksaan juga pada penggandaan pane "Kegiatan N" dan pada `resetTableSelectHydration` bila pilihan kemasan memakai komponen select kustom.

## 7. Perubahan Validasi

Menggantikan [materialPackagingRules()](app/Http/Controllers/ReportOpsController.php:1358):

- `packaging_code` wajib termasuk kode katalog aktif untuk setiap baris yang berisi.
- Aturan lama "wajib mengisi Jumbo Bag **dan** Bag 50 Kg" **dihapus**, diganti "minimal satu baris bahan baku berisi bila kegiatan bongkar bahan baku diisi". Aturan lama akan menolak laporan yang sah, misalnya shift yang hanya membongkar Bag 25 Kg.
- Satu kode kemasan tidak boleh muncul pada dua kelompok dalam satu kegiatan.
- Draf tetap bebas dari seluruh aturan ini.
- Saat menyimpan, server mengisi `packaging_code`, `packaging_type` (label dari katalog), dan `packaging_factor` (dari katalog) berdasarkan kode. Kiriman `packaging_type` dari browser tidak dipakai sebagai penentu.
- Kompatibilitas: kiriman lama yang hanya memuat `packaging_type` berisi label dikenali, dipetakan ke kode yang sesuai.

## 8. Perubahan Laporan

Pada [report-paper.blade.php](resources/views/report-ops/partials/report-paper.blade.php):

- `$materialTons` tidak lagi memeriksa nama kemasan. Rumusnya menjadi `bag × (packaging_factor ?? 1)`, dan baris tanpa kemasan tetap ditampilkan sebagai Ton dengan kolom Bag berisi `—`.
- Pengelompokan memakai `packaging_code`, bukan teks kemasan, dengan cadangan ke teks untuk baris lama. Urutan kelompok mengikuti `order` katalog, kelompok "Kemasan belum dicatat" tetap paling akhir.
- Kolom KEMASAN menampilkan label katalog. Baris **JUMLAH** per kelompok tidak berubah bentuknya, dan tetap tidak digabung antar kemasan.
- Struktur kolom, dua tingkat header, dan gaya visual tidak berubah, sehingga tampilan ponsel dan PDF yang sudah dirapikan tetap aman.

## 9. Perubahan Rekap Kinerja

Pada [OperationalPerformanceService.php:335](app/Services/OperationalPerformanceService.php:335):

```sql
-- tonase
CASE WHEN material_items.packaging_factor IS NOT NULL
     THEN COALESCE(qty, 0) * material_items.packaging_factor
     ELSE COALESCE(qty, 0) END

-- jumlah bag
CASE WHEN material_items.packaging_code IS NOT NULL
     THEN COALESCE(qty, 0)
     ELSE 0 END
```

Nama kemasan hilang seluruhnya dari SQL, sehingga penambahan kemasan berikutnya tidak menyentuh berkas ini. Rincian per bahan baku tetap memakai `packaging_type` sebagai label tampilan.

## 10. Catatan Ekspor Excel

[ReportOpsController.php:935](app/Http/Controllers/ReportOpsController.php:935) hanya memetakan **tiga baris pertama** bahan baku ke templat Excel (baris 85–87). Dengan dua kemasan, batas itu sudah ketat; dengan empat kemasan hampir pasti terlampaui dan sisanya terpotong diam-diam. Rancangan ini tidak mengubahnya. Perlu keputusan terpisah: melebarkan area templat, atau menerima pemotongan tersebut.

## 11. Urutan Implementasi

1. `app/Support/MaterialPackaging.php` beserta pengujian unitnya.
2. Migrasi `packaging_code` + `packaging_factor` beserta pengisian data lama.
3. Penyimpanan dan validasi pada `ReportOpsController`.
4. Form: `step4-bongkar.blade.php` dan JavaScript pada `report-form.blade.php`.
5. Tampilan laporan `report-paper.blade.php` dan gaya pada `report-ops.css`.
6. Rekap kinerja `OperationalPerformanceService.php`.
7. Teks bantuan pada popover form dan catatan "Dua kategori kemasan tetap".
8. Pembaruan `database/seeders/OperationalReportSeeder.php`.
9. Pembaruan berkas uji dan dokumen `docs/perubahan-form-dan-laporan-bongkar-bahan-baku.md`.

## 12. Rencana Pengujian

- Penyimpanan laporan dengan satu kemasan saja, misalnya hanya Bag 25 Kg.
- Penyimpanan laporan dengan tiga dan empat kemasan sekaligus.
- Penolakan kode kemasan di luar katalog dan penolakan kemasan ganda dalam satu kegiatan.
- Konversi tiap kemasan: 200 Jumbo Bag 1,5 Ton = 300 Ton; 800 Bag 25 Kg = 20 Ton.
- `packaging_factor` yang tersimpan tidak ikut berubah setelah katalog disunting.
- Migrasi: laporan lama `Jumbo Bag` dan `Bag 50 Kg` menghasilkan tonase yang sama persis seperti sebelum migrasi; baris tanpa kemasan tetap dibaca sebagai Ton.
- Pemulihan draf yang memuat kelompok kemasan tidak baku.
- Struktur kolom laporan, subtotal per kelompok, unduhan PDF, dan tampilan 320 piksel.

## 13. Keputusan yang Perlu Dikonfirmasi

1. **Bentuk form**: kelompok kemasan dinamis dengan pilihan (rancangan ini), atau empat seksi tetap yang selalu tampil? Rancangan ini memilih yang pertama karena empat seksi tetap membuat form panjang padahal dua di antaranya jarang dipakai.
2. **Label `Jumbo Bag` lama**: diganti menjadi `Jumbo Bag 1 Ton` (rancangan ini) atau dibiarkan apa adanya?
3. **Katalog kemasan**: cukup di berkas PHP (rancangan ini), atau perlu tabel master dengan halaman kelola sendiri supaya admin bisa menambah kemasan tanpa rilis baru?
4. **Batas tiga baris pada ekspor Excel**: dilebarkan atau dibiarkan?

## 14. Susulan: Kemasan yang Menyatu dengan Nama Bahan (15 Agustus 2026)

Pengisian data pada migrasi `2026_08_14_000001` hanya membaca kolom
`packaging_type`. Kolom itu sendiri baru ada sejak `2026_08_13_000001`, sehingga
seluruh laporan 10–13 Agustus 2026 terlewat: kemasannya ditulis petugas menyatu
dengan nama bahan — `MGO 18% Bag @50Kg`, `Clay Jumbo Bag @ 1 Ton`.

Tanpa `packaging_factor`, rekap kinerja membaca jumlah Bag sebagai Ton. Untuk
jumbo bag hasilnya kebetulan benar (1 Bag = 1 Ton), tetapi 34.080 Bag MgO
tercatat sebagai 34.080 Ton — dua puluh kali lipat dari 1.704 Ton yang
sebenarnya. Kartu Bongkar Bahan Baku menunjukkan 36.685 Ton, seharusnya 4.309
Ton.

Migrasi `2026_08_15_000001_backfill_material_packaging_from_raw_type` membaca
kemasan dari teks nama bahan itu, dalam dua langkah:

1. Kemasan yang tertulis lengkap dibaca langsung. Ukuran kilogram diperiksa lebih
   dulu karena namanya juga memuat kata "Bag"; jumbo bag tanpa angka mengikuti
   ukuran bawaan 1 Ton seperti alias katalog.
2. Baris yang namanya disingkat antar shift — `MGO`, `Clay` — mengikuti baris
   sekapal dan sebahan yang kemasannya tertulis lengkap. Ruang lingkupnya dikunci
   per `ship_name_key`, dan bahan yang pernah tercatat dalam dua kemasan berbeda
   sengaja dilewatkan.

Faktor di luar katalog tidak pernah dikarang; baris yang kemasannya tidak dapat
dipastikan dibiarkan apa adanya dan tetap dibaca sebagai Ton.

`raw_material_type` tidak disentuh. Teks itu ikut tercetak pada laporan PDF yang
sudah disetujui, dan kadar di dalamnya ("17%", "18%") bukan milik katalog
kemasan. Akibatnya panel Rincian Kegiatan masih memecah satu bahan menjadi
beberapa baris selama ejaan lamanya berbeda — penyeragaman tampilan itu dicatat
sebagai pekerjaan tersendiri, bukan sebagai perbaikan angka.

Perintah `material:repair-packaging` memanggil pengisian data yang sama.
Jalankan dengan `--dry-run` untuk melihat rencananya pada basis data produksi
sebelum menulis. Aman diulang: baris yang `packaging_code`-nya sudah terisi tidak
pernah disentuh.

### Penyeragaman Ejaan pada Panel Rincian Kegiatan

Panel Rincian Kegiatan mengelompokkan komposisi tonase menurut nama bahan apa
adanya, sedangkan nama bahan diketik bebas tiap shift. Tiga bahan dari satu
pelayaran karena itu terpecah menjadi 20 baris — "Clay", "CLAY JUMBO 17%",
"Clay jumbo bag", dan seterusnya masing-masing hanya memuat sebagian tonasenya.

`App\Support\MaterialNameNormalizer` memberi bentuk kanonik untuk pengelompokan,
sejalan dengan `ShipNameNormalizer` untuk nama kapal: keterangan kemasan dan
kadar dibuang, sisanya dipakai sebagai kunci. Ejaan yang ditampilkan diambil dari
baris terbaru tiap kelompok, sehingga label mengikuti bentuk yang sedang dipakai
di lapangan tanpa perlu daftar bahan baku tersendiri.

Penyeragaman ini hanya berlaku saat menampilkan. Nama yang tersimpan tidak
diubah, dan pemisahan menurut kemasan tetap dipertahankan — satu bahan dalam dua
kemasan memang dua subtotal operasional yang berbeda.

Pencocokannya sengaja persis, bukan mirip. Salah ketik seperti "Limeston" tetap
berdiri sendiri sebagai satu baris. Baris yang terlihat janggal masih bisa
dibetulkan lewat menu edit laporan, sedangkan peleburan dua bahan yang keliru
tidak akan pernah terlihat pada angkanya.
