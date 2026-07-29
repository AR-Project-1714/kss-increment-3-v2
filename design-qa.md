# Design QA — Peringkat Lembur

## Artefak Pembanding

- Source visual truth path: `C:\Users\MUHAMM~1\AppData\Local\Temp\codex-clipboard-0e7e89d0-f5db-4765-af85-ff0aafb0f0fe.png`
- Implementation screenshot path: `C:\laragon\www\app-kss-codex\design-qa-implementation-full.jpg`
- Focused implementation path: `C:\laragon\www\app-kss-codex\design-qa-implementation-focused.png`
- Side-by-side comparison path: `C:\laragon\www\app-kss-codex\design-qa-comparison.png`
- Responsive screenshot path: `C:\laragon\www\app-kss-codex\design-qa-responsive.jpg`

## Normalisasi

- Source: 852 × 437 piksel.
- Implementasi penuh: 1440 × 877 piksel dari viewport CSS 1440 × 1000, device scale factor 1.
- Fokus implementasi: crop 1137 × 647 piksel, lalu diperkecil secara proporsional menjadi lebar 852 piksel pada gambar perbandingan.
- Gambar perbandingan: 1724 × 485 piksel; source di kiri dan implementasi di kanan.
- State: mode terang, periode bulan berjalan 1–29 Jul 2026, tabel berisi data riil laporan operasi.

## Pemeriksaan Permukaan Utama

- Fonts and typography: memakai Instrument Sans dan bobot yang sudah menjadi sistem desain aplikasi. Hierarki header, nama, angka, dan unit tetap jelas setelah normalisasi.
- Spacing and layout rhythm: padding header/baris, garis pembatas, tinggi baris, radius, dan alignment numerik konsisten dengan referensi serta kartu aplikasi.
- Colors and visual tokens: warna netral memakai token aplikasi; indikator semantik hijau/merah/biru dan badge Regu A–D mempunyai warna berbeda dengan kontras yang cukup.
- Image quality and asset fidelity: referensi menggunakan avatar foto, tetapi kebutuhan produk secara eksplisit menggantinya dengan identitas regu berbentuk lingkaran. Ikon naik/turun memakai pustaka ikon aplikasi, bukan aset tiruan.
- Copy and content: kolom akhir sesuai permintaan—Posisi, Nama Petugas, Jumlah Lembur, Total Jam Lembur, dan Rata-rata Jam Lembur.

## Full-view Comparison Evidence

`design-qa-comparison.png` menunjukkan struktur tabel, kepadatan baris, header netral, garis pemisah, dan alignment angka mengikuti karakter referensi. Perbedaan kolom “Previous Times” dan “Changes +/-” pada referensi adalah penyesuaian yang memang diminta pengguna: perubahan posisi digabungkan di kolom Posisi dan rata-rata jam menjadi kolom terakhir.

## Focused Region Comparison Evidence

Fokus tabel diperlukan karena halaman aplikasi memiliki sidebar, navbar, dan kartu analitik lain yang tidak termasuk source. Crop `design-qa-implementation-focused.png` memperlihatkan badge Regu A–D, nama, posisi, dan tiga metrik lembur tanpa gangguan konteks halaman.

## Findings

- Tidak ada temuan P0, P1, atau P2.
- Indikator “Baru” tampil pada data live karena nama personil periode Juni dan Juli tidak saling beririsan. State naik/turun diverifikasi lewat pengujian terisolasi dengan data dua periode.
- Pada viewport 700 × 800, pembungkus tabel memiliki lebar 623 piksel dan scroll width 820 piksel. Overflow hanya terjadi di dalam tabel; halaman tidak mengalami overflow horizontal.

## Interaction and Runtime Checks

- Tombol “Lihat semua 12 personil” berhasil membuka baris 11–12 dan berubah menjadi “Tampilkan 10 teratas” dengan `aria-expanded="true"`.
- Scroll horizontal tersedia ketika tabel tidak muat pada viewport sempit.
- Console browser: tidak ada error.

## Comparison History

1. Final pass: membandingkan source dengan capture desktop terfokus dan capture responsive. Tidak ditemukan perbedaan P0/P1/P2; tidak diperlukan iterasi perbaikan visual blocking.

## Follow-up Polish

- Tidak ada polish yang diperlukan untuk memenuhi permintaan saat ini.

final result: passed
