# Perancangan Modul Pemeliharaan — Sistem Laporan KSS

**Increment ke-2 — Unit Kerja Pemeliharaan & Peralatan**
Dokumen perancangan teknis: dashboard petugas, form laporan harian, skema basis data, dan penyelarasan dengan *design system* yang telah ada.

---

## 1. Pendahuluan

### 1.1 Tujuan Modul
Modul ini mendigitalkan dokumen fisik **"Laporan Harian Unit Kerja Pemeliharaan dan Peralatan"** beserta **"Daftar Hadir Karyawan"**-nya. Modul dibangun sebagai *increment* kedua di atas fondasi arsitektur yang sudah berjalan pada modul Operasional (Laravel 12, MVC, Blade, Tailwind CSS v4, MySQL), dengan memanfaatkan kembali sistem peran (`role`) dan alur persetujuan yang ada.

### 1.2 Perbedaan Paradigma dengan Modul Operasional
Penyelarasan visual modul ini mengikuti Operasional, namun **paradigma datanya berbeda** dan perbedaan ini menentukan keputusan perancangan:

| Aspek | Operasional | Pemeliharaan |
|---|---|---|
| Basis waktu | Per **shift** | Per **hari** (Non Shift) |
| Antar-pihak | Serah-terima antar regu | Tidak ada serah-terima |
| Orientasi data | Kuantitas (akumulasi) | Tugas & status (selesai/tidak) |
| Pengesahan | submitted → acknowledged → approved | submitted → approved (2 pihak) |

> **Implikasi:** field Shift, Group/Regu, Jam Kerja, dan Group Penerima yang ada di Operasional **tidak digunakan** di modul ini. Memaksakannya hanya menghasilkan kolom kosong yang membingungkan.

### 1.3 Ringkasan Keputusan Perancangan (Terkunci)
Hasil validasi lapangan ke Manajer (Pak Mustari) dan analisis dokumen terisi:

1. Laporan dibuat **sekali sehari**, tanpa shift.
2. **Group I–IV** = empat kelompok kerja personil/mekanik penanggung jawab Pekerjaan Utama; dapat bergabung bila perlu (label, bukan entitas regu).
3. **Pekerjaan Utama** (4 baris tetap, terikat grup) dan **Pekerjaan Prioritas** (baris dinamis) digabung satu tabel via kolom `work_type`.
4. **Kondisi Unit** dicatat **per unit** (nomor + status); total ready/rusak dihitung otomatis oleh sistem.
5. **Pengesahan = 2 pihak**: Kasi Pemeliharaan (pembuat) dan Manajer (penyetuju). Tanda tangan Karu Pemeliharaan & Karu Peralatan tidak masuk *workflow* — hanya disimpan sebagai field informatif untuk kesetiaan cetak PDF.
6. **Satu user pembuat** dengan peran `pemeliharaan` (sudah disiapkan di increment 1). Tidak diperlukan RBAC bertingkat.
7. **Armada** dan **roster karyawan** pemeliharaan dibuat **terpisah** dari master Operasional (`maintenance_units`, `maintenance_employees`).

---

## 2. Design System (Acuan Penyelarasan UI)

Token berikut diekstrak dari modul Operasional yang sudah berjalan. Modul Pemeliharaan **wajib** mengikutinya agar konsisten.

### 2.1 Warna
| Peran | Tailwind | Penggunaan |
|---|---|---|
| Primary | `blue-600` | Tombol utama, tab aktif, header tabel, tombol "Lanjut" |
| Draft / sekunder | `amber-500` / orange | Tombol "Simpan Sebagai Draft" |
| Sukses / setuju | `green-600` | Tombol tanda tangani/setuju, aksi "Set Semua" |
| Bahaya | `red-500` | Ikon hapus baris, hapus draft |
| Latar halaman | `gray-50` | Background utama |
| Kartu | `white` + `rounded-xl` + `shadow-sm` | Kontainer konten |

### 2.2 Badge Status (mengikuti pola existing)
| Status | Kelas | Label |
|---|---|---|
| `draft` | `bg-gray-100 text-gray-700` | Draft |
| `submitted` | `bg-yellow-100 text-yellow-800` | Menunggu Persetujuan |
| `approved` | `bg-green-100 text-green-800` | Disetujui |

> Catatan: status `acknowledged` (biru) dari Operasional **tidak dipakai** di sini karena tidak ada tahap serah-terima.

### 2.3 Komponen Baku
- **Header aplikasi:** logo KSS (kiri) · sapaan "Selamat Pagi, [Nama]" + sub-peran · toggle tema + tombol Logout (kanan).
- **Blok judul halaman:** judul + ID dokumen + tombol "Simpan Sebagai Draft" (amber, kanan atas).
- **Navigasi tab seksi:** kartu putih berisi tab; aktif = *pill* biru terisi + ikon, non-aktif = teks abu + ikon.
- **Kartu seksi form:** header (ikon + judul) + *pill* indikator "Form X dari N" (kanan).
- **Tabel data:** baris header biru (teks putih); baris isi berupa input; baris terakhir `+ Tambah Baris` bergaya *dashed*.
- **Input:** `rounded-lg border-gray-300`, *placeholder* abu.
- **Bilah aksi bawah:** "✕ Batalkan" (ghost, kiri) · "Lanjut ›" (biru, kanan); pada seksi terakhir tombol kanan menjadi "Kirim".
- **Footer:** `© 2026 Sistem Laporan KSS. Dibuat oleh Muhammad Arobi.`

---

## 3. Skema Basis Data (Migration)

Enam migration, berurutan sesuai dependensi: master dahulu → induk → anak.

### 3.1 Relasi Antar-Tabel
```
users ──< maintenance_reports (created_by, approved_by)
                  │
                  ├──< maintenance_work_items        (cascade)
                  ├──< maintenance_unit_conditions    (cascade)
                  └──< maintenance_attendances        (cascade)

maintenance_units      ──< maintenance_work_items      (nullOnDelete)
maintenance_units      ──< maintenance_unit_conditions (restrictOnDelete)
maintenance_employees  ──< maintenance_attendances     (nullOnDelete)
```

### 3.2 Daftar Tabel & Kolom Inti

**`maintenance_units`** — master armada
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| unit_code | string | Jenis: TRL, TRT, DT, FL, EXC, WL |
| brand | string null | Merek: UD, YALE, TOYOTA, HINO |
| unit_number | string | Nomor unit |
| name | string null | Nama tampil opsional |
| macro_category | enum | `truck` \| `heavy` (untuk kelompok Kondisi Unit) |
| is_active | boolean | |
| *unique* | (unit_code, unit_number) | |

**`maintenance_employees`** — master roster (12 personel)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| name | string | |
| position | string | Kasi/Karu/Mekanik/Helper/Driver/Checker |
| work_time | string | default "Non Shift" |
| is_active | boolean | |

**`maintenance_reports`** — induk
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| report_date | date | |
| day_name | string null | Diturunkan dari tanggal |
| status | enum | `draft` \| `submitted` \| `approved` |
| created_by | FK users | Tanda tangan 1 (Kasi) |
| submitted_at | timestamp null | |
| approved_by | FK users null | Tanda tangan 2 (Manajer) |
| approved_at | timestamp null | |
| karu_pemeliharaan_name | string null | Informatif (PDF) |
| karu_peralatan_name | string null | Informatif (PDF) |

**`maintenance_work_items`** — Pekerjaan Utama + Prioritas
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| maintenance_report_id | FK | cascade |
| work_type | enum | `utama` \| `prioritas` |
| work_group | string null | I/II/III/IV (utama saja) |
| maintenance_unit_id | FK null | null bila non-unit (mis. BENGKEL) |
| unit_label | string null | fallback teks unit |
| description | text | Uraian pekerjaan |
| assignee | string null | Petugas (teks bebas, multi-nama) |
| is_completed | boolean | Selesai = true |
| notes | string null | Keterangan |
| sort_order | integer | |

**`maintenance_unit_conditions`** — Kondisi Unit (per unit)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| maintenance_report_id | FK | cascade |
| maintenance_unit_id | FK | restrict |
| condition | enum | `ready` \| `rusak` |
| notes | string null | |
| *unique* | (report_id, unit_id) | satu status per unit per laporan |

**`maintenance_attendances`** — Daftar Hadir
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| maintenance_report_id | FK | cascade |
| maintenance_employee_id | FK null | |
| employee_name | string | snapshot |
| position | string null | snapshot |
| time_in | time null | Masuk |
| time_out | time null | Pulang |
| notes | string null | Keterangan |

### 3.3 Catatan Keputusan Teknis (untuk dipertanggungjawabkan)
- **Pola snapshot** (`employee_name`, `position`, `unit_label`) berdampingan dengan FK ke master: agar laporan historis tetap akurat meski data master berubah. Praktik baku pada sistem pelaporan.
- **`is_completed` boolean**, bukan enum dua-nilai: status pada form hanya biner (Selesai/Tidak), lebih idiomatis dan hemat.
- **`work_group` varchar**, bukan enum: mengakomodasi kasus grup bergabung (mis. "I & II").
- **Tanpa tabel sign-off**: dua pengesahan diwakili `created_by`/`submitted_at` dan `approved_by`/`approved_at`.

---

## 4. Dashboard Petugas Pemeliharaan

### 4.1 Struktur Halaman
Mengikuti pola "Laporan Operasional", **disederhanakan**: hanya **dua** tab, karena tidak ada konsep "Laporan Masuk" (serah-terima antar regu tidak ada).

```
┌─────────────────────────────────────────────────────────┐
│ [Logo KSS]  Selamat Pagi, [Nama]        [tema] [Logout]   │
│             Petugas Pemeliharaan                          │
├─────────────────────────────────────────────────────────┤
│  Laporan Pemeliharaan                    [+ Buat Laporan] │
│  Kelola laporan harian unit pemeliharaan di sini.         │
│                                                           │
│  [ Draft (n) ] [ Riwayat Laporan ]                        │
│  ───────────────────────────────────────────────         │
│  (daftar kartu / tabel laporan)                           │
└─────────────────────────────────────────────────────────┘
│        © 2026 Sistem Laporan KSS. Dibuat oleh M. Arobi.   │
```

### 4.2 Tab "Draft"
Daftar laporan berstatus `draft` (kartu): badge **Draft** (abu), judul "Laporan Harian Pemeliharaan", ID dokumen, info "Terakhir diedit ...", tombol **Lanjutkan Edit** (biru) + ikon hapus (merah). Konsisten dengan kartu draft Operasional.

### 4.3 Tab "Riwayat Laporan"
Tabel: No · Info Dokumen · Tanggal Laporan · Status (badge) · Aksi (Lihat / Edit). Badge mengikuti §2.2. Tanpa kolom Shift (tidak relevan); boleh diganti kolom **Hari**.

### 4.4 Tombol "Buat Laporan"
Membuka Form Laporan Pemeliharaan (§5) dalam keadaan kosong, status awal `draft`.

---

## 5. Form Laporan Pemeliharaan

Form bertab, meniru "Form Laporan Shift Harian" Operasional. Indikator progres "Form X dari 6". Tombol "Simpan Sebagai Draft" (amber) selalu tampil di header. Navigasi antar-seksi: "✕ Batalkan" (kiri) · "Lanjut ›" (kanan); seksi terakhir → "Kirim".

**Tab seksi:** Info Umum · Pekerjaan Utama · Pekerjaan Prioritas · Kondisi Unit · Daftar Hadir · Pengesahan

### 5.1 Seksi — Info Umum
Sengaja minimal (tanpa Shift/Group/Jam Kerja).
| Field | Input | Kolom | Wajib |
|---|---|---|---|
| Hari / Tanggal | date picker | `report_date` | ya |
| Hari | otomatis dari tanggal | `day_name` | auto |

### 5.2 Seksi — Pekerjaan Utama
Empat baris ter-render mengikuti Group I–IV; baris boleh kosong; grup boleh digabung. `work_type` = `utama`.
| Field per baris | Input | Kolom |
|---|---|---|
| Group | label I/II/III/IV (editable bila gabung) | `work_group` |
| Jenis Unit (Nama + Nomor) | pilih dari master armada | `maintenance_unit_id` |
| Pekerjaan Utama | textarea | `description` |
| Petugas | teks bebas + autocomplete roster | `assignee` |
| Status | toggle Selesai / Tdk Selesai | `is_completed` |
| Keterangan | teks | `notes` |

### 5.3 Seksi — Pekerjaan Prioritas
Struktur kolom sama dengan §5.2 **tanpa kolom Group**; baris **dinamis** (`+ Tambah Baris` / hapus). `work_type` = `prioritas`. Selektor unit harus mengizinkan **entri bebas** (`unit_label`) untuk kasus non-unit seperti "BENGKEL".

### 5.4 Seksi — Kondisi Unit Saat Ini
Dua kelompok visual dengan **penghitung total langsung** di atasnya (realisasi "jumlah otomatis"):
- **Kelompok A — Trailer / Tronton / Dump Truck** (`macro_category = truck`)
- **Kelompok B — Forklift / Excavator / Wheel Loader** (`macro_category = heavy`)

| Field per baris | Input | Kolom |
|---|---|---|
| Unit | pilih dari master armada | `maintenance_unit_id` |
| Kondisi | toggle Ready / Rusak | `condition` |
| Keterangan | teks | `notes` |

Header tiap kelompok menampilkan `Ready: X | Rusak: Y` yang dihitung dari baris terisi secara *real-time* (Vanilla JS).

### 5.5 Seksi — Daftar Hadir Karyawan
Roster 12 personel dari `maintenance_employees` di-*preload* (Nama + Jabatan terisi, Waktu Kerja "Non Shift").
| Field per baris | Input | Kolom |
|---|---|---|
| Nama Karyawan | dari roster (+ tambah manual) | `employee_name` (+`maintenance_employee_id`) |
| Jabatan | otomatis | `position` |
| Masuk | time | `time_in` |
| Pulang | time | `time_out` |
| Keterangan | teks | `notes` |

### 5.6 Seksi — Pengesahan
Bukan kanvas tanda tangan, melainkan info penanggung jawab + aksi simpan:
- **Kasi Pemeliharaan**: terisi otomatis dari akun login pembuat (`created_by`).
- **Karu Pemeliharaan** & **Karu Peralatan**: field nama opsional (`karu_pemeliharaan_name`, `karu_peralatan_name`) untuk kesetiaan PDF.
- Tanda tangan kedua (Manajer) terjadi di sisi manajer saat *approve*, bukan di form ini.

**Aksi:** "Simpan Sebagai Draft" (`draft`) dan "Kirim" (`submitted`, mengisi `submitted_at`).

---

## 6. Alur Status (Workflow)

```
[draft] ──(Petugas: Kirim)──> [submitted] ──(Manajer: Setujui)──> [approved]
   ▲                               │
   └────────(Manajer: Revisi*)─────┘
```
*Opsi pengembalian ke draft bila ditolak — bersifat opsional, lihat §7.

Badge status mengikuti §2.2: Draft (abu) → Menunggu Persetujuan (kuning) → Disetujui (hijau).

---

## 7. Asumsi Terbuka & Risiko (Perlu Konfirmasi)

1. **Identitas pembuat:** asumsi saat ini nama Kasi diambil dari akun login. Bila yang login adalah staf yang mengetik *atas nama* Kasi, perlu kolom tambahan `prepared_by_name`.
2. **Cakupan Kondisi Unit harian:** apakah seluruh armada diisi statusnya tiap hari (preload penuh), atau hanya unit yang perlu dicatat (mis. yang rusak) dengan sisanya dianggap ready? Menentukan strategi *preload* daftar.
3. **Penyederhanaan 3→2 tanda tangan:** form fisik memuat 3 TTD; versi digital 2. Wajib dicatat eksplisit di bab Perancangan sebagai keputusan pemangku kepentingan, untuk antisipasi pertanyaan penguji.
4. **Status revisi/penolakan:** apakah manajer dapat mengembalikan laporan ke `draft`? Bila ya, perlu kolom catatan revisi.
5. **Penamaan unit:** transkripsi dari tulisan tangan (mis. "FL YALE", "TRL UD") perlu diverifikasi ke Karu sebelum mengisi master.

---

## 8. Lampiran — Urutan File Migration
```
2026_05_29_100001_create_maintenance_units_table.php
2026_05_29_100002_create_maintenance_employees_table.php
2026_05_29_100003_create_maintenance_reports_table.php
2026_05_29_100004_create_maintenance_work_items_table.php
2026_05_29_100005_create_maintenance_unit_conditions_table.php
2026_05_29_100006_create_maintenance_attendances_table.php
```
