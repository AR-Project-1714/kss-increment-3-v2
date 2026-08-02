# Rancangan Perbaikan Dashboard, Kinerja Operasi, dan Peringkat Lembur

## Tujuan

Membuat Dashboard Manajer lebih cepat dibaca untuk kondisi operasi harian, menyelaraskan tampilan **Kinerja Operasi** dengan susunan pada referensi spreadsheet, serta memastikan **Rincian Kegiatan** menampilkan peringkat lembur seluruh karyawan dengan 10 peringkat teratas sebagai tampilan awal.

Rancangan ini merupakan perluasan antarmuka yang sudah ada, bukan penggantian desain sistem. Bahasa, kartu, warna berbasis token, dark mode, filter periode/regu/shift, dan alur ekspor yang sekarang tetap dipertahankan.

## Ringkasan keputusan

| Area | Perubahan yang dirancang | Hasil untuk manajer |
|---|---|---|
| Dashboard | Menambah kartu/section **Kinerja Operasi** yang merangkum kegiatan pelabuhan dalam format grid seperti spreadsheet. | Kondisi setiap kegiatan terbaca tanpa harus membuka banyak halaman. |
| Kinerja Operasi | Menata ringkasan kegiatan dan analitik dengan urutan yang sama seperti referensi: kegiatan utama di atas, tujuh analisis di bawah. | Halaman menjadi pembacaan lengkap dari ringkasan menuju analisis. |
| Rincian Kegiatan | Peringkat lembur per kegiatan memuat seluruh karyawan yang relevan; hanya 10 pertama yang langsung terlihat. | Daftar tetap ringkas saat dibuka, tetapi tidak ada karyawan yang tersembunyi permanen. |

## 1. Dashboard Manajer: kartu Kinerja Operasi

### Posisi dan tujuan

- Letakkan section **Kinerja Operasi** setelah deretan KPI dashboard dan sebelum **Laporan Masuk**.
- Section ini adalah ringkasan navigasi, bukan duplikasi seluruh grafik pada halaman Kinerja Operasi.
- Kepala section menampilkan judul, periode aktif/default, serta tombol **Buka Kinerja Operasi**. Tombol membawa pengguna ke `manajer.performa`.
- Empat KPI dashboard yang telah ada (Tonase Ditangani, Kapal Dilayani, Tonase per Shift, Rasio Kerusakan) tetap dipertahankan agar ringkasan eksekutif tidak hilang.

### Isi kartu

Pada desktop, gunakan grid dua kolom; pada layar kecil berubah menjadi satu kolom. Setiap kelompok kegiatan memiliki ikon kecil, judul, lalu baris metrik berbentuk `label — nilai satuan`. Nilai harus rata kanan dengan angka tabular agar mudah dibandingkan.

| Kelompok kegiatan | Baris metrik yang ditampilkan | Satuan |
|---|---|---|
| Pemuatan Pupuk Kantong | Kapal; Pengiriman; Pemuatan; Kerusakan | Kapal, Ton |
| Pemuatan Urea Curah | Kapal; Pemuatan | Kapal, Ton |
| Pemuatan Amoniak | Kapal; Pemuatan | Kapal, Ton |
| Bongkar Bahan Baku | Kapal; Pembongkaran | Kapal, Ton |
| Bongkar Container | Kapal; Bongkar Empty | Kapal, Teus |
| Muat Container | Kapal; Muat Full | Kapal, Teus |
| Trucking ke Gudang Turba | Rit/DO; Pembongkaran/Pengiriman | Rit, Ton |

Catatan data:

- Gunakan satuan yang telah dipakai sistem: **Ton** dan **Teus**. Istilah `MT` pada spreadsheet diperlakukan sebagai Ton hanya bila sumber data memang Ton; antarmuka tidak boleh mengonversi atau membuat nilai baru.
- Container tetap dipisahkan menjadi **Bongkar Empty** dan **Muat Full**, sesuai katalog kegiatan saat ini. Keduanya tidak masuk total tonase.
- Jika sebuah metrik tidak memiliki data pada periode aktif, tampilkan `—` dan keterangan singkat yang jujur, bukan angka nol yang tampak sebagai capaian.
- Klik judul kelompok atau tautan **Lihat rincian** mengarahkan ke Rincian Kegiatan pada tab kegiatan terkait dengan filter aktif yang ikut terbawa.

### Sumber dan dampak implementasi

- Tambahkan ringkasan data dashboard pada `OperationalPerformanceService` dari katalog kegiatan yang sudah menjadi sumber kebenaran; jangan menyalin rumus ke Blade.
- `ManajerController::dashboardKpi()`/cache dashboard dapat memperluas payload dengan ringkasan kegiatan tersebut. Kunci cache dan mekanisme invalidasi yang berlaku tetap dipakai.
- Render section di `resources/views/manajer/index.blade.php` sebagai kartu yang menggunakan kelas dan token incumbent, lalu tambahkan aturan responsif di `resources/css/layouts/manajer.css`.

## 2. Menu Kinerja Operasi

### Struktur pembacaan

Tampilan mengikuti alur pada referensi spreadsheet: identitas menu dan periode, daftar kegiatan, lalu daftar analitik. Filter periode, regu, dan shift tetap menjadi sumber konteks seluruh angka.

1. **Kepala halaman** — judul `Kinerja Operasi`, periode yang terbaca jelas, dan toolbar filter yang sudah ada.
2. **Ringkasan Kegiatan Operasi** — grid dua kolom memakai tujuh kelompok kegiatan dan metrik pada tabel Dashboard di atas. Bagian ini menjadi terjemahan digital dari blok atas spreadsheet.
3. **Analitik Operasi** — susun dan beri nomor blok analitik berikut agar urutannya stabil:
   1. Tren kuantum (Tonase dan Teus);
   2. Komposisi kegiatan (Muat Kantong, Muat Curah, Muat Amoniak, Bongkar Bahan Baku, Bongkar Container, Muat Container, Trucking Turba);
   3. Kuantum per shift (Tonase dan Teus);
   4. Rasio kerusakan;
   5. Perbandingan regu;
   6. Beban kerja;
   7. Peringkat lembur.

`Tren kuantum` dan `kuantum per shift` boleh menggunakan visual grafik yang saat ini sudah ada, tetapi judul dan legenda harus memperjelas bahwa Ton dan Teus tidak dijumlahkan. Komposisi kegiatan hanya menghitung kegiatan bersatuan Ton, sedangkan container dilaporkan terpisah agar satuannya tidak tercampur.

### Perilaku dan batasan

- Kelompok kegiatan dengan nilai nol seluruhnya tidak perlu membentuk kartu kosong; tampilkan status kosong hanya bila semua kegiatan dalam section tidak memiliki data.
- Link dari setiap kelompok kegiatan membuka tab yang tepat di Rincian Kegiatan, bukan sekadar halaman awal tab pertama.
- Halaman tidak mengubah logika status laporan terhitung: hanya `submitted`, `acknowledged`, dan `approved` yang masuk angka.
- Tidak ada tabel detail per baris laporan di halaman ini. Penelusuran per kapal/rit tetap berada di ekspor dan Rincian Kegiatan.

## 3. Rincian Kegiatan: peringkat lembur seluruh karyawan

### Perilaku yang diinginkan

- Pada setiap tab kegiatan, blok **Peringkat Lembur** berisi **seluruh karyawan** yang memiliki catatan lembur pada laporan yang memuat kegiatan tersebut dan sesuai filter aktif.
- Saat panel pertama kali terbuka, tampilkan peringkat 1–10 saja.
- Jika jumlah karyawan lebih dari 10, tampilkan tombol `Lihat semua N personil` di bawah tabel. Setelah ditekan, seluruh baris tampil di panel yang sama dan label berubah menjadi `Tampilkan 10 teratas`.
- Tombol tidak melakukan muat ulang halaman atau permintaan data tambahan. Data lengkap sudah berada pada payload panel kegiatan yang sedang dibuka.
- Urutan utama tetap total jam lembur, lalu frekuensi, lalu nama. Kolom yang dipertahankan: Posisi, Nama Petugas, Jumlah Lembur, Total Jam Lembur, dan Rata-rata Jam Lembur.
- Bila tidak ada lembur pada kegiatan/periode tersebut, tampilkan satu empty state yang jelas dan jangan tampilkan tombol.

### Temuan pada implementasi saat ini

Komponen `resources/views/manajer/charts/overtime-leaders.blade.php` sebenarnya sudah mendukung pola 10 teratas dan tombol `Lihat semua`. Namun data untuk panel Rincian Kegiatan dipangkas menjadi lima di `OperationalPerformanceService::overtimeLeadersFrom()` karena `activityPanelOvertime()` memakai batas bawaan tersebut. Akibatnya tombol tidak akan pernah dapat membuka seluruh karyawan untuk konteks per kegiatan.

### Perubahan teknis yang direncanakan

1. Pada `activityPanelOvertime()`, minta `overtimeLeadersFrom()` mengembalikan ranking tanpa limit (`limit: null`).
2. Pada `resources/views/manajer/partials/activity-detail.blade.php`, panggil komponen `overtime-leaders` dengan `visible => 10` secara eksplisit.
3. Pertahankan JavaScript `data-leader-toggle` pada `public/js/components/charts.js`; pastikan ia tetap bekerja setelah panel kegiatan dimuat lewat `fetch` dan ketika pengguna berpindah tab.
4. Biarkan daftar jam dan frekuensi yang khusus digunakan ekspor mengikuti kebutuhan ekspor yang ada; perubahan antarmuka tidak boleh mengubah isi workbook tanpa keputusan terpisah.

## 4. Responsif, aksesibilitas, dan keadaan data

- Desktop: ringkasan kegiatan dua kolom dengan label di kiri dan angka/satuan di kanan.
- Tablet/mobile: satu kolom; tabel lembur boleh digeser horizontal, dengan kolom nama tetap mudah terbaca.
- Tombol `Lihat semua` memakai `aria-expanded`, `aria-controls`, dan fokus yang terlihat pada mode terang maupun gelap.
- Semua warna memakai CSS variable yang sudah ada. Tidak menambahkan hex warna atau dependensi baru.
- Status loading panel tetap menggunakan skeleton yang ada. Jika pengambilan panel gagal, tampilkan pesan dan tindakan coba lagi tanpa menghapus tab yang dipilih.

## 5. Berkas yang terdampak

| Berkas | Peran perubahan |
|---|---|
| `app/Services/OperationalPerformanceService.php` | Menyediakan ringkasan kegiatan dashboard; menghapus limit lima untuk ranking lembur per kegiatan. |
| `app/Http/Controllers/ManajerController.php` | Meneruskan payload ringkasan operasi dan menjaga cache dashboard. |
| `resources/views/manajer/index.blade.php` | Menampilkan section Kinerja Operasi pada Dashboard. |
| `resources/views/manajer/performa.blade.php` | Menata blok ringkasan kegiatan dan urutan analitik sesuai rancangan. |
| `resources/views/manajer/kegiatan.blade.php` | Menerima konteks tab kegiatan dari tautan dashboard/kinerja bila diperlukan. |
| `resources/views/manajer/partials/activity-detail.blade.php` | Menetapkan tampilan awal ranking lembur sebanyak 10 baris. |
| `resources/views/manajer/charts/overtime-leaders.blade.php` | Memakai ulang pola buka/tutup daftar penuh yang telah tersedia. |
| `resources/css/layouts/manajer.css` dan `resources/css/components/charts.css` | Grid, hierarki, responsivitas, serta dark mode. |
| `public/js/components/charts.js` | Memastikan tab target dan tombol lihat semua berjalan pada panel yang dimuat dinamis. |
| `tests/Feature/BlackBox/ManagerDashboardTest.php` | Pengujian perilaku dashboard, rincian kegiatan, dan ranking lembur. |

## 6. Kriteria penerimaan

1. Dashboard memiliki section Kinerja Operasi yang menampilkan tujuh kelompok kegiatan dan angka bersatuan benar untuk periode aktif.
2. Nilai Ton dan Teus tidak pernah dijumlahkan atau diberi satuan yang keliru.
3. Halaman Kinerja Operasi mengikuti urutan tujuh analitik pada rancangan, dengan filter tetap memengaruhi angka yang seharusnya terfilter.
4. Tautan kegiatan membuka Rincian Kegiatan pada tab yang sesuai serta mempertahankan filter.
5. Untuk lebih dari 10 karyawan lembur pada satu kegiatan, halaman awal hanya memuat 10 baris, tombol menyebut jumlah total personil, dan satu klik menampilkan seluruh baris tanpa request tambahan.
6. Untuk tepat 10 atau kurang karyawan, tombol `Lihat semua` tidak muncul.
7. Tampilan dan kontrol tetap dapat digunakan pada layar kecil, keyboard, dark mode, dan kondisi tanpa data.

## Asumsi yang perlu dikonfirmasi saat implementasi

- Nama `Trucking ke Gudang Turba` pada referensi dipetakan ke kegiatan yang saat ini bernama `Trucking Pengiriman Pupuk Kantong`; perubahan nama tampilan tidak sekaligus mengubah nama data atau ekspor.
- Periode pada Dashboard mengikuti periode dashboard yang sekarang. Jika Dashboard harus memakai pemilih periode yang sama dengan Kinerja Operasi, itu perlu ditambahkan sebagai keputusan tersendiri karena mengubah konteks seluruh KPI dashboard.
