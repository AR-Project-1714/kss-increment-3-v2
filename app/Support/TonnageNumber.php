<?php

namespace App\Support;

/**
 * Pembacaan angka tonase yang ditulis petugas.
 *
 * Petugas menulis angka dengan dua kebiasaan yang bercampur dalam satu berkas
 * laporan, dan keduanya memakai tanda titik:
 *
 *   "16.750"   → enam belas ribu tujuh ratus lima puluh   (titik = pemisah ribuan)
 *   "4420.25"  → empat ribu empat ratus dua puluh koma 25 (titik = koma desimal)
 *
 * Bukti bahwa keduanya memang bercampur ada pada laporan berurutan untuk kapal
 * yang sama: shift malam menulis "16.750" lalu shift pagi berikutnya menulis
 * "16750" untuk pembacaan COB yang sama persis. Begitu pula "19.400"/"19400"
 * dan "38.170"/"38170".
 *
 * Aturan pembacaannya:
 *
 *   1. Bila ada titik DAN koma, pemisah yang terakhir muncul adalah koma
 *      desimalnya — "4.420,25" maupun "4,420.25" sama-sama terbaca 4420,25.
 *   2. Bila hanya satu jenis pemisah dan muncul lebih dari sekali, ia pasti
 *      pemisah ribuan — "1.234.567".
 *   3. Bila muncul sekali dan diikuti TEPAT tiga angka, ia pemisah ribuan —
 *      "16.750" menjadi 16750. Pengelompokan tiga angka adalah ciri khas
 *      pemisah ribuan; koma desimal tonase praktis selalu satu atau dua angka.
 *   4. Selain itu ia koma desimal — "4420.25", "604.5".
 *
 * Batasannya jujur disebut: muatan yang benar-benar bernilai "0.500" ton akan
 * terbaca 500. Untuk muatan curah yang diukur ribuan ton, salah baca ke arah
 * itu jauh lebih kecil risikonya daripada membaca 16.750 ton menjadi 16,75 ton.
 */
final class TonnageNumber
{
    /**
     * Angka tonase dari teks yang diketik. null bila tidak ada angka sama sekali.
     */
    public static function parse(mixed $value): ?float
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        $text = preg_replace('/[^\d.,\-]/', '', $text) ?? '';

        if ($text === '' || ! preg_match('/\d/', $text)) {
            return null;
        }

        $negative = str_starts_with($text, '-');
        $text = ltrim($text, '-');

        $dot = strrpos($text, '.');
        $comma = strrpos($text, ',');

        if ($dot !== false && $comma !== false) {
            // Dua jenis pemisah: yang terakhir adalah koma desimalnya.
            $decimalAt = max($dot, $comma);
            $number = (float) (
                str_replace([',', '.'], '', substr($text, 0, $decimalAt))
                .'.'.preg_replace('/\D/', '', substr($text, $decimalAt + 1))
            );
        } else {
            $separator = $dot !== false ? '.' : ($comma !== false ? ',' : null);

            if ($separator === null) {
                $number = (float) $text;
            } else {
                $parts = explode($separator, $text);
                $last = end($parts);

                $number = count($parts) > 2 || strlen($last) === 3
                    ? (float) implode('', $parts)          // pemisah ribuan
                    : (float) (implode('', array_slice($parts, 0, -1)).'.'.$last);
            }
        }

        return $negative ? -$number : $number;
    }

    /**
     * Sama seperti parse(), tetapi nol dan nilai negatif dianggap "tidak ada
     * penimbangan". Dipakai untuk COB, yang nol-nya berarti kosong.
     */
    public static function reading(mixed $value): ?float
    {
        $number = self::parse($value);

        return $number !== null && $number > 0 ? round($number, 2) : null;
    }
}
