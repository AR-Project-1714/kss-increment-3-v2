# Rangkuman Perubahan Dashboard, Card, dan Chart

Dokumen ini merangkum seluruh perubahan tampilan dan data pada dashboard manajer, dashboard admin, halaman Performa Operasional, dan Arsip Laporan. Disusun agar mudah dirujuk kembali tanpa perlu membuka ulang riwayat percakapan.

---

## 1. Kartu KPI (Dashboard Manajer)

**Sebelum:** kartu statistik polos — label, angka, ikon. Tidak ada pembanding periode.

**Sesudah** ([manajer/layouts/card-kpi.blade.php](resources/views/manajer/layouts/card-kpi.blade.php)):

```text
┌─────────────────────────────┐
│ [ikon]  Judul Kartu          │
│                              │
│ 65.911 Ton     ▲ 48,8%       │
│ vs 1–25 Jun 2026             │
│ ╱╲___╱╲___╱                 │  ← sparkline bergradien
└─────────────────────────────┘
```

- Badge pill perubahan (hijau/merah/netral) di sebelah angka, bukan lagi teks polos.
- Sparkline 6 bulan dengan isian gradien mengikuti warna nada delta.
- Class terpisah (`.kpi-card`) dari `.stat-card` lama — Arsip dan halaman Admin lama tidak ikut berubah otomatis.
- Header kartu section (judul + garis biru tebal di atas) diganti header netral dengan garis rambut, supaya warna halaman datang dari isi chart, bukan bingkai kartu.

**Berlaku juga di:** Dashboard Admin, Log Aktivitas, Arsip Laporan (manajer & admin) — lewat partial bersama [`charts/kpi-row.blade.php`](resources/views/charts/kpi-row.blade.php) dan [`charts/delta.blade.php`](resources/views/charts/delta.blade.php).

---

## 2. Halaman Performa Operasional

### 2.1 Tren Tonase — line/bar toggle

- Grafik garis dengan area gradien, bisa ditukar ke bentuk batang lewat tombol di header kartu.
- Kedua bentuk digambar sekaligus di DOM, ditukar lewat CSS (`data-chart-view`) — tidak ada kedipan, tetap tampil walau JS gagal dimuat.
- Pilihan bentuk diingat di `localStorage`.
- Tooltip hover menampilkan tonase, jumlah laporan, dan jumlah kapal per bulan.

### 2.2 Komposisi Kegiatan — donut chart

- Lima kategori (Muat Curah, Muat Kantong, Bongkar Bahan Baku, Bongkar/Muat Container, Turba) dengan warna berbeda: biru, hijau, oranye, ungu, cyan.
- Legend di bawah donut (bukan di samping) karena nama kegiatan cukup panjang.
- Ukuran akhir: **210px** (sempat naik ke 340px lalu dikecilkan lagi agar proporsional terhadap kartu).

### 2.3 Rasio Kerusakan — gauge setengah lingkaran

- Tiga zona warna: hijau (<0,5%, terkendali), oranye (0,5–1%, perlu dipantau), merah (>1%, perlu tindak lanjut).
- Label skala (0% / 0,5% / 1% / 2%+) diposisikan mengikuti sudut busur, bukan dipisah di bawah SVG.
- Ukuran akhir: **260px** (sempat naik ke 400px lalu dikecilkan lagi).

### 2.4 Tonase per Shift — area chart bertumpuk

- Tiga pita warna (Pagi oranye, Sore biru, Malam ungu) menumpuk membentuk total bulanan.
- Dipakai ulang dari partial generik [`charts/area-stacked.blade.php`](resources/views/charts/area-stacked.blade.php) — komponen yang sama dipakai dashboard admin untuk grafik aktivitas sistem.

### 2.5 Perbandingan Regu — bar horizontal

- Menggantikan tabel lama. Warna mengikuti peringkat (hijau = tertinggi, oranye = terendah), bukan identitas regu.

### 2.6 Peringkat Lembur — panel baru

- Top 5 **jam lembur terbanyak** dan top 5 **frekuensi lembur terbanyak**, ditampilkan berdampingan.
- Perlu dua ukuran karena sebagian entri lembur diisi tanpa jam masuk/pulang — orang yang sering lembur belum tentu muncul di daftar jam.

### 2.7 Layout

```text
┌─────────────────────────┬──────────────────┐
│ Tren Tonase (line/bar)  │ Komposisi Kegiatan│
├─────────────────────────┼──────────────────┤
│ Tonase per Shift        │ Rasio Kerusakan   │
├─────────────────────────┴──────────────────┤
│ Perbandingan Regu │ Beban Kerja             │
├──────────────────────────────────────────────┤
│ Peringkat Lembur (selebar halaman)          │
├──────────────────────────────────────────────┤
│ Kapal Dilayani                               │
└──────────────────────────────────────────────┘
```

(Sempat dicoba tata letak 3 kolom sejajar, tapi dikembalikan ke 2 kolom sesuai permintaan — donut dan gauge tetap besar karena mengisi penuh lebar kolomnya.)

---

## 3. Dashboard Admin (Dashboard Sistem)

- 4 kartu KPI baru dengan pembanding periode: **Pengguna Aktif**, **Storage Terpakai**, **Aktivitas Hari Ini**, **Kejadian Keamanan** — menggantikan kartu statis tanpa delta.
- Grafik area bertumpuk **Aktivitas Sistem** (30 hari terakhir), dipecah 3 warna: Login (biru), Perubahan Data (hijau), Keamanan (oranye).
- "Status Backup Terakhir" dipindah dari kartu KPI ke keterangan di bawah grafik — nilainya bukan angka dan tidak punya pembanding, jadi tidak cocok jadi KPI.
- "Aktivitas Hari Ini" sengaja diberi warna netral (bukan merah saat turun) karena hari berjalan belum selesai, sehingga wajar lebih rendah dari kemarin.

**Sumber pembanding:** tabel baru `system_metric_snapshots`, diisi otomatis setiap hari lewat `php artisan system:snapshot` (dijadwalkan jam 23:50). Storage dan jumlah pengguna tidak punya riwayat historis di tabel lain, jadi snapshot ini yang jadi dasar perbandingan "vs kemarin" / "vs minggu lalu".

---

## 4. Arsip Laporan (Manajer & Admin)

- Kartu statistik lama diganti kartu KPI berdelta: **Laporan Hari Ini**, **Menunggu Tindakan**, **Laporan Bulan Ini** (dengan sparkline), **Total Arsip** (dengan persentase selesai).
- Pembanding bulan dipotong pada tanggal yang sama (1–25 Juli vs 1–25 Juni), bukan dibandingkan ke sebulan penuh — supaya adil.
- Badge yang menunjukkan porsi (bukan perubahan antar waktu) sengaja tanpa panah naik/turun, supaya tidak salah dibaca sebagai kenaikan/penurunan.
- Grafik "Tren Laporan Masuk" sempat ditambahkan lalu **dihapus kembali** atas permintaan — kartu KPI-nya tetap ada, tapi tanpa chart di bawahnya.

---

## 5. Log Aktivitas (Admin)

- Kartu statistik disamakan persis dengan Dashboard Sistem (kartu + delta yang sama), menggantikan kartu gaya lama yang terpisah.

---

## 6. Ekspor Excel Performa Operasional

Tombol **Ekspor** pada halaman Performa Operasional sebelumnya nonaktif (placeholder). Sekarang berfungsi dan menghasilkan berkas `.xlsx` berisi 5 sheet yang menyalin isi halaman:

| Sheet | Isi |
|---|---|
| **Ringkasan** | 4 KPI utama + perubahannya, status zona rasio kerusakan, beban kerja, tonase per shift |
| **Tren Bulanan** | Rekap 6 bulan: tonase, laporan, kapal, ton/shift, rasio kerusakan, pecahan per shift |
| **Regu & Kegiatan** | Perbandingan antar regu + komposisi jenis kegiatan beserta porsinya |
| **Peringkat Lembur** | Top 5 jam terbanyak & top 5 paling sering, masing-masing dengan angka pembandingnya |
| **Kapal Dilayani** | Daftar kunjungan kapal, kapasitas, termuat, realisasi, waktu sandar |

Detail penting:
- **Filter ikut terbawa.** Tombol mengirim query string yang sedang aktif (periode, regu, shift), dan controller membaca filter lewat helper yang sama dengan halaman — berkas selalu menggambarkan apa yang sedang tampil di layar.
- **Angka ditulis sebagai sel numerik**, bukan teks seperti pada ekspor arsip, sehingga penerimanya bisa langsung menghitung ulang (SUM, rata-rata). Format tampilan (ribuan, desimal, persen) tetap diterapkan.
- Ambang status rasio kerusakan pada sheet Ringkasan memakai batas yang sama dengan gauge di halaman (0,5% dan 1%), supaya ekspor tidak bercerita lain dari layarnya.
- Setiap unduhan tercatat di Log Aktivitas dengan tipe `export`.

File terkait: [`PerformanceExportService`](app/Services/PerformanceExportService.php), route `manajer.performa.export`.

---

## 7. Paginasi (perbaikan lintas halaman)

**Masalah lama:** semua nomor halaman dirender sekaligus (mis. 1 sampai 37), tidak ada windowing.

**Perbaikan:** helper baru [`App\Support\PaginationWindow`](app/Support/PaginationWindow.php) menghasilkan jendela 5 halaman di sekitar halaman aktif, dengan halaman pertama & terakhir selalu terlihat sebagai pintasan lompat ke ujung:

```text
1  …  15  16  17  18  19  …  37
```

Diterapkan di 4 lokasi (bug yang sama, sekali perbaikan lewat partial bersama menjangkau banyak halaman):

| Lokasi | File |
|---|---|
| Partial paginasi admin (dipakai Arsip Admin, Kelola Pengguna, 7 tab Data Master) | [admin/layouts/pagination.blade.php](resources/views/admin/layouts/pagination.blade.php) |
| Arsip Laporan (manajer) | [manajer/archive.blade.php](resources/views/manajer/archive.blade.php) |
| Riwayat & Diterima (Report-Ops) | [report-ops/index.blade.php](resources/views/report-ops/index.blade.php) |

---

## 8. Data Demo (Seeder)

[`PerformanceDemoSeeder`](database/seeders/PerformanceDemoSeeder.php) — dijalankan manual, tidak otomatis ikut `db:seed` biasa:

```bash
php artisan db:seed --class=PerformanceDemoSeeder
```

Menghasilkan:
- ~324 laporan operasi 6 bulan terakhir (4 regu × 3 shift), tren tonase musiman naik-turun agar grafik tidak datar.
- Realisasi muat kapal 76–95% (bukan 999% seperti data lama yang salah input).
- 10 laporan pemeliharaan + 8 laporan K3 demo.
- 30 hari log aktivitas admin untuk mengisi grafik Aktivitas Sistem, termasuk beberapa hari dengan lonjakan insiden keamanan agar zona merah pada grafik ikut teruji.

Seeder ini hanya menghapus datanya sendiri (ditandai `[demo]` / `payload.source`), aman dijalankan berulang tanpa menyentuh data asli.

---

## 9. Perapian Struktural

- CSS chart (donut, gauge, area, kpi-card, dsb.) dan JS tooltip/toggle-nya dipindah ke lokasi bersama — [`charts.css`](resources/css/components/charts.css) dan [`charts.js`](public/js/components/charts.js) — di-import oleh layout manajer maupun admin, menghindari duplikasi ~600 baris CSS.
- Perhitungan pemakaian storage dipindah dari controller ke [`SystemMetricsService`](app/Services/SystemMetricsService.php), dipakai bersama halaman Backup dan perekam metrik harian.
- Statistik arsip dipindah ke [`ArchiveMetricsService`](app/Services/ArchiveMetricsService.php).
- Kode dan partial yang jadi tidak terpakai setelah refactor (mis. `manajer/layouts/card.blade.php`, method kartu statistik gaya lama) sudah dihapus.

---

## Catatan Uji

- 165 test otomatis (PHPUnit) lulus semua setelah seluruh perubahan di atas — termasuk dua pengujian baru untuk ekspor performa (isi berkas dibongkar sungguhan, bukan sekadar dicek header-nya).
- Sudah diverifikasi manual di browser: light & dark mode, lebar desktop (1440px) dan mobile (375px), tanpa error console, tanpa overflow horizontal.
