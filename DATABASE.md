# Dokumentasi Database — KSS Report (Increment 3)

Dokumen ini menjelaskan struktur database lengkap aplikasi pelaporan KSS yang
mencakup tiga modul utama: **Operasional**, **Pemeliharaan**, dan **Safety (K3)**,
ditambah tabel autentikasi/otorisasi, master data, dan tabel bawaan Laravel.

Skema disusun berdasarkan kondisi **akhir** setelah seluruh migration dijalankan
(termasuk migration penggabungan `maintenance_units`/`maintenance_employees` ke
dalam master operasional, sehingga kedua tabel legacy tersebut **sudah tidak ada**).

- Engine: MySQL/MariaDB (default Laravel) atau SQLite untuk dev.
- Semua tabel memakai kolom `id` `BIGINT UNSIGNED AUTO_INCREMENT` sebagai primary key kecuali disebutkan lain.
- `timestamps()` menghasilkan dua kolom: `created_at` dan `updated_at` bertipe `TIMESTAMP NULL`.
- Tipe `string` default Laravel = `VARCHAR(255)`.
- FK = Foreign Key.

---

## Daftar Tabel

### Autentikasi & Otorisasi
1. [users](#1-users)
2. [roles](#2-roles)
3. [password_reset_tokens](#3-password_reset_tokens)
4. [sessions](#4-sessions)

### Master Data Umum
5. [master_units](#5-master_units)
6. [master_employees](#6-master_employees)
7. [master_inventory_items](#7-master_inventory_items)
8. [master_trucks](#8-master_trucks)
9. [admin_activity_logs](#9-admin_activity_logs)

### Modul Operasional
10. [daily_reports](#10-daily_reports)
11. [ship_operations](#11-ship_operations)
12. [loading_activities](#12-loading_activities)
13. [loading_timesheets](#13-loading_timesheets)
14. [bulk_loading_activities](#14-bulk_loading_activities)
15. [bulk_loading_logs](#15-bulk_loading_logs)
16. [material_activities](#16-material_activities)
17. [material_items](#17-material_items)
18. [container_activities](#18-container_activities)
19. [container_items](#19-container_items)
20. [turba_activities](#20-turba_activities)
21. [turba_deliveries](#21-turba_deliveries)
22. [unit_check_logs](#22-unit_check_logs)
23. [employee_logs](#23-employee_logs)

### Modul Pemeliharaan
24. [maintenance_reports](#24-maintenance_reports)
25. [maintenance_work_items](#25-maintenance_work_items)
26. [maintenance_unit_conditions](#26-maintenance_unit_conditions)
27. [maintenance_attendances](#27-maintenance_attendances)

### Modul Safety (K3)
28. [master_safety_locations](#28-master_safety_locations)
29. [master_safety_items](#29-master_safety_items)
30. [master_safety_location_items](#30-master_safety_location_items)
31. [safety_reports](#31-safety_reports)
32. [safety_inspections](#32-safety_inspections)
33. [safety_operation_logs](#33-safety_operation_logs)
34. [safety_incident_logs](#34-safety_incident_logs)

### Tabel Sistem Laravel
35. [cache & cache_locks](#35-cache--cache_locks)
36. [jobs, job_batches, failed_jobs](#36-jobs-job_batches-failed_jobs)

---

## Autentikasi & Otorisasi

### 1. `users`
Akun pengguna sistem. Setiap user terikat satu role yang menentukan modul yang bisa diakses.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| name | VARCHAR | 255 | No | Nama lengkap pengguna |
| email | VARCHAR | 255 | No | Email, **unique** |
| email_verified_at | TIMESTAMP | - | Yes | Waktu verifikasi email |
| password | VARCHAR | 255 | No | Hash password |
| remember_token | VARCHAR | 100 | Yes | Token "remember me" |
| username | VARCHAR | 255 | Yes | Username login, **unique** |
| role_id | BIGINT UNSIGNED | - | Yes | FK → `roles.id` (null on delete) |
| status | VARCHAR | 255 | No | Status akun, default `aktif` |
| signature_path | VARCHAR | 255 | Yes | Path file tanda tangan digital |
| group | VARCHAR | 10 | Yes | Grup/regu kerja (mis. I–IV) |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 2. `roles`
Daftar peran. Lima role pengembangan: `admin`, `manajer`, `operasional`, `pemeliharaan`, `safety`.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| name | VARCHAR | 255 | No | Nama role, **unique** |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 3. `password_reset_tokens`
Token reset password. Primary key adalah `email`.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| email | VARCHAR | 255 | No | Primary key |
| token | VARCHAR | 255 | No | Token reset |
| created_at | TIMESTAMP | - | Yes | Waktu pembuatan token |

### 4. `sessions`
Sesi login (driver session database). Primary key `id` bertipe string.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | VARCHAR | 255 | No | Primary key (session id) |
| user_id | BIGINT UNSIGNED | - | Yes | Index, id pemilik sesi |
| ip_address | VARCHAR | 45 | Yes | Alamat IP |
| user_agent | TEXT | - | Yes | User agent browser |
| payload | LONGTEXT | - | No | Data sesi terserialisasi |
| last_activity | INTEGER | - | No | Index, timestamp aktivitas terakhir |

---

## Master Data Umum

### 5. `master_units`
Master armada/unit (operasional & pemeliharaan digabung). Dipakai modul Cek Unit operasional dan Kondisi Unit pemeliharaan.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| name | VARCHAR | 255 | No | Nama tampil unit |
| type | VARCHAR | 255 | Yes | Tipe unit (Trailer, Tronton, Dump Truck, Forklift, dll) |
| status | VARCHAR | 255 | No | Status, default `active` |
| unit_code | VARCHAR | 255 | Yes | Kode jenis (TRL, TRT, DT, FL, EXC, WL) |
| brand | VARCHAR | 255 | Yes | Merek (UD, YALE, TOYOTA, HINO) |
| unit_number | VARCHAR | 255 | Yes | Nomor unit (mis. KSS-01) |
| plate_number | VARCHAR | 255 | Yes | Nomor plat kendaraan |
| macro_category | VARCHAR | 255 | Yes | Kelompok kondisi: `truck` / `heavy` |
| in_operational_check | BOOLEAN | - | No | Default `false`; true bila unit ikut seksi "Cek Unit" operasional |
| year | SMALLINT UNSIGNED | - | Yes | Tahun unit/kendaraan |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

**Index/Unique:** index `(macro_category, status)`, unique `(unit_code, unit_number)`.

### 6. `master_employees`
Master karyawan (operasional & pemeliharaan digabung dalam satu tabel via kolom `division`).

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| npk | VARCHAR | 255 | Yes | Nomor pokok karyawan, **unique** (boleh null untuk pegawai tanpa NPK) |
| name | VARCHAR | 255 | No | Nama karyawan |
| group_name | VARCHAR | 255 | Yes | Nama grup/regu |
| position | VARCHAR | 255 | Yes | Jabatan (Kasi/Karu/Mekanik/Helper/Driver/Checker, dll) |
| division | VARCHAR | 255 | Yes | Divisi: `operasional` / `pemeliharaan` / `both` |
| work_time | VARCHAR | 255 | Yes | Pola kerja (mis. `Non Shift`) |
| status | VARCHAR | 255 | No | Status, default `active` |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

**Index:** index `(division, status)`.

### 7. `master_inventory_items`
Master barang inventaris/perlengkapan.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| name | VARCHAR | 255 | No | Nama barang |
| category | VARCHAR | 255 | Yes | Kategori barang |
| stock | INTEGER | - | No | Jumlah stok, default `0` |
| status | VARCHAR | 255 | No | Status, default `active` |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 8. `master_trucks`
Master truk pihak ketiga / kendaraan pengangkut.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| name | VARCHAR | 255 | No | Nama/identitas truk |
| plate_number | VARCHAR | 255 | Yes | Nomor plat |
| description | VARCHAR | 255 | Yes | Keterangan tambahan |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 9. `admin_activity_logs`
Log aktivitas admin (audit trail perubahan master/sistem).

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| user_id | BIGINT UNSIGNED | - | Yes | FK → `users.id` (null on delete) |
| type | VARCHAR | 255 | No | Jenis aksi, default `update` |
| description | VARCHAR | 255 | No | Deskripsi aktivitas |
| ip_address | VARCHAR | 45 | Yes | Alamat IP |
| properties | JSON | - | Yes | Detail/payload perubahan |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

**Index:** index `(type, created_at)`.

---

## Modul Operasional

### 10. `daily_reports`
Tabel induk laporan harian operasional. Menyimpan workflow shift, serah-terima antar grup, dan persetujuan.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| user_id | BIGINT UNSIGNED | - | Yes | FK → `users.id`, pembuat/pemilik |
| created_by | BIGINT UNSIGNED | - | Yes | FK → `users.id`, pembuat laporan |
| report_date | DATE | - | Yes | Tanggal laporan |
| shift | VARCHAR | 255 | Yes | Shift kerja |
| group_name | VARCHAR | 255 | Yes | Nama grup/regu pelapor |
| received_by_group | VARCHAR | 255 | Yes | Grup penerima serah-terima |
| received_by_user_id | BIGINT UNSIGNED | - | Yes | FK → `users.id`, penerima |
| received_at | TIMESTAMP | - | Yes | Waktu serah-terima |
| time_range | VARCHAR | 255 | Yes | Rentang jam kerja |
| status | VARCHAR | 255 | No | Status workflow, default `draft` |
| approved_by | BIGINT UNSIGNED | - | Yes | FK → `users.id`, penyetuju |
| approved_at | TIMESTAMP | - | Yes | Waktu persetujuan |
| payload | JSON | - | Yes | Data tambahan terstruktur |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

**Index:** `(status, report_date)`, `(group_name, received_by_group)`.

### 11. `ship_operations`
Operasi kapal yang berlangsung lintas-laporan (status aktif/selesai), dirujuk oleh aktivitas bongkar-muat.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| type | VARCHAR | 30 | No | Jenis operasi |
| status | VARCHAR | 20 | No | Status, default `active` |
| ship_name | VARCHAR | 255 | No | Nama kapal |
| agent | VARCHAR | 255 | Yes | Agen pelayaran |
| jetty | VARCHAR | 255 | Yes | Dermaga |
| destination | VARCHAR | 255 | Yes | Tujuan |
| capacity | DECIMAL | 15,2 | Yes | Kapasitas/tonase, default `0` |
| wo_number | VARCHAR | 255 | Yes | Nomor Work Order |
| cargo_type | VARCHAR | 255 | Yes | Jenis muatan |
| marking | VARCHAR | 255 | Yes | Marking muatan |
| stevedoring | VARCHAR | 255 | Yes | Perusahaan stevedoring |
| commodity | VARCHAR | 255 | Yes | Komoditas |
| arrival_time | DATETIME | - | Yes | Waktu kedatangan |
| berthing_time | DATETIME | - | Yes | Waktu sandar |
| start_loading_time | DATETIME | - | Yes | Waktu mulai muat |
| started_at | TIMESTAMP | - | Yes | Waktu operasi dimulai |
| completed_at | TIMESTAMP | - | Yes | Waktu operasi selesai |
| created_by | BIGINT UNSIGNED | - | Yes | FK → `users.id` |
| completed_by | BIGINT UNSIGNED | - | Yes | FK → `users.id`, penyelesai |
| last_report_id | BIGINT UNSIGNED | - | Yes | FK → `daily_reports.id`, laporan terakhir |
| last_report_date | DATE | - | Yes | Tanggal laporan terakhir |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

**Index:** `(type, status)`, `(ship_name, type)`.

### 12. `loading_activities`
Aktivitas bongkar-muat per kapal (kantong/bag) dalam satu laporan.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| daily_report_id | BIGINT UNSIGNED | - | No | FK → `daily_reports.id` (cascade delete) |
| ship_operation_id | BIGINT UNSIGNED | - | Yes | FK → `ship_operations.id` (null on delete) |
| sequence | INTEGER | - | No | Urutan, default `1` |
| ship_name | VARCHAR | 255 | Yes | Nama kapal |
| agent | VARCHAR | 255 | Yes | Agen |
| jetty | VARCHAR | 255 | Yes | Dermaga |
| destination | VARCHAR | 255 | Yes | Tujuan |
| capacity | DECIMAL | 15,2 | Yes | Kapasitas, default `0` |
| wo_number | VARCHAR | 255 | Yes | Nomor Work Order |
| cargo_type | VARCHAR | 255 | Yes | Jenis muatan |
| marking | VARCHAR | 255 | Yes | Marking |
| arrival_time | DATETIME | - | Yes | Waktu kedatangan |
| operating_gang | VARCHAR | 255 | Yes | Gang/regu operasi |
| tkbm_count | INTEGER | - | No | Jumlah TKBM, default `0` |
| foreman | VARCHAR | 255 | Yes | Mandor |
| qty_delivery_current | DECIMAL | 15,2 | No | Qty kirim shift ini, default `0` |
| qty_delivery_prev | DECIMAL | 15,2 | No | Qty kirim shift sebelumnya, default `0` |
| qty_loading_current | DECIMAL | 15,2 | No | Qty muat shift ini, default `0` |
| qty_loading_prev | DECIMAL | 15,2 | No | Qty muat shift sebelumnya, default `0` |
| qty_damage_current | DECIMAL | 15,2 | No | Qty rusak shift ini, default `0` |
| qty_damage_prev | DECIMAL | 15,2 | No | Qty rusak shift sebelumnya, default `0` |
| tally_warehouse | VARCHAR | 255 | Yes | Tally gudang |
| driver_name | VARCHAR | 255 | Yes | Nama supir |
| truck_number | VARCHAR | 255 | Yes | Nomor truk |
| tally_ship | VARCHAR | 255 | Yes | Tally kapal |
| operator_ship | VARCHAR | 255 | Yes | Operator kapal |
| forklift_ship | VARCHAR | 255 | Yes | Forklift kapal |
| operator_warehouse | VARCHAR | 255 | Yes | Operator gudang |
| forklift_warehouse | VARCHAR | 255 | Yes | Forklift gudang |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 13. `loading_timesheets`
Catatan waktu (timesheet) per aktivitas bongkar-muat.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| loading_activity_id | BIGINT UNSIGNED | - | No | FK → `loading_activities.id` (cascade delete) |
| category | VARCHAR | 255 | No | Kategori waktu |
| time | TIME | - | Yes | Jam |
| activity | VARCHAR | 255 | Yes | Deskripsi kegiatan |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 14. `bulk_loading_activities`
Aktivitas bongkar-muat curah per kapal.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| daily_report_id | BIGINT UNSIGNED | - | No | FK → `daily_reports.id` (cascade delete) |
| ship_operation_id | BIGINT UNSIGNED | - | Yes | FK → `ship_operations.id` (null on delete) |
| sequence | INTEGER | - | No | Urutan, default `1` |
| ship_name | VARCHAR | 255 | Yes | Nama kapal |
| jetty | VARCHAR | 255 | Yes | Dermaga |
| destination | VARCHAR | 255 | Yes | Tujuan |
| agent | VARCHAR | 255 | Yes | Agen |
| stevedoring | VARCHAR | 255 | Yes | Stevedoring |
| commodity | VARCHAR | 255 | Yes | Komoditas |
| capacity | DECIMAL | 15,2 | Yes | Kapasitas, default `0` |
| berthing_time | DATETIME | - | Yes | Waktu sandar |
| start_loading_time | DATETIME | - | Yes | Waktu mulai muat |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 15. `bulk_loading_logs`
Log kronologi kegiatan curah (per waktu).

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| bulk_loading_activity_id | BIGINT UNSIGNED | - | No | FK → `bulk_loading_activities.id` (cascade delete) |
| datetime | DATETIME | - | Yes | Waktu kegiatan |
| activity | VARCHAR | 255 | Yes | Deskripsi kegiatan |
| cob | INTEGER | - | Yes | Cargo On Board / nilai numerik |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 16. `material_activities`
Aktivitas bongkar bahan baku/material per kapal.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| daily_report_id | BIGINT UNSIGNED | - | No | FK → `daily_reports.id` (cascade delete) |
| ship_name | VARCHAR | 255 | Yes | Nama kapal |
| agent | VARCHAR | 255 | Yes | Agen |
| capacity | DECIMAL | 15,2 | Yes | Kapasitas, default `0` |
| ship_tally_names | VARCHAR | 255 | Yes | Nama tally kapal |
| forklift_operator_names | VARCHAR | 255 | Yes | Nama operator forklift |
| delivery_tally_names | VARCHAR | 255 | Yes | Nama tally pengiriman |
| driver_names | VARCHAR | 255 | Yes | Nama supir |
| working_hours | VARCHAR | 255 | Yes | Jam kerja |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 17. `material_items`
Rincian item material per aktivitas material.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| material_activity_id | BIGINT UNSIGNED | - | No | FK → `material_activities.id` (cascade delete) |
| raw_material_type | VARCHAR | 255 | Yes | Jenis bahan baku |
| qty_current | DECIMAL | 15,2 | No | Qty shift ini, default `0` |
| qty_prev | DECIMAL | 15,2 | No | Qty shift sebelumnya, default `0` |
| qty_total | DECIMAL | 15,2 | No | Qty total, default `0` |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 18. `container_activities`
Aktivitas bongkar-muat kontainer per kapal.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| daily_report_id | BIGINT UNSIGNED | - | No | FK → `daily_reports.id` (cascade delete) |
| ship_name | VARCHAR | 255 | Yes | Nama kapal |
| agent | VARCHAR | 255 | Yes | Agen |
| capacity | DECIMAL | 15,2 | Yes | Kapasitas, default `0` |
| ship_tally_names | VARCHAR | 255 | Yes | Nama tally kapal |
| gudang_tally_names | VARCHAR | 255 | Yes | Nama tally gudang |
| driver_names | VARCHAR | 255 | Yes | Nama supir |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 19. `container_items`
Rincian item kontainer per aktivitas kontainer.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| container_activity_id | BIGINT UNSIGNED | - | No | FK → `container_activities.id` (cascade delete) |
| time | TIME | - | Yes | Jam |
| qty_current | DECIMAL | 15,2 | No | Qty shift ini, default `0` |
| qty_prev | DECIMAL | 15,2 | No | Qty shift sebelumnya, default `0` |
| qty_total | DECIMAL | 15,2 | No | Qty total, default `0` |
| status | VARCHAR | 255 | Yes | Status kontainer |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 20. `turba_activities`
Aktivitas TURBA (Turun Barang) — pengiriman ke gudang.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| daily_report_id | BIGINT UNSIGNED | - | No | FK → `daily_reports.id` (cascade delete) |
| tally_gudang_names | VARCHAR | 255 | Yes | Nama tally gudang |
| forklift_operator_names | VARCHAR | 255 | Yes | Nama operator forklift |
| driver_names | VARCHAR | 255 | Yes | Nama supir |
| working_hours | VARCHAR | 255 | Yes | Jam kerja |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 21. `turba_deliveries`
Rincian pengiriman per truk pada aktivitas TURBA.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| turba_activity_id | BIGINT UNSIGNED | - | No | FK → `turba_activities.id` (cascade delete) |
| truck_name | VARCHAR | 255 | Yes | Nama/nomor truk |
| do_so_number | VARCHAR | 255 | Yes | Nomor DO/SO |
| capacity | DECIMAL | 15,2 | No | Kapasitas, default `0` |
| marking_type | VARCHAR | 255 | Yes | Jenis marking |
| qty_current | DECIMAL | 15,2 | No | Qty shift ini, default `0` |
| qty_prev | DECIMAL | 15,2 | No | Qty shift sebelumnya, default `0` |
| qty_accumulated | DECIMAL | 15,2 | No | Qty akumulasi, default `0` |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 22. `unit_check_logs`
Catatan pemeriksaan unit/inventaris pada laporan operasional (seksi Cek Unit).

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| daily_report_id | BIGINT UNSIGNED | - | No | FK → `daily_reports.id` (cascade delete) |
| category | VARCHAR | 255 | No | Kategori unit |
| item_name | VARCHAR | 255 | Yes | Nama unit/item (snapshot) |
| master_id | VARCHAR | 255 | Yes | Referensi id master (teks) |
| fuel_level | VARCHAR | 255 | Yes | Level BBM |
| condition_received | VARCHAR | 255 | Yes | Kondisi saat diterima |
| condition_handed_over | VARCHAR | 255 | Yes | Kondisi saat diserahkan |
| quantity | INTEGER | - | No | Jumlah, default `1` |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 23. `employee_logs`
Catatan kehadiran/penugasan karyawan pada laporan operasional.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| daily_report_id | BIGINT UNSIGNED | - | No | FK → `daily_reports.id` (cascade delete) |
| category | VARCHAR | 255 | No | Kategori personil |
| name | VARCHAR | 255 | Yes | Nama karyawan |
| no_forklift_ | VARCHAR | 255 | Yes | Nomor forklift |
| work_area | VARCHAR | 255 | Yes | Area kerja |
| personil_count | VARCHAR | 255 | Yes | Jumlah personil |
| time_in | TIME | - | Yes | Jam masuk |
| time_out | TIME | - | Yes | Jam keluar |
| work_time | VARCHAR | 255 | Yes | Durasi/jam kerja |
| description | VARCHAR | 255 | Yes | Keterangan |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

---

## Modul Pemeliharaan

### 24. `maintenance_reports`
Tabel induk laporan harian pemeliharaan. Dua pengesahan: `created_by`/`submitted_at` (Kasi) dan `approved_by`/`approved_at` (Manajer).

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| report_date | DATE | - | Yes | Tanggal laporan |
| day_name | VARCHAR | 255 | Yes | Nama hari (diturunkan dari tanggal) |
| status | ENUM | `draft`,`submitted`,`approved` | No | Status workflow, default `draft` |
| created_by | BIGINT UNSIGNED | - | Yes | FK → `users.id`, pembuat (Kasi) |
| submitted_at | TIMESTAMP | - | Yes | Waktu submit |
| approved_by | BIGINT UNSIGNED | - | Yes | FK → `users.id`, penyetuju (Manajer) |
| approved_at | TIMESTAMP | - | Yes | Waktu persetujuan |
| karu_pemeliharaan_name | VARCHAR | 255 | Yes | Nama Karu Pemeliharaan (untuk cetak PDF) |
| karu_peralatan_name | VARCHAR | 255 | Yes | Nama Karu Peralatan (untuk cetak PDF) |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

**Index:** `(status, report_date)`.

### 25. `maintenance_work_items`
Pekerjaan Utama (terikat Group I–IV) + Pekerjaan Prioritas (dinamis) dalam satu tabel via kolom `work_type`.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| maintenance_report_id | BIGINT UNSIGNED | - | No | FK → `maintenance_reports.id` (cascade delete) |
| work_type | ENUM | `utama`,`prioritas` | No | Jenis pekerjaan |
| work_group | VARCHAR | 255 | Yes | Group I/II/III/IV (hanya untuk `utama`) |
| master_unit_id | BIGINT UNSIGNED | - | Yes | FK → `master_units.id` (null on delete) |
| unit_label | VARCHAR | 255 | Yes | Snapshot teks unit (mis. BENGKEL) |
| description | TEXT | - | Yes | Uraian pekerjaan |
| assignee | VARCHAR | 255 | Yes | Petugas (teks bebas, multi-nama) |
| is_completed | BOOLEAN | - | No | Status selesai, default `false` |
| notes | VARCHAR | 255 | Yes | Catatan |
| sort_order | INTEGER | - | No | Urutan tampil, default `0` |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

**Index:** `(maintenance_report_id, work_type)`.

### 26. `maintenance_unit_conditions`
Kondisi unit per laporan (ready/rusak). Total ready/rusak dihitung otomatis dari baris ini.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| maintenance_report_id | BIGINT UNSIGNED | - | No | FK → `maintenance_reports.id` (cascade delete) |
| master_unit_id | BIGINT UNSIGNED | - | Yes | FK → `master_units.id` (null on delete) |
| unit_label | VARCHAR | 255 | Yes | Snapshot nama unit (integritas historis) |
| condition | ENUM | `ready`,`rusak` | No | Kondisi unit, default `ready` |
| notes | VARCHAR | 255 | Yes | Catatan |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

**Unique:** `(maintenance_report_id, master_unit_id)` — satu status per unit per laporan.

### 27. `maintenance_attendances`
Daftar hadir karyawan pemeliharaan. `employee_name` & `position` adalah snapshot agar laporan historis tetap akurat.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| maintenance_report_id | BIGINT UNSIGNED | - | No | FK → `maintenance_reports.id` (cascade delete) |
| master_employee_id | BIGINT UNSIGNED | - | Yes | FK → `master_employees.id` (null on delete) |
| employee_name | VARCHAR | 255 | No | Nama karyawan (snapshot) |
| position | VARCHAR | 255 | Yes | Jabatan (snapshot) |
| time_in | TIME | - | Yes | Jam masuk |
| time_out | TIME | - | Yes | Jam keluar |
| notes | VARCHAR | 255 | Yes | Catatan |
| sort_order | INTEGER | - | No | Urutan tampil, default `0` |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

**Index:** `maintenance_report_id`.

---

## Modul Safety (K3)

### 28. `master_safety_locations`
Master lokasi inspeksi K3 (7 lokasi tetap).

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| name | VARCHAR | 255 | No | Nama lokasi |
| sort_order | SMALLINT UNSIGNED | - | No | Urutan tampil, default `0` |
| is_active | BOOLEAN | - | No | Aktif, default `true` |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 29. `master_safety_items`
Master item yang diinspeksi (Bangunan, Lampu, AC, APAR, dst).

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| name | VARCHAR | 255 | No | Nama item |
| is_countable | BOOLEAN | - | No | true = QTY relevan, default `false` |
| is_active | BOOLEAN | - | No | Aktif, default `true` |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

### 30. `master_safety_location_items`
Template inspeksi (pivot): item apa yang muncul di lokasi mana.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| location_id | BIGINT UNSIGNED | - | No | FK → `master_safety_locations.id` (cascade delete) |
| item_id | BIGINT UNSIGNED | - | No | FK → `master_safety_items.id` (cascade delete) |
| default_qty | SMALLINT UNSIGNED | - | Yes | QTY default item |
| sort_order | SMALLINT UNSIGNED | - | No | Urutan tampil, default `0` |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

**Unique:** `(location_id, item_id)`.

### 31. `safety_reports`
Tabel induk laporan harian K3. FSM: draft → submitted → approved. Dua pengesahan: `created_by` (Karu Safety) & `approved_by` (Manajer).

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| document_number | VARCHAR | 255 | Yes | Nomor dokumen (DOC-2026-00X), **unique** |
| report_date | DATE | - | Yes | Tanggal laporan |
| time_range | VARCHAR | 255 | Yes | Rentang jam (mis. "19:00-03:00") |
| shift | VARCHAR | 255 | Yes | Shift (opsional) |
| status | ENUM | `draft`,`submitted`,`approved` | No | Status workflow, default `draft` |
| created_by | BIGINT UNSIGNED | - | Yes | FK → `users.id`, pelapor (role safety) |
| submitted_at | TIMESTAMP | - | Yes | Waktu submit |
| reporter_signature_path | VARCHAR | 255 | Yes | Path TTD pelapor |
| approved_by | BIGINT UNSIGNED | - | Yes | FK → `users.id`, penyetuju (role manajer) |
| approved_at | TIMESTAMP | - | Yes | Waktu persetujuan |
| approver_signature_path | VARCHAR | 255 | Yes | Path TTD penyetuju |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

**Index:** `(status, report_date)`.

### 32. `safety_inspections`
Baris inspeksi K3 (Lokasi → Item → QTY → Kondisi → Rekomendasi). `*_name_snapshot` menjaga integritas historis.

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| safety_report_id | BIGINT UNSIGNED | - | No | FK → `safety_reports.id` (cascade delete) |
| location_id | BIGINT UNSIGNED | - | Yes | FK → `master_safety_locations.id` (null on delete) |
| item_id | BIGINT UNSIGNED | - | Yes | FK → `master_safety_items.id` (null on delete) |
| location_name_snapshot | VARCHAR | 255 | No | Snapshot nama lokasi |
| item_name_snapshot | VARCHAR | 255 | No | Snapshot nama item |
| qty | SMALLINT UNSIGNED | - | Yes | Jumlah |
| condition | ENUM | `bagus`,`rusak`,`normal`,`tidak_normal` | Yes | Kondisi item |
| recommendation | VARCHAR | 255 | Yes | Rekomendasi/tindak lanjut |
| sort_order | SMALLINT UNSIGNED | - | No | Urutan tampil, default `0` |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

**Index:** `(safety_report_id, location_id)`.

### 33. `safety_operation_logs`
Section 8 form: Kegiatan Operasi & Pemeliharaan. KONDISI di sini berupa teks bebas ("Aman").

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| safety_report_id | BIGINT UNSIGNED | - | No | FK → `safety_reports.id` (cascade delete) |
| activity_name | VARCHAR | 255 | No | Nama kegiatan (GRESIK NIAGA, dst) |
| condition | VARCHAR | 255 | Yes | Kondisi (teks bebas, mis. "Aman") |
| action | VARCHAR | 255 | Yes | Tindakan |
| notes | VARCHAR | 255 | Yes | Keterangan (mis. "In Bags"/"Curah") |
| sort_order | SMALLINT UNSIGNED | - | No | Urutan tampil, default `0` |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

**Index:** `safety_report_id`.

### 34. `safety_incident_logs`
Section 9 form: Laporan Kejadian & Lain-lain. Semua kolom nullable (boleh nol baris).

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| safety_report_id | BIGINT UNSIGNED | - | No | FK → `safety_reports.id` (cascade delete) |
| description | VARCHAR | 255 | Yes | Uraian kejadian |
| condition | VARCHAR | 255 | Yes | Kondisi |
| action | VARCHAR | 255 | Yes | Tindakan |
| notes | VARCHAR | 255 | Yes | Keterangan |
| sort_order | SMALLINT UNSIGNED | - | No | Urutan tampil, default `0` |
| created_at | TIMESTAMP | - | Yes | Waktu dibuat |
| updated_at | TIMESTAMP | - | Yes | Waktu diperbarui |

**Index:** `safety_report_id`.

---

## Tabel Sistem Laravel

### 35. `cache` & `cache_locks`
Penyimpanan cache berbasis database.

**`cache`**

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| key | VARCHAR | 255 | No | Primary key |
| value | MEDIUMTEXT | - | No | Nilai cache |
| expiration | BIGINT | - | No | Index, timestamp kedaluwarsa |

**`cache_locks`**

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| key | VARCHAR | 255 | No | Primary key |
| owner | VARCHAR | 255 | No | Pemilik lock |
| expiration | BIGINT | - | No | Index, timestamp kedaluwarsa |

### 36. `jobs`, `job_batches`, `failed_jobs`
Antrian pekerjaan (queue) Laravel.

**`jobs`**

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| queue | VARCHAR | 255 | No | Index, nama antrian |
| payload | LONGTEXT | - | No | Payload job |
| attempts | SMALLINT UNSIGNED | - | No | Jumlah percobaan |
| reserved_at | INTEGER UNSIGNED | - | Yes | Waktu job direservasi |
| available_at | INTEGER UNSIGNED | - | No | Waktu job tersedia |
| created_at | INTEGER UNSIGNED | - | No | Waktu dibuat (epoch) |

**`job_batches`**

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | VARCHAR | 255 | No | Primary key |
| name | VARCHAR | 255 | No | Nama batch |
| total_jobs | INTEGER | - | No | Total job |
| pending_jobs | INTEGER | - | No | Job tertunda |
| failed_jobs | INTEGER | - | No | Job gagal |
| failed_job_ids | LONGTEXT | - | No | Daftar id job gagal |
| options | MEDIUMTEXT | - | Yes | Opsi batch |
| cancelled_at | INTEGER | - | Yes | Waktu dibatalkan |
| created_at | INTEGER | - | No | Waktu dibuat (epoch) |
| finished_at | INTEGER | - | Yes | Waktu selesai (epoch) |

**`failed_jobs`**

| Field | Tipe Data | Length | Null | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | - | No | Primary key |
| uuid | VARCHAR | 255 | No | **unique** |
| connection | TEXT | - | No | Koneksi |
| queue | TEXT | - | No | Antrian |
| payload | LONGTEXT | - | No | Payload job |
| exception | LONGTEXT | - | No | Detail exception |
| failed_at | TIMESTAMP | - | No | Waktu gagal, default current |

---

## Ringkasan Relasi Utama

```
roles 1───* users
users 1───* daily_reports          (user_id, created_by, received_by_user_id, approved_by)
users 1───* maintenance_reports    (created_by, approved_by)
users 1───* safety_reports         (created_by, approved_by)
users 1───* ship_operations        (created_by, completed_by)
users 1───* admin_activity_logs    (user_id)

daily_reports 1───* loading_activities ───* loading_timesheets
daily_reports 1───* bulk_loading_activities ───* bulk_loading_logs
daily_reports 1───* material_activities ───* material_items
daily_reports 1───* container_activities ───* container_items
daily_reports 1───* turba_activities ───* turba_deliveries
daily_reports 1───* unit_check_logs
daily_reports 1───* employee_logs
ship_operations 1───* loading_activities / bulk_loading_activities

maintenance_reports 1───* maintenance_work_items       (master_unit_id → master_units)
maintenance_reports 1───* maintenance_unit_conditions  (master_unit_id → master_units)
maintenance_reports 1───* maintenance_attendances      (master_employee_id → master_employees)

master_safety_locations *───* master_safety_items  (via master_safety_location_items)
safety_reports 1───* safety_inspections   (location_id, item_id → master safety)
safety_reports 1───* safety_operation_logs
safety_reports 1───* safety_incident_logs
```

> Catatan: Tabel legacy `maintenance_units` dan `maintenance_employees` sudah
> **dihapus** pada migration `2026_06_01_000003`; seluruh referensi kini mengarah
> ke `master_units` dan `master_employees`.
