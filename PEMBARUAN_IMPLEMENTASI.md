# Peta Pembaruan Implementasi

Dokumen ini merangkum pembaruan yang sudah diterapkan pada Sistem Laporan Operasional KSS. Tujuannya untuk memudahkan pelacakan perubahan fitur, perilaku sistem, area file yang terdampak, dan test yang melindungi perubahan tersebut.

Tanggal catatan: 21 Mei 2026

---

## Ringkasan Status

Fokus pembaruan terbaru:

- Role sistem dikembangkan menjadi 5 role.
- Form laporan dibuat lebih tahan terhadap kesalahan input dan masalah penyimpanan.
- Data operasi kapal dapat dipakai lintas shift selama pekerjaan belum selesai.
- Suggestion operasi kapal otomatis dibersihkan setelah 3 hari tidak digunakan.
- Draft laporan otomatis dibersihkan setelah 3 hari tidak dilanjutkan.
- Cek unit mengikuti kondisi laporan sebelumnya dan default kondisi serah mengikuti kondisi terima.
- Error login ditampilkan sebagai toast animatif agar petugas lebih cepat memperbaiki kredensial.
- UX dashboard dan pencarian diperhalus agar petugas lebih mudah menyelesaikan laporan.
- Modul manajer aktif dengan dashboard, laporan masuk, approval final, arsip laporan, pencarian arsip, dan pusat bantuan.
- Akses role manajer dibatasi ke route manajer dan dicegah masuk ke halaman divisi operasional.
- Halaman manajer dibuat mobile responsive dengan sidebar off-canvas, stats card adaptif, dan tab laporan horizontal scroll.
- Query list manajer diringankan dan statistik dicache singkat untuk mempercepat rendering halaman.
- Pencarian riwayat petugas dan arsip manajer diperbaiki agar hasil lintas pagination muncul setelah Enter.
- Suggestion pencarian sekarang menjadi pilihan keyword tabel, bukan membuka detail laporan.
- Nama bulan parsial yang tidak ambigu seperti `apri`, `janu`, `me`, dan `jul` didukung pada pencarian tanggal.
- Filter tanggal/regu/shift arsip manajer diposisikan di bawah baris pencarian.
- Toast sukses/error memakai gaya liquid glass yang mengikuti styling box login.
- Tombol Logout sidebar manajer memiliki hover, active, dan focus-visible state.

Verifikasi terakhir:

- `php -l` untuk file PHP/Blade yang diubah: aman.
- `php artisan test --filter=OpsFlowTest`: lulus `16 tests`, `135 assertions`.

---

## 1. Role Sistem Menjadi 5 Role

Sebelumnya sistem hanya memakai 2 role besar. Sekarang role dipetakan menjadi:

| Role Baru | Keterangan |
|---|---|
| `admin` | Administrasi sistem |
| `manajer` | Level manajerial/approval |
| `operasional` | Pengganti role petugas |
| `pemeliharaan` | Kebutuhan pengembangan pemeliharaan |
| `safety` | Kebutuhan pengembangan safety |

File utama:

- `app/Models/Role.php`
- `database/migrations/2026_05_19_000001_update_roles_to_five_development_roles.php`
- `database/seeders/RoleSeeder.php`

Catatan:

- Role `admin` lama dipecah menjadi `admin` dan `manajer`.
- Role `petugas` diarahkan menjadi `operasional`.
- Role `pemeliharaan` dan `safety` disiapkan untuk pengembangan lanjutan.

---

## 2. Validasi Alur Regu Pengirim dan Penerima

Sistem sekarang mencegah laporan dikirim ke regu yang sama.

Contoh:

- Group B tidak boleh mengirim ke Group B.
- Group A tidak boleh mengirim ke Group A.

Perilaku:

- Warning muncul saat `group_name` dan `received_by_group` sama.
- Saat submit final, field penerima diberi `setCustomValidity`, sehingga laporan tidak dapat dikirim.
- Draft tetap lebih longgar agar petugas masih bisa menyimpan data sementara.

File utama:

- `resources/views/report-ops/create.blade.php`
- `resources/views/report-ops/edit.blade.php`
- `app/Http/Controllers/ReportOpsController.php`
- `tests/Feature/OpsFlowTest.php`

---

## 3. Input Angka Dibuat Non-negatif

Semua input angka penting dibuat tidak boleh bernilai negatif.

Area yang terdampak:

- Kapasitas
- Jumlah/qty
- BBM/fuel
- COB
- Kuantitas pengiriman, pemuatan, kerusakan
- Input angka dinamis pada tabel lain

Perilaku frontend:

- `input[type="number"]` otomatis diberi `min="0"`.
- Tombol `-`, `+`, `e`, dan `E` diblok pada input angka.
- Nilai negatif yang masuk melalui paste/input akan dikoreksi menjadi `0`.
- Scroll mouse pada input angka yang sedang fokus dicegah agar nilai tidak berubah tanpa sengaja.

Perilaku backend:

- Helper numerik di controller meng-clamp angka negatif menjadi `0`.
- `fuel_level` yang berupa string numerik juga dinormalisasi agar tidak negatif.

File utama:

- `resources/views/report-ops/layouts/app.blade.php`
- `app/Http/Controllers/ReportOpsController.php`
- `tests/Feature/OpsFlowTest.php`

Test terkait:

- `test_negative_numeric_values_are_clamped_when_saving_report`

---

## 4. Step Gudang Turba Diubah Menjadi Tracking

Label user-facing untuk step 5 diubah menjadi `Tracking`.

Perubahan tampilan:

- Tab form: `Tracking`
- Header form: `Form Tracking`
- PDF/View PDF: `IV. Tracking Pengiriman Pupuk Kantong`

Catatan teknis:

- Nama file dan tabel masih memakai istilah legacy `turba` untuk menjaga kompatibilitas data dan menghindari refactor besar.

File utama:

- `resources/views/report-ops/create.blade.php`
- `resources/views/report-ops/edit.blade.php`
- `resources/views/report-ops/sections/step5-gudangturba.blade.php`
- `resources/views/report-ops/pdf.blade.php`
- `resources/views/report-ops/viewpdf.blade.php`

---

## 5. Shift Otomatis Berdasarkan Jam WITA

Pada form Info Umum, shift otomatis terisi saat membuat laporan baru jika field shift masih kosong.

Aturan WITA:

| Jam WITA | Shift | Jam Kerja |
|---|---|---|
| 07.00 - 14.59 | Pagi | 07.00 - 15.00 |
| 15.00 - 22.59 | Siang | 15.00 - 23.00 |
| 23.00 - 06.59 | Malam | 23.00 - 07.00 |

Perilaku:

- Berlaku pada form create.
- Edit mode tidak menimpa data lama.
- Jam kerja tetap sinkron jika user mengganti shift manual.

File utama:

- `resources/views/report-ops/create.blade.php`
- `resources/views/report-ops/edit.blade.php`
- `resources/views/report-ops/sections/step1-infoumum.blade.php`

---

## 6. Cek Unit Mengikuti Kondisi Laporan Sebelumnya

Default `Kondisi Terima` pada Cek Unit sekarang diambil dari `Kondisi Diserahkan` pada laporan sebelumnya.

Contoh:

- Laporan sebelumnya menyerahkan forklift sebagai `Rusak`.
- Laporan baru otomatis menerima forklift tersebut sebagai `Rusak`.

Kategori yang didukung:

- Unit kendaraan
- Inventaris
- Lingkungan/shelter

Sumber data:

- `unit_check_logs` dari laporan berstatus `submitted`, `acknowledged`, atau `approved`.
- Saat edit laporan, report yang sedang diedit dikecualikan dari lookup agar tidak membaca dirinya sendiri.

File utama:

- `app/Http/Controllers/ReportOpsController.php`
- `resources/views/report-ops/create.blade.php`
- `resources/views/report-ops/edit.blade.php`
- `tests/Feature/OpsFlowTest.php`

Test terkait:

- `test_create_form_uses_previous_handed_over_condition_as_check_unit_defaults`

---

## 7. Kondisi Diserahkan Default Mengikuti Kondisi Terima

Setelah kondisi terima ditentukan, kondisi diserahkan akan mengikuti nilai awal yang sama.

Perilaku:

- Jika `Kondisi Terima = Baik`, maka default `Kondisi Diserahkan = Baik`.
- Jika `Kondisi Terima = Rusak`, maka default `Kondisi Diserahkan = Rusak`.
- Jika user mengubah `Kondisi Terima`, `Kondisi Diserahkan` ikut berubah selama kolom serah belum diubah manual.
- Jika user sudah mengubah kolom serah manual, sistem tidak memaksa sinkron ulang.

File utama:

- `resources/views/report-ops/create.blade.php`
- `resources/views/report-ops/edit.blade.php`
- `resources/views/report-ops/sections/step6-cekunit.blade.php`

---

## 8. Ship Operation Suggestion untuk Pekerjaan Kapal Berkelanjutan

Muat Kantong dan Muat Curah sekarang menyimpan pekerjaan kapal aktif ke tabel `ship_operations`.

Tujuan:

- Kapal yang masih dikerjakan beberapa shift/hari dapat dipilih kembali dari suggestion.
- Petugas tidak perlu mengetik ulang nama kapal, agen, dermaga, tujuan, kapasitas, dan data terkait.

Perilaku Muat Kantong:

- Suggestion mengisi data kapal.
- Akumulasi sebelumnya otomatis masuk ke field `Lalu`:
  - `qty_delivery_prev`
  - `qty_loading_prev`
  - `qty_damage_prev`
- Status pekerjaan kapal dapat dipilih `Masih Berjalan` atau `Selesai`.

Perilaku Muat Curah:

- Suggestion mengisi data kapal curah.
- Status pekerjaan kapal juga memakai pilihan `Masih Berjalan` atau `Selesai`.

File utama:

- `database/migrations/2026_05_19_000002_create_ship_operations_table.php`
- `app/Models/ShipOperation.php`
- `app/Http/Controllers/ReportOpsController.php`
- `resources/views/report-ops/create.blade.php`
- `resources/views/report-ops/edit.blade.php`
- `resources/views/report-ops/sections/step2-muatkantong.blade.php`
- `resources/views/report-ops/sections/step3-muatcurah.blade.php`

Test terkait:

- `test_active_ship_operation_can_be_reused_and_completed`

---

## 9. Ship Operation Suggestion Kadaluarsa Setelah 3 Hari Tidak Dipakai

Jika operasi kapal masih `active` tetapi tidak digunakan/disimpan lagi selama lebih dari 3 hari, suggestion otomatis dihapus.

Contoh kasus:

- Petugas A membuat operasi kapal `KM Agusta`.
- Petugas B memakai suggestion tersebut tetapi lupa menekan `Selesai`.
- Jika setelah 3 hari tidak ada laporan baru yang memakai operasi kapal tersebut, suggestion akan dibersihkan otomatis.

Patokan waktu:

- Menggunakan `updated_at` dari `ship_operations`.
- Setiap kali kapal dipakai lagi di laporan, `updated_at` berubah, sehingga masa aktif suggestion diperpanjang lagi 3 hari.

Tempat cleanup dipanggil:

- Saat endpoint suggestion dibuka.
- Saat laporan disimpan.

Alasan cleanup juga dipanggil saat simpan:

- Mencegah hidden `ship_operation_id` lama menghidupkan kembali data kapal yang sudah kadaluarsa.

File utama:

- `app/Models/ShipOperation.php`
- `app/Http/Controllers/ReportOpsController.php`
- `tests/Feature/OpsFlowTest.php`

Test terkait:

- `test_stale_ship_operation_suggestions_are_pruned_after_three_days_for_bag_and_bulk_loading`

---

## 10. Dropdown Suggestion Nama Kapal Lebih Stabil

Dropdown suggestion nama kapal pada Muat Kantong dan Muat Curah diperbaiki agar tidak hilang saat mouse melewati gap kecil antara input dan dropdown.

Perilaku:

- Dropdown tetap terbuka selama pointer berada di input, dropdown, atau gap kecil di antaranya.
- Dropdown hilang saat pointer benar-benar keluar dari area tersebut.
- Dropdown muncul kembali saat input dipilih/fokus lagi.

File utama:

- `resources/views/report-ops/create.blade.php`
- `resources/views/report-ops/edit.blade.php`

---

## 11. Akumulasi Muat Kantong Tidak Aktif Jika Data Kapal Kosong

Bug sebelumnya:

- Field Pengiriman, Pemuatan, dan Kerusakan bisa menunjukkan angka walaupun data utama pemuatan/nama kapal belum diisi.

Perbaikan:

- Akumulasi hanya dianggap relevan jika detail pemuatan kapal sudah ada.
- Saat ship operation selection dibersihkan, nilai `Lalu` untuk pengiriman/pemuatan/kerusakan dikembalikan ke `0`.
- Ringkasan card akumulasi pada baris kosong direset ke `0`.

File utama:

- `resources/views/report-ops/create.blade.php`
- `resources/views/report-ops/edit.blade.php`

---

## 12. Reminder Draft Diberi Animasi

Reminder `Laporan Belum Diselesaikan` di dashboard dibuat lebih menarik perhatian.

Animasi:

- Pulse/glow halus pada box reminder.
- Wiggle ringan pada ikon info.
- Nudge dan efek kilau pada tombol `Lanjutkan Draft`.

Aksesibilitas:

- Animasi dimatikan jika user memakai `prefers-reduced-motion: reduce`.

File utama:

- `resources/views/report-ops/layouts/app.blade.php`

---

## 13. Draft Kadaluarsa Setelah 3 Hari Tidak Dilanjutkan

Draft laporan otomatis dihapus jika tidak dilanjutkan/disimpan ulang lebih dari 3 hari.

Patokan waktu:

- Menggunakan `updated_at` dari `daily_reports`.
- Setiap kali draft disimpan ulang, masa aktifnya diperpanjang lagi 3 hari.
- Draft tepat berumur 3 hari masih dipertahankan; draft yang melewati 3 hari dihapus.

Perilaku:

- Cleanup berjalan saat dashboard laporan dibuka.
- Cleanup juga berjalan saat form create dibuka.
- Jika user membuka/edit draft stale lewat URL langsung, draft dihapus dan user dikembalikan ke tab Draft dengan toast error.
- Detail relasi draft ikut hilang karena relasi tabel laporan memakai cascade delete.

File utama:

- `app/Models/DailyReport.php`
- `app/Http/Controllers/ReportOpsController.php`
- `tests/Feature/OpsFlowTest.php`

Test terkait:

- `test_stale_draft_reports_are_pruned_after_three_days_without_continuation`

---

## 14. Error Login Menggunakan Toast Attention

Pesan error login sekarang seragam dengan pola notifikasi toast, bukan alert inline.

Perilaku:

- Error validasi login muncul sebagai toast fixed di kanan atas.
- Toast punya animasi masuk dan gerak ringan untuk menarik perhatian.
- Card login ikut bergerak ringan saat kredensial salah.
- Field bermasalah diberi style invalid dan difokuskan kembali.
- Alert Bootstrap inline lama dihapus dari form.
- Animasi mengikuti `prefers-reduced-motion`.

File utama:

- `resources/views/auth/layouts/app.blade.php`
- `resources/views/auth/index.blade.php`
- `tests/Feature/OpsFlowTest.php`

Test terkait:

- `test_login_errors_are_rendered_as_attention_toast`

---

## 15. Search Riwayat dan Dropdown Pencarian

Dropdown pencarian riwayat laporan dibuat lebih nyaman digunakan.

Perilaku:

- Dropdown hilang saat klik/mouse keluar dari area pencarian dan dropdown.
- Dropdown muncul kembali saat input pencarian dipilih lagi.
- Keyboard navigation tetap didukung.
- Enter mengirim pencarian ke server sehingga hasil di halaman pagination lain tetap muncul di tabel.
- Klik suggestion memakai ID dokumen suggestion sebagai keyword pencarian tabel, bukan membuka detail laporan.
- Keyword tanggal dengan bulan parsial yang tidak ambigu seperti `apri`, `janu`, `me`, dan `jul` didukung oleh `buildDateSearchPatterns()`.
- Jika keyword terbaca sebagai tanggal, query difokuskan ke kolom `report_date` agar lebih ringan.

File utama:

- `resources/views/report-ops/index.blade.php`
- `resources/views/report-ops/layouts/app.blade.php`
- `app/Http/Controllers/ReportOpsController.php`
- `tests/Feature/OpsFlowTest.php`

Test terkait:

- `test_history_search_finds_date_from_later_pagination_page`
- `test_report_search_suggestions_accept_clear_partial_month_names`

---

## 16. Stabilitas Submit dan Draft

Beberapa titik submit dibuat lebih aman:

- Sebelum submit, form memanggil normalisasi input angka.
- Payload form disinkronkan sebelum simpan draft/submitted.
- Submit draft tetap melonggarkan required field agar petugas bisa menyimpan sementara.
- Submit final tetap menjalankan validasi penuh.

File utama:

- `resources/views/report-ops/create.blade.php`
- `resources/views/report-ops/edit.blade.php`
- `resources/views/report-ops/layouts/app.blade.php`
- `app/Http/Controllers/ReportOpsController.php`

---

## 17. Test Coverage yang Ditambahkan

Test fitur utama berada di:

- `tests/Feature/OpsFlowTest.php`

Area yang sudah ditutup test:

- Login petugas dengan username.
- Validasi regu penerima tidak boleh sama dengan regu pengirim.
- Angka negatif di-clamp menjadi `0`.
- Cek unit memakai kondisi diserahkan sebelumnya sebagai default kondisi terima.
- Ship operation aktif dapat dipakai ulang dan diselesaikan.
- Ship operation kadaluarsa setelah 3 hari untuk muat kantong dan muat curah.
- Draft laporan kadaluarsa setelah 3 hari tanpa dilanjutkan.
- Error login tampil sebagai toast attention dari atas-tengah layar, bukan alert inline.
- Laporan yang sudah ditandatangani keluar dari laporan masuk dan tetap masuk riwayat.
- Manajer diarahkan menjauh dari halaman operasional dan dikembalikan ke dashboard manajer.
- Manajer dapat meninjau laporan melalui route khusus manajer tanpa membuka route operasional.

Perintah verifikasi:

```bash
php artisan test
```

Hasil terakhir:

```text
16 tests, 135 assertions
```

---

## 18. Modul Dashboard Manajer

Role `manajer` sekarang memiliki dashboard terpisah di `/manajer`.

Fitur:

- Card statistik laporan hari ini, pending, bulan ini, dan total laporan.
- Daftar laporan masuk dari divisi.
- Tab divisi: Semua, Operasional, Pemeliharaan, Safety/K3.
- Untuk saat ini data aktif berasal dari Operasional, sementara Pemeliharaan dan Safety/K3 disiapkan sebagai pengembangan lanjutan.
- Modal konfirmasi tanda tangan digital manajer.

File utama:

- `app/Http/Controllers/ManajerController.php`
- `resources/views/manajer/index.blade.php`
- `resources/views/manajer/layouts/card.blade.php`
- `resources/views/manajer/layouts/app.blade.php`

---

## 19. Arsip Laporan Manajer

Menu Arsip Laporan menampilkan laporan yang sudah berada pada alur final manajerial.

Status yang ditampilkan:

- `submitted` sebagai "Diserahkan"
- `acknowledged` sebagai "Ditanda Tangani"
- `approved` sebagai "Ditanda Tangani"

Catatan:

- Arsip tidak bergantung pada status bernama `archived`.
- Label user-facing tidak memakai "Diarsipkan" agar sesuai dengan alur status sistem saat ini.
- Aksi arsip: lihat laporan, download PDF, dan hapus arsip.
- PDF yang sudah dibuat disimpan di `storage/app/public/reports` dan dipakai ulang saat download berikutnya.

File utama:

- `app/Http/Controllers/ManajerController.php`
- `resources/views/manajer/archive.blade.php`

---

## 20. Pencarian Arsip Manajer

Pencarian arsip dibuat mengikuti perilaku pencarian riwayat laporan operasional.

Perilaku:

- Search input memfilter baris pada halaman yang sedang dibuka tanpa reload.
- Endpoint `GET /manajer/archive/suggestions?q=...` mengembalikan maksimal 8 saran.
- Request suggestion memakai debounce dan request lama dibatalkan dengan `AbortController`.
- Dropdown tertutup saat pointer keluar dari area input, dropdown, dan safe gap kecil di antaranya.
- Keyboard navigation didukung dengan Arrow Up/Down, Enter, dan Escape.
- Enter menjalankan pencarian server agar hasil di pagination lain muncul di tabel.
- Klik suggestion memakai ID dokumen suggestion sebagai keyword pencarian tabel, bukan membuka detail laporan.
- Pencarian tanggal mendukung bulan parsial yang tidak ambigu seperti `apri`, `janu`, `me`, dan `jul`.
- Jika keyword terbaca sebagai tanggal, query difokuskan ke `report_date` agar lebih ringan.
- Filter tambahan tersedia untuk tanggal, regu, shift, dan urutan terbaru/terlama.
- Filter tanggal, regu, dan shift diposisikan di bawah baris pencarian agar toolbar lebih rapi.
- Native clear button input search disembunyikan agar tidak muncul dua tanda silang.

File utama:

- `resources/views/manajer/archive.blade.php`
- `app/Http/Controllers/ManajerController.php`

---

## 21. Batas Akses Role Manajer

Akun manajer dibatasi hanya untuk halaman manajer.

Perilaku:

- Setelah login, role `manajer` diarahkan ke `manajer.index`.
- Jika manajer membuka route `/report-ops`, sistem mengembalikan ke dashboard manajer dengan toast error.
- Request JSON dari manajer ke route divisi menerima response `403`.
- Manajer tetap dapat melihat laporan melalui route khusus `manajer.reports.show`.

File utama:

- `app/Http/Controllers/LoginV2Controller.php`
- `app/Http/Middleware/PreventManagerDivisionAccess.php`
- `routes/web.php`
- `tests/Feature/OpsFlowTest.php`

Test terkait:

- `test_manager_is_redirected_away_from_operational_pages`
- `test_manager_can_review_reports_from_manager_route_only`

---

## 22. Mobile Responsive Halaman Manajer

Layout manajer diperbaiki agar nyaman digunakan dari HP.

Perubahan:

- Sidebar berubah menjadi off-canvas pada layar mobile.
- Navbar memiliki tombol toggle untuk membuka/menutup sidebar.
- Backdrop muncul saat sidebar terbuka dan dapat diklik untuk menutup.
- Stats card menjadi 2 kolom pada mobile normal, lalu 1 kolom pada layar sangat sempit.
- Tab Laporan Masuk tidak dipaksa mengecil dan dapat digeser horizontal.
- Tabel arsip tetap memakai horizontal scroll pada layar kecil.

File utama:

- `resources/views/manajer/layouts/app.blade.php`
- `resources/views/manajer/layouts/navbar.blade.php`
- `resources/views/manajer/layouts/sidebar.blade.php`

---

## 23. Toast, Loading, dan Pusat Bantuan Manajer

Layout manajer sekarang memakai pola feedback yang sama dengan halaman petugas operasional.

Perubahan:

- Success/error memakai toast message, bukan alert statis.
- Toast sukses/error memakai background liquid glass mengikuti styling box login (`glass-bg`, border 3D, blur, shadow, dan inner shadow).
- Loading awal halaman memakai spinner overlay.
- Modal konfirmasi dan hapus memakai handler custom.
- Pusat Bantuan berisi penjelasan sistem untuk manajer: ringkasan menu, alur laporan, arti status, pencarian/filter arsip, batas akses, dan kendala umum.
- Tombol Logout sidebar manajer memiliki hover, active, dan focus-visible state.

File utama:

- `resources/views/manajer/layouts/app.blade.php`
- `resources/views/manajer/layouts/sidebar.blade.php`
- `resources/views/report-ops/layouts/app.blade.php`
- `resources/views/auth/layouts/app.blade.php`
- `resources/views/manajer/bantuan.blade.php`

---

## 24. Optimasi Rendering Halaman Manajer

Query halaman manajer diringankan agar daftar laporan lebih cepat dirender.

Perubahan:

- Dashboard manajer hanya mengambil kolom yang dibutuhkan untuk list.
- Arsip manajer hanya mengambil kolom list dan relasi ringan `approver:id,name`.
- Relasi lengkap laporan baru di-load saat membuka detail laporan atau saat membuat/download PDF.
- Statistik dashboard dan arsip dicache 60 detik.
- Cache statistik dibersihkan saat laporan di-approve atau arsip dihapus.

File utama:

- `app/Http/Controllers/ManajerController.php`

---

## 25. Penyempurnaan UX Pencarian, Toast, dan Logout

Pembaruan lanjutan berfokus pada perilaku pencarian yang lebih sesuai ekspektasi user dan detail interaksi UI.

Perubahan pencarian:

- Riwayat laporan petugas tidak lagi hanya bergantung pada filter halaman pagination yang sedang terbuka.
- Enter pada search riwayat/arsip mengirim pencarian ke server sehingga laporan di pagination lain tetap muncul.
- Suggestion tidak membuka halaman detail laporan; suggestion dipakai sebagai pilihan keyword tabel.
- Klik suggestion memfilter tabel berdasarkan ID dokumen suggestion.
- Keyword bulan parsial yang jelas (`apri`, `janu`, `me`, `jul`) dikenali sebagai pencarian tanggal.
- Query tanggal di riwayat dan arsip difokuskan ke `report_date` agar pencarian lebih ringan.

Perubahan tampilan:

- Filter tanggal, regu, dan shift pada arsip manajer diletakkan di bawah baris pencarian.
- Native clear button input search arsip disembunyikan agar tidak muncul dua tanda silang.
- Toast semua area mengikuti gaya liquid glass dari box login.
- Button Logout sidebar manajer memiliki hover, active, dan focus-visible state.

File utama:

- `app/Http/Controllers/ReportOpsController.php`
- `app/Http/Controllers/ManajerController.php`
- `resources/views/report-ops/index.blade.php`
- `resources/views/report-ops/layouts/app.blade.php`
- `resources/views/manajer/archive.blade.php`
- `resources/views/manajer/layouts/app.blade.php`
- `resources/views/manajer/layouts/sidebar.blade.php`
- `resources/views/auth/layouts/app.blade.php`
- `tests/Feature/OpsFlowTest.php`

Test terkait:

- `test_history_search_finds_date_from_later_pagination_page`
- `test_report_search_suggestions_accept_clear_partial_month_names`

---

## Catatan Teknis Lanjutan

Beberapa nama internal masih memakai istilah lama untuk menjaga kompatibilitas:

- `turba_activities`
- `turba_deliveries`
- `step5-gudangturba.blade.php`
- `#step-gudang-turba`

Istilah user-facing sudah diarahkan ke `Tracking`, tetapi refactor nama tabel/model bisa dijadwalkan terpisah jika ingin membersihkan domain naming secara menyeluruh.
