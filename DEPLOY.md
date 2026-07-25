# Panduan Deploy Production

Dokumen ini mencatat langkah dan konfigurasi yang harus dijalankan saat aplikasi
dipasang di server production. Semua isinya bersifat konfigurasi — tidak ada
perubahan fitur.

---

## Ringkasan perintah rutin

Untuk update rutin di VPS, urutan ini yang dipakai:

```bash
php artisan down --render="errors::503"
```

```bash
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
```

```bash
php artisan migrate --force && php artisan optimize
```

```bash
sudo systemctl reload php8.3-fpm && php artisan up
```

**`reload php8.3-fpm` wajib** kalau `opcache.validate_timestamps=0` diaktifkan
(lihat bagian 3). Tanpa itu, PHP tetap menjalankan kode versi lama dari memori
dan perubahan tidak akan muncul sama sekali — ini jebakan paling sering terjadi.

Setup satu kali di server (modul Apache dan OPcache) ada di bagian 3 dan 4.

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
```

> `APP_DEBUG=false` wajib. Selain memperlambat respons, mode debug menampilkan
> jejak error berisi isi konfigurasi dan kredensial database kepada pengguna.

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
npm ci && npm run build
```

```bash
php artisan optimize
```

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

## 3. OPcache — perbaikan backend terbesar

**Ini butuh perhatian khusus.** Saat pemeriksaan, OPcache dalam keadaan mati
total. Tanpa OPcache, PHP mem-parsing ulang seluruh berkas aplikasi pada setiap
request, dan manfaat `php artisan optimize` hampir tidak terasa — keduanya
saling bergantung.

Aktifkan di `php.ini` server production:

```ini
zend_extension=opcache

opcache.enable=1
opcache.memory_consumption=192
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=0

; Berkas tidak berubah di antara deploy, jadi PHP tidak perlu mengecek
; timestamp tiap request. WAJIB restart PHP/web server setelah tiap deploy
; kalau ini diaktifkan, kalau tidak perubahan kode tidak akan terbaca.
opcache.validate_timestamps=0
```

Setelah setiap deploy, restart PHP-FPM atau Apache agar OPcache memuat kode baru:

```bash
sudo systemctl reload php8.3-fpm
```

---

## 4. Kompresi & cache header (Apache)

Aturannya sudah ditulis di [`public/.htaccess`](public/.htaccess) dan aktif
otomatis — **asalkan modul Apache-nya dimuat**. Blok kompresi dibungkus
`<IfModule mod_deflate.c>`, jadi kalau modulnya tidak ada, berkas tetap dikirim
tanpa kompresi tanpa menimbulkan error.

Pastikan modul berikut aktif di server production:

```bash
sudo a2enmod deflate headers setenvif && sudo systemctl restart apache2
```

Verifikasi setelah deploy:

```bash
curl -sI -H "Accept-Encoding: gzip" https://domain-anda.com/vendor/bootstrap/bootstrap.min.css | grep -i "content-encoding\|cache-control"
```

Hasil yang diharapkan: `Content-Encoding: gzip` dan `Cache-Control: public, max-age=2592000`.

Dampak kompresi pada aset aplikasi ini (hasil pengukuran):

| Berkas | Mentah | Gzip | Hemat |
|---|---|---|---|
| uicons-regular.css | 249 KB | 30 KB | 88% |
| bootstrap.min.css | 227 KB | 30 KB | 87% |
| bootstrap.bundle.js | 78 KB | 23 KB | 71% |
| report-ops.css | 55 KB | 9 KB | 84% |
| report-ops.js | 43 KB | 8 KB | 81% |

Total CSS + JS turun dari ±1.170 KB menjadi ±160 KB per kunjungan pertama.

### Kalau server memakai Nginx

`.htaccess` tidak dibaca Nginx. Padanan konfigurasinya:

```nginx
gzip on;
gzip_comp_level 6;
gzip_min_length 256;
gzip_types text/plain text/css text/xml text/javascript
           application/javascript application/json application/xml image/svg+xml;

# Berhash / berversi — aman disimpan lama
location ~ ^/(build|js)/ {
    add_header Cache-Control "public, max-age=31536000, immutable";
}

# Tidak berversi — dibatasi 30 hari
location ~ ^/(vendor|assets)/ {
    add_header Cache-Control "public, max-age=2592000";
}

# Tanda tangan bersifat privat
location ^~ /signatures/ {
    add_header Cache-Control "private, no-store";
}

# Service worker harus selalu divalidasi ulang
location = /sw.js {
    add_header Cache-Control "public, max-age=0, must-revalidate";
}
```

---

## 5. Cron untuk tugas terjadwal

Tanpa cron ini, pembersihan draft kadaluarsa, snapshot metrik dashboard admin,
dan backup otomatis tidak akan berjalan (lihat [`routes/console.php`](routes/console.php)):

```bash
* * * * * cd /path-ke-aplikasi && php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. Verifikasi setelah deploy

- [ ] `php artisan about` menampilkan `Environment: production`, `Debug Mode: OFF`
- [ ] Config, Events, Routes, Views semuanya `CACHED` pada output `php artisan about`
- [ ] `curl -sI` pada berkas di `/vendor/` mengembalikan `Content-Encoding: gzip`
- [ ] Halaman dinamis tetap mengembalikan `Cache-Control: no-cache, private`
      (jangan sampai ikut ter-cache — ini dijaga oleh Laravel, bukan `.htaccess`)
- [ ] Login, buat laporan, dan export PDF berjalan normal
- [ ] `storage/logs` tidak membengkak setelah beberapa jam pemakaian
