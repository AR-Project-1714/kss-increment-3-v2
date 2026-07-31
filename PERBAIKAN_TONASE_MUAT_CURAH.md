# Perbaikan Identitas Kapal dan Perhitungan Tonase

**Laporan masalah:** Pak Mustari — tonase muat curah bulan Juli seharusnya berada
di kisaran ratusan ribu ton, tetapi sistem menampilkan angka sampai **1,6 miliar**.
Penyebab yang beliau duga: nama kapal ditulis tidak konsisten antar shift karena
petugas kebanyakan tidak memakai fitur **Simpan Operasi Kapal**.

**Data uji:** `backup-kss-otomatis-20260730-020002.json` — backup otomatis
produksi, 66 laporan operasi terisi (8–29 Juli 2026), sudah dijadikan seeder
`BackupOperationalReportSeeder`.

**Cakupan:** perbaikan penamaan kapal berlaku untuk **seluruh** kegiatan yang
mencatat nama kapal — muat kantong, muat curah, muat amoniak, bongkar bahan baku,
serta bongkar dan muat container. Kelimanya kini juga punya **operasi kapal**;
sebelumnya hanya pemuatan yang punya. Perbaikan perhitungan tonase (COB) khusus
untuk muat curah dan muat amoniak, karena hanya kedua kegiatan itu yang mencatat
angka kumulatif.

**Hasil akhir yang terverifikasi pada data itu:**

| Perhitungan | Total tonase muat curah, Juli 2026 |
|---|---|
| Cara lama (kode sebelum perbaikan) | **1.637.713 ton** |
| Cara baru | **66.330,25 ton** |

Setiap pelayaran kini berhenti di sekitar kapasitas kapalnya — uji kewarasan yang
sebelumnya tidak pernah terpenuhi:

| Kapal | Kapasitas | Cara lama | Cara baru | Realisasi |
|---|---:|---:|---:|---:|
| MV. Buana Gemilang II | 4.200 | 17.600 | 3.800,00 | 90,5 % |
| KM. Malacca Strait | 9.000 | 54.740 | 8.350,00 | 92,8 % |
| KM. Golden Rejeki | 40.000 | 696.919 | 41.130,00 | 102,8 % |
| KM. Noah Asyera | 40.000 | 63.643 | 8.630,00 | 21,6 % (masih memuat) |
| LPG/C Marianna 28 | 4.420,25 | 8.555 | 4.420,25 | 100,0 % |

---

## 1. Diagnosis: tiga cacat yang saling menumpuk

Dugaan Pak Mustari benar, tetapi penamaan kapal ternyata **bukan** penyebab
terbesarnya. Setelah backup diputar ulang lewat jalur simpan yang asli, terlihat
tiga cacat yang saling menggandakan. Nomor 2 adalah penyumbang terbesar.

### 1.1 Cacat 1 — Sebagian koma desimal terbuang saat menyimpan

`ReportOpsController` menyimpan COB memakai pembantu `integer()`:

```php
// sebelum
'cob' => $this->integer($log['cob'] ?? null) ?: null,

// isi integer()
return max(0, (int) preg_replace('/[^\d\-]/', '', (string) $value));
```

`preg_replace` itu membuang **semua titik**. Akibatnya bergantung pada arti titik
tersebut — dan petugas memakai dua arti yang berbeda dalam berkas yang sama:

| Diketik petugas | Maksudnya | Tersimpan | Benar? |
|---|---|---|---|
| `16.750` | 16.750 ton (titik = pemisah ribuan) | `16750` | ✅ kebetulan benar |
| `19.400` | 19.400 ton | `19400` | ✅ |
| `4420.25` | 4.420,25 ton (titik = koma desimal) | `442025` | ❌ 100× terlalu besar |

Bukti bahwa titik memang dipakai sebagai pemisah ribuan ada pada laporan
berurutan untuk kapal yang sama — pembacaan COB yang identik ditulis dua cara:

| Shift | Ditulis |
|---|---|
| 16 Jul Malam | `16.750` |
| 17 Jul Pagi | `16750` |
| 17 Jul Sore | `19.400` |
| 18 Jul Pagi | `19400` |
| 20 Jul Malam | `38.170` |
| 21 Jul Pagi | `38170` |

Jadi cacat ini **jauh lebih sempit** daripada yang tampak sekilas. Pada seluruh
data Juli 2026, hanya **satu** nilai yang benar-benar rusak: `4420.25` milik
LPG/C Marianna 28 tersimpan sebagai `442025`. Nilai tunggal itu menyumbang
437.605 ton — sekitar 27 % dari total 1.637.713 yang dikeluhkan.

Perbaikannya dua arah:

- **Laporan baru** dibaca oleh `App\Support\TonnageNumber`, yang membedakan
  pemisah ribuan dari koma desimal: titik yang diikuti TEPAT tiga angka adalah
  pemisah ribuan, selain itu koma desimal. Pengelompokan tiga angka adalah ciri
  khas pemisah ribuan, sedangkan koma desimal tonase praktis selalu satu atau
  dua angka.
- **Laporan lama** dipulihkan dari `daily_reports.payload`, yang masih menyimpan
  isian form apa adanya, memakai pembacaan yang sama. Nilai yang sudah benar
  tidak ikut tersentuh.

### 1.2 Cacat 2 — COB dijumlahkan, padahal angkanya kumulatif

Ini penyebab pembengkakan terbesar.

COB (*Cargo On Board*) adalah **berapa ton yang sudah ada di kapal saat itu**.
Angkanya naik terus sepanjang pelayaran, persis seperti odometer kendaraan.
Sementara katalog kegiatan menjumlahkannya:

```php
'muat_curah' => [
    'from'   => 'bulk_loading_logs',
    'column' => 'bulk_loading_logs.cob',   // ← SUM() atas pembacaan odometer
    ...
]
```

Padahal komentar di kepala `OperationalPerformanceService` sudah menyebut
asumsinya sendiri:

> *"kolom qty_*_current berisi tonase shift itu saja, sedangkan qty_*_prev adalah
> akumulasi shift sebelumnya."*

Asumsi itu benar untuk muat kantong, bongkar bahan baku, container, dan trucking
— semuanya menyimpan tonase per shift. Hanya muat curah yang menyimpan
**pembacaan kumulatif**, dan justru kolom itulah yang ikut dijumlahkan.

Contoh nyata, KM. Golden Rejeki 14–22 Juli. Kapal ini memuat 41.130 ton, tercatat
dalam 23 shift dengan 40 pembacaan terisi. Deret naiknya:

```
5.000 → 7.840 → 9.140 → 10.000 → 12.500 → 14.510 → 15.000 → 16.750 →
19.400 → 20.280 → 20.850 → 21.530 → 27.140 → 31.390 → 31.680 → 32.000 →
32.140 → 33.410 → 35.000 → 37.040 → 38.170 → 39.970 → 40.970 → 41.130
```

- Yang benar: **41.130 ton** (pembacaan terakhir).
- Yang dihitung sistem: **696.919 ton** — hasil menjumlahkan seluruh pembacaan.

Satu kapal berkapasitas 40.000 ton dilaporkan memuat 696.919 ton. Karena itu
angka bulanan bisa membengkak belasan sampai puluhan kali lipat, dan pembengkakan
itu **makin parah kalau petugas rajin mencatat** — tiap catatan tambahan menambah
satu pembacaan penuh.

### 1.3 Cacat 3 — Identitas kapal pecah (yang dilaporkan Pak Mustari)

Nama kapal diketik bebas tiap shift, dan `resolveShipOperation()` mencocokkannya
dengan `=` biasa:

```php
$query->where('ship_name', $shipName);   // "KM.NOAH ASYERA" ≠ "KM. Noah Asyera"
```

Pada backup, **6 kapal fisik** menghasilkan **9 ejaan**:

| Kapal | Ejaan yang terpakai |
|---|---|
| Golden Rejeki | `KM. Golden Rejeki`, `KM. GOLDEN REJEKI` |
| Malacca Strait | `KM. Malacca Strait`, `Km.Malacca Strait` |
| Noah Asyera | `KM. Noah Asyera`, `KM.NOAH ASYERA` |

Pola yang sama muncul di **semua** kegiatan yang mencatat nama kapal, bukan hanya
muat curah:

| Kegiatan | Contoh nyata |
|---|---|
| Muat kantong | `KLM.SUMBER UTAMA KELUARGA` / `KLM. Sumber Utama Keluarga` — persis contoh Pak Mustari. Juga `Klm.Nurul Yaqin IV` / `KLM. Nurul Yaqin IV`, dan `KM. Sentausa 8` / `KM.SENTAUSA 8` |
| Bongkar/muat container | `KM. Ayer Mas` / `KM.Ayer Mas` / `MV.AYER MAS` — tiga ejaan, tujuh baris aktivitas, satu kapal |
| Bongkar bahan baku | Struktur datanya sama dan sama-sama rawan, walau pada periode ini kebetulan ejaannya konsisten |

Penggelembungan jumlah kapal terukur pada database Juli 2026:

| Kegiatan | Nama mentah | Nama kanonik |
|---|---:|---:|
| Muat kantong | 18 | **12** |
| Muat curah + amoniak | 12 | **10** |
| Bongkar/muat container | 6 | **4** |
| Bongkar bahan baku | 4 | 4 |

Akibatnya berlapis. Untuk muat curah, muat kantong, dan muat amoniak — kegiatan
yang punya operasi kapal:

1. Satu pelayaran terpecah menjadi beberapa baris `ship_operations`. Saat backup
   diputar ulang memakai kode lama, enam kapal itu menghasilkan **10 operasi
   kapal** — Golden Rejeki 3, Malacca Strait 3, Noah Asyera 2. Sesudah perbaikan:
   tepat 6.
2. Hitungan **Kapal Dilayani** ikut menggelembung, karena penandanya
   `nama_kapal | waktu_sandar` — dan waktu sandar pun diketik berbeda
   (`2026-07-01T00:25` vs `2026-07-15T00:25` untuk pelayaran yang sama).
3. Pada muat kantong, kolom **Nilai Lalu** (`qty_loading_prev`) yang seharusnya
   membawa akumulasi shift sebelumnya ikut ter-reset ke nol setiap kali identitas
   pecah.
4. Yang paling penting: selama identitas pecah, **pembacaan COB satu pelayaran
   tidak bisa dirangkai**, sehingga perbaikan cacat 2 pun tidak akan bisa
   dikerjakan.

Sedangkan untuk bongkar bahan baku dan bongkar/muat container — yang sebelum
perbaikan ini **tidak punya** operasi kapal sama sekali, karena formnya memang
belum menyediakan tombol Simpan Operasi Kapal:

5. Rekap bulanan menghitung satu kapal sebagai beberapa kapal.
6. Panel Rincian Kegiatan menampilkannya sebagai beberapa baris, masing-masing
   hanya membawa sebagian tonase dan realisasi yang terlihat kecil — persis
   gejala yang membuat angka realisasi tampak tidak masuk akal.
7. Pencarian laporan gagal menemukan riwayat kapal bila petugas mengetiknya
   dengan ejaan yang berbeda dari yang tersimpan.

Karena itu cacat 3 wajib diperbaiki lebih dulu — bukan karena ia penyumbang angka
terbesar, tetapi karena ia syarat bagi perbaikan yang lain — dan perbaikannya
harus berlaku di seluruh kegiatan, bukan hanya muat curah.

### 1.4 Cacat sampingan — satuan COB campur aduk

Sebagian shift menulis COB dalam **ribuan ton**: `16.75` untuk 16.750 ton,
`31.39` untuk 31.390 ton. Terlihat jelas karena shift berikutnya menulis angka
penuhnya (`16750`, lalu `19.4`, lalu `20280`). Selain itu banyak baris berisi
`0` atau kosong — artinya *tidak ada penimbangan pada kejadian itu*, bukan muatan
nol.

---

## 2. Rancangan perbaikan: enam lapis

Dikerjakan berlapis, dari mencegah data buruk masuk sampai menyembuhkan data lama.
Tidak ada satu lapis pun yang bergantung pada kedisiplinan petugas.

```
Lapis 0  Pencegahan di form         → kapal ditemukan lalu dipilih, bukan diketik ulang
Lapis 1  Nama kanonik               → satu kapal, satu kunci
Lapis 2  Pencocokan berjenjang      → singkatan, nama terpotong, salah ketik
Lapis 3  Tonase = selisih, bukan jumlah  ← perbaikan angka yang sebenarnya
Lapis 4  Perbaikan data lama        → gabungkan yang terlanjur pecah
Lapis 5  Pagar kewajaran            → angka mustahil ditolak, bukan dijumlahkan
```

### Lapis 0 — Pencegahan di form

Sumber masalahnya adalah nama yang **diketik ulang** padahal kapalnya sudah ada di
sistem. Endpoint saran operasi kapal kini ikut mencari memakai bentuk kanonik kata
kunci, bukan hanya `LIKE` mentah:

```php
$canonical = ShipNameNormalizer::key($keyword);

if ($canonical !== '') {
    $search->orWhere('ship_name_key', 'like', '%'.$canonical.'%');
}
```

Dengan begitu `km golden rejeki`, `Golden-Rejeki`, `GOLDEN REJEKI`, bahkan
`sumber utama k` sama-sama menemukan kapal yang sudah tersimpan. Petugas
menemukan kapalnya lalu memilih dari saran — dan tidak perlu lagi ingat cara
mengetiknya persis seperti shift sebelumnya.

Selain pencarian saran, **pencarian laporan** (riwayat operasional dan arsip
manajer) juga ikut memakai bentuk kanonik. Mencari `golden rejeki` kini
menemukan laporan yang menulisnya `KM. GOLDEN REJEKI` maupun `Km.Golden-Rejeki`,
pada keempat kegiatan berkapal sekaligus.

### Lapis 1 — Nama kanonik (`ShipNameNormalizer::key()`)

Berkas: `app/Support/ShipNameNormalizer.php`

Kolom `ship_name_key` dipasang pada **lima** tabel — setiap tempat nama kapal
disimpan:

| Tabel | Kegiatan | Jenis operasi kapal |
|---|---|---|
| `ship_operations` | induk pelayaran | — |
| `loading_activities` | muat kantong | `muat_kantong` |
| `bulk_loading_activities` | muat curah & amoniak | `muat_curah`, `muat_amoniak` |
| `material_activities` | bongkar bahan baku | `bongkar_bahan_baku` |
| `container_activities` | bongkar & muat container | `container` |

Bongkar dan muat container berbagi satu jenis operasi (`container`) karena
keduanya memang satu bagian form yang sama — pembedanya penanda Empty/Full per
baris, bukan kapalnya.

Nama tampilan **tidak pernah diubah** — yang diketik petugas tetap tersimpan dan
tetap tercetak di laporan. Yang diseragamkan hanya identitasnya, disimpan pada
kolom baru `ship_name_key`.

Langkahnya deterministik:

1. Huruf disamakan menjadi kapital.
2. Semua yang bukan huruf/angka menjadi spasi (`KM.` `LPG/C` `Km-Malacca` rata).
3. Spasi ganda dirapatkan.
4. Awalan jenis kapal dibuang — `KM`, `KLM`, `KMP`, `MV`, `MS`, `MT`, `SS`, `TB`,
   `TK`, `KT`, `LCT`, `SPOB`, `SPB`, `OB`, `BG`, `LPG`, `LNG`, `AHTS`, `TUG`,
   `KAPAL`. Bukan bagian dari nama kapal, dan justru paling sering ditulis
   berbeda-beda.

Hasilnya:

| Diketik | Kunci kanonik |
|---|---|
| `KM. Golden Rejeki` | `GOLDEN REJEKI` |
| `KM. GOLDEN REJEKI` | `GOLDEN REJEKI` |
| `Km.Malacca Strait` | `MALACCA STRAIT` |
| `KLM.SUMBER UTAMA KELUARGA` | `SUMBER UTAMA KELUARGA` |
| `LPG/C Marianna 28` | `MARIANNA 28` |

Dua pengaman kecil: nama yang seluruhnya berupa awalan tidak habis terpangkas
(`KM` tetap `KM`), dan huruf tunggal hanya dibuang bila menempel pada awalan yang
sudah dibuang — sehingga `LPG/C` rapi tanpa memangkas kapal yang namanya memang
diawali satu huruf.

### Lapis 2 — Pencocokan berjenjang (`ShipNameNormalizer::score()`)

Kunci kanonik menyelesaikan huruf besar/kecil, tanda baca, dan awalan. Ia belum
menyelesaikan **singkatan** (`SUK`), **nama terpotong** (`Sumber Utama K`), dan
**salah ketik** (`Rezeki` vs `Rejeki`). Untuk itu ada skor kemiripan 0..1,
berjenjang dari yang paling meyakinkan:

| Jenjang | Skor | Contoh |
|---|---|---|
| Kunci kanonik sama | 1,00 | `KM. Golden Rejeki` = `KM. GOLDEN REJEKI` |
| Singkatan = huruf awal | 0,95 | `SUK` = `SUMBER UTAMA KELUARGA` |
| Nama terpotong | 0,90 | `Sumber Utama K` = `SUMBER UTAMA KELUARGA` |
| Kemiripan kata + huruf | dihitung | `Golden Rezeki` ≈ `Golden Rejeki` → 0,96 |

Ambang penerimaan **0,82**. Jenjang terakhir memakai dua ukuran yang dirata-rata:
irisan kata yang toleran salah ketik (`REJEKI` dan `REZEKI` dihitung satu kata),
dan jarak Levenshtein pada keseluruhan nama.

**Pagar keamanan** — yang berbeda harus tetap berbeda:

| Pasangan | Skor | Hasil |
|---|---:|---|
| `KM. Malacca Strait` vs `KM. Malacca Star` | 0,56 | beda ✓ |
| `KM Tanto Sejahtera` vs `KM Tanto Sejati` | 0,53 | beda ✓ |
| `KM Sinar Sulawesi` vs `KM Sinar Sulawesi 8` | 0,77 | beda ✓ |
| `MV. Buana Gemilang II` vs `MV. Buana Gemilang` | 0,75 | beda ✓ |

Dua yang terakhir disengaja: **kapal kembar dibedakan justru oleh angka di
belakang namanya**, jadi selisih yang seluruhnya berupa angka atau angka Romawi
tidak pernah dianggap nama terpotong.

Pencocokan mirip juga dibatasi ruangnya. Ia hanya boleh menyentuh operasi yang
**masih berjalan** — belum berstatus selesai, dan laporan terakhirnya belum lewat
7 hari:

```php
private function isOpenVoyage(ShipOperation $operation): bool
{
    if ($operation->status === ShipOperation::STATUS_COMPLETED) {
        return false;
    }

    $lastSeen = $operation->last_report_date ?? $operation->updated_at ?? $operation->created_at;

    return $lastSeen === null
        || Carbon::parse($lastSeen)->greaterThanOrEqualTo(now()->subDays(BulkTonnageService::VOYAGE_GAP_DAYS));
}
```

Kapal yang sudah berangkat tidak lagi menjadi kandidat, sehingga nama mirip milik
kapal berlainan pada bulan berbeda tidak bisa saling tertarik.

Urutan lengkap di `resolveShipOperation()`:

```
1. Nomor operasi kapal dari form   → dipakai apa adanya (perilaku lama)
2. Nama persis sama                → perilaku lama, tidak berubah
3. Kunci kanonik sama              → BARU
4. Nama mirip, pelayaran berjalan  → BARU, dicatat ke log aplikasi
5. Tidak ada yang cocok            → operasi kapal baru
```

Satu penyaring lama sengaja **dilepas** untuk muat curah: dulu kandidat disaring
`where('commodity', ...)`, padahal komoditas pun diketik bebas
(`UC SUB` vs `UC.BERSUBSIDI` untuk kapal yang sama) — penyaring itulah yang
memecah KM. Noah Asyera menjadi dua operasi. Untuk muat kantong, penyaring
`wo_number` tetap dipertahankan karena nomor WO memang menandai pekerjaan berbeda.

### Lapis 3 — Tonase adalah SELISIH, bukan jumlah ⭐

Berkas: `app/Services/BulkTonnageService.php`

Inilah perbaikan yang benar-benar mengembalikan angkanya.

**Gagasannya.** Tonase satu pelayaran = COB terakhir − COB saat mulai (0 ketika
kapal sandar kosong). Tetapi angka per shift dan per regu tetap harus bisa
dipotong, jadi selisih itu **dibagikan kembali** ke baris log yang menyebabkannya,
disimpan pada kolom baru `bulk_loading_logs.cob_delta`.

Sifat yang didapat dari cara ini:

- `SUM(cob_delta)` benar di **semua** tingkat — per shift, per regu, per bulan,
  per kapal — tanpa rumus khusus di masing-masing tempat.
- Jumlah seluruh shift satu pelayaran otomatis sama dengan COB terakhirnya
  (teleskopik).
- Katalog kegiatan cukup diganti satu baris; seluruh grafik, KPI, panel rincian,
  dan ekspor Excel ikut benar tanpa disentuh:

```php
// sesudah
'column' => 'bulk_loading_logs.cob_delta',
```

**Algoritmanya** — telusuri pembacaan satu pelayaran secara kronologis, dengan
patokan *nilai tertinggi sejauh ini* (high-water mark):

```
patokan = 0
untuk tiap pembacaan, urut menurut tanggal → shift → urutan baris:

    1. kosong atau 0?         → delta 0, patokan tidak berubah
    2. perbaiki satuan        → "16.75" menjadi 16.750 bila memenuhi syarat
    3. pelayaran baru?        → patokan kembali 0
    4. di luar kewajaran?     → dicatat, tetapi delta 0
    5. delta = maks(0, nilai − patokan)
       patokan = maks(patokan, nilai)
```

Urutan langkah 2 dan 3 **tidak boleh dibalik**. Kalau satuan belum diseragamkan,
`37.04` pada kapal yang sudah memuat 35.000 ton akan terbaca sebagai muatan
anjlok — pelayaran dipotong, dan pembacaan berikutnya (38.170) dihitung penuh
sebagai tonase baru. Persis kekeliruan itu sempat terjadi saat perbaikan ini
dikerjakan dan tertangkap oleh uji.

Empat kebiasaan lapangan yang ditangani langkah-langkah di atas:

| Kejadian nyata | Perlakuan |
|---|---|
| COB kosong / `0` | Diabaikan. Artinya tidak ada penimbangan, bukan muatan nol. |
| `16.75` untuk 16.750 ton | Dikembalikan ke ton penuh, dengan tiga syarat sekaligus. |
| `4.863` lalu `4.280` (draft survey ulang) | Koreksi, bukan bongkar. Delta 0, patokan tetap. |
| Kapal yang sama datang lagi | Rangkaian dipotong, pelayaran baru mulai dari nol. |

**Syarat perbaikan satuan** sengaja berlapis supaya kapal kecil yang memang
bermuatan ratusan ton tidak ikut dikali seribu:

```php
if ($ceiling <= 0.0 || $reading >= 1000.0 || $reading >= $highWater) {
    return $reading;                      // bukan kasus ribuan ton
}

$rescaled = $reading * 1000.0;

return $rescaled >= $highWater && $rescaled <= $ceiling
    ? $rescaled
    : $reading;
```

Artinya sebuah angka baru dikali seribu bila **ketiganya** terpenuhi: angkanya
tampak mundur dari pembacaan tertinggi, kelipatan seribunya justru melanjutkan
pembacaan itu, dan hasilnya masih masuk kapasitas kapal. Pada data nyata syarat
ini memperbaiki `12.5`, `16.75`, `19.4`, `31.39`, `31.68`, `32`, `37.04`,
`38.17`, `39.97` — dan **tidak menyentuh** `200` milik Malacca Strait (kapasitas
9.000) maupun `4420.25` milik Marianna 28.

**Penanda pelayaran baru** butuh dua tanda bersamaan, supaya koreksi kecil tidak
salah dibaca sebagai kedatangan berikutnya:

- jeda antarpembacaan ≥ **7 hari** (`VOYAGE_GAP_DAYS`), **atau**
- kapal sempat terisi ≥ 50 % kapasitas lalu pembacaannya anjlok di bawah
  setengah patokan.

**Kapan dihitung ulang.** Menyimpan satu laporan bisa mengubah tonase shift-shift
*sesudahnya* pada pelayaran yang sama, jadi yang dihitung ulang adalah
pelayarannya — bukan baris laporan itu saja:

- `ReportOpsController::store()` dan `update()` memanggil
  `recalculateForReport()` di dalam transaksi yang sama.
- Laporan **draft tidak ikut dirangkai** — angkanya masih bisa berubah, dan
  statistik manajer pun tidak menghitungnya.
- Status laporan masih bisa berubah setelah disimpan (draft menjadi terkirim,
  laporan disetujui), jadi seluruh pelayaran dirangkai ulang sekali sehari:

```
Schedule::command('ops:repair-ship-identity --recalculate-only')->dailyAt('01:45');
```

Penulisan hasilnya memakai satu `UPDATE ... CASE` per 500 baris, bukan satu
`UPDATE` per baris, supaya menghitung ulang seluruh riwayat tetap murah.

### Lapis 3b — Operasi kapal untuk kegiatan bongkar

Sampai perbaikan ini, hanya pemuatan yang punya operasi kapal. Bongkar bahan baku
dan bongkar/muat container mencatat nama kapalnya sebagai teks bebas tanpa induk
apa pun, sehingga keterangan kapal harus diketik ulang tiap shift — dan justru
pengetikan ulang itulah sumber ejaan yang berbeda-beda.

Keduanya kini memakai mekanisme yang sama persis dengan pemuatan:

- **Jenis operasi baru** — `bongkar_bahan_baku` dan `container`. Bongkar dan muat
  container berbagi satu jenis karena memang satu bagian form; pembedanya penanda
  Empty/Full per baris, bukan kapalnya.
- **Form** mendapat kolom tersembunyi `ship_operation_material_id_N` /
  `ship_operation_container_id_N` dan pilihan status **Masih Berjalan / Selesai**,
  sama seperti bagian muat.
- **Dropdown saran kapal** otomatis aktif di kedua bagian: `shipOperationConfig()`
  mengenali `ship_name_material_N` dan `ship_name_container_N`, dan
  `prepareShipOperationFields()` sudah menyapu semua input `ship_name_*`.
- **Penyambungan lintas shift** memakai `resolveShipOperation()` yang sama, jadi
  seluruh lapis 2 — kunci kanonik, singkatan, nama terpotong, salah ketik, jendela
  pelayaran berjalan — langsung berlaku tanpa kode tambahan.

Yang sengaja TIDAK dibawa: akumulasi otomatis "Nilai Lalu". Bongkar mencatat
qty_prev per jenis bahan baku dan per penanda Empty/Full, bukan satu angka per
kapal, sehingga penurunannya tidak sesederhana muat kantong.

### Lapis 4 — Menyembuhkan data lama

Perbaikan di atas hanya berlaku untuk laporan baru. Riwayat yang sudah tersimpan
dirapikan oleh perintah:

```bash
php artisan ops:repair-ship-identity --dry-run
```

```bash
php artisan ops:repair-ship-identity
```

Empat langkah, semuanya aman diulang:

1. **Isi nama kanonik** pada kelima tabel yang menyimpan nama kapal.
1b. **Pulihkan nilai COB dari payload laporan.** Baris log dipasangkan dengan
   baris payload memakai penyaring yang sama dengan controller (baris dianggap
   ada bila salah satu dari jam, aktivitas, atau COB terisi), lalu diurutkan
   menurut id. Bila jumlahnya tidak cocok, kegiatan itu dilewati dan dilaporkan
   — lebih baik membiarkan satu angka apa adanya daripada memasangkannya ke
   baris yang keliru. Laporan lama tanpa payload juga dibiarkan.
2. **Gabungkan operasi kapal kembar.** Dua operasi disatukan bila jenisnya sama,
   namanya kanonik sama atau mirip, **dan** rentang tanggal laporannya
   bersinggungan. Syarat tanggal itulah yang menjaga kunjungan kapal yang sama
   pada bulan berbeda tetap terhitung dua pelayaran. Khusus muat kantong,
   penggabungan ditahan bila nomor WO keduanya sama sekali tidak beririsan —
   `3460429762` dan `3460429762, 3460431016` boleh disatukan (WO kedua ditambahkan
   di tengah pemuatan), dua WO yang tak berhubungan tidak.
   Operasi yang dipertahankan mewarisi keterangan terlengkap dan nama yang
   terakhir diketik.
3. **Sambungkan aktivitas yatim** — baris yang tersimpan tanpa `ship_operation_id`,
   yaitu jejak "petugas tidak menekan Simpan Operasi Kapal".
3c. **Bentuk operasi kapal untuk riwayat bongkar.** Laporan bongkar yang ditulis
   sebelum kegiatan ini punya operasi kapal tidak akan pernah tersambung oleh
   langkah 3, karena memang tidak ada induk yang bisa dicocokkan. Di sini
   riwayatnya dibentuk ulang: baris dikelompokkan menurut nama kanonik, lalu
   dipotong menjadi kunjungan terpisah setiap kali jedanya melewati 7 hari.
4. **Hitung ulang `cob_delta`** untuk seluruh pelayaran, lalu cetak tabel
   perbandingan cara lama vs cara baru per kapal.

Pada backup Juli 2026, langkah 2 menggabungkan 3 operasi muat kantong yang lolos
dari penyaring WO (`Klm.Nurul Yaqin IV`, `KM.SENTAUSA 8`).

### Lapis 5 — Pagar kewajaran

Terakhir, angka yang mustahil ditolak alih-alih ikut dijumlahkan:

- Pembacaan di atas **kapasitas kapal × 1,15** (`CAPACITY_TOLERANCE`) dicatat pada
  `cob_normalized` supaya bisa ditelusuri, tetapi tidak menyumbang tonase.
- Delta tidak pernah negatif — muatan tidak bisa berkurang di tengah pemuatan.
- Kolom `cob_normalized` menyimpan pembacaan setelah satuannya diseragamkan,
  sehingga selisih antara yang diketik dan yang dihitung selalu bisa diperiksa
  ulang tanpa menjalankan program.

---

## 3. Perubahan pada basis data

Migrasi `2026_07_31_000001_add_ship_identity_and_bulk_tonnage_columns.php`:

| Tabel | Kolom | Guna |
|---|---|---|
| `ship_operations` | `ship_name_key` | Nama kanonik, terindeks bersama `type` |
| `bulk_loading_activities` | `ship_name_key` | Pengelompokan pelayaran muat curah/amoniak |
| `loading_activities` | `ship_name_key` | Sama, untuk muat kantong |
| `bulk_loading_logs` | `cob_normalized` | Pembacaan setelah satuan diseragamkan |
| `bulk_loading_logs` | `cob_delta` | Pertambahan nyata — inilah yang dijumlahkan |
| `bulk_loading_logs` | `cob` | Diubah `integer` → `decimal(15,2)` |

Migrasi `2026_07_31_000002_add_ship_name_key_to_unloading_tables.php` menambahkan
kolom yang sama untuk kegiatan bongkar:

| Tabel | Kolom | Guna |
|---|---|---|
| `material_activities` | `ship_name_key` | Identitas kapal bongkar bahan baku |
| `container_activities` | `ship_name_key` | Identitas kapal bongkar/muat container |

Migrasi `2026_07_31_000003_add_ship_operation_to_unloading_tables.php`
menghubungkan kegiatan bongkar ke induk pelayarannya:

| Tabel | Kolom | Guna |
|---|---|---|
| `material_activities` | `ship_operation_id` | Induk pelayaran bongkar bahan baku |
| `container_activities` | `ship_operation_id` | Induk pelayaran bongkar/muat container |

Kolom `cob` diubah tipenya karena muatan cair (amoniak) ditimbang sampai dua angka
di belakang koma — `4420.25` ton terpotong menjadi `4420` selama tipenya masih
integer. Nilai lamanya tidak dibuang; ia tetap tersimpan apa adanya sebagai
pembacaan asli.

Penanda kunjungan kapal pada `OperationalPerformanceService` juga diubah. Dulu
`nama_kapal | waktu_sandar` — dua-duanya diketik bebas. Sekarang ada dua bentuk,
tergantung kegiatannya punya operasi kapal atau tidak.

Kegiatan **dengan** operasi kapal (muat kantong, muat curah, muat amoniak) —
nomor operasi lebih dulu, nama kanonik sebagai cadangan untuk baris lama:

```sql
CASE WHEN ship_operation_id IS NOT NULL
     THEN CONCAT('operasi:', ship_operation_id)
     ELSE CONCAT('nama:', COALESCE(NULLIF(ship_name_key,''), ship_name, ''), '|', COALESCE(berthing_time, ''))
END
```

Kegiatan **tanpa** operasi kapal (bongkar bahan baku, container) — nama kanonik
saja:

```sql
COALESCE(NULLIF(ship_name_key, ''), ship_name, '')
```

Keduanya dipakai di tiga tempat sekaligus, sehingga tidak ada satu pun jalur yang
tertinggal:

| Tempat | Yang diperbaiki |
|---|---|
| `recap.count` pada katalog kegiatan | Kolom **Kapal** pada rekap bulanan, ketujuh kegiatan |
| Panel rincian (`baggedDetail`, `bulkDetail`, `materialDetail`, `containerDetail`) | Pengelompokan baris tabel dan metrik **Kapal dilayani** |
| `shipVisitSources()` | Grafik dan KPI kunjungan kapal |

Dengan begitu satu kapal tidak lagi muncul sebagai beberapa baris yang
masing-masing hanya membawa sebagian tonase — dan realisasinya kembali terbaca
benar.

---

## 4. Cara menjalankan

### Memasang data uji dari backup

```bash
php artisan migrate
```

```bash
php artisan db:seed --class=BackupOperationalReportSeeder
```

Seeder ini **memutar ulang payload form asli apa adanya** lewat
`ReportOpsController::store()` — jalur simpan yang sama persis dengan yang dipakai
petugas. Karena itu seluruh kebiasaan pengisian di lapangan ikut terbawa, termasuk
yang keliru, dan cacatnya bisa direproduksi kapan saja.

> Seeder ini sengaja **tidak** didaftarkan di `DatabaseSeeder`: periodenya
> bertabrakan dengan data contoh `OperationalReportSeeder`, yang juga mengisi
> tanggal, shift, dan regu yang sama untuk Juli 2026.

### Merapikan data yang sudah ada — WAJIB sekali setelah migrasi

Kolom `cob_delta` lahir dalam keadaan kosong. Selama belum diisi, **tonase muat
curah dan muat amoniak akan terbaca 0** di seluruh menu. Karena itu perintah
berikut harus dijalankan satu kali setelah `migrate`, baik di server maupun di
komputer pengembang.

Lihat rencananya lebih dulu:

```bash
php artisan ops:repair-ship-identity --dry-run
```

Lalu jalankan:

```bash
php artisan ops:repair-ship-identity
```

Bila belum ingin menggabungkan operasi kapal dan hanya butuh angkanya kembali
benar, cukup jalankan mode hitung ulang di bawah — tidak ada baris yang digabung
atau dihapus.

### Menghitung ulang tonase saja

```bash
php artisan ops:repair-ship-identity --recalculate-only
```

Sudah terjadwal otomatis setiap hari pukul 01.45.

---

## 5. Bukti pengujian

Seluruh berkas uji ada di `tests/`. Suite lengkap: **277 uji, semua lulus**.

| Berkas | Yang dibuktikan |
|---|---|
| `tests/Unit/ShipNameNormalizerTest.php` | Enam ejaan `Sumber Utama Keluarga` menghasilkan satu kunci; singkatan, nama terpotong, dan salah ketik dikenali; kapal kembar tetap terpisah |
| `tests/Feature/BulkTonnageServiceTest.php` | Tonase satu pelayaran = COB terakhir; selisih terbagi benar ke tiap shift; `0`/kosong diabaikan; koreksi turun tidak menambah; ribuan ton dikembalikan; kapal kecil tidak dikali seribu; kunjungan berikutnya terpisah; angka mustahil ditolak; draft tidak dirangkai; hitung ulang idempoten |
| `tests/Feature/ShipIdentityConsistencyTest.php` | Tiga ejaan satu kapal menjadi satu baris dan satu hitungan pada **kelima** kegiatan berkapal |
| `tests/Feature/UnloadingShipOperationTest.php` | Menyimpan laporan bongkar membentuk operasi kapal; ejaan berbeda antar shift menempel pada operasi yang sama; kapal berstatus Selesai tidak menarik kunjungan berikutnya; perintah perbaikan membentuk induk untuk data bongkar lama dan memotongnya per kunjungan; saran kapal melayani jenis bongkar |
| `tests/Unit/TonnageNumberTest.php` | Titik dengan tiga angka dibaca sebagai pemisah ribuan, satu-dua angka sebagai koma desimal; dua jenis pemisah sekaligus; COB nol dianggap tidak ada penimbangan |
| `tests/Feature/RestoreCorruptedCobTest.php` | Pemisah ribuan yang sudah tersimpan benar TIDAK ikut diubah; koma desimal sejati dipulihkan; baris payload kosong tidak menggeser pemasangan; laporan tanpa payload dibiarkan |
| `tests/Feature/BackupOperationalReportSeederTest.php` | Reproduksi cacat dari data nyata, lalu 66.330,25 ton dan tiap pelayaran ≤ kapasitas × 1,15 |

`ShipIdentityConsistencyTest` sudah diuji kepekaannya: dengan penanda kapal
dikembalikan ke nama mentah, empat dari lima ujinya gagal — masing-masing
melaporkan 3 baris/3 kapal dan bukan 1.

Contoh yang paling langsung menggambarkan masalahnya:

```php
public function test_tonase_satu_pelayaran_sama_dengan_pembacaan_cob_terakhir(): void
{
    $this->voyage('KM. Contoh', 9000, [
        ['2026-07-10', 'Pagi',  [200]],
        ['2026-07-10', 'Sore',  [970, 2140]],
        ['2026-07-10', 'Malam', [3540, 4540]],
        ['2026-07-11', 'Pagi',  [5780, 6040]],
        ['2026-07-11', 'Sore',  [7210, 8350]],
    ]);

    app(BulkTonnageService::class)->recalculate();

    // Cara lama menjumlahkan sembilan pembacaan odometer dan menghasilkan
    // 38.770 ton untuk kapal berkapasitas 9.000 ton.
    $this->assertSame(38_770.0, (float) BulkLoadingLog::sum('cob'));
    $this->assertSame(8_350.0, (float) BulkLoadingLog::sum('cob_delta'));
}
```

Tampilan manajer untuk Juli 2026 sesudah perbaikan:

```
Total Tonase Juli 2026 : 77.835,85 Ton

  Pemuatan Pupuk Kantong             7.385,60 Ton
  Pemuatan Urea Curah               66.330,25 Ton
  Bongkar Container (Empty)            141,00 Teus
  Muat Container (Full)                 60,00 Teus
  Trucking Pengiriman Pupuk Kantong  4.120,00 Ton
```

---

## 5b. Simulasi deploy

Seluruh rangkaian diuji pada salinan basis data yang dibuat menyerupai keadaan
server: data backup Juli dimuat, lalu nilai COB-nya sengaja dirusakkan memakai
pembantu `integer()` versi lama, dan `cob_delta` dikosongkan.

| Tahap | SUM(cob) | Total tonase |
|---|---:|---:|
| Keadaan awal (persis seperti server sekarang) | **1.637.713** | — |
| Sesudah `migrate` + `ops:repair-ship-identity` | 1.200.108 | **66.330,25** |

Angka awalnya cocok persis dengan yang dikeluhkan Pak Mustari. Selisih SUM(cob)
sebelum dan sesudah — 437.605 ton — seluruhnya berasal dari satu nilai Marianna
28 yang koma desimalnya terbuang.

Keluaran perintahnya:

```
Isi nama kanonik .. DONE
COB dipulihkan dari payload laporan: 1 nilai
gabung [muat_kantong] #7 "KLM. Nurul Yaqin IV" → #6 "Klm.Nurul Yaqin IV"
gabung [muat_kantong] #13 "KM.SENTAUSA 8" → #10 "KM. Sentausa 8"
gabung [muat_kantong] #14 "KM. Sentausa 8" → #10 "KM. Sentausa 8"
Selesai: 3 operasi kapal digabung, 95 baris log dihitung ulang.
```

Hasilnya identik dengan hitungan atas data yang tidak pernah rusak — 66.330,25
ton — sehingga perbaikan ini aman dijalankan langsung di atas data produksi.

---

## 6. Catatan dan batasan

1. **Angka "sekitar 600 ribu" belum terkonfirmasi.** Perhitungan baru menghasilkan
   66.330 ton untuk 8–29 Juli. Angka itu konsisten dengan kapasitas kelima kapal
   yang dilayani (41.130 + 8.630 + 8.350 + 4.420 + 3.800), jadi 600 ribu ton
   kemungkinan mengacu pada periode atau cakupan lain — misalnya akumulasi tahun
   berjalan, atau seluruh jenis kegiatan. Perlu dikonfirmasi ke Pak Mustari
   sebelum dijadikan patokan.

2. **Backup dimulai 8 Juli.** MV. Buana Gemilang II sudah membawa 3.100 ton pada
   pembacaan pertamanya karena pemuatan dimulai 7 Juli — di luar cakupan berkas.
   Muatan itu tercatat pada shift 8 Juli. Ini batas data, bukan salah hitung; pada
   basis data produksi yang riwayatnya utuh, shift 7 Juli akan menerima bagiannya.

3. **Pencocokan mirip dicatat, bukan diam-diam.** Setiap kali lapis 2 menyatukan
   dua nama yang tidak persis sama, kejadiannya masuk log aplikasi lengkap dengan
   skornya — sehingga bisa ditinjau bila suatu saat ada kapal yang salah
   tersambung:

   ```php
   Log::info('Nama kapal dicocokkan berdasarkan kemiripan.', [
       'diketik' => $shipName,
       'tersimpan' => $best->ship_name,
       'ship_operation_id' => $best->id,
       'skor' => $bestScore,
   ]);
   ```

4. **Nama tampilan tidak pernah diubah.** Yang diketik petugas tetap tersimpan dan
   tetap tercetak di laporan; yang diseragamkan hanya identitas di baliknya.
   Perbaikan ini tidak menghapus atau menimpa satu pun catatan lapangan.

5. **Panel rincian sekarang satu baris per KUNJUNGAN, bukan per nama kapal.**
   Ini perubahan tampilan yang disengaja: kapal yang datang dua kali dalam satu
   periode menghasilkan dua baris, masing-masing dengan realisasi terhadap
   kapasitasnya sendiri. Sebelumnya keduanya menyatu menjadi satu baris dengan
   realisasi yang menyesatkan. Pemisah kunjungan adalah operasi kapal, atau —
   untuk baris lama — jeda 7 hari antar laporan.

6. **Aktivitas muat lama masih ada yang tanpa induk.** Perintah perbaikan hanya
   MEMBENTUK operasi kapal baru untuk kegiatan bongkar, karena di sanalah
   induknya memang belum pernah ada. Untuk muat kantong dan muat curah, baris
   tanpa `ship_operation_id` hanya disambungkan ke operasi yang sudah ada — tidak
   dibuatkan yang baru, supaya logika nomor WO dan pemotongan pelayaran tidak
   ditebak-tebak oleh program. Baris seperti ini tetap terhitung benar lewat nama
   kanonik.

7. **Yang tersisa untuk dikerjakan.** Peringatan di form saat petugas mengetik
   nama yang mirip kapal aktif ("Maksud Anda: KM. Golden Rejeki?") belum dibuat —
   yang sudah jalan baru pencarian sarannya dan pencarian laporan. Selain itu,
   panel mutu data yang mendaftar pembacaan COB mencurigakan akan mempercepat
   penelusuran, tetapi angkanya sendiri sudah tidak lagi terpengaruh.
