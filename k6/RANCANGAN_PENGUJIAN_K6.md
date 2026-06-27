# Rancangan Pengujian Beban (Load Testing) dengan k6

> Dokumen ini berisi rancangan skenario dan ambang batas (*threshold*) pengujian
> beban sistem **Manajemen Dokumen Operasional KSS**. Isinya disusun agar dapat
> langsung disalin dan diadaptasi ke dalam laporan Tugas Akhir / Skripsi
> (BAB Pengujian).

---

## 1. Identitas Pengujian

| Aspek | Keterangan |
|---|---|
| Nama pengujian | Pengujian Beban Akses Serentak 12 Pengguna |
| Jenis pengujian | **Pengujian Non-Fungsional → Performance Testing → Load Testing** |
| Pendekatan | *Black-box* (sistem diakses dari luar melalui endpoint HTTP) |
| Acuan kualitas | ISO/IEC 25010 — karakteristik *Performance Efficiency* (Time Behaviour, Capacity) |
| Alat bantu | k6 (open-source load testing tool) |
| Berkas skrip | `k6/concurrent-users-test.js` |

> **Catatan klasifikasi.** Load testing memakai pendekatan *black-box* karena
> menguji sistem dari sisi luar tanpa melihat kode sumber, namun fokusnya adalah
> **kualitas non-fungsional (performa)**, bukan kebenaran fitur seperti pada
> *black-box functional testing*. Keduanya merupakan kategori yang berbeda.

---

## 2. Tujuan Pengujian

1. Memvalidasi bahwa sistem **mampu melayani 12 pengguna yang mengakses secara
   bersamaan** tanpa kegagalan (error) maupun penurunan performa yang berarti.
2. Mengukur waktu respons (response time) sistem pada kondisi beban puncak 12
   pengguna aktif.
3. Memastikan proses kritis — **login** dan **membuka dashboard** — tetap stabil
   pada akses serentak.

---

## 3. Lingkungan Pengujian

> Lengkapi sesuai perangkat dan konfigurasi yang Anda gunakan saat pengujian.

| Komponen | Spesifikasi |
|---|---|
| Perangkat (CPU/RAM) | _mis. Intel Core i5, RAM 8 GB_ |
| Sistem operasi | _mis. Windows 11_ |
| Web server / runtime | _mis. PHP 8.x, Laravel, Laragon / `php artisan serve`_ |
| Basis data | _mis. MySQL / MariaDB_ |
| URL aplikasi | _mis. `http://localhost:8000` atau `http://increment-3.test`_ |
| Versi k6 | _mis. k6 v0.5x.x (`k6 version`)_ |
| Jaringan | Lokal (localhost) — menghilangkan variabel latensi internet |

---

## 4. Data Uji (12 Akun Pengguna)

Setiap *Virtual User* (VU) dipetakan ke **satu akun berbeda**, sehingga benar-benar
meniru 12 pengguna unik yang berbeda peran.

| No | Username | Peran | Halaman tujuan setelah login |
|---|---|---|---|
| 1 | `admin` | Admin | `/admin` |
| 2 | `manajer` | Manajer | `/manajer` |
| 3 | `karu.a` | Operasional | `/report-ops` |
| 4 | `wakaru.a` | Operasional | `/report-ops` |
| 5 | `karu.b` | Operasional | `/report-ops` |
| 6 | `wakaru.b` | Operasional | `/report-ops` |
| 7 | `karu.c` | Operasional | `/report-ops` |
| 8 | `wakaru.c` | Operasional | `/report-ops` |
| 9 | `karu.d` | Operasional | `/report-ops` |
| 10 | `wakaru.d` | Operasional | `/report-ops` |
| 11 | `kasi.pemeliharaan` | Pemeliharaan | `/pemeliharaan` |
| 12 | `karu.safety` | Safety | `/report-safety` |

Kata sandi seluruh akun uji: `password` (sesuai data seeder pengembangan).

---

## 5. Skenario Pengujian

### 5.1 Alur yang diuji (per pengguna)

Setiap pengguna virtual menjalankan alur yang meniru perilaku nyata:

1. **Membuka halaman login** → server mengirim halaman + cookie sesi + token CSRF.
2. **Mengirim kredensial** (username, password, `_token`) → autentikasi.
3. **Membuka dashboard** sesuai perannya → menavigasi sistem (berulang dengan
   *think time* 1 detik) selama fase beban berlangsung.

> Login dilakukan **sekali per pengguna**, lalu sesi dipakai ulang untuk navigasi
> berikutnya. Ini realistis (pengguna login sekali) sekaligus menghormati
> *rate-limit* login aplikasi (maksimum 20 percobaan per IP dalam 300 detik).

### 5.2 Pola pembebanan (Load Profile)

Menggunakan *executor* `ramping-vus` dengan tiga tahap:

| Tahap | Durasi | Jumlah VU (pengguna) | Tujuan |
|---|---|---|---|
| Ramp-up | 10 detik | 0 → 12 | Pengguna berdatangan secara bertahap |
| Steady (puncak) | 30 detik | 12 (konstan) | **12 pengguna aktif bersamaan** |
| Ramp-down | 5 detik | 12 → 0 | Pengguna menyelesaikan sesi |

Total durasi pengujian ± **45 detik**.

```
VU
12 |          ┌───────────────────────┐
   |         /                         \
   |        /                           \
 0 |_______/                             \____
   0s     10s                          40s  45s
        (ramp-up)     (steady 12 VU)   (ramp-down)
```

---

## 6. Parameter & Threshold (Kriteria Kelulusan)

*Threshold* adalah ambang batas otomatis: jika seluruh ambang terpenuhi, k6
menyatakan pengujian **PASS**. Inilah tabel utama yang ditulis di laporan.

| Metrik | Threshold | Arti | Status uji |
|---|---|---|---|
| `http_req_failed` | `rate < 0.01` | Request gagal < 1% (idealnya 0%) | Wajib |
| `http_req_duration` | `p(95) < 500ms` | 95% request selesai di bawah 500 ms | Wajib |
| `http_req_duration` | `p(99) < 1000ms` | 99% request selesai di bawah 1 detik | Wajib |
| `waktu_login` | `p(95) < 800ms` | 95% proses login di bawah 800 ms | Tambahan |
| `waktu_buka_dashboard` | `p(95) < 600ms` | 95% buka dashboard di bawah 600 ms | Tambahan |
| `rasio_login_berhasil` | `rate > 0.99` | Login berhasil > 99% | Wajib |
| `checks` | `rate > 0.99` | Validasi (status & sesi) lolos > 99% | Wajib |

> **Istilah `p(95)` (persentil ke-95):** nilai di mana 95% request lebih cepat
> dari angka tersebut. Persentil lebih representatif daripada rata-rata karena
> tidak tertutup oleh nilai ekstrem.

> Angka threshold di atas adalah target untuk lingkungan **lokal**. Bila perangkat
> uji lebih lambat, ambang boleh dilonggarkan (mis. `p(95) < 800ms`) dengan tetap
> mencantumkan alasannya di laporan.

---

## 7. Metrik yang Diamati

| Metrik k6 | Penjelasan |
|---|---|
| `http_reqs` | Total request HTTP yang dikirim selama pengujian |
| `http_req_duration` | Waktu respons rata-rata, median, p(90), p(95), maks |
| `http_req_failed` | Persentase request yang gagal |
| `vus` / `vus_max` | Jumlah pengguna virtual aktif / maksimum |
| `iterations` | Total siklus alur yang diselesaikan |
| `waktu_login` | (kustom) durasi proses login |
| `waktu_buka_dashboard` | (kustom) durasi membuka dashboard |
| `rasio_login_berhasil` | (kustom) persentase login yang berhasil |

---

## 8. Cara Menjalankan

```bash
# 1. Pastikan server aplikasi berjalan, contoh:
php artisan serve            # -> http://localhost:8000

# 2. Pastikan database sudah di-seed (akun uji tersedia):
php artisan migrate:fresh --seed

# 3. Jalankan pengujian:
k6 run k6/concurrent-users-test.js

# Dengan URL kustom (mis. virtual host Laragon):
k6 run -e BASE_URL=http://increment-3.test k6/concurrent-users-test.js

# Menyimpan ringkasan hasil ke file (untuk lampiran laporan):
k6 run --summary-export=k6/hasil-pengujian.json k6/concurrent-users-test.js
```

---

## 9. Template Tabel Hasil (diisi setelah pengujian)

> Salin angka dari ringkasan akhir output k6 ke tabel berikut.

| Metrik | Hasil | Threshold | Keterangan |
|---|---|---|---|
| Total request (`http_reqs`) | _…_ | — | — |
| Request gagal (`http_req_failed`) | _… %_ | < 1% | _PASS / FAIL_ |
| Rata-rata response time (avg) | _… ms_ | — | — |
| Response time p(95) | _… ms_ | < 500 ms | _PASS / FAIL_ |
| Response time maksimum | _… ms_ | — | — |
| Waktu login p(95) | _… ms_ | < 800 ms | _PASS / FAIL_ |
| Waktu buka dashboard p(95) | _… ms_ | < 600 ms | _PASS / FAIL_ |
| Rasio login berhasil | _… %_ | > 99% | _PASS / FAIL_ |
| Validasi (`checks`) | _… %_ | > 99% | _PASS / FAIL_ |
| VU maksimum | _12_ | — | — |

---

## 10. Template Analisis & Kesimpulan

**Analisis (contoh kalimat untuk laporan):**

> Berdasarkan hasil pengujian beban menggunakan k6 dengan 12 pengguna virtual yang
> mengakses sistem secara bersamaan selama ± 45 detik, diperoleh tingkat kegagalan
> request sebesar _… %_ dan waktu respons p(95) sebesar _… ms_. Seluruh proses
> login berhasil dengan rasio _… %_. Karena seluruh nilai memenuhi ambang batas
> yang ditetapkan, sistem dinyatakan **mampu melayani 12 pengguna serentak dengan
> aman dan stabil**.

**Kesimpulan:**

> Sistem Manajemen Dokumen Operasional KSS lolos pengujian beban (load testing)
> pada skenario 12 pengguna serentak. Hal ini menunjukkan bahwa sistem memenuhi
> aspek *performance efficiency* (ISO/IEC 25010) untuk kapasitas pengguna yang
> ditargetkan.

---

## 11. (Opsional) Pengembangan Lanjutan

Jika ingin memperkuat bab pengujian, dapat ditambahkan:

- **Stress testing** — menaikkan jumlah VU bertahap (mis. 12 → 25 → 50) untuk
  menemukan titik jenuh sistem.
- **Spike testing** — lonjakan VU mendadak untuk menguji ketahanan terhadap beban
  tiba-tiba.
- Perbandingan hasil **sebelum vs sesudah optimasi** (mis. caching) sebagai bukti
  perbaikan performa.
