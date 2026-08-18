<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\MaterialActivity;
use App\Models\MaterialItem;
use App\Models\ShipOperation;
use App\Services\OperationalPerformanceService;
use App\Support\ShipNameNormalizer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Jumlah kapal pada Kinerja Operasi dan Rincian Kegiatan harus sama.
 *
 * Keduanya menjawab pertanyaan yang sama untuk periode yang sama, tetapi
 * dihitung lewat dua jalur berbeda: kartu rekap memakai COUNT(DISTINCT penanda
 * kunjungan) atas tabel rincian, sedangkan panel rincian mengelompokkan tabel
 * induk menurut penanda yang sama. Tiga hal pernah membuat keduanya berselisih
 * dan dikunci di sini supaya tidak kembali.
 */
class ShipCountConsistencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: int, 1: int} [kartu Kinerja Operasi, metrik Rincian Kegiatan]
     */
    private function shipCounts(array $filters): array
    {
        $service = app(OperationalPerformanceService::class);

        $recap = $service->activityRecap($filters, ['bongkar_bahan_baku']);
        $card = (int) collect($recap['rows'])->firstWhere('key', 'bongkar_bahan_baku')['total']['count'];

        $detail = $service->activityDetail('bongkar_bahan_baku', $filters);
        $metric = collect($detail['metrics'])->firstWhere('label', 'Kapal dilayani')['value'] ?? null;

        return [$card, (int) $metric];
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(string $start, string $end): array
    {
        return [
            'start' => Carbon::parse($start),
            'end' => Carbon::parse($end),
            'group' => null,
            'shift' => null,
        ];
    }

    private function material(string $date, ?string $ship, ?int $operationId, ?float $qty, string $shift = 'Pagi'): MaterialActivity
    {
        $report = DailyReport::create([
            'report_date' => $date,
            'shift' => $shift,
            'group_name' => 'A',
            'status' => 'approved',
        ]);

        $activity = MaterialActivity::create([
            'daily_report_id' => $report->id,
            'sequence' => 1,
            'ship_operation_id' => $operationId,
            'ship_name' => $ship,
            'ship_name_key' => $ship === null ? null : (ShipNameNormalizer::key($ship) ?: null),
            'capacity' => 8000,
        ]);

        if ($qty !== null) {
            MaterialItem::create([
                'material_activity_id' => $activity->id,
                'raw_material_type' => 'Clay',
                'qty_current' => $qty,
            ]);
        }

        return $activity;
    }

    /**
     * Rekap memecah periode menjadi bulan berjalan dan bulan-bulan sebelumnya.
     * Tonase boleh dijumlahkan dari kedua segmen karena aditif, tetapi jumlah
     * kapal tidak: satu pelayaran yang bersandar melewati pergantian bulan
     * muncul pada kedua segmen dan penjumlahannya melaporkan dua kapal.
     */
    public function test_kapal_yang_melintasi_pergantian_bulan_tidak_dihitung_dua_kali(): void
    {
        $operation = ShipOperation::create([
            'type' => ShipOperation::TYPE_MATERIAL_UNLOADING,
            'status' => ShipOperation::STATUS_ACTIVE,
            'ship_name' => 'KM. Hasil Bahari 8',
            'ship_name_key' => ShipNameNormalizer::key('KM. Hasil Bahari 8'),
            'capacity' => 8000,
        ]);

        $this->material('2026-07-30', 'KM. Hasil Bahari 8', $operation->id, 100);
        $this->material('2026-08-02', 'KM.HASIL BAHARI 8', $operation->id, 150, 'Sore');

        [$card, $metric] = $this->shipCounts($this->filters('2026-01-01', '2026-08-18'));

        $this->assertSame(1, $card, 'Kartu rekap menggandakan satu pelayaran yang melintasi pergantian bulan.');
        $this->assertSame(1, $metric);
    }

    /**
     * Kegiatan yang tersimpan tanpa satu pun baris rincian belum menjadi
     * pembongkaran. Rekap memang tidak melihatnya karena berangkat dari tabel
     * rincian; panel rincian dahulu tetap menghitungnya sebagai kapal.
     */
    public function test_kegiatan_tanpa_baris_rincian_tidak_dihitung_sebagai_kapal(): void
    {
        $this->material('2026-08-05', 'KM. Sinar Jaya', null, 500);
        $this->material('2026-08-06', 'KM. Bahari Indah', null, null, 'Sore');

        [$card, $metric] = $this->shipCounts($this->filters('2026-08-01', '2026-08-18'));

        $this->assertSame(1, $card);
        $this->assertSame(1, $metric, 'Panel rincian masih menghitung kegiatan tanpa baris rincian sebagai kapal.');
    }

    /**
     * Blok bahan baku yang terkirim tanpa nama kapal maupun operasi kapal tidak
     * punya identitas apa pun. COUNT(DISTINCT) memang melewatinya, tetapi
     * GROUP BY tetap mengumpulkannya menjadi satu kelompok "Nama kapal belum
     * diisi" yang dahulu ikut terhitung sebagai satu kapal.
     */
    public function test_blok_tanpa_nama_kapal_tidak_dihitung_sebagai_kapal(): void
    {
        $this->material('2026-08-05', 'KM. Sinar Jaya', null, 500);
        $this->material('2026-08-06', null, null, 0, 'Sore');
        $this->material('2026-08-07', null, null, 0, 'Malam');

        [$card, $metric] = $this->shipCounts($this->filters('2026-08-01', '2026-08-18'));

        $this->assertSame(1, $card);
        $this->assertSame(1, $metric, 'Kelompok tanpa nama kapal masih terhitung sebagai kapal.');
    }

    /**
     * Bentuk data produksi: satu kapal fisik yang tercatat lewat beberapa
     * operasi kapal sekaligus beberapa laporan tanpa operasi. Angkanya boleh
     * saja lebih dari satu — yang wajib adalah kedua halaman menyebut angka
     * yang sama.
     */
    public function test_kedua_halaman_sama_pada_bentuk_data_produksi(): void
    {
        $operations = collect(range(1, 3))->map(fn (): ShipOperation => ShipOperation::create([
            'type' => ShipOperation::TYPE_MATERIAL_UNLOADING,
            'status' => ShipOperation::STATUS_ACTIVE,
            'ship_name' => 'KM. Hasil Bahari 8',
            'ship_name_key' => ShipNameNormalizer::key('KM. Hasil Bahari 8'),
            'capacity' => 8000,
        ]));

        $this->material('2026-08-08', 'KM.HASIL BAHARI 8', null, null, 'Malam');
        $this->material('2026-08-09', 'KM.HASIL BAHARI 8', $operations[0]->id, 20);
        $this->material('2026-08-10', 'KM. HASIL BAHARI 8', $operations[0]->id, 300, 'Sore');
        $this->material('2026-08-12', 'KM.HASIL BAHARI.8', $operations[1]->id, 100, 'Malam');
        $this->material('2026-08-12', 'KM.HASIL BAHARI.8', $operations[2]->id, 140);
        $this->material('2026-08-13', 'KM. Hasil Bahari 8', null, 129, 'Sore');

        [$card, $metric] = $this->shipCounts($this->filters('2026-01-01', '2026-08-18'));

        $this->assertSame($card, $metric, 'Kinerja Operasi dan Rincian Kegiatan menyebut jumlah kapal yang berbeda.');
        $this->assertSame(4, $card);
    }
}
