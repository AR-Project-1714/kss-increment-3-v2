# Perancangan Modul Safety/K3 — OpsTrack KSS

> Increment lanjutan setelah **Operasional** (Increment 1) dan **Pemeliharaan** (Increment 2).
> Dokumen ini menjadi acuan implementasi modul **Safety/K3** ke dalam codebase Laravel yang sudah berjalan, sekaligus bahan bab perancangan TA.
> Sumber kebenaran fungsional: `FORM LAPORAN HARIAN KESELAMATAN DAN KESEHATAN KERJA` (Karu Safety, PT Kaltim Satria Samudera).

---

## 0. Posisi & Prinsip yang Diwarisi

Modul Safety **mengikuti pola arsitektur yang sudah mapan** di dua increment sebelumnya, bukan menciptakan pola baru:

| Prinsip | Operasional (`daily_reports`) | Pemeliharaan (`maintenance_reports`) | Safety (`safety_reports`) |
|---|---|---|---|
| Entitas akar tersendiri | ✔ | ✔ | ✔ (wajib — bukan seksi laporan lain) |
| Tabel anak via FK `*_report_id` | ✔ | ✔ | ✔ |
| Master data terpisah | ✔ | ✔ | ✔ |
| Persetujuan dua pihak | grup→grup→manajer | Kasi→manajer | **safety→manajer** |
| Form ter-compose dari seksi | ✔ | ✔ | ✔ |
| Write dibungkus `DB::transaction` | ✔ | ✔ | ✔ |

**Catatan penamaan:** ikuti konvensi paralel yang sudah ada — controller `ReportSafetyController` (mengikuti `ReportOpsController`), folder view `resources/views/report-safety/` (mengikuti `report-ops/`), middleware `role:safety` untuk pembuat dan `role:manajer` untuk pengesah. Samakan istilah teknis dengan modul pemeliharaan bila ada yang sudah baku di sana.

---

## 1. Cross-check Form Fisik → Rancangan (verifikasi terhadap PDF)

Pemetaan setiap bagian form ke skema, beserta temuan yang **mengoreksi asumsi awal**:

| Bagian form | Field/Tabel | Temuan & koreksi |
|---|---|---|
| Header: HARI | diturunkan dari `report_date` | Tidak disimpan redundan. |
| Header: TANGGAL | `safety_reports.report_date` | — |
| Header: JAM KERJA "19:00–03:00" | `safety_reports.time_range` (string bebas) | **Koreksi 1.** Form TIDAK memakai pilihan Shift Pagi/Siang/Malam, dan "19:00–03:00" tidak cocok dengan jam shift operasional. Jadi jam kerja = teks bebas, **bukan** enum shift, dan logika auto-WITA operasional **tidak** dipakai di sini. |
| (tidak ada) | — | **Koreksi 2.** Form safety TIDAK punya Group/Regu maupun Regu Penerima. Tidak ada `group_name`/`received_by_group`. Sistem serah-terima antar regu adalah milik Operasional saja. |
| Tabel inspeksi: LOKASI TEMPAT KERJA | `master_safety_locations` + `safety_inspections.location_id` | 7 lokasi tetap. |
| Tabel inspeksi: ITEM YANG DILAPORKAN | `master_safety_items` + `master_safety_location_items` (template) | Set item **berbeda tiap lokasi** → tabel template (pivot) dibenarkan. |
| Tabel inspeksi: QTY (banyak baris berisi "−") | `safety_inspections.qty` nullable + `master_safety_items.is_countable` | **Koreksi 3 (terkonfirmasi).** Hanya item terhitung (Bangunan, Lampu, AC, APAR, dst.) berangka; sisanya "−" → qty nullable. |
| Tabel inspeksi: KONDISI (BAGUS/RUSAK/NORMAL/TIDAK NORMAL) | `safety_inspections.condition` enum **4 nilai, satu pilihan per baris** | **Koreksi 4 (RESOLUSI).** Pada data nyata, setiap baris hanya dicentang **satu** kolom (semuanya di BAGUS). Tidak ada baris dengan dua centang. Maka secara empiris form berperilaku sebagai **enum tunggal 4-nilai**, bukan dua sumbu biner. Lihat §2.4 untuk pembahasan akademisnya. |
| Tabel inspeksi: REKOMENDASI | `safety_inspections.recommendation` nullable | — |
| Section 8: KEGIATAN OPERASI & PEMELIHARAAN | `safety_operation_logs` | KONDISI di sini berupa teks ("Aman"), **berbeda** dari enum inspeksi → kolom string, bukan enum. Ada baris kosong (➢) → mendukung baris ad-hoc dinamis. |
| Section 9: LAPORAN KEJADIAN & LAIN-LAIN | `safety_incident_logs` | Kosong pada sampel → semua kolom nullable, boleh nol baris. |
| Tanda tangan: "Dilaporkan, Usman Ali — Karu Safety" | `created_by` + `reporter_signature_path` | Pembuat = role `safety`. |
| Tanda tangan: "Mengetahui, Mustari — Manager Ops & K3" | `approved_by` + `approver_signature_path` | Pengesah = role `manajer`. **Separation of Duties** eksplisit. |

**Implikasi status laporan:** karena tidak ada serah-terima antar regu, FSM safety **lebih pendek** dari operasional — hanya `draft → submitted → approved` (tanpa state `acknowledged`). Lihat §3.1.

---

## 2. Skema Database

Tujuh tabel baru: 3 master + 4 transaksi. Tidak ada perubahan pada tabel `users`/`roles` (role `safety` & `manajer` sudah ada).

### 2.1 Tabel Master

```php
// 2026_XX_XX_000001_create_master_safety_locations_table.php
Schema::create('master_safety_locations', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// 2026_XX_XX_000002_create_master_safety_items_table.php
Schema::create('master_safety_items', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->boolean('is_countable')->default(false); // true = QTY relevan
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// 2026_XX_XX_000003_create_master_safety_location_items_table.php
// Template: mendefinisikan item apa yang muncul di lokasi mana
Schema::create('master_safety_location_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('location_id')->constrained('master_safety_locations')->cascadeOnDelete();
    $table->foreignId('item_id')->constrained('master_safety_items')->cascadeOnDelete();
    $table->unsignedSmallInteger('default_qty')->nullable();
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();
    $table->unique(['location_id', 'item_id']);
});
```

### 2.2 Tabel Transaksi

```php
// 2026_XX_XX_000004_create_safety_reports_table.php
Schema::create('safety_reports', function (Blueprint $table) {
    $table->id();
    $table->string('document_number')->nullable()->unique();   // DOC-2026-00X
    $table->date('report_date');
    $table->string('time_range')->nullable();                   // "19:00-03:00" (teks bebas, sesuai form)
    $table->string('shift')->nullable();                        // OPSIONAL — hanya bila manajer butuh filter shift
    $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
    $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();  // role: safety
    $table->string('reporter_signature_path')->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('users');         // role: manajer
    $table->timestamp('approved_at')->nullable();
    $table->string('approver_signature_path')->nullable();
    $table->timestamps();
});

// 2026_XX_XX_000005_create_safety_inspections_table.php
Schema::create('safety_inspections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('safety_report_id')->constrained('safety_reports')->cascadeOnDelete();
    $table->foreignId('location_id')->nullable()->constrained('master_safety_locations')->nullOnDelete();
    $table->foreignId('item_id')->nullable()->constrained('master_safety_items')->nullOnDelete();
    $table->string('location_name_snapshot');   // integritas historis (lihat §2.4)
    $table->string('item_name_snapshot');
    $table->unsignedSmallInteger('qty')->nullable();
    $table->enum('condition', ['bagus', 'rusak', 'normal', 'tidak_normal'])->nullable();
    $table->string('recommendation')->nullable();
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();
});

// 2026_XX_XX_000006_create_safety_operation_logs_table.php  (Section 8)
Schema::create('safety_operation_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('safety_report_id')->constrained('safety_reports')->cascadeOnDelete();
    $table->string('activity_name');             // GRESIK NIAGA, PENGIRIMAN KE GD TURBA, RENTAL ...
    $table->string('condition')->nullable();     // teks: "Aman" (BUKAN enum inspeksi)
    $table->string('action')->nullable();        // Tindakan
    $table->string('notes')->nullable();         // Keterangan: "In Bags" / "Curah"
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();
});

// 2026_XX_XX_000007_create_safety_incident_logs_table.php  (Section 9)
Schema::create('safety_incident_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('safety_report_id')->constrained('safety_reports')->cascadeOnDelete();
    $table->string('description')->nullable();   // Uraian kejadian
    $table->string('condition')->nullable();
    $table->string('action')->nullable();
    $table->string('notes')->nullable();
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();
});
```

### 2.3 Relasi Eloquent

```php
class SafetyReport extends Model
{
    protected $fillable = ['document_number','report_date','time_range','shift','status','created_by'];
    protected $casts = ['report_date' => 'date', 'approved_at' => 'datetime'];

    public function inspections()   { return $this->hasMany(SafetyInspection::class); }
    public function operationLogs() { return $this->hasMany(SafetyOperationLog::class); }
    public function incidentLogs()  { return $this->hasMany(SafetyIncidentLog::class); }
    public function creator()       { return $this->belongsTo(User::class, 'created_by'); }
    public function approver()      { return $this->belongsTo(User::class, 'approved_by'); }
}

class SafetyInspection extends Model
{
    public function report()   { return $this->belongsTo(SafetyReport::class, 'safety_report_id'); }
    public function location() { return $this->belongsTo(MasterSafetyLocation::class, 'location_id'); }
    public function item()     { return $this->belongsTo(MasterSafetyItem::class, 'item_id'); }
}

class MasterSafetyLocation extends Model
{
    public function items() {
        return $this->belongsToMany(MasterSafetyItem::class, 'master_safety_location_items', 'location_id', 'item_id')
                    ->withPivot('default_qty', 'sort_order')->orderBy('pivot_sort_order');
    }
}
```

### 2.4 Catatan Desain Wajib Dipahami

**Kolom `condition` — satu enum, bukan dua sumbu.** Form fisik menampilkan empat kolom yang secara konseptual berpasangan (fisik: `bagus`/`rusak`; fungsi: `normal`/`tidak_normal`). Namun pada data nyata setiap baris hanya dicentang satu kolom — tidak ada item yang dinilai di dua sumbu sekaligus. Maka model yang **setia pada penggunaan aktual** adalah enum tunggal 4-nilai. Di bab pembahasan TA, ini layak diangkat: form mengandung *ambiguitas desain* (empat nilai pada satu sumbu yang mencampur dimensi fisik dan fungsional), dan sistem digital menyederhanakannya menjadi pilihan tunggal eksplisit. Jika di kemudian hari Pak Mustari mengonfirmasi penilaian dua sumbu benar-benar dibutuhkan, migrasi ke `physical_condition` + `functional_condition` bersifat aditif dan tidak merusak data lama.

**`*_name_snapshot` = integritas temporal.** Codebase belum memakai soft-delete (hapus permanen). Jika nama lokasi/item master diubah/dihapus setelah laporan dibuat, laporan historis akan ikut berubah bila hanya bergantung FK. Menyimpan snapshot nama saat insert menjaga laporan lama tetap akurat. FK tetap dipertahankan untuk analitik; snapshot untuk tampilan/PDF historis.

**`qty` & `condition` nullable.** Sesuai form: banyak item bertanda "−" (qty kosong) dan beberapa baris template bisa tidak dinilai (kondisi kosong).

---

## 3. Logika Laporan Safety

### 3.1 Mesin Status (FSM)

```
draft  ──submit──▶  submitted  ──approve (manajer)──▶  approved
  ▲                     │
  └──── edit/simpan ─────┘
```

| Status | Arti | Aktor pemicu |
|---|---|---|
| `draft` | Tersimpan sebagian, belum dikirim | safety |
| `submitted` | Dikirim, menunggu pengesahan manajer | safety |
| `approved` | Disahkan manajer, PDF di-cache | manajer |

Kontras dengan Operasional yang punya state `acknowledged` (tanda tangan regu penerima): **Safety tidak punya state ini** karena tidak ada serah-terima antar regu. Implementasikan FSM sebagai PHP 8.1 Backed Enum agar transisi eksplisit dan dapat diuji (selaras rencana refactor FSM operasional).

*Opsional (perlu konfirmasi Pak Mustari):* state `rejected`/revisi bila manajer dapat menolak laporan. Bila tidak dibutuhkan, jangan ditambahkan agar FSM tetap sederhana.

### 3.2 Separation of Duties (poin novelty TA)

Aturan kuncinya: **pembuat ≠ pengesah**. Role `safety` membuat dan mengirim; hanya role `manajer` yang dapat mengubah status ke `approved` dan membubuhkan tanda tangan pengesah. Tegakkan via Policy/Gate, bukan `if` tersebar:

```php
// SafetyReportPolicy
public function approve(User $user, SafetyReport $report): bool {
    return $user->role->name === 'manajer' && $report->status === 'submitted';
}
public function update(User $user, SafetyReport $report): bool {
    return $user->id === $report->created_by && $report->status !== 'approved';
}
```

### 3.3 Aturan Bisnis

- **Form digenerate dari template.** Saat membuka form baru, baris inspeksi di-*seed* dari `master_safety_location_items` (lokasi → item + default_qty + urutan). Petugas tinggal menilai, bukan mengetik ulang daftar.
- **"Set Semua Bagus".** Aksi bulk men-set `condition='bagus'` untuk semua baris lintas lokasi (meniru "Set Semua Baik" di Cek Unit operasional).
- **Baris ad-hoc.** Petugas boleh `+ Tambah Item` per lokasi, `+ Tambah Lokasi`, dan menambah baris di Section 8 & 9 (form fisik menyediakan baris kosong ➢).
- **Guard input angka.** Reuse helper operasional: `min=0`, blok `-`/`e`/`E`, cegah negatif via paste, cegah scroll mengubah nilai.
- **Tidak ada akumulasi 3-state.** Pola `qty_current/prev/accumulated` adalah milik Operasional. Jangan dibawa ke Safety — `qty` di sini hanya nilai tunggal hitungan fisik.

### 3.4 Penyimpanan (Store/Update)

Bungkus seluruh insert/update dalam satu `DB::transaction`. Pola payload array dinamis selaras operasional:

```
inspeksi[lokasiIndex][itemIndex][location_id|item_id|qty|condition|recommendation]
kegiatan[i][activity_name|condition|action|notes]
kejadian[i][description|condition|action|notes]
```

Pada store: buat `SafetyReport`, lalu loop tiap array → isi `*_name_snapshot` dari master saat insert. Pada update laporan `draft`/`submitted`: hapus baris anak lama lalu insert ulang (pola sederhana yang konsisten dengan operasional), atau diff bila ingin lebih hemat.

### 3.5 PDF

Reuse pipeline DomPDF + cache yang sudah ada. Template `resources/views/report-safety/pdf.blade.php` mereplikasi tata letak form K3 resmi (kop, tabel inspeksi per lokasi, Section 8, Section 9, dua blok tanda tangan). Generate & cache saat manajer approve → `storage/app/public/reports/`.

---

## 4. Interface

Struktur view paralel dengan `report-ops/`:

```
resources/views/report-safety/
├── index.blade.php                 # Daftar: Draft + Riwayat
├── create.blade.php                # Form bertahap
├── edit.blade.php
├── show.blade.php                  # Lihat detail (read-only)
├── pdf.blade.php                   # Template DomPDF
├── viewpdf.blade.php
├── sections/
│   ├── step1-infoumum.blade.php
│   ├── step2-inspeksi.blade.php
│   ├── step3-kegiatan.blade.php
│   └── step4-kejadian.blade.php
└── layouts/
    ├── app.blade.php               # reuse pola layout/toast/guard operasional
    └── navbar.blade.php
```

### 4.1 Halaman Index — "Laporan Keselamatan (K3)"

- Header: judul + subjudul, tombol **Buat Laporan** (biru) kanan atas.
- **Dua tab saja: Draft | Riwayat Laporan.** Tidak ada tab "Laporan Masuk" (tidak ada serah-terima).
- Tab Draft: kartu draft (badge Draft, judul, ID dokumen, "terakhir diedit"), tombol Lanjutkan Edit + hapus.
- Tab Riwayat: tabel `No | Info Dokumen | Tanggal | Jam Kerja | Status | Aksi`. Status badge: Draft/Dikirim/Disahkan. Aksi: Lihat + Edit. (Catatan: kolom **Jam Kerja**, bukan Shift, mengikuti form.)

### 4.2 Form Bertahap — "Form Laporan Harian K3" (4 langkah, badge "Form X dari 4")

Elemen tetap: tombol **Simpan Sebagai Draft** (oranye) kanan atas; bar tab langkah (pill biru aktif); bottom bar `× Batalkan` (kiri) + `Kembali`/`Lanjut >` (kanan), berubah jadi **Kirim** di langkah 4.

- **Langkah 1 — Info Umum:** Hari/Tanggal (date native, auto hari ini), **Jam Kerja (teks bebas, mis. "19:00–03:00")**. Tanpa Shift dropdown, tanpa Group/Regu.
- **Langkah 2 — Inspeksi K3 [inti]:** accordion per lokasi (buka/tutup), tiap accordion = tabel `No | Item | QTY | Kondisi | Rekomendasi | Hapus`. Kondisi = segmented control 4 opsi (pilih satu). Tombol hijau **Set Semua Bagus** di kanan atas seksi. `+ Tambah Item` per lokasi, `+ Tambah Lokasi` di bawah.
- **Langkah 3 — Kegiatan Operasi & Pemeliharaan:** tabel dinamis `No | Kegiatan | Kondisi | Tindakan | Keterangan | Hapus` + Tambah Baris. Boleh diseed dengan entri umum (Gresik Niaga, Golden Rejeki, Pengiriman ke GD Turba, Rental Unit PP&P, Rental TRL PT.KAD, Rental FL OP6 & OP7).
- **Langkah 4 — Kejadian & Lain-lain:** tabel dinamis `No | Uraian Kejadian | Kondisi | Tindakan | Keterangan | Hapus` + Tambah Baris. Boleh kosong.

### 4.3 Sisi Manajer

Sambungkan tab **"Safety/K3"** yang sudah ada di dashboard manajer ke `safety_reports`. Tambahkan halaman detail + modal **Sahkan & Tanda Tangan** (reuse mekanisme tanda tangan PNG operasional). Saat approve: set `approved_by`, `approved_at`, `approver_signature_path`, generate PDF.

### 4.4 Sisi Admin

Tambahkan ke Data Master: **Lokasi K3**, **Item K3**, dan pengelolaan **template (lokasi ↔ item)**. Arsip admin menyertakan laporan safety (tanpa tombol approve — approval hanya hak manajer).

---

## 5. Routes & Controller

```php
// Pembuat (role: safety)
Route::middleware(['auth', 'role:safety'])->prefix('report-safety')->group(function () {
    Route::get('/',               [ReportSafetyController::class, 'history'])->name('safety.index');
    Route::get('/create',         [ReportSafetyController::class, 'create'])->name('safety.create');
    Route::post('/store',         [ReportSafetyController::class, 'store'])->name('safety.store');
    Route::get('/{id}/edit',      [ReportSafetyController::class, 'edit'])->name('safety.edit');
    Route::put('/{id}',           [ReportSafetyController::class, 'update'])->name('safety.update');
    Route::get('/{id}',           [ReportSafetyController::class, 'show'])->name('safety.show');
    Route::get('/{id}/export-pdf',[ReportSafetyController::class, 'exportPdf'])->name('safety.pdf');
});

// Pengesah (role: manajer) — tambahkan pada grup manajer yang sudah ada
Route::get('/manajer/safety/{id}',         [ManajerController::class, 'safetyShow']);
Route::post('/manajer/safety/{id}/approve',[ManajerController::class, 'safetyApprove']);
```

Method controller: `history / create / store / edit / update / show / exportPdf` (paralel `ReportOpsController`), semua write `DB::transaction`, otorisasi via `SafetyReportPolicy`.

---

## 6. Seeder Data (sesuai PDF — untuk akurasi)

**Katalog item** (`is_countable`): Bangunan ✔, Lampu/Pencahayaan ✔, AC ✔, APAR ✔, Kotak P3K ✔, Papan Informasi ✔, Mesin Check O'clock ✔, Exaus Fan ✔; Kebersihan ✘, Kerapian ✘, Instalasi Listrik ✘, Instalasi Air ✘.

**Template lokasi → item** (persis dari form):

| Lokasi | Item |
|---|---|
| Shelter Shift Operasi | Bangunan, Kebersihan, Kerapian, Instalasi Listrik, Lampu/Pencahayaan, Instalasi Air, AC, APAR, Kotak P3K, Papan Informasi, Mesin Check O'clock |
| Work Shop dan Sekitarnya | Bangunan, Kebersihan, Kerapian, Instalasi Listrik, Lampu/Pencahayaan, Instalasi Air, APAR, Kotak P3K |
| Shelter Mekanik dan Sekitarnya | Bangunan, Kebersihan, Kerapian, Instalasi Listrik, Lampu/Pencahayaan, Instalasi Air, AC, APAR |
| Shelter Karu Peralatan | Bangunan, Kebersihan, Kerapian, Instalasi Listrik, Lampu/Pencahayaan, APAR |
| Kontainer Peralatan Bongkar Muat | Bangunan, Kebersihan, Kerapian, Instalasi Listrik, Lampu/Pencahayaan, APAR |
| Gudang Spare Part | Bangunan, Kebersihan, Kerapian, Instalasi Listrik, Lampu/Pencahayaan, APAR, Exaus Fan |
| Shelter Operasi di Tursina dan Sekitarnya | Bangunan, Kebersihan, Kerapian, Instalasi Listrik, Lampu/Pencahayaan, AC, APAR, Kotak P3K, Mesin Check O'clock |

---

## 7. Checklist Implementasi

- [ ] 7 migrasi dibuat sesuai §2 (urutan: master dulu, baru transaksi).
- [ ] Seeder lokasi, item, dan template diisi sesuai §6.
- [ ] Model + relasi Eloquent (§2.3); FSM sebagai Backed Enum.
- [ ] `SafetyReportPolicy` (SoD: pembuat ≠ pengesah) terdaftar.
- [ ] `ReportSafetyController` + route `role:safety`; store/update `DB::transaction`.
- [ ] View `report-safety/` reuse layout/toast/guard angka operasional.
- [ ] Form generate baris dari template; "Set Semua Bagus" berfungsi.
- [ ] Tab Safety/K3 manajer tersambung; modal approve + tanda tangan.
- [ ] Data Master admin: Lokasi/Item/Template K3.
- [ ] Template PDF K3 + cache saat approve.
- [ ] Black-Box test + UAT untuk alur create→submit→approve.

## 8. Asumsi Terbuka (konfirmasi ke Pak Mustari sebelum final)

1. **Kondisi**: dipakai sebagai enum tunggal 4-nilai (default rancangan). Konfirmasi apakah penilaian dua sumbu (fisik + fungsi sekaligus) benar-benar dibutuhkan.
2. **Kardinalitas**: apakah boleh lebih dari satu laporan safety per hari (mis. shift siang & malam)? Jika ya, **jangan** pasang `unique(report_date)`. Rancangan saat ini mengizinkan banyak laporan/hari.
3. **Field `shift`**: dipertahankan nullable hanya bila manajer butuh filter per shift; jika tidak, boleh dihapus karena form tidak memilikinya.
4. **Revisi/penolakan**: apakah manajer perlu menolak laporan (state `rejected`)? Default: tidak ada.
5. **Section 8 vs data operasional**: entri seperti "Pengiriman ke GD Turba" beririsan dengan data operasional. Untuk lingkup TA, input ulang manual dapat diterima — **catat sebagai keterbatasan sistem** di bab pembahasan.
