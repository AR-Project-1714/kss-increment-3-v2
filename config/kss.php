<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas Awal Memori Susunan Karyawan OP.7
    |--------------------------------------------------------------------------
    |
    | Form laporan mengingat susunan karyawan OP.7 (urutan baris, no. forklift,
    | area kerja) dari laporan terakhir tiap regu. Laporan yang DIBUAT sebelum
    | tanggal ini diabaikan sebagai sumber susunan.
    |
    | Default null: seluruh riwayat laporan dipertimbangkan. Ini pilihan yang
    | tepat bila laporan yang sudah ada memang disusun petugas dengan benar --
    | kerja mereka langsung terpakai tanpa perlu menyusun ulang.
    |
    | Isi dengan tanggal (format Y-m-d) hanya bila riwayat laporan mengandung
    | susunan lama/uji coba yang tidak ingin terbawa. Nama di luar anggota OP.7
    | regu bersangkutan sudah tersaring otomatis, jadi setelan ini hanya
    | diperlukan untuk data keliru yang namanya justru anggota regu itu sendiri.
    |
    */

    'roster_memory_since' => env('ROSTER_MEMORY_SINCE', null),

];
