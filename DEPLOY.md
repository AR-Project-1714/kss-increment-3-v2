# Panduan Deploy Production

Dokumen ini mencatat langkah dan konfigurasi yang harus dijalankan saat aplikasi
dipasang di server production. Semua isinya bersifat konfigurasi — tidak ada
perubahan fitur.

## Lingkungan produksi saat ini

| | |
|---|---|
| Server | Ubuntu 24.04 LTS, VPS (`kss-operational`) |
| Web server | **Nginx** (bukan Apache) |
| PHP | 8.3 FPM, pool khusus `php8.3-fpm-kss-multidivisi.sock` |
| Domain | `app.kss-operation.my.id`, HTTPS via Certbot |
| Root aplikasi | `/var/www/kss-multidivisi` |
| Config Nginx | `/etc/nginx/sites-enabled/kss-multidivisi` |

> Karena produksi memakai Nginx, berkas `public/.htaccess` **tidak dibaca sama
> sekali di server**. Berkas itu tetap disimpan karena development lokal
> (Laragon) memakai Apache. Aturan yang setara untuk produksi ada di bagian 4.

---

## Ringkasan perintah rutin

Untuk update rutin di VPS, urutan ini yang dipakai. Jalankan dari
`/var/www/kss-multidivisi`:

```bash
php artisan down --render="errors::503"
```

```bash
git pull && composer install --no-dev --optimize-autoloader
```

```bash
php artisan migrate --force && php artisan optimize
```

```bash
sudo systemctl reload php8.3-fpm && php artisan up
```

> **Jangan jalankan `npm` di server.** VPS ini tidak memasang Node/npm, dan
> memang tidak perlu: hasil build Vite sengaja di-commit ke repo (lihat
> [`.gitignore`](.gitignore) baris 17). Aset di-build di mesin lokal dengan
> `npm run build`, lalu ikut ter-push — jadi `git pull` sudah membawa
> `public/build/` versi terbaru. Kalau lupa build sebelum push, aset lama yang
> naik ke produksi.

`reload php8.3-fpm` disertakan sebagai pengaman. Dengan setelan sekarang
(`opcache.validate_timestamps=On`) OPcache sudah memuat kode baru sendiri, jadi
langkah ini tidak wajib — tapi murah dan menghilangkan satu sumber kebingungan.
Kalau suatu saat `validate_timestamps` dimatikan, langkah ini jadi **wajib**.

Setup satu kali di server (OPcache dan konfigurasi Nginx) ada di bagian 3 dan 4.

---

## 1. Konfigurasi `.env` production

Salin `.env` yang ada, lalu pastikan nilai berikut. Yang bertanda **wajib**
berpengaruh langsung ke keamanan atau performa.

```env
# --- wajib ---
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com

# Level debug menulis setiap query dan deprecation ke disk. Di production
# ini membebani I/O dan membuat berkas log membengkak cepat.
LOG_LEVEL=warning
LOG_CHANNEL=stack
LOG_STACK=daily

# --- database ---
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama-database
DB_USERNAME=user-database
DB_PASSWORD=password-database

# --- session & cache ---
# Driver database sudah memadai untuk skala pemakaian saat ini (belasan akun).
# Pindah ke Redis hanya perlu jika jumlah pengguna bertambah banyak.
SESSION_DRIVER=database
SESSION_LIFETIME=240
CACHE_STORE=database
QUEUE_CONNECTION=database

# Cookie sesi hanya dikirim lewat HTTPS. Disyaratkan di KEAMANAN_SISTEM.md.
# JANGAN diaktifkan kalau domain masih HTTP — pengguna akan gagal login karena
# cookie sesi tidak pernah sampai ke server.
SESSION_SECURE_COOKIE=true

# --- monitoring saldo IDCloudHost (wajib untuk card billing admin) ---
# Token tetap server-side; jangan pernah memakai nama VITE_*.
IDCLOUDHOST_API_KEY=token-api
IDCLOUDHOST_BILLING_ACCOUNT_ID=id-billing
IDCLOUDHOST_CURRENCY=IDR

# Peringatan muncul bila salah satu batas nominal/hari terlewati.
IDCLOUDHOST_LOW_CREDIT_THRESHOLD=100000
IDCLOUDHOST_WARNING_DAYS=7
IDCLOUDHOST_CRITICAL_DAYS=3

# Opsional: isi biaya bulanan saat akun belum punya histori invoice.
# IDCLOUDHOST_ESTIMATED_MONTHLY_COST=500000
```

> `APP_DEBUG=false` wajib. Selain memperlambat respons, mode debug menampilkan
> jejak error berisi isi konfigurasi dan kredensial database kepada pengguna.

Setelah mengubah konfigurasi IDCloudHost pada server yang sudah memakai config
cache, jalankan `php artisan optimize` lalu verifikasi sekali dengan:

```bash
php artisan idcloudhost:refresh-credit
```

Perintah hanya menampilkan saldo terformat dan estimasi; API key tidak ditulis
ke output atau database. Snapshot saldo disimpan maksimal 90 hari.

---

## 2. Perintah deploy

Jalankan berurutan dari root aplikasi setiap kali menaikkan versi baru:

```bash
composer install --no-dev --optimize-autoloader
```

```bash
php artisan migrate --force
```

```bash
php artisan optimize
```

Aset frontend tidak di-build di server — lihat catatan di "Ringkasan perintah
rutin" di atas.

### 2.1 Rilis perbaikan identitas kapal & tonase (satu kali)

Khusus rilis yang memuat migrasi `2026_07_31_000001` sampai `000003`, ada satu
perintah tambahan yang **wajib** dijalankan sesudah `migrate`:

```bash
php artisan ops:repair-ship-identity
```

Tanpa perintah ini, tonase muat curah dan muat amoniak akan terbaca **0** di
seluruh menu, karena kolom `cob_delta` yang menjadi sumber angkanya lahir dalam
keadaan kosong.

Perintah ini juga:

- memulihkan nilai COB yang titik desimalnya terbuang oleh pembantu `integer()`
  versi lama (`4420.25` tersimpan sebagai `442025`) — dibaca ulang dari isian
  form asli di `daily_reports.payload`, bukan ditebak;
- menyatukan operasi kapal yang terpecah hanya karena ejaan namanya berbeda; dan
- membentuk operasi kapal untuk riwayat bongkar bahan baku dan container.

Lihat rencananya lebih dulu tanpa menulis apa pun:

```bash
php artisan ops:repair-ship-identity --dry-run
```

Ambil backup sebelum menjalankannya — perintah ini menggabungkan dan menghapus
baris `ship_operations`:

```bash
php artisan backup:run
```

Sesudah rilis ini, penjadwal harian sudah menangani sisanya
(`ops:repair-ship-identity --recalculate-only`, tiap 01.45). Selengkapnya di
`PERBAIKAN_TONASE_MUAT_CURAH.md`.

`php artisan optimize` menggabungkan cache config, event, route, dan view dalam
satu perintah. Sudah diuji pada aplikasi ini dan keempatnya terbentuk bersih —
tidak ada route berbasis closure maupun pemanggilan `env()` di luar folder
`config/` yang bisa membuat `config:cache` memutus nilai konfigurasi.

Untuk membatalkan cache (misal saat menelusuri masalah):

```bash
php artisan optimize:clear
```

### Storage

Pastikan symlink storage ada dan folder yang ditulis aplikasi bisa ditulis:

```bash
php artisan storage:link
```

---

## 3. OPcache

Tanpa OPcache, PHP mem-parsing ulang seluruh berkas aplikasi pada setiap
request, dan manfaat `php artisan optimize` hampir tidak terasa — keduanya
saling bergantung. Pada pengukuran di lingkungan lokal (OPcache mati),
`php artisan optimize` tidak memberi perbaikan sama sekali.

### Kondisi terverifikasi di produksi (25 Juli 2026)

`php-fpm8.3 -i` menunjukkan OPcache **sudah aktif** dengan nilai bawaan:

| Setelan | Nilai | Penilaian |
|---|---|---|
| `opcache.enable` | On | Sudah benar |
| `opcache.max_accelerated_files` | 10000 | Cukup — lihat catatan di bawah |
| `opcache.memory_consumption` | 128 MB | Cukup, bisa dinaikkan sebagai margin |
| `opcache.interned_strings_buffer` | 8 MB | Agak kecil untuk Laravel |
| `opcache.validate_timestamps` | On | **Biarkan On** — lihat di bawah |

Aplikasi ini berisi **7.236 berkas PHP**. Angka `max_accelerated_files=10000`
terlihat mepet, tapi tidak: PHP membulatkannya ke bilangan prima berikutnya
dari daftar internalnya, sehingga kapasitas sebenarnya **16.229 berkas**.
Tidak perlu diubah.

### Soal `validate_timestamps`

**Biarkan `On` (bawaan).** Mematikannya (`=0`) sering disarankan di panduan
umum, tapi untuk konteks ini tidak sepadan: keuntungannya kecil — PHP hanya
melakukan `stat()` per berkas maksimal tiap 2 detik, praktis tak terasa di
SSD — sementara risikonya nyata. Sekali lupa `reload php8.3-fpm` setelah
deploy, server menjalankan kode lama tanpa gejala apa pun, dan gejala itu
sangat menyesatkan saat ditelusuri.

### Penyetelan opsional

Hanya menambah margin, bukan memperbaiki masalah. Lewatkan saja kalau server
sedang terbatas RAM-nya:

```bash
sudo nano /etc/php/8.3/fpm/conf.d/99-kss-opcache.ini
```

```ini
opcache.memory_consumption=192
opcache.interned_strings_buffer=16
```

Dipasang lewat `conf.d`, bukan mengedit `php.ini` langsung, supaya tidak
tertimpa saat paket PHP di-upgrade. Terapkan dengan:

```bash
sudo systemctl reload php8.3-fpm
```

---

## 4. Kompresi & cache header (Nginx)

**Status: sudah terpasang dan terverifikasi di produksi (25 Juli 2026).**
Bagian ini didokumentasikan supaya bisa dipasang ulang kalau server diganti
atau konfigurasinya hilang.

Dua berkas yang terlibat:

| Berkas | Isi |
|---|---|
| `/etc/nginx/conf.d/gzip.conf` | Kompresi, berlaku untuk semua site |
| `/etc/nginx/snippets/kss-static-cache.conf` | Cache header aset statis |

Keduanya sengaja berupa berkas terpisah, bukan editan langsung pada
`nginx.conf` — supaya tidak tertimpa saat paket nginx di-upgrade.

### 4.1 Kompresi

Bawaan Ubuntu menyalakan `gzip on` tapi membiarkan `gzip_types` ter-komentar,
sehingga **hanya `text/html` yang terkompresi** dan seluruh CSS/JS terkirim
mentah. Ini yang diperbaiki:

```bash
sudo tee /etc/nginx/conf.d/gzip.conf > /dev/null <<'EOF'
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_min_length 256;
gzip_types
    text/plain
    text/css
    text/xml
    text/javascript
    application/javascript
    application/json
    application/xml
    application/rss+xml
    image/svg+xml;
EOF
```

Berkas biner (woff2, png, webp, ico, pdf, xlsx) sengaja tidak didaftarkan —
formatnya sudah terkompresi, mengompres ulang hanya membakar CPU. `text/html`
juga tidak perlu ditulis karena nginx selalu mengompresnya saat `gzip on`.

### 4.2 Cache header

```bash
sudo tee /etc/nginx/snippets/kss-static-cache.conf > /dev/null <<'EOF'
location ^~ /build/ {
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Cache-Control "public, max-age=31536000, immutable";
    access_log off;
}
location ^~ /js/ {
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Cache-Control "public, max-age=31536000, immutable";
    access_log off;
}
location ^~ /vendor/ {
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Cache-Control "public, max-age=2592000";
    access_log off;
}
location ^~ /assets/ {
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Cache-Control "public, max-age=2592000";
    access_log off;
}
location ^~ /signatures/ {
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Cache-Control "private, no-store";
}
location = /sw.js {
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Cache-Control "public, max-age=0, must-revalidate";
}
EOF
```

Lalu satu baris di `/etc/nginx/sites-enabled/kss-multidivisi`, di dalam
`server { }` yang punya `root`, tepat setelah penutup `location / { }`:

```nginx
    include snippets/kss-static-cache.conf;
```

**Dua hal yang WAJIB diperhatikan kalau blok ini diubah:**

1. `X-Frame-Options` dan `X-Content-Type-Options` ditulis ulang di setiap
   `location`. Ini bukan duplikasi ceroboh — di Nginx, begitu sebuah `location`
   punya `add_header` sendiri, ia **berhenti mewarisi seluruh `add_header` dari
   blok induk**. Menghapusnya berarti menghilangkan header keamanan pada semua
   aset statis, tanpa peringatan apa pun.
2. Prefix `^~` dipakai supaya blok ini menang atas `location` regex di
   bawahnya, termasuk aturan `deny all` untuk dotfile.

### 4.3 Pasang & verifikasi

Selalu test sebelum reload — kalau ada salah ketik, `nginx -t` menangkapnya
sebelum situs mati:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

```bash
for p in /vendor/bootstrap/bootstrap.min.css /build/manifest.json /signatures/manajer.png /sw.js; do echo "--- $p"; curl -sI -H "Accept-Encoding: gzip" "https://app.kss-operation.my.id$p" | grep -iE "content-encoding|cache-control|x-content-type-options"; done
```

Hasil yang benar:

| Path | Cache-Control | Content-Encoding |
|---|---|---|
| `/vendor/bootstrap/bootstrap.min.css` | `public, max-age=2592000` | `gzip` |
| `/build/manifest.json` | `max-age=31536000, immutable` | `gzip` |
| `/signatures/manajer.png` | `private, no-store` | — (benar, PNG tidak dikompres) |
| `/sw.js` | `max-age=0, must-revalidate` | `gzip` |

`X-Content-Type-Options: nosniff` harus muncul di keempatnya.

### 4.4 Hasil terukur di produksi

Pengukuran nyata pada `uicons-regular-rounded.css` setelah konfigurasi aktif:

```
255.338 byte  ->  33.709 byte   (hemat 86,8%)
```

Total CSS + JS per kunjungan pertama turun dari ±1.173 KB menjadi ±171 KB.

---

## 5. Cron untuk tugas terjadwal

Tanpa cron ini, pembersihan draft kadaluarsa, snapshot metrik dashboard admin,
backup otomatis, **dan penyiapan bundel ZIP arsip di latar** tidak akan berjalan
(lihat [`routes/console.php`](routes/console.php)):

```bash
* * * * * cd /var/www/kss-multidivisi && php artisan schedule:run >> /dev/null 2>&1
```

### 5.1 Antrean (queue) untuk unduh massal arsip

Unduh massal di halaman Arsip Laporan punya dua jalur:

| Jumlah laporan | Jalur | Kebutuhan |
| --- | --- | --- |
| sampai 50 | ZIP dirakit langsung dalam satu request | tidak ada |
| di atas 50 | job `BuildArchiveBundle` merakit ZIP di latar | **queue worker harus jalan** |

Jadwal di `routes/console.php` sudah menguras antrean lewat cron per menit yang
sama (`queue:work --stop-when-empty --max-time=55 --memory=512`), jadi VPS
sederhana tidak perlu daemon terpisah. Konsekuensinya: bundel mulai dikerjakan
paling lama ±1 menit setelah diminta, dan panel progres di UI menampilkan
"menunggu antrean" selama itu.

Kalau server ingin bundel langsung mulai tanpa jeda, jalankan worker daemon
(supervisor/systemd) lalu **hapus** entri `queue:work` dari `routes/console.php`
agar tidak ada dua worker berebut job:

```bash
php artisan queue:work --queue=default --memory=512 --timeout=3600 --tries=1
```

Catatan penting:

- `--memory=512` bukan opsional: render dompdf memakai ±100 MB, di atas batas
  bawaan 128 MB. Worker yang melampauinya berhenti di tengah bundel.
- `--timeout=3600` menyamai `$timeout` pada job; bundel 300 laporan bisa
  memakan beberapa menit.
- Berkas bundel tersimpan di `storage/app/private/archive-bundles` dan dibuang
  otomatis setelah 24 jam oleh `archive:prune-bundles` (terjadwal per jam).
  Sisakan ruang disk untuk beberapa bundel sekaligus (±10 MB per 100 laporan).

---

## 6. Verifikasi setelah deploy

- [ ] `php artisan about` menampilkan `Environment: production`, `Debug Mode: OFF`
- [ ] Config, Events, Routes, Views semuanya `CACHED` pada output `php artisan about`
- [ ] `curl -sI` pada berkas di `/vendor/` mengembalikan `Content-Encoding: gzip`
      dan `X-Content-Type-Options: nosniff`
- [ ] Halaman dinamis tetap mengembalikan `Cache-Control: no-cache, private`
      (jangan sampai ikut ter-cache — ini dijaga oleh Laravel, bukan Nginx)
- [ ] Login, buat laporan, dan export PDF berjalan normal
- [ ] Tanda tangan tampil di laporan dan di PDF hasil export
- [ ] `storage/logs` tidak membengkak setelah beberapa jam pemakaian
