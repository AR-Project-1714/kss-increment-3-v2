# Kinerja Operasi & Rincian Kegiatan

Penjelasan isi kedua menu manajer, dari mana setiap angkanya berasal, dan bagaimana angka itu diambil. Ditujukan untuk dua pembaca sekaligus: pengembang yang mengubah perhitungannya, dan manajer yang ingin tahu kenapa sebuah angka berbunyi begitu.

| | Kinerja Operasi | Rincian Kegiatan |
|---|---|---|
| Route | `manajer.performa` → `/manajer/performa` | `manajer.kegiatan` → `/manajer/kegiatan` |
| View | `resources/views/manajer/performa.blade.php` | `resources/views/manajer/kegiatan.blade.php` |
| Periode bawaan | **1 Januari sampai hari ini** | **Tanggal 1 bulan berjalan sampai hari ini** |
| Kegiatan yang tampil | Enam kegiatan (rekap) | Enam kegiatan (panel bertab) |
| Menjawab | Seberapa produktif divisi operasi tahun berjalan, dibanding periode sebelumnya, regu mana yang unggul, dan bagaimana bacaannya per jenis kegiatan | Apa saja yang dikerjakan bulan ini, dipecah menurut jenis kegiatan, sampai ke tiap kapal dan tiap rit |
| Satuan cerita | Divisi, regu, shift, personil, jenis kegiatan | Jenis kegiatan, kapal, muatan, tujuan |

Keduanya memakai **toolbar filter yang sama** (`manajer.partials.performance-toolbar`) dan **laporan agregat yang sama**, jadi angka yang sama tidak akan pernah berbeda antar menu.

---

## 1. Aturan dasar yang berlaku di kedua menu

Enam hal ini menjelaskan sebagian besar pertanyaan "kenapa angkanya begitu".

**Tidak ada tabel statistik.** Seluruh angka dihitung ulang dari laporan harian operasi yang sudah masuk — `daily_reports` beserta tabel anaknya. Tidak ada tabel rekap yang perlu di-*generate* atau bisa basi.

**Hanya tiga status yang dihitung.** `submitted`, `acknowledged`, `approved` (`OperationalPerformanceService::COUNTED_STATUSES`). **Draft tidak pernah ikut** karena isinya masih bisa berubah. Ini penyebab paling umum angka aplikasi berbeda dengan hitungan manual di lapangan.

**Tiga filter.** Periode (preset Januari–Sekarang / Tahun Lalu / Bulan Ini / Bulan Lalu / 3 Bulan, atau rentang tanggal bebas), Regu (`daily_reports.group_name`), dan Shift (`daily_reports.shift`). Filter menempel pada tanggal laporan (`report_date`), bukan tanggal input.

**Periode bawaannya berbeda antar menu.** Kinerja Operasi terbuka pada *Januari–Sekarang* (1 Januari tahun berjalan sampai hari ini), Rincian Kegiatan pada *Bulan Ini*. Keduanya tetap memakai toolbar yang sama, dan filter yang sudah dipilih ikut terbawa saat berpindah menu lewat sidebar. Karena bawaannya berbeda, tautan Ekspor selalu menuliskan periodenya secara eksplisit di query string.

**Katalog kegiatan menentukan kegiatan mana tampil di mana.** Penandanya `showOnPerformance` dan `showOnActivityDetail` di `activityCatalog()`; keduanya kini `true` untuk keenam kegiatan. Endpoint panel hanya melayani kunci yang ditandai tampil pada menunya — selain itu 404, jadi menyembunyikan satu kegiatan cukup dengan mengubah satu penanda dan tidak menyisakan pintu belakang.

**Periode pembanding dipilih agar adil** (`equivalentPreviousPeriod()`). Kalau periode dimulai tanggal 1, pembandingnya rentang yang sama di bulan sebelumnya — 1–25 Juli dibanding 1–25 Juni, bukan Juli penuh dibanding Juni penuh, supaya bulan berjalan tidak terlihat anjlok. Untuk rentang bebas, pembandingnya rentang sepanjang durasi yang sama tepat sebelumnya.

**Ton dan Teus tidak dijumlahkan.** Bongkar dan muat container dicatat dalam Teus (jumlah box), empat kegiatan lain dalam Ton. Penandanya `countsToTonnage` di `activityCatalog()` — satu-satunya tempat, supaya keliru satuan tidak bisa terulang saat kegiatan baru ditambahkan. Akibatnya container tidak masuk Total Tonase maupun donat Komposisi Kegiatan.

**Kolom `qty_*_current` vs `qty_*_prev`.** Kolom `current` berisi tonase shift itu saja, `prev` adalah akumulasi shift sebelumnya. Semua penjumlahan periode memakai `current`, jadi satu kapal yang muncul di banyak laporan tidak terhitung ganda. Kolom `prev` hanya dipakai saat menampilkan "termuat sampai sekarang" pada tabel kapal.

---

## 2. Menu Kinerja Operasi

Semua blok di bawah ini berasal dari satu pemanggilan `performanceReport($filters)`.

Halaman dibaca dari atas ke bawah dalam tiga bab, dipisah penanda `.page-section` supaya tidak terbaca sebagai satu tumpukan kartu yang sama beratnya:

1. **Empat kartu KPI** tanpa penanda bab, karena itulah pembuka halaman.
2. **Ringkasan Kegiatan** berisi satu tabel rekap per jenis kegiatan.
3. **Ringkasan Divisi** berisi seluruh kegiatan yang digabung: tren, komposisi, sebaran shift, rasio kerusakan, perbandingan regu, beban kerja, dan peringkat lembur.
4. **Analisis per Jenis Kegiatan** berisi tab kegiatan beserta panelnya.

Aturan tampilan yang berlaku di kedua menu: angka dirata-kanankan dengan lebar digit tetap (`.perf-table__num`), satuan ditulis sebagai keterangan kecil di sebelah angkanya, rentang tanggal memakai tanda hubung biasa, dan blok yang seluruh nilainya nol tidak dirender.

### Empat kartu KPI

| Kartu | Rumus | Sumber |
|---|---|---|
| Tonase Ditangani | Jumlah tonase empat kegiatan bersatuan Ton | `loading_activities.qty_loading_current` + `bulk_loading_logs.cob` + `material_items.qty_current` + `turba_deliveries.qty_current` |
| Laporan Masuk | Jumlah laporan berstatus dihitung | `daily_reports` |
| Tonase per Shift | Total tonase ÷ jumlah laporan | `daily_reports` — satu laporan mewakili satu shift kerja |
| Rasio Kerusakan | Kerusakan ÷ tonase muat kantong × 100 | `loading_activities.qty_damage_current` dibagi `qty_loading_current` |

### Ringkasan Kegiatan

Blok pertama setelah kartu KPI, bentuknya mengikuti rekap yang biasa dipaparkan ke manajemen: satu baris per kegiatan, dibaca dalam tiga kelompok kolom — **Bulan Berjalan**, **Sebelumnya** (bulan-bulan lain di dalam periode), dan **Akumulasi** (jumlah keduanya). Tiap kelompok memuat pencacah dan volumenya.

| Hal | Catatan |
|---|---|
| Pencacah | Kapal untuk kegiatan berbasis kapal, **rit** untuk trucking |
| Kontainer | Dipecah **Bongkar (Empty)** dan **Muat (Full)** memakai `container_items.status`; satuannya tetap Teus |
| Muat kantong | Punya baris pendamping berisi kirim gudang → kapal dan kerusakan |
| Kapal lintas segmen | Kapal yang sandar melewati pergantian bulan terhitung di kedua kelompok, sama seperti cara rekap manual disusun |
| Baris nol | Tidak dicetak |

Seluruhnya berasal dari **satu query gabungan** (`activityRecap()`), dan mengikuti filter periode, regu, serta shift yang sedang aktif.

Kartu **Kapal Dilayani** sudah tidak ada di menu ini — begitu pula tabelnya dan sheet ekspornya. Kartu itu masih dipakai dashboard manajer, dan data kapal pada laporan harian tidak disentuh sama sekali. Karena kunjungan kapal tidak lagi dihitung di jalur ini, empat query-nya ikut hilang.

Tiap kartu punya badge perubahan terhadap periode pembanding dan sparkline enam bulan. Dua perilaku yang disengaja:

- **Rasio kerusakan tanpa dasar hitung tampil sebagai strip (–), bukan 0%.** Periode tanpa muatan kantong menghasilkan 0% yang bukan capaian (`hasDamageBase`).
- **Baseline nol tidak pernah jadi "+100%".** Kalau periode pembanding nol, badge-nya berbunyi "Baru pada periode ini" atau "Belum ada data" (`delta()`).

### Grafik dan tabel

| Blok | Isi | Catatan sumber |
|---|---|---|
| Tren Tonase & Teus | Dua deret per bulan — tonase (Ton) dan container (Teus) — plus garis rata-rata tonase | **Selalu 6 bulan terakhir dari bulan berjalan** — tidak mengikuti periode yang dipilih. Filter regu/shift tetap berlaku. Karena satuannya berbeda, tiap deret punya sumbunya sendiri: Ton di kiri, Teus di kanan. Deret Teus baru muncul bila ada kegiatan container. |
| Komposisi Kegiatan | Donat porsi tonase antar kegiatan | Hanya kegiatan bersatuan Ton; container tidak muncul di sini |
| Tonase per Shift | Area bertumpuk Pagi/Sore/Malam, 6 bulan | Penulisan shift di lapangan tidak seragam ("1", "Pagi", "Shift 1") — dirapikan ke tiga kelompok; nilai tak dikenal masuk **Pagi** agar tonasenya tidak hilang |
| Rasio Kerusakan | Gauge periode terpilih | Sama dengan kartu KPI, ditampilkan sebagai busur |
| Perbandingan Regu | Peringkat regu menurut tonase | **Mengabaikan filter regu** — memang gunanya membandingkan antar regu |
| Beban Kerja | Personil/shift, jam lembur, entri lembur, relief, ketepatan lapor, sebaran shift | `employee_logs`; bagian sebaran shift **mengabaikan filter shift** |
| Peringkat Lembur | Sepuluh personil teratas menurut jam dan menurut frekuensi, dengan tombol pembuka daftar penuh di bawah tiap kolom | `employee_logs` kategori `operasi` deskripsi `Lembur`. Daftar penuh sudah ikut terkirim bersama halaman — tombolnya hanya membuka yang tersembunyi, tidak memanggil server. Panel per kegiatan di Rincian Kegiatan tetap dipangkas lima karena ruangnya sempit |

**Bedah per jenis kegiatan tidak ada di menu ini.** Tab kegiatan beserta panelnya hanya ada di Rincian Kegiatan. Halaman Kinerja Operasi menjawab "bagaimana divisi berjalan", menu itu menjawab "apa isi tiap kegiatan".

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

Dua kartu: **Capaian per Kegiatan** berisi lima kartu ringkas, lalu **Rincian Kegiatan** berisi tab dan panel yang berganti isi mengikuti tab. Keduanya dipisah supaya tab terbaca sebagai kendali panel di bawahnya, bukan bagian dari kartu di atasnya.

Tiap panel dibuka dengan kepala panel (`.act-panel__head`) yang menyebut kegiatan yang sedang dibaca beserta angka utamanya, sehingga isi panel tidak kehilangan konteks setelah tab berpindah dan halaman digulir.

### Katalog kegiatan

Sumbernya tunggal — `OperationalPerformanceService::activityCatalog()`:

| Kegiatan | Tabel nilai | Kolom yang dijumlahkan | Satuan | Ikut Total Tonase | Kinerja Operasi | Rincian Kegiatan |
|---|---|---|---|---|---|---|
| Pemuatan Pupuk Kantong | `loading_activities` | `qty_loading_current` | Ton | ya | ya | ya |
| Pemuatan Urea Curah | `bulk_loading_logs` (via `bulk_loading_activities`) | `cob` | Ton | ya | ya | ya |
| Bongkar Bahan Baku | `material_items` (via `material_activities`) | `qty_current` | Ton | ya | ya | ya |
| Bongkar/Muat Container | `container_items` (via `container_activities`) | `qty_current` | **Teus** | **tidak** | ya | ya |
| Trucking Pengiriman Pupuk Kantong | `turba_deliveries` (via `turba_activities`) | `qty_current` | Ton | ya | ya | ya |

Menambah kegiatan baru cukup di katalog ini: kartu, tab, tren, peringkat regu, panel Kinerja Operasi, dan ekspor ikut mengikuti tanpa perubahan lain.

### Lima kartu

Angka utama = tonase kegiatan itu pada periode terpilih; badge = perubahan terhadap periode pembanding; sparkline = enam bulan terakhir. **Tidak ada query tambahan untuk kartu ini** — seluruhnya diambil dari matriks yang sudah dihitung untuk halaman.

### Isi satu panel

| Bagian | Isi | Sumber |
|---|---|---|
| Kepala panel | Nama kegiatan, angka utama + satuan, dan sparkline area enam bulan tepat di bawah satuannya | Warna sparkline mengikuti perbandingan angka utama dengan periode sepanjang durasi yang sama tepat sebelumnya — hijau naik, merah turun, abu-abu bila belum ada pembanding. Rentang dan besar perubahannya muncul saat sparkline disentuh |
| Empat metrik | Khas per kegiatan, lihat tabel berikut | Query agregat pada tabel kegiatan itu |
| Tren 6 Bulan | Batang bulanan kegiatan itu saja, dengan sumbu Y dan garis bantu di angka bulat | Sama seperti tren halaman kinerja, tapi satu kegiatan. Batas atas sumbu dibulatkan ke kelipatan bulat di atas nilai tertinggi (puncak 1.120 Teus → sumbu 1.500, garis di 500/1.000/1.500), jadi batangnya memang tidak pernah menyentuh tepi atas |
| Peringkat Regu | Kontribusi tiap regu untuk kegiatan itu | **Mengabaikan filter regu** |
| Beban Kerja | Personil/shift, jam & entri lembur, relief, ketepatan lapor | `employee_logs` pada laporan yang memuat kegiatan itu |
| Sebaran per Shift | Jumlah laporan yang memuat kegiatan itu | `daily_reports` pada laporan yang sama |
| Peringkat Lembur | Jam terbanyak & paling sering | `employee_logs` kategori `operasi` deskripsi `Lembur` |
| Komposisi tambahan | Jenis bahan baku / tujuan trucking | Hanya pada dua kegiatan itu |

Tabel rincian baris-per-baris **tidak ditampilkan di panel** atas permintaan pemangku kepentingan — panel ini untuk membaca ringkasan, bukan menelusuri satu per satu. Datanya tetap disusun service dan tetap ikut pada berkas ekspor Excel, jadi rinciannya tidak hilang, hanya pindah tempat.

Metrik khas tiap kegiatan:

- **Muat kantong** — kapal dilayani, tonase delivery gudang→kapal, rasio kerusakan, rata-rata TKBM.
- **Muat curah** — kapal dilayani, entri log jam, rata-rata COB per entri, rata-rata jeda sandar→mulai muat (satu-satunya kegiatan yang menyimpan kedua waktu itu).
- **Bongkar bahan baku** — kapal dilayani, jenis bahan baku, kegiatan tercatat, rata-rata per kapal.
- **Container** — kapal dilayani, kapasitas empty, kapasitas full, baris kegiatan tercatat.
- **Trucking** — rit/DO tercatat, jumlah tujuan, rata-rata muatan per rit, tujuan terbesar.

### Tiga aturan tampilan panel

Panel sengaja menyembunyikan yang kosong supaya periode sepi tidak menghasilkan halaman panjang berisi tanda hubung:

1. **Kolom yang tidak pernah terisi dibuang.** Kolom yang seluruh barisnya null, kosong, atau nol tidak ikut dicetak — mis. No DO/SO dan Kapasitas pada trucking. Kolom penanda baris (Tujuan, Tanggal) tidak pernah dibuang. Berlaku pada ekspor, karena tabelnya sudah tidak dicetak di panel.
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

Panel **Rincian Kegiatan** tidak ikut di jalur itu. Panel diambil sendiri lewat `manajer.kegiatan.panel` (`/manajer/kegiatan/panel/{key}`) yang mengembalikan potongan HTML, dipanggil `public/js/components/charts.js` memakai `fetch`. Panel pertama dimuat setelah halaman selesai digambar; tiga sisanya menunggu tabnya diklik, lalu hasilnya disimpan di memori sehingga berpindah-pindah tab tidak memanggil server lagi. Kunci di luar katalog — atau kegiatan yang ditandai tidak tampil di menu ini — dijawab 404.

Alasannya sederhana: panel itu menjalankan query rinciannya sendiri (tabel kapal/rit), jadi merender semuanya bersama halaman berarti membayar beban untuk sesuatu yang biasanya hanya dibaca satu.

Panel **Kinerja Operasi** justru sebaliknya: seluruh angkanya sudah ada di `performanceReport()`, jadi kelimanya dirender bersama halaman dan berpindah tab tidak memanggil server sama sekali.

### Kenapa query-nya sedikit

Inti perhitungannya adalah **matriks agregat**: satu query per sumber kegiatan, dikelompokkan menurut `periode × regu × shift` sekaligus. Periode berjalan dan periode pembanding ditarik dalam **satu** query lewat ekspresi `CASE WHEN report_date >= ? THEN 'ini' ELSE 'lalu' END`.

Filter regu dan shift **sengaja tidak diterapkan di SQL**. Hasil query adalah superset yang dipotong di PHP, sehingga blok yang memang harus mengabaikan filter (Perbandingan Regu, sebaran shift, peringkat regu per kegiatan) bisa dilayani dari matriks yang sama tanpa query tambahan. Konsekuensinya jumlah query tidak ikut bertambah saat regu atau shift bertambah.

Laporan ditarik satu baris per laporan, bukan sebagai hitungan yang sudah teragregasi — begitu pula beban kerja. Dengan begitu potongan "hanya laporan yang memuat kegiatan X" bisa dilayani dari baris yang sama, dan analisis per kegiatan tidak menambah query sebanyak jumlah kegiatan.

Hasil ukur di data pengembangan (17 laporan, 4 regu, 3 shift):

| | Query | Waktu |
|---|---|---|
| `performanceReport()` | 16 | ~14 ms |
| Halaman (cache kosong) | 19 | ~30 ms |
| Halaman (cache hangat) | 4 | ~8 ms |
| Satu panel Rincian Kegiatan | 7–8 | 4–6 ms |

Turun dari 20 query karena empat query kunjungan kapal dan dua query daftar kapal ikut hilang bersama blok Kapal Dilayani; dua query gabungan ditambahkan sebagai gantinya — kegiatan → laporan, dan rekap kegiatan per segmen bulan.

### Cache

Kunci: `manajer.performa.v2.{bagian}.{stempel}.{mulai}.{akhir}.{regu}.{shift}`, TTL 10 menit, penyimpanan di tabel `cache`.

- `{bagian}` bernilai `ringkasan` untuk laporan halaman, atau `kegiatan.{key}` untuk satu panel.
- `{stempel}` adalah `MAX(updated_at)` laporan yang ikut dihitung, dicari maksimal sekali per menit. **Ini yang membuat cache tidak pernah basi:** begitu ada laporan baru atau laporan yang diedit, stempelnya berubah, kuncinya berubah, dan angkanya dihitung ulang — tanpa perlu menghapus cache secara manual. Jeda terburuknya satu menit.
- Kunci `ringkasan` **dipakai bersama** oleh Kinerja Operasi dan Rincian Kegiatan. Membuka menu kedua dalam filter yang sama praktis tidak menambah beban — 4 query, ~8 ms.

### Ekspor

Kedua menu membawa filter aktif ke ekspornya, tetapi workbook-nya dibedakan sesuai kebutuhan pembaca:

- **Kinerja Operasi** memakai `manajer.performa.export`. Workbook enam sheet ini tetap menjadi laporan ringkas lintas divisi.
- **Rincian Kegiatan** memakai `manajer.kegiatan.export`. Tombol **Ekspor Excel** tampil langsung di kepala halaman dan menghasilkan workbook analisis per kegiatan.

Workbook Kinerja Operasi tetap berisi:

| Sheet | Isi |
|---|---|
| **Kinerja Operasional** | Rekap kegiatan mengikuti format laporan manajemen: satu blok per jenis kegiatan, tiga kelompok kolom (bulan sekarang · sebelumnya · akumulasi). Kegiatan dengan susunan kolom yang sama berbagi satu kepala tabel, seperti bongkar dan muat kontainer |
| Ringkasan | Empat KPI + beban kerja + status rasio kerusakan |
| Per Kegiatan | Nilai tiap kegiatan + tabel "Analisis per Kegiatan" (porsi, sebaran shift, regu teratas, lembur) |
| Tren Bulanan | Enam bulan: tonase, laporan, ton/shift, rasio kerusakan, pecahan shift |
| Regu & Kegiatan | Perbandingan regu + komposisi kegiatan bersatuan Ton |
| Peringkat Lembur | Jam terbanyak & paling sering |

Workbook Rincian Kegiatan berisi:

| Sheet | Isi |
|---|---|
| **Gambaran Besar** | Rekap seluruh kegiatan mengikuti format laporan manajemen: bulan sekarang, sebelumnya, dan akumulasi. Kegiatan tanpa nilai tetap dicetak agar mudah dibedakan dari data yang tidak diekspor |
| **Muat Kantong** s.d. **Trucking Pupuk** | Satu sheet per kegiatan: ringkasan manajerial, indikator khusus, rekap formula-driven, sorotan otomatis, beban kerja, lembur, breakdown bila tersedia, dan tabel rincian |

Setiap sheet kegiatan membawa dua chart native Excel: tren enam bulan dan perbandingan yang paling relevan (komposisi bahan/tujuan, kontribusi regu, atau sebaran shift). Data sumber chart tetap terlihat agar dapat diaudit. Tabel rincian diekspor hingga 5.000 baris per kegiatan; bila melampaui batas, workbook menuliskan jumlah baris yang tidak ditampilkan.

Satuan selalu ditulis eksplisit. Container tetap **Teus** dan tidak pernah dijumlahkan ke total **Ton**. Kegiatan pada gambar contoh yang belum memiliki sumber data di aplikasi—seperti Amoniak serta STV/CD/R-D—tidak diberi angka rekaan.

---

## 5. Pertanyaan yang sering muncul

**"Angka tonase di aplikasi lebih kecil dari catatan saya."** Kemungkinan besar ada laporan yang masih berstatus draft. Draft tidak pernah dihitung.

**"Container 50 Teus kok tidak menambah Total Tonase?"** Memang tidak boleh — Teus adalah jumlah box, bukan berat. Angkanya berdiri sendiri di kartu container, panelnya, dan sebagai deret kedua pada grafik Tren Tonase & Teus.

**"Grafik tren tidak berubah waktu saya ganti periode."** Betul. Tren Tonase & Teus dan Tonase per Shift selalu menampilkan enam bulan terakhir supaya bentuk kurvanya bisa dibandingkan antar periode. Yang mengikuti periode adalah kartu KPI, komposisi, perbandingan regu, beban kerja, dan seluruh isi Rincian Kegiatan.

**"Filter regu saya nyalakan, tapi tabel Perbandingan Regu tetap menampilkan semua regu."** Disengaja — tabel itu memang untuk membandingkan. Hal yang sama berlaku pada sebaran shift halaman dan peringkat regu di dalam panel kegiatan.

**"Kenapa Kinerja Operasi terbuka di rentang Januari, bukan bulan ini?"** Menu itu memang untuk membaca capaian tahun berjalan. Kalau yang dicari kondisi bulan ini, tekan preset **Bulan Ini** — atau buka Rincian Kegiatan yang memang terbuka pada bulan berjalan.

**"Kenapa kontainer dipecah Empty dan Full di Ringkasan Kegiatan, tapi menyatu di kartu dan panel?"** Rekap memakai penanda `Empty`/`Full` yang sudah ada di tiap baris laporan, karena laporan manajemen memang memisahkan keduanya. Kartu dan panel tetap menyatu mengikuti form laporan yang mencatat bongkar dan muat kontainer dalam satu bagian. Jumlah Teus-nya sama.

**"Angka trucking pada rekap 0 Ton padahal ritnya ada."** Kolom rit dan kolom tonase datang dari baris yang sama; rit tercatat tetapi kolom tonasenya belum diisi di lapangan.

**"Blok Kapal Dilayani hilang dari Kinerja Operasi."** Juga sesuai arahan. Data kapal pada laporan harian, PDF, dan ekspor laporan harian tidak disentuh; kartu Kapal Dilayani juga masih ada di dashboard manajer.

**"Kolom Kapasitas hilang dari tabel rincian."** Kolom yang tidak terisi sama sekali pada periode itu memang tidak dicetak. Begitu ada satu baris saja yang terisi, kolomnya muncul lagi.

**"Rasio kerusakan menunjukkan strip, bukan 0%."** Berarti belum ada muatan pupuk kantong pada periode itu, sehingga rasionya tidak punya pembagi.

**"Perubahan periode berbunyi 'Baru pada periode ini'."** Periode pembandingnya nol. Persentase perubahan terhadap nol akan menyesatkan, jadi tidak ditampilkan.

---

## Berkas terkait

| Berkas | Peran |
|---|---|
| `app/Services/OperationalPerformanceService.php` | Seluruh perhitungan: katalog kegiatan, matriks agregat, ringkasan, panel |
| `app/Http/Controllers/ManajerController.php` | Filter dari query string, kunci cache, kedua halaman + endpoint panel |
| `app/Services/PerformanceExportService.php` | Ekspor Excel enam sheet, dipimpin rekap "Kinerja Operasional" |
| `app/Services/ActivityDetailExportService.php` | Workbook Rincian Kegiatan: overview, sheet per kegiatan, sorotan, tabel, dan chart |
| `resources/views/manajer/performa.blade.php` | Halaman Kinerja Operasi |
| `resources/views/manajer/kegiatan.blade.php` | Halaman Rincian Kegiatan |
| `resources/views/manajer/layouts/card-kpi.blade.php` | Empat kartu KPI; susunannya bisa diganti lewat `$cardKeys` |
| `resources/views/manajer/partials/performance-toolbar.blade.php` | Toolbar filter bersama |
| `resources/views/manajer/partials/activity-recap.blade.php` | Tabel Ringkasan Kegiatan di Kinerja Operasi |
| `resources/views/manajer/partials/activity-strip.blade.php` | Kartu ringkas kegiatan |
| `resources/views/manajer/partials/activity-performance.blade.php` | Satu panel analisis kegiatan di Kinerja Operasi |
| `resources/views/manajer/partials/activity-detail.blade.php` | Isi satu panel Rincian Kegiatan |
| `resources/css/layouts/manajer.css` | Penanda bab halaman, tabel berkelompok, tab kegiatan, kepala panel |
| `public/js/components/charts.js` | Pemuatan panel, perpindahan tab, tooltip grafik |
| `tests/Feature/BlackBox/ManagerDashboardTest.php` | TC-MGR-08 s.d. TC-MGR-23 mengunci perilaku kedua menu |
