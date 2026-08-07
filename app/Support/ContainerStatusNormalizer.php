<?php

namespace App\Support;

/**
 * Penyeragaman penanda Empty/Full pada baris bongkar-muat container.
 *
 * Satu tabel `container_items` menampung dua kegiatan sekaligus: Bongkar
 * Container (Empty) dan Muat Container (Full). Yang memisahkan keduanya hanya
 * kolom `status`. Selama kolom itu diisi teks bebas, satu maksud yang sama
 * ditulis dengan banyak ejaan — "Empty", "Container empty", "Empty Container",
 * "EMPYTY" — dan hanya ejaan yang persis sama dengan katalog yang ikut
 * terhitung. Sisanya tidak masuk Bongkar maupun Muat: hilang tanpa jejak.
 *
 * Kelas ini mengembalikan tepat tiga kemungkinan:
 *
 *   'Empty' — baris bongkar container kosong
 *   'Full'  — baris muat container isi
 *   null    — belum ditandai atau maksudnya tidak dapat dipastikan
 *
 * null bukan kegagalan yang disembunyikan. Baris ber-null sengaja dihitung
 * dan ditampilkan sebagai "belum ditandai" pada panel Rincian Kegiatan,
 * supaya angka yang belum berkategori tidak lagi lenyap diam-diam.
 *
 * Penerjemahannya berjenjang, dari penanda yang paling tegas ke yang paling
 * longgar. Bila dua penanda berlawanan muncul pada jenjang yang sama, hasilnya
 * null — menebak lebih berbahaya daripada mengakui tidak tahu.
 */
final class ContainerStatusNormalizer
{
    public const EMPTY_STATUS = 'Empty';

    public const FULL_STATUS = 'Full';

    /**
     * Ejaan "container" yang pernah ditemukan di lapangan. Bukan penanda
     * kategori — hanya kata latar yang mengapit penandanya — jadi dibuang
     * lebih dulu agar tidak ikut dinilai pencocokan mirip.
     */
    private const CONTAINER_WORDS = [
        'CONTAINER', 'CONTAINERS', 'CONTANER', 'CONTENER', 'COUTENER',
        'KONTAINER', 'KONTENER', 'KONTEINER', 'CNTR', 'CONT', 'BOX', 'TEUS',
    ];

    /**
     * Bentuk baku sebuah penanda, atau null bila tidak dapat dipastikan.
     */
    public static function normalize(?string $value): ?string
    {
        $tokens = self::tokenize($value);

        if ($tokens === []) {
            return null;
        }

        // Jenjang 1 — penanda tegas dalam bahasa Inggris, termasuk salah ketik
        // ringan seperti "EMPYTY". Inilah yang dipakai mayoritas laporan.
        $decision = self::decide(
            $tokens,
            static fn (string $token): bool => self::looksLike($token, 'EMPTY', 4, 2),
            static fn (string $token): bool => self::looksLike($token, 'FULL', 3, 1),
        );

        if ($decision !== null) {
            return $decision;
        }

        // Jenjang 2 — padanan bahasa Indonesia. Dicocokkan sebagai kata utuh:
        // "isi" sebagai potongan akan ikut menangkap "posisi" dan "kondisi".
        $decision = self::decide(
            $tokens,
            static fn (string $token): bool => str_starts_with($token, 'KOSONG'),
            static fn (string $token): bool => $token === 'ISI' || $token === 'ISIAN' || $token === 'BERISI',
        );

        if ($decision !== null) {
            return $decision;
        }

        // Jenjang 3 — nama kegiatannya. Di dermaga ini container dibongkar
        // dalam keadaan kosong dan dimuat dalam keadaan isi, jadi kata kerjanya
        // menyiratkan kategori. Sengaja ditaruh paling akhir karena ini
        // penyimpulan, bukan penanda yang ditulis operator dengan sadar.
        return self::decide(
            $tokens,
            static fn (string $token): bool => str_starts_with($token, 'BONGKAR'),
            static fn (string $token): bool => str_starts_with($token, 'MUAT'),
        );
    }

    /**
     * Apakah sebuah nilai sudah berbentuk baku. Dipakai perintah perapian data
     * untuk melewati baris yang memang tidak perlu disentuh.
     */
    public static function isCanonical(?string $value): bool
    {
        return $value === self::EMPTY_STATUS || $value === self::FULL_STATUS;
    }

    /**
     * Pilih kategori dari sepasang pengenal. Penanda berlawanan yang muncul
     * bersamaan menghasilkan null, bukan salah satunya.
     *
     * @param  array<int, string>  $tokens
     * @param  callable(string): bool  $isEmpty
     * @param  callable(string): bool  $isFull
     */
    private static function decide(array $tokens, callable $isEmpty, callable $isFull): ?string
    {
        $empty = false;
        $full = false;

        foreach ($tokens as $token) {
            $empty = $empty || $isEmpty($token);
            $full = $full || $isFull($token);
        }

        if ($empty && $full) {
            return null;
        }

        if ($empty) {
            return self::EMPTY_STATUS;
        }

        return $full ? self::FULL_STATUS : null;
    }

    /**
     * Pecah isian menjadi kata-kata huruf besar tanpa tanda baca, lalu buang
     * kata latar "container" beserta ejaan kelirunya.
     *
     * @return array<int, string>
     */
    private static function tokenize(?string $value): array
    {
        $text = trim((string) $value);

        if ($text === '') {
            return [];
        }

        $text = mb_strtoupper($text, 'UTF-8');
        $text = preg_replace('/[^A-Z0-9]+/u', ' ', $text) ?? '';

        $tokens = array_filter(
            explode(' ', $text),
            static fn (string $token): bool => $token !== '' && ! in_array($token, self::CONTAINER_WORDS, true)
        );

        return array_values($tokens);
    }

    /**
     * Apakah satu kata adalah bentuk (atau salah ketik ringan) dari kata acuan.
     *
     * Panjang minimum menjaga kata pendek yang kebetulan mirip agar tidak ikut
     * lolos; toleransi jarak dipisah per acuan karena "EMPTY" jauh lebih sering
     * salah ketik daripada "FULL" — "EMPYTY" dan "EMTPY" keduanya perlu lolos.
     *
     * Toleransi dua huruf memang cukup longgar untuk ikut menangkap kata lain
     * yang kebetulan berjarak dekat. Itu diterima karena penerjemahan massal
     * hanya dijalankan lewat `container:repair-status`, yang wajib ditinjau
     * lewat --dry-run lebih dulu, sedangkan input baru sudah dibatasi dropdown.
     */
    private static function looksLike(string $token, string $reference, int $minLength, int $tolerance): bool
    {
        if ($token === $reference) {
            return true;
        }

        if (mb_strlen($token) < $minLength) {
            return false;
        }

        // Selisih panjang yang sudah melebihi toleransi tidak mungkin tertutup
        // oleh levenshtein(); memeriksanya lebih dulu sekaligus mencegah kata
        // panjang yang tidak berkaitan ikut dihitung.
        if (abs(mb_strlen($token) - mb_strlen($reference)) > $tolerance) {
            return false;
        }

        return levenshtein($token, $reference) <= $tolerance;
    }
}
