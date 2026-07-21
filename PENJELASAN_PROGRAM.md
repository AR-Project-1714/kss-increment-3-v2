# Penjelasan Program — KSS Report v2

Dokumen ini menjelaskan cara kerja aplikasi dari sisi arsitektur: fungsi-fungsi
di **Controller**, peran **Model**, **Enum**, **trait (Concerns)**, alur data,
serta **alasan pemilihan MySQL (bukan PostgreSQL)** dan **SQL relasional (bukan
NoSQL)**.

---

## 1. Gambaran Umum

KSS Report v2 adalah **sistem pelaporan harian** untuk operasional pelabuhan /
bongkar-muat. Aplikasi menangani tiga divisi laporan utama plus alur persetujuan
bertingkat:

- **Operasional** — laporan harian regu: aktivitas bongkar/muat (loading), muat
  curah (bulk), material, kontainer, turba, cek unit, dan log karyawan.
- **Pemeliharaan** — laporan kondisi & perbaikan unit/armada beserta absensi
  teknisi.
- **Safety / K3** — inspeksi keselamatan, log operasi, dan log insiden.

Alur status laporan (enum [`ReportStatus`](app/Enums/ReportStatus.php)):

```
Draft → Submitted (Menunggu TTD) → Acknowledged (Menunggu Approval) → Approved (Disetujui)
```

Lima peran pengguna ([`Role`](app/Models/Role.php)): `admin`, `manajer`,
`operasional`, `pemeliharaan`, `safety`. Route dipetakan per peran di
[`routes/web.php`](routes/web.php), masing-masing mendarat di dashboard sendiri
(`Role::homeRoute`).

---

## 2. Controller & Fungsinya

### `LoginV2Controller`
[`app/Http/Controllers/LoginV2Controller.php`](app/Http/Controllers/LoginV2Controller.php)

Menangani autentikasi. Fungsi kunci:

- `index()` — tampilkan halaman login (atau redirect bila sudah login).
- `authenticate()` — validasi kredensial, terapkan rate limit, login via
  `Auth::attempt` (mendukung login pakai **username _atau_ email**), regenerasi
  sesi, tolak akun nonaktif, catat event keamanan.
- `logout()` — hancurkan sesi & token.
- `redirectBasedOnRole()` — arahkan user ke dashboard sesuai peran.
- Helper rate-limit: `ensureIsNotRateLimited`, `throttleKey`, `ipThrottleKey`,
  `recordLoginLockoutOnce`, `recordLoginSecurityEvent`.

### `AdminV2Controller`
[`app/Http/Controllers/AdminV2Controller.php`](app/Http/Controllers/AdminV2Controller.php)

Controller terbesar — pusat kendali admin. Kelompok fungsinya:

- **Dashboard & arsip:** `index`, `archive`, `archiveExport`,
  `archiveSuggestions` — statistik dan arsip lintas divisi.
- **Log aktivitas:** `log`, `logExport` — menampilkan/ekspor `admin_activity_logs`.
- **Manajemen user:** `userManage`, `storeUser`, `updateUser`,
  `toggleUserStatus`, `destroyUser` — CRUD akun dengan validasi
  (`validateUser`), proteksi admin terakhir (`isLastActiveAdmin`), dan upload
  tanda tangan (`storeSignature`).
- **Data master:** CRUD untuk pegawai, unit, truk, inventaris, item lingkungan,
  lokasi & item safety (`storeEmployee`, `storeUnit`, `storeTruck`,
  `storeInventory`, `storeEnvironment`, `storeSafetyLocation`, `storeSafetyItem`,
  dan pasangan `update*`/`destroy*`-nya).
- **Laporan lintas divisi:** `showReport`, `downloadReport`, `destroyReport`
  (plus varian `*MaintenanceReport` dan `*SafetyReport`).
- **Backup:** `backup`, `generateBackup`, `annualBackup`, `updateBackupSchedule`,
  `downloadBackup`, `destroyBackup`, `restoreBackup` (restore sengaja hanya
  mencatat permintaan, tidak menimpa DB).
- **Bantuan:** `help`.

### `ManajerController`
[`app/Http/Controllers/ManajerController.php`](app/Http/Controllers/ManajerController.php)

Dashboard manajer & **persetujuan** laporan (tahap akhir alur):

- Operasional: `index`, `archive`, `archiveExport`, `archiveSuggestions`,
  `show`, `approve`, `download`, `destroy`.
- Pemeliharaan: `showMaintenance`, `approveMaintenance`, `downloadMaintenance`,
  `destroyMaintenance`.
- Safety: `showSafety`, `approveSafety`, `downloadSafety`, `destroySafety`.
- Helper: statistik dashboard (`dashboardStats`, `archiveStats`), **cache** PDF
  yang sudah disetujui (`cacheApprovedPdf`, `cacheApprovedMaintenancePdf`,
  `cacheApprovedSafetyPdf`), invalidasi cache statistik, dan
  `authorizeManagementAccess`.

### `ReportOpsController`
[`app/Http/Controllers/ReportOpsController.php`](app/Http/Controllers/ReportOpsController.php)

Inti laporan operasional harian (dipakai peran operasional):

- `index` — tiga tab: **Laporan** (masuk untuk ditandatangani), **Riwayat**
  (dibuat regu sendiri), **Diterima** (dari regu lain), dengan pencarian +
  paginasi.
- `create`, `store`, `show`, `edit`, `update`, `destroy` — CRUD laporan.
- `sign` — proses tanda tangan/serah terima antar regu.
- `extendDraft`, `pruneStaleDraftReports` — kelola masa hidup draft (auto-hapus
  draft > 3 hari, lihat `DailyReport::DRAFT_TTL_DAYS`).
- `exportPdf` (DomPDF), `exportExcel` (PhpSpreadsheet).
- `*Suggestions` — autocomplete pencarian untuk riwayat, diterima, dan operasi
  kapal.
- Memakai **cache master data** (TTL 24 jam) agar dropdown pegawai/unit/truk
  cepat.

### `ReportMaintenanceController` & `ReportSafetyController`
Struktur serupa `ReportOps` tetapi untuk divisi pemeliharaan dan safety:
`index/history`, `create`, `store`, `show`, `edit`, `update`, `destroy`,
`extendDraft`, `exportPdf`.

### Trait Bersama — `app/Http/Controllers/Concerns/`
Logika dipakai-ulang antar controller (komposisi, bukan pewarisan):

- **`SearchesReports`** — bangun query pencarian `LIKE` + pola tanggal.
- **`ResolvesReportMeta` / `ResolvesMaintenanceMeta` / `ResolvesSafetyMeta`** —
  metadata laporan: label shift/status, nomor dokumen, nama file ekspor, eager-load
  relasi.
- **`BuildsDivisionArchive`** — filter & paginasi arsip lintas divisi + ekspor.
- **`BuildsExportSpreadsheet`** — merakit file Excel dengan header & konteks.
- **`AutosavesDraftReports`** — deteksi request autosave dan balas JSON, sehingga
  form panjang bisa tersimpan otomatis sebagai draft.

---

## 3. Model & Relasi

Model dikelompokkan sesuai domain. Semua memakai Eloquent, `$guarded = ['id']`,
dan `casts()` untuk tipe data (tanggal, enum, JSON).

**Inti / Autentikasi**
- [`User`](app/Models/User.php) — akun; relasi `role()`, cast `password=hashed`,
  helper `jobTitle()` (mis. "Kepala Regu A", "Kasi Pemeliharaan", "Karu Safety").
- [`Role`](app/Models/Role.php) — peran + helper statis (`normalize`,
  `homeRoute`, `displayName`, `hasManagementAccess`).
- [`AdminActivityLog`](app/Models/AdminActivityLog.php) — audit trail, `properties`
  di-cast array.

**Operasional**
- [`DailyReport`](app/Models/DailyReport.php) — laporan induk; kolom `payload`
  (JSON) + banyak relasi anak: `loadingActivities`, `bulkLoadingActivities`,
  `materialActivity`, `containerActivity`, `turbaActivity`, `unitCheckLogs`,
  `employeeLogs`, serta relasi user `creator/receiver/approver`. Punya
  `pruneStaleDrafts()`.
- Detail aktivitas: `LoadingActivity`, `LoadingTimesheet`, `BulkLoadingActivity`,
  `BulkLoadingLog`, `MaterialActivity`, `MaterialItem`, `ContainerActivity`,
  `ContainerItem`, `TurbaActivity`, `TurbaDelivery`, `UnitCheckLog`,
  `EmployeeLog`, `ShipOperation`.

**Pemeliharaan**
- `MaintenanceReport`, `MaintenanceWorkItem`, `MaintenanceUnitCondition`,
  `MaintenanceAttendance`.

**Safety / K3**
- `SafetyReport`, `SafetyInspection`, `SafetyOperationLog`, `SafetyIncidentLog`.

**Data Master** (referensi/dropdown)
- `MasterEmployee`, `MasterUnit`, `MasterTruck`, `MasterInventoryItem`,
  `MasterEnvironmentItem`, `MasterSafetyLocation`, `MasterSafetyItem`.
- Trait `Concerns/InvalidatesMasterDataCache` — otomatis kosongkan cache saat
  master data berubah.

**Enum** (`app/Enums/`)
- `ReportStatus`, `MaintenanceStatus`, `SafetyStatus` — status laporan + method
  `label()` untuk teks Indonesia yang ramah pengguna.

Pola relasi induk→anak (`hasMany`/`hasOne`) mencerminkan struktur berjenjang:
satu laporan harian memiliki banyak baris aktivitas, log, dan detail.

---

## 4. Middleware, Routing, & Tugas Terjadwal

- **`EnsureRole`** ([app/Http/Middleware/EnsureRole.php](app/Http/Middleware/EnsureRole.php))
  — kunci route per peran (allow-list & deny-list).
- **Routing** — grup `guest` (login), `auth` + `role:` untuk tiap divisi &
  manajemen.
- **Terjadwal** ([routes/console.php](routes/console.php)):
  - `reports:prune-stale` tiap hari 01:30 — hapus draft & saran kadaluarsa.
  - `backup:run` mengikuti jadwal yang disimpan admin
    (`admin-backups/schedule.json`).

---

## 5. Mengapa MySQL, Bukan PostgreSQL?

Konfigurasi mendukung banyak driver, tetapi produksi memakai **MySQL/MariaDB**
([`config/database.php`](config/database.php)). Alasannya:

1. **Ekosistem hosting Indonesia.** Mayoritas shared hosting/VPS lokal (cPanel,
   Laragon — yang dipakai proyek ini) hadir dengan MySQL/MariaDB out-of-the-box,
   plus **phpMyAdmin** untuk backup/restore manual — cocok dengan strategi
   restore manual di aplikasi ini.
2. **Kebutuhan cukup terlayani.** Beban aplikasi ini adalah OLTP sederhana (CRUD
   laporan + join relasi). Fitur canggih khas PostgreSQL (tipe data JSON/GIS
   lanjutan, CTE rekursif berat, ekstensi) tidak dibutuhkan; kolom JSON pun sudah
   tersedia di MySQL 8 / MariaDB dan dipakai lewat cast `payload => array`.
3. **Operasional lebih ringan.** Tuning, replikasi, dan pemeliharaan MySQL lebih
   familiar bagi admin lokal; kurva belajar lebih landai.
4. **Kompatibilitas Laravel penuh.** Eloquent, migrasi, `utf8mb4`, mode `strict`,
   dan opsi SSL (`MYSQL_ATTR_SSL_CA`) semuanya didukung mulus.
5. **Portabilitas.** MariaDB (drop-in MySQL) memberi fleksibilitas lisensi &
   deployment tanpa mengubah kode.

> Catatan: berkat abstraksi Eloquent, pindah ke PostgreSQL hanya soal mengganti
> `DB_CONNECTION` — tidak ada SQL vendor-specific yang mengunci aplikasi. MySQL
> dipilih karena **paling praktis untuk konteks deployment**, bukan karena
> keterbatasan teknis.

---

## 6. Mengapa SQL Relasional, Bukan NoSQL?

Meski NoSQL lebih fleksibel untuk skema yang berubah-ubah, data aplikasi ini
sangat **relasional dan butuh konsistensi kuat**, sehingga SQL lebih tepat:

1. **Relasi antar-entitas padat.** User → Role, DailyReport → puluhan tabel anak
   (aktivitas, log, absensi, kondisi unit), laporan → approver. `JOIN` dan
   foreign key adalah operasi sehari-hari — inilah yang paling efisien di basis
   data relasional, dan mahal/manual di NoSQL.
2. **Integritas data (ACID).** Alur approval (Draft → Submitted → Approved),
   serah-terima antar regu, dan pencatatan audit menuntut transaksi atomik dan
   konsistensi kuat. Model *eventual consistency* NoSQL berisiko memunculkan
   laporan setengah-jadi atau status yang tak sinkron.
3. **Skema stabil & terkontrol.** Struktur laporan sudah jelas dan diatur lewat
   **migrasi** (`database/migrations`). Keunggulan "schema-less" NoSQL justru
   jadi beban: validasi bentuk data harus dipindah ke aplikasi, rawan
   inkonsistensi.
4. **Butuh fleksibilitas? Sudah tercakup.** Bagian data yang memang dinamis
   disimpan sebagai **kolom JSON** (`payload` di `DailyReport`) di dalam MySQL —
   mendapat fleksibilitas dokumen tanpa mengorbankan relasi & transaksi. Ini pola
   "SQL + JSON" yang memberi keduanya sekaligus.
5. **Pelaporan & agregasi.** Statistik dashboard, arsip, dan ekspor Excel/PDF
   mengandalkan agregasi (`GROUP BY`, `COUNT`, filter tanggal) yang matang di
   SQL.
6. **Tooling & dukungan Laravel.** Eloquent, relasi, migrasi, dan validasi
   dirancang untuk relasional; memakai NoSQL berarti kehilangan sebagian besar
   kenyamanan ini.

> Kesimpulan: kebutuhan utama aplikasi adalah **konsistensi, relasi, dan
> pelaporan** — ranah unggulan SQL. Fleksibilitas skema yang biasanya jadi alasan
> memilih NoSQL sudah dijawab oleh kolom JSON MySQL, tanpa mengorbankan
> integritas data.

---

## 7. Ringkasan Alur Data

```
Petugas Operasional  → buat DailyReport (Draft, autosave)
                     → submit → Submitted (Menunggu TTD)
Regu penerima        → sign  → Acknowledged (Menunggu Approval)
Manajer              → approve → Approved  → PDF di-cache
Admin                → pantau arsip, log aktivitas, kelola user & master data, backup
```

Divisi Pemeliharaan dan Safety mengikuti pola pembuatan → submit → persetujuan
manajer yang serupa, memakai model & controller masing-masing.
