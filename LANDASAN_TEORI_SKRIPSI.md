# Landasan Teori dan Metode Project KSS

Dokumen ini berisi kumpulan teknik, konsep, dan logika pengembangan yang dapat dijadikan bahan landasan teori skripsi untuk project Sistem Laporan Operasional KSS. Isinya disusun sebagai bahan mentah akademik: bisa dipilih, diringkas, lalu dikembangkan dengan sitasi dari buku, jurnal, dokumentasi Laravel, atau referensi rekayasa perangkat lunak yang digunakan di kampus.

---

## 1. Sistem Informasi Operasional

Sistem informasi operasional adalah sistem yang membantu pencatatan, pengolahan, penyimpanan, dan penyajian data kegiatan harian suatu organisasi. Pada project ini, sistem digunakan untuk menggantikan pencatatan laporan shift secara manual menjadi laporan digital yang lebih terstruktur.

Penerapan pada project:

- Petugas membuat laporan shift harian melalui form multi-step.
- Data operasional disimpan ke database agar dapat dicari kembali.
- Manajer dapat memantau laporan yang masuk dan memberikan persetujuan.
- Admin mengelola user, data master, arsip, log aktivitas, backup, dan pusat bantuan.
- Laporan dapat diekspor menjadi PDF dan Excel sebagai dokumen arsip.

Logika teoritis yang dapat ditulis:

- Sistem informasi membantu mengurangi duplikasi pencatatan.
- Data tersimpan secara terpusat sehingga memudahkan pencarian dan audit.
- Proses manual yang berulang dapat diubah menjadi workflow digital.
- Laporan digital mendukung efisiensi, konsistensi data, dan kemudahan distribusi informasi.

Contoh narasi skripsi:

> Sistem informasi operasional pada penelitian ini digunakan untuk mendigitalisasi proses pencatatan laporan shift harian. Dengan sistem tersebut, data yang sebelumnya dicatat secara manual dapat dikelola melalui aplikasi berbasis web sehingga proses pencarian, validasi, persetujuan, dan pengarsipan laporan menjadi lebih terstruktur.

---

## 2. Metode Pengembangan Incremental

Metode incremental adalah pendekatan pengembangan perangkat lunak yang membagi sistem menjadi beberapa bagian kecil. Setiap bagian dikembangkan, diuji, dan disempurnakan secara bertahap sampai sistem menjadi lengkap.

Alasan metode ini cocok untuk project:

- Kebutuhan sistem berkembang secara bertahap.
- Fitur operasional, manajer, dan admin dapat dibangun per modul.
- Setiap perubahan dapat diuji sebelum fitur berikutnya ditambahkan.
- Perbaikan UI dan workflow dapat mengikuti umpan balik pengguna.

Pemetaan increment pada project:

| Increment | Fokus Implementasi |
|---|---|
| 1 | Login, role, dashboard operasional, dan form laporan dasar |
| 2 | Multi-step form, validasi, draft, tanda tangan regu penerima, export PDF/Excel |
| 3 | Dashboard manajer, arsip laporan, approval, pencarian, dan pusat bantuan |
| 4 | Modul admin: kelola user, data master, backup, log aktivitas, arsip, dan pusat bantuan |
| 5 | Penyempurnaan UX: modal konfirmasi, toast message, toggle status, debounce search, date/time picker |

Contoh narasi skripsi:

> Metode incremental digunakan karena sistem dikembangkan secara bertahap berdasarkan kebutuhan pengguna. Setiap modul, seperti laporan operasional, manajemen manajer, dan administrasi sistem, diimplementasikan sebagai peningkatan terpisah sehingga proses pengujian dan evaluasi dapat dilakukan pada setiap tahap pengembangan.

---

## 3. Arsitektur MVC

MVC atau Model-View-Controller adalah pola arsitektur yang memisahkan aplikasi menjadi tiga bagian utama:

| Bagian | Fungsi | Contoh di Project |
|---|---|---|
| Model | Mengelola data dan relasi database | `DailyReport`, `User`, `MasterEmployee`, `Role` |
| View | Menampilkan antarmuka pengguna | Blade di `resources/views` |
| Controller | Mengatur request, validasi, proses bisnis, dan response | `ReportOpsController`, `ManajerController`, `AdminV2Controller` |

Manfaat MVC:

- Kode lebih mudah dibaca dan dipelihara.
- Logika bisnis tidak bercampur dengan tampilan.
- Perubahan tampilan tidak selalu mengganggu proses backend.
- Model dapat digunakan kembali oleh beberapa controller.

Contoh penerapan:

- `ReportOpsController` menerima input laporan, memvalidasi data, menyimpan ke model, lalu mengembalikan view atau redirect.
- Blade menampilkan form, tabel, modal, toast, dan komponen UI.
- Eloquent Model menghubungkan tabel laporan dengan tabel aktivitas muat, bongkar, tracking, cek unit, dan karyawan.

---

## 4. Laravel Blade sebagai Server-Side Rendering

Blade adalah template engine Laravel yang digunakan untuk membangun halaman HTML dinamis. Project ini menggunakan Blade karena sebagian besar data berasal dari server dan perlu dirender sesuai role pengguna.

Konsep penting:

- `@extends` untuk memakai layout utama.
- `@section` dan `@yield` untuk mengisi konten halaman.
- `@include` untuk memecah komponen view.
- `@csrf` untuk keamanan form.
- `@method('PUT')`, `@method('DELETE')`, dan method spoofing untuk request selain GET/POST.
- `@error` dan session flash untuk pesan validasi atau notifikasi.

Penerapan pada project:

- Halaman operasional memakai layout `report-ops`.
- Halaman manajer memakai layout khusus manajer.
- Halaman admin memakai layout khusus admin.
- Modal dan komponen date/time picker dibuat reusable agar tampilan lebih konsisten.

---

## 5. Role-Based Access Control

Role-Based Access Control atau RBAC adalah teknik pembatasan akses berdasarkan peran pengguna. Dalam project ini, role digunakan untuk membedakan hak akses antara admin, manajer, operasional, pemeliharaan, dan safety.

Role utama:

| Role | Hak Akses Utama |
|---|---|
| Admin | Mengelola sistem, user, data master, arsip, backup, dan log aktivitas |
| Manajer | Melihat laporan masuk, melakukan approval final, dan membuka arsip manajer |
| Operasional | Membuat laporan, menandatangani laporan masuk regu, dan mengelola draft |
| Pemeliharaan | Disiapkan untuk modul pengembangan pemeliharaan |
| Safety | Disiapkan untuk modul pengembangan keselamatan kerja |

Logika penting:

- Admin tidak diberi tombol approve laporan karena approval adalah tanggung jawab manajer.
- Manajer diarahkan ke area `/manajer`.
- User operasional diarahkan ke area `/report-ops`.
- Middleware memastikan user hanya mengakses area sesuai role.
- Status user dapat dinonaktifkan oleh admin melalui toggle.

Contoh narasi skripsi:

> Pembatasan akses pada sistem menggunakan konsep Role-Based Access Control. Setiap pengguna memiliki role tertentu yang menentukan menu dan aksi yang dapat dilakukan. Dengan pendekatan ini, sistem dapat menerapkan prinsip pemisahan tugas sehingga admin, manajer, dan petugas operasional memiliki kewenangan yang berbeda.

---

## 6. Separation of Duties

Separation of Duties adalah prinsip pemisahan tanggung jawab agar satu pengguna tidak memiliki seluruh kendali terhadap proses penting. Pada project ini, prinsip tersebut terlihat pada pemisahan tugas admin, petugas, dan manajer.

Penerapan:

- Petugas operasional membuat dan menyerahkan laporan.
- Regu penerima menandatangani laporan sebagai penerima.
- Manajer melakukan approval final.
- Admin mengelola konfigurasi sistem, tetapi tidak menyetujui laporan.

Manfaat:

- Mengurangi risiko penyalahgunaan akses.
- Membuat alur persetujuan lebih akuntabel.
- Mendukung kebutuhan audit karena setiap aksi dilakukan oleh role yang jelas.

---

## 7. CRUD dan Master Data

CRUD adalah singkatan dari Create, Read, Update, Delete. Konsep ini digunakan pada modul data master dan pengelolaan user.

Penerapan pada project:

- Create: admin menambahkan user, karyawan, unit, truck, dan inventaris.
- Read: data ditampilkan dalam tabel dengan pencarian dan pagination.
- Update: admin mengubah data melalui modal edit.
- Delete: penghapusan memakai modal konfirmasi agar tidak dieksekusi tanpa sengaja.

Best practice yang diterapkan:

- Form tambah/edit menggunakan modal agar user tidak berpindah halaman.
- Aksi sensitif seperti hapus memakai dialog konfirmasi.
- Pesan hasil aksi memakai toast agar feedback lebih cepat terbaca.
- Pencarian data master memakai debounce agar server tidak menerima request setiap huruf.

---

## 8. Workflow Status Laporan

Workflow status digunakan untuk menunjukkan posisi laporan dalam alur proses. Pada project ini, laporan memiliki beberapa status:

| Status | Arti |
|---|---|
| `draft` | Laporan masih sementara dan belum dikirim |
| `submitted` | Laporan sudah diserahkan ke regu penerima |
| `acknowledged` | Laporan sudah ditandatangani oleh regu penerima |
| `approved` | Laporan sudah disetujui oleh manajer |

Logika status:

- Laporan baru dapat disimpan sebagai draft.
- Saat disubmit, laporan berubah menjadi `submitted`.
- Regu penerima menandatangani laporan dan status berubah menjadi `acknowledged`.
- Manajer menyetujui laporan dan status berubah menjadi `approved`.
- Admin dapat melihat, mengunduh, dan menghapus arsip sesuai kebutuhan, tetapi tidak melakukan approval.

Konsep akademik yang bisa digunakan:

- State machine sederhana.
- Workflow management.
- Audit trail berdasarkan perubahan status.
- Pembatasan aksi berdasarkan status dan role.

---

## 9. Form Multi-Step

Form multi-step adalah form panjang yang dibagi menjadi beberapa bagian agar lebih mudah diisi. Project ini menggunakan form 7 langkah untuk laporan operasional.

Step laporan:

1. Info Umum.
2. Muat Kantong.
3. Muat Curah.
4. Bongkar.
5. Tracking.
6. Cek Unit.
7. Karyawan.

Manfaat:

- Mengurangi beban kognitif pengguna.
- Validasi dapat dilakukan per bagian.
- Tampilan lebih rapi untuk data operasional yang banyak.
- User dapat menyimpan draft sebelum laporan selesai.

Logika teknis:

- Navigasi step dikendalikan JavaScript.
- Tombol lanjut memvalidasi step aktif.
- Payload form disinkronkan ke JSON agar data dinamis dapat dipulihkan saat edit.
- Tabel dinamis memakai template row dan event handler untuk tambah/edit/hapus data.

---

## 10. Validasi Data

Validasi data memastikan input user sesuai aturan sistem. Pada project ini validasi dilakukan di frontend dan backend.

Frontend validation:

- Field wajib diberi atribut `required`.
- Input angka dibatasi agar tidak negatif.
- Group penerima tidak boleh sama dengan group pengirim.
- Step aktif divalidasi sebelum user lanjut ke step berikutnya.
- Modal konfirmasi digunakan sebelum aksi final.

Backend validation:

- Controller memvalidasi request sebelum menyimpan data.
- Angka negatif tetap dinormalisasi meskipun lolos dari frontend.
- File tanda tangan hanya menerima PNG.
- Status laporan hanya menerima nilai yang diizinkan.
- Akses edit/hapus dicek berdasarkan user, role, dan status laporan.

Contoh narasi skripsi:

> Validasi dilakukan pada sisi client dan server untuk menjaga integritas data. Validasi client membantu pengguna memperbaiki kesalahan input lebih cepat, sedangkan validasi server memastikan data yang masuk ke database tetap aman dan sesuai aturan meskipun request dimanipulasi.

---

## 11. Date Picker dan Date-Time Picker

Input tanggal dan waktu sangat penting pada laporan operasional karena data shift berkaitan dengan waktu kejadian. Project ini menggunakan dua pendekatan:

- Info Umum memakai input tanggal native HTML agar tanggal laporan otomatis terisi dan tetap mudah diubah.
- Muat Kantong dan Muat Curah memakai date-time picker custom bergaya shadcn untuk memilih tanggal dan jam 24 jam.

Fitur date-time picker:

- Format 24 jam.
- Tombol "Hari ini" untuk mengisi tanggal hari berjalan.
- Tombol "Hapus" untuk mengosongkan nilai.
- Navigasi bulan.
- Trigger custom agar visualnya sama dengan input lain.
- Dukungan Enter untuk lanjut ke input berikutnya.
- Saat fokus masuk ke date-time picker, kalender dapat terbuka otomatis.

Logika teoritis:

- Consistency of input control.
- User experience pada input temporal.
- Prevention of input error.
- Keyboard accessibility untuk mempercepat entry data.

---

## 12. Pencarian Server-Side, Debounce, dan Pagination

Pencarian server-side digunakan ketika data berpotensi banyak dan tidak cukup difilter hanya pada halaman yang sedang tampil. Project ini memakai kombinasi pencarian server-side, suggestion, debounce, dan pagination.

Teknik yang digunakan:

- Query parameter `q` untuk keyword pencarian.
- Server mencari data berdasarkan ID, tanggal, shift, regu, user, dan relasi laporan.
- Debounce menunda eksekusi pencarian sampai user berhenti mengetik.
- `AbortController` membatalkan request suggestion sebelumnya agar response lama tidak menimpa hasil terbaru.
- Pagination menjaga tabel tetap ringan dan mudah dibaca.

Penerapan:

- Riwayat laporan operasional.
- Arsip laporan manajer.
- Data master admin.
- Arsip laporan admin.

Contoh narasi skripsi:

> Pencarian pada sistem menerapkan debounce untuk mengurangi jumlah request ke server. Dengan debounce, sistem tidak langsung menjalankan pencarian pada setiap karakter yang diketik, tetapi menunggu jeda tertentu setelah pengguna berhenti mengetik. Teknik ini meningkatkan efisiensi request dan menjaga respons antarmuka tetap stabil.

---

## 13. Upload File Tanda Tangan Digital

Tanda tangan digital pada project ini berupa file gambar PNG yang diunggah oleh admin ketika membuat atau mengedit akun user.

Logika implementasi:

- File divalidasi agar hanya menerima format PNG.
- File disimpan ke folder `public/signatures`.
- Path file disimpan pada kolom `signature_path`.
- Path tersebut digunakan saat laporan perlu menampilkan tanda tangan user.

Best practice:

- Batasi tipe file yang diterima.
- Gunakan nama file unik agar tidak menimpa file lama.
- Simpan path relatif, bukan path absolut komputer.
- Hindari menyimpan file langsung ke database jika file bisa disimpan di storage publik.

---

## 14. Export PDF dan Excel

Export dokumen digunakan agar laporan dapat disimpan sebagai arsip formal.

Penerapan:

- PDF dibuat menggunakan DomPDF.
- Excel dibuat menggunakan PhpSpreadsheet.
- PDF arsip dapat dicache agar download berikutnya lebih cepat.
- Nama file dibuat berdasarkan tanggal, shift, dan group agar mudah dikenali.

Konsep teoritis:

- Report generation.
- Document rendering.
- Data transformation dari record database ke format dokumen.
- Caching hasil export untuk efisiensi.

---

## 15. Backup dan Restore

Backup adalah proses membuat salinan data agar sistem dapat dipulihkan jika terjadi kerusakan atau kehilangan data. Modul admin menyediakan manajemen backup sebagai bagian dari kontrol operasional sistem.

Penerapan:

- Admin dapat generate backup manual.
- Jadwal backup dapat diatur.
- File backup dapat diunduh, dihapus, atau dicatat untuk restore.
- Aktivitas backup dicatat pada log admin.

Konsep akademik:

- Data recovery.
- Disaster recovery planning.
- Retensi backup.
- Audit terhadap aktivitas administratif.

---

## 16. Toast Message dan Modal Konfirmasi

Toast message adalah notifikasi singkat yang muncul untuk memberi feedback terhadap aksi user. Modal konfirmasi digunakan untuk memastikan aksi penting tidak dilakukan tanpa persetujuan.

Penerapan toast:

- Pesan sukses setelah tambah, edit, hapus, update status, backup, atau login error.
- Pesan error validasi atau kegagalan aksi.
- Styling konsisten dengan halaman operasional, manajer, dan admin.

Penerapan modal:

- Hapus data.
- Restore backup.
- Generate backup.
- Tambah/edit user.
- Tambah/edit data master.
- Finalisasi laporan.

Manfaat UX:

- User mendapat feedback langsung.
- Aksi berisiko membutuhkan konfirmasi.
- UI lebih rapi karena form tambah/edit tidak harus membuka halaman baru.

---

## 17. Responsive Design

Responsive design memastikan aplikasi dapat digunakan pada berbagai ukuran layar. Project ini menerapkan layout yang menyesuaikan tampilan desktop, tablet, dan mobile.

Teknik:

- Sidebar off-canvas pada layar kecil.
- Tabel memakai horizontal scroll ketika kolom terlalu banyak.
- Card statistik menyesuaikan jumlah kolom.
- Tombol dan toolbar disusun ulang agar tidak bertumpuk.
- Modal menggunakan lebar responsif.

Manfaat:

- Aplikasi tetap dapat dipakai di laptop kantor maupun perangkat mobile.
- Tabel operasional yang kompleks masih dapat dibaca.
- Navigasi tetap jelas meskipun layar sempit.

---

## 18. Audit Log

Audit log adalah pencatatan aktivitas penting pada sistem. Fitur ini berguna untuk mengetahui siapa melakukan apa dan kapan.

Aktivitas yang layak dicatat:

- Login dan logout.
- Perubahan status user.
- Tambah/edit/hapus data master.
- Generate, download, delete, dan restore backup.
- Hapus arsip laporan.
- Approval laporan.

Manfaat:

- Memudahkan pelacakan masalah.
- Mendukung keamanan dan akuntabilitas.
- Menjadi bukti aktivitas sistem saat evaluasi.

---

## 19. Pengujian Perangkat Lunak

Pengujian dilakukan untuk memastikan fitur berjalan sesuai kebutuhan. Project ini memakai PHPUnit untuk feature test Laravel.

Jenis pengujian yang relevan:

| Jenis Pengujian | Tujuan | Contoh |
|---|---|---|
| Unit Test | Menguji fungsi kecil secara terpisah | Helper parsing tanggal |
| Feature Test | Menguji alur request dan response | Login, akses admin, simpan laporan |
| Validation Test | Menguji aturan input | Angka negatif, file tanda tangan PNG |
| Access Test | Menguji pembatasan role | Manajer tidak masuk halaman operasional |
| Regression Test | Menjaga fitur lama tidak rusak | Pencarian, pagination, draft |

Contoh narasi skripsi:

> Pengujian sistem dilakukan menggunakan feature test untuk memastikan setiap alur utama, seperti autentikasi, pembatasan akses, penyimpanan laporan, pencarian, dan modul admin, berjalan sesuai kebutuhan. Pengujian ini juga membantu mencegah regresi ketika fitur baru ditambahkan.

---

## 20. Pemetaan Teori ke Fitur Project

| Teori/Konsep | Fitur Project |
|---|---|
| Sistem Informasi Operasional | Laporan shift harian berbasis web |
| Incremental Development | Pengembangan bertahap modul operasional, manajer, dan admin |
| MVC | Laravel controller, model, dan Blade view |
| RBAC | Role admin, manajer, operasional, pemeliharaan, safety |
| Separation of Duties | Admin tidak approve, manajer approve, petugas membuat laporan |
| CRUD | Kelola user dan data master |
| State Machine | Status draft, submitted, acknowledged, approved |
| Client-Server Validation | Validasi form dan validasi controller |
| Debounce Search | Pencarian data master dan suggestion laporan |
| File Upload | Tanda tangan PNG di `public/signatures` |
| Report Generation | Export PDF dan Excel |
| Backup Management | Generate dan kelola backup admin |
| UX Feedback | Toast message dan modal konfirmasi |
| Responsive Design | Sidebar mobile dan tabel scroll |
| Automated Testing | PHPUnit feature test |

---

## 21. Contoh Subbab Landasan Teori

Berikut susunan subbab yang dapat dimasukkan ke Bab II:

1. Sistem Informasi Operasional.
2. Metode Pengembangan Incremental.
3. Framework Laravel.
4. Arsitektur MVC.
5. Role-Based Access Control.
6. Database Relasional dan ORM.
7. Form Multi-Step dan Validasi Data.
8. Workflow Status Laporan.
9. Pencarian Data dan Debounce.
10. Digital Signature Berbasis Gambar.
11. Export PDF dan Excel.
12. Backup dan Audit Log.
13. User Experience pada Aplikasi Web.
14. Pengujian Perangkat Lunak.

---

## 22. Contoh Rumusan Metode Penelitian

Contoh narasi metode:

> Penelitian ini menggunakan metode pengembangan incremental. Metode tersebut dipilih karena sistem yang dibangun memiliki beberapa modul utama yang dapat dikembangkan secara bertahap, yaitu modul laporan operasional, modul manajer, dan modul admin. Setiap increment menghasilkan fitur yang dapat diuji dan dievaluasi sebelum pengembangan fitur berikutnya dilakukan.

Contoh narasi implementasi:

> Implementasi sistem menggunakan framework Laravel dengan pola arsitektur MVC. Model digunakan untuk merepresentasikan tabel database, controller digunakan untuk mengolah request dan proses bisnis, sedangkan Blade view digunakan untuk menampilkan antarmuka pengguna. Sistem juga menerapkan Role-Based Access Control untuk membedakan hak akses antara admin, manajer, dan petugas operasional.

Contoh narasi pengujian:

> Pengujian dilakukan menggunakan pendekatan black-box dan feature test. Pengujian black-box digunakan untuk memastikan fungsi sistem sesuai kebutuhan pengguna, sedangkan feature test Laravel digunakan untuk memastikan route, validasi, autentikasi, dan alur data berjalan dengan benar.

---

## 23. Catatan Penyusunan Skripsi

Saat dipindahkan ke skripsi:

- Tambahkan sitasi untuk definisi teori dari buku/jurnal/dokumentasi resmi.
- Hindari menulis terlalu banyak detail kode pada Bab II.
- Detail file, controller, dan route lebih cocok diletakkan di Bab IV implementasi.
- Gunakan tabel pemetaan teori ke fitur untuk menjelaskan hubungan antara konsep dan aplikasi yang dibangun.
- Jika kampus meminta metode tertentu, bagian incremental dapat disesuaikan menjadi waterfall, prototyping, atau RAD, tetapi jelaskan alasan pemilihannya berdasarkan proses project yang benar-benar dilakukan.

