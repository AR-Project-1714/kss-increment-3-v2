# Design QA — Filter dan Tab Kinerja Manajer

## Target

- Source visual truth: `C:\Users\MUHAMM~1\AppData\Local\Temp\codex-clipboard-c3d37fef-389c-41e9-94e5-7adc28210cb5.png`
- Source bug tab: `C:\Users\MUHAMM~1\AppData\Local\Temp\codex-clipboard-cfa757bd-d9ab-435c-b3f8-840cc919e5c4.png`
- Source tab seluler: `C:\Users\MUHAMM~1\AppData\Local\Temp\codex-clipboard-af02696d-0419-4d8e-8a72-ff2ec77b60f3.png`
- Source bug lifecycle tab: `C:\Users\MUHAMM~1\AppData\Local\Temp\codex-clipboard-287113bb-4ab1-4cd8-8a83-84b20d426acb.png`
- Referensi tab: `resources/views/manajer/bantuan.blade.php`
- Implementasi: komponen aktual dari `performance-toolbar.blade.php`, `kegiatan.blade.php`, `manajer.css`, dan `charts.js`, dirender melalui harness lokal dengan aset build aplikasi.
- Screenshot desktop tertutup: `storage/app/qa/kss-performance-controls-desktop-closed.png`
- Screenshot desktop terbuka: `storage/app/qa/kss-performance-controls-desktop-open.png`
- Screenshot seluler: `storage/app/qa/kss-performance-controls-mobile.png`
- Perbandingan gabungan: `storage/app/qa/filter-reference-comparison.png`
- Screenshot fallback tab: `storage/app/qa/activity-tabs-fallback.png`
- Screenshot indikator siap: `storage/app/qa/activity-tabs-indicator-ready.png`
- Perbandingan bug dan hasil: `storage/app/qa/activity-tabs-comparison.png`
- Screenshot tab ikon seluler: `storage/app/qa/activity-tabs-mobile-icons.png`
- Perbandingan tab ikon seluler: `storage/app/qa/activity-tabs-mobile-comparison.png`
- Screenshot lifecycle desktop sesudah perbaikan: `storage/app/qa/activity-tabs-lifecycle-fixed-desktop.png`
- Screenshot lifecycle seluler sesudah perbaikan: `storage/app/qa/activity-tabs-lifecycle-fixed-mobile.png`
- Perbandingan collapse sebelum dan sesudah: `storage/app/qa/activity-tabs-collapse-comparison.png`

## Kondisi Perbandingan

- Desktop viewport: 1280 × 720 CSS px, device scale 1.
- Screenshot sumber: 2397 × 174 px.
- Screenshot implementasi desktop: 1280 × 720 px.
- Fokus perbandingan: toolbar filter sumber dibandingkan dengan popover filter terbuka; crop implementasi dinormalisasi ke lebar sumber dalam gambar perbandingan gabungan.
- Seluler: 390 × 844 CSS px, device scale 1.
- Focus tab ikon seluler: 390 × 180 CSS px, device scale 1. Screenshot sumber berukuran 512 × 87 px dan screenshot implementasi berukuran 390 × 180 px.
- Lifecycle tab desktop: 1280 × 720 CSS px, device scale 1. Screenshot bug sumber berukuran 2428 × 117 px dan screenshot implementasi berukuran 1280 × 720 px.
- State yang diperiksa: filter tertutup, filter terbuka, filter ditutup kembali, tab pertama aktif, tab kedua aktif, dan horizontal overflow tab pada layar kecil.
- State lifecycle yang diperiksa: skeleton awal, panel AJAX panjang selesai dimuat, klik tab kedua sebelum callback idle, cache/render ulang, font selesai dimuat, desktop 1280 px, dan seluler 390 px.

## Pemeriksaan Fidelity

- Fonts dan tipografi: tetap menggunakan font, bobot, hierarki, dan ukuran dari design system halaman manajer.
- Spacing dan layout: tombol Filter berada di kanan header; popover sejajar ke kanan, tidak mengambil ruang saat tertutup, dan berubah menjadi panel fixed yang dapat digulir di layar kecil.
- Colors dan tokens: warna, border, radius, shadow, active state, dan focus state memakai token yang sudah ada (`--blue-main`, `--smooth-border`, `--white`).
- Image dan asset: tidak ada aset raster baru; seluruh ikon menggunakan library UIcons yang sudah dipakai aplikasi.
- Copy dan konten: preset, tanggal, regu, shift, Reset, Ekspor, dan Terapkan tetap tersedia. Preset awal berubah menjadi Januari–Sekarang sesuai permintaan.

## Full-view dan Focused-region Evidence

- Full-view desktop menunjukkan filter tidak lagi menjadi island permanen; hanya tombol Filter yang terlihat pada header.
- Full-view seluler menunjukkan judul, tombol Filter, tab horizontal, dan kartu isi tidak bertumpuk.
- Focused-region comparison menunjukkan seluruh kontrol dari toolbar lama tetap ada di popover baru, dengan preset Januari–Sekarang aktif.
- Indikator tab diuji pada tab kedua: `offsetLeft` 195 px, `offsetWidth` 166 px, transform indikator `translateX(195px)`, dan width indikator `166px`.
- Console browser: tidak ada error atau warning.
- Tab seluler berisi enam tombol ikon dengan lebar sama 51 px, tanpa label teks yang terlihat dan tanpa horizontal overflow.
- Tab aktif tetap mempunyai `aria-label` lengkap (`Muat Kantong, Ton`) dan `title`, sehingga label tidak hilang bagi pembaca layar maupun pengguna pointer.
- Reproduksi sebelum perbaikan menghasilkan tinggi `.act-tabs` 11,33 px dan tinggi indikator 0 px setelah panel setinggi 1194 px masuk; tab aktif masih mempunyai teks putih tetapi background indikator tidak dapat digambar.
- Sesudah `flex-shrink: 0`, panel yang sama tetap menghasilkan tinggi tab 41,33 px dan tinggi indikator 30 px. Tab kedua tetap aktif setelah 1,2 detik, termasuk setelah callback idle dijalankan.
- Posisi indikator sesudah font dan scrollbar stabil sejajar dengan tombol aktif (selisih posisi/lebar di bawah 0,6 px).
- Pada 390 × 844, tinggi tab tetap 47,33 px, tinggi indikator 36 px, seluruh label tersembunyi, dan horizontal overflow 0 px.

## Comparison History

1. Pemeriksaan awal menemukan P2 pada tab seluler: lebar minimum 132 px membuat label dan unit terlalu padat.
2. Lebar minimum tab seluler dinaikkan menjadi 170 px.
3. Pemeriksaan ulang pada 390 × 844 menghasilkan `activeWidth = 170` dan `activeScrollWidth = 170`; konten tab tidak overflow, sedangkan container tetap dapat digeser horizontal.
4. Focus outline bawaan yang tidak sesuai warna sistem diganti dengan focus ring biru berbasis token aplikasi.
5. Pemeriksaan ulang desktop dan seluler tidak menemukan P0, P1, atau P2 tersisa.
6. Pemeriksaan lanjutan dari screenshot pengguna menemukan P1: tab pertama sudah aktif, tetapi teks putih dirender sebelum indikator biru memiliki lebar, sehingga tab tampak hilang.
7. State aktif diberi background biru sebagai fallback SSR/JS-lambat. Background tombol baru dilepas setelah `is-indicator-ready` ditambahkan oleh JavaScript sesudah ukuran dan posisi indikator valid.
8. Pemeriksaan fallback menghasilkan background `rgb(37, 99, 235)`, teks putih, dan tab pertama terlihat penuh pada lebar 180 px.
9. Pemeriksaan state siap menghasilkan indikator selebar 180 px pada `translateX(5px)` dan background tombol transparan, sehingga animasi indikator tetap bekerja tanpa mengorbankan tampilan awal.
10. Referensi terbaru menunjukkan P2 pada tab seluler: semua label dan unit harus disembunyikan, bukan dipadatkan dalam tab horizontal.
11. Pada viewport maksimum 560 px, label dan unit disembunyikan, setiap tab menjadi tombol ikon selebar minimum 44 px, dan `aria-label` serta `title` ditambahkan untuk mempertahankan konteks aksesibilitas.
12. Pemeriksaan animasi awal menemukan P2 singkat: indikator bergerak dari lebar nol dan membuat tab aktif tampak terbelah dua warna. Transisi dinonaktifkan hanya saat penempatan pertama, lalu dipulihkan pada frame berikutnya.
13. Pemeriksaan akhir pada 390 × 180 menunjukkan enam ikon terdistribusi merata, tab pertama berwarna biru penuh, tidak ada label yang terlihat, dan tidak ada overflow. Tidak ada P0, P1, atau P2 tersisa.
14. Screenshot lifecycle terbaru menemukan P1: setelah skeleton diganti oleh panel AJAX panjang, `.act-tabs` menyusut dari tinggi normal menjadi 11,33 px. Indikator dengan inset vertikal 5 px menjadi setinggi 0 px dan tab aktif tampak hilang.
15. Penyebabnya adalah `.act-tabs` sebagai flex-item di `.page-content` tidak mempunyai shrink guard, sedangkan referensi `.help-tabs` mempunyai `flex-shrink: 0`. Properti tersebut ditambahkan dan panel panjang diuji ulang.
16. Pemeriksaan interaksi menemukan P1 terpisah: callback idle awal selalu memuat tab pertama dan dapat menimpa tab yang dipilih cepat oleh pengguna. Callback kini hanya berjalan bila belum ada pilihan, sementara `selectedKey` juga mencegah respons lama menimpa panel tab cached.
17. Perubahan ukuran tanpa event resize (font, scrollbar panel, transisi sidebar) sebelumnya dapat menggeser indikator sekitar 1–2 px. `ResizeObserver` dan `document.fonts.ready` kini menyinkronkan ulang posisi berdasarkan tombol aktif.
18. Pemeriksaan akhir desktop menunjukkan tinggi tab 41,33 px, indikator 30 px, tab `muat-curah` tetap aktif setelah callback idle, dan indikator sejajar. Pemeriksaan seluler menunjukkan tinggi tab 47,33 px, ikon-only tetap berlaku, serta tidak ada overflow.

## Findings

Tidak ada mismatch P0, P1, atau P2 yang masih dapat ditindaklanjuti. Perbedaan struktur dari toolbar sumber menjadi popover dua baris merupakan perubahan yang disengaja sesuai permintaan pengguna.

## Interaksi yang Diuji

- Tombol Filter membuka popover dan mengubah `aria-expanded` menjadi `true`.
- Tombol tutup menutup popover.
- Tab kedua dapat dipilih dan indikator berpindah sesuai posisi dan lebar tombol.
- Tab dapat digeser horizontal pada viewport seluler tanpa memotong isi tab aktif.
- Pada viewport maksimum 560 px, tab hanya menampilkan ikon dan tetap dapat dipilih.
- Setelah tab kedua dipilih sebelum callback idle, pilihan tetap pada tab kedua setelah callback dan respons panel selesai.
- Setelah konten panel panjang dimuat, tinggi tab dan indikator tidak berubah menjadi strip tipis.

final result: passed
