# Keamanan Sistem — KSS Report v2

Dokumen ini menjelaskan lapisan keamanan yang **sudah diterapkan** dalam aplikasi
Laravel ini: perlindungan terhadap brute force, web defacement, injeksi konten
judi online (judol), XSS, SQL Injection, CSRF, dan sebagainya. Semua klaim di
bawah merujuk pada kode yang benar-benar ada di dalam repositori.

> Stack: **PHP 8.3**, **Laravel 13.8**, sesi berbasis database, autentikasi
> bawaan Laravel. Tidak ada endpoint publik yang bisa menulis data — seluruh
> aksi berada di balik autentikasi + otorisasi peran.

---

## 1. Perlindungan Brute Force (Login)

Berkas: [`app/Http/Controllers/LoginV2Controller.php`](app/Http/Controllers/LoginV2Controller.php)

Login memakai **rate limiting ganda** melalui `Illuminate\Support\Facades\RateLimiter`:

| Lapisan | Kunci (throttle key) | Batas | Masa blokir |
|---------|----------------------|-------|-------------|
| Per-identitas | `login:identity:{sha1(username+ip)}` | **5** percobaan | **60 detik** |
| Per-IP | `login:ip:{sha1(ip)}` | **20** percobaan | **300 detik** |

Detail penerapannya:

- **Dua batas sekaligus.** Batas per-identitas menghentikan penebakan password
  satu akun; batas per-IP menghentikan penyerang yang mencoba banyak username
  dari satu sumber (credential stuffing). Keduanya diperiksa di
  `ensureIsNotRateLimited()`.
- **Kunci di-hash (`sha1`)** dan username dinormalisasi (`Str::lower` +
  `Str::transliterate`) sehingga kunci konsisten dan tidak menyimpan identitas
  mentah.
- **Reset saat sukses.** Setelah login berhasil, `RateLimiter::clear()`
  menghapus hitungan percobaan.
- **Pesan aman.** Kegagalan selalu mengembalikan pesan generik
  *"Username/email atau password salah."* — tidak membocorkan apakah username
  ada atau tidak (mencegah user enumeration).
- **Umpan balik lockout** memberi tahu sisa detik (`Terlalu banyak percobaan…`)
  tanpa membuka celah tebak.

## 2. Audit & Deteksi (Security Event Log)

Setiap peristiwa login mencurigakan dicatat ke tabel `admin_activity_logs`
(model [`AdminActivityLog`](app/Models/AdminActivityLog.php)) melalui
`recordLoginSecurityEvent()`:

- `failed_login` — login gagal, lengkap dengan jumlah percobaan, IP, dan
  User-Agent.
- `identity_rate_limited` / `ip_rate_limited` — brute force terblokir (dicatat
  **sekali** per periode blokir agar log tidak banjir, via `recordLoginLockoutOnce`).
- `inactive_account_login` — upaya login ke akun yang dinonaktifkan.

Kolom yang disimpan: `user_id`, `type=security`, `description`, `ip_address`,
dan `properties` (JSON) berisi konteks lengkap. Ini memberi jejak forensik untuk
mendeteksi serangan.

## 3. Keamanan Kredensial & Sesi

- **Password di-hash otomatis.** Model [`User`](app/Models/User.php) memakai cast
  `'password' => 'hashed'` (bcrypt/argon bawaan Laravel). Password tidak pernah
  disimpan/ditampilkan sebagai plaintext, dan kolom `password` + `remember_token`
  masuk `$hidden`.
- **Session fixation dicegah.** Setelah login sukses
  `session()->regenerate()` dipanggil; saat logout `session()->invalidate()` +
  `regenerateToken()`.
- **Akun nonaktif ditolak.** Meski password benar, akun dengan `status != aktif`
  langsung di-logout dan diblokir.
- **Konfigurasi sesi aman** ([`config/session.php`](config/session.php)):
  - `http_only = true` — cookie sesi tidak bisa dibaca JavaScript (mitigasi
    pencurian sesi lewat XSS).
  - `same_site = lax` — mitigasi CSRF lintas situs.
  - `secure` — dapat dipaksa HTTPS-only lewat env `SESSION_SECURE_COOKIE`.
  - `serialization = json` — **bukan** `php`, sehingga aman dari serangan
    *PHP object injection / gadget chain* bila APP_KEY bocor.
  - Driver `database`, masa berlaku 240 menit.

## 4. Otorisasi Berbasis Peran (RBAC)

Berkas: [`app/Http/Middleware/EnsureRole.php`](app/Http/Middleware/EnsureRole.php),
[`app/Models/Role.php`](app/Models/Role.php),
[`routes/web.php`](routes/web.php)

Lima peran: `admin`, `manajer`, `operasional`, `pemeliharaan`, `safety`. Setiap
grup route dikunci middleware `role:` dengan dua mode:

- **Allow-list:** `role:admin` atau `role:operasional,pemeliharaan,safety`.
- **Deny-list:** `role:except,admin,manajer` (semua kecuali yang disebut).

Prinsip keamanan yang diterapkan:

- **Fail-closed.** Peran tanpa dashboard yang dikenal langsung `abort(403)`,
  bukan diarahkan sembarangan; ada penjagaan anti redirect-loop.
- **Pemisahan respons.** Permintaan JSON/API menerima `401`/`403`; permintaan
  halaman diarahkan ke dashboard-nya sendiri dengan pesan.
- **Normalisasi peran** (`Role::normalize`) menangani alias legacy (`petugas`
  → `operasional`) agar pengecekan tidak bisa di-bypass lewat variasi ejaan.
- **Perlindungan admin terakhir.** `isLastActiveAdmin()` mencegah admin
  menonaktifkan/menghapus dirinya sendiri sehingga sistem tak pernah kehilangan
  seluruh admin aktif (mencegah lockout total / kehilangan kendali).
- Route guest & auth dipisah tegas (`middleware('guest')` vs `middleware('auth')`),
  dengan `redirectTo` yang mengarahkan tamu ke login dan user ke dashboard-nya
  ([`bootstrap/app.php`](bootstrap/app.php)).

## 5. Anti Web Defacement & Injeksi Konten Judol

"Defacement" dan penyisipan konten judi online biasanya masuk lewat: (a) form
publik tanpa auth, (b) upload file arbitrer, (c) output HTML mentah dari input
user, atau (d) endpoint tulis yang tidak divalidasi. Semua jalur itu ditutup:

- **Tidak ada endpoint tulis publik.** Seluruh route `POST/PUT/PATCH/DELETE`
  berada di dalam grup `auth` + `role`. Tidak ada guestbook, komentar, atau form
  publik yang bisa disalahgunakan untuk menanam backlink/konten judol.
- **Output di-escape.** Blade meng-escape otomatis lewat `{{ }}`. Tempat yang
  memakai output mentah `{!! !!}` (mis. highlight pencarian di log admin) tetap
  aman karena datanya **sudah di-escape lebih dulu** dengan `e()` sebelum
  markup `<mark>` disisipkan — lihat `AdminV2Controller` baris ~1601
  (`'desc' => e($activity->description)`). Jadi input user tidak pernah dirender
  sebagai HTML aktif.
- **Sanitasi tambahan.** Sebagian input dibersihkan dengan `strip_tags()`
  sebelum diproses.
- **Upload sangat dibatasi.** Satu-satunya upload yang ada adalah tanda tangan
  (`signature`) dengan aturan ketat:
  `['nullable','file','mimes:png','mimetypes:image/png','max:2048']` — hanya PNG
  asli (dicek MIME nyata, bukan sekadar ekstensi), maksimal 2 MB. File disimpan
  dengan **nama yang di-generate acak** (`Str::slug` + timestamp +
  `Str::random`), sehingga tidak bisa menimpa file lain atau menanam skrip
  `.php`.
- **APP_DEBUG & APP_ENV** diharapkan `false`/`production` di server sehingga
  jejak error tidak bocor ke publik.

## 6. Perlindungan CSRF

- Middleware CSRF bawaan Laravel aktif untuk semua route web; setiap form
  memakai token `@csrf`.
- **Penanganan token kedaluwarsa yang mulus.** `TokenMismatchException` (error
  419 "Page Expired") ditangkap di [`bootstrap/app.php`](bootstrap/app.php) dan
  dialihkan ke halaman login dengan pesan — mencegah kebocoran halaman error dan
  memaksa sesi baru.
- Cookie `same_site=lax` menambah lapisan mitigasi CSRF di sisi browser.

## 7. Perlindungan SQL Injection

- **Eloquent ORM & Query Builder** dipakai di seluruh controller — query
  ter-parameterisasi (prepared statements), bukan konkatenasi string SQL.
- **Validasi ketat di setiap endpoint** lewat `$request->validate([...])`,
  termasuk aturan `exists:`, `unique:`, `in:`, `date_format:`, batas `max:`, dan
  pengecekan tipe. Contoh: perubahan peran user divalidasi
  `role_id => ['required','exists:roles,id']`.
- **Mass-assignment terjaga.** Model memakai `$guarded = ['id']`, sehingga kolom
  `id` tidak bisa ditimpa lewat input massal.
- Pencarian (trait `SearchesReports`) membangun pola `LIKE` melalui binding
  parameter, bukan menyisipkan keyword mentah ke SQL.

## 8. Keamanan Berkas & Path Traversal

- Operasi berkas (backup, tanda tangan, PDF arsip) memakai `Storage` disk dan
  selalu memvalidasi keberadaan file dengan `abort_unless(...exists, 404)`.
- Nama berkas dinormalisasi dengan `basename()` sebelum dipakai, mencegah
  path traversal (`../`).
- **Restore backup tidak otomatis.** Endpoint restore sengaja *hanya mencatat
  permintaan* dan tidak menimpa database, karena pemulihan adalah operasi
  destruktif — dilakukan manual oleh admin server. Ini mencegah kerusakan/timpa
  data secara tidak sengaja atau lewat penyalahgunaan.

## 9. Keamanan Basis Data (Transport)

- Koneksi MySQL/MariaDB mendukung **SSL/TLS** via opsi
  `Mysql::ATTR_SSL_CA` (env `MYSQL_ATTR_SSL_CA`) di
  [`config/database.php`](config/database.php), sehingga data dalam perjalanan
  bisa dienkripsi.
- Mode `strict = true` diaktifkan untuk mencegah perilaku data yang longgar
  (mis. penyisipan nilai yang terpotong diam-diam).
- Charset `utf8mb4` penuh — aman untuk seluruh karakter tanpa trik encoding.

## 10. Audit Trail Aktivitas Admin

Selain event keamanan login, setiap aksi sensitif admin/manajer (buat/ubah/hapus
user, master data, backup, approval, unduh) dicatat via `recordActivity()` ke
`admin_activity_logs` dengan tipe aksi, deskripsi, dan IP. Ini memberi
akuntabilitas dan mempercepat investigasi bila terjadi insiden.

---

## Ringkasan Ancaman → Mitigasi

| Ancaman | Mitigasi yang diterapkan |
|---------|--------------------------|
| Brute force / credential stuffing | Rate limit ganda (identitas 5/60s, IP 20/300s), logging lockout, pesan generik |
| Pencurian sesi | Cookie `http_only` + `same_site` + regenerasi sesi, serialisasi JSON |
| Password bocor | Hash bawaan Laravel, `$hidden`, tidak pernah plaintext |
| Akses tak sah / privilege escalation | Middleware `EnsureRole` (allow/deny-list), fail-closed 403, proteksi admin terakhir |
| Web defacement / injeksi judol | Tak ada endpoint tulis publik, upload dibatasi PNG asli, nama file acak |
| XSS | Auto-escape Blade `{{ }}`, `e()` sebelum output mentah, `strip_tags` |
| SQL Injection | Eloquent/Query Builder terparameterisasi, validasi `exists/in/unique`, `$guarded` |
| CSRF | Token `@csrf`, penanganan 419, cookie `same_site=lax` |
| Path traversal / kerusakan file | `basename()`, `Storage` + `abort_unless`, restore manual |
| Kehilangan jejak insiden | Audit log aktivitas + event keamanan dengan IP & User-Agent |

## Rekomendasi Pengerasan Lanjutan (belum tentu diterapkan)

Bukan kelemahan aktif, melainkan penguatan opsional untuk lingkungan produksi:

1. Pastikan `APP_DEBUG=false`, `APP_ENV=production`, dan
   `SESSION_SECURE_COOKIE=true` di server (paksa HTTPS).
2. Tambahkan security headers (HSTS, `X-Frame-Options`, `Content-Security-Policy`,
   `X-Content-Type-Options`) via middleware/web server untuk lapisan anti-clickjacking
   dan anti-XSS ekstra.
3. Pertimbangkan verifikasi ulang (mis. 2FA) untuk peran admin.
4. Aktifkan `SESSION_ENCRYPT=true` bila data sesi dianggap sensitif.
5. Rotasi & pembatasan izin folder `public/signatures` dan `storage` di server.
