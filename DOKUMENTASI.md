# Sistem Laporan Operasional KSS

Dokumentasi proyek Sistem Laporan Operasional KSS — aplikasi web internal untuk pencatatan, pelaporan, dan distribusi laporan shift harian kegiatan operasional pelabuhan/bongkar muat.

---

## 1. Gambaran Umum

Aplikasi ini menggantikan proses pelaporan shift harian yang sebelumnya manual menjadi sistem digital terstruktur. Setiap regu (Group) yang bertugas mengisi laporan multi-step yang merangkum aktivitas muat kantong, muat curah, bongkar, tracking pengiriman, cek unit kendaraan, hingga daftar karyawan. Laporan kemudian diserahkan ke regu penerima untuk ditandatangani secara digital, dan akhirnya bisa diekspor menjadi PDF atau Excel.

**Status alur laporan:**

| Status | Arti |
|---|---|
| `draft` | Laporan masih disimpan oleh pembuat, belum dikirim |
| `submitted` | Diserahkan ke regu penerima, menunggu tanda tangan |
| `acknowledged` | Sudah ditanda tangani oleh regu penerima |
| `approved` | Dikonfirmasi (final) |

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
    └── User.php                    # User + relasi role
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

1. **Dashboard** - menampilkan card statistik dan daftar laporan masuk dari divisi. Saat ini divisi aktif adalah Operasional; Pemeliharaan dan Safety disiapkan sebagai tab pengembangan berikutnya.
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
- Test fitur terbaru berada di `tests/Feature/OpsFlowTest.php`; hasil terakhir `php artisan test --filter=OpsFlowTest` lulus `16 tests`, `135 assertions`.
