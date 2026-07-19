# Analisis Harga Jual — Sistem Laporan Operasional KSS

> Disusun 17 Juli 2026 sebagai bahan pertimbangan penjualan aplikasi ke pihak KSS (Bontang, Kalimantan Timur).
> Konteks: aplikasi ini sekaligus objek penelitian skripsi pengembang, dan KSS telah berperan sebagai mitra
> penelitian (akses lapangan, kebutuhan riil, data uji, umpan balik petugas). Harga yang direkomendasikan
> memperhitungkan hubungan simbiosis tersebut — bukan harga komersial murni, tapi juga bukan harga "gratisan"
> yang merendahkan nilai kerja.

---

## 1. Ringkasan Rekomendasi

| Skenario | Angka |
|---|---|
| **Harga jangkar (pembuka negosiasi)** | **Rp 40.000.000** |
| **Target penyelesaian yang wajar** | **Rp 30.000.000 – 35.000.000** |
| **Lantai negosiasi (jangan di bawah ini)** | **Rp 25.000.000** |
| Maintenance/dukungan (opsional, per bulan) | Rp 1.000.000 – 1.500.000 |
| Biaya server VPS (beban KSS, per bulan) | ± Rp 150.000 – 400.000 |

Angka di atas untuk **lisensi pakai penuh internal KSS + serah terima terpasang + pelatihan + garansi perbaikan
bug 3 bulan**, dengan kode sumber tetap milik pengembang. Jika KSS ingin **membeli kode sumber sepenuhnya**
(buyout), tambahkan 40–60%: kisaran **Rp 45.000.000 – 60.000.000**.

---

## 2. Skala Proyek (Data Objektif)

Diukur langsung dari repositori per 17 Juli 2026 (di luar library pihak ketiga):

| Metrik | Nilai |
|---|---|
| Total baris kode tulisan tangan | **± 50.700 baris** |
| File tampilan (Blade) | 72 file / 23.709 baris |
| Controller | 13 file / 7.462 baris |
| Model database | 33 model |
| Struktur tabel (migrasi) | 36 migrasi |
| Route/endpoint terdaftar | 108 |
| Pengujian otomatis | **151 test, 769 assertion — semuanya lolos** |
| Dokumentasi | 6 dokumen (dokumentasi teknis, panduan pengguna, panduan testing, katalog fitur) |
| Masa pengembangan tercatat di git | 21 Mei – 17 Juli 2026 (± 2 bulan intensif) |

### Cakupan fungsional

**5 modul peran:** Admin (kelola user, master data, log aktivitas), Manajer (dashboard persetujuan + arsip),
Operasional (laporan shift harian), Pemeliharaan, Safety/K3.

**3 modul laporan lengkap** dengan logika domain yang tidak sepele:

- Serah terima antar regu dengan **tanda tangan digital** dan sinkronisasi kondisi unit antar shift.
- **Registri Ship Operation** — akumulasi muat kantong/curah lintas hari dengan pengarsipan otomatis
  (bukan hapus), autocomplete kapal, dan penyambungan kembali operasi yang jeda.
- Carry-over pekerjaan pemeliharaan yang belum selesai ke laporan berikutnya.
- Guard laporan ganda (tanggal+shift+regu), wizard bertahap, autosave 30 detik + **autosave offline
  (localStorage) yang sinkron ulang otomatis**, draft ber-TTL 3 hari dengan badge sisa umur + perpanjang.
- Panel "Intip Laporan Sebelumnya" tanpa keluar dari form.
- **Export PDF** (ketiga modul) + **Export Excel** (operasional), pratinjau cetak.
- Service worker: cache aset + halaman fallback offline — dirancang untuk sinyal buruk di lapangan.
- Mode gelap, responsif mobile, dan build aset ikut repo sehingga deploy VPS tanpa Node.

Ini bukan aplikasi CRUD skripsi tipikal — ini sistem multi-modul dengan alur kerja nyata yang **sudah dipakai
dan dirasakan manfaatnya oleh KSS** (terbukti dari inisiatif mereka menawar).

---

## 3. Nilai Penggantian (Berapa Biaya KSS Jika Membangun Ini Lewat Jalur Lain)

Cara paling adil menilai harga adalah *replacement cost* — berapa KSS harus bayar pihak lain untuk hasil setara:

| Jalur | Estimasi durasi | Estimasi biaya |
|---|---|---|
| Freelancer menengah (solo, Kaltim/remote) | 4–6 bulan | Rp 35 – 70 juta |
| Software house kecil regional (Samarinda/Balikpapan) | 3–5 bulan | Rp 60 – 120 juta |
| Software house mapan (Jakarta/Surabaya) | 3–4 bulan | Rp 100 – 200 juta |

Catatan penting: durasi 2 bulan di git bukan tolok ukur harga — yang dibeli KSS adalah **hasil dan fungsinya**,
bukan jam kerja. Sistem dengan 3 modul laporan berlogika domain, 5 dashboard peran, export PDF/Excel,
151 test otomatis, dan dokumentasi lengkap secara wajar berada di kelas pekerjaan Rp 60 juta ke atas
bila dikerjakan vendor komersial.

---

## 4. Faktor Penyesuaian (Kenapa Tidak Dijual Harga Komersial Penuh)

Ada empat faktor yang menarik harga **turun** dari kisaran komersial, dan dua yang menahannya agar
**tidak terlalu rendah**:

**Penurun:**
1. **Simbiosis skripsi.** KSS memberi akses lapangan, kebutuhan riil, petugas untuk uji coba, dan (idealnya)
   surat keterangan implementasi untuk sidang. Itu kontribusi bernilai nyata — wajar dikompensasi lewat diskon.
2. **Status pengembang** perorangan/mahasiswa, tanpa badan usaha, tanpa overhead kantor — struktur biaya
   memang lebih ringan daripada software house.
3. **Konteks kota Bontang** — pasar jasa IT lokal lebih tipis daripada Jakarta; harga pasar wajar regional
   memang lebih rendah, meski daya beli perusahaan industri di Bontang sebenarnya kuat.
4. **Relasi jangka panjang.** Menjaga hubungan baik membuka peluang kontrak maintenance, pengembangan
   lanjutan, dan referensi ke perusahaan lain di kawasan industri Bontang.

**Penahan (jangan sampai terlalu murah):**
1. KSS adalah **kontraktor di kawasan industri** (lingkungan PKT/Badak) — mereka terbiasa dengan harga jasa
   B2B; angka belasan juta untuk sistem sekelas ini justru bisa terkesan tidak serius.
2. **Mereka yang menawar duluan** karena sudah merasakan manfaat. Posisi tawar pengembang sedang bagus;
   jangan disia-siakan dengan membuka harga terlalu rendah.

Netto dari semua faktor itu: **diskon ± 40–50% dari lantai harga komersial (Rp 60 juta)** → mendarat di
kisaran **Rp 30–35 juta**.

---

## 5. Struktur Paket yang Disarankan Saat Menawarkan

Tawarkan dalam bentuk paket agar diskusi bergeser dari "berapa harganya" ke "pilih yang mana":

### Opsi A — Lisensi Pakai Internal (rekomendasi utama) · **Rp 30–35 juta**
- KSS berhak memakai aplikasi tanpa batas waktu untuk operasional internal.
- Termasuk: pemasangan di server KSS/VPS, migrasi data awal, pelatihan petugas & admin (1–2 sesi),
  dokumentasi lengkap, garansi perbaikan bug 3 bulan.
- Kode sumber tetap hak cipta pengembang; KSS mendapat salinan untuk keamanan (escrow sederhana) namun
  tidak untuk dijual ulang.

### Opsi B — Buyout Kode Sumber · **Rp 45–60 juta**
- Seperti Opsi A **plus** penyerahan penuh hak ekonomi kode sumber kepada KSS.
- Pengembang tetap memegang hak untuk mencantumkan proyek ini di portofolio dan skripsi.

### Opsi C — Lisensi Tahunan (jika anggaran sekali bayar berat) · **Rp 12–15 juta/tahun**
- Termasuk dukungan ringan dan pembaruan minor selama masa lisensi.
- Cocok ditawarkan sebagai jalan tengah bila negosiasi Opsi A buntu — tapi jadikan pilihan terakhir,
  karena pendapatan totalnya baru menyamai Opsi A setelah ± 2,5 tahun.

### Tambahan di semua opsi
- **Maintenance pasca-garansi:** Rp 1–1,5 juta/bulan (perbaikan bug, penyesuaian kecil, pemantauan server)
  atau Rp 500 ribu/insiden bila tanpa retainer.
- **Biaya infrastruktur** (VPS, domain, backup) menjadi beban KSS langsung, ± Rp 150–400 ribu/bulan.
- **Pengembangan fitur baru** di luar cakupan dihitung terpisah (patokan wajar: Rp 150–300 ribu/jam
  atau borongan per fitur).

---

## 6. Strategi Negosiasi Singkat

1. **Buka di Rp 40 juta** (Opsi A) sambil menunjukkan tabel Bagian 3 — biarkan pembanding vendor yang
   "berbicara". Angka pembuka ini memberi ruang turun tanpa melukai nilai.
2. **Target selesai di Rp 30–35 juta.** Jika KSS menawar di bawah itu, tukar penurunan harga dengan
   pengurangan cakupan (mis. garansi 1 bulan, pelatihan 1 sesi) — jangan menurunkan harga tanpa imbal balik.
3. **Lantai mutlak Rp 25 juta.** Di bawah ini, lebih sehat menawarkan Opsi C (tahunan) daripada melepas
   lisensi permanen terlalu murah.
4. Sepakati **termin pembayaran**: umum dipakai 50% di muka (tanda jadi + serah terima terpasang),
   50% setelah masa uji 2–4 minggu. Ini melindungi kedua pihak.
5. Semua kesepakatan dituangkan tertulis minimal dalam **surat perjanjian sederhana + berita acara serah
   terima (BAST)** — penting juga sebagai lampiran administratif skripsi.

---

## 7. Klausul Non-Harga yang Wajib Diamankan (Kepentingan Skripsi)

Karena aplikasi ini objek penelitian, pastikan perjanjian memuat:

1. **Hak akademik**: pengembang berhak menggunakan arsitektur, tangkapan layar, dan data yang
   **dianonimkan** untuk dokumen skripsi, sidang, dan publikasi ilmiah.
2. **Surat keterangan implementasi** dari KSS (bermeterai/kop resmi) yang menyatakan aplikasi dipakai
   di operasional — ini bernilai besar untuk sidang dan portofolio.
3. **Akses demo** ke lingkungan aplikasi (atau salinan staging) minimal sampai sidang skripsi selesai.
4. **Atribusi**: nama pengembang tetap tercantum di aplikasi (footer "Dibuat oleh …" yang sudah ada).
5. **Batas tanggung jawab**: garansi mencakup perbaikan bug, bukan kehilangan data akibat kelalaian
   operasional pengguna atau kegagalan infrastruktur milik KSS.

---

## 8. Kesimpulan

Nilai komersial wajar sistem ini di pasar regional berada di **Rp 60 juta ke atas**. Dengan memperhitungkan
kontribusi KSS sebagai mitra penelitian, status pengembang, dan konteks pasar Bontang, harga jual yang
**pas, fair untuk dua pihak, dan tetap menghargai bobot pekerjaan** adalah:

> ## Rp 30 – 35 juta
> (buka di Rp 40 juta · jangan di bawah Rp 25 juta · maintenance Rp 1–1,5 juta/bulan terpisah)

Harga ini sekitar setengah dari harga vendor komersial — bentuk nyata "simbiosis mutualisme": KSS mendapat
sistem yang sudah terbukti membantu dengan harga jauh di bawah pasar, dan pengembang mendapat kompensasi
yang layak plus dukungan penuh untuk penyelesaian skripsi.
