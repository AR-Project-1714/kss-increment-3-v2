# Rencana Pengujian Black Box — Sistem Laporan KSS

Dokumen ini berisi daftar skenario/kasus uji **black box testing** untuk Sistem Laporan KSS.
Pengujian black box berfokus pada **fungsionalitas** (masukan → keluaran) tanpa melihat kode program.

## 1. Tujuan & Metode

- **Tujuan:** memastikan setiap fitur berjalan sesuai kebutuhan fungsional pada semua peran pengguna.
- **Metode:** *Equivalence Partitioning* dan *scenario-based testing* — menguji masukan valid maupun tidak valid serta alur kerja utama.
- **Indikator:** tiap kasus uji dinyatakan **Valid** bila hasil aktual sama dengan hasil yang diharapkan, atau **Tidak Valid** bila berbeda.

## 2. Lingkungan Pengujian

| Komponen | Keterangan |
|---|---|
| Perangkat keras | (mis. Laptop, RAM 8 GB) |
| Sistem operasi | (mis. Windows 11) |
| Peramban | (mis. Google Chrome versi …) |
| Server lokal | Laravel (PHP) + database MySQL |
| Resolusi diuji | Desktop & Mobile (responsif) |

## 3. Peran/Akun yang Diuji

| Peran | Contoh Akun | Halaman Utama |
|---|---|---|
| Admin | `admin` | Dashboard Sistem |
| Manajer | `manajer` | Dashboard Manajer |
| Karu/Wakaru Operasi | `karu.a` / `wakaru.a` | Laporan Operasional |
| Kasi Pemeliharaan | `kasi.pemeliharaan` | Laporan Pemeliharaan |
| Karu Safety | `karu.safety` | Laporan K3/Safety |

> Cara mengisi kolom **Kesimpulan**: tandai **Valid** jika sesuai, atau **Tidak Valid** jika tidak sesuai, setelah kasus uji dijalankan.

---

## 4. Kasus Uji

### A. Autentikasi & Login

| ID | Skenario Pengujian | Hasil yang Diharapkan | Kesimpulan |
|---|---|---|---|
| TC-LOGIN-01 | Login dengan username & password **benar** | Berhasil masuk dan diarahkan ke halaman sesuai peran | |
| TC-LOGIN-02 | Login dengan password **salah** | Gagal login; muncul notifikasi "Username/email atau password salah" | |
| TC-LOGIN-03 | Login dengan username **tidak terdaftar** | Gagal login; muncul notifikasi kesalahan | |
| TC-LOGIN-04 | Login dengan field **kosong** (username/password) | Muncul pesan validasi field wajib diisi | |
| TC-LOGIN-05 | Login pada akun berstatus **nonaktif** | Ditolak; muncul pesan "Akun Anda dinonaktifkan. Silakan hubungi admin." | |
| TC-LOGIN-06 | Salah password **berkali-kali** (≥ 5×) | Login diblokir sementara; muncul pesan "Terlalu banyak percobaan login. Coba lagi dalam … detik." | |
| TC-LOGIN-07 | Centang **Ingat Saya** lalu login | Sesi tetap aktif sesuai mekanisme "remember me" | |
| TC-LOGIN-08 | Indikator **Caps Lock** saat mengetik password | Muncul peringatan "Caps Lock aktif" | |
| TC-LOGIN-09 | Klik tombol **Logout** | Sesi berakhir; diarahkan kembali ke halaman login | |
| TC-LOGIN-10 | Akses URL halaman saat **belum login** | Diarahkan (redirect) ke halaman login | |

### B. Kontrol Akses (Hak Akses Antar Peran)

| ID | Skenario Pengujian | Hasil yang Diharapkan | Kesimpulan |
|---|---|---|---|
| TC-RBAC-01 | Akun **Operasional** membuka URL halaman Admin (`/admin`) | Akses ditolak / diarahkan ke halaman miliknya | |
| TC-RBAC-02 | Akun **Manajer** membuka halaman petugas (operasional/pemeliharaan/safety) | Akses ditolak / diarahkan | |
| TC-RBAC-03 | Akun **Pemeliharaan** membuka halaman **Safety**, dan sebaliknya | Akses ditolak / diarahkan | |
| TC-RBAC-04 | Akun **Admin** membuka halaman manajer/petugas | Akses ditolak / diarahkan | |
| TC-RBAC-05 | Login tiap peran → diarahkan ke **halaman awal yang benar** | Admin→Dashboard, Manajer→Dashboard Manajer, dst. sesuai peran | |
| TC-RBAC-06 | Label jabatan di header sesuai akun | Operasional: "Kepala Regu A"/"Wakil Kepala Regu A"; Pemeliharaan: "Kasi Pemeliharaan"; Safety: "Karu Safety" | |

### C. Admin — Dashboard Sistem

| ID | Skenario Pengujian | Hasil yang Diharapkan | Kesimpulan |
|---|---|---|---|
| TC-ADASH-01 | Membuka Dashboard Sistem | Tampil 4 kartu: Total Pengguna Aktif, Kapasitas Storage, Status Backup Terakhir, Kejadian Keamanan Hari Ini | |
| TC-ADASH-02 | Memverifikasi nilai kartu sesuai data | Nilai kartu sesuai kondisi sistem (mis. jumlah user aktif benar) | |
| TC-ADASH-03 | Melihat daftar **aktivitas terbaru** | Menampilkan ringkasan aktivitas terakhir dari Log Aktivitas | |

### D. Admin — Arsip Laporan

| ID | Skenario Pengujian | Hasil yang Diharapkan | Kesimpulan |
|---|---|---|---|
| TC-AARS-01 | Membuka Arsip Laporan | Menampilkan daftar laporan dari ketiga divisi | |
| TC-AARS-02 | Mencari laporan dengan **kata kunci** (ID/tanggal/regu/kapal/karyawan) | Daftar tersaring sesuai kata kunci; saran pencarian muncul | |
| TC-AARS-03 | Mencari kata kunci yang **tidak ada** | Menampilkan status "laporan tidak ditemukan" | |
| TC-AARS-04 | Memfilter berdasarkan **Tanggal, Divisi, Regu, Shift, Status** | Daftar tersaring sesuai filter | |
| TC-AARS-05 | Mengubah urutan **Terbaru / Terlama** | Urutan daftar berubah sesuai tanggal | |
| TC-AARS-06 | Menekan tombol **Reset** filter | Filter kembali ke kondisi awal | |
| TC-AARS-07 | Menekan tombol **Lihat** laporan | Membuka pratinjau isi laporan | |
| TC-AARS-08 | Menekan tombol **Unduh** | Berkas PDF laporan terunduh | |
| TC-AARS-09 | Menekan tombol **Hapus** laporan | Laporan terhapus permanen; muncul notifikasi sukses; tercatat di Log Aktivitas | |
| TC-AARS-10 | Pindah **halaman (pagination)** arsip | Daftar berpindah halaman dengan benar | |

### E. Admin — Log Aktivitas

| ID | Skenario Pengujian | Hasil yang Diharapkan | Kesimpulan |
|---|---|---|---|
| TC-ALOG-01 | Membuka Log Aktivitas | Menampilkan daftar aktivitas (maks. 60 entri terbaru) | |
| TC-ALOG-02 | Mencari log (deskripsi/IP/nama/username) | Daftar tersaring sesuai kata kunci | |
| TC-ALOG-03 | Memfilter log berdasarkan **Tanggal, Role, Jenis** | Daftar tersaring sesuai filter | |
| TC-ALOG-04 | Melakukan tindakan (mis. hapus data) lalu cek log | Aktivitas baru tercatat otomatis di log | |
| TC-ALOG-05 | Login gagal lalu cek log | Tercatat kejadian keamanan (security) | |

### F. Admin — Kelola Pengguna

| ID | Skenario Pengujian | Hasil yang Diharapkan | Kesimpulan |
|---|---|---|---|
| TC-AUSR-01 | Menambah pengguna dengan data **lengkap & valid** | Pengguna tersimpan; muncul notifikasi sukses | |
| TC-AUSR-02 | Menambah pengguna dengan **username yang sudah ada** | Ditolak; muncul pesan username harus unik | |
| TC-AUSR-03 | Menambah pengguna dengan **password < 6 karakter** | Ditolak; muncul pesan validasi minimal 6 karakter | |
| TC-AUSR-04 | Menambah pengguna **tanpa email** | Email dibuat otomatis; data tetap tersimpan | |
| TC-AUSR-05 | Mengunggah tanda tangan **format PNG ≤ 2 MB** | Tanda tangan tersimpan | |
| TC-AUSR-06 | Mengunggah tanda tangan **bukan PNG / > 2 MB** | Ditolak; muncul pesan validasi format/ukuran | |
| TC-AUSR-07 | Mengedit pengguna dengan field password **dikosongkan** | Data tersimpan; password lama **tidak** berubah | |
| TC-AUSR-08 | Mengubah **status** pengguna (toggle Aktif/Nonaktif) | Status berubah; akun nonaktif tidak dapat login | |
| TC-AUSR-09 | Menghapus pengguna lain | Pengguna terhapus; muncul notifikasi sukses | |
| TC-AUSR-10 | Mencoba **menonaktifkan/menghapus akun admin sendiri** | Ditolak; muncul pesan akun yang sedang dipakai tidak bisa diubah | |
| TC-AUSR-11 | Mencari pengguna pada tabel | Daftar tersaring sesuai kata kunci | |

### G. Admin — Data Master

| ID | Skenario Pengujian | Hasil yang Diharapkan | Kesimpulan |
|---|---|---|---|
| TC-AMST-01 | Berpindah antar tab (Karyawan, Unit, Truck, Inventaris, Lokasi K3, Item K3) | Tab & data yang sesuai ditampilkan | |
| TC-AMST-02 | Menambah data **Karyawan** valid | Data tersimpan; muncul notifikasi sukses | |
| TC-AMST-03 | Menambah data dengan field **wajib kosong** | Ditolak; muncul pesan validasi | |
| TC-AMST-04 | Menambah data **Unit** (Tipe + Nomor unit) | Nama unit terbentuk otomatis dari Tipe + Nomor unit | |
| TC-AMST-05 | Mengedit data master | Perubahan tersimpan | |
| TC-AMST-06 | Menghapus data master | Data terhapus; laporan lama yang sudah dibuat **tetap utuh** | |
| TC-AMST-07 | Mencari & memfilter (mis. Regu/Divisi/Jabatan, Tipe/Kategori) | Daftar tersaring sesuai pencarian/filter | |
| TC-AMST-08 | Menambah/menonaktifkan **Lokasi K3 / Item K3** | Status aktif/nonaktif memengaruhi pilihan pada form Safety | |

### H. Admin — Manajemen Backup

| ID | Skenario Pengujian | Hasil yang Diharapkan | Kesimpulan |
|---|---|---|---|
| TC-ABCK-01 | Membuat **Backup Manual** | Berkas cadangan dibuat; muncul notifikasi sukses | |
| TC-ABCK-02 | Mengatur **Jadwal Backup** (Frekuensi, Jam, Retensi, Target) | Pengaturan tersimpan | |
| TC-ABCK-03 | Mengunduh berkas backup | Berkas terunduh | |
| TC-ABCK-04 | Menghapus berkas backup | Berkas terhapus; muncul notifikasi sukses | |
| TC-ABCK-05 | Menekan **Restore** | Tidak dijalankan otomatis; permintaan dicatat ke log; muncul pesan restore dilakukan manual oleh admin server | |
| TC-ABCK-06 | **Backup Tahunan** saat tidak ada laporan tahun sebelumnya | Tidak tersedia / muncul pesan fitur belum dapat dijalankan | |
| TC-ABCK-07 | **Backup Tahunan** saat ada laporan tahun lalu | Laporan tahun lalu diarsipkan ke ZIP lalu dihapus dari sistem; file ZIP dapat diunduh; muncul notifikasi sukses | |
| TC-ABCK-08 | Memantau **kapasitas storage** | Persentase pemakaian storage ditampilkan | |

### I. Manajer — Dashboard & Tanda Tangan

| ID | Skenario Pengujian | Hasil yang Diharapkan | Kesimpulan |
|---|---|---|---|
| TC-MGR-01 | Membuka Dashboard Manajer | Menampilkan kartu ringkasan & daftar laporan masuk dari 3 divisi | |
| TC-MGR-02 | Berpindah **tab divisi** (Semua/Operasional/Pemeliharaan/Safety) | Daftar tersaring per divisi; angka jumlah pada tab sesuai | |
| TC-MGR-03 | Menekan **Lihat** laporan masuk | Membuka pratinjau laporan | |
| TC-MGR-04 | **Menandatangani** laporan masuk | Laporan disetujui; status menjadi "Diarsipkan"; pindah ke Arsip Laporan | |
| TC-MGR-05 | Laporan Operasional yang **belum diterima regu tujuan** | Tidak muncul di dashboard manajer | |
| TC-MGR-06 | Laporan Pemeliharaan/Safety yang **sudah diserahkan** | Langsung muncul di dashboard manajer untuk ditandatangani | |
| TC-MGR-07 | Menandatangani saat **tanda tangan akun kosong** | Sistem menampilkan pesan/penanganan yang sesuai (hubungi admin) | |

### J. Manajer — Arsip Laporan

| ID | Skenario Pengujian | Hasil yang Diharapkan | Kesimpulan |
|---|---|---|---|
| TC-MARS-01 | Membuka Arsip Laporan manajer | Menampilkan laporan yang sudah diarsipkan | |
| TC-MARS-02 | Cari / filter / urutkan arsip | Daftar tersaring & terurut sesuai masukan | |
| TC-MARS-03 | **Unduh** PDF arsip | Berkas terunduh | |
| TC-MARS-04 | **Hapus** arsip | Arsip terhapus; muncul notifikasi sukses | |

### K. Operasional — Laporan Operasi Harian

| ID | Skenario Pengujian | Hasil yang Diharapkan | Kesimpulan |
|---|---|---|---|
| TC-OPS-01 | Membuka halaman, melihat tab **Laporan Masuk / Draft / Riwayat** | Ketiga tab tampil dan dapat dibuka | |
| TC-OPS-02 | Membuat laporan baru — mengisi **Step 1 Info Umum** | Dapat lanjut ke langkah berikutnya bila valid | |
| TC-OPS-03 | Menekan **Lanjut** dengan field wajib kosong | Ditolak; muncul notifikasi data belum lengkap | |
| TC-OPS-04 | Memilih **regu tujuan = regu sendiri** | Muncul peringatan "Group tidak valid" | |
| TC-OPS-05 | Mengisi semua langkah (Info Umum, Muat Kantong, Muat Curah, Bongkar, Gudang & Turba, Cek Unit) | Seluruh langkah dapat diisi & navigasi antar langkah berfungsi | |
| TC-OPS-06 | **Simpan sebagai Draft** | Laporan tersimpan berstatus Draft; muncul di tab Draft | |
| TC-OPS-07 | **Serahkan** laporan ke regu tujuan | Laporan terkirim berstatus "Diserahkan" | |
| TC-OPS-08 | **Lanjutkan Draft** | Draft terbuka kembali dengan data terakhir tersimpan | |
| TC-OPS-09 | **Hapus Draft** | Draft terhapus dan tidak muncul lagi | |
| TC-OPS-10 | Tab **Laporan Masuk** → meninjau laporan dari regu lain | Laporan dari regu pengirim ditampilkan | |
| TC-OPS-11 | **Menandatangani** laporan masuk | Status menjadi "Diterima"; diteruskan ke manajer | |
| TC-OPS-12 | Tab **Riwayat** → mengunduh **PDF** | Berkas PDF terunduh | |
| TC-OPS-13 | Tab **Riwayat** → mengunduh **Excel** | Berkas Excel terunduh | |
| TC-OPS-14 | Menutup tab/keluar saat mengisi laporan (sesi terputus) | Laporan tersimpan otomatis sebagai Draft | |

### L. Pemeliharaan — Laporan Pemeliharaan

| ID | Skenario Pengujian | Hasil yang Diharapkan | Kesimpulan |
|---|---|---|---|
| TC-PML-01 | Membuat laporan — mengisi 5 langkah (Info Umum, Pekerjaan Utama, Pekerjaan Prioritas, Kondisi Unit, Daftar Hadir) | Seluruh langkah dapat diisi & navigasi berfungsi | |
| TC-PML-02 | Menekan **Lanjut** dengan data wajib kosong | Ditolak; muncul notifikasi validasi | |
| TC-PML-03 | **Simpan sebagai Draft** | Tersimpan berstatus Draft | |
| TC-PML-04 | **Serahkan** laporan | Berstatus "Diserahkan"; langsung muncul di dashboard manajer | |
| TC-PML-05 | **Lanjutkan / Hapus Draft** | Draft dapat dilanjutkan atau dihapus | |
| TC-PML-06 | Mengunduh **PDF** laporan | Berkas PDF terunduh | |
| TC-PML-07 | Sesi terputus saat mengisi | Tersimpan otomatis sebagai Draft | |

### M. Safety (K3) — Laporan K3

| ID | Skenario Pengujian | Hasil yang Diharapkan | Kesimpulan |
|---|---|---|---|
| TC-SFT-01 | Membuat laporan — mengisi 4 langkah (Info Umum, Inspeksi K3, Kegiatan, Kejadian) | Seluruh langkah dapat diisi & navigasi berfungsi | |
| TC-SFT-02 | **Inspeksi K3**: mengisi QTY, kondisi, rekomendasi per item/lokasi | Data inspeksi tersimpan per lokasi | |
| TC-SFT-03 | Menggunakan tombol **Set semua "Bagus"** | Seluruh item terisi kondisi Bagus; muncul notifikasi | |
| TC-SFT-04 | Menekan **Lanjut** dengan data wajib kosong | Ditolak; muncul notifikasi validasi | |
| TC-SFT-05 | **Simpan sebagai Draft** | Tersimpan berstatus Draft | |
| TC-SFT-06 | **Serahkan** laporan | Berstatus "Diserahkan"; langsung muncul di dashboard manajer | |
| TC-SFT-07 | Mengunduh **PDF** laporan | Berkas PDF terunduh | |
| TC-SFT-08 | Item/lokasi inspeksi mengikuti **Data Master K3** | Hanya item/lokasi yang aktif yang tampil di form | |

### N. Fungsi Umum & Antarmuka

| ID | Skenario Pengujian | Hasil yang Diharapkan | Kesimpulan |
|---|---|---|---|
| TC-UI-01 | Melakukan aksi berhasil/gagal (mis. simpan, hapus, login gagal) | Muncul **notifikasi (toast)** yang seragam di semua halaman | |
| TC-UI-02 | Notifikasi toast setelah beberapa detik | Toast hilang otomatis / dapat ditutup manual | |
| TC-UI-03 | Mengaktifkan **Mode Gelap (dark mode)** | Tampilan beralih ke gelap dan tersimpan saat pindah halaman/refresh | |
| TC-UI-04 | Membuka aplikasi di **layar HP (responsif)** | Sidebar menjadi menu geser; tabel/tab dapat digeser horizontal | |
| TC-UI-05 | **Pusat Bantuan** (Admin/Manajer): pencarian topik | Topik tersaring sesuai kata kunci; empty-state bila tidak ada | |
| TC-UI-06 | **Pusat Bantuan**: navigasi **tab** | Tab aktif berpindah & konten ter-scroll ke bagian terkait | |
| TC-UI-07 | Membuka **viewer PDF** laporan | Pratinjau PDF tampil; unduhan berfungsi | |
| TC-UI-08 | Membuka laporan pertama kali vs berikutnya | PDF dibuat saat pertama; berikutnya memakai berkas tersimpan (lebih cepat) | |

---

## 5. Rekapitulasi Hasil Pengujian

| Modul | Jumlah Kasus Uji | Valid | Tidak Valid |
|---|---|---|---|
| A. Autentikasi & Login | 10 | | |
| B. Kontrol Akses | 6 | | |
| C. Admin — Dashboard | 3 | | |
| D. Admin — Arsip Laporan | 10 | | |
| E. Admin — Log Aktivitas | 5 | | |
| F. Admin — Kelola Pengguna | 11 | | |
| G. Admin — Data Master | 8 | | |
| H. Admin — Manajemen Backup | 8 | | |
| I. Manajer — Dashboard & TTD | 7 | | |
| J. Manajer — Arsip | 4 | | |
| K. Operasional | 14 | | |
| L. Pemeliharaan | 7 | | |
| M. Safety (K3) | 8 | | |
| N. Fungsi Umum & Antarmuka | 8 | | |
| **Total** | **109** | | |

**Persentase keberhasilan** = (Jumlah Valid ÷ Total Kasus Uji) × 100% = …… %

## 6. Kesimpulan Pengujian

> *(Diisi setelah pengujian.)* Berdasarkan hasil pengujian black box terhadap … kasus uji, sebanyak … kasus dinyatakan **Valid** dan … kasus **Tidak Valid**, sehingga persentase keberhasilan sistem sebesar …%. Hal ini menunjukkan bahwa secara fungsional sistem ……

---

*Dokumen rencana pengujian black box untuk Sistem Laporan KSS.*
