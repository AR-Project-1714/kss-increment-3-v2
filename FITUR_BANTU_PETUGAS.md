# Fitur yang Membantu Petugas Lapangan — Sistem Laporan KSS

Dokumen ini merangkum fitur-fitur "pintar" pada aplikasi yang dibuat khusus untuk
**meringankan pekerjaan petugas/karu lapangan** saat membuat laporan — mengurangi
ketik ulang, mencegah kehilangan data, dan menjaga kesinambungan data antar shift.

Setiap fitur disertai: **apa fungsinya**, **manfaat di lapangan**, **cara kerja
teknis**, dan **lokasi di kode** agar mudah ditelusuri.

> Ringkasan cepat — berlaku di modul mana:
>
> | Fitur | Operasional | Pemeliharaan | Safety |
> |---|:---:|:---:|:---:|
> | Autosave draft | ✅ | ✅ | ✅ |
> | Autosave offline (localStorage) | ✅ | ✅ | ✅ |
> | Draft manual + auto-hapus 3 hari | ✅ | ✅ | ✅ |
> | Badge sisa umur draft + Perpanjang | ✅ | ✅ | ✅ |
> | Guard laporan ganda | ✅ | ✅ | ✅ |
> | Panel "Intip Laporan Sebelumnya" | ✅ | ✅ | ✅ |
> | Cache aset + fallback offline | ✅ | ✅ | ✅ |
> | Form bertahap (wizard tab) | ✅ | ✅ | ✅ |
> | Integrasi Data Master + cache | ✅ | ✅ | ✅ |
> | Export PDF / Excel | ✅ | ✅ (PDF) | ✅ (PDF) |
> | Serah terima antar regu + tanda tangan | ✅ | — | — |
> | Sinkronisasi Cek Unit antar shift | ✅ | — | — |
> | "Set Semua Baik" | ✅ | — | — |
> | Ship Operation (registri kapal pintar + arsip) | ✅ | — | — |
> | Akumulasi muat kantong otomatis | ✅ | — | — |
> | Saran/autocomplete pencarian | ✅ | — | — |
> | Carry-over pekerjaan belum selesai | — | ✅ | — |

---

## 1. Autosave Draft — laporan tidak hilang walau sesi putus

**Fungsi.** Laporan yang sedang diisi otomatis tersimpan sebagai **Draft** tanpa
perlu menekan tombol apa pun.

**Manfaat di lapangan.** Petugas tidak kehilangan pekerjaan ketika:
- sesi login habis (timeout),
- tombol **Logout** tertekan tidak sengaja,
- tab/browser ditutup atau berpindah aplikasi,
- koneksi/laptop bermasalah di tengah pengisian.

**Cara kerja.**
- Menyimpan **berkala tiap 30 detik** (`AUTOSAVE_INTERVAL_MS`), tetapi hanya bila
  ada perubahan (`dirty`) — draft kosong tidak dibuat tanpa interaksi nyata.
- Saat menutup tab / pindah aplikasi memakai `navigator.sendBeacon` (`pagehide`,
  `visibilitychange`) agar simpan tetap terkirim walau halaman ditutup.
- Saat menekan **Logout**, draft disimpan dulu (maks. tunggu 2 detik) baru proses
  logout dilanjutkan.
- Permintaan autosave **selalu dipaksa berstatus `draft`** dan menyetel flag
  `autosave=1`, jadi tidak akan menaikkan/menurunkan status laporan secara
  tak sengaja.
- Setelah draft pertama dibuat, server mengembalikan `update_url`; form lalu
  mengarah ke draft itu sehingga simpan berikutnya **memperbarui draft yang sama
  (tidak menduplikat)**.
- Indikator: pil kecil di atas-tengah layar — berputar (spinner) lalu berubah jadi
  centang **"Laporan tersimpan"**.

**Lokasi kode.**
- Front-end: `resources/views/partials/report-autosave.blade.php`
- Konvensi server: `app/Http/Controllers/Concerns/AutosavesDraftReports.php`
  (`isAutosaveRequest`, `autosaveResponse`)
- Dipakai di ketiga modul: `report-ops`, `pemeliharaan`, `report-safety`
  (lihat `partials/report-form.blade.php` masing-masing).

---

## 2. Draft Manual + Pembersihan Otomatis 3 Hari

**Fungsi.** Selain autosave, petugas bisa menekan **Simpan Sebagai Draft** untuk
menyimpan dan melanjutkan nanti. Draft tampil di tab **Draft**.

**Manfaat di lapangan.** Bisa mengisi laporan bertahap (mis. menunggu data shift
selesai) tanpa harus langsung menyerahkan.

**Cara kerja.**
- Draft yang **lebih dari 3 hari** tidak dilanjutkan akan **dihapus otomatis**
  (`DRAFT_TTL_DAYS = 3`) agar daftar draft tidak menumpuk. Pembersihan dijalankan
  saat membuka halaman buat/edit.
- Jika petugas mencoba membuka draft yang sudah kedaluwarsa, sistem memberi pesan
  bahwa draft telah dihapus otomatis.

**Lokasi kode.**
- `app/Models/DailyReport.php` → `DRAFT_TTL_DAYS`, `pruneStaleDrafts()`
- `ReportOpsController.php` → `pruneStaleDraftReports()`, `deleteIfStaleDraft()`,
  `isStaleDraft()`, `draftExpiryCutoff()`

---

## 3. Serah Terima Antar Regu + Tanda Tangan (Operasional)

**Fungsi.** Laporan operasional dibuat satu regu lalu **diserahkan ke regu
penerima**. Regu penerima meninjau dan **menandatangani** untuk menerima, baru
diteruskan ke manajer untuk pengesahan akhir.

**Manfaat di lapangan.** Proses serah terima shift terdokumentasi dan berjenjang:
jelas siapa membuat, siapa menerima, dan kapan.

**Cara kerja (alur status).**
`Draft → Diserahkan (submitted) → Diterima (acknowledged, oleh regu tujuan) →
Diarsipkan (approved, oleh manajer)`
- Saat regu penerima menandatangani, sistem mencatat `received_by_user_id` dan
  `received_at`, status menjadi **Diterima**.
- Pengaman: regu **tidak bisa** menandatangani laporan yang ditujukan ke regu lain
  (`canReceiveReport`).
- Halaman utama operasional punya tab **Laporan Masuk**, **Draft**, dan
  **Riwayat Laporan**.

**Lokasi kode.**
- `ReportOpsController.php` → `sign()`, `canReceiveReport()`, `canAccessReport()`
- View: `resources/views/report-ops/index.blade.php`

---

## 4. Sinkronisasi Cek Unit Antar Shift

**Fungsi.** Pada langkah **Cek Unit**, kolom **Kondisi Terima** untuk shift baru
otomatis terisi dari **Kondisi Serah** shift sebelumnya (untuk unit, inventaris,
dan lingkungan shelter).

**Manfaat di lapangan.** Petugas tidak perlu mengingat/menyalin kondisi unit dari
laporan sebelumnya — kesinambungan kondisi alat antar shift terjaga otomatis dan
mengurangi selisih data serah terima.

**Cara kerja.**
- Server mengumpulkan **kondisi serah terakhir** (`condition_handed_over`) dari
  laporan berstatus Diserahkan/Diterima/Diarsipkan, dipetakan per **ID master**
  dan per **nama item** (`lastUnitHandoverConditions`).
- Di form, fungsi `previousHandoverCondition()` mencocokkan tiap unit dengan data
  tersebut dan mengisi **Kondisi Terima** otomatis.
- `syncHandedOverWithReceived()` membuat **Kondisi Serah** mengikuti **Kondisi
  Terima** secara otomatis — kecuali petugas sudah mengubahnya manual
  (`data-userAdjusted`), agar input manual tidak tertimpa.

**Lokasi kode.**
- Server: `ReportOpsController.php` → `lastUnitHandoverConditions()`,
  `storeUnitChecks()`
- Front-end: `resources/views/report-ops/partials/report-form.blade.php`
  (`previousHandoverCondition`, `syncHandedOverWithReceived`,
  `applyPreviousShelterConditions`)
- View langkah: `resources/views/report-ops/sections/step6-cekunit.blade.php`

---

## 5. "Set Semua Baik" — input cek unit sekali klik

**Fungsi.** Tombol **Set Semua Baik** menandai seluruh baris cek unit sebagai
"Baik" sekaligus.

**Manfaat di lapangan.** Bila mayoritas unit kondisinya baik (kasus paling umum),
petugas cukup satu klik lalu hanya mengubah item yang bermasalah — jauh lebih cepat
daripada mencentang satu per satu.

**Lokasi kode.**
- `resources/views/report-ops/partials/report-form.blade.php` → `setAllGood()`
- `resources/views/report-ops/sections/step6-cekunit.blade.php` (tombol
  `.set-all-good`)

---

## 6. Ship Operation — registri kapal pintar + autofill (Operasional)

**Fungsi.** Data operasi kapal (muat kantong/curah) disimpan sebagai entitas
**Ship Operation** yang bisa **dipilih ulang** di laporan berikutnya. Memilih saran
kapal akan **mengisi otomatis** seluruh data kapal (nama kapal, agen, jetty,
tujuan, kapasitas, No. WO, jenis muatan, marking, stevedoring, komoditas, serta
waktu kedatangan/sandar/mulai muat).

**Manfaat di lapangan.** Operasi muat satu kapal biasanya berlangsung beberapa
shift/hari. Petugas tidak perlu mengetik ulang detail kapal yang sama tiap laporan —
cukup pilih dari saran.

**Cara kerja.**
- Endpoint saran `report-ops/ship-operations/suggestions` mengembalikan kapal yang
  masih **aktif** (maks. 8, terbaru dulu), dengan pencarian pada nama kapal, agen,
  jetty, tujuan, No. WO, jenis/komoditas muatan, dan marking.
- Saat laporan disimpan, `resolveShipOperation()` mencocokkan kapal yang sudah ada
  (berdasarkan ID, atau nama kapal + No. WO/komoditas) lalu memperbaruinya;
  status bisa ditandai **Selesai** ketika operasi rampung.
- Saran kapal aktif yang **tidak diperbarui > 3 hari** dibersihkan otomatis
  (`ACTIVE_SUGGESTION_TTL_DAYS = 3`).

**Lokasi kode.**
- Model: `app/Models/ShipOperation.php`
- Controller: `ReportOpsController.php` → `shipOperationSuggestions()`,
  `shipOperationSuggestionItem()`, `resolveShipOperation()`,
  `pruneStaleShipOperations()`

---

## 7. Akumulasi Muat Kantong Otomatis (Operasional)

**Fungsi.** Untuk muat kantong, sistem menjumlahkan kuantitas muat dari
laporan-laporan sebelumnya pada kapal yang sama, dan menampilkannya sebagai
**akumulasi sebelumnya**.

**Manfaat di lapangan.** Petugas langsung tahu **total terkumpul** (delivery,
loading, damage) dari shift-shift terdahulu tanpa menghitung manual, sehingga
progres muat per kapal mudah dipantau dan sisa kapasitas lebih akurat.

**Cara kerja.**
- `shipOperationAccumulation()` menjumlahkan `qty_delivery_current`,
  `qty_loading_current`, `qty_damage_current` dari seluruh aktivitas muat kapal
  tersebut (mengecualikan laporan yang sedang diedit), menghasilkan
  `qty_delivery_prev`, `qty_loading_prev`, `qty_damage_prev`.

**Lokasi kode.**
- `ReportOpsController.php` → `shipOperationAccumulation()` (dipakai dalam
  `shipOperationSuggestionItem()`).

---

## 8. Saran / Autocomplete Pencarian Laporan (Operasional)

**Fungsi.** Kotak pencarian pada tab Riwayat dan Laporan Masuk memberi **saran
laporan** secara langsung saat mengetik.

**Manfaat di lapangan.** Cepat menemukan laporan tertentu (berdasarkan ID, tanggal,
shift, regu, status) tanpa menelusuri tabel panjang.

**Cara kerja.**
- **Riwayat** (`historySuggestions`): dibatasi pada laporan yang dibuat oleh regu
  pengguna sendiri.
- **Laporan Diterima** (`receivedSuggestions`): dibatasi pada laporan yang ditujukan
  ke regu pengguna.
- Keduanya mengembalikan maks. 8 saran dengan metadata + tautan **Lihat / PDF /
  Excel**.

**Lokasi kode.**
- `ReportOpsController.php` → `historySuggestions()`, `receivedSuggestions()`,
  `reportSuggestionItem()`

---

## 9. Integrasi Data Master + Cache (semua modul)

**Fungsi.** Pilihan **karyawan, unit, truck, inventaris, lokasi & item K3** pada
form berasal dari Data Master — petugas memilih dari daftar, bukan mengetik bebas.

**Manfaat di lapangan.** Konsisten (nama/kode seragam), lebih cepat, dan minim salah
ketik. Form tetap ringan dibuka karena data master di-cache.

**Cara kerja.**
- Data master di-cache hingga **24 jam** (`MASTER_DATA_CACHE_TTL = 60*60*24`).
- Cache **otomatis di-reset** saat data master diubah/dihapus admin
  (trait `InvalidatesMasterDataCache` pada model master).
- Unit untuk "Cek Unit" mengikuti urutan & kategori dari Data Master
  (`orderedForReport`).

**Lokasi kode.**
- `ReportOpsController.php` → `masterData()`
- `app/Models/Concerns/InvalidatesMasterDataCache.php`
- Model master: `MasterUnit`, `MasterInventoryItem`, `MasterTruck`,
  `MasterEmployee`, dll.

---

## 10. Form Bertahap (Wizard Tab) — semua modul

**Fungsi.** Form laporan dibagi menjadi langkah-langkah bertab dengan tombol
**Lanjut / Kembali**, plus header **sticky** berisi tombol Simpan Draft.

**Manfaat di lapangan.** Pengisian terstruktur dan tidak membingungkan; petugas
fokus satu bagian per layar. Bisa berpindah antar langkah lewat tab maupun tombol.

**Langkah per modul.**
- **Operasional (7 bagian):** Info Umum → Muat Kantong → Muat Curah → Bongkar →
  Tracking (Gudang & Turba) → Cek Unit → Karyawan.
- **Pemeliharaan (5 bagian):** Info Umum → Pekerjaan Utama → Pekerjaan Prioritas →
  Kondisi Unit → Daftar Hadir.
- **Safety/K3 (4 bagian):** Info Umum → Inspeksi K3 → Kegiatan → Kejadian.

**Lokasi kode.**
- `resources/views/report-ops/partials/report-form.blade.php` (+ `sections/`)
- `resources/views/pemeliharaan/partials/report-form.blade.php`
- `resources/views/report-safety/partials/report-form.blade.php`

---

## 11. Export PDF & Excel + Cache PDF (Operasional)

**Fungsi.** Laporan dapat diunduh sebagai **PDF** (ketiga modul) dan **Excel**
(operasional).

**Manfaat di lapangan.** Mudah diarsip/dicetak/dibagikan luar sistem. Unduhan
berikutnya lebih cepat karena PDF yang sudah dibuat di-cache.

**Cara kerja.**
- PDF laporan yang belum final di-cache sementara (`pendingPdfCacheKey`,
  `PENDING_PDF_CACHE_TTL`) — unduhan pertama membuat file, berikutnya memakai
  cache.
- Excel diisi per bagian (info umum, muat kantong/curah, material & kontainer,
  turba, unit, karyawan) memakai template.

**Lokasi kode.**
- `ReportOpsController.php` → `exportPdf()`, `renderReportPdf()`,
  `pendingPdfCacheKey()`, `exportExcel()` dan fungsi `fillExcel*`.

---

## 12. Autosave Offline — localStorage fallback (semua modul)

**Fungsi.** Bila jaringan putus saat autosave, seluruh isian form disimpan ke
`localStorage` browser (pil menampilkan **"Tersimpan offline — sinkron saat
online"**), lalu otomatis dikirim ulang ke server begitu koneksi kembali atau
saat halaman form berikutnya dibuka.

**Lokasi kode.** `resources/views/partials/report-autosave.blade.php`
(`storeOfflineDraft`, `syncOfflineDrafts`, `OFFLINE_KEY_PREFIX`).

## 13. Badge Sisa Umur Draft + Tombol Perpanjang (semua modul)

**Fungsi.** Tab Draft menampilkan chip **"Terhapus otomatis dalam X hari/jam"**
(merah bila < 24 jam) beserta tombol **Perpanjang** yang me-reset hitungan masa
simpan 3 hari — draft tidak lagi terhapus diam-diam.

**Lokasi kode.** `resources/views/partials/draft-expiry.blade.php`; endpoint
`extendDraft()` di ketiga controller laporan (route `*.extend-draft`).

## 14. Guard Laporan Ganda (semua modul)

**Fungsi.** Submit final ditolak bila sudah ada laporan terkirim untuk
**tanggal + shift + regu** yang sama (Operasional) atau **tanggal** yang sama
(Pemeliharaan/Safety), sehingga data satu periode tidak terpecah dua. Draft
tidak dibatasi.

**Lokasi kode.** Closure rule pada `report_date` di `rules()` masing-masing
controller laporan.

## 15. Panel "Intip Laporan Sebelumnya" (semua modul)

**Fungsi.** Tombol melayang di form laporan membuka drawer berisi laporan
periode sebelumnya (untuk Operasional: laporan non-draft terakhir yang dibuat/
diserahkan ke regu user) tanpa keluar dari form — memudahkan mencocokkan data
lanjutan.

**Lokasi kode.** `resources/views/partials/report-peek.blade.php`; metode
`previousReportPeek()` di ketiga controller laporan.

## 16. Ship Operation Diarsipkan, Bukan Dihapus (Operasional)

**Fungsi.** Kapal aktif yang tidak diperbarui > 3 hari kini **diarsipkan**
(status `inactive`), bukan dihapus. Kapal terarsip tetap muncul di pencarian
saran (chip **"Diarsipkan"**) dan otomatis aktif kembali saat dipakai — akumulasi
muat tidak putus walau operasi jeda (cuaca/antrean jetty).

**Lokasi kode.** `ShipOperation::pruneStaleActiveSuggestions()`,
`ReportOpsController::shipOperationSuggestions()`, `resolveShipOperation()`.

## 17. Carry-over Pekerjaan Belum Selesai (Pemeliharaan)

**Fungsi.** Pekerjaan Prioritas berstatus "Belum" dari laporan terkirim terakhir
otomatis dimuat sebagai baris awal laporan baru, dengan keterangan
**"Lanjutan dari [tanggal]"** dan banner informasi — pekerjaan lanjutan tidak
hilang antar hari kerja.

**Lokasi kode.** `ReportMaintenanceController::unfinishedPriorityItems()`;
blok carry-over di `resources/views/pemeliharaan/partials/report-form.blade.php`.

## 18. Cache Aset & Fallback Offline (seluruh aplikasi)

**Fungsi.** Aset statis di-cache service worker sehingga kunjungan berikutnya
jauh lebih ringan, dan saat benar-benar offline tampil halaman fallback yang
menjelaskan bahwa isian tersimpan di perangkat (lihat [12] Autosave Offline).

**Catatan.** Aplikasi sengaja **tidak** dibuat installable sebagai PWA
standalone. Alur tinjau/cetak/unduh PDF memakai `target="_blank"` di banyak
tempat; pada mode standalone tab baru terpaksa dibuka sebagai window terpisah,
yang justru mengganggu petugas. Service worker tidak memerlukan mode standalone,
jadi cache dan fallback offline tetap didapat penuh lewat tab browser biasa.

**Lokasi kode.** `public/sw.js`, `public/offline.html`,
`resources/views/partials/offline-support.blade.php` (di-include di semua
layout).

## Catatan untuk Tim

- Fitur **autosave** bersifat *best-effort* (mengandalkan jaringan); pesan
  **"Laporan tersimpan"** hanya muncul bila simpan benar-benar berhasil.
- Nilai-nilai ambang (interval 30 dtk, TTL draft 3 hari, TTL saran kapal 3 hari,
  cache master 24 jam) didefinisikan sebagai konstanta — mudah disesuaikan bila
  kebijakan operasional berubah.
- Untuk panduan langkah penggunaan per peran, lihat `PANDUAN_PENGGUNA.md`.

---

*Disusun dari hasil analisis kode Sistem Laporan KSS.*
