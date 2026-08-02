---
name: "Sistem Laporan Operasional KSS"
description: "Antarmuka pelaporan operasi yang terstruktur, cepat dipindai, dan konsisten lintas tema."
colors:
  primary-blue: "#2563EB"
  primary-blue-hover: "#1D4ED8"
  primary-blue-active: "#1E40AF"
  primary-blue-soft: "rgba(37,99,235,0.10)"
  cyan: "#0EA5E9"
  success: "#10B981"
  warning: "#F7931E"
  danger: "#D20000"
  text: "#0F172A"
  text-secondary: "#334155"
  text-muted: "#64748B"
  border: "#E2E8F0"
  divider: "#CBD5E1"
  canvas: "#F8FAFC"
  surface: "#FFFFFF"
  surface-pure: "#FFFFFF"
  dark-primary-blue: "#3B82F6"
  dark-primary-blue-hover: "#60A5FA"
  dark-primary-blue-active: "#93C5FD"
  dark-danger: "#EF4444"
  dark-text: "#F8FAFC"
  dark-text-secondary: "#CBD5E1"
  dark-text-muted: "#94A3B8"
  dark-border: "#334155"
  dark-canvas: "#0F172A"
  dark-surface: "#1E293B"
  chart-blue: "#2563EB"
  chart-green: "#10B981"
  chart-amber: "#F59E0B"
  chart-cyan: "#06B6D4"
  chart-violet: "#8B5CF6"
typography:
  headline:
    fontFamily: "Poppins, sans-serif"
    fontSize: "20px"
    fontWeight: 600
    lineHeight: 1.2
  title:
    fontFamily: "Poppins, sans-serif"
    fontSize: "15px"
    fontWeight: 600
  body:
    fontFamily: "Poppins, sans-serif"
    fontSize: "12px"
    fontWeight: 400
  label:
    fontFamily: "Poppins, sans-serif"
    fontSize: "10px"
    fontWeight: 500
  metric:
    fontFamily: "Poppins, sans-serif"
    fontSize: "26px"
    fontWeight: 700
    lineHeight: 1.1
rounded:
  compact: "6px"
  control: "8px"
  card: "10px"
  surface: "14px"
  pill: "999px"
spacing:
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
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-secondary}"
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
---

# Design System: Sistem Laporan Operasional KSS

## Overview

**Creative North Star: "The Structured Operations Ledger"**

Sistem ini menerjemahkan kepadatan spreadsheet operasional menjadi antarmuka terstruktur yang dapat dipindai cepat. Ia terasa tenang, presisi, dan praktis: permukaan terang atau gelap yang berlapis tipis, kartu ringkas, angka tabular, serta warna semantik yang dipakai untuk orientasi dan status—bukan dekorasi.

Mode utamanya adalah **Operate**. Dashboard membuka pembacaan langsung dengan tujuh kartu kegiatan dan laporan masuk. Kinerja Operasi melanjutkan dengan KPI, ringkasan kegiatan, serta analitik sebelum pengguna masuk ke Rincian Kegiatan. Poppins, Flaticon UICons, token tema, anatomi kartu, dan perilaku responsif tetap menjadi satu dunia visual lintas modul.

**Key Characteristics:**

- Padat namun berjarak teratur, seperti lembar kerja yang sudah disusun untuk keputusan cepat.
- Hierarki angka-ke-konteks: nilai utama tegas, satuan dan keterangan lebih tenang tetapi tetap terbaca.
- Kartu putih atau slate dengan batas halus, sudut membulat, dan bayangan rendah.
- Aksen biru untuk orientasi dan aksi; hijau, jingga, merah, cyan, dan palet grafik hanya untuk makna data.
- Paritas light/dark dan keterbacaan teks sekunder adalah bagian dari sistem, bukan variasi kosmetik.

## Colors

Palet memakai biru operasional sebagai aksen utama, warna status yang terbatas, dan netral slate yang menjaga data tetap dominan.

### Primary

- **Operational Blue:** aksi utama, navigasi aktif, fokus, indikator tab, dan seri data utama.
- **Deep Operational Blue:** keadaan hover dan aktif pada tema terang.
- **Soft Operational Blue:** latar pilihan, hover tenang, fokus, dan progress track.

### Secondary

- **Signal Cyan:** konteks shift dan kategori pendukung.
- **Success Green:** capaian baik, persetujuan, serta delta membaik.
- **Attention Orange:** tindakan baca dan kondisi yang perlu perhatian.
- **Alert Red:** bahaya, reset, penolakan, dan delta memburuk.

### Tertiary

- **Analytic Amber, Cyan, and Violet:** membedakan seri grafik pendamping tanpa mengubah hierarki aksi.

### Neutral

- **Ink Slate:** teks utama dan angka.
- **Secondary Slate:** label, subjudul, dan deskripsi operasional; harus tetap terbaca pada kedua tema.
- **Muted Slate:** metadata dan konteks tersier, bukan isi kritis.
- **Hairline Slate:** batas kartu, divider, grid, dan struktur tabel.
- **Canvas and Surface:** pemisahan latar aplikasi dari kartu, navbar, sidebar, dan popover.

**The Semantic Color Rule.** Warna non-netral harus menjelaskan aksi, status, kategori, atau seri data; jangan menggunakannya sebagai hiasan bebas.

**The Theme Pair Rule.** Semua warna implementasi harus berasal dari variabel tema yang memiliki pasangan light/dark; jangan menambahkan nilai warna langsung pada permukaan baru.

## Typography

**Display Font:** Poppins (sans-serif fallback)  
**Body Font:** Poppins (sans-serif fallback)

**Character:** Satu keluarga sans-serif menjaga layar internal tetap seragam dan efisien. Perbedaan peran datang dari ukuran, bobot, warna, dan numeric alignment—bukan pergantian keluarga font.

### Hierarchy

- **Headline** (600, 20px, 1.2): judul halaman; turun ke 18px pada layar sempit.
- **Title** (600, 15px): judul kartu, grafik, dan blok rincian.
- **Body** (400, 12px): navigasi, kontrol, tabel, dan isi operasional.
- **Label** (500, 10px): keterangan metrik, header tabel, metadata, dan unit pendamping.
- **Metric** (700, 26px, 1.1): KPI utama; gunakan angka tabular agar perbandingan vertikal stabil.

**The Readable Muted Rule.** Teks sekunder dan muted boleh lebih tenang, tetapi tidak boleh menjadi abu-abu yang terlalu redup untuk dibaca pada light maupun dark mode.

## Layout

Shell desktop memakai sidebar tetap (234px, dapat diringkas menjadi 60px), navbar atas, dan satu area konten vertikal yang menggulir. Konten memakai padding 30px horizontal dan ritme vertikal 15–20px. Dashboard memakai tujuh kartu kegiatan dalam tiga kolom, sedangkan Kinerja Operasi memakai kartu KPI empat kolom dan blok analitik dua kolom ketika ruang cukup.

Pada lebar 900px ke bawah, sidebar menjadi drawer dengan backdrop, navbar menempel di atas, dan padding konten menyusut. KPI Kinerja Operasi mempertahankan matriks 2×2 selama masih terbaca; analitik dan ringkasan turun menjadi satu kolom. Kartu kegiatan Dashboard memakai dua kolom ringkas pada ponsel, dengan kartu terakhir memenuhi baris saat tidak memiliki pasangan. Tabel lebar dan tujuh tab kegiatan tetap dapat digulir horizontal; tab kegiatan menjadi bilah bawah berikon agar akses detail tetap dekat dengan ibu jari.

**The Progressive Ledger Rule.** Mulai Dashboard dari tujuh kegiatan, lalu arahkan ke laporan masuk; pada Kinerja Operasi gunakan KPI → ringkasan kegiatan → analitik → detail. Jangan mendahulukan tabel panjang sebelum ringkasannya.

## Elevation & Depth

Sistem memakai hibrida batas halus dan bayangan ambient rendah. Kartu biasa hampir datar; popover filter, drawer, modal, dan tab kaca memperoleh bayangan lebih kuat karena benar-benar berada di atas alur. Tonal layering antara canvas dan surface tetap menjadi penanda kedalaman utama, termasuk dalam dark mode.

### Shadow Vocabulary

- **Card Low** (`0 1px 2px rgba(15,23,42,0.04)`): section card pada keadaan diam.
- **Metric Low** (`0 2px 4px rgba(37,99,235,0.07)`): kartu KPI dan statistik.
- **Action Lift** (`0 5px 14px rgba(37,99,235,0.18)`): pemicu filter utama.
- **Glass Float** (`0 8px 24px rgba(15,23,42,0.08)`): tab kegiatan yang sticky.
- **Popover High** (`0 20px 52px rgba(15,23,42,0.18)`): popover filter desktop.

**The Earned Elevation Rule.** Bayangan kuat hanya untuk elemen yang mengambang, menutup, atau menuntut fokus sementara.

## Shapes

Kontrol kecil memakai sudut 6–8px; kartu ringkas 10px; kartu utama dan dialog 14px. Pill 999px dipakai untuk status, delta, unit, dan progress, sementara ikon metrik dapat memakai lingkaran penuh. Batas satu piksel berwarna token memisahkan struktur tanpa membuat layar terasa seperti grid berat.

## Components

### Buttons

- **Shape:** kontrol kompak dengan radius 8px dan tinggi sekitar 34–38px.
- **Primary:** biru operasional, teks putih, padding 8px 14px; hover memakai biru yang lebih dalam.
- **Hover / Focus:** perubahan 0.2 detik; fokus terlihat sebagai ring biru transparan, bukan sekadar perubahan warna.
- **Secondary / Ghost:** surface dengan batas halus; hover mendapat tint biru lembut. Reset memakai outline merah dan tint merah saat hover.

### Chips

- **Style:** pill ringkas untuk status, kategori, shift, unit, dan delta; latar transparan bernada dengan teks semantik.
- **State:** warna mengomunikasikan makna; ikon kecil mendukung tetapi teks tetap menjadi sumber utama.

### Cards / Containers

- **Corner Style:** kartu KPI dan section card memakai sudut 14px; kartu kegiatan ringkas 10px.
- **Background:** surface di atas canvas, keduanya berganti melalui token tema.
- **Shadow Strategy:** low elevation pada kartu; high elevation hanya untuk overlay dan popover.
- **Border:** section card dan kartu analitik memakai batas satu piksel; header dipisah dengan hairline.
- **Internal Padding:** 14–20px sesuai kepadatan dan lebar layar.

### Inputs / Fields

- **Style:** Poppins 12px, surface, batas halus, radius 8px, dan label 10px di atas bidang.
- **Focus:** batas berubah ke biru dan kontrol khusus mendapat ring lembut yang tetap terlihat.
- **Error / Disabled:** gunakan token bahaya dan penurunan kontras yang masih terbaca; jangan mengandalkan warna saja.

### Navigation

Sidebar mengelompokkan menu dengan label uppercase kecil dan Flaticon UICons. Item default memakai teks sekunder; hover mendapat tint biru, sedangkan item aktif memakai tint biru, teks biru aktif, dan bobot 500. Pada mobile, sidebar menjadi drawer; tujuh tab kegiatan menjadi bilah ikon horizontal dengan state aktif biru dan semantik tab ARIA.

### KPI and Analytics

KPI memakai ikon bernada, label pendek, angka tabular besar, unit terpisah, delta pill, dan sparkline opsional. Grafik server-rendered memakai grid tenang, label sumbu yang tetap terbaca, serta bentuk/garis putus-putus selain warna untuk membedakan seri. Ton, MT untuk COB Muat Curah/Amoniak, dan Teus harus dilabeli sesuai sumber; Teus tidak dijumlahkan dengan massa atau memakai satu sumbu seolah satuannya setara.

## Do's and Don'ts

### Do:

- **Do** gunakan token tema, Poppins, Flaticon UICons, dan anatomi kartu incumbent untuk semua ekstensi.
- **Do** pertahankan Dashboard yang activity-first dan Kinerja Operasi yang KPI/summary-first sebelum analitik dan rincian.
- **Do** rata-kan angka pembanding ke kanan dan gunakan tabular numerals pada KPI, tabel, gauge, dan delta.
- **Do** pertahankan fokus terlihat, semantik ARIA, scroll horizontal terkontrol, dan teks muted yang terbaca pada kedua tema.
- **Do** nyatakan empty, unavailable, stale, dan partial-error secara jujur di dalam struktur yang sama.

### Don't:

- **Don't** membuat identitas, palet, font, ikon, atau anatomi kartu baru untuk satu halaman.
- **Don't** memakai warna hardcoded di luar token tema atau menggunakan warna non-netral tanpa makna data.
- **Don't** menghapus label sumber Ton, MT, atau Teus, atau mencampur Teus dengan massa dalam total maupun skala visual.
- **Don't** menumpuk kartu KPI Kinerja Operasi satu kolom di ponsel bila matriks 2×2 masih terbaca.
- **Don't** menyembunyikan fungsi atau status hanya di balik ikon, warna, hover, atau teks berkontras rendah.
