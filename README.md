# Sistem Laporan Operasional KSS

Aplikasi web internal untuk pencatatan laporan shift harian operasional KSS. Sistem ini mencakup pengisian laporan operasional, tanda tangan regu penerima, approval manajer, arsip laporan, data master, manajemen user, backup, dan pusat bantuan admin.

## Ringkasan Fitur

- Login berbasis username dengan role `admin`, `manajer`, `operasional`, `pemeliharaan`, dan `safety`.
- Form laporan operasional 7 langkah: Info Umum, Muat Kantong, Muat Curah, Bongkar, Tracking, Cek Unit, dan Karyawan.
- Draft laporan, validasi step-by-step, tanda tangan regu penerima, export PDF/Excel, dan arsip laporan.
- Dashboard manajer dengan approval final, arsip, pencarian, filter, dan pusat bantuan.
- Dashboard admin dengan kelola pengguna, upload tanda tangan PNG, toggle status user, data master, log aktivitas, backup, dan pusat bantuan.
- Pencarian server-side, debounce search, pagination, toast message, modal konfirmasi, serta date/time picker custom untuk input tanggal dan jam operasional.

## Stack

- PHP 8.3+
- Laravel 13
- Blade
- SQLite untuk development
- Vite
- Bootstrap utility via CDN
- Tailwind CSS 4
- DomPDF
- PhpSpreadsheet
- PHPUnit

## Setup Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

URL default:

```text
http://localhost:8000
```

## Pengujian

```bash
php artisan test
```

## Dokumentasi

- [DOKUMENTASI.md](DOKUMENTASI.md) - dokumentasi teknis utama project.
- [PEMBARUAN_IMPLEMENTASI.md](PEMBARUAN_IMPLEMENTASI.md) - catatan pembaruan fitur dan implementasi.
- [LANDASAN_TEORI_SKRIPSI.md](LANDASAN_TEORI_SKRIPSI.md) - bahan teori, metode, dan logika implementasi yang dapat dikembangkan untuk skripsi.

## Route Utama

- `/` dan `/login` - halaman login.
- `/report-ops` - dashboard petugas operasional.
- `/report-ops/create` - form laporan operasional.
- `/manajer` - dashboard manajer.
- `/manajer/archive` - arsip laporan manajer.
- `/admin` - dashboard admin.
- `/admin/archive` - arsip laporan admin.
- `/admin/user-manage` - kelola pengguna.
- `/admin/datamaster` - data master.
- `/admin/backup` - manajemen backup.
- `/admin/help` - pusat bantuan admin.

