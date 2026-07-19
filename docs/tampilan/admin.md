# Tampilan Admin

Peran **Admin** adalah pengelola penuh sistem: mengatur pengguna, data master, backup, dan mengawasi seluruh laporan dari ketiga divisi (Operasional, Pemeliharaan, Safety/K3).

- **Route prefix**: `/admin` (middleware `role:admin`)
- **Controller**: `app/Http/Controllers/AdminV2Controller.php`
- **Layout**: `resources/views/admin/layouts/*` (sidebar + navbar)

## Menu & Fungsi

### Dashboard Sistem
`admin.index` — `resources/views/admin/index.blade.php`
Ringkasan kondisi sistem: kartu statistik (Total Pengguna Aktif, Kapasitas Server Terpakai, Status Backup Terakhir), panel Log Aktivitas terbaru, dan panel "Aksi Cepat IT" berisi dua aksi cepat:
- **Generate Manual Backup** — memicu backup manual.
- **Tambah Pengguna Baru** — modal form untuk membuat akun baru (nama, username, role, regu A–D/Kantor, status aktif/nonaktif, password awal, upload tanda tangan PNG).

### Arsip Laporan
`admin.archive` — `resources/views/admin/archive.blade.php`
Tabel seluruh laporan dari ketiga divisi (Operasional, Pemeliharaan, Safety) lintas status (diserahkan, diterima, diarsipkan). Kolom: No, Info Dokumen, Tanggal, Divisi, Regu, Shift, Status, Aksi. Admin dapat melihat, mengunduh, dan menghapus laporan apa pun.

### Log Aktivitas
`admin.log` — `resources/views/admin/log.blade.php`
Jejak audit sistem: Pengguna, Waktu, Tipe Aktivitas, Deskripsi, IP Address — untuk keperluan keamanan dan pelacakan tindakan pengguna.

### Kelola Pengguna
`admin.user-manage` — `resources/views/admin/user-manage.blade.php`
Manajemen akun staf: tabel (Nama Lengkap, Username, Role, Regu, Status, Aksi) dengan CRUD penuh — tambah, ubah, aktif/nonaktifkan akun, reset sandi, dan pengaturan hak akses berbasis role.

### Data Master
`admin.datamaster` — `resources/views/admin/datamaster.blade.php`
Pusat konfigurasi data acuan yang dipakai form laporan di divisi lain, terbagi 7 tab:
- **Data Karyawan** — NPK, Nama, Regu, Jabatan, Divisi, Jam Kerja
- **Data Unit** — Nama, Kode, Merk, Plat, Tipe, Kategori, Cek Unit, Tahun
- **Data Truck** — Nama, Nomor Plat, Keterangan
- **Data Inventaris** — Nama, Kategori, Jumlah
- **Data Lingkungan Operasi**
- **Data Lokasi K3**
- **Data Item K3**

### Manajemen Backup
`admin.backup` — `resources/views/admin/backup.blade.php`
Daftar file backup, status, dan jadwal. Aksi: generate manual/tahunan, ubah jadwal otomatis, unduh, hapus, atau pulihkan (restore) backup.

### Pusat Bantuan
`admin.help` — `resources/views/admin/help.blade.php`
Dokumentasi internal penggunaan panel admin: ringkasan peran, penjelasan tiap menu, alur & status laporan, tabel peran & hak akses, serta form pengajuan tiket bantuan.

## Ringkasan Fungsi Utama
1. Mengelola akun pengguna dan hak akses seluruh peran.
2. Mengelola data master (referensi) yang dipakai form laporan divisi lain.
3. Mengawasi, mengunduh, dan menghapus seluruh laporan lintas divisi.
4. Memantau log aktivitas/audit sistem.
5. Mengelola backup data sistem.
