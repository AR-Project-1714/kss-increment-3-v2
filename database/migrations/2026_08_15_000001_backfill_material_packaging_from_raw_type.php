<?php

use App\Support\MaterialPackaging;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Kolom kemasan baru ada sejak 13 Agustus 2026. Sebelum itu petugas menuliskan
 * kemasannya menyatu dengan nama bahan — "MGO 18% Bag @50Kg", "Clay Jumbo Bag
 * 1 Ton" — sehingga pengisian data pada migrasi kemasan berkode hanya membaca
 * kolom `packaging_type` dan melewatkan seluruh baris itu.
 *
 * Faktor yang kosong dibaca rekap kinerja sebagai "isinya sudah Ton". Asumsi itu
 * benar untuk jumbo bag (1 Bag = 1 Ton), tetapi salah untuk bag berukuran
 * kilogram: 34.080 Bag MgO terbaca 34.080 Ton, dua puluh kali lipat dari 1.704
 * Ton yang sebenarnya, dan Bongkar Bahan Baku ikut membengkak menjadi 36.685 Ton
 * dari 4.309 Ton.
 *
 * `raw_material_type` sengaja tidak disentuh. Teks itu ikut tercetak pada
 * laporan PDF yang sudah disetujui, dan kadar di dalamnya ("17%", "18%") bukan
 * milik katalog kemasan — menyeragamkannya berarti menghapus keterangan yang
 * ditulis petugas, bukan memperbaiki angka.
 */
return new class extends Migration
{
    /**
     * Kata yang hanya menerangkan kemasan, bukan nama bahan. Dibuang sebelum
     * dua baris dibandingkan agar "Clay Jumbo Bag @ 1 Ton" dan "CLAY" dikenali
     * sebagai bahan yang sama.
     *
     * @var array<int, string>
     */
    private const PACKAGING_WORDS = ['jumbo', 'bag', 'bags', 'kemasan', 'in', 'ton', 'tons', 'kg', 'per'];

    /**
     * Panjang awalan terpendek yang boleh dianggap bahan yang sama. Cukup untuk
     * menutup salah ketik seperti "Limeston" tanpa melebur bahan yang memang
     * berbeda.
     */
    private const MIN_PREFIX = 4;

    /** Selisih yang masih dianggap faktor yang sama, mengikuti 4 angka desimal kolomnya. */
    private const FACTOR_EPSILON = 0.00005;

    public function up(): void
    {
        $report = $this->backfill();

        echo sprintf(
            "Kemasan bahan baku: %d baris diperbaiki, %d baris dilewati. Tonase %s -> %s.\n",
            count($report['resolved']),
            count($report['unresolved']),
            number_format($report['tonnage_before'], 2),
            number_format($report['tonnage_after'], 2)
        );
    }

    /**
     * Membalik pengisian ini berarti mengembalikan tonase yang salah, jadi
     * tidak dilakukan. Kolomnya sendiri tetap bisa dilepas dengan menurunkan
     * migrasi yang membuatnya.
     */
    public function down(): void
    {
        //
    }

    /**
     * Isi kode dan faktor kemasan baris yang kemasannya hanya tertulis pada nama
     * bahan. Dipisah dari up() supaya pengujian dan perintah
     * `material:repair-packaging` bisa memanggilnya apa adanya — yang terakhir
     * juga untuk melihat rencananya lebih dulu lewat $dryRun.
     *
     * @return array{
     *     resolved: array<int, array<string, mixed>>,
     *     unresolved: array<int, array<string, mixed>>,
     *     tonnage_before: float,
     *     tonnage_after: float
     * }
     */
    public function backfill(bool $dryRun = false): array
    {
        $rows = DB::table('material_items')
            ->join('material_activities', 'material_items.material_activity_id', '=', 'material_activities.id')
            ->whereNull('material_items.packaging_code')
            ->orderBy('material_items.id')
            ->get([
                'material_items.id',
                'material_items.raw_material_type',
                'material_items.qty_current',
                DB::raw("COALESCE(NULLIF(material_activities.ship_name_key, ''), NULLIF(material_activities.ship_name, ''), '') as ship_key"),
            ]);

        $packages = $this->readFromName($rows);
        $packages += $this->inheritFromSameShip($rows, $packages);

        $resolved = [];
        $unresolved = [];
        $before = 0.0;
        $after = 0.0;

        foreach ($rows as $row) {
            $quantity = (float) ($row->qty_current ?? 0);
            $before += $quantity;

            $package = $packages[$row->id] ?? null;

            if ($package === null) {
                // Baris pra-kemasan yang memang sudah dicatat dalam Ton jatuh ke
                // sini, dan justru tidak boleh diubah.
                $after += $quantity;
                $unresolved[] = ['id' => $row->id, 'raw_material_type' => $row->raw_material_type, 'qty_current' => $quantity];

                continue;
            }

            if (! $dryRun) {
                DB::table('material_items')->where('id', $row->id)->update([
                    'packaging_type' => $package['label'],
                    'packaging_code' => $package['code'],
                    'packaging_factor' => $package['tonPerBag'],
                ]);
            }

            $after += $quantity * $package['tonPerBag'];
            $resolved[] = [
                'id' => $row->id,
                'raw_material_type' => $row->raw_material_type,
                'code' => $package['code'],
                'factor' => $package['tonPerBag'],
                'qty_current' => $quantity,
                'before' => $quantity,
                'after' => $quantity * $package['tonPerBag'],
            ];
        }

        return [
            'resolved' => $resolved,
            'unresolved' => $unresolved,
            'tonnage_before' => $before,
            'tonnage_after' => $after,
        ];
    }

    /**
     * Langkah pertama: kemasan yang tertulis lengkap pada nama bahan.
     *
     * @param  Collection<int, object>  $rows
     * @return array<int, array{code: string, label: string, tonPerBag: float}>
     */
    private function readFromName(iterable $rows): array
    {
        $packages = [];

        foreach ($rows as $row) {
            $package = $this->sniff($row->raw_material_type);

            if ($package !== null) {
                $packages[$row->id] = $package;
            }
        }

        return $packages;
    }

    /**
     * Langkah kedua: baris yang namanya disingkat antar shift — "MGO", "Clay" —
     * menumpang pada baris sekapal dan sebahan yang kemasannya tertulis lengkap.
     * Deret akumulasinya memang satu rangkaian, jadi kemasannya pun sama.
     *
     * Ruang lingkupnya sengaja dikunci per kapal: satu bahan bisa saja dibongkar
     * dalam kemasan berbeda di kapal yang berbeda.
     *
     * @param  Collection<int, object>  $rows
     * @param  array<int, array{code: string, label: string, tonPerBag: float}>  $known
     * @return array<int, array{code: string, label: string, tonPerBag: float}>
     */
    private function inheritFromSameShip(iterable $rows, array $known): array
    {
        $catalog = [];

        foreach ($rows as $row) {
            $package = $known[$row->id] ?? null;
            $material = $this->materialKey($row->raw_material_type);

            if ($package === null || $material === '') {
                continue;
            }

            $catalog[$row->ship_key][$material][$package['code']] = $package;
        }

        $inherited = [];

        foreach ($rows as $row) {
            if (isset($known[$row->id])) {
                continue;
            }

            $package = $this->lookup($catalog[$row->ship_key] ?? [], $this->materialKey($row->raw_material_type));

            if ($package !== null) {
                $inherited[$row->id] = $package;
            }
        }

        return $inherited;
    }

    /**
     * Kemasan sebuah bahan menurut catatan sekapal. Bahan yang pernah tercatat
     * dalam dua kemasan berbeda dikembalikan sebagai null — tebakannya tidak
     * cukup kuat untuk menyentuh angka laporan.
     *
     * @param  array<string, array<string, array{code: string, label: string, tonPerBag: float}>>  $catalog
     * @return array{code: string, label: string, tonPerBag: float}|null
     */
    private function lookup(array $catalog, string $material): ?array
    {
        if ($material === '') {
            return null;
        }

        $matches = [];

        foreach ($catalog as $candidate => $packages) {
            if ($this->sameMaterial($candidate, $material)) {
                $matches += $packages;
            }
        }

        return count($matches) === 1 ? reset($matches) : null;
    }

    /**
     * Dua nama bahan dianggap sama bila salah satunya awalan yang lain, sehingga
     * "Limeston" tetap bertemu "Limestone".
     */
    private function sameMaterial(string $left, string $right): bool
    {
        if ($left === $right) {
            return true;
        }

        // Batas panjang hanya berlaku untuk pencocokan sebagian; nama sependek
        // "MgO" tetap harus bertemu dirinya sendiri.
        $shortest = min(mb_strlen($left), mb_strlen($right));

        if ($shortest < self::MIN_PREFIX) {
            return false;
        }

        return mb_substr($left, 0, $shortest) === mb_substr($right, 0, $shortest);
    }

    /**
     * Kemasan yang tersirat pada nama bahan. Ukuran kilogram diperiksa lebih
     * dulu karena namanya juga memuat kata "Bag", sedangkan jumbo bag tanpa
     * angka mengikuti ukuran bawaannya seperti alias "Jumbo Bag" di katalog.
     *
     * @return array{code: string, label: string, tonPerBag: float}|null
     */
    private function sniff(?string $name): ?array
    {
        $text = $this->normalize($name);

        if ($text === '') {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*kg/', $text, $match) === 1) {
            return $this->byFactor(((float) $match[1]) / 1000);
        }

        if (! str_contains($text, 'jumbo')) {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*ton/', $text, $match) === 1) {
            return $this->byFactor((float) $match[1]);
        }

        return MaterialPackaging::find(MaterialPackaging::CODE_JUMBO_1000);
    }

    /**
     * Kemasan katalog yang faktornya cocok. Ukuran di luar katalog dikembalikan
     * sebagai null supaya tidak ada faktor karangan yang masuk ke laporan.
     *
     * @return array{code: string, label: string, tonPerBag: float}|null
     */
    private function byFactor(float $tonPerBag): ?array
    {
        foreach (MaterialPackaging::all() as $package) {
            if (abs($package['tonPerBag'] - $tonPerBag) < self::FACTOR_EPSILON) {
                return $package;
            }
        }

        return null;
    }

    /** Nama bahan tanpa keterangan kemasan, kadar, dan tanda baca. */
    private function materialKey(?string $name): string
    {
        $text = preg_replace('/[^a-z0-9 ]+/', ' ', $this->normalize($name)) ?? '';
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $words = array_filter(
            $words,
            static fn (string $word): bool => ! in_array($word, self::PACKAGING_WORDS, true)
                && preg_match('/\d/', $word) !== 1
        );

        return implode(' ', $words);
    }

    /** Samakan bentuk teks: huruf kecil, spasi tunggal, desimal bertitik. */
    private function normalize(?string $text): string
    {
        $text = str_replace(',', '.', (string) $text);
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        return mb_strtolower($text, 'UTF-8');
    }
};
