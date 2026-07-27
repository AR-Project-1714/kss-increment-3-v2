<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas Awal Memori Susunan Karyawan
    |--------------------------------------------------------------------------
    |
    | Form laporan mengingat susunan karyawan (urutan baris, no. forklift, area
    | kerja) dari laporan terakhir tiap regu. Laporan yang DIBUAT sebelum
    | tanggal ini diabaikan sebagai sumber susunan, supaya data lama atau data
    | uji coba tidak ikut terbawa ke laporan baru.
    |
    | Isi dengan tanggal saat roster resmi mulai berlaku (format Y-m-d). Set
    | null untuk mempertimbangkan seluruh laporan tanpa batas.
    |
    */

    'roster_memory_since' => env('ROSTER_MEMORY_SINCE', '2026-07-27'),

];
