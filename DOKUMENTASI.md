# Sistem Laporan Operasional KSS

Dokumentasi proyek Sistem Laporan Operasional KSS — aplikasi web internal untuk pencatatan, pelaporan, dan distribusi laporan shift harian kegiatan operasional pelabuhan/bongkar muat.

---

## 1. Gambaran Umum

Aplikasi ini menggantikan proses pelaporan shift harian yang sebelumnya manual menjadi sistem digital terstruktur. Setiap regu (Group) yang bertugas mengisi laporan multi-step yang merangkum aktivitas muat kantong, muat curah, bongkar, tracking pengiriman, cek unit kendaraan, hingga daftar karyawan. Laporan kemudian diserahkan ke regu penerima untuk ditandatangani secara digital, dan akhirnya bisa diekspor menjadi PDF atau Excel.

**Status alur laporan:**

| Status | Label UI | Arti |
|---|---|---|
| `draft` | Draft | Laporan masih disimpan oleh pembuat, belum dikirim |
| `submitted` | Menunggu TTD | Diserahkan ke regu penerima, menunggu tanda tangan |
| `acknowledged` | Menunggu Approval | Sudah ditanda tangani oleh regu penerima |
| `approved` | Disetujui | Dikonfirmasi final oleh manajer (approval hanya milik manajer) |

> Status laporan dikelola memakai PHP 8.1 Backed Enum `App\Enums\ReportStatus` (di-cast pada model `DailyReport`). Method `ReportStatus::label()` menyediakan label UI di atas. Approval final `acknowledged → approved` hanya dilakukan role `manajer`.

### 1.1 Pendekatan Pengembangan Inkremental

Sistem dibangun dengan model pengembangan **inkremental**: setiap increment menambahkan satu modul fungsional utuh di atas fondasi arsitektur yang sama (Laravel + Blade + Eloquent + design system bersama). Hingga dokumen ini diperbarui, **tiga increment telah selesai dibangun**, sehingga sistem memiliki **3 modul laporan**: Operasional, Pemeliharaan, dan Safety/K3.

| Increment | Lingkup | Komponen yang dibangun | Status |
|---|---|---|---|
| **Increment 1** | Fondasi sistem + **Modul Operasional** + area **Manajer** + area **Admin** | Autentikasi berbasis username & 5 role, alur laporan shift harian (multi-step, tanda tangan digital, export PDF/Excel), dashboard & arsip manajer dengan approval final, dashboard admin (kelola user, data master, backup, log aktivitas, pusat bantuan), design system, dan master data | ✅ Selesai |
| **Increment 2** | **Modul Pemeliharaan** (Unit Kerja Pemeliharaan & Peralatan) | Dashboard petugas pemeliharaan, form laporan harian non-shift (Pekerjaan Utama/Prioritas, Kondisi Unit, Daftar Hadir, Pengesahan), alur 2 pihak `draft → submitted → approved`, approval di sisi manajer, integrasi arsip admin, dan penyatuan master data ke `master_units`/`master_employees` | ✅ Selesai |
| **Increment 3** | **Modul Safety/K3** (Laporan Harian K3) | Dashboard petugas safety (Karu Safety), form laporan harian (Info Umum, Inspeksi K3 berbasis lokasi + item, Kegiatan Operasi & Pemeliharaan, Laporan Kejadian), alur 2 pihak `draft → submitted → approved`, approval di sisi manajer (tab Safety), integrasi arsip admin, master data Lokasi & Item K3, autosave draft, dan export PDF | ✅ Selesai |

> **Catatan paradigma:** Modul Operasional berbasis **shift** dengan serah-terima antar regu (alur 3 tahap `submitted → acknowledged → approved`). Modul Pemeliharaan dan Safety berbasis **hari (Non Shift)** tanpa serah-terima (alur 2 tahap `submitted → approved`, pengesahan dua pihak: petugas pembuat & manajer). Increment 2 dan 3 memakai kembali role, layout, dan design system Increment 1, tetapi sengaja **tidak** memakai field Shift/Group/Jam Kerja/Regu Penerima. Rincian perancangan tiap modul terdapat di [`PERANCANGAN_MODUL_PEMELIHARAAN.md`](PERANCANGAN_MODUL_PEMELIHARAAN.md) dan [`PERANCANGAN_MODUL_SAFETY.md`](PERANCANGAN_MODUL_SAFETY.md).

---

## 2. Stack Teknologi

### Backend
- **PHP 8.3+**
- **Laravel 13.8** — framework utama
- **Eloquent ORM** dengan relasi `hasMany` / `hasOne` / `belongsTo`
- **barryvdh/laravel-dompdf 3.1** — generasi PDF
- **phpoffice/phpspreadsheet 5.7** — generasi Excel
- **Laravel Tinker, Pail, Pao** — tooling
- **Laravel Pint** — code formatter (PSR-12)

### Frontend
- **Blade Templates** (Laravel)
- **Vite 8** — bundler
- **Tailwind CSS 4** (`@tailwindcss/vite`) — utility framework (di-import via `resources/css/app.css`)
- **Bootstrap 5.3.3** — utility class & grid (loaded via CDN di layout)
- **Flatpickr** — time/date picker
- **Flaticon UICons 2.6.0** — icon set (Regular/Bold/Solid Rounded) loaded via CDN
- **Google Font Poppins** — typografi utama

### Database
- Default: **SQLite** (`database/database.sqlite`) untuk dev
- Production-ready: **MySQL/MariaDB/PostgreSQL** (sesuai konfigurasi `.env`)

### Dev tooling
- `composer dev` — menjalankan server, queue listener, log streamer (Pail), dan Vite secara konkuren
- `composer test` — menjalankan PHPUnit 12
- `npm run dev` / `npm run build` — Vite

---

## 3. Struktur Direktori

```
app/
├── Http/Controllers/
│   ├── LoginV2Controller.php       # Auth (login/logout)
│   ├── ReportOpsController.php     # Inti: CRUD laporan, sign, export, search
│   ├── ManajerController.php       # Dashboard manajer, approval, arsip, search arsip
│   └── ReportController.php        # (legacy/aux)
└── Models/
    ├── DailyReport.php             # Laporan utama
    ├── LoadingActivity.php         # Muat Kantong (per-kapal)
    ├── LoadingTimesheet.php        # Aktivitas timesheet pengiriman/pemuatan
    ├── BulkLoadingActivity.php     # Muat Curah
    ├── BulkLoadingLog.php          # Log laporan harian + COB
    ├── MaterialActivity.php        # Bongkar (curah)
    ├── MaterialItem.php            # Detail bahan baku bongkar
    ├── ContainerActivity.php       # Bongkar (kontainer)
    ├── ContainerItem.php           # Detail kontainer
    ├── TurbaActivity.php           # Tracking (nama model legacy Turba)
    ├── TurbaDelivery.php           # Detail pengiriman truk
    ├── UnitCheckLog.php            # Cek unit kendaraan/forklift
    ├── EmployeeLog.php             # Karyawan shift / OP.7 / pengganti / lembur / lain
    ├── MasterEmployee.php          # Master data karyawan
    ├── MasterUnit.php              # Master kendaraan/forklift
    ├── MasterTruck.php             # Master truk
    ├── MasterInventoryItem.php     # Master inventaris
    ├── Role.php                    # Role (admin/manajer/operasional/pemeliharaan/safety)
    ├── User.php                    # User + relasi role
    ├── MaintenanceReport.php       # [Increment 2] Laporan harian pemeliharaan
    ├── MaintenanceWorkItem.php     # [Increment 2] Pekerjaan Utama & Prioritas
    ├── MaintenanceUnitCondition.php# [Increment 2] Kondisi unit (ready/rusak)
    ├── MaintenanceAttendance.php   # [Increment 2] Daftar hadir karyawan
    ├── SafetyReport.php            # [Increment 3] Laporan harian K3
    ├── SafetyInspection.php        # [Increment 3] Inspeksi K3 per lokasi+item (snapshot)
    ├── SafetyOperationLog.php      # [Increment 3] Kegiatan Operasi & Pemeliharaan
    ├── SafetyIncidentLog.php       # [Increment 3] Laporan Kejadian & lain-lain
    ├── MasterSafetyLocation.php    # [Increment 3] Master lokasi inspeksi K3
    └── MasterSafetyItem.php        # [Increment 3] Master item/objek inspeksi K3
database/migrations/
├── 2026_05_18_000001_add_ops_auth_columns_to_users_table.php
├── 2026_05_18_000002_create_operational_report_tables.php
└── 2026_05_19_000001_update_roles_to_five_development_roles.php
resources/views/report-ops/
├── index.blade.php                 # Dashboard operasional (Laporan Masuk, Draft, Riwayat)
├── create.blade.php                # Form buat laporan (multi-step)
├── edit.blade.php                  # Form edit laporan
├── pdf.blade.php / viewpdf.blade.php
├── layouts/app.blade.php           # Layout + CSS global + variable theme
└── sections/
    ├── step1-infoumum.blade.php
    ├── step2-muatkantong.blade.php
    ├── step3-muatcurah.blade.php
    ├── step4-bongkar.blade.php
    ├── step5-gudangturba.blade.php   # Tracking (nama file legacy)
    ├── step6-cekunit.blade.php
    └── step7-karyawan.blade.php
resources/views/manajer/
├── index.blade.php                  # Dashboard manajer + laporan masuk
├── archive.blade.php                # Arsip laporan + search/filter
├── bantuan.blade.php                # Pusat bantuan manajer
└── layouts/
    ├── app.blade.php                # Layout manajer + responsive sidebar + toast/loading
    ├── card.blade.php               # Card statistik
    ├── navbar.blade.php             # Navbar manajer
    └── sidebar.blade.php            # Menu Dashboard / Arsip / Bantuan
resources/views/pemeliharaan/        # [Increment 2] Modul Pemeliharaan
├── index.blade.php                  # Dashboard petugas (Draft / Riwayat)
├── create.blade.php / edit.blade.php# Form laporan harian (6 seksi)
├── pdf.blade.php / viewpdf.blade.php
├── layouts/                         # app + header + footer
└── partials/                        # report-form, report-paper
resources/views/report-safety/       # [Increment 3] Modul Safety/K3
├── index.blade.php                  # Dashboard petugas safety (Draft / Riwayat)
├── create.blade.php / edit.blade.php# Form laporan harian K3 (4 seksi)
├── pdf.blade.php / viewpdf.blade.php
├── layouts/                         # app + header + footer
└── partials/                        # report-form, report-paper
routes/web.php
```

---

## 4. Autentikasi & Otorisasi

- **Login berbasis username** (bukan email). Skema user diperluas dengan kolom: `username`, `role_id`, `status`, `signature_path`, `group`.
- **Role**: `admin`, `manajer`, `operasional`, `pemeliharaan`, dan `safety` (lewat tabel `roles`).
- **Group / Regu**: setiap user operasional memiliki kode regu (mis. `A`, `B`, `C`, `D`) yang dipakai untuk memfilter laporan masuk/keluar.
- **Signature path**: kolom `signature_path` menyimpan path tanda tangan digital user (dipakai saat menandatangani laporan).
- Middleware `auth` mengamankan semua route aplikasi; `guest` untuk halaman login.
- Akun `manajer` diarahkan ke `/manajer` setelah login dan dicegah mengakses halaman divisi seperti `/report-ops` oleh `PreventManagerDivisionAccess`.
- Route manajer melakukan guard tambahan lewat `ManajerController::authorizeManagementAccess()` sehingga hanya role `manajer` dan `admin` yang dapat membuka halaman manajer.
- **Rate limiting login**: proses login dibatasi 5 percobaan per kombinasi username/IP selama 60 detik, serta 20 percobaan per IP selama 5 menit untuk menahan brute force yang mengganti-ganti username. Login gagal, percobaan akun nonaktif, dan lockout brute force dicatat sebagai log keamanan di `admin_activity_logs`.
- **Monitoring keamanan admin**: Dashboard admin menampilkan jumlah login gagal hari ini, sedangkan menu Log Aktivitas menyediakan filter tipe `Keamanan` untuk melihat percobaan login gagal dan brute force.

---

## 5. Fitur Utama

### 5.1 Dashboard Operasional (`/report-ops`)
Tiga tab utama:

1. **Laporan Masuk** — laporan dengan status `submitted` yang ditujukan ke regu user untuk ditanda tangani.
2. **Draft** — laporan setengah jadi milik user. Ada reminder card "Laporan Belum Diselesaikan" jika ada draft tersisa.
3. **Riwayat Laporan** — semua laporan yang relevan (dibuat, dikirim, atau diterima oleh user), dengan paginasi 10/halaman.

### 5.2 Pencarian Laporan (Riwayat)
Pencarian server-side + dropdown saran live:

- **Endpoint suggestion**: `GET /report-ops/history/suggestions?q=...` mengembalikan JSON berisi sampai 8 laporan paling relevan.
- Perilaku terbaru: Enter menjalankan pencarian server dan suggestion dipakai sebagai keyword tabel, bukan membuka halaman detail laporan.
- **Dropdown dropdown live** muncul saat user mengetik (debounce 200ms), menampilkan ringkasan: judul + tanggal, ID Dokumen, chip Shift (warna sesuai shift), chip Regu pengirim → penerima, chip Status, dan label "Terakhir diedit …".
- **Tidak ada page reload** saat mengetik. Request sebelumnya dibatalkan via `AbortController`.
- Navigasi keyboard: ↑/↓ pilih item, **Enter** buka di tab baru, **Esc** kosongkan & tutup.
- **Pencarian tanggal Bahasa Indonesia**: bisa cari dengan `Mei`, `Mei 2026`, `19 Mei`, `19 Mei 2026`, `19/05/2026`, dll. Keyword bulan parsial yang tidak ambigu seperti `apri`, `janu`, `me`, dan `jul` ikut dikenali sebagai April, Januari, Mei, dan Juli. Helper `buildDateSearchPatterns()` di controller mem-parsing keyword jadi pola LIKE untuk kolom `report_date`; jika keyword terbaca sebagai tanggal, query difokuskan ke kolom `report_date` agar lebih ringan.
- **Search lain**: ID dokumen (`#OPS-YYYY-NNN`), shift, regu, nama kapal, agen, jetty, employee, truck, aktivitas, COB, dll. (lihat `applyHistorySearch` di `ReportOpsController`).

Catatan: perilaku Enter yang aktif saat ini adalah submit pencarian tabel; pembukaan detail laporan dilakukan dari tombol aksi tabel.

### 5.3 Form Laporan Multi-Step (Create & Edit)

Form dibagi menjadi 7 step dengan tab navigation:

| Step | File | Konten |
|---|---|---|
| 1 | `step1-infoumum.blade.php` | Tanggal, Shift (Pagi/Siang/Malam), Group/Regu, Jam Kerja, Regu Penerima |
| 2 | `step2-muatkantong.blade.php` | Aktivitas muat kantong per-kapal + dua **Timesheet**: Pengiriman & Pemuatan |
| 3 | `step3-muatcurah.blade.php` | Aktivitas muat curah per-kapal + **Laporan Harian** timesheet dengan kolom COB (Close of Business) |
| 4 | `step4-bongkar.blade.php` | Bongkar (bahan baku / kontainer), tabel material/container item |
| 5 | `step5-gudangturba.blade.php` | Tracking pengiriman + tabel pengiriman truk |
| 6 | `step6-cekunit.blade.php` | Cek unit forklift, kendaraan, dan inventaris (kondisi terima/serahkan) |
| 7 | `step7-karyawan.blade.php` | 4 sub-tab: Karyawan Shift, Relief & Lembur, OP.7 & Pengganti, Lain-lain |

#### Fitur khusus form

- **Auto-save sebagai draft**: tombol "Simpan Sebagai Draft" (create) / "Simpan Pembaruan" (edit) di sticky header.
- **Auto-fill shift & jam kerja WITA**: saat membuat laporan baru, Shift otomatis mengikuti jam WITA (Pagi 07.00-15.00, Siang 15.00-23.00, Malam 23.00-07.00). Jika user memilih Shift manual, kolom Jam Kerja tetap sinkron.
- **Sync jam karyawan**: jam masuk & pulang di tabel **Karyawan Shift** dan **OP.7** otomatis mengikuti Jam Kerja dari Info Umum. Saat status diubah ke "Cuti" / "Tidak Masuk", jam dikosongkan otomatis.
- **Auto-fill OP.7 Forklift & Area**: tabel OP.7 terisi otomatis berurutan dari mapping 11 forklift (FL.KSS-100 → P.6, FL.KSS-101 → Popka, FL.KSS-102/104 → Bagging-1, FL.KSS-105/106 → Bagging-2, FL.KSS-108 → Gudang Produk Tursina, FL.KSS-109/103/107/110 → Blending).
- **Auto-fill karyawan per-regu**: tabel Karyawan Shift & OP.7 otomatis terisi nama-nama karyawan sesuai regu yang dipilih (dari `master_employees`).
- **Kapal aktif berkelanjutan**: Muat Kantong dan Muat Curah menyimpan pekerjaan kapal ke `ship_operations`; kapal berstatus `active` muncul sebagai saran di laporan shift berikutnya, hilang saat ditandai `Selesai`, dan otomatis dibersihkan jika tidak digunakan lebih dari 3 hari.
- **Input angka non-negatif**: kapasitas, jumlah, BBM, COB, dan qty lain tidak boleh negatif. Scroll mouse pada input angka yang sedang fokus dicegah agar nilai tidak berubah tanpa sengaja.
- **Validasi regu penerima**: Group/Regu penerima tidak boleh sama dengan Group/Regu pengirim saat submit final.
- **Cek unit berkelanjutan**: default Kondisi Terima mengambil Kondisi Diserahkan dari laporan sebelumnya; default Kondisi Diserahkan mengikuti Kondisi Terima sampai user mengubahnya manual.
- **Datalist master data**: input nama karyawan, kendaraan, forklift, dan truck di-attach `<datalist>` untuk autocomplete.
- **Validasi step-by-step**: tombol Lanjut memvalidasi step berjalan; tombol Kembali untuk navigasi mundur. Tombol Selesai (di step terakhir) memicu modal konfirmasi.

#### Timesheet (Pengiriman / Pemuatan / Laporan Harian COB)

- Input baris: jam + aktivitas (+ COB Ton untuk muat curah).
- Klik **Tambah** → baris validasi → ditampilkan sebagai **timeline-item** kronologis dengan dot di sebelah kiri.
- Setiap timeline-item punya tombol **Edit** (pencil) dan **Hapus** (trash) yang muncul saat hover.
- **Edit**: klik pencil → row form kembali muncul dengan data terisi (highlight border biru/orange sesuai variant), user koreksi, klik Tambah → item ter-update di posisi yang sama (tidak pindah ke akhir).
- Variant warna: `deliv` (biru) untuk Pengiriman, `load` (orange) untuk Pemuatan, `deliv` lebar penuh untuk Laporan Harian.
- Layout: di laptop (>1024px) Timesheet Pengiriman & Pemuatan **berdampingan**; di tablet/HP (≤1024px) **bertumpuk** vertikal.

### 5.4 Tanda Tangan Digital

User operasional regu penerima dapat menanda tangani laporan masuk:

- Klik tombol **Tanda Tangani** pada laporan masuk → modal konfirmasi.
- Tersedia tombol **Periksa Laporan** untuk meninjau isi sebelum menanda tangani.
- Hanya bisa dilakukan jika `received_by_group` cocok dengan regu user, dan user bukan pembuat laporan.
- Submission: `POST /report-ops/{report}/sign` → mengubah status ke `acknowledged`, mengisi `received_by_user_id` dan `received_at`.

### 5.5 Export

- **PDF**: `GET /report-ops/{report}/pdf` — menggunakan DomPDF, render template `pdf.blade.php` / `viewpdf.blade.php`.
- **Excel**: `GET /report-ops/{report}/excel` — menggunakan PhpSpreadsheet.
- Indikator loading muncul pada tombol export selama proses.

### 5.6 Edit & Hapus Draft

- **Edit**: hanya boleh oleh pembuat (`created_by`) dan jika status `draft` atau `submitted`.
- **Hapus**: hanya boleh oleh pembuat dan jika status masih `draft`. Modal konfirmasi muncul sebelum eksekusi.

### 5.7 Dashboard dan Arsip Manajer (`/manajer`)

Role `manajer` memiliki area sendiri untuk proses approval akhir dan arsip laporan.

Menu utama:

1. **Dashboard** - menampilkan card statistik dan daftar laporan masuk dari divisi. Tab divisi yang aktif: **Operasional**, **Pemeliharaan**, dan **Safety** — masing-masing menampilkan laporan masuk yang menunggu approval manajer.
2. **Arsip Laporan** - menampilkan laporan berstatus `submitted`, `acknowledged`, dan `approved` dengan label user-facing "Diserahkan" atau "Ditanda Tangani".
3. **Pusat Bantuan** - berisi penjelasan sistem, alur laporan, status, pencarian/filter, batas akses, dan langkah awal saat ada kendala.

Perilaku approval:

- Dashboard manajer memunculkan laporan `acknowledged`, yaitu laporan yang sudah ditanda tangani oleh regu penerima.
- Manajer dapat meninjau laporan melalui route khusus `manajer.reports.show`.
- Saat manajer menekan **Tanda Tangani**, status laporan berubah menjadi `approved`, kolom `approved_by` dan `approved_at` terisi, lalu PDF arsip dicache ke `storage/app/public/reports`.
- Setelah approve berhasil, user diarahkan ke Arsip Laporan dan melihat toast sukses.

Pencarian arsip:

- Endpoint suggestion: `GET /manajer/archive/suggestions?q=...`.
- Dropdown saran mengikuti pola riwayat laporan operasional: request dibatalkan dengan `AbortController`, ada navigasi keyboard, dan dropdown tertutup saat pointer keluar dari area input/dropdown/gap kecil di antaranya.
- Suggestion berperan sebagai pilihan keyword pencarian, bukan link detail laporan. Enter menjalankan pencarian tabel, sedangkan klik suggestion memfilter tabel memakai ID dokumen suggestion.
- Pencarian tanggal mendukung nama bulan penuh dan parsial yang tidak ambigu (`apri`, `janu`, `me`, `jul`). Jika keyword terbaca sebagai tanggal, query arsip langsung fokus ke `report_date`.
- Pencarian mendukung ID dokumen, tanggal, shift, regu, user terkait, payload laporan, serta relasi laporan operasional.
- Filter arsip tersedia untuk tanggal, regu, shift, serta urutan terbaru/terlama. Posisi filter tanggal/regu/shift berada di bawah baris pencarian agar toolbar lebih mudah dipindai.

Optimasi performa:

- Query list dashboard dan arsip memakai kolom ringkas (`select`) agar halaman daftar tidak memuat relasi berat.
- Relasi lengkap baru di-load saat membuka detail laporan atau membuat/download PDF.
- Statistik dashboard dan arsip dicache 60 detik dan dibersihkan saat laporan di-approve atau dihapus.

Responsive:

- Layout manajer memiliki sidebar off-canvas pada mobile dengan backdrop dan tombol toggle di navbar.
- Stats card menjadi 2 kolom pada mobile normal dan 1 kolom pada layar sangat sempit.
- Tab Laporan Masuk dan tabel arsip dapat digeser horizontal saat layar kecil.
- Tombol Logout sidebar memiliki state hover, active, dan focus-visible berbasis warna merah agar respons interaksi lebih jelas.

### 5.8 Dashboard dan Administrasi Admin (`/admin`)

Role `admin` memiliki area khusus untuk administrasi sistem. Area ini digunakan untuk memantau ringkasan sistem, mengelola akun, mengelola master data, memantau arsip, mencatat aktivitas, mengelola backup, dan membuka pusat bantuan.

Menu utama:

1. **Dashboard Sistem** - menampilkan ringkasan status laporan, pengguna, data master, backup, serta akses cepat ke aksi administratif.
2. **Arsip Laporan** - menampilkan laporan operasional dalam format tabel yang konsisten dengan arsip manajer. Admin dapat melihat, download, dan hapus arsip, tetapi tidak melakukan approval laporan.
3. **Log Aktivitas** - menampilkan catatan aktivitas administratif untuk kebutuhan audit.
4. **Kelola Pengguna** - tambah/edit/hapus user, upload tanda tangan PNG, reset password awal, dan toggle status aktif/nonaktif langsung dari tabel.
5. **Data Master** - CRUD data karyawan, unit, truck, dan inventaris dengan modal tambah/edit serta konfirmasi hapus.
6. **Manajemen Backup** - generate backup manual, atur jadwal backup, download, hapus, dan catat permintaan restore.
7. **Pusat Bantuan** - referensi bantuan admin, kontrol sistem, dan form tiket internal.

Catatan akses:

- Route admin dilindungi middleware `auth` dan `EnsureAdminAccess`.
- Admin dapat mengelola data sistem, tetapi tombol persetujuan laporan tidak ditampilkan karena approval final hanya menjadi hak role `manajer`.
- Pesan sukses/error di area admin memakai toast message agar konsisten dengan halaman operasional dan manajer.
- Aksi destruktif seperti hapus user, hapus data master, hapus backup, dan restore backup memakai modal konfirmasi.

### 5.9 Modul Pemeliharaan (`/pemeliharaan`) — Increment 2

Modul ini mendigitalkan dokumen fisik **"Laporan Harian Unit Kerja Pemeliharaan dan Peralatan"** beserta **Daftar Hadir Karyawan**-nya. Diakses oleh role `pemeliharaan` (akun **Kasi Pemeliharaan**) dan dilindungi middleware `role:pemeliharaan`. Berbeda dengan Operasional, laporan dibuat **sekali per hari (Non Shift)** dan **tidak ada serah-terima antar regu**.

Alur status memakai enum `App\Enums\MaintenanceStatus` (di-cast pada `MaintenanceReport`):

| Status | Label UI | Arti |
|---|---|---|
| `draft` | Draft | Tersimpan oleh Kasi, belum dikirim |
| `submitted` | Diserahkan | Dikirim, menunggu persetujuan manajer |
| `approved` | Diarsipkan | Disetujui/ditanda tangani manajer (final) |

> Tidak ada tahap `acknowledged` di sini karena tidak ada serah-terima. Pengesahan bersifat **2 pihak**: Kasi Pemeliharaan (`created_by`) sebagai pembuat dan Manajer (`approved_by`) sebagai penyetuju.

**Dashboard petugas** (`pemeliharaan.index`) menyederhanakan pola Operasional menjadi **dua tab** (tanpa "Laporan Masuk"):

1. **Draft** — laporan `draft` milik Kasi, lengkap dengan tombol Lanjutkan Edit & hapus.
2. **Riwayat Laporan** — daftar laporan yang sudah dikirim/disetujui (kolom Hari menggantikan Shift).

**Form laporan multi-step** (`create`/`edit`) memakai layout & komponen yang sama dengan Operasional, dengan 6 seksi:

| Seksi | Konten |
|---|---|
| Info Umum | Tanggal laporan + nama hari otomatis (tanpa Shift/Group/Jam Kerja) |
| Pekerjaan Utama | 4 baris terikat Group I–IV (`work_type = utama`), unit dari master, status Selesai/Tidak |
| Pekerjaan Prioritas | Baris dinamis (`work_type = prioritas`), mendukung entri unit bebas (mis. "BENGKEL") |
| Kondisi Unit | Dua kelompok (`truck` / `heavy`) dengan penghitung **Ready/Rusak otomatis** real-time |
| Daftar Hadir | Roster karyawan pemeliharaan ter-preload (Nama + Jabatan + "Non Shift"), jam masuk/pulang |
| Pengesahan | Nama Kasi (otomatis dari akun), nama Karu Pemeliharaan & Karu Peralatan (informatif untuk PDF) |

**Persetujuan manajer**: laporan `submitted` muncul pada tab **Pemeliharaan** di dashboard manajer. Manajer membuka detail lewat `manajer.pemeliharaan.show`, lalu menekan setuju (`manajer.pemeliharaan.approve`) untuk mengubah status menjadi `approved` (mengisi `approved_by`/`approved_at`). Tersedia juga download PDF dan hapus arsip dari sisi manajer.

**Integrasi admin**: laporan pemeliharaan ikut tampil di arsip admin dengan aksi lihat (`admin.maintenance-reports.show`), download (`...download`), dan hapus (`...destroy`). Admin tidak melakukan approval.

**Penyatuan master data**: rancangan awal memakai tabel terpisah `maintenance_units`/`maintenance_employees`, tetapi pada implementasi keduanya **disatukan ke `master_units` dan `master_employees`** (migration `2026_06_01_*`). `master_units` diperluas kolom `unit_code`, `brand`, `unit_number`, dan `macro_category` (`truck`/`heavy`); tabel `maintenance_work_items` & `maintenance_unit_conditions` kini mereferensikan `master_unit_id`. Dengan ini admin cukup mengelola satu set master data untuk kedua modul.

**Lain-lain**:
- **ID Dokumen**: format `#MNT-YYYY-NNN` (lihat trait `ResolvesMaintenanceMeta`).
- **Export PDF**: `GET /pemeliharaan/{report}/pdf` (DomPDF, template `pemeliharaan/pdf.blade.php`).
- **Draft kadaluarsa**: `MaintenanceReport::pruneStaleDrafts()` menghapus draft > 3 hari, dijalankan oleh command terjadwal `reports:prune-stale` (harian 01:30) bersama pembersihan draft & saran kapal Operasional.
- **Views**: `resources/views/pemeliharaan/` (`index`, `create`, `edit`, `pdf`, `viewpdf`, `layouts/`, `partials/`).

### 5.10 Modul Safety/K3 (`/report-safety`) — Increment 3

Modul ini mendigitalkan dokumen fisik **"Laporan Harian K3 (Keselamatan & Kesehatan Kerja)"**. Diakses oleh role `safety` (akun **Karu Safety**) dan dilindungi middleware `role:safety`. Seperti Pemeliharaan, laporan dibuat **per hari (Non Shift)** dan **tidak ada serah-terima antar regu**.

Alur status memakai enum `App\Enums\SafetyStatus` (di-cast pada `SafetyReport`):

| Status | Label UI | Arti |
|---|---|---|
| `draft` | Draft | Tersimpan oleh Karu Safety, belum dikirim |
| `submitted` | Diserahkan | Dikirim, menunggu persetujuan manajer |
| `approved` | Diarsipkan | Disetujui/ditanda tangani manajer (final) |

> Tidak ada tahap `acknowledged` (tidak ada serah-terima). Enum `SafetyStatus` menyediakan `label()`, `badgeClass()`, `icon()`, dan transisi FSM eksplisit `canTransitionTo()`.

**Dashboard petugas** (`safety.index`) memakai pola dua tab seperti Pemeliharaan:

1. **Draft** — laporan `draft` milik Karu Safety, lengkap dengan tombol Lanjutkan Edit & hapus.
2. **Riwayat Laporan** — daftar laporan yang sudah dikirim/disetujui (paginasi 10/halaman).

**Form laporan multi-step** (`create`/`edit`) memakai layout & komponen yang sama dengan modul lain, dengan **4 seksi**:

| Seksi | Konten |
|---|---|
| Info Umum | Tanggal laporan + jam kerja (jam masuk/pulang manual, digabung jadi `time_range`) |
| Inspeksi K3 | Accordion **per lokasi** (dari master); tiap lokasi berisi daftar item dengan QTY (hanya untuk item terhitung/`is_countable`), kondisi (`bagus`/`rusak`/`normal`/`tidak_normal`), dan rekomendasi. Badge status "Belum diperiksa / Selesai" per lokasi; lokasi & item dapat ditambah bebas |
| Kegiatan Operasi & Pemeliharaan | Tabel kegiatan (ter-seed default: Gresik Niaga, Golden Rejeki, Pengiriman ke GD Turba, Rental Unit PP&P, Rental TRL PT.KAD, Rental FL OP6 & OP7) dengan kondisi (mis. "Aman"), tindakan, dan keterangan |
| Laporan Kejadian & Lain-lain | Tabel kejadian (uraian, kondisi, tindakan, keterangan) — boleh dikosongkan bila tidak ada kejadian |

**Integritas historis**: setiap baris inspeksi menyimpan `location_name_snapshot` dan `item_name_snapshot`, sehingga laporan lama tetap utuh meski master lokasi/item kemudian diubah atau dihapus.

**Autosave draft**: form memakai concern `AutosavesDraftReports` — laporan yang sedang dikerjakan otomatis tersimpan sebagai Draft saat logout/kehilangan sesi (flag `autosave=1`).

**Persetujuan manajer**: laporan `submitted` muncul pada tab **Safety** di dashboard manajer. Manajer membuka detail lewat `manajer.safety.show`, lalu menyetujui (`manajer.safety.approve`) untuk mengubah status menjadi `approved` (mengisi `approved_by`/`approved_at`); PDF arsip dicache ke `storage/app/public/safety-reports`. Tersedia juga download PDF dan hapus arsip.

**Integrasi admin**: laporan safety ikut tampil di arsip admin dengan aksi lihat (`admin.safety-reports.show`), download (`...download`), dan hapus (`...destroy`). Master data dikelola admin lewat pane **Data Lokasi K3** (`safety_lokasi`) dan **Data Item K3** (`safety_item`) di Data Master.

**Lain-lain**:
- **ID Dokumen**: format `#K3-YYYY-NNN` (lihat trait `ResolvesSafetyMeta`).
- **Export PDF**: `GET /report-safety/{report}/pdf` (DomPDF, template `report-safety/pdf.blade.php`, paper portrait 612×936).
- **Draft kadaluarsa**: `SafetyReport::pruneStaleDrafts()` menghapus draft > 3 hari, selaras dengan Operasional & Pemeliharaan.
- **Master data**: `master_safety_locations`, `master_safety_items`, dan pivot `master_safety_location_items` (dengan `default_qty` per lokasi-item).

---

## 6. Skema Data (Ringkas)

### `daily_reports`
Tabel pusat yang menyimpan satu laporan shift harian.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | PK |
| `user_id`, `created_by` | FK users | Pembuat |
| `report_date` | date | Tanggal laporan |
| `shift` | string | Pagi/Siang/Malam |
| `group_name` | string | Regu pengirim |
| `received_by_group` | string | Regu penerima |
| `received_by_user_id`, `received_at` | FK + timestamp | Penanda tangan |
| `time_range` | string | mis. "07:00-15:00" |
| `status` | string | draft/submitted/acknowledged/approved |
| `approved_by`, `approved_at` | FK + timestamp | Konfirmator |
| `payload` | json | Snapshot full form (untuk restore saat edit) |
| `timestamps` | datetime | |

Index: `[status, report_date]`, `[group_name, received_by_group]`.

### Tabel relasi (semua `cascadeOnDelete` dari `daily_reports`)

- `loading_activities` (1:N) → `loading_timesheets` (1:N) — Muat Kantong + timesheet
- `bulk_loading_activities` (1:N) → `bulk_loading_logs` (1:N, dengan kolom `cob`) — Muat Curah
- `material_activities` (1:1) → `material_items` (1:N) — Bongkar material
- `container_activities` (1:1) → `container_items` (1:N) — Bongkar kontainer
- `turba_activities` (1:1) → `turba_deliveries` (1:N) — Tracking pengiriman (nama tabel legacy)
- `unit_check_logs` (1:N) — Cek unit
- `employee_logs` (1:N, kolom `category` membedakan shift/relief/overtime/op7/replacement/other)

### Tabel master (data referensi)

- `master_units` — kendaraan/forklift
- `master_inventory_items` — inventaris (stock)
- `master_trucks` — truk (plate_number)
- `master_employees` — karyawan (npk, group_name, position)

### `users` (extended)

`username`, `role_id`, `status`, `signature_path`, `group` ditambahkan via migration auth, lalu daftar role dikembangkan menjadi 5 role lewat migration role.

### Tabel Modul Pemeliharaan (Increment 2)

- `maintenance_reports` — induk laporan harian (`report_date`, `day_name`, `status` enum draft/submitted/approved, `created_by`/`submitted_at`, `approved_by`/`approved_at`, `karu_pemeliharaan_name`, `karu_peralatan_name`)
- `maintenance_work_items` (1:N, cascade) — Pekerjaan Utama + Prioritas (`work_type`, `work_group`, `master_unit_id`, `unit_label`, `description`, `assignee`, `is_completed`, `notes`, `sort_order`)
- `maintenance_unit_conditions` (1:N, cascade) — Kondisi Unit per unit (`master_unit_id`, `condition` ready/rusak, `notes`; unik per `[report, unit]`)
- `maintenance_attendances` (1:N, cascade) — Daftar Hadir (`maintenance_employee_id` opsional, snapshot `employee_name`/`position`, `time_in`, `time_out`, `notes`)

> Master armada & roster pemeliharaan **disatukan ke `master_units` dan `master_employees`** (lihat §5.9), sehingga `maintenance_work_items`/`maintenance_unit_conditions` mereferensikan `master_unit_id` dan tabel `maintenance_units`/`maintenance_employees` lama tidak lagi dipakai sebagai master aktif.

### Tabel Modul Safety/K3 (Increment 3)

- `safety_reports` — induk laporan harian K3 (`document_number`, `report_date`, `time_range`, `shift` opsional, `status` enum draft/submitted/approved, `created_by`/`submitted_at`/`reporter_signature_path`, `approved_by`/`approved_at`/`approver_signature_path`; index `[status, report_date]`)
- `safety_inspections` (1:N, cascade) — baris Inspeksi K3 (`location_id`/`item_id` nullable + `location_name_snapshot`/`item_name_snapshot`, `qty`, `condition` enum bagus/rusak/normal/tidak_normal, `recommendation`, `sort_order`)
- `safety_operation_logs` (1:N, cascade) — Kegiatan Operasi & Pemeliharaan (`activity_name`, `condition` teks bebas, `action`, `notes`, `sort_order`)
- `safety_incident_logs` (1:N, cascade) — Laporan Kejadian (`description`, `condition`, `action`, `notes`, `sort_order`)

### Tabel master Safety/K3

- `master_safety_locations` — lokasi inspeksi (`name`, `sort_order`, `is_active`)
- `master_safety_items` — item/objek inspeksi (`name`, `is_countable` = QTY relevan, `is_active`)
- `master_safety_location_items` — pivot lokasi ↔ item dengan `default_qty` & `sort_order` (unik per `[location, item]`)

---

## 7. Route Map

```
GET    /                                  → login
POST   /login                             → authenticate
POST   /logout                            → logout

GET    /report-ops                        → dashboard (Laporan Masuk / Draft / Riwayat)
GET    /report-ops/history/suggestions    → JSON saran pencarian
GET    /report-ops/ship-operations/suggestions → JSON saran kapal aktif Muat Kantong / Muat Curah
GET    /report-ops/create                 → form buat laporan
POST   /report-ops                        → simpan laporan baru (draft / submitted)
GET    /report-ops/{report}               → lihat laporan
GET    /report-ops/{report}/edit          → form edit
PUT    /report-ops/{report}               → update laporan
DELETE /report-ops/{report}               → hapus draft
POST   /report-ops/{report}/sign          → tanda tangani
GET    /report-ops/{report}/pdf           → export PDF
GET    /report-ops/{report}/excel         → export Excel
```

---

Route manajer tambahan:

```
GET    /manajer                           -> dashboard manajer
GET    /manajer/archive                   -> arsip laporan
GET    /manajer/archive/suggestions       -> JSON saran pencarian arsip
GET    /manajer/bantuan                   -> pusat bantuan manajer
GET    /manajer/reports/{report}          -> lihat laporan dari area manajer
POST   /manajer/reports/{report}/approve  -> tanda tangan/approval manajer
GET    /manajer/reports/{report}/download -> download PDF arsip
DELETE /manajer/reports/{report}          -> hapus arsip laporan
```

---

Route admin tambahan:

```
GET    /admin                             -> dashboard admin
GET    /admin/archive                     -> arsip laporan admin
GET    /admin/log                         -> log aktivitas admin
GET    /admin/user-manage                 -> kelola pengguna
GET    /admin/datamaster                  -> data master
GET    /admin/backup                      -> manajemen backup
GET    /admin/help                        -> pusat bantuan admin
GET    /admin/reports/{report}            -> lihat laporan dari area admin
GET    /admin/reports/{report}/download   -> download PDF arsip
DELETE /admin/reports/{report}            -> hapus arsip laporan
POST   /admin/users                       -> tambah user
PUT    /admin/users/{user}                -> update user
PATCH  /admin/users/{user}/status         -> aktif/nonaktif user
DELETE /admin/users/{user}                -> hapus user
POST   /admin/master/employees            -> tambah data karyawan
PUT    /admin/master/employees/{employee} -> update data karyawan
DELETE /admin/master/employees/{employee} -> hapus data karyawan
POST   /admin/master/units                -> tambah data unit
PUT    /admin/master/units/{unit}         -> update data unit
DELETE /admin/master/units/{unit}         -> hapus data unit
POST   /admin/master/trucks               -> tambah data truck
PUT    /admin/master/trucks/{truck}       -> update data truck
DELETE /admin/master/trucks/{truck}       -> hapus data truck
POST   /admin/master/inventories          -> tambah data inventaris
PUT    /admin/master/inventories/{inventory} -> update data inventaris
DELETE /admin/master/inventories/{inventory} -> hapus data inventaris
POST   /admin/backup/generate             -> generate backup manual
PUT    /admin/backup/schedule             -> update jadwal backup
GET    /admin/backup/files/{file}         -> download backup
DELETE /admin/backup/files/{file}         -> hapus backup
POST   /admin/backup/files/{file}/restore -> catat permintaan restore
POST   /admin/help/ticket                 -> kirim tiket bantuan admin
```

---

Route modul Pemeliharaan (Increment 2):

```
# Petugas pemeliharaan (role:pemeliharaan, prefix /pemeliharaan)
GET    /pemeliharaan                       -> dashboard petugas (Draft / Riwayat)
GET    /pemeliharaan/create                -> form buat laporan harian
POST   /pemeliharaan                       -> simpan laporan (draft / submitted)
GET    /pemeliharaan/{report}              -> lihat laporan
GET    /pemeliharaan/{report}/edit         -> form edit
PUT    /pemeliharaan/{report}              -> update laporan
DELETE /pemeliharaan/{report}              -> hapus draft
GET    /pemeliharaan/{report}/pdf          -> export PDF

# Approval manajer (role:manajer)
GET    /manajer/pemeliharaan/{report}          -> lihat laporan pemeliharaan
POST   /manajer/pemeliharaan/{report}/approve  -> setujui (submitted -> approved)
GET    /manajer/pemeliharaan/{report}/download -> download PDF
DELETE /manajer/pemeliharaan/{report}          -> hapus arsip

# Arsip admin (role:admin)
GET    /admin/maintenance-reports/{report}          -> lihat laporan pemeliharaan
GET    /admin/maintenance-reports/{report}/download -> download PDF
DELETE /admin/maintenance-reports/{report}          -> hapus arsip
```

---

Route modul Safety/K3 (Increment 3):

```
# Petugas safety (role:safety, prefix /report-safety)
GET    /report-safety                      -> dashboard petugas (Draft / Riwayat)
GET    /report-safety/create               -> form buat laporan K3
POST   /report-safety                       -> simpan laporan (draft / submitted)
GET    /report-safety/{report}             -> lihat laporan
GET    /report-safety/{report}/edit        -> form edit
PUT    /report-safety/{report}             -> update laporan
DELETE /report-safety/{report}             -> hapus draft
GET    /report-safety/{report}/pdf         -> export PDF

# Approval manajer (role:manajer)
GET    /manajer/safety/{report}            -> lihat laporan K3
POST   /manajer/safety/{report}/approve    -> setujui (submitted -> approved)
GET    /manajer/safety/{report}/download   -> download PDF
DELETE /manajer/safety/{report}            -> hapus arsip

# Arsip admin (role:admin)
GET    /admin/safety-reports/{report}          -> lihat laporan K3
GET    /admin/safety-reports/{report}/download -> download PDF
DELETE /admin/safety-reports/{report}          -> hapus arsip

# Master data K3 (role:admin)
POST   /admin/master/safety-locations             -> tambah lokasi K3
PUT    /admin/master/safety-locations/{location}  -> update lokasi K3
DELETE /admin/master/safety-locations/{location}  -> hapus lokasi K3
POST   /admin/master/safety-items                 -> tambah item K3
PUT    /admin/master/safety-items/{item}          -> update item K3
DELETE /admin/master/safety-items/{item}          -> hapus item K3
```

---

## 8. Design System

### 8.1 Palet Warna (CSS Variables)

Didefinisikan di `:root` pada `resources/views/report-ops/layouts/app.blade.php`. Setiap palet punya variant `-25 / -10 / -5 / -40` untuk opacity.

| Token | Hex | Penggunaan |
|---|---|---|
| `--blue-main` | `#2563EB` | Aksi utama, link, focus state |
| `--cyan-main` | `#0EA5E9` | Shift Pagi, status Dikonfirmasi |
| `--orange-main` | `#F7931E` | Shift Siang, status Diserahkan, tombol Lihat |
| `--red-main` | `#D20000` | Aksi hapus, validation error |
| `--success` | `#10B981` | Status Ditanda Tangani |
| `--dark-main` | `#0F172A` | Teks utama |
| `--dark-secondary` | `#334155` | Teks sekunder, status Draft |
| `--muted` | `#94A3B8` | Placeholder, label muted |
| `--smooth-border` | `#E2E8F0` | Border lembut |
| `--divider` | `#CBD5E1` | Divider/border |
| `--main-bg` | `#F8FAFC` | Background card |
| `--white` | `#FFFFFF` | Surface |

**Dark mode** tersedia: `body.dark-mode` meng-override semua token (background gelap, teks terang, warna utama lebih cerah).

### 8.2 Mapping Warna Semantik

**Shift:**
- Pagi → cyan (`--cyan-main`)
- Siang → orange (`--orange-main`)
- Malam → blue (`--blue-main`)

**Status:**
- Draft → abu (`--dark-secondary`)
- Diserahkan (submitted) → orange (`--orange-main`)
- Ditanda Tangani (acknowledged) → hijau (`--success`)
- Dikonfirmasi (approved) → cyan (`--cyan-main`)

**Timesheet variant:**
- Pengiriman / Laporan Harian → biru (`.deliv`)
- Pemuatan → orange (`.load`)

### 8.3 Typografi

- Font: **Poppins** (300, 400, 500, 600, 700)
- Skala: utility class `fsize-9` / `fsize-10` / `fsize-11` / `fsize-12` / `fsize-14` / `fsize-16` / `fsize-20`
- Bobot: `fw-300` / `fw-400` / `fw-500` / `fw-600` / `fw-700`

### 8.4 Komponen Reusable

| Komponen | Selector | Fungsi |
|---|---|---|
| `.btn-new` | Tombol primer kanan-atas content header | Buat laporan, Simpan Draft |
| `.btn-form` | Tombol navigasi step (back/next/finish) | Lanjut, Kembali, Selesai |
| `.btn-add-activity` | Tombol "+ Tambah" di timesheet | Tambah/edit baris aktivitas |
| `.btn-tambah-baris` | Tombol "+ Tambah Baris" di tabel | Tambah row tabel |
| `.btn-trash-row` / `.btn-trash` | Tombol hapus | Hapus row / item |
| `.btn-edit` | Tombol edit di timeline-item | Edit aktivitas timesheet |
| `.shift.pagi/sore/malam` | Badge shift | Indikator shift |
| `.status.draft/submit/approve/confirm` | Badge status | Indikator status laporan |
| `.timesheet-card` | Card timesheet | Wrapper timesheet pengiriman/pemuatan/COB |
| `.timeline-item` | Item timeline | Aktivitas yang sudah disubmit |
| `.table-input` | Tabel input dengan body + head + tambah baris | Tabel data dinamis |
| `.form-card` | Card kuantitas (Pengiriman/Pemuatan/Kerusakan) | Card input numerik |
| `.tab-form` / `.list-form-tab` | Tab utama form | Navigasi 7 step |
| `.tab-sections` | Sub-tab dalam step | Sub-navigasi (mis. di step 7) |
| `.modal-overlay` + `.pop-up` | Modal | Konfirmasi sign / hapus / edit |
| `.history-suggest-dropdown` | Dropdown saran pencarian | Live search di riwayat |
| `.history-table` | Tabel riwayat laporan | Tabel dengan kolom Aksi sticky |
| `.stats-row` / `.stat-card` | Card statistik manajer | Ringkasan total, pending, harian, bulanan |
| `.report-tabs` / `.report-tab` | Tab laporan masuk manajer | Filter divisi dan horizontal scroll di mobile |
| `.archive-suggest-dropdown` | Dropdown saran arsip | Live search arsip laporan manajer |
| `.toast-message` / `.auth-toast` | Notifikasi toast | Pesan sukses/error dengan gaya liquid glass mengikuti box login |
| `.kss-date-trigger` / `.kss-date-popover` | Date/time picker custom | Input tanggal, jam, dan datetime dengan format visual konsisten |
| `.modal-overlay` / `.modal-box` | Modal admin/manajer | Konfirmasi aksi sensitif dan form tambah/edit data |
| `.admin-pagination` | Pagination admin | Navigasi halaman tabel admin mengikuti gaya riwayat laporan |

### 8.5 Icon

**Flaticon UICons 2.6.0** via CDN. Tiga varian:

- `fi fi-rr-*` — Regular Rounded
- `fi fi-br-*` — Bold Rounded
- `fi fi-sr-*` — Solid Rounded

### 8.6 Responsive Breakpoints

Media query global di `layouts/app.blade.php` (dan override di create/edit):

| Range | Target | Penyesuaian utama |
|---|---|---|
| `> 1024px` | Laptop/Desktop | Timesheet 2 kolom, tabel riwayat lengkap, navbar penuh |
| `≤ 1024px` | Tablet | `.p-content { padding: 0 40px }`, timesheet jadi 1 kolom |
| `≤ 768px` | Mobile | Content header stack vertikal, tombol full-width, form cards min-width 220-280px |
| `≤ 480px` | Small mobile | Form tabs icon-only, label disembunyikan, p-content padding 12px |

Tambahan responsive untuk layout manajer:

- `max-width: 900px`: sidebar menjadi off-canvas dengan backdrop dan tombol toggle di navbar.
- `max-width: 560px`: stats card menjadi 2 kolom, tab laporan masuk bisa digeser horizontal, aksi laporan dibuat full-width.
- `max-width: 360px`: stats card kembali 1 kolom agar teks dan angka tetap terbaca.

### 8.7 Tabel Riwayat (`.history-table`)

Layout flexbox dengan kolom fixed-width + Aksi dipush ke kanan via `margin-left: auto`:

| Kolom | Lebar |
|---|---|
| No | 40px |
| Info Dokumen | 170px fix |
| Tanggal Laporan | 195-210px |
| Shift | 115-130px (nowrap) |
| Group Penerima | 110px fix |
| Status | 140-150px (nowrap) |
| Aksi | 215px (right-aligned, `margin-left: auto`) |

Min-width tabel: 960px. Wrapper `.table-responsive-wrapper` menyediakan scroll horizontal saat layar lebih sempit.

---

## 9. Detail JavaScript Penting

Semua logika form dieksekusi di blok `@push('scripts')` pada `create.blade.php` dan `edit.blade.php`. Fungsi-fungsi kunci:

| Fungsi | Tugas |
|---|---|
| `currentWorkTimes()` | Parse `time_range` Info Umum → `{timeIn, timeOut}` |
| `applyShiftTimesToRow(row)` | Set time_in/time_out berdasarkan jam kerja, kosongkan jika status absen |
| `applyShiftTimesToEmployeeRows()` | Apply ke semua row Karyawan Shift + OP.7 |
| `applyOp7ForkliftDefaults(row, i)` | Isi forklift+area dari mapping OP.7 |
| `op7RowHtml(emp, i)` / `employeeShiftRowHtml(emp, i)` | Template HTML baris karyawan |
| `renderOp7Rows(group)` / `renderEmployeeShiftRows(group)` | Render full ulang baris saat group berubah |
| `addTableRow(button)` | Clone last row + clear values + apply defaults |
| `addTimesheetInput(button)` | Validate row → hide + render timeline-item → buat blank row baru |
| `renderTimesheetTimelineItem(row)` | Render item kronologis (dengan tombol edit & trash); replace in-place jika existing |
| `startEditTimesheetTimelineItem(button)` | Re-show row tersembunyi → highlight as `.is-editing` |
| `removeTimesheetTimelineItem(button)` | Hapus row + item |
| `syncPayload()` | Serialize semua input form → kolom hidden `formPayload` (JSON) |
| `restoreSavedPayload()` | Restore data tersimpan ke form (edit mode) |
| `syncTimeRangeWithShift()` | Auto-fill jam kerja berdasarkan shift |

---

Layout manajer (`resources/views/manajer/layouts/app.blade.php`) memiliki JavaScript ringan untuk:

- membuka/menutup sidebar mobile dan backdrop;
- menghapus loading spinner setelah halaman selesai dimuat;
- menampilkan dan menutup toast message;
- membuka/menutup modal konfirmasi approval/hapus;
- filter tab laporan masuk berdasarkan divisi.

Arsip manajer (`resources/views/manajer/archive.blade.php`) memiliki JavaScript khusus untuk:

- filter baris di halaman berjalan tanpa reload;
- live suggestion arsip dengan debounce dan `AbortController`;
- navigasi keyboard pada dropdown suggestion;
- submit pencarian ke server saat Enter agar hasil lintas pagination muncul di tabel;
- klik suggestion sebagai pencarian berdasarkan ID dokumen, bukan membuka detail laporan;
- menutup dropdown saat pointer keluar dari input, dropdown, dan safe gap kecil di antaranya;
- auto-submit filter tanggal, regu, shift, dan urutan.

Layout admin (`resources/views/admin/layouts/app.blade.php`) memiliki JavaScript untuk:

- membuka/menutup modal form dan modal konfirmasi;
- menampilkan toast sukses/error dari session flash dan validasi;
- menjalankan aksi konfirmasi berbasis atribut `data-confirm-*`;
- menjaga logout admin tetap submit langsung tanpa modal konfirmasi;
- mengaktifkan interaksi sidebar, hover, active, dan submenu data master.

Data master admin (`resources/views/admin/datamaster.blade.php`) memiliki pencarian otomatis:

- input search memakai debounce agar request tidak berjalan pada setiap huruf;
- tombol Cari dihilangkan karena submit dilakukan setelah user berhenti mengetik;
- Enter tetap dapat dipakai untuk submit langsung;
- pane aktif tetap dipertahankan lewat query `pane`.

Komponen date/time picker custom (`resources/views/components/kss-datetime-picker.blade.php`) dipakai pada filter admin/manajer, jadwal backup, serta input datetime Muat Kantong dan Muat Curah. Komponen ini mendukung format 24 jam, tombol "Hari ini", tombol "Hapus", trigger custom yang seukuran input lain, dan event `kss-picker:advance` untuk navigasi keyboard ke input berikutnya.

---

## 10. Setup Lokal

```bash
# Pertama kali
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build

# Development (server + queue + log + vite)
composer dev

# Atau manual
php artisan serve
npm run dev
```

Default URL: `http://localhost:8000` → diarahkan ke halaman login.

---

## 11. Konvensi & Catatan Pengembangan

- **Bahasa UI**: Bahasa Indonesia (label, tombol, pesan validasi, pesan empty state).
- **Locale tanggal**: Indonesian (`Carbon::parse(...)->locale('id')->translatedFormat('d F Y')`) untuk display.
- **ID Dokumen format**: `#OPS-YYYY-NNN` (NNN = `id` laporan padded 3 digit).
- **Payload JSON**: kolom `payload` di `daily_reports` menyimpan snapshot lengkap form. Berguna untuk restore di mode edit tanpa harus rebuild dari tabel-tabel relasi.
- **Submit dua jenis**: tombol header (`#btnSaveDraft`) → status `draft`; tombol Selesai (modal) → status `submitted`.
- **No bootstrap JS modal**: modal custom dengan class `.modal-overlay.show` + handler JS sendiri (bukan Bootstrap Modal).
- **Pencarian server-side**: gunakan helper `applyHistorySearch()` di `ReportOpsController` agar konsisten antara index page dan endpoint suggestion.
- **Area manajer terpisah**: gunakan route `manajer.*` untuk review/download/delete/approve agar role manajer tidak perlu membuka route divisi.
- **Status arsip manajer**: arsip menampilkan laporan berstatus `submitted`, `acknowledged`, dan `approved`; label user-facing tidak memakai istilah "diarsipkan".
- **Performa list manajer**: gunakan select kolom ringkas untuk daftar dan load relasi penuh hanya saat detail/PDF.
- **Dokumen landasan teori**: bahan teori, metode, dan logika implementasi untuk skripsi tersedia di [`LANDASAN_TEORI_SKRIPSI.md`](LANDASAN_TEORI_SKRIPSI.md).

---

## 12. Pembaruan Implementasi Terbaru

Catatan detail pembaruan terbaru disimpan di [`PEMBARUAN_IMPLEMENTASI.md`](PEMBARUAN_IMPLEMENTASI.md).

Ringkasan pembaruan:

- Role sistem dikembangkan menjadi `admin`, `manajer`, `operasional`, `pemeliharaan`, dan `safety`.
- Step 5 tampil sebagai `Tracking`, sementara nama tabel/model legacy `turba` tetap dipertahankan.
- Input angka dibuat non-negatif di frontend dan backend, termasuk pencegahan perubahan nilai akibat scroll mouse.
- Info Umum otomatis mengisi Shift dan Jam Kerja berdasarkan jam WITA pada form create.
- Cek Unit memakai kondisi diserahkan laporan sebelumnya sebagai default kondisi terima.
- Kondisi diserahkan default mengikuti kondisi terima, tetapi tetap bisa diubah manual.
- Group penerima tidak boleh sama dengan group pengirim saat submit final.
- Ship operation suggestion untuk Muat Kantong dan Muat Curah dapat dipakai lintas shift, dapat ditandai selesai, dan otomatis dihapus jika tidak digunakan lebih dari 3 hari.
- Draft laporan otomatis dihapus jika lebih dari 3 hari tidak dilanjutkan/disimpan ulang.
- Error login tampil sebagai toast animatif dari atas-tengah layar, bukan alert inline.
- Dropdown suggestion kapal dan pencarian dibuat lebih stabil saat mouse keluar/masuk area input.
- Pencarian riwayat petugas dan arsip manajer sekarang mendukung hasil lintas pagination, suggestion sebagai keyword tabel, serta nama bulan parsial yang tidak ambigu.
- Reminder draft di dashboard diberi animasi agar lebih menarik perhatian petugas.
- Modul manajer kini memiliki dashboard, arsip laporan, pencarian arsip, approval final, toast/loading, dan pusat bantuan.
- Akses manajer dibatasi ke halaman manajer dan dicegah masuk ke halaman divisi operasional.
- Tampilan manajer dibuat mobile responsive: sidebar off-canvas, stats card adaptif, tab laporan horizontal scroll.
- Toast sukses/error memakai gaya liquid glass mengikuti box login, dan tombol logout manajer memiliki hover/active/focus state.
- Query list manajer diringankan dan statistik dicache singkat agar rendering halaman lebih cepat.
- Modul admin aktif dengan dashboard, arsip, log aktivitas, kelola user, data master, backup, pusat bantuan, toast, modal konfirmasi, upload tanda tangan PNG, dan toggle status user.
- Input tanggal Info Umum kembali memakai native HTML date yang otomatis terisi tanggal hari ini dan tetap bisa diganti. Input datetime Muat Kantong/Muat Curah memakai komponen custom 24 jam.
- Pencarian data master admin memakai debounce tanpa tombol Cari.
- Bahan landasan teori skripsi tersedia di [`LANDASAN_TEORI_SKRIPSI.md`](LANDASAN_TEORI_SKRIPSI.md).
- **Increment 2 — Modul Pemeliharaan selesai dibangun**: dashboard petugas (2 tab Draft/Riwayat), form laporan harian Non Shift (Info Umum, Pekerjaan Utama/Prioritas, Kondisi Unit dengan penghitung Ready/Rusak otomatis, Daftar Hadir, Pengesahan), alur 2 pihak `draft → submitted → approved`, ID dokumen `#MNT-YYYY-NNN`, dan export PDF.
- Approval laporan pemeliharaan tersedia di dashboard manajer (tab Pemeliharaan) dan arsipnya ikut tampil di area admin.
- Master armada & roster pemeliharaan disatukan ke `master_units`/`master_employees` sehingga admin mengelola satu set data master untuk kedua modul.
- Draft pemeliharaan kadaluarsa (> 3 hari) ikut dibersihkan oleh command terjadwal `reports:prune-stale`.
- Rincian perancangan Increment 2 didokumentasikan di [`PERANCANGAN_MODUL_PEMELIHARAAN.md`](PERANCANGAN_MODUL_PEMELIHARAAN.md).
- **Increment 3 — Modul Safety/K3 selesai dibangun**: dashboard petugas safety (2 tab Draft/Riwayat), form laporan harian K3 (Info Umum, Inspeksi K3 berbasis lokasi + item dengan snapshot nama, Kegiatan Operasi & Pemeliharaan, Laporan Kejadian), alur 2 pihak `draft → submitted → approved`, ID dokumen `#K3-YYYY-NNN`, autosave draft, dan export PDF.
- Approval laporan safety tersedia di dashboard manajer (tab Safety) dan arsipnya ikut tampil di area admin.
- Master data K3 (Lokasi & Item) dikelola admin lewat pane Data Lokasi K3 dan Data Item K3 di Data Master.
- Draft safety kadaluarsa (> 3 hari) dibersihkan via `SafetyReport::pruneStaleDrafts()`, selaras Operasional & Pemeliharaan.
- Rincian perancangan Increment 3 didokumentasikan di [`PERANCANGAN_MODUL_SAFETY.md`](PERANCANGAN_MODUL_SAFETY.md).
- Test fitur berada di `tests/Feature/OpsFlowTest.php`; hasil terakhir `php artisan test` (2026-06-14): `39 tests`, `315 assertions` — `38` lulus, `1` gagal pada test master-unit operasional karena keterbatasan fungsi `CONCAT` di SQLite in-memory (tidak terkait modul Safety).
