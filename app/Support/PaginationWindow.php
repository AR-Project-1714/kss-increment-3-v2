<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Jendela nomor halaman untuk paginasi bergaya "1 ... 15 16 17 18 19 ... 37"
 * — dipakai bersama oleh Arsip Laporan, Kelola Pengguna, Data Master, dan
 * riwayat Report-Ops, supaya paginasi dengan ratusan baris tidak menampilkan
 * seluruh nomor halaman sekaligus.
 *
 * Halaman pertama dan terakhir selalu terlihat sebagai pintasan lompat ke
 * ujung; di antaranya ada jendela sebanyak $windowSize halaman di sekitar
 * halaman aktif, dengan elipsis mengisi jarak yang terpotong.
 */
class PaginationWindow
{
    /**
     * @return array<int, array{type: string, page?: int}>
     */
    public static function build(LengthAwarePaginator $paginator, int $windowSize = 5): array
    {
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();

        if ($last <= 1) {
            return [['type' => 'page', 'page' => 1]];
        }

        // Hitung ujung jendela dari halaman aktif, lalu geser ke dalam batas
        // 1..$last. Regeser ulang $start dari $end (bukan hanya clamp $end)
        // supaya jendela tetap berisi $windowSize halaman penuh ketika berada
        // di dekat ujung awal atau akhir — bukan menyusut jadi 3 halaman saja.
        $half = intdiv($windowSize, 2);
        $start = max($current - $half, 1);
        $end = min($start + $windowSize - 1, $last);
        $start = max($end - $windowSize + 1, 1);

        $items = [];

        if ($start > 1) {
            $items[] = ['type' => 'page', 'page' => 1];

            if ($start > 2) {
                $items[] = ['type' => 'ellipsis'];
            }
        }

        for ($page = $start; $page <= $end; $page++) {
            $items[] = ['type' => 'page', 'page' => $page];
        }

        if ($end < $last) {
            if ($end < $last - 1) {
                $items[] = ['type' => 'ellipsis'];
            }

            $items[] = ['type' => 'page', 'page' => $last];
        }

        return $items;
    }
}
