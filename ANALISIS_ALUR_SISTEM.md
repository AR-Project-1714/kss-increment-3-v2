# Analisis Alur Sistem KSS — Temuan yang Belum Sesuai

Dokumen ini merangkum hasil penelusuran alur sistem (auth → role → laporan operasional → manajer → admin) dan mencatat hal-hal yang **belum sesuai / tidak konsisten / setengah jadi**. Belum ada perubahan kode yang dilakukan — ini murni catatan temuan + rekomendasi.

Tanggal analisis: 2026-05-23

---

## Ringkasan alur yang SUDAH berjalan benar

- **Login** (`LoginV2Controller`): login via username/email, rate-limit per identitas + per IP, blokir akun nonaktif, pencatatan event keamanan, redirect sesuai role. ✔
- **Kontrol akses** (`EnsureRole` + `bootstrap/app.php`): allow-list & deny-list role, redirect ke dashboard masing-masing, anti redirect-loop, handling 419 (sesi habis). ✔
- **Alur status laporan** (happy path):
  `draft → submitted → acknowledged → approved`
  - Petugas buat/simpan draft & submit (`ReportOpsController::store`)
  - Regu penerima tanda tangan: `submitted → acknowledged` (`ReportOpsController::sign`)
  - Manajer setujui: `acknowledged → approved` + arsip PDF (`ManajerController::approve`) ✔
- **Enum `ReportStatus`** dipakai konsisten di query, perbandingan, dan cast model. ✔
- **Caching**: master data (array, di-invalidasi via model event) & PDF sementara untuk laporan belum approved. ✔

---

## Temuan yang belum sesuai

### 🔴 1. `AdminController.php` adalah dead code dan rusak
- **File**: `app/Http/Controllers/AdminController.php`
- Tidak terdaftar di route mana pun — seluruh route `admin.*` memakai `AdminV2Controller` (`routes/web.php:18-56`).
- Masih merujuk view `officer.pdf` (baris 64, 107) dan `officer.viewpdf` (baris 121) yang **tidak ada** (folder `resources/views/officer/` tidak ditemukan). Kalau pernah dipanggil → fatal error.
- **Dampak**: membingungkan, menambah beban maintenance (ikut diubah saat refactor enum padahal tak terpakai).
- **Rekomendasi**: hapus `AdminController.php`. Pastikan tidak ada referensi tersisa sebelum menghapus.

### 🟢 2. Cabang `isAdmin()` mati di `ReportOpsController` — ✅ SELESAI (2026-05-23)
> **Tindak lanjut**: Approval ditetapkan **hanya milik manajer** (`ManajerController::approve`). Yang dibersihkan: cabang `acknowledged → approved` di `sign()` dihapus; kondisi `isAdmin($user) ||` di `canAccessReport`/`canEditReport`/`canDeleteReport` dan scoping `when(! $isAdmin, …)` di `index()`/`historySuggestions()` disederhanakan (perilaku identik karena `$isAdmin` memang selalu `false` di sini); method `isAdmin()` + import `App\Models\Role` yang tak terpakai dihapus. Seluruh test lulus (25/25).

<em>Temuan asli:</em>
- **File**: `app/Http/Controllers/ReportOpsController.php`
- Route `/report-ops/*` dibungkus middleware `role:except,admin,manajer` (`routes/web.php:59`), jadi admin & manajer **tidak akan pernah** masuk ke controller ini.
- Tapi `isAdmin($user)` (`:2052`) bernilai `true` hanya untuk admin/manajer → di dalam controller ini **selalu `false`**. Akibatnya:
  - Cabang `acknowledged → approved` di `sign()` (`:400-411`) tidak pernah tercapai (selalu jatuh ke pesan "Hanya admin atau manajer...").
  - Kondisi `isAdmin($user) ||` di `canAccessReport` (`:2001`), `canEditReport` (`:2027`), `canDeleteReport` (`:2036`) adalah cabang mati.
- **Dampak**: kode menyiratkan admin/manajer bisa mengelola laporan dari sisi petugas, padahal tidak. Satu-satunya jalur approve yang aktif adalah `ManajerController::approve`.
- **Rekomendasi**: tentukan intent — kalau approval memang hanya milik manajer, bersihkan cabang `isAdmin` yang mati di controller ini. Kalau admin seharusnya bisa, sesuaikan middleware/route.

### ⏸️ 3. Fitur multi-divisi (pemeliharaan & safety) setengah jadi — DITUNDA
> **Keputusan**: Dibiarkan dulu, memang disiapkan untuk pengembangan selanjutnya.

- **File**: `ManajerController.php:28-33` & `:286-291`, `AdminV2Controller.php:906-910`, view `manajer/index.blade.php:120-126`, `manajer/archive.blade.php:435-436`, `admin/archive.blade.php:582-583`.
- Role `pemeliharaan` & `safety` ada (`Role.php:12-13`, `DIVISION_ROLES`), dan UI sudah punya tab/filter divisi untuk keduanya.
- **Namun**:
  - Laporan (`daily_reports`) tidak punya kolom/penanda divisi — tidak ada yang mengategorikan laporan ke pemeliharaan/safety.
  - `applyArchiveDivisionFilter` mengembalikan `whereRaw('1 = 0')` untuk divisi selain operasional → filter pemeliharaan/safety **selalu kosong**.
  - `divisionCounts` di dashboard manajer hardcode `pemeliharaan => 0, safety => 0`, dan count "operasional" = semua laporan acknowledged tanpa memandang divisi (label bisa menyesatkan).
- **Dampak**: tab/filter divisi terlihat ada tapi tidak berfungsi → membingungkan pengguna.
- **Rekomendasi**: pilih salah satu —
  (a) Selesaikan fitur: tambah kolom `division` di laporan + isi saat submit + filter beneran, atau
  (b) Sembunyikan tab/filter pemeliharaan & safety sampai fiturnya siap.

### 🟢 4. Stok inventaris tidak pernah di-update — ✅ SELESAI (2026-05-23)
> **Tindak lanjut**: Field **Jumlah** (`stock`) ditambahkan di master Data Inventaris (form Tambah/Edit + kolom tabel) dan dipakai sebagai referensi qty default di laporan operasional (`stock as qty` → `item.qty`).

- **File**: `AdminV2Controller.php:572` & `AdminController.php:216` (create `stock => 0`), `ReportOpsController.php:1897` (`stock as qty`).
- `stock` hanya di-set 0 saat pembuatan dan tidak pernah ditambah/dikurangi di mana pun. Form buat/edit memakai `stock as qty` sebagai default qty → nilainya selalu 0.
- **Dampak**: kolom qty default dari stok tidak bermakna.
- **Rekomendasi**: implementasikan pelacakan stok (increment/decrement saat pemakaian) atau hilangkan asumsi qty-dari-stok bila memang tidak dipakai.

### 🟡 5. Cache statistik dashboard manajer bisa basi hingga 60 detik
- **File**: `ManajerController.php` `dashboardStats`/`archiveStats` (~`:242`, `:263`), invalidasi hanya di `forgetManagerStatsCache` (`:345`) saat approve/destroy.
- Saat petugas tanda tangan laporan (`submitted → acknowledged`), cache manajer **tidak** di-invalidasi → angka "Laporan Pending" bisa telat update sampai TTL 60 detik habis.
- **Dampak**: kecil (angka telat sebentar), bukan bug fungsional.
- **Rekomendasi**: bila ingin real-time, invalidasi cache manajer saat status berubah ke acknowledged, atau biarkan (TTL 60s sudah dapat diterima).

### 🟡 6. PDF approved sisi petugas tidak memakai arsip disk
- **File**: `ReportOpsController::exportPdf` / `renderReportPdf`.
- Laporan approved yang dibuka dari sisi petugas selalu di-generate ulang, sedangkan sisi manajer/admin menyajikan file arsip di disk (`storage/app/public/reports/report-{id}.pdf`).
- **Dampak**: sedikit boros (regenerate), tidak salah secara hasil.
- **Rekomendasi** (opsional): untuk laporan approved, sajikan file arsip disk bila ada, sebelum generate ulang.

### 🟡 7. Celah cakupan test
- **File**: `tests/Feature/OpsFlowTest.php`.
- Belum ada test untuk:
  - `ManajerController::approve()` (transisi `acknowledged → approved` + pembuatan arsip PDF) — ini jalur approval yang sebenarnya aktif.
  - Konfirmasi bahwa cabang approve di `ReportOpsController::sign()` memang tidak dapat dicapai.
- **Rekomendasi**: tambahkan test alur manajer-approve agar jalur kritikal terkunci.

---

## Prioritas saran tindak lanjut

| Prioritas | Temuan | Aksi | Status |
|-----------|--------|------|--------|
| Tinggi | #1 AdminController dead/rusak | Hapus file | Belum |
| Sedang | #2 Dead branch `isAdmin` di ReportOps | Bersihkan (approval = manajer) | ✅ Selesai |
| Sedang | #3 Multi-divisi setengah jadi | Selesaikan atau sembunyikan UI | ⏸️ Ditunda |
| Rendah | #4 Stok inventaris | Implementasi field Jumlah | ✅ Selesai |
| Rendah | #5 Cache stats manajer | Invalidasi saat acknowledged (opsional) | Belum |
| Rendah | #6 PDF approved petugas | Pakai arsip disk (opsional) | Belum |
| Rendah | #7 Test coverage | Tambah test manajer-approve | Belum |

> Catatan: temuan #5–#7 bersifat penyempurnaan, bukan bug. Yang paling layak ditangani lebih dulu adalah #1, #2, dan #3.
