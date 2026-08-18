<?php

namespace App\Support;

/**
 * Penyeragaman nama bahan baku.
 *
 * Jenis bahan diketik bebas oleh petugas di setiap shift, dan sebelum kolom
 * kemasan ada, kemasannya pun ikut diketik di kolom yang sama. Satu bahan yang
 * sama karena itu muncul dengan banyak ejaan: "Clay", "CLAY",
 * "Clay Jumbo Bag @ 1 Ton", "CLAY JUMBO 17%". Selama ejaan dipakai apa adanya
 * sebagai identitas, panel Rincian Kegiatan memecah tiga bahan menjadi puluhan
 * baris yang masing-masing hanya memuat sebagian tonasenya.
 *
 * Kelas ini memberi dua hal, seperti {@see ShipNameNormalizer} untuk nama kapal:
 *
 *   1. key()   — bentuk kanonik yang deterministik, dipakai sebagai kunci
 *                pengelompokan.
 *   2. label() — nama bahan tanpa keterangan kemasan dan kadar, dengan ejaan
 *                asli petugas dipertahankan.
 *
 * Nama yang tersimpan tidak pernah diubah. Kolom `raw_material_type` ikut
 * tercetak pada laporan PDF yang sudah disetujui, jadi yang diseragamkan hanya
 * pengelompokannya di layar.
 */
final class MaterialNameNormalizer
{
    /**
     * Kata yang menerangkan kemasan, bukan bahan. Dibuang lebih dulu supaya
     * "Clay Jumbo Bag @ 1 Ton" dan "CLAY" jatuh ke kelompok yang sama.
     *
     * @var array<int, string>
     */
    private const PACKAGING_WORDS = [
        'jumbo', 'bag', 'bags', 'kemasan', 'in', 'ton', 'tons', 'kg', 'per', 'jb',
    ];

    /**
     * Bentuk kanonik sebuah nama bahan. String kosong bila tidak ada nama yang
     * tersisa setelah keterangan kemasan dibuang.
     */
    public static function key(?string $name): string
    {
        return mb_strtolower(implode(' ', self::words($name)), 'UTF-8');
    }

    /**
     * Nama bahan untuk ditampilkan: keterangan kemasan dan kadar dibuang, ejaan
     * petugas dibiarkan apa adanya.
     *
     * Kadar ikut dibuang karena "MGO 18%" dan "Mgo 17%" adalah bahan yang sama
     * dengan mutu berbeda — pemisahnya bukan komposisi tonase. Angka aslinya
     * tetap utuh pada laporan hariannya.
     */
    public static function label(?string $name): string
    {
        return implode(' ', self::words($name));
    }

    /**
     * Kata yang benar-benar menamai bahan: bukan keterangan kemasan, bukan
     * angka, dan bukan tanda baca.
     *
     * @return array<int, string>
     */
    private static function words(?string $name): array
    {
        $text = str_replace(',', '.', (string) $name);
        $text = preg_replace('/[^\p{L}\p{N} ]+/u', ' ', $text) ?? '';
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(
            $words,
            static fn (string $word): bool => ! in_array(mb_strtolower($word, 'UTF-8'), self::PACKAGING_WORDS, true)
                && preg_match('/\p{N}/u', $word) !== 1
        ));
    }
}
