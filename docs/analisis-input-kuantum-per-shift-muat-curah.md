# Analisis: Input Kuantum per Shift vs Maksud Pak Mustari

**Konteks pertanyaan client (Pak Mustari):**
> Agar total kuantum pada laporan tidak salah, data yang dimasukkan pada setiap shift harus berupa jumlah yang dikerjakan pada shift tersebut ("jumlah sekarang"), bukan jumlah akumulasi dari shift sebelumnya. Sistem yang menjumlahkan otomatis menjadi total.

Contoh yang beliau berikan: Shift 1 muat 100 ton → input 100. Shift 2 muat 80 ton → input 80 (bukan 180). Sistem menjumlahkan otomatis → 180.

## Kesimpulan singkat

Sistem **sudah benar** untuk modul **Pemuatan Pupuk Kantong**, tapi **belum konsisten** untuk modul **Muat Curah (urea) dan Muat Amoniak** — dan justru di dua modul curah/amoniak inilah kemungkinan besar sumber laporan yang "salah total" yang dikeluhkan.

---

## 1. Modul Pemuatan Pupuk Kantong — SUDAH sesuai maksud Pak Mustari

File: `resources/views/report-ops/sections/step2-muatkantong.blade.php` (baris 128–158)

Setiap shift punya 2 kolom berdampingan:
- **"Sekarang"** (`qty_loading_current`, dst.) → operator mengisi jumlah yang dikerjakan **pada shift itu saja**.
- **"Lalu"** (`qty_loading_prev`, dst.) → **otomatis terisi** dari akumulasi shift sebelumnya (lihat `ReportOpsController::shipOperationAccumulation()`, baris 1845–1877), masih bisa diedit manual bila perlu.

Total yang ditampilkan di dashboard kinerja dihitung sistem dengan menjumlahkan kolom "Sekarang" langsung:
```php
// app/Services/OperationalPerformanceService.php:95, 2010
SUM(loading_activities.qty_loading_current)
```

Ini persis pola yang diminta Pak Mustari: input = jumlah shift itu saja, sistem yang menjumlahkan.

---

## 2. Modul Muat Curah & Muat Amoniak — mekanisme COB memang atas permintaan Pak Mustari, tapi implementasinya belum lengkap

File: `resources/views/report-ops/sections/step3-muatcurah.blade.php`, `step4-muatamoniak.blade.php`
Controller: `app/Http/Controllers/ReportOpsController.php` (`storeBulkLoadingActivities()`, baris 1518–1589)

**Catatan penting:** field `cob_received` ("COB Diterima"), `cob_delivered` ("COB Diserahkan"), dan `loading_qty` ("Jumlah Pemuatan" = Diserahkan − Diterima) memang dibuat atas permintaan Pak Mustari sendiri — bukan bug/kesalahan desain. `loading_qty` ini **sudah** merupakan delta/incremental per shift, sesuai maksud "jumlah sekarang". Jadi bagian ini tidak perlu diubah pola dasarnya.

Yang masih jadi celah adalah **implementasinya belum menyamai kelengkapan pola pupuk kantong**, di 2 titik:

### Celah A — Tidak ada auto-carry-forward
Di pupuk kantong, kolom "Lalu" otomatis terisi dari shift sebelumnya (`shipOperationAccumulation()`). Untuk curah/amoniak, fungsi yang sama **sengaja mengembalikan array kosong** untuk tipe curah (baris 1847–1849: `if ($operation->type !== ShipOperation::TYPE_BAG_LOADING) return [];`). Artinya "COB Diterima" shift berjalan **tidak otomatis terisi** dari "COB Diserahkan" shift sebelumnya — operator harus mencari/mengingat manual, rawan salah ketik.

### Celah B — Dua jalur perhitungan total yang berbeda sumber
Total yang tampil di dashboard kinerja (`OperationalPerformanceService`, baris 128, 154, 2091, 2112) dihitung dari `SUM(bulk_loading_logs.cob_delta)` — yaitu dari log per jam ("Laporan Harian", field COB per jam yang merupakan bacaan kumulatif kapal, diproses oleh `BulkTonnageService` menjadi delta per jam). Sementara total yang tampil di laporan cetak/PDF (`report-paper.blade.php` baris 426) dan Excel export memakai `loading_qty` (Diserahkan − Diterima) per shift.

**Akibatnya:** ada dua jalur perhitungan "jumlah dimuat" yang berjalan sendiri-sendiri untuk kapal/shift yang sama:
- Jalur 1 (dashboard kinerja) = jumlah dari log per jam (`cob_delta`).
- Jalur 2 (laporan cetak & Excel) = `cob_delivered − cob_received` per shift.

Kalau kedua form ini diisi dengan cara yang tidak persis sinkron (misal ada jam yang terlewat di log per jam, atau nilai COB Diterima salah karena tidak auto-carry), **angka total di laporan cetak vs dashboard kinerja bisa berbeda** untuk shift/hari yang sama. Inilah kemungkinan besar akar masalah yang membuat Pak Mustari melihat "total kuantum salah" — bukan karena mekanisme COB itu sendiri salah, tapi karena dua celah implementasi di atas.

---

## 3. Rekomendasi perubahan (belum diterapkan — menunggu konfirmasi)

1. **Aktifkan auto-carry-forward** untuk `cob_received`/`cob_delivered` di modul curah & amoniak, menyamai pupuk kantong: perluas `shipOperationAccumulation()` agar "COB Diterima" shift berikutnya otomatis terisi dari "COB Diserahkan" shift sebelumnya (tetap bisa diedit manual bila perlu).
2. **Satukan sumber total.** Pilih satu jalur sebagai sumber kebenaran ("single source of truth") untuk total kuantum per shift — pakai `loading_qty` (Diserahkan − Diterima) yang sudah incremental per shift, dan gunakan itu juga di dashboard kinerja, alih-alih `SUM(cob_delta)` dari log per jam. Log per jam bisa tetap ada untuk keperluan monitoring jam-jaman, tapi tidak dijadikan basis total resmi agar tidak ada risiko selisih dengan input per-shift.

Catatan: perubahan-perubahan di atas **belum diimplementasikan**. Dokumen ini murni hasil analisis kode saat ini sebagai dasar diskusi dengan client sebelum eksekusi.
