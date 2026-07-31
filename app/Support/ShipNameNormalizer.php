<?php

namespace App\Support;

/**
 * Penyeragaman nama kapal.
 *
 * Nama kapal diketik bebas oleh petugas di setiap shift, sehingga satu kapal
 * yang sama muncul dengan banyak ejaan: "KLM. Sumber Utama Keluarga",
 * "sumber utama keluarga", "SUMBER UTAMA KELUARGA", "Sumber Utama K", "SUK".
 * Selama ejaan dipakai apa adanya sebagai identitas, satu pelayaran terpecah
 * menjadi beberapa operasi kapal dan akumulasi muatnya ikut terpecah.
 *
 * Kelas ini memberi dua hal:
 *
 *   1. key()   — bentuk kanonik yang deterministik (huruf besar, tanpa tanda
 *                baca, tanpa awalan jenis kapal). Dipakai sebagai kunci
 *                pencocokan utama dan disimpan di kolom ship_name_key.
 *   2. score() — kemiripan 0..1 untuk kasus yang tidak bisa diselesaikan
 *                kunci kanonik: singkatan ("SUK"), nama terpotong
 *                ("Sumber Utama K"), dan salah ketik satu-dua huruf.
 *
 * Nama tampilan tidak pernah diubah. Yang diseragamkan hanya identitasnya.
 */
final class ShipNameNormalizer
{
    /**
     * Ambang kemiripan agar dua nama dianggap kapal yang sama.
     *
     * Sengaja tinggi karena pencocokan mirip hanya boleh menjadi jaring
     * pengaman terakhir; pemanggilnya wajib mempersempit kandidat lebih dulu
     * ke pelayaran yang memang sedang berlangsung.
     */
    public const MATCH_THRESHOLD = 0.82;

    /**
     * Awalan jenis kapal yang dibuang dari kunci kanonik. Bukan bagian dari
     * nama kapal, dan justru paling sering ditulis tidak konsisten
     * ("KM." / "KM" / "Km.").
     */
    private const VESSEL_PREFIXES = [
        'KM', 'KLM', 'KMP', 'MV', 'MS', 'MT', 'SS', 'TB', 'TK', 'KT', 'LCT',
        'SPOB', 'SPB', 'OB', 'BG', 'LPG', 'LNG', 'AHTS', 'TUG', 'KAPAL',
    ];

    /** Kata jenis muatan/pengelola yang kadang ikut diketik di belakang nama. */
    private const NOISE_SUFFIXES = ['EX', 'EKS'];

    /**
     * Bentuk kanonik sebuah nama kapal. String kosong bila tidak ada nama.
     */
    public static function key(?string $name): string
    {
        $value = trim((string) $name);

        if ($value === '') {
            return '';
        }

        // Tanda baca ("KM.", "LPG/C", "Km-Malacca") dan huruf kecil adalah dua
        // sumber perbedaan yang paling sering; keduanya diratakan lebih dulu.
        $value = mb_strtoupper($value, 'UTF-8');
        $value = preg_replace('/[^A-Z0-9]+/u', ' ', $value) ?? '';

        $tokens = array_values(array_filter(explode(' ', $value), static fn (string $token): bool => $token !== ''));

        if ($tokens === []) {
            return '';
        }

        $tokens = self::stripVesselPrefixes($tokens);

        while ($tokens !== [] && in_array(end($tokens), self::NOISE_SUFFIXES, true)) {
            array_pop($tokens);
        }

        return implode(' ', $tokens);
    }

    /**
     * Huruf awal tiap kata pada kunci kanonik — pembanding untuk singkatan
     * seperti "SUK" bagi "SUMBER UTAMA KELUARGA".
     */
    public static function initials(string $key): string
    {
        $tokens = array_filter(explode(' ', $key), static fn (string $token): bool => $token !== '');

        if (count($tokens) < 2) {
            return '';
        }

        return implode('', array_map(static fn (string $token): string => mb_substr($token, 0, 1), $tokens));
    }

    /**
     * Seberapa besar kemungkinan dua nama menunjuk kapal yang sama (0..1).
     *
     * Nilainya berjenjang, dari yang paling meyakinkan ke yang paling longgar,
     * supaya alasan sebuah pasangan dianggap cocok bisa ditelusuri kembali.
     */
    public static function score(?string $left, ?string $right): float
    {
        $a = self::key($left);
        $b = self::key($right);

        if ($a === '' || $b === '') {
            return 0.0;
        }

        if ($a === $b) {
            return 1.0;
        }

        // "SUK" vs "SUMBER UTAMA KELUARGA".
        if (self::initials($a) === $b || self::initials($b) === $a) {
            return 0.95;
        }

        // "SUMBER UTAMA K" vs "SUMBER UTAMA KELUARGA": kata-kata awal sama
        // persis dan kata terakhir yang lebih pendek adalah awalan dari
        // pasangannya. Nama yang dipotong di tengah pengetikan tertangkap di
        // sini, tanpa ikut meloloskan nama yang hanya berbagi satu kata.
        if (self::isTruncationOf($a, $b) || self::isTruncationOf($b, $a)) {
            return 0.9;
        }

        return round((self::tokenOverlap($a, $b) + self::characterSimilarity($a, $b)) / 2, 4);
    }

    /**
     * Apakah dua nama cukup mirip untuk dianggap kapal yang sama.
     */
    public static function isSameShip(?string $left, ?string $right): bool
    {
        return self::score($left, $right) >= self::MATCH_THRESHOLD;
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private static function stripVesselPrefixes(array $tokens): array
    {
        $stripped = 0;

        while (count($tokens) > 1) {
            $head = $tokens[0];

            if (in_array($head, self::VESSEL_PREFIXES, true)) {
                array_shift($tokens);
                $stripped++;

                continue;
            }

            // Huruf tunggal hanya dibuang bila menempel pada awalan yang sudah
            // dibuang — "LPG/C" menjadi ["LPG", "C"] — supaya kapal yang
            // namanya memang diawali satu huruf tidak ikut terpangkas.
            if ($stripped > 0 && mb_strlen($head) === 1 && ! ctype_digit($head)) {
                array_shift($tokens);
                $stripped++;

                continue;
            }

            break;
        }

        return $tokens;
    }

    private static function isTruncationOf(string $short, string $long): bool
    {
        $shortTokens = explode(' ', $short);
        $longTokens = explode(' ', $long);

        if (count($shortTokens) < 2 || count($shortTokens) > count($longTokens)) {
            return false;
        }

        // Kapal kembar dibedakan justru oleh angka di belakang namanya
        // ("Sinar Sulawesi" dan "Sinar Sulawesi 8" adalah dua kapal). Selisih
        // yang seluruhnya berupa angka karena itu bukan nama terpotong.
        $extra = array_slice($longTokens, count($shortTokens));

        if ($extra !== [] && array_filter($extra, static fn (string $token): bool => ! self::isNumeric($token)) === []) {
            return false;
        }

        $last = count($shortTokens) - 1;

        foreach ($shortTokens as $index => $token) {
            if ($index < $last) {
                if ($token !== $longTokens[$index]) {
                    return false;
                }

                continue;
            }

            if (! str_starts_with($longTokens[$index], $token)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Irisan kata yang toleran terhadap salah ketik: "REJEKI" dan "REZEKI"
     * dihitung sebagai kata yang sama, sedangkan "STRAIT" dan "STAR" tidak.
     * Tanpa toleransi ini satu huruf keliru sudah cukup membuat dua catatan
     * kapal yang sama tampak seperti dua kapal berbeda.
     */
    private static function tokenOverlap(string $a, string $b): float
    {
        $left = array_values(array_unique(explode(' ', $a)));
        $right = array_values(array_unique(explode(' ', $b)));

        if ($left === [] || $right === []) {
            return 0.0;
        }

        $matched = 0;
        $available = $right;

        foreach ($left as $token) {
            foreach ($available as $index => $candidate) {
                if ($token === $candidate || self::characterSimilarity($token, $candidate) >= 0.8) {
                    $matched++;
                    unset($available[$index]);

                    break;
                }
            }
        }

        // Jaccard dengan pembagi yang ikut memperhitungkan pasangan mirip:
        // "REJEKI" dan "REZEKI" menyumbang satu kata, bukan dua.
        return $matched / (count($left) + count($right) - $matched);
    }

    private static function isNumeric(string $token): bool
    {
        return ctype_digit($token) || preg_match('/^[IVXLC]+$/', $token) === 1;
    }

    private static function characterSimilarity(string $a, string $b): float
    {
        $length = max(mb_strlen($a), mb_strlen($b));

        if ($length === 0) {
            return 0.0;
        }

        // levenshtein() hanya menerima string sampai 255 karakter; nama kapal
        // jauh lebih pendek, tetapi pemotongan tetap dipasang sebagai pengaman.
        $distance = levenshtein(mb_substr($a, 0, 255), mb_substr($b, 0, 255));

        return max(0.0, 1 - ($distance / $length));
    }
}
