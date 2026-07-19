# Analisis Keamanan — Ketahanan Terhadap Serangan Judi Online (Judol)

> Disusun 17 Juli 2026. Audit kode sumber aplikasi Sistem Laporan Operasional KSS terhadap pola serangan
> "judol defacement" yang marak menyerang situs Indonesia (termasuk landing page KSS lama buatan pihak lain).
>
> **Dokumen ini hanya analisis. Belum ada satu baris kode pun yang diubah.** Rekomendasi di Bagian 5–6 ditulis
> sebagai daftar tindakan untuk dieksekusi nanti atas persetujuan Anda.

---

## 1. Ringkasan Eksekutif

**Kabar baik: aplikasi Anda jauh lebih aman daripada landing page yang di-hack itu, dan bukan karena
kebetulan.** Aplikasi ini dibangun di atas Laravel dengan praktik yang benar — autentikasi berlapis, proteksi
role, validasi upload ketat, dan escaping output. Pola serangan judol yang paling umum **tidak punya pintu
masuk** di sini.

Namun "lebih aman" bukan "kebal". Ada beberapa **lapисan pertahanan tambahan** yang belum dipasang — bukan
lubang menganga, melainkan pengerasan (*hardening*) yang lazim untuk aplikasi produksi. Selama konfigurasi
saat *deploy* dilakukan dengan benar (terutama `APP_DEBUG=false` dan HTTPS), risiko *takeover* judol sangat rendah.

| Aspek | Status |
|---|---|
| Kerentanan kritis (bisa langsung di-*takeover*) | **Tidak ditemukan** |
| Fondasi keamanan (auth, upload, SQL, XSS) | **Kuat** |
| Pengerasan produksi (header, CSP, config deploy) | **Perlu dilengkapi** |
| Risiko utama yang tersisa | **Salah konfigurasi saat deploy** (faktor manusia, bukan kode) |

---

## 2. Bagaimana Judol Menembus & Mengambil Alih Website (Cara Kerja Serangan)

Agar Anda paham *kenapa* aplikasi Anda aman, penting memahami dulu bagaimana situs seperti landing page KSS
lama bisa dibajak. Judol hampir tidak pernah "menembak" satu situs spesifik — mereka **memindai internet secara
massal** mencari kelemahan yang sama, lalu mengeksploitasi yang rentan. Jalur masuk paling umum:

### a. Upload file berbahaya (paling sering — "web shell")
Situs mengizinkan upload gambar/dokumen tapi **tidak memvalidasi isi file**. Penyerang meng-upload file
bernama `foto.php` (atau `foto.php.jpg`, atau `.phtml`) yang sebenarnya berisi kode PHP. Jika server mengeksekusi
file itu, penyerang mendapat "*web shell*" — remote control penuh atas situs. Dari sini mereka mengganti halaman
depan dengan promosi judi, menanam ratusan halaman SEO judi, atau menyisipkan skrip redirect.

### b. Plugin/tema/CMS usang (khusus WordPress dkk.)
Ini penyebab **mayoritas** kasus judol di Indonesia. Landing page yang dibuat cepat sering pakai WordPress
dengan tema/plugin bajakan (*nulled*) yang sudah disusupi *backdoor*, atau plugin usang dengan celah yang
sudah dipublikasikan. Bot memindai versi rentan dan masuk otomatis, tanpa perlu tahu situs itu milik siapa.

### c. Kredensial lemah / bocor
Password admin default (`admin/admin`), password yang dipakai ulang dari situs lain yang pernah bocor, atau
panel login tanpa pembatasan percobaan (*brute force*). Bot mencoba ribuan kombinasi sampai tembus.

### d. SQL Injection
Form yang menyisipkan input pengguna langsung ke query database tanpa penyaringan. Penyerang mengetik perintah
SQL di kolom input untuk mencuri data login atau menyuntik konten.

### e. Konfigurasi server bocor
File `.env` (berisi password database & kunci aplikasi) bisa diakses publik, atau mode *debug* menyala di
produksi sehingga pesan error membocorkan struktur internal, path server, dan kredensial.

### f. Kelemahan di hosting bersama (*shared hosting*)
Jika landing page KSS lama satu server dengan situs lain yang lemah, penyerang bisa "lompat" antar situs
melalui folder yang izin aksesnya salah. Ini sering jadi alasan situs yang "kelihatannya aman" tetap kena.

**Kesimpulan penting:** Landing page KSS lama kemungkinan besar kena lewat jalur **(b)** atau **(a)** — khas
situs statis/WordPress yang dibuat sekali lalu tidak dirawat. Itu masalah *jenis dan perawatan* situs, bukan
sekadar nasib buruk.

---

## 3. Posisi Aplikasi Anda Terhadap Setiap Jalur Serangan

Saya audit langsung kode sumbernya. Berikut hasil per jalur:

### a. Upload file — ✅ AMAN (dilindungi berlapis)
Satu-satunya fitur upload adalah tanda tangan (`AdminV2Controller::storeSignature`). Validasinya sangat ketat:
```
'signature' => ['nullable', 'file', 'mimes:png', 'mimetypes:image/png', 'max:2048']
```
- `mimes:png` mengecek ekstensi, `mimetypes:image/png` mengecek **isi asli file** (bukan cuma nama) — trik
  `shell.php.png` tertangkap di sini.
- Nama file **di-generate ulang** dengan pola acak (`signature-{slug}-{waktu}-{random}.png`), sehingga penyerang
  tidak bisa menentukan nama/ekstensi file hasil upload.
- Hanya **Admin** yang bisa mengaksesnya (di balik `middleware role:admin`).

Ini praktik terbaik. Jalur judol paling umub tertutup rapat.

> ⚠️ Satu catatan: file disimpan di `public/signatures/` (dalam docroot). Karena isinya dipastikan PNG asli
> dan nama diacak, risikonya rendah — tapi Bagian 5 menyarankan lapisan ekstra (tolak eksekusi skrip di folder itu).

### b. Plugin/CMS usang — ✅ TIDAK RELEVAN (bukan WordPress)
Aplikasi ini **framework Laravel murni**, bukan WordPress/CMS. Tidak ada ekosistem plugin pihak ketiga yang jadi
sarang *backdoor*. Dependensi hanya 4 paket resmi & tepercaya (Laravel, DomPDF, PhpSpreadsheet, Tinker).
Seluruh penyebab nomor satu judol di Indonesia **tidak berlaku** di sini.

### c. Kredensial lemah / brute force — ✅ KUAT
`LoginV2Controller` punya pertahanan yang lebih baik dari kebanyakan aplikasi:
- **Rate limit ganda**: maks 5 percobaan per identitas (jeda 60 dtk) DAN maks 20 percobaan per IP (jeda 300 dtk).
- Password di-hash (bcrypt bawaan Laravel), tidak pernah disimpan polos.
- Setiap login gagal, lockout, dan akun nonaktif **dicatat ke audit log keamanan** lengkap dengan IP & user-agent.
- Session **di-regenerate** setiap login sukses (mencegah *session fixation*).

> ⚠️ Yang di luar kode: password akun nyata milik KSS harus kuat. Seeder memakai `password` sebagai default
> pengembangan — pastikan **diganti semua** sebelum produksi (lihat Bagian 5).

### d. SQL Injection — ✅ AMAN
Seluruh query memakai Eloquent ORM / query builder Laravel yang otomatis mem-parameterisasi input. Saya periksa
semua penggunaan query mentah (`whereRaw`, `orderByRaw`, `selectRaw`):
- Mayoritas adalah `whereRaw('1 = 0')` — konstanta statis, tanpa input pengguna.
- `orderByRaw` yang ada memakai **konstanta kelas** (`ShipOperation::STATUS_ACTIVE`), bukan input mentah.

Tidak ada satu pun input pengguna yang masuk langsung ke SQL. Jalur ini tertutup.

### e. Konfigurasi bocor — ⚠️ TERGANTUNG DEPLOY (ini risiko nyata Anda)
Di **kode**, sudah benar: `.env` ada di `.gitignore` (tidak ikut ter-*commit*), docroot menunjuk ke `public/`
(bukan root proyek). **Tapi** `.env.example` memakai `APP_DEBUG=true`. Jika saat *deploy* nilai ini terbawa,
setiap error akan membocorkan detail internal server ke publik — persis jalur (e). **Ini titik lemah paling
mungkin Anda alami**, karena sifatnya kesalahan konfigurasi manusia, bukan bug kode. Wajib dipastikan
`APP_DEBUG=false` di produksi (Bagian 5).

### f. Shared hosting — ⚠️ TERGANTUNG INFRASTRUKTUR
Riwayat commit menunjukkan target *deploy* adalah **VPS** ("deploy VPS tanpa Node"), bukan shared hosting.
VPS jauh lebih aman untuk jalur ini karena terisolasi. Pastikan tetap begitu — jangan taruh di shared hosting
murah bersama situs lain yang tak terkontrol.

---

## 4. Yang Belum Ada (Bukan Lubang, Tapi Lapisan yang Layak Ditambah)

Ini bukan kerentanan yang bisa langsung dieksploitasi, melainkan **pertahanan berlapis** (*defense in depth*)
yang standar untuk aplikasi produksi menghadap internet:

1. **Tidak ada HTTP Security Headers.** Tidak ditemukan `X-Frame-Options`, `X-Content-Type-Options`,
   `Referrer-Policy`, atau `Permissions-Policy`. Header ini mempersulit serangan *clickjacking* dan
   *MIME-sniffing*.
2. **Tidak ada Content-Security-Policy (CSP).** CSP adalah **pertahanan paling ampuh terhadap injeksi skrip
   judol**: meski penyerang berhasil menyisipkan `<script>`, CSP memblokir eksekusi skrip dari sumber tak
   dikenal. Ini lapisan yang paling saya rekomendasikan untuk kasus Anda.
3. **Tidak ada HSTS** (`Strict-Transport-Security`) — memaksa browser selalu pakai HTTPS.
4. **`SESSION_ENCRYPT=false`** di contoh config — sebaiknya `true` di produksi.
5. **Folder `public/signatures/` di dalam docroot** — perlu aturan server yang menolak eksekusi skrip apa pun
   di sana (lapisan ekstra andai validasi upload suatu saat bocor).

> Konteks jujur: banyak aplikasi Laravel produksi berjalan tanpa item-item ini dan tetap aman, karena fondasinya
> (auth, validasi, ORM) sudah menutup jalur utama. Menambahkannya menaikkan Anda dari "aman" ke "aman berlapis" —
> dan untuk klien seperti KSS yang **pernah kena judol**, lapisan ini bernilai jual dan menenangkan.

---

## 5. Rekomendasi — Wajib Sebelum Produksi (Konfigurasi Deploy)

Ini **bukan perubahan kode**, tapi checklist konfigurasi saat *deploy* ke VPS. Paling penting & paling murah:

- [ ] **`APP_DEBUG=false`** di `.env` produksi. (Paling kritis — mencegah kebocoran info jalur (e).)
- [ ] **`APP_ENV=production`**.
- [ ] **`APP_KEY` di-generate** (`php artisan key:generate`) dan unik untuk produksi.
- [ ] **HTTPS aktif** (sertifikat SSL, mis. Let's Encrypt gratis) + set **`SESSION_SECURE_COOKIE=true`**.
- [ ] **`SESSION_ENCRYPT=true`** di produksi.
- [ ] **Ganti SEMUA password akun** dari default `password` seeder menjadi password kuat & unik per user.
- [ ] Pastikan **docroot web server menunjuk ke `public/`**, bukan folder root proyek.
- [ ] File `.env` **tidak boleh** bisa diakses via URL (uji: buka `situs.com/.env` → harus 403/404).
- [ ] Uji: buka `situs.com/storage/logs/laravel.log` → harus tidak bisa diakses.
- [ ] Set **backup database & file otomatis** (mis. harian) agar bisa pulih cepat jika terjadi apa pun.
- [ ] Pastikan **VPS**, bukan shared hosting; update OS & PHP rutin.

---

## 6. Rekomendasi — Pengerasan Kode (Untuk Dieksekusi Nanti Atas Persetujuan)

Perubahan kode yang menaikkan level keamanan. Ditulis di sini untuk dieksekusi bertahap **setelah Anda setuju**:

### Prioritas TINGGI
1. **Tambah middleware Security Headers.** Buat satu middleware yang menyisipkan `X-Frame-Options: SAMEORIGIN`,
   `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, dan
   `Permissions-Policy` minimal. Daftarkan global di `bootstrap/app.php`. Efek besar, risiko regresi kecil.
2. **Tambah Content-Security-Policy (CSP).** Karena semua aset sudah lokal (Bootstrap, Poppins, uicons di-*host*
   sendiri — tidak ada CDN), CSP ketat justru **mudah** diterapkan di sini: `default-src 'self'`. Perlu penanganan
   khusus untuk skrip/style *inline* yang ada (pakai *nonce* atau pindahkan ke file). Ini benteng terkuat
   melawan injeksi skrip judol.

### Prioritas MENENGAH
3. **Proteksi eksekusi di folder upload.** Tambah aturan server (Nginx/Apache) atau `.htaccess` di
   `public/signatures/` yang menolak permintaan ke `*.php`/`*.phtml` dsb. Lapisan cadangan andai validasi
   upload suatu hari ditembus.
4. **Pertimbangkan pindah upload keluar docroot.** Simpan tanda tangan di `storage/app/` dan sajikan lewat
   route terkontrol, bukan langsung dari `public/`. Perubahan lebih besar; opsional.

### Prioritas RENDAH (nice-to-have)
5. **Audit berkala `{!! !!}`.** Saat ini semua sudah aman (nilai di-*escape* dengan `e()` di controller, atau
   berisi HTML statis buatan sendiri). Tapi pola *unescaped output* ini rapuh terhadap perubahan masa depan —
   beri komentar penanda di tiap lokasi agar developer berikutnya tidak lupa meng-*escape*.
6. **Pertimbangkan 2FA untuk akun Admin** — opsional, tapi bernilai untuk akun paling berkuasa.

---

## 7. Kesimpulan Untuk Disampaikan ke KSS

> Aplikasi laporan ini **tidak rentan terhadap jenis serangan yang membajak landing page lama KSS**. Landing
> page itu kemungkinan besar situs statis/WordPress yang dibuat sekali lalu tidak dirawat — sarang empuk bot
> judol. Aplikasi ini berbeda secara fundamental: dibangun di atas framework modern (Laravel) dengan login
> berlapis anti-*brute-force*, kcontrol akses per peran, validasi upload berlapis, dan kekebalan bawaan terhadap
> SQL injection.
>
> Yang perlu dijaga adalah **disiplin saat pemasangan** (mode debug mati, HTTPS menyala, password kuat) dan
> **perawatan berkala** (update, backup). Dengan menambahkan beberapa lapisan pengerasan (security headers +
> CSP), aplikasi ini berada di level keamanan yang pantas untuk sistem operasional perusahaan.

**Poin jual untuk negosiasi:** justru karena KSS pernah kena judol, keamanan aplikasi ini adalah nilai tambah
konkret yang bisa Anda tonjolkan — dan menawarkan paket *maintenance* (yang mencakup update keamanan & backup)
menjadi sangat masuk akal bagi mereka.
