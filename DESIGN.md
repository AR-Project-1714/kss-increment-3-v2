---
name: "Sistem Laporan Operasional KSS"
description: "Antarmuka pelaporan operasi yang terstruktur, cepat dipindai, dan konsisten lintas tema."
colors:
  primary-blue: "#2563EB"
  primary-blue-hover: "#1D4ED8"
  primary-blue-active: "#1E40AF"
  primary-blue-soft: "rgba(37,99,235,0.10)"
  primary-blue-bg: "#E5F1FF"
  cyan: "#0EA5E9"
  cyan-hover: "#0B83B5"
  cyan-active: "#09658B"
  success: "#10B981"
  success-hover: "#0F9A6B"
  success-active: "#0E7A55"
  warning: "#F7931E"
  warning-hover: "#E67E00"
  warning-active: "#CC6F00"
  warning-bg: "#FEF4E8"
  danger: "#D20000"
  danger-hover: "#B80000"
  danger-active: "#9F0000"
  text: "#0F172A"
  text-secondary: "#334155"
  text-muted: "#64748B"
  border: "#E2E8F0"
  divider: "#CBD5E1"
  canvas: "#F8FAFC"
  surface: "#FFFFFF"
  surface-pure: "#FFFFFF"
  frost-surface: "rgba(255,255,255,0.72)"
  frost-border: "rgba(226,232,240,0.78)"
  frost-edge: "rgba(255,255,255,0.72)"
  frost-inset: "rgba(255,255,255,0.46)"
  dark-primary-blue: "#3B82F6"
  dark-primary-blue-hover: "#60A5FA"
  dark-primary-blue-active: "#93C5FD"
  dark-cyan-hover: "#38BDF8"
  dark-success-hover: "#34D399"
  dark-warning: "#F97316"
  dark-danger: "#EF4444"
  dark-danger-hover: "#F87171"
  dark-text: "#F8FAFC"
  dark-text-secondary: "#CBD5E1"
  dark-text-muted: "#94A3B8"
  dark-border: "#334155"
  dark-canvas: "#0A101C"
  dark-surface: "#1E293B"
  dark-frost-surface: "rgba(30,41,59,0.68)"
  dark-frost-border: "rgba(148,163,184,0.24)"
  chart-blue: "#2563EB"
  chart-green: "#10B981"
  chart-amber: "#F59E0B"
  chart-cyan: "#06B6D4"
  chart-violet: "#8B5CF6"
typography:
  metric:
    fontFamily: "Poppins, sans-serif"
    fontSize: "26px"
    fontWeight: 700
    lineHeight: 1.1
  display:
    fontFamily: "Poppins, sans-serif"
    fontSize: "24px"
    fontWeight: 700
    lineHeight: 1.15
  metric-sm:
    fontFamily: "Poppins, sans-serif"
    fontSize: "22px"
    fontWeight: 700
    lineHeight: 1.15
  headline:
    fontFamily: "Poppins, sans-serif"
    fontSize: "20px"
    fontWeight: 600
    lineHeight: 1.2
  headline-sm:
    fontFamily: "Poppins, sans-serif"
    fontSize: "18px"
    fontWeight: 600
    lineHeight: 1.25
  title:
    fontFamily: "Poppins, sans-serif"
    fontSize: "16px"
    fontWeight: 600
    lineHeight: 1.3
  subtitle:
    fontFamily: "Poppins, sans-serif"
    fontSize: "15px"
    fontWeight: 600
    lineHeight: 1.3
  body-lg:
    fontFamily: "Poppins, sans-serif"
    fontSize: "14px"
    fontWeight: 500
    lineHeight: 1.4
  caption:
    fontFamily: "Poppins, sans-serif"
    fontSize: "13px"
    fontWeight: 500
    lineHeight: 1.35
  body:
    fontFamily: "Poppins, sans-serif"
    fontSize: "12px"
    fontWeight: 400
    lineHeight: 1.5
  body-sm:
    fontFamily: "Poppins, sans-serif"
    fontSize: "11px"
    fontWeight: 400
    lineHeight: 1.45
  label:
    fontFamily: "Poppins, sans-serif"
    fontSize: "10px"
    fontWeight: 500
    lineHeight: 1.4
  meta:
    fontFamily: "Poppins, sans-serif"
    fontSize: "9px"
    fontWeight: 400
    lineHeight: 1.45
  micro:
    fontFamily: "Poppins, sans-serif"
    fontSize: "8px"
    fontWeight: 700
    lineHeight: 1.4
rounded:
  hairline: "4px"
  compact: "6px"
  control: "8px"
  control-lg: "9px"
  card: "10px"
  panel: "12px"
  surface: "14px"
  overlay: "16px"
  lozenge: "18px"
  bubble: "20px"
  pill: "999px"
  circle: "50%"
spacing:
  micro: "2px"
  hairline: "4px"
  tight: "6px"
  control: "8px"
  compact: "10px"
  regular: "12px"
  card: "14px"
  section: "16px"
  content: "20px"
  wide: "24px"
  page: "30px"
components:
  button-primary:
    backgroundColor: "{colors.primary-blue}"
    textColor: "{colors.surface-pure}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "8px 14px"
  button-primary-hover:
    backgroundColor: "{colors.primary-blue-hover}"
    textColor: "{colors.surface-pure}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-secondary}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "8px 14px"
  button-danger:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.danger}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "8px 14px"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "8px 12px"
  kpi-card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.surface}"
    padding: "16px 18px 14px"
  section-card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.surface}"
    padding: "20px"
  nav-active:
    backgroundColor: "{colors.primary-blue-soft}"
    textColor: "{colors.primary-blue-active}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "8px 10px"
  tab-active:
    backgroundColor: "{colors.primary-blue}"
    textColor: "{colors.surface-pure}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "6px 12px"
  status-success:
    backgroundColor: "rgba(16,185,129,0.10)"
    textColor: "{colors.success}"
    typography: "{typography.label}"
    rounded: "{rounded.pill}"
    padding: "3px 9px"
  frost-popover:
    backgroundColor: "{colors.frost-surface}"
    textColor: "{colors.text}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.surface}"
    padding: "14px"
  toast:
    backgroundColor: "{colors.frost-surface}"
    textColor: "{colors.text}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.lozenge}"
    padding: "12px 14px"
---

# Design System: Sistem Laporan Operasional KSS

## Overview

**Creative North Star: "The Structured Operations Ledger"**

Sistem ini menerjemahkan kepadatan spreadsheet operasional menjadi antarmuka terstruktur yang dapat dipindai cepat. Ia terasa tenang, presisi, dan praktis: permukaan terang atau gelap yang berlapis tipis, kartu ringkas, angka tabular, serta warna semantik yang dipakai untuk orientasi dan status—bukan dekorasi.

Mode utamanya adalah **Operate**. Dashboard membuka pembacaan langsung dengan tujuh kartu kegiatan dan laporan masuk. Kinerja Operasi melanjutkan dengan KPI, ringkasan kegiatan, serta analitik sebelum pengguna masuk ke Rincian Kegiatan. Poppins, Flaticon UICons, token tema, anatomi kartu, dan perilaku responsif tetap menjadi satu dunia visual lintas modul.

Sistem ini padat dengan sengaja. Antarmuka internal yang dipakai berjam-jam oleh petugas shift menukar kelapangan dengan jumlah data per layar, sehingga ramp tipografinya turun sampai 8px dan langkah antar ukurannya rapat—1px pada rentang bawah. Itu bukan kecelakaan yang harus dirapikan menjadi skala tipografi editorial; itu konsekuensi dari lembar kerja yang harus muat.

**Key Characteristics:**

- Padat namun berjarak teratur, seperti lembar kerja yang sudah disusun untuk keputusan cepat.
- Hierarki angka-ke-konteks: nilai utama tegas, satuan dan keterangan lebih tenang tetapi tetap terbaca.
- Kartu putih atau slate dengan batas halus, sudut membulat, dan bayangan rendah.
- Permukaan mengambang memakai satu resep kaca beku (frosted) yang matte, bukan liquid glass yang mengilap.
- Aksen biru untuk orientasi dan aksi; hijau, jingga, merah, cyan, dan palet grafik hanya untuk makna data.
- Paritas light/dark dan keterbacaan teks sekunder adalah bagian dari sistem, bukan variasi kosmetik.

## Colors

Palet memakai biru operasional sebagai aksen utama, warna status yang terbatas, dan netral slate yang menjaga data tetap dominan.

### Primary

- **Operational Blue** (`#2563EB`, dark `#3B82F6`): aksi utama, navigasi aktif, fokus, indikator tab, dan seri data utama.
- **Deep Operational Blue** (`#1D4ED8` hover, `#1E40AF` active): keadaan hover dan aktif pada tema terang.
- **Soft Operational Blue** (`rgba(37,99,235,0.10)`): latar pilihan, hover tenang, fokus, dan progress track.

### Secondary

- **Signal Cyan** (`#0EA5E9`): konteks shift dan kategori pendukung.
- **Success Green** (`#10B981`): capaian baik, persetujuan, serta delta membaik.
- **Attention Orange** (`#F7931E`, dark `#F97316`): tindakan baca dan kondisi yang perlu perhatian.
- **Alert Red** (`#D20000`, dark `#EF4444`): bahaya, reset, penolakan, dan delta memburuk.

### Tertiary

- **Analytic Amber, Cyan, and Violet** (`#F59E0B`, `#06B6D4`, `#8B5CF6`): membedakan seri grafik pendamping tanpa mengubah hierarki aksi.

### Neutral

- **Ink Slate** (`#0F172A`, dark `#F8FAFC`): teks utama dan angka.
- **Secondary Slate** (`#334155`, dark `#CBD5E1`): label, subjudul, dan deskripsi operasional; harus tetap terbaca pada kedua tema.
- **Muted Slate** (`#64748B` / `#94A3B8`): metadata dan konteks tersier, bukan isi kritis.
- **Hairline Slate** (`#E2E8F0`, dark `#334155`): batas kartu, divider, grid, dan struktur tabel.
- **Canvas and Surface** (`#F8FAFC` / `#FFFFFF`, dark `#0A101C` / `#1E293B`): pemisahan latar aplikasi dari kartu, navbar, sidebar, dan popover.

**The Dark Canvas Depth Rule.** Pada dark mode kanvas harus jauh lebih dalam daripada permukaan kartu — jarak yang dipakai sistem ini adalah **11,7 langkah L\*** (kanvas L\* 4,7 ke permukaan L\* 16,4). Bayangan hampir tidak terbaca di atas latar gelap, jadi pemisahan nada inilah yang menggantikannya. Jangan menurunkan jarak itu, dan jangan pula membawa kanvas ke hitam pekat: teks aplikasi ini banyak yang 8–11px, dan kontras ekstrem pada ukuran sekecil itu membuat huruf bergetar.

### Alpha Ramps

Setiap hue aksi membawa turunan transparan pada 2%, 5%, 10%, 25%, dan 40% (`--blue-main-5`, `--red-main-25`, dan seterusnya). Turunan inilah yang dipakai untuk tint hover, latar chip, ring fokus, dan ikon bernada—bukan warna solid yang diberi opacity pada elemennya.

**The Semantic Color Rule.** Warna non-netral harus menjelaskan aksi, status, kategori, atau seri data; jangan menggunakannya sebagai hiasan bebas.

**The Theme Pair Rule.** Semua warna implementasi harus berasal dari variabel tema yang memiliki pasangan light/dark; jangan menambahkan nilai warna langsung pada permukaan baru.

## Typography

**Display Font:** Poppins (sans-serif fallback)
**Body Font:** Poppins (sans-serif fallback)

**Character:** Satu keluarga sans-serif menjaga layar internal tetap seragam dan efisien. Perbedaan peran datang dari ukuran, bobot, warna, dan numeric alignment—bukan pergantian keluarga font.

### Hierarchy

Ramp ini rapat karena antarmukanya padat. Langkah 1px pada rentang bawah adalah bagian dari sistem, bukan penyimpangan.

- **Metric** (700, 26px, 1.1): nilai KPI utama; gunakan angka tabular agar perbandingan vertikal stabil.
- **Display** (700, 24px): angka besar non-KPI dan judul ringkasan tebal.
- **Metric Small** (700, 22px): nilai KPI pada layar sempit.
- **Headline** (600, 20px, 1.2): judul halaman.
- **Headline Small** (600, 18px): judul halaman pada layar sempit dan kepala modal besar.
- **Title** (600, 16px): judul blok dan kepala modal.
- **Subtitle** (600, 15px): judul kartu, grafik, dan blok rincian.
- **Body Large** (500, 14px): baris penegasan, label kontrol utama, dan isi tombol besar.
- **Caption** (500, 13px): judul popover filter, ringkasan pendamping, dan teks kontrol sekunder.
- **Body** (400, 12px): navigasi, kontrol, tabel, dan isi operasional. Ukuran paling sering dipakai.
- **Body Small** (400, 11px): sel tabel padat, isi popover, dan teks pendukung.
- **Label** (500, 10px): keterangan metrik, header tabel, metadata, dan unit pendamping.
- **Meta** (400, 9px): stempel waktu, keterangan tersier, dan catatan kaki kartu.
- **Micro** (700, 8px): badge hitung, pill status ringkas, dan footer panel.

### Responsive Steps

Metric Small (22px) dan Headline Small (18px) adalah turunan responsif, bukan langkah bebas: Headline turun ke 18px di bawah 900px, dan Metric turun ke 22px lalu 18px pada ponsel. Subtitle turun ke Caption (13px). Jangan memakai keduanya sebagai ukuran dasar pada layar lebar.

Nilai **17px**, **7px**, dan **48px** yang masih muncul di kode adalah sisa satu-dua tempat dan bukan bagian sistem; konvergensikan ke langkah terdekat saat menyentuh aturan tersebut.

**The Readable Muted Rule.** Teks sekunder dan muted boleh lebih tenang, tetapi tidak boleh menjadi abu-abu yang terlalu redup untuk dibaca pada light maupun dark mode.

**The Dense Ramp Rule.** Ukuran di bawah 12px sudah menjadi bagian sah dari sistem ini, tetapi hanya untuk label, metadata, dan badge. Jangan memakai 9px atau 8px untuk kalimat yang harus dibaca, dan jangan menambah langkah baru di antara yang sudah ada.

## Layout

Shell desktop memakai sidebar tetap (234px, dapat diringkas menjadi 60px), navbar atas, dan satu area konten vertikal yang menggulir. Konten memakai padding 30px horizontal dan ritme vertikal 15–20px. Dashboard memakai tujuh kartu kegiatan dalam tiga kolom, sedangkan Kinerja Operasi memakai kartu KPI empat kolom dan blok analitik dua kolom ketika ruang cukup.

Pada lebar 900px ke bawah, sidebar menjadi drawer dengan backdrop, navbar menempel di atas, dan padding konten menyusut. KPI Kinerja Operasi mempertahankan matriks 2×2 selama masih terbaca; analitik dan ringkasan turun menjadi satu kolom. Kartu kegiatan Dashboard memakai dua kolom ringkas pada ponsel, dengan kartu terakhir memenuhi baris saat tidak memiliki pasangan. Tabel lebar dan tujuh tab kegiatan tetap dapat digulir horizontal; tab kegiatan menjadi bilah bawah berikon agar akses detail tetap dekat dengan ibu jari.

### Breakpoints

Titik henti yang benar-benar dipakai: **1100px**, **1024px**, **900px** (sidebar menjadi drawer), **768px**, **640px**, **560px**, **480px**, dan **430px** (KPI mengecil agar 2×2 tetap muat). Nilai 920px, 767px, 720px, 700px, dan 420px muncul sebagai penyesuaian lokal per halaman; jangan menambah titik henti global baru tanpa alasan yang jelas.

**The Progressive Ledger Rule.** Mulai Dashboard dari tujuh kegiatan, lalu arahkan ke laporan masuk; pada Kinerja Operasi gunakan KPI → ringkasan kegiatan → analitik → detail. Jangan mendahulukan tabel panjang sebelum ringkasannya.

## Elevation & Depth

Sistem memakai hibrida batas halus dan bayangan ambient rendah. Kartu biasa hampir datar; popover, drawer, modal, dan toast memperoleh bayangan lebih kuat karena benar-benar berada di atas alur. Tonal layering antara canvas dan surface tetap menjadi penanda kedalaman utama, termasuk dalam dark mode.

Semua permukaan mengambang berbagi satu resep **frosted** dari `resources/css/components/frosted-surface.css`: veil rata 72% putih (dark 68% slate), `backdrop-filter: blur(26px) saturate(150%)`, satu hairline terang di sisi atas, dan bayangan berlapis tipis. Resep ini sengaja bukan liquid glass—tidak ada gradien diagonal, border terang/gelap asimetris, atau inner sheen.

### Shadow Vocabulary

- **Card Low** (`0 1px 2px rgba(15,23,42,0.04)`): section card pada keadaan diam.
- **Metric Low** (`0 2px 4px rgba(37,99,235,0.07)`): kartu KPI dan statistik.
- **Focus Ring** (`0 0 0 3px var(--blue-main-10)`): ring fokus kontrol; varian merah dan jingga memakai alpha hue yang sama.
- **Action Lift** (`0 5px 14px rgba(37,99,235,0.18)`): pemicu filter utama.
- **Glass Float** (`0 8px 24px rgba(15,23,42,0.08)`): tab kegiatan yang sticky.
- **Frost Float** (`0 1px 2px / 0 4px 10px / 0 12px 26px / 0 26px 50px` pada alpha 4–6%): seluruh keluarga frosted—popover info form, tooltip status kapal, dropdown profil, panel notifikasi, popover filter, dan toast. Empat lapis tipis, bukan satu lapis pekat.
- **Card Ring** (`0 0 0 1px var(--kss-card-ring)`): hairline tepi kartu, transparan pada mode terang dan `--smooth-border` pada mode gelap. Dipasang sebagai lapis pertama box-shadow kartu.

**The Dark Edge Rule.** Di mode gelap, tepi kartu digambar oleh hairline, bukan oleh bayangan—bayangan hampir tidak terbaca di atas latar gelap. Hairline itu memakai ring box-shadow (blur 0, spread 1px), bukan `border`, karena kartu-kartu ini sudah punya padding dan tinggi mapan yang tidak boleh bergeser saat tema berganti.

### Motion

Easing utama `cubic-bezier(.22, 1, .36, 1)` untuk buka-tutup permukaan, `cubic-bezier(0.16, 1, 0.3, 1)` untuk masuknya toast, dan `cubic-bezier(0.4, 0, 0.2, 1)` untuk transisi sidebar. Durasi berkisar 0.15–0.34s; 0.2s adalah default.

**The Earned Elevation Rule.** Bayangan kuat hanya untuk elemen yang mengambang, menutup, atau menuntut fokus sementara.

**The Matte Overlay Rule.** Permukaan mengambang memakai satu veil rata dengan blur tebal. Gradien diagonal, border terang/gelap asimetris, dan inner sheen adalah kosakata liquid glass dan tidak dipakai di sistem ini.

## Shapes

Radius naik seiring ukuran dan ketinggian elemen: penanda kecil dan progress 4px; kontrol kompak 6–9px; kartu ringkas 10px; panel dan popover filter 12–14px; overlay besar 16px; toast 18px; bilah mengambang 20px. Pill 999px dipakai untuk status, delta, unit, dan progress, sementara ikon metrik dan avatar memakai lingkaran penuh (50%). Batas satu piksel berwarna token memisahkan struktur tanpa membuat layar terasa seperti grid berat.

**The Pill Spelling Rule.** Pill kanonis ditulis `999px`. Nilai `50px` dan `100px` yang masih tersisa adalah ejaan lama dengan hasil visual sama; jangan menambah ejaan baru, dan konvergensikan ke `999px` saat menyentuh aturan tersebut.

## Components

### Buttons

- **Shape:** kontrol kompak dengan radius 8px dan tinggi sekitar 34–38px.
- **Primary:** biru operasional, teks putih, padding 8px 14px; hover memakai biru yang lebih dalam.
- **Hover / Focus:** perubahan 0.2 detik; fokus terlihat sebagai ring biru transparan 3px, bukan sekadar perubahan warna.
- **Secondary / Ghost:** surface dengan batas halus; hover mendapat tint biru lembut. Reset memakai outline merah dan tint merah saat hover.

### Chips

- **Style:** pill ringkas untuk status, kategori, shift, unit, dan delta; latar transparan bernada dengan teks semantik.
- **State:** warna mengomunikasikan makna; ikon kecil mendukung tetapi teks tetap menjadi sumber utama.

### Cards / Containers

- **Corner Style:** kartu KPI dan section card memakai sudut 14px; kartu kegiatan ringkas 10px.
- **Background:** surface di atas canvas, keduanya berganti melalui token tema.
- **Shadow Strategy:** low elevation pada kartu; high elevation hanya untuk overlay dan popover.
- **Border:** section card dan kartu analitik memakai batas satu piksel; header dipisah dengan hairline.
- **Internal Padding:** 14–20px sesuai kepadatan dan lebar layar; turun ke 10–12px pada ponsel.

### Inputs / Fields

- **Style:** Poppins 12px, surface, batas halus, radius 8px, dan label 10px di atas bidang.
- **Focus:** batas berubah ke biru dan kontrol khusus mendapat ring lembut yang tetap terlihat.
- **Error / Disabled:** gunakan token bahaya dan penurunan kontras yang masih terbaca; jangan mengandalkan warna saja.

### Navigation

Sidebar mengelompokkan menu dengan label uppercase kecil dan Flaticon UICons. Item default memakai teks sekunder; hover mendapat tint biru, sedangkan item aktif memakai tint biru, teks biru aktif, dan bobot 500. Pada mobile, sidebar menjadi drawer; tujuh tab kegiatan menjadi bilah ikon horizontal dengan state aktif biru dan semantik tab ARIA.

### Frosted Overlays

Keluarga permukaan mengambang yang berbagi satu resep: popover info form, tooltip status pekerjaan kapal, dropdown profil, panel notifikasi, popover filter, dan toast. Semuanya memakai token `--kss-frost-*`, radius 14px (toast 18px), dan hairline atas yang sama.

Isi di dalam panel frosted harus ikut tembus. Tile dan baris daftar memakai `--kss-frost-inset` atau transparan; latar solid `var(--white)` di dalam panel akan memotong kabutnya. Ketika `backdrop-filter` tidak tersedia atau pengguna meminta `prefers-reduced-transparency`, veil naik ke hampir solid agar teks tetap kontras.

Dropdown profil dibuka dua cara dengan umur berbeda: hover membuka sementara dan menutup saat kursor pergi; klik memaku sampai ada klik di luar. Panel notifikasi dan dropdown profil tidak pernah terbuka bersamaan—keduanya berkoordinasi lewat event `kss:overlay-open`.

### Theme Selector

Segmented control tiga pilihan setara di dalam dropdown profil: **Terang**, **Gelap**, dan **Sistem**. Jalurnya cekung memakai `--kss-frost-inset`; segmen aktif diangkat ke `--white` dengan bayangan tipis sehingga terbaca timbul di atas jalurnya.

Sakelar biner tidak dipakai karena "Sistem" bukan posisi tengah antara Terang dan Gelap—ia pilihan ketiga yang menyerahkan keputusan ke perangkat. Bentuk segmented menyatakan ketiganya sejajar, dan yang aktif terbaca dari posisi, bukan dari hafalan arah sakelar.

Teks status di bawah judul menyebut hasil nyata, bukan sekadar nama pilihan: saat "Sistem" terpilih ia berbunyi `Mengikuti sistem (gelap)`. Di bawah 380px label teks disembunyikan dan menyisakan ikon agar ketiga segmen tetap muat.

Semantiknya `role="radiogroup"` dengan `aria-checked` dan satu titik tab (`tabindex` 0 pada yang aktif, -1 pada sisanya); panah kiri/kanan, Home, dan End berpindah pilihan.

**The Single Theme Source Rule.** Resolusi tema hanya boleh datang dari `window.kssTheme` di `partials/theme-init.blade.php`. Jangan menulis ulang `localStorage.getItem('theme') === 'dark'` di tempat lain: pemeriksaan seperti itu buta terhadap pilihan "Sistem" dan akan menyimpang begitu aturannya berubah.

### KPI and Analytics

KPI memakai ikon bernada, label pendek, angka tabular besar, unit terpisah, delta pill, dan sparkline opsional. Grafik server-rendered memakai grid tenang, label sumbu yang tetap terbaca, serta bentuk/garis putus-putus selain warna untuk membedakan seri. Ton, MT untuk COB Muat Curah/Amoniak, dan Teus harus dilabeli sesuai sumber; Teus tidak dijumlahkan dengan massa atau memakai satu sumbu seolah satuannya setara.

## Do's and Don'ts

### Do:

- **Do** gunakan token tema, Poppins, Flaticon UICons, dan anatomi kartu incumbent untuk semua ekstensi.
- **Do** ambil ukuran teks dari dua belas peran yang sudah terdaftar, termasuk langkah rapat 9–14px.
- **Do** pakai token `--kss-frost-*` untuk setiap permukaan mengambang baru, bukan meracik kaca sendiri.
- **Do** pertahankan Dashboard yang activity-first dan Kinerja Operasi yang KPI/summary-first sebelum analitik dan rincian.
- **Do** rata-kan angka pembanding ke kanan dan gunakan tabular numerals pada KPI, tabel, gauge, dan delta.
- **Do** pertahankan fokus terlihat, semantik ARIA, scroll horizontal terkontrol, dan teks muted yang terbaca pada kedua tema.
- **Do** nyatakan empty, unavailable, stale, dan partial-error secara jujur di dalam struktur yang sama.

### Don't:

- **Don't** membuat identitas, palet, font, ikon, atau anatomi kartu baru untuk satu halaman.
- **Don't** menambah langkah tipografi atau radius baru di antara yang sudah terdaftar; ramp ini sudah rapat.
- **Don't** memakai gradien diagonal, border asimetris, atau inner sheen pada permukaan mengambang.
- **Don't** memakai warna hardcoded di luar token tema atau menggunakan warna non-netral tanpa makna data.
- **Don't** menghapus label sumber Ton, MT, atau Teus, atau mencampur Teus dengan massa dalam total maupun skala visual.
- **Don't** menumpuk kartu KPI Kinerja Operasi satu kolom di ponsel bila matriks 2×2 masih terbaca.
- **Don't** menyembunyikan fungsi atau status hanya di balik ikon, warna, hover, atau teks berkontras rendah.
