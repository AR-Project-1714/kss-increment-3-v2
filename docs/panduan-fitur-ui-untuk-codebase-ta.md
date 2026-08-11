# Panduan Penerapan Fitur UI ke Codebase Tugas Akhir

Dokumen ini merangkum fitur UI/UX yang ada pada codebase PT KSS dan dapat diterapkan ke codebase tugas akhir (TA). Panduan ini berfokus pada pola antarmuka, perilaku responsive, dan alur data yang reusable.

## Batasan penerapan untuk project TA

Codebase PT KSS memiliki dua menu khusus yang tidak ada pada project TA:

- **Kinerja Operasi**
- **Rincian Kegiatan**

Kedua menu tersebut, route-nya, kartu metrik operasionalnya, dan tautan menuju keduanya tidak perlu dipindahkan. Yang dipindahkan adalah pola desain yang umum, terutama layout kartu statistik, toolbar, tab, pencarian, tabel, dan responsive behavior.

Tampilan admin pada project TA boleh dibuat lebih sederhana. Gunakan struktur visual yang sama secara proporsional, tetapi pertahankan navigasi dan warna yang lebih biasa bila itu lebih sesuai dengan kebutuhan TA.

## Peta file sumber pada codebase PT KSS

| Fitur | File utama | File pendukung |
|---|---|---|
| Arsip laporan admin | `resources/views/admin/archive.blade.php` | `app/Http/Controllers/AdminV2Controller.php`, `app/Http/Controllers/Concerns/BuildsDivisionArchive.php` |
| Arsip laporan manajer | `resources/views/manajer/archive.blade.php` | `app/Http/Controllers/ManajerController.php` |
| Pusat Bantuan admin | `resources/views/admin/help.blade.php` | `routes/web.php`, `resources/css/layouts/admin.css` |
| Pusat Bantuan manajer | `resources/views/manajer/bantuan.blade.php` | `routes/web.php`, `resources/css/layouts/manajer.css` |
| Data Master | `resources/views/admin/datamaster.blade.php` | `AdminV2Controller::dataMaster()`, migration/model master employee |
| Layout dashboard admin | `resources/views/admin/index.blade.php` | `resources/views/charts/kpi-row.blade.php`, `resources/css/components/charts.css` |
| Layout dashboard manajer | `resources/views/manajer/index.blade.php` | `resources/views/manajer/partials/operational-summary.blade.php` |
| Layout shell responsive | `resources/views/admin/layouts/*`, `resources/views/manajer/layouts/*` | `resources/css/layouts/admin.css`, `resources/css/layouts/manajer.css` |

---

## 1. Menu Arsip Laporan

### Tujuan

Menu Arsip Laporan menampilkan daftar laporan yang sudah masuk ke sistem. Pengguna dapat mencari, memfilter, mengurutkan, melihat detail, mengunduh, menghapus sesuai hak akses, dan bila diperlukan mengunduh beberapa laporan sekaligus.

Pada codebase PT KSS, admin dapat melihat laporan lintas divisi. Manajer menggunakan pola halaman yang sama dengan hak akses manajerial.

### Struktur tampilan

Bagian halaman disusun seperti berikut:

```text
Header halaman
├── Kiri: judul "Arsip Laporan" dan deskripsi singkat
└── Kanan atas: tombol "Ekspor Excel" dan tombol "Filter"

Kartu KPI/statistik arsip

Card "Riwayat Laporan"
├── Toolbar pencarian
├── Jumlah hasil + jumlah baris per halaman + urutan
├── Toolbar unduh massal (opsional)
├── Tabel laporan
└── Pagination
```

### Tombol Filter di ujung kanan atas

Tombol filter berada di dalam header halaman, bukan di bawah tabel. Class utama yang digunakan:

- `.performance-page-header`
- `.performance-filter`
- `.performance-filter--with-export`
- `.performance-filter__trigger`
- `.performance-filter__popover`

Perilakunya:

1. Tombol filter memakai `type="button"` dan `aria-expanded="false"`.
2. Saat ditekan, popover dibuka dengan mengubah atribut `hidden` menjadi false dan `aria-expanded` menjadi `true`.
3. Popover memiliki judul, deskripsi, tombol tutup, form filter, tombol reset, dan tombol terapkan.
4. Popover ditutup saat tombol tutup ditekan, pengguna menekan `Escape`, atau pengguna mengklik di luar popover.
5. Jika ada filter aktif, tombol diberi class status aktif dan label kecil **Aktif**.
6. Pada mobile, popover berubah menjadi panel yang memenuhi lebar hampir seluruh layar dan memiliki tinggi maksimum yang dapat di-scroll.

Contoh struktur minimal:

```html
<div class="page-header performance-page-header">
  <div class="performance-page-header__heading">
    <span class="page-title">Arsip Laporan</span>
    <span class="page-subtitle">Daftar laporan yang tersimpan.</span>
  </div>

  <div class="performance-filter" data-archive-filter>
    <button type="button"
            class="btn-tool btn-tool--primary performance-filter__trigger"
            data-archive-filter-trigger
            aria-expanded="false"
            aria-controls="archive-filter-popover">
      Filter
    </button>

    <div id="archive-filter-popover"
         class="performance-filter__popover"
         data-archive-filter-popover
         hidden>
      <!-- field filter + aksi -->
    </div>
  </div>
</div>
```

### Field filter

Codebase sumber menyediakan field berikut:

| Label | Parameter query | Nilai umum |
|---|---|---|
| Tanggal | `tanggal` | tanggal tertentu |
| Divisi | `divisi` | `all`, atau divisi yang tersedia di TA |
| Regu | `regu` | `all`, A, B, C, D, atau pilihan TA |
| Shift | `shift` | `all`, pagi, sore, malam |
| Status | `status` | `all`, submitted, acknowledged, approved |

Project TA tidak harus menggunakan semua field. Hanya tampilkan filter yang memang ada pada domain TA. Jangan menyimpan pilihan filter khusus PT KSS jika project TA tidak menggunakan regu atau shift.

### Toolbar pencarian dan tabel

Pencarian berada di dalam card riwayat laporan, tepat di atas tabel. Search bar memiliki:

- ikon pencarian;
- input dengan `type="search"`;
- tombol clear;
- optional dropdown saran pencarian;
- atribut ARIA `role="combobox"`, `aria-expanded`, dan `aria-controls`.

Placeholder sumber mencakup ID, divisi, tanggal, shift, regu, kapal, karyawan, dan isi laporan. Untuk TA, ubah placeholder sesuai field yang benar-benar dapat dicari.

Kolom tabel sumber:

1. No dan checkbox pilih semua.
2. Info Dokumen: nama laporan dan ID.
3. Tanggal Laporan.
4. Divisi.
5. Regu.
6. Shift.
7. Status.
8. Aksi: Download, Lihat, Hapus.

Tabel menggunakan `.table-responsive-wrapper` dengan `overflow-x: auto` dan `min-width` agar kolom tidak bertumpuk. Pada mobile tabel tetap dapat digeser horizontal; jangan mengecilkan font sampai teks tidak terbaca.

### Status dan badge

Gunakan badge yang mudah dipindai:

- badge divisi dengan warna berbeda;
- badge shift dengan ikon;
- badge status berbentuk pill dengan titik status;
- tombol aksi konsisten: biru untuk download, oranye untuk lihat, merah untuk hapus.

Jika warna PT KSS tidak cocok dengan TA, pertahankan makna warnanya dan ganti token warna pada design system TA.

### Alur backend yang disarankan

Gunakan satu fungsi parser filter agar halaman, ekspor, dan pencarian tidak memiliki definisi filter yang berbeda.

```php
protected function archiveFiltersFromRequest(Request $request): array
{
    return [
        'search' => trim((string) $request->input('q', '')),
        'sort' => $request->input('sort') === 'oldest' ? 'oldest' : 'newest',
        'perPage' => in_array((int) $request->input('per_page', 10), [10, 20, 50], true)
            ? (int) $request->input('per_page', 10)
            : 10,
        'date' => $request->input('tanggal'),
        'division' => strtolower((string) $request->input('divisi', 'all')),
        'status' => strtolower((string) $request->input('status', 'all')),
    ];
}
```

Gunakan query builder/Eloquent dengan kondisi `when()` sehingga filter kosong tidak membatasi hasil. Hasil harus dipaginasi di server. Simpan semua parameter filter melalui `appends()` saat membuat pagination.

### Ekspor dan unduh massal

Fitur yang dapat dipilih sesuai kebutuhan TA:

- **Ekspor Excel:** mengekspor seluruh hasil yang cocok dengan filter aktif, bukan hanya halaman yang sedang terlihat.
- **Unduh satu laporan:** mengunduh PDF/detail laporan.
- **Unduh massal:** checkbox pada tabel membentuk ZIP.
- **Unduh latar belakang:** untuk jumlah besar, proses ZIP dibuat melalui queue dan frontend memantau token/status.

Untuk project TA dengan data kecil, ekspor Excel dan unduh satu laporan sudah cukup. Unduh massal berbasis queue hanya perlu diterapkan bila memang menjadi kebutuhan fungsional.

---

## 2. Tab Pusat Bantuan menjadi bottom navigation di mobile

Fitur ini tersedia pada:

- `resources/views/admin/help.blade.php`
- `resources/views/manajer/bantuan.blade.php`

### Struktur tab desktop

Setiap halaman bantuan memiliki daftar section dengan `id` dan tombol tab dengan `data-tab` yang sama.

```html
<div class="help-tabs" id="helpTabs">
  <button type="button" class="help-tab" data-tab="ringkasan">
    <span class="icon-tab"><i class="fi fi-rr-dashboard"></i></span>
    <span>Ringkasan</span>
  </button>
  <span class="help-tab-indicator" id="helpTabIndicator"></span>
</div>

<section class="help-section" id="ringkasan">
  ...
</section>
```

Pada desktop:

- tab berbentuk bar horizontal;
- tab dapat digeser horizontal bila label banyak;
- tab `active` memiliki indikator biru;
- tab dibuat `sticky` agar tetap terlihat saat membaca section panjang;
- isi tab menggunakan section dengan `scroll-margin-top`.

### Navigasi berbasis section

JS melakukan tiga hal:

1. Klik tab melakukan scroll ke section target.
2. Scroll halaman membaca section yang sedang terlihat lalu memberi class `.active` pada tab yang sesuai.
3. `#helpTabIndicator` dipindahkan mengikuti `offsetLeft` dan `offsetWidth` tab aktif.

Pencarian tidak perlu membuat halaman baru. Setiap konten yang dapat dicari diberi atribut `data-help-item`; JS menyembunyikan item yang tidak cocok dan menampilkan empty state jika tidak ada hasil.

### Perubahan pada mobile

Breakpoint yang digunakan adalah `max-width: 767px`. Pada ukuran ini `.help-tabs`:

- berubah menjadi `position: fixed`;
- dipindahkan ke bawah layar dengan `bottom: calc(14px + env(safe-area-inset-bottom))`;
- berada di tengah menggunakan `left: 50%` dan `transform: translateX(-50%)`;
- menjadi bentuk pill/island dengan `border-radius: 999px`;
- hanya menampilkan ikon, label teks disembunyikan;
- setiap tombol memiliki ukuran sentuh minimal sekitar `46px`;
- tetap dapat digeser horizontal jika jumlah tab lebih banyak daripada lebar layar;
- mempertahankan indicator aktif dalam bentuk pill.

Konten halaman harus diberi ruang bawah agar tidak tertutup navigasi:

```css
@media (max-width: 767px) {
  .page-content {
    padding-bottom: calc(76px + env(safe-area-inset-bottom));
  }

  .help-tabs {
    position: fixed;
    left: 50%;
    bottom: calc(14px + env(safe-area-inset-bottom));
    transform: translateX(-50%);
    width: max-content;
    max-width: calc(100vw - 28px);
    border-radius: 999px;
    z-index: 850;
  }

  .help-tab {
    min-width: 46px;
    min-height: 46px;
    padding: 8px 12px;
    border-radius: 999px;
  }

  .help-tab span:not(.icon-tab) {
    display: none;
  }
}
```

### Tab admin dan manajer

Tab pada bantuan admin berisi topik seperti dashboard, arsip, log, pengguna, data master, backup, status, akses, dan FAQ.

Tab pada bantuan manajer berisi ringkasan, alur laporan, tanda tangan, status, arsip, dan kendala. Topik analitik/kinerja operasional pada codebase sumber harus dihapus atau diganti dengan topik yang ada di TA.

Struktur bottom navigation boleh dipakai pada kedua role, tetapi isi tab harus berbeda sesuai hak akses dan menu yang tersedia.

---

## 3. Posisi search bar pada Pusat Bantuan

Search bar bantuan ditempatkan di ujung kanan header halaman, sejajar dengan judul/deskripsi pada desktop.

Class yang digunakan:

- `.help-page-header`: grid dua kolom;
- `.help-page-header__heading`: kolom kiri;
- `.help-toolbar`: kolom kanan;
- `.help-searchbox`: input berbentuk pill;
- `.help-search__clear`: tombol clear di sisi kanan input.

Pola layout desktop:

```css
.help-page-header {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(300px, 420px);
  gap: 24px;
  align-items: start;
}
```

Pada `max-width: 900px`, layout berubah menjadi satu kolom. Search bar turun ke bawah judul dan memakai lebar penuh. Pada mobile, input tetap berada di atas konten dan tidak ikut menjadi bottom navigation; hanya tab section yang dipindahkan ke bawah.

Perilaku pencarian:

- input menggunakan `autocomplete="off"`;
- pencarian berjalan di sisi frontend pada item bantuan yang sudah dirender;
- kata kunci dibandingkan dengan judul dan teks item;
- tombol clear dikendalikan oleh class `.is-hidden`;
- jika tidak ada hasil, tampilkan empty state `Topik tidak ditemukan`;
- hasil pencarian tidak boleh menyembunyikan tab secara permanen; pengguna tetap dapat menghapus query.

---

## 4. Perbaikan layout card/stat dashboard

### Komponen KPI reusable

Codebase memiliki komponen reusable `resources/views/charts/kpi-row.blade.php` dengan class utama:

- `.kpi-row`
- `.kpi-card`
- `.kpi-card__head`
- `.kpi-card__icon`
- `.kpi-card__label`
- `.kpi-card__row`
- `.kpi-card__value`
- `.kpi-card__delta`
- `.kpi-card__note`
- `.kpi-card__spark`

Anatomi satu kartu:

```text
Ikon + label metrik
Angka utama + unit + badge perubahan
Catatan pembanding
Sparkline opsional
```

Kartu sebaiknya menerima data dari controller dalam bentuk array, bukan menghitung nilai bisnis di Blade.

```php
$stats = [
    [
        'key' => 'total_reports',
        'label' => 'Total Laporan',
        'value' => 128,
        'unit' => 'laporan',
        'icon' => 'fi fi-rr-document',
        'tint' => 'blue',
        'delta' => [
            'available' => true,
            'text' => '+12%',
            'direction' => 'up',
            'tone' => 'up',
        ],
        'note' => 'dibanding periode sebelumnya',
    ],
];
```

### Responsive grid

Aturan dari `resources/css/components/charts.css`:

| Lebar layar | Layout |
|---|---|
| Di atas 1100px | 4 kartu dalam satu baris |
| 561–1100px | 2 kartu per baris |
| 431–560px | 2 kartu dengan padding dan angka lebih kecil |
| 341–430px | 2 kartu dengan ukuran lebih padat |
| 340px ke bawah | 1 kartu per baris |

Prinsip yang perlu dipertahankan:

- semua kartu memiliki tinggi minimum yang seimbang;
- angka memakai `font-variant-numeric: tabular-nums`;
- label boleh membungkus, tetapi angka tidak boleh terpotong;
- unit dibuat lebih kecil dan berwarna muted;
- badge delta tidak ditampilkan bila data pembanding tidak tersedia;
- gunakan `min-width: 0` pada grid/card agar tidak menyebabkan overflow.

### Panel dashboard

Dashboard admin menggunakan dua panel utama, yaitu aktivitas/audit dan aksi cepat. Pada desktop panel memakai grid tidak sama besar (`3fr 2fr`), lalu pada `max-width: 900px` berubah menjadi satu kolom. Pola ini dapat dipakai untuk dashboard TA dengan panel seperti:

- aktivitas terbaru;
- laporan terbaru;
- aksi cepat;
- notifikasi atau status sistem.

Jangan memindahkan isi khusus PT KSS seperti saldo cloud, billing, operasi kapal, tonase, atau metrik lembur bila tidak ada pada TA.

### Versi admin yang lebih biasa

Untuk admin TA yang lebih sederhana, gunakan:

1. Judul halaman dan deskripsi singkat.
2. Tiga atau empat kartu statistik biasa.
3. Satu card tabel aktivitas/laporan terbaru.
4. Satu card aksi cepat.

Visual KPI tetap dapat dipakai, tetapi hilangkan sparkline/delta jika data pembanding tidak tersedia. Konsistensi informasi lebih penting daripada menampilkan semua ornamen dashboard PT KSS.

---

## 5. Data Master Karyawan

### Tujuan

Data Master Karyawan menjadi sumber data referensi untuk form laporan. Admin dapat mencari, memfilter, menambah, mengubah, dan menghapus data karyawan.

### Struktur halaman

```text
Header
├── Judul "Master Data"
└── Breadcrumb: Data Master / Data Karyawan

Card Data Karyawan
├── Search bar di kiri
├── Tombol Filter
├── Tombol Reset bila filter aktif
├── Tombol Tambah Karyawan
├── Panel filter yang dapat dibuka/tutup
├── Tabel responsive
└── Pagination
```

Pada sumber, Data Master memiliki beberapa pane (`karyawan`, `unit`, `truck`, `inventaris`, dan lainnya). Project TA dapat memindahkan hanya pane `karyawan` jika data master lain belum diperlukan.

### Search dan filter

Search bar mengirim parameter `q` dan pada pane karyawan mencari ke:

- `npk`;
- `name`;
- `group_name`;
- `position`;
- `division`;
- `work_time`.

Filter tambahan pada sumber:

- Divisi: `f_division`;
- Group/regu: `f_group`;
- Jabatan: `f_position`.

Filter ditampilkan hanya ketika tombol Filter ditekan. Panel menggunakan pola yang sama dengan filter Arsip Laporan: animasi turun, tombol reset, dan form GET.

Jika project TA tidak memiliki divisi, group, atau jabatan, jangan tampilkan filter kosong. Minimal pertahankan search bar dan pagination.

### Kolom tabel karyawan

Kolom sumber:

| Kolom | Keterangan |
|---|---|
| No | nomor urut mengikuti pagination |
| NPK | nomor induk/identitas karyawan |
| Nama | nama lengkap, ellipsis bila terlalu panjang |
| Group | regu atau kelompok kerja |
| Posisi | jabatan dengan badge ikon |
| Divisi | badge warna sesuai divisi |
| Jam Kerja | Shift, Non Shift, atau Relief |
| Penugasan Sementara | group sementara jika ada |
| Aksi | Edit dan Hapus |

Untuk TA, kolom dapat disederhanakan menjadi No, ID/NIP, Nama, Jabatan, Status, dan Aksi. Yang penting adalah hierarki data tetap mudah dipindai.

### Detail visual tabel

Class penting:

- `.employee-table` dengan `min-width: 1260px` pada sumber;
- `.employee-name` untuk ellipsis;
- `.employee-npk` untuk tampilan identitas monospace;
- `.division-badge` untuk divisi;
- `.position-badge` untuk jabatan;
- `.employee-muted-value` untuk nilai kosong;
- `.table-responsive-wrapper` untuk scroll horizontal;
- `.btn-act.edit` dan `.btn-act.delete` untuk aksi.

Nilai kosong harus memiliki teks yang jelas seperti `Belum diisi`, `Tidak ada`, atau `-`, bukan ruang kosong tanpa penjelasan.

### Backend dan pagination

Controller sumber memvalidasi pane, mengambil query berdasarkan search/filter, mengurutkan karyawan berdasarkan nama, lalu melakukan `paginate(10)`. Parameter query dipertahankan saat berpindah halaman.

Pola minimal:

```php
$employees = Employee::query()
    ->when($search !== '', function (Builder $query) use ($search) {
        $like = '%'.$search.'%';
        $query->where(function (Builder $q) use ($like) {
            $q->where('employee_id', 'like', $like)
              ->orWhere('name', 'like', $like)
              ->orWhere('position', 'like', $like);
        });
    })
    ->when($division !== '', fn (Builder $q) => $q->where('division', $division))
    ->orderBy('name')
    ->paginate(10)
    ->appends(request()->query());
```

CRUD tetap harus memakai route terpisah dan method HTTP yang benar:

- `POST /master/employees` untuk tambah;
- `PUT/PATCH /master/employees/{employee}` untuk ubah;
- `DELETE /master/employees/{employee}` untuk hapus.

Gunakan CSRF, validasi server, konfirmasi sebelum hapus, dan cek apakah karyawan masih dipakai oleh laporan lain.

---

## 6. Rekomendasi urutan implementasi pada project TA

1. Pastikan layout dasar admin/manajer memiliki `.page-content`, `.page-header`, `.section-card`, `.btn-tool`, dan `.table-responsive-wrapper`.
2. Terapkan komponen kartu statistik reusable terlebih dahulu.
3. Terapkan halaman Arsip Laporan dan parser filter GET tunggal.
4. Tambahkan posisi tombol Filter di ujung kanan header.
5. Tambahkan search bar arsip, pagination, dan empty state.
6. Terapkan halaman Data Master Karyawan dengan search/filter server-side.
7. Terapkan Pusat Bantuan dengan section, tab, scroll-spy, dan pencarian frontend.
8. Tambahkan CSS mobile bottom navigation pada tab Pusat Bantuan.
9. Uji semua halaman pada desktop, tablet, lebar 430px, 360px, dan 320px.
10. Hapus route, menu, link, dan teks yang berkaitan dengan Kinerja Operasi serta Rincian Kegiatan.

## 7. Checklist penerimaan fitur

### Arsip Laporan

- [ ] Tombol Filter terlihat di ujung kanan atas header.
- [ ] Tombol filter memiliki status aktif saat query filter digunakan.
- [ ] Popover dapat dibuka, ditutup, dan ditutup dengan `Escape`.
- [ ] Filter tetap terbawa saat pagination, sort, dan ekspor.
- [ ] Search bar mencari field yang relevan dengan domain TA.
- [ ] Tabel dapat di-scroll horizontal pada mobile.
- [ ] Empty state berbeda antara data kosong dan hasil pencarian kosong.
- [ ] Hak akses lihat, download, hapus, dan ekspor sudah dibatasi sesuai role.

### Pusat Bantuan

- [ ] Search bar berada di kanan header pada desktop.
- [ ] Search bar memenuhi lebar yang tersedia pada tablet/mobile.
- [ ] Setiap tab memiliki target section dengan ID yang benar.
- [ ] Tab aktif berubah mengikuti klik dan scroll.
- [ ] Pada lebar <= 767px tab berubah menjadi bottom navigation berbentuk pill.
- [ ] Label tab disembunyikan di mobile tetapi ikon tetap memiliki `aria-label`.
- [ ] Konten memiliki padding bawah sehingga tidak tertutup bottom navigation.
- [ ] Topik khusus Kinerja Operasi/Rincian Kegiatan sudah dihapus dari versi TA.

### Dashboard

- [ ] Kartu statistik memiliki ukuran dan jarak yang konsisten.
- [ ] Grid berubah menjadi dua kolom lalu satu kolom pada layar kecil.
- [ ] Angka, unit, label, dan status tidak saling bertumpuk.
- [ ] Panel dashboard berubah menjadi satu kolom pada tablet/mobile.
- [ ] Metrik yang tidak tersedia tidak menampilkan angka palsu atau delta kosong yang membingungkan.

### Data Master Karyawan

- [ ] Search, filter, reset, tambah, edit, dan hapus berjalan pada pane karyawan.
- [ ] Pagination mempertahankan query pencarian/filter.
- [ ] Tabel tidak memaksa layout desktop menyusut hingga sulit dibaca.
- [ ] Nilai kosong memiliki fallback yang jelas.
- [ ] Aksi hapus menggunakan konfirmasi dan validasi server.

## Ringkasan hasil yang perlu dibawa ke project TA

Implementasi minimum yang direkomendasikan adalah:

1. Arsip laporan dengan search, tombol Filter di kanan atas, sort, pagination, dan tabel responsive.
2. Pusat Bantuan dengan search bar di kanan header serta tab yang berubah menjadi bottom navigation pada mobile.
3. Dashboard dengan card statistik reusable dan layout grid yang responsif.
4. Data Master Karyawan dengan search/filter, tabel horizontal responsive, pagination, dan CRUD.
5. Admin yang lebih sederhana dengan hanya menu dan metrik yang benar-benar ada di project TA.

Jangan membawa domain operasional PT KSS secara otomatis. Bawa polanya, lalu ganti label, field, route, query, role, dan isi konten agar sesuai dengan kebutuhan tugas akhir.
