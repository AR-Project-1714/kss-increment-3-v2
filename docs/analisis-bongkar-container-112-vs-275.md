# Analisis: Bongkar Container tercatat 112 Teus, seharusnya 275 Teus

> **Status: sudah diimplementasi dan diverifikasi.** Bagian 4 di bawah adalah
> rancangannya; catatan pelaksanaan beserta dua temuan tambahan yang muncul saat
> pengerjaan ada di [bagian 8](#8-catatan-pelaksanaan).


**Keluhan client (Pak Mustari), 7 Agustus 2026:**
> Sepertinya masih ada yg keliru input data dari aplikasi. Contoh saat ini /bulan berjalan Bongkar Kontainer harusnya tercatat 275 teus tapi yg tampil pada kinerja operasi dan rincian kegiatan hanya 112 teus.

Sumber data pemeriksaan: `backup-kss-manual-20260807-095521.json` (90 laporan harian, berisi payload form apa adanya).

---

## Kesimpulan singkat

Pak Mustari **benar**, dan angka 275 itu **tepat sampai satuan terakhir**.

Datanya sebenarnya **sudah masuk dengan lengkap** ke database — tidak ada yang hilang. Yang salah adalah **cara sistem memilah baris mana yang termasuk "Bongkar" dan mana yang "Muat"**.

Pemilahnya adalah kolom **"Ket"** pada tabel container. Kolom itu dibuat sebagai **kotak teks bebas** — operator boleh mengetik apa saja. Tetapi di sisi laporan, sistem menyaring dengan pencocokan **kata persis**:

```php
// app/Services/OperationalPerformanceService.php:212
'conditions' => [['container_items.status', 'Empty']],   // Bongkar Container
// app/Services/OperationalPerformanceService.php:238
'conditions' => [['container_items.status', 'Full']],    // Muat Container
```

Akibatnya baris yang ditulis `"Container empty"`, `"Empty Container"`, atau `"EMPYTY"` **tidak dianggap bongkar sama sekali** — nilainya tidak masuk ke mana pun, tidak ke Bongkar, tidak ke Muat. Hilang senyap.

**Bukan salah operator, dan bukan salah rumus.** Ini kesalahan rancangan form: sistem meminta data terkontrol lewat kotak yang tidak mengontrol apa pun.

---

## 1. Bukti angka — Agustus 2026, kapal MV. Curug Mas

Seluruh laporan di bawah sudah berstatus `approved`/`acknowledged`, jadi semuanya memang ikut dihitung.

| Tgl | Shift | Regu | "Ket" yang diketik operator | Sekarang | Lalu |
|-----|-------|------|------------------------------|---------:|-----:|
| 03 Agu | Sore | D | `Empty` | 15 | 0 |
| 03 Agu | Malam | C | `Empty` | 97 | 15 |
| 04 Agu | Pagi | B | `Empty Container` | 70 | 112 |
| 04 Agu | Sore | A | `Container empty` | 93 | 182 |
| 04 Agu | Malam | D | `Contaner Isi` | 30 | 0 |
| 05 Agu | Pagi | B | `Empty` | 0 | 275 |
| 05 Agu | Pagi | B | `Full` | 48 | 30 |
| 05 Agu | Sore | A | `Muat container isi` | 46 | 78 |
| 05 Agu | Malam | D | `Coutener isi` | 71 | 124 |

### Bongkar Container (Empty)

| | Nilai |
|---|---:|
| Yang **seharusnya** (semua baris bermaksud Empty): 15 + 97 + 70 + 93 + 0 | **275 Teus** |
| Yang **tampil** (hanya yang diketik persis `Empty`): 15 + 97 + 0 | **112 Teus** |
| **Selisih hilang** | **163 Teus** |

**275 Teus persis seperti yang disebut Pak Mustari.**

Ada dua konfirmasi silang yang menguatkan:
1. Rantai kolom "Lalu" berjalan mulus **0 → 15 → 112 → 182 → 275**, lalu berhenti. Artinya pembongkaran empty memang tuntas di angka 275.
2. Kapasitas Empty kapal ini diisi **275 Teus**. Realisasi 275/275 = 100% — pembongkaran selesai penuh.

### Muat Container (Full) — ikut salah, belum disadari

Masalah yang sama juga merusak angka sebelahnya:

| | Nilai |
|---|---:|
| Yang **seharusnya**: 30 + 48 + 46 + 71 | **195 Teus** |
| Yang **tampil** (hanya yang diketik persis `Full`) | **48 Teus** |
| **Selisih hilang** | **147 Teus** |

Sebaiknya ini disampaikan lebih dulu ke Pak Mustari sebelum beliau menemukannya sendiri.

---

## 2. Masalahnya lebih lama dari bulan ini

Sebaran seluruh isi kolom "Ket" di database (92 baris, sejak awal pemakaian):

| Yang diketik | Jumlah baris |
|---|---:|
| *(dikosongkan)* | 78 |
| `Empty` | 5 |
| `Full` | 3 |
| `Coutener isi` | 1 |
| `Muat container isi` | 1 |
| `Contaner Isi` | 1 |
| `Container empty` | 1 |
| `Empty Container` | 1 |
| `EMPYTY` | 1 |

Dampaknya per bulan:

| Bulan | Bongkar tampil | Bongkar seharusnya | Muat tampil | Muat seharusnya | Tak berkategori |
|---|---:|---:|---:|---:|---:|
| Juli 2026 | 141 | ≥ 249 | 60 | 60 | **153 Teus** |
| Agustus 2026 | **112** | **275** | 48 | 195 | 0 |

Catatan Juli: selain `EMPYTY` (108 Teus) yang jelas maksudnya Empty, masih ada **153 Teus pada 2 baris yang kolom "Ket"-nya dikosongkan** (86 Teus pada 20 Juli Malam dan 67 Teus pada 22 Juli Pagi, kapal MV. Ayer Mas). Baris ini **tidak boleh ditebak sistem** — perlu dicocokkan dengan laporan kertasnya.

Di Agustus kebetulan semua baris berkolom kosong bernilai 0, jadi tidak menambah selisih. Itu keberuntungan, bukan pengaman.

---

## 3. Cara kerja sekarang — di mana persisnya rantai ini putus

### Lapisan 1 — Form input (sumber masalah)

`resources/views/report-ops/sections/step4-bongkar.blade.php:235`

```html
<input type="text" name="unloading_containers_1[0][status]"
       class="form-control-custom" placeholder="Ket" autocomplete="off">
```

Kotak teks bebas, tanpa daftar pilihan, tanpa validasi, tanpa contoh pengisian. Label kolomnya hanya **"Ket"** — operator wajar membacanya sebagai "keterangan", bukan "penentu jenis kegiatan". Tidak ada satu pun petunjuk di layar bahwa isian ini yang menentukan angka masuk ke Bongkar atau ke Muat.

Catatan pada form justru hanya menjelaskan kolom Lalu/Total (baris 13), tidak menyinggung kolom Ket sama sekali.

### Lapisan 2 — Penyimpanan (meneruskan apa adanya)

`app/Http/Controllers/ReportOpsController.php:1479`

```php
'status' => $this->string($container['status'] ?? null),
```

`string()` (baris 2547) hanya melakukan `trim()` dan potong 255 karakter. Tidak ada penyeragaman. Kolomnya sendiri `string`/nullable tanpa batasan nilai:

```php
// database/migrations/2026_05_18_000002_create_operational_report_tables.php:142
$table->string('status')->nullable();
```

### Lapisan 3 — Penyajian (menuntut kata persis)

`app/Services/OperationalPerformanceService.php:197–249` — katalog kegiatan memecah satu tabel `container_items` menjadi dua kegiatan hanya berdasarkan kolom ini. Filter diterapkan di `applyActivityConditions()` (baris 2984) sebagai `WHERE container_items.status = 'Empty'`.

Karena collation database `utf8mb4_unicode_ci`, perbandingannya **tidak peka huruf besar-kecil** — `empty` dan `EMPTY` tetap lolos. Tetapi **kata tambahan dan salah ketik tidak**: `Container empty`, `Empty Container`, dan `EMPYTY` semuanya gugur.

### Akibatnya menyebar ke seluruh laporan

Katalog ini adalah satu-satunya sumber kebenaran untuk keduanya, jadi satu filter yang salah merusak semua turunannya sekaligus:

- **Kinerja Operasi** — kartu kegiatan, rekap bulanan, grafik tren, grafik per shift, sparkline
- **Rincian Kegiatan** — panel Bongkar/Muat Container, tabel rincian, metrik realisasi
- **Dashboard Manajer** — grid tujuh kartu kegiatan dan pembanding bulanannya
- **Ekspor Excel** — `PerformanceExportService` dan `ActivityDetailExportService`
- **Total Teus** gabungan

Yang **tidak** terpengaruh: laporan kertas per shift (`report-paper.blade.php:534`) menampilkan `$item->status` apa adanya, sehingga laporan cetak tetap benar. Ini menjelaskan kenapa selisihnya baru terlihat di layar manajer.

### Kelemahan paling berbahaya: hilangnya senyap

Baris yang tidak cocok Empty maupun Full **tidak muncul di mana pun**. Tidak ada peringatan, tidak ada kategori "lain-lain", tidak ada selisih yang terlihat. Kalau Pak Mustari tidak kebetulan hafal angka 275, kekeliruan ini tidak akan pernah ketahuan.

---

## 4. Rancangan perbaikan

Prinsip: **kolom penentu kategori tidak boleh berupa teks bebas**, dan **tidak boleh ada angka yang hilang tanpa jejak**.

Perbaikan dipecah jadi empat langkah yang saling menutup. Langkah 1–3 memperbaiki masalahnya; langkah 4 memastikan kalau nanti bocor lagi, kebocorannya kelihatan.

### Langkah 1 — Form: beri daftar saran, dan tolak isian yang tidak terbaca

`resources/views/report-ops/sections/step4-bongkar.blade.php`

Kolomnya **tetap teks bebas**, tetapi dilengkapi daftar saran Empty/Full:

```html
<input type="text" name="unloading_containers_1[0][status]" class="form-control-custom"
       list="container-status-options" placeholder="Empty / Full" autocomplete="off">

<datalist id="container-status-options">
    <option value="Empty"></option>
    <option value="Full"></option>
</datalist>
```

Rancangan awal memakai dropdown dua pilihan. Diputuskan tetap teks bebas atas arahan pemilik sistem, dan itu **aman selama langkah 2 terpasang**: apa pun yang diketik akan diseragamkan di server sebelum disimpan, dan baris yang benar-benar tidak terbaca muncul sebagai "belum ditandai" (langkah 4) alih-alih hilang diam-diam. Teks bebas juga menghindarkan satu kelas persoalan tersendiri — dropdown tabel pada form ini punya dua hidrator JS yang tidak sinkron (lihat bagian 8b).

Menyertainya:
- **Ganti label kolom** dari `Ket` menjadi **`Empty / Full`**. Nama "Ket" adalah setengah dari penyebab masalah ini; petugas wajar membacanya sebagai "keterangan", bukan penentu kegiatan.
- **Tambah keterangan pada catatan form**: Empty berarti bongkar, Full berarti muat, dan isian itulah yang menentukan baris masuk ke kegiatan mana.
- **Penyeragaman huruf saat selesai mengetik**: "empty" menjadi "Empty", supaya yang terlihat di layar sama dengan yang tersimpan.
- **Validasi di layar**: baris yang "Sekarang"-nya berisi angka tetapi penandanya tidak terbaca sebagai Empty/Full ditahan sebelum laporan dikirim. Penjaga di layar ini sengaja hanya mengenali dua kata bakunya — jauh lebih ketat daripada normalizer di server — supaya tidak perlu menyalin ulang seluruh aturan server (yang pasti akan menyimpang). Server tetap penentu akhir.

### Langkah 2 — Backend: seragamkan saat simpan

Form yang diperbaiki hanya mengamankan data baru. Backend tetap perlu jadi penjaga terakhir, karena draft lama dan autosave masih bisa membawa nilai bebas.

Tambah helper kecil, mis. `app/Support/ContainerStatusNormalizer.php`:

```php
// Empty  <- mengandung "empt", "empy", "kosong"
// Full   <- mengandung "full" atau kata utuh "isi"
// null   <- selain itu (termasuk kosong)
```

Catatan penting: pencocokan `isi` **wajib memakai batas kata** (`\bisi\b`). Tanpa itu, kata seperti "posisi", "kondisi", atau "revisi" akan salah tergolong Full.

Panggil dari `ReportOpsController::storeContainerActivities()` (baris 1479):

```php
'status' => ContainerStatusNormalizer::normalize($container['status'] ?? null),
```

Tambahkan juga aturan validasi `in:Empty,Full` (nullable) di `rules()` (sekitar baris 1264) agar kiriman di luar dua nilai itu ditolak, bukan diam-diam disimpan.

Dengan langkah 1 + 2, kolom di database dijamin hanya berisi `Empty`, `Full`, atau `NULL` — dan filter `WHERE status = 'Empty'` yang sekarang ada **menjadi benar tanpa perlu diubah**.

### Langkah 3 — Rapikan data lama

92 baris yang sudah tersimpan tetap salah kategori sampai diperbaiki. Buat artisan command (mis. `php artisan container:repair-status`), mengikuti pola `RepairShipIdentity` yang sudah ada di `app/Console/Commands/`.

Pembagiannya harus tegas:

**Boleh diperbaiki otomatis** — 5 baris, maksudnya tidak ambigu:

| Nilai lama | Menjadi |
|---|---|
| `Container empty`, `Empty Container`, `EMPYTY` | `Empty` |
| `Coutener isi`, `Muat container isi`, `Contaner Isi` | `Full` |

**Tidak boleh ditebak** — 78 baris berkolom kosong. Dari jumlah itu hanya sedikit yang bernilai bukan nol, dan yang paling penting **153 Teus di Juli 2026**. Command sebaiknya mencetak daftarnya (tanggal, shift, regu, kapal, jumlah) untuk dicocokkan Pak Mustari dengan arsip kertas, lalu diperbaiki lewat menu edit laporan. Baris bernilai 0 boleh dibiarkan `NULL`.

Wajib dijalankan dengan opsi `--dry-run` lebih dulu, dan backup diambil sebelum eksekusi sungguhan.

### Langkah 4 — Jangan biarkan ada yang hilang senyap lagi

Ini bagian yang mencegah keluhan yang sama terulang dalam bentuk lain.

Setelah langkah 1–3, tambahkan penanda **"Belum ditandai"** pada panel Rincian Kegiatan container: hitung baris container yang `status`-nya `NULL` pada periode terpilih, dan bila jumlahnya lebih dari nol, tampilkan sebagai catatan kecil — misalnya *"3 baris (153 Teus) belum ditandai Empty/Full dan belum masuk hitungan."*

Biayanya satu query ringan, dan hasilnya: selisih seperti ini akan **terlihat sendiri di layar**, tanpa perlu ada yang hafal angkanya.

---

## 5. Temuan sampingan — kapasitas container terhitung berlipat

Ditemukan saat menelusuri panel yang sama. **Terpisah dari keluhan Pak Mustari dan tidak diusulkan dikerjakan sekarang**, tapi ada di panel yang sama sehingga kemungkinan besar akan jadi pertanyaan berikutnya.

`OperationalPerformanceService::containerDetail()` (sekitar baris 2270) menjumlahkan kapasitas dari tabel induk:

```php
->selectRaw('COALESCE(SUM('.$activity['capacityColumn'].'), 0) as capacity')
```

`container_activities` berisi **satu baris per laporan shift**, sehingga satu kapal yang dikerjakan lintas 5 shift menyumbang kapasitasnya **5 kali**. Untuk MV. Curug Mas di Agustus: kapasitas tercatat menjadi 5 × 275 = **1.375 Teus**, dan "Realisasi terhadap kapasitas" jatuh ke sekitar 20% padahal sebenarnya 100%.

Perbaikannya sejalan dengan pola yang sudah dipakai di tempat lain: kapasitas diambil satu kali per kunjungan kapal (`visitIdentity`), bukan dijumlahkan per laporan.

Terkait: pengisian kapasitas juga belum konsisten — pada 4 Agustus Sore kolom Full dikosongkan, pada 5 Agustus Sore kolom Empty dikosongkan. Perlu diseragamkan lewat pengisian otomatis dari shift sebelumnya, seperti yang sudah berjalan pada kolom "Lalu".

---

## 6. Yang perlu diuji sebelum rilis

1. **Angka acuan** — setelah langkah 1–3, Bongkar Container Agustus 2026 harus tepat **275 Teus** dan Muat Container **195 Teus**.
2. **Draft lama** — `restoreSavedPayload()` (`report-form.blade.php:1817`) memulihkan isian dengan `control.value = value`. Pada `<select>`, nilai lama seperti `"Container empty"` **tidak akan cocok dengan opsi mana pun** dan berubah jadi kosong. Draft yang belum dikirim perlu dipastikan tidak kehilangan isian saat dibuka — uji khusus untuk ini.
3. **Baris tambahan** — tekan "Tambah Baris" dan "Kegiatan 2", pastikan dropdown ikut ter-render (`hydrateTableSelects`) dan penomoran `name` tetap benar (`report-form.blade.php:3310`).
4. **Laporan kertas** — `report-paper.blade.php:534` harus tetap menampilkan Empty/Full seperti sebelumnya.
5. **Konsistensi lintas menu** — Dashboard Manajer, Kinerja Operasi, Rincian Kegiatan, dan hasil ekspor Excel harus menunjukkan angka yang sama.
6. **Regresi Juli** — setelah 153 Teus dikonfirmasi dan diperbaiki, angka Juli ikut berubah. Beri tahu Pak Mustari lebih dulu supaya perubahan angka bulan lalu tidak dikira kekeliruan baru.

---

## 7. Urutan pengerjaan yang disarankan

| Urutan | Pekerjaan | Dampak |
|---|---|---|
| 1 | Langkah 1 + 2 (form dropdown + normalizer + validasi) | Menghentikan masalah bertambah |
| 2 | Langkah 3 otomatis (5 baris tak ambigu) | Agustus langsung benar: 275 / 195 |
| 3 | Langkah 3 manual (konfirmasi 153 Teus Juli) | Butuh arsip kertas Pak Mustari |
| 4 | Langkah 4 (penanda "Belum ditandai") | Mencegah terulang |
| 5 | Bagian 5 (kapasitas berlipat) | Terpisah, dijadwalkan tersendiri |

Langkah 1 dan 2 sudah cukup untuk menjawab keluhan yang masuk. Langkah 4 yang membuat kejadian ini tidak berulang diam-diam.

---

## 8. Catatan pelaksanaan

Langkah 1-4 sudah dikerjakan. Berkas yang disentuh:

| Berkas | Peran |
|---|---|
| `app/Support/ContainerStatusNormalizer.php` | Penerjemah penanda, berjenjang: Inggris → Indonesia → nama kegiatan |
| `resources/views/report-ops/sections/step4-bongkar.blade.php` | Kolom "Ket" → **Empty / Full**, teks bebas + daftar saran |
| `app/Http/Controllers/ReportOpsController.php` | Penyeragaman sebelum validasi + aturan `in:Empty,Full` |
| `app/Console/Commands/RepairContainerStatus.php` | `container:repair-status`, dengan `--dry-run` |
| `app/Services/OperationalPerformanceService.php` | Hitungan "belum ditandai" |
| `resources/views/manajer/partials/activity-detail.blade.php` | Tampilan peringatannya |
| `app/Services/ActivityDetailExportService.php` | Peringatan yang sama ikut ke workbook |
| `tests/Unit/ContainerStatusNormalizerTest.php`, `tests/Feature/ContainerStatusIntegrityTest.php` | 31 uji, termasuk angka 275/195 sebagai patokan |

### Hasil verifikasi

Basis data pratinjau diisi persis dengan delapan baris MV. Curug Mas dari backup:

| | Sebelum | Sesudah |
|---|---:|---:|
| Bongkar Container | 112,00 Teus | **275,00 Teus** |
| Muat Container | 48,00 Teus | **195,00 Teus** |

Sebelum perapian, panel juga sudah menyebut sendiri *"5 baris container (310 Teus) belum ditandai Empty atau Full"* — kebocoran yang dulu tak terlihat kini muncul di layar.

Uji otomatis: 312 dari 315 lolos. Tiga yang gagal (`ManagerDashboardTest` baris 727, 866, 1239) **sudah gagal sebelum perubahan ini** — diperiksa dengan menjalankan suite pada tree bersih, hasilnya sama persis. Tidak berkaitan dengan container.

### Dua temuan tambahan yang muncul saat pengerjaan

Keduanya bukan bagian rancangan awal, ditemukan saat verifikasi di browser, dan sudah ikut diperbaiki:

**a. Cache laporan manajer tidak ikut kedaluwarsa.** Kunci cache halaman manajer memuat `updated_at` laporan terbaru, sedangkan perapian hanya menyentuh `container_items`. Akibatnya perbaikan sudah masuk database tetapi layar manajer masih menampilkan angka lama — persis hal yang paling membingungkan sesudah perbaikan data dijalankan. Perintahnya sekarang menggeser `updated_at` laporan yang barisnya berubah.

**b. Dropdown tabel punya dua hidrator yang tidak sinkron.** Ditemukan saat kolom ini sempat dibuat dropdown. `.tbl-select-wrapper` dihidrasi oleh `hydrateTableSelects()` (report-form.blade.php) **dan** `initCustomSelects()` (public/js/layouts/report-ops.js). Yang kedua mengirim `new Event("change")` tanpa `bubbles: true` dan sama sekali tidak punya listener `change`. Akibatnya tanda merah tidak hilang walau penanda sudah dipilih, dan laporan yang dibuka kembali menampilkan kolomnya seolah masih kosong padahal nilainya sudah pulih — petugas bisa salah "membetulkan" data yang sebenarnya benar.

Kolom ini akhirnya kembali memakai teks bebas, sehingga persoalan itu tidak lagi menyentuhnya. **Cacatnya sendiri belum diperbaiki** dan masih mengintai setiap dropdown tabel yang ditambahkan ke form operasi di kemudian hari. Perlu dicatat sebagai pekerjaan tersendiri.

Penandaan Empty/Full tetap dipasang pada fase **capture**, bukan bubble — bukan lagi karena hidrator, melainkan karena baris container bisa disusun ulang dan di-clone oleh "Tambah Baris"/"Kegiatan N". Dengan capture, baris baru ikut terjaga tanpa perlu memasang listener ulang di tiap baris.

### Yang harus dilakukan saat penerapan

1. `npm run build` — CSS berubah (penanda merah dan kotak peringatan).
2. Backup database.
3. `php artisan container:repair-status --dry-run`, tinjau tabel pemetaannya.
4. `php artisan container:repair-status`.
5. Cocokkan **153 Teus di Juli 2026** yang penandanya kosong dengan arsip kertas, lalu lengkapi lewat menu edit laporan. Perintah pada langkah 3 sudah mencetak daftarnya lengkap dengan tanggal, shift, regu, dan kapal.
6. Beri tahu Pak Mustari lebih dulu bahwa angka Juli ikut berubah, supaya tidak dikira kekeliruan baru.

Bagian 5 (kapasitas berlipat) **belum dikerjakan** — masih terlihat pada verifikasi: satu kapal berkapasitas 275 Teus terbaca 1.100 Teus setelah tersebar di empat laporan shift, dan "Realisasi terhadap kapasitas" jatuh ke 25%. Perlu dijadwalkan tersendiri.
