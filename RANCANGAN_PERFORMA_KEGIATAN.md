# Rancangan: Halaman Performa Operasi Dipecah per Jenis Kegiatan

Tujuannya memecah halaman Performa Operasional per jenis kegiatan tanpa membuat
halamannya jadi lambat.

Ditulis: 27 Juli 2026
Status: **sudah dieksekusi** — lihat [Bagian 12](#12-hasil-eksekusi) untuk hasil
terukur dan penyimpangan dari rancangan awal

---

## 1. Keputusan yang Sudah Final

| # | Pertanyaan | Jawaban | Sumber |
|---|---|---|---|
| 1 | Bongkar dan muat container dipisah jadi 2 poin? | **Tidak.** Di form laporan keduanya satu section, jadi di performa tetap **satu panel** "Bongkar/Muat Container" | Keputusan tim |
| 2 | Satuan kolom qty container? | **Teus** (jumlah box), bukan ton | Pak Mustari |
| 3 | Container dikeluarkan dari Total Tonase? | **Ya.** "Kontainer itu satuannya Teus sehingga tidak bisa digabung dengan Tonase" | Pak Mustari |
| 4 | Kolom `turba_deliveries.truck_name` isinya apa? | **Tujuan pengiriman**, bukan nama truk — terbukti dari data (`Buffer Stock`, `Buffer Stufing`) | Pemeriksaan data |

### Akibat keputusan di atas

- Panel performa menjadi **5**, bukan 6.
- **Tidak ada migrasi database.** Kolom `container_activities.activity_type` yang
  sempat dirancang **dibatalkan**.
- **Form Laporan Operasi tidak disentuh sama sekali.** Begitu juga PDF dan Excel
  laporan harian.
- Perkiraan waktu kerja turun dari 5–6 hari menjadi **3–4 hari**.
- Utang teknis yang dicatat tapi **tidak dikerjakan sekarang**: nama kolom
  `truck_name` menyesatkan karena isinya tujuan pengiriman. Menggantinya berarti
  menyentuh form, controller, PDF, dan Excel tanpa manfaat langsung ke pengguna.

---

## 2. Lima Panel Kegiatan

| # | Kunci | Nama panel | Tabel induk | Tabel rincian | Kolom nilai | Satuan |
|---|---|---|---|---|---|---|
| 1 | `muat_kantong` | Pemuatan Pupuk Kantong | `loading_activities` | `loading_timesheets` | `qty_loading_current` | Ton |
| 2 | `muat_curah` | Pemuatan Urea Curah | `bulk_loading_activities` | `bulk_loading_logs` | `cob` | Ton |
| 3 | `bongkar_bahan_baku` | Bongkar Bahan Baku | `material_activities` | `material_items` | `qty_current` | Ton |
| 4 | `container` | Bongkar/Muat Container | `container_activities` | `container_items` | `qty_current` | **Teus** |
| 5 | `trucking_turba` | Trucking Pengiriman Pupuk Kantong | `turba_activities` | `turba_deliveries` | `qty_current` | Ton |

Semua sumber sudah tersedia di database — **tidak ada tabel maupun kolom baru**.

---

## 3. Aturan Satuan (bagian terpenting dari revisi ini)

Sekarang `tonnageByActivity()` menjumlahkan kelima sumber menjadi satu angka
"Total Tonase" — artinya **Teus container ikut terhitung sebagai Ton** di kartu
KPI dashboard maupun di donut Komposisi Kegiatan.

Aturan baru:

1. Katalog kegiatan menyimpan `unit` dan penanda `countsToTonnage`.
2. **Total Tonase = 4 kegiatan bersatuan Ton** (kantong, curah, bahan baku, trucking).
3. **Container tidak pernah masuk penjumlahan Ton.** Angkanya berdiri sendiri
   sebagai "Volume Container" dengan satuan Teus.
4. Donut Komposisi Kegiatan hanya berisi 4 kegiatan Ton, dengan catatan kaki:
   "Container dihitung terpisah karena bersatuan Teus."
5. Metrik turunan yang memakai tonase (Ton per shift, kontribusi regu, tren
   bulanan) otomatis ikut aturan ini.

> **Perlu disampaikan ke manajemen:** angka Total Tonase di dashboard akan
> terlihat lebih kecil daripada sebelumnya. Ini koreksi satuan, bukan penurunan
> kinerja, dan angka periode lampau ikut berubah karena dihitung ulang dari data
> yang sama.

---

## 4. Kondisi Sekarang (hasil pengukuran, bukan perkiraan)

Diukur pada data saat ini (17 laporan, 4 regu, 3 shift), memanggil
`OperationalPerformanceService::performanceReport()` untuk periode bulan berjalan:

```
queries = 158
time    = 78,1 ms (murni waktu SQL)
```

Fakta lain yang relevan:

- Hasilnya di-cache 10 menit lewat `Cache::remember()` di
  [ManajerController.php:247](app/Http/Controllers/ManajerController.php:247).
- Driver cache adalah **`database`** (`CACHE_STORE=database`), jadi satu payload
  besar berarti satu baris besar yang dibaca + di-unserialize tiap request.
- Cache performa **tidak pernah di-`forget`**. `forgetManagerStatsCache()` hanya
  membersihkan kunci dashboard/arsip/kpi, bukan `manajer.performa.*` — akibatnya
  laporan yang baru disetujui bisa tertunda muncul sampai 10 menit.
- Ekspor Excel memakai payload yang sama persis, jadi perubahan struktur data
  ikut terbawa ke [PerformanceExportService.php](app/Services/PerformanceExportService.php).

### Kenapa 158 query?

`summaryFor()` dipanggil berulang, dan tiap panggilannya sendiri berisi 9 query
(5 sumber tonase + jumlah laporan + kerusakan + 2 sumber kunjungan kapal):

```
summaryFor dipanggil untuk:  periode ini, periode pembanding,
                             tiap regu × 2 periode  (4 regu → 8×),
                             tiap shift             (3 shift → 3×)
= 13 panggilan × 9 query     ≈ 117 query
+ monthlyMetrics             ≈ 9 query
+ shiftTrend                 = 5 query
+ workload                   ≈ 12 query
+ shipList / lembur / filter ≈ 15 query
```

**Ini titik kritisnya.** Jumlah query tumbuh mengikuti *(jumlah regu × jumlah
shift × jumlah kegiatan)*. Kalau 5 panel detail ditambahkan dengan pola yang sama,
angkanya naik ke kisaran 300 query per halaman. Karena itu Tahap 1 memperbaiki
pola query lebih dulu, sebelum panel kegiatan ditambahkan.

---

## 5. Isi Tiap Panel

### 5.1 Pola seragam

```
┌─ KARTU RINGKAS (selalu tampil, 5 buah dalam grid) ──────────┐
│  Ikon + Nama kegiatan                                        │
│  NILAI UTAMA + satuan          [badge Δ vs periode pembanding]│
│  sparkline 6 bulan                                            │
│  2 metrik pendukung (mis. "4 kapal · 8 laporan")             │
└──────────────────────────────────────────────────────────────┘

┌─ PANEL DETAIL (dimuat saat kegiatan dibuka) ────────────────┐
│  4 metrik sekunder khas kegiatan                             │
│  Grafik tren bulanan kegiatan ini                            │
│  Peringkat regu untuk kegiatan ini (bar)                     │
│  Tabel rincian (maks. 50 baris + tautan Ekspor)              │
└──────────────────────────────────────────────────────────────┘
```

### 5.2 Rincian per kegiatan

**1. Pemuatan Pupuk Kantong** — nilai utama: **Tonase muat (Ton)**

| Jenis | Isi |
|---|---|
| Metrik sekunder | Kapal dilayani · Tonase delivery gudang→kapal · Rasio kerusakan (%) · Realisasi rata-rata terhadap kapasitas (%) |
| Grafik | Tren bulanan + peringkat regu |
| Tabel | Kapal · Agen · Dermaga · Tujuan · Kapasitas · Termuat · Realisasi · Kerusakan · TKBM · Waktu tiba |

**2. Pemuatan Urea Curah** — nilai utama: **Tonase COB (Ton)**

| Jenis | Isi |
|---|---|
| Metrik sekunder | Kapal dilayani · Jumlah entri log jam · Realisasi terhadap kapasitas (%) · **Rata-rata jeda sandar → mulai muat (jam)** |
| Grafik | Tren bulanan + peringkat regu |
| Tabel | Kapal · Agen · Stevedoring · Komoditi · Dermaga · Kapasitas · COB · Realisasi · Sandar · Mulai muat · Jeda |

**3. Bongkar Bahan Baku** — nilai utama: **Tonase bongkar (Ton)**

| Jenis | Isi |
|---|---|
| Metrik sekunder | Kapal dilayani · Jumlah kegiatan · Jenis bahan baku berbeda · Rata-rata ton per kegiatan |
| Grafik | Tren bulanan + **komposisi per `raw_material_type`** (nilai tambah utama panel ini) |
| Tabel | Kapal · Agen · Dermaga · Kapasitas · Jenis bahan baku · Tonase · Jam kerja |

**4. Bongkar/Muat Container** — nilai utama: **Volume (Teus)**

| Jenis | Isi |
|---|---|
| Metrik sekunder | Kapal dilayani · Kapasitas Empty (Teus) · Kapasitas Full (Teus) · Rata-rata Teus per kegiatan |
| Grafik | Tren bulanan (sumbu Teus, **terpisah dari grafik tonase**) + peringkat regu |
| Tabel | Kapal · Agen · Dermaga · Jam kerja (`time_text`) · Sekarang · Lalu · Total · Ket |
| Catatan | Seluruh angka panel ini diberi label "Teus" secara eksplisit, termasuk di tooltip grafik dan di Excel |

**5. Trucking Pengiriman Pupuk Kantong** — nilai utama: **Tonase terkirim (Ton)**

| Jenis | Isi |
|---|---|
| Metrik sekunder | Jumlah rit/DO · Jumlah tujuan pengiriman · Rata-rata muatan per rit · Realisasi terhadap kapasitas angkut (%) |
| Grafik | Tren bulanan + **peringkat tujuan pengiriman** (dari `truck_name`, mis. Buffer Stock / Buffer Stufing) |
| Tabel | Tujuan · Nomor DO/SO · Marking · Kapasitas · Terkirim · Akumulasi |

### 5.3 Yang **tidak** dipecah per kegiatan

Tetap berlaku lintas kegiatan dan tidak diduplikasi ke 5 panel, supaya halaman
tidak menggelembung:

- Beban Kerja (personil, lembur, relief, ketepatan lapor)
- Peringkat Lembur
- Rasio Kerusakan (hanya relevan untuk muat kantong — tetap di tempatnya)
- Tonase per Shift

### 5.4 Metrik yang sengaja ditunda

Produktivitas **ton per jam** belum bisa dihitung akurat karena data waktunya
belum terstruktur:

- `material_activities.working_hours` dan `turba_activities.working_hours` = **string**
- `container_items.time_text` = teks rentang bebas, mis. `"23:00 - 04:00"`
- `loading_timesheets` = daftar (jam, aktivitas ketikan bebas), tanpa penanda mulai/selesai

Pengecualiannya hanya urea curah, yang punya `berthing_time` dan
`start_loading_time` bertipe `dateTime` sehingga selisihnya sah dihitung. Metrik
waktu untuk kegiatan lain menunggu kolom jamnya dibuat terstruktur.

---

## 6. Susunan Halaman

```
Performa Operasional
├── Toolbar filter (periode, regu, shift, ekspor)            ← tetap
├── 4 kartu KPI                                              ← tetap, Total Tonase dikoreksi (tanpa Teus)
├── ★ Strip "Performa per Kegiatan" — 5 kartu ringkas         ← BARU (1 pass query)
│      grid 3 kolom (desktop) / 2 (tablet) / 1 (ponsel)
├── ★ Panel detail kegiatan — tab horizontal 5 pilihan        ← BARU (dimuat terpisah)
│      panel pertama diambil setelah halaman siap; empat sisanya saat diklik
├── Tren Tonase + Komposisi Kegiatan                          ← tetap (4 kegiatan Ton saja)
├── Tonase per Shift + Rasio Kerusakan                        ← tetap
├── Perbandingan Regu + Beban Kerja                           ← tetap
├── Peringkat Lembur                                          ← tetap
└── Kapal Dilayani                                            ← tetap
```

**Kenapa tab dan bukan lima kartu besar yang langsung tampil?**

| Pendekatan | Query saat halaman dibuka | Tinggi halaman | Catatan |
|---|---|---|---|
| Lima panel dirender sekaligus | ± 5× lipat | > 7.000 px | Terberat, dan 4 dari 5 panel biasanya tidak dibaca |
| **Tab + lazy load (dipakai)** | 0 tambahan (hanya strip 5 kartu) | Tetap wajar | Detail diambil sesuai kebutuhan, tiap tab punya cache sendiri |
| Halaman terpisah per kegiatan | 0 tambahan | Ringan | Konteks hilang, filter harus dibawa-bawa, 5 menu baru |

Strip 5 kartu ringkas tetap dirender langsung karena angkanya berasal dari
kumpulan query agregat yang memang sudah dihitung untuk KPI — jadi tidak menambah
beban berarti.

---

## 7. Rancangan Teknis

### 7.1 Katalog kegiatan menggantikan `tonnageSources()`

```php
// app/Services/OperationalPerformanceService.php
private function activityCatalog(): array
{
    return [
        'muat_kantong' => [
            'label'  => 'Pemuatan Pupuk Kantong',
            'unit'   => 'Ton',
            'countsToTonnage' => true,          // ikut Total Tonase
            'from'   => 'loading_activities',
            'column' => 'loading_activities.qty_loading_current',
            'joins'  => [],
            'reportKey' => 'loading_activities.daily_report_id',
        ],
        // muat_curah, bongkar_bahan_baku → sama polanya, unit Ton
        'container' => [
            'label'  => 'Bongkar/Muat Container',
            'unit'   => 'Teus',
            'countsToTonnage' => false,         // ← satuan beda, tidak dijumlahkan
            'from'   => 'container_items',
            'column' => 'container_items.qty_current',
            'joins'  => [['container_activities', 'container_items.container_activity_id', 'container_activities.id']],
            'reportKey' => 'container_activities.daily_report_id',
        ],
        'trucking_turba' => [ /* turba_deliveries.qty_current, Ton, countsToTonnage true */ ],
    ];
}
```

Katalog ini jadi satu-satunya sumber kebenaran untuk service, view, dan ekspor —
menambah kegiatan baru nanti cukup menambah satu entri. Penjumlahan Total Tonase
menyaring lewat `countsToTonnage`, jadi kekeliruan satuan tidak bisa terulang
diam-diam.

### 7.2 Ganti loop per regu/shift dengan satu query beragregasi

Inti perbaikan performanya. Alih-alih memanggil `summaryFor()` sekali per regu dan
sekali per shift, satu query per sumber sudah cukup:

```php
$rows = $this->sourceQuery($source, $scope)
    ->selectRaw($periodFlag.' as periode')          // CASE: 'ini' / 'lalu'
    ->selectRaw('daily_reports.group_name as regu')
    ->selectRaw('daily_reports.shift as shift')
    ->selectRaw('COALESCE(SUM('.$source['column'].'), 0) as total')
    ->selectRaw('COUNT(DISTINCT daily_reports.id) as laporan')
    ->groupBy('periode', 'daily_reports.group_name', 'daily_reports.shift')
    ->get();
```

Hasilnya diagregasi ulang di PHP (jumlah barisnya kecil: *periode × regu × shift*).
Satu query ini sekaligus mengisi: total periode ini, total periode pembanding,
rincian per regu, rincian per shift, dan rincian per kegiatan.

### 7.3 Anggaran query sesudah perubahan

| Bagian | Query |
|---|---|
| 5 kegiatan × 1 query agregat (periode × regu × shift) | 5 |
| 5 kegiatan × 1 query tren bulanan (bulan × shift) | 5 |
| Jumlah laporan (periode × regu × shift) | 1 |
| Kunjungan kapal (2 sumber) | 2 |
| Kerusakan muat kantong | 1 |
| Beban kerja + lembur | 5 |
| Daftar kapal | 2 |
| Opsi filter | 2 |
| **Total halaman utama** | **± 23** |
| Tiap panel detail dibuka (AJAX, cache sendiri) | + 3–5 |

Perbandingan: **158 → ± 23** untuk halaman utama, meski isinya bertambah 5 kartu
kegiatan. Jumlah query juga berhenti tumbuh saat regu atau shift bertambah.

Hasil terukur setelah dikerjakan: **22 query** untuk halaman utama dan **4 query**
per panel — lihat [Bagian 12](#12-hasil-eksekusi).

### 7.4 Berkas yang disentuh

| Berkas | Perubahan |
|---|---|
| `routes/web.php` | + `GET /manajer/performa/kegiatan/{key}` (nama: `manajer.performa.kegiatan`) |
| `app/Http/Controllers/ManajerController.php` | + method `performaKegiatan()`; kunci cache per seksi; validasi `{key}` terhadap katalog |
| `app/Services/OperationalPerformanceService.php` | `activityCatalog()`, `activitySummaries()`, `activityDetail(string $key)`, penulisan ulang `summaryFor` menjadi agregat, penyaringan `countsToTonnage` |
| `resources/views/manajer/performa.blade.php` | + strip 5 kartu, + wadah tab detail |
| `resources/views/manajer/partials/activity-strip.blade.php` | **baru** |
| `resources/views/manajer/partials/activity-detail.blade.php` | **baru** (satu template dipakai 5 kegiatan) |
| `resources/views/manajer/charts/bar-simple.blade.php` | **baru**, untuk peringkat jenis bahan baku & tujuan trucking |
| `resources/views/manajer/charts/donut-activity.blade.php` | Hanya 4 kegiatan Ton + catatan kaki container |
| `app/Services/PerformanceExportService.php` | Sheet komposisi memuat satuan per kegiatan + 1 sheet baru "Rincian Kegiatan" |
| `public/js/components/charts.js` | + pemuat tab (fetch → sisipkan HTML → tandai sudah dimuat) |

**Tidak disentuh:** migrasi/database, form Laporan Operasi, `report-paper.blade.php`,
`pdf.blade.php`, dan ekspor Excel laporan harian.

### 7.5 Pagar performa yang wajib dipasang

1. **Tabel detail dibatasi 50 baris**, sisanya diarahkan ke tombol Ekspor.
2. **Panel detail tidak pernah dihitung saat halaman utama dirender** — hanya lewat
   endpoint AJAX-nya sendiri.
3. **Cache dipisah per seksi**, bukan satu payload gemuk — penting karena driver
   cache-nya `database`:
   - `manajer.performa.v2.ringkasan.{stamp}.{start}.{end}.{regu}.{shift}` (10 menit)
   - `manajer.performa.v2.kegiatan.{key}.{stamp}.{start}.{end}.{regu}.{shift}` (10 menit)
4. **`{stamp}` diambil dari `max(updated_at)` laporan yang dihitung** (di-cache 60
   detik), sehingga laporan baru langsung tercermin tanpa perlu menghapus banyak
   kunci cache satu per satu.
5. **Hasil panel disimpan sebagai array siap tampil** (tanggal sudah jadi teks),
   mengikuti pola `shipRow()` yang sudah ada — objek `Carbon` tidak aman
   diserialisasi ke cache.
6. **Tidak ada index baru.** Index `daily_reports(status, report_date)` yang sudah
   ada tetap melayani semua filter.
7. **Tab yang sudah dimuat tidak diambil ulang** selama filter tidak berubah.

---

## 8. Dampak ke Bagian Sistem Lain

| Bagian | Terpengaruh? | Penjelasan |
|---|---|---|
| Dashboard manajer (4 kartu KPI) | **Ya, angkanya berubah** | Total Tonase tidak lagi menjumlahkan Teus container. Angka historis ikut terkoreksi — perlu diberitahukan lebih dulu |
| Donut Komposisi Kegiatan | Ya | Isinya jadi 4 kegiatan Ton + catatan kaki container |
| Ekspor Excel performa | Ya | Payload bertambah; kolom satuan per kegiatan + sheet rincian baru |
| Form input Laporan Operasi | **Tidak** | Tidak ada perubahan sama sekali |
| PDF & Excel laporan harian | **Tidak** | Tidak ada perubahan sama sekali |
| Database & data lama | **Tidak** | Tanpa migrasi, tanpa kolom baru, tanpa backfill |
| Halaman Arsip / statistik laporan | Tidak | `ArchiveMetricsService` tidak disentuh |
| Peran selain manajer | Tidak | Halaman performa tetap di balik `authorizeManagementAccess()` |
| Waktu muat halaman | **Membaik** | ± 23 query vs 158 sekarang, walau isinya bertambah |
| Ukuran halaman (HTML) | Naik tipis | Strip 5 kartu ≈ +12 KB; panel detail tidak ikut kecuali dibuka |
| Pengujian | Ya | Tes yang mengunci Total Tonase gabungan perlu disesuaikan (Teus keluar) |

---

## 9. Rencana Eksekusi Bertahap

Perbaikan performa ditaruh **sebelum** penambahan fitur, supaya tidak menumpuk
beban di atas pola query yang sudah berat.

**Tahap 0 — Jaring pengaman**
- [x] Rekaman hasil `performanceReport()` sebelum perubahan, dipakai sebagai pembanding
- [x] Catat baseline: 158 query, 78 ms

**Tahap 1 — Perbaikan pola query, tanpa perubahan tampilan**
- [x] `summaryFor()` per regu/shift diganti satu query beragregasi
- [x] Verifikasi angka tidak berubah selain tonase (Teus keluar)
- [x] Ukur ulang

**Tahap 2 — Katalog kegiatan & pemisahan satuan**
- [x] `tonnageSources()` → `activityCatalog()` dengan `unit` dan `countsToTonnage`
- [x] Total Tonase, donut, dan tren disaring hanya kegiatan Ton
- [x] Container tampil sebagai angka Teus tersendiri

**Tahap 3 — Strip 5 kartu ringkas**
- [x] `activityCards()` di service
- [x] Partial strip + gaya kartunya
- [x] Label satuan eksplisit di tiap kartu

**Tahap 4 — Panel detail**
- [x] Route + `performaKegiatan()` + validasi kunci kegiatan
- [x] `activityDetail()` untuk 5 kegiatan
- [x] Partial detail + pemuat tab di `charts.js`
- [x] Skeleton saat panel dimuat

**Tahap 5 — Ekspor & penutup**
- [x] Sheet Excel "Per Kegiatan" + penegasan satuan di komposisi
- [x] Cap waktu laporan pada kunci cache (7.5 poin 4)
- [x] Perbarui [docs/tampilan/manajer.md](docs/tampilan/manajer.md)
- [x] Ukur ulang dan bandingkan dengan baseline

---

## 10. Risiko & Penanganannya

| Risiko | Dampak | Penanganan |
|---|---|---|
| Angka Total Tonase berubah karena Teus dikeluarkan | Manajer mengira data hilang | Sampaikan lebih dulu; beri catatan kaki di kartu KPI dan di donut |
| Grafik container tercampur sumbu tonase | Salah baca satuan | Grafik container dipisah, label "Teus" eksplisit di sumbu dan tooltip |
| Panel detail terasa lambat saat pertama diklik | Terasa macet | Skeleton saat memuat; hasilnya di-cache 10 menit |
| Cache membengkak karena kunci per kegiatan | Tabel cache membesar | TTL 10 menit + kombinasi filter terbatas (3 preset × regu × shift) |
| Perubahan `summaryFor()` menggeser angka tanpa disadari | Kepercayaan data turun | Tahap 0 mengunci angka lewat tes sebelum kode diubah |
| Tabel rincian panjang pada periode 3 bulan | Halaman berat | Batas 50 baris + arahkan ke Ekspor |

---


## 11. Catatan untuk Nanti (di luar lingkup ini)

1. Kolom `turba_deliveries.truck_name` sebenarnya berisi tujuan pengiriman —
   penamaannya menyesatkan, tapi tidak diganti sekarang.
2. Jam kerja (`working_hours`, `time_text`) masih teks bebas. Kalau nanti dibuat
   terstruktur, metrik produktivitas ton/jam bisa dibuka untuk semua kegiatan.
3. Kalau suatu saat bongkar dan muat container perlu dipisah, jalurnya sudah
   dipetakan: tambah kolom `activity_type` di `container_activities` + pilihan di
   form, data lama di-set `bongkar`. Pemisahan hanya berlaku untuk laporan baru.

---

## 12. Hasil Eksekusi

Diukur pada data yang sama dengan baseline (17 laporan, 4 regu, 3 shift):

| Bagian | Sebelum | Sesudah |
|---|---|---|
| Halaman Performa (perhitungan + opsi filter) | 158 query · 78,1 ms | **22 query · 17,1 ms** |
| Satu panel kegiatan dibuka | — | 4 query · 2,6 ms |
| Kartu KPI dashboard manajer | 27 query | 17 query |

Angka yang dibandingkan sebelum/sesudah cocok seluruhnya — kapal dilayani,
jumlah laporan, rincian regu, sebaran shift, tren bulanan, beban kerja, dan
ketepatan lapor tidak bergeser sedikit pun. Satu-satunya perubahan adalah Total
Tonase: **9.063 → 8.928**, tepat sebesar 135 Teus container yang dikeluarkan
sesuai keputusan di Bagian 1.

Seluruh 185 tes yang ada lulus, termasuk tes ekspor Excel yang disesuaikan ke
enam sheet.

### Penyimpangan dari rancangan

**Panel pertama dimuat segera setelah halaman selesai digambar, bukan menunggu
tabnya masuk layar.** Rancangan semula memakai `IntersectionObserver`. Saat
diuji di peramban, observer itu tidak pernah memicu — begitu pula peristiwa
gulir, karena isi halaman berada di wadah bergulir sendiri (`.page-content`,
`body` ber-`overflow: hidden`) dan sebagian peramban tidak meneruskan
peristiwanya ke jendela. Panel yang diam-diam tidak pernah termuat jauh lebih
buruk daripada satu permintaan kecil, jadi pemuatannya dipindah ke
`requestIdleCallback` setelah halaman siap.

Dampaknya: satu permintaan tambahan (4 query, di-cache 10 menit) per kunjungan
halaman. Empat panel sisanya tetap menunggu tabnya diklik, dan hasil yang sudah
diambil disimpan di memori sehingga berpindah-pindah tab tidak memanggil server
lagi.

**Metrik "realisasi rata-rata terhadap kapasitas" pada panel muat kantong
diganti "rata-rata TKBM per kegiatan".** Rata-rata realisasi hanya bisa dihitung
tepat dengan menelusuri seluruh kunjungan, sedangkan tabelnya dibatasi 50 baris —
angkanya akan menyesatkan begitu ada lebih dari 50 kunjungan. Realisasi per kapal
tetap ada di setiap baris tabel.

---

## 13. Perubahan Lanjutan (28 Juli 2026)

Rancangan di atas tetap berlaku sebagai catatan pengerjaan, tetapi susunan
halamannya sudah bergeser mengikuti arahan berikutnya dari Pak Mustari —
lihat [PERBAIKAN_MENU_KINERJA_DAN_RINCIAN_KEGIATAN.md](PERBAIKAN_MENU_KINERJA_DAN_RINCIAN_KEGIATAN.md):

- Periode bawaan Kinerja Operasi menjadi **1 Januari sampai hari ini**;
  Rincian Kegiatan tetap bulan berjalan.
- Blok **Kapal Dilayani** dihapus dari Kinerja Operasi (kartu, tabel, dan sheet
  ekspornya). Kartunya masih ada di dashboard manajer, dan data kapal pada
  laporan harian tidak disentuh.
- Kinerja Operasi mendapat **analisis terpisah per jenis kegiatan** (lima panel
  bertab yang dirender bersama halaman, tanpa query tambahan).
- Panel **Pemuatan Pupuk Kantong** dihapus dari Rincian Kegiatan lewat penanda
  katalog `showOnActivityDetail`; kegiatannya tetap dihitung penuh pada Kinerja
  Operasi.
- Bagian 6 (susunan halaman) dan Bagian 7.4 (berkas yang disentuh) pada dokumen
  ini sudah tidak menggambarkan keadaan terkini.
