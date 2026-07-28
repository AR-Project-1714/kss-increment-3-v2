# Kinerja Operasi & Rincian Kegiatan

Penjelasan isi kedua menu manajer, dari mana setiap angkanya berasal, dan bagaimana angka itu diambil. Ditujukan untuk dua pembaca sekaligus: pengembang yang mengubah perhitungannya, dan manajer yang ingin tahu kenapa sebuah angka berbunyi begitu.

| | Kinerja Operasi | Rincian Kegiatan |
|---|---|---|
| Route | `manajer.performa` → `/manajer/performa` | `manajer.kegiatan` → `/manajer/kegiatan` |
| View | `resources/views/manajer/performa.blade.php` | `resources/views/manajer/kegiatan.blade.php` |
| Menjawab | Seberapa produktif divisi operasi periode ini, dibanding periode sebelumnya, dan regu mana yang unggul | Apa saja yang dikerjakan, dipecah menurut lima jenis kegiatan, sampai ke tiap kapal dan tiap rit |
| Satuan cerita | Divisi, regu, shift, personil | Jenis kegiatan, kapal, muatan, tujuan |

Keduanya memakai **toolbar filter yang sama** (`manajer.partials.performance-toolbar`) dan **laporan agregat yang sama**, jadi angka yang sama tidak akan pernah berbeda antar menu.

---

## 1. Aturan dasar yang berlaku di kedua menu

Enam hal ini menjelaskan sebagian besar pertanyaan "kenapa angkanya begitu".

**Tidak ada tabel statistik.** Seluruh angka dihitung ulang dari laporan harian operasi yang sudah masuk — `daily_reports` beserta tabel anaknya. Tidak ada tabel rekap yang perlu di-*generate* atau bisa basi.

**Hanya tiga status yang dihitung.** `submitted`, `acknowledged`, `approved` (`OperationalPerformanceService::COUNTED_STATUSES`). **Draft tidak pernah ikut** karena isinya masih bisa berubah. Ini penyebab paling umum angka aplikasi berbeda dengan hitungan manual di lapangan.

**Tiga filter.** Periode (preset Bulan Ini / Bulan Lalu / 3 Bulan, atau rentang tanggal bebas), Regu (`daily_reports.group_name`), dan Shift (`daily_reports.shift`). Filter menempel pada tanggal laporan (`report_date`), bukan tanggal input.

**Periode pembanding dipilih agar adil** (`equivalentPreviousPeriod()`). Kalau periode dimulai tanggal 1, pembandingnya rentang yang sama di bulan sebelumnya — 1–25 Juli dibanding 1–25 Juni, bukan Juli penuh dibanding Juni penuh, supaya bulan berjalan tidak terlihat anjlok. Untuk rentang bebas, pembandingnya rentang sepanjang durasi yang sama tepat sebelumnya.

**Ton dan Teus tidak dijumlahkan.** Container dicatat dalam Teus (jumlah box), empat kegiatan lain dalam Ton. Penandanya `countsToTonnage` di `activityCatalog()` — satu-satunya tempat, supaya keliru satuan tidak bisa terulang saat kegiatan baru ditambahkan. Akibatnya container tidak masuk Total Tonase maupun donat Komposisi Kegiatan.

**Kolom `qty_*_current` vs `qty_*_prev`.** Kolom `current` berisi tonase shift itu saja, `prev` adalah akumulasi shift sebelumnya. Semua penjumlahan periode memakai `current`, jadi satu kapal yang muncul di banyak laporan tidak terhitung ganda. Kolom `prev` hanya dipakai saat menampilkan "termuat sampai sekarang" pada tabel kapal.

---

## 2. Menu Kinerja Operasi

Ringkasan divisi. Semua blok di bawah ini berasal dari satu pemanggilan `performanceReport($filters)`.

### Empat kartu KPI

| Kartu | Rumus | Sumber |
|---|---|---|
| Tonase Ditangani | Jumlah tonase empat kegiatan bersatuan Ton | `loading_activities.qty_loading_current` + `bulk_loading_logs.cob` + `material_items.qty_current` + `turba_deliveries.qty_current` |
| Kapal Dilayani | Jumlah kunjungan unik | Pasangan **nama kapal + waktu tiba** (`loading_activities.arrival_time`) dan **nama kapal + waktu sandar** (`bulk_loading_activities.berthing_time`), dihitung `DISTINCT` |
| Tonase per Shift | Total tonase ÷ jumlah laporan | `daily_reports` — satu laporan mewakili satu shift kerja |
| Rasio Kerusakan | Kerusakan ÷ tonase muat kantong × 100 | `loading_activities.qty_damage_current` dibagi `qty_loading_current` |

Tiap kartu punya badge perubahan terhadap periode pembanding dan sparkline enam bulan. Dua perilaku yang disengaja:

- **Rasio kerusakan tanpa dasar hitung tampil sebagai strip (–), bukan 0%.** Periode tanpa muatan kantong menghasilkan 0% yang bukan capaian (`hasDamageBase`).
- **Baseline nol tidak pernah jadi "+100%".** Kalau periode pembanding nol, badge-nya berbunyi "Baru pada periode ini" atau "Belum ada data" (`delta()`).

### Grafik dan tabel

| Blok | Isi | Catatan sumber |
|---|---|---|
| Tren Tonase | Tonase bulanan + garis rata-rata | **Selalu 6 bulan terakhir dari bulan berjalan** — tidak mengikuti periode yang dipilih. Filter regu/shift tetap berlaku. |
| Komposisi Kegiatan | Donat porsi tonase antar kegiatan | Hanya kegiatan bersatuan Ton; container tidak muncul di sini |
| Tonase per Shift | Area bertumpuk Pagi/Sore/Malam, 6 bulan | Penulisan shift di lapangan tidak seragam ("1", "Pagi", "Shift 1") — dirapikan ke tiga kelompok; nilai tak dikenal masuk **Pagi** agar tonasenya tidak hilang |
| Rasio Kerusakan | Gauge periode terpilih | Sama dengan kartu KPI, ditampilkan sebagai busur |
| Perbandingan Regu | Peringkat regu menurut tonase | **Mengabaikan filter regu** — memang gunanya membandingkan antar regu |
| Beban Kerja | Personil/shift, jam lembur, entri lembur, relief, ketepatan lapor, sebaran shift | `employee_logs`; bagian sebaran shift **mengabaikan filter shift** |
| Peringkat Lembur | Lima personil terbanyak menurut jam dan menurut frekuensi | `employee_logs` kategori `operasi` deskripsi `Lembur` |
| Kapal Dilayani | Satu baris per kunjungan kapal | `loading_activities` + `bulk_loading_activities` |

Rincian sumber Beban Kerja — semuanya dari `employee_logs` yang tersambung ke laporan:

| Angka | Cara ambil |
|---|---|
| Personil rata-rata per shift | Baris berkategori `shift` ÷ jumlah laporan |
| Jam lembur | Selisih `time_out − time_in` pada entri `operasi`/`Lembur`; jam pulang lebih kecil berarti lewat tengah malam, ditambah 24 jam |
| Entri lembur | Jumlah entri `operasi`/`Lembur` — termasuk yang jamnya tidak diisi, karena itu angkanya bisa ada meski jam lembur nol |
| Relief & pengganti | Entri `operasi`/`Relief` atau kategori `replacement` |
| Ketepatan waktu lapor | Laporan yang `DATE(created_at) = report_date` ÷ jumlah laporan |

Peringkat Lembur memakai dua daftar karena keduanya menjawab hal berbeda: jam menunjukkan beban waktu, frekuensi menunjukkan seberapa sering seseorang diminta. Nama dikelompokkan tanpa memandang besar-kecil huruf, jadi "Zein" dan "zein" tetap satu orang.

---

## 3. Menu Rincian Kegiatan

Lima kartu kegiatan + satu panel rincian yang berganti isi mengikuti tab.

### Katalog lima kegiatan

Sumbernya tunggal — `OperationalPerformanceService::activityCatalog()`:

| Kegiatan | Tabel nilai | Kolom yang dijumlahkan | Satuan | Ikut Total Tonase |
|---|---|---|---|---|
| Pemuatan Pupuk Kantong | `loading_activities` | `qty_loading_current` | Ton | ya |
| Pemuatan Urea Curah | `bulk_loading_logs` (via `bulk_loading_activities`) | `cob` | Ton | ya |
| Bongkar Bahan Baku | `material_items` (via `material_activities`) | `qty_current` | Ton | ya |
| Bongkar/Muat Container | `container_items` (via `container_activities`) | `qty_current` | **Teus** | **tidak** |
| Trucking Pengiriman Pupuk Kantong | `turba_deliveries` (via `turba_activities`) | `qty_current` | Ton | ya |

Menambah kegiatan baru cukup di katalog ini: kartu, tab, tren, dan peringkat regu ikut mengikuti tanpa perubahan lain.

### Lima kartu

Angka utama = tonase kegiatan itu pada periode terpilih; badge = perubahan terhadap periode pembanding; sparkline = enam bulan terakhir. **Tidak ada query tambahan untuk kartu ini** — seluruhnya diambil dari matriks yang sudah dihitung untuk halaman.

### Isi satu panel

| Bagian | Isi | Sumber |
|---|---|---|
| Empat metrik | Khas per kegiatan, lihat tabel berikut | Query agregat pada tabel kegiatan itu |
| Tren 6 Bulan | Batang bulanan kegiatan itu saja | Sama seperti tren halaman kinerja, tapi satu kegiatan |
| Peringkat Regu | Kontribusi tiap regu untuk kegiatan itu | **Mengabaikan filter regu** |
| Komposisi tambahan | Jenis bahan baku / tujuan trucking | Hanya pada dua kegiatan itu |
| Tabel rincian | Satu baris per kapal atau per rit | Tabel kegiatan + tabel induknya |

Metrik khas tiap kegiatan:

- **Muat kantong** — kapal dilayani, tonase delivery gudang→kapal, rasio kerusakan, rata-rata TKBM.
- **Muat curah** — kapal dilayani, entri log jam, rata-rata COB per entri, rata-rata jeda sandar→mulai muat (satu-satunya kegiatan yang menyimpan kedua waktu itu).
- **Bongkar bahan baku** — kapal dilayani, jenis bahan baku, kegiatan tercatat, rata-rata per kapal.
- **Container** — kapal dilayani, kapasitas empty, kapasitas full, baris kegiatan tercatat.
- **Trucking** — rit/DO tercatat, jumlah tujuan, rata-rata muatan per rit, tujuan terbesar.

### Tiga aturan tampilan panel

Panel sengaja menyembunyikan yang kosong supaya periode sepi tidak menghasilkan halaman panjang berisi tanda hubung:

1. **Kolom yang tidak pernah terisi dibuang.** Kolom yang seluruh barisnya null, kosong, atau nol tidak ikut dicetak — mis. No DO/SO dan Kapasitas pada trucking. Kolom penanda baris (Tujuan, Tanggal) tidak pernah dibuang.
2. **Blok tanpa data tidak dirender.** Metrik tanpa nilai dilewati; tren dan peringkat regu yang nol hilang seluruhnya.
3. **Batas baris.** Trucking 10 rit terbaru (satu baris = satu rit, jadi paling cepat memanjang), kegiatan lain 50 baris. Kalau seluruh angka satu tabel kosong, tabelnya diganti satu kalimat yang menyebut berapa baris tercatat — bukan daftar nama tanpa nilai.

---

## 4. Bagaimana datanya diambil

### Alur satu kali buka halaman

```
Permintaan  →  ManajerController::performa() / kegiatan()
            →  performanceFiltersFromRequest()   (periode, regu, shift dari query string)
            →  Cache::remember(kunci, 10 menit)
            →  OperationalPerformanceService::performanceReport($filters)
            →  view
```

Panel kegiatan tidak ikut di jalur itu. Panel diambil sendiri lewat `manajer.kegiatan.panel` (`/manajer/kegiatan/panel/{key}`) yang mengembalikan potongan HTML, dipanggil `public/js/components/charts.js` memakai `fetch`. Panel pertama dimuat setelah halaman selesai digambar; empat sisanya menunggu tabnya diklik, lalu hasilnya disimpan di memori sehingga berpindah-pindah tab tidak memanggil server lagi.

Alasannya sederhana: merender kelima panel bersama halaman berarti lima kali beban query untuk sesuatu yang biasanya hanya dibaca satu.

### Kenapa query-nya sedikit

Inti perhitungannya adalah **matriks agregat**: satu query per sumber kegiatan, dikelompokkan menurut `periode × regu × shift` sekaligus. Periode berjalan dan periode pembanding ditarik dalam **satu** query lewat ekspresi `CASE WHEN report_date >= ? THEN 'ini' ELSE 'lalu' END`.

Filter regu dan shift **sengaja tidak diterapkan di SQL**. Hasil query adalah superset yang dipotong di PHP, sehingga blok yang memang harus mengabaikan filter (Perbandingan Regu, sebaran shift, peringkat regu per kegiatan) bisa dilayani dari matriks yang sama tanpa query tambahan. Konsekuensinya jumlah query tidak ikut bertambah saat regu atau shift bertambah.

Hasil ukur di data pengembangan:

| | Query | Waktu |
|---|---|---|
| `performanceReport()` | 20 | ~16 ms |
| Halaman (cache kosong) | 27 | ~34 ms |
| Halaman (cache hangat) | 4 | ~8 ms |
| Satu panel kegiatan | 7–8 | 9–12 ms |

### Cache

Kunci: `manajer.performa.v2.{bagian}.{stempel}.{mulai}.{akhir}.{regu}.{shift}`, TTL 10 menit, penyimpanan di tabel `cache`.

- `{bagian}` bernilai `ringkasan` untuk laporan halaman, atau `kegiatan.{key}` untuk satu panel.
- `{stempel}` adalah `MAX(updated_at)` laporan yang ikut dihitung, dicari maksimal sekali per menit. **Ini yang membuat cache tidak pernah basi:** begitu ada laporan baru atau laporan yang diedit, stempelnya berubah, kuncinya berubah, dan angkanya dihitung ulang — tanpa perlu menghapus cache secara manual. Jeda terburuknya satu menit.
- Kunci `ringkasan` **dipakai bersama** oleh Kinerja Operasi dan Rincian Kegiatan. Membuka menu kedua dalam filter yang sama praktis tidak menambah beban — 4 query, ~8 ms.

### Ekspor

Tombol Ekspor di kedua menu menunjuk ke `manajer.performa.export` dengan membawa filter yang sedang aktif, jadi isi berkas selalu sama dengan yang tampil di layar. Berkasnya enam sheet: Ringkasan, Per Kegiatan, Tren Bulanan, Regu & Kegiatan, Peringkat Lembur, Kapal Dilayani. **Berkas ini belum memuat tabel rincian per rit atau per baris container.**

---

## 5. Pertanyaan yang sering muncul

**"Angka tonase di aplikasi lebih kecil dari catatan saya."** Kemungkinan besar ada laporan yang masih berstatus draft. Draft tidak pernah dihitung.

**"Container 50 Teus kok tidak menambah Total Tonase?"** Memang tidak boleh — Teus adalah jumlah box, bukan berat. Angkanya berdiri sendiri di kartu container dan panelnya.

**"Grafik tren tidak berubah waktu saya ganti periode."** Betul. Tren Tonase dan Tonase per Shift selalu menampilkan enam bulan terakhir supaya bentuk kurvanya bisa dibandingkan antar periode. Yang mengikuti periode adalah kartu KPI, komposisi, perbandingan regu, beban kerja, dan seluruh isi Rincian Kegiatan.

**"Filter regu saya nyalakan, tapi tabel Perbandingan Regu tetap menampilkan semua regu."** Disengaja — tabel itu memang untuk membandingkan. Hal yang sama berlaku pada sebaran shift dan peringkat regu di dalam panel kegiatan.

**"Kolom Kapasitas hilang dari tabel rincian."** Kolom yang tidak terisi sama sekali pada periode itu memang tidak dicetak. Begitu ada satu baris saja yang terisi, kolomnya muncul lagi.

**"Rasio kerusakan menunjukkan strip, bukan 0%."** Berarti belum ada muatan pupuk kantong pada periode itu, sehingga rasionya tidak punya pembagi.

**"Perubahan periode berbunyi 'Baru pada periode ini'."** Periode pembandingnya nol. Persentase perubahan terhadap nol akan menyesatkan, jadi tidak ditampilkan.

---

## Berkas terkait

| Berkas | Peran |
|---|---|
| `app/Services/OperationalPerformanceService.php` | Seluruh perhitungan: katalog kegiatan, matriks agregat, ringkasan, panel |
| `app/Http/Controllers/ManajerController.php` | Filter dari query string, kunci cache, kedua halaman + endpoint panel |
| `app/Services/PerformanceExportService.php` | Ekspor Excel enam sheet |
| `resources/views/manajer/performa.blade.php` | Halaman Kinerja Operasi |
| `resources/views/manajer/kegiatan.blade.php` | Halaman Rincian Kegiatan |
| `resources/views/manajer/partials/performance-toolbar.blade.php` | Toolbar filter bersama |
| `resources/views/manajer/partials/activity-strip.blade.php` | Lima kartu kegiatan |
| `resources/views/manajer/partials/activity-detail.blade.php` | Isi satu panel kegiatan |
| `public/js/components/charts.js` | Pemuatan panel, tooltip grafik |
| `tests/Feature/BlackBox/ManagerDashboardTest.php` | TC-MGR-08 s.d. TC-MGR-13 mengunci perilaku kedua menu |
