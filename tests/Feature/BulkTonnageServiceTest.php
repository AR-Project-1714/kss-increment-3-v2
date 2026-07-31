<?php

namespace Tests\Feature;

use App\Models\BulkLoadingActivity;
use App\Models\BulkLoadingLog;
use App\Models\DailyReport;
use App\Models\ShipOperation;
use App\Services\BulkTonnageService;
use App\Support\ShipNameNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkTonnageServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Inti perbaikan: COB adalah pembacaan kumulatif, jadi tonase satu
     * pelayaran adalah pembacaan terakhirnya — bukan jumlah seluruh baris.
     */
    public function test_tonase_satu_pelayaran_sama_dengan_pembacaan_cob_terakhir(): void
    {
        $this->voyage('KM. Contoh', 9000, [
            ['2026-07-10', 'Pagi', [200]],
            ['2026-07-10', 'Sore', [970, 2140]],
            ['2026-07-10', 'Malam', [3540, 4540]],
            ['2026-07-11', 'Pagi', [5780, 6040]],
            ['2026-07-11', 'Sore', [7210, 8350]],
        ]);

        app(BulkTonnageService::class)->recalculate();

        // Cara lama menjumlahkan sembilan pembacaan odometer dan menghasilkan
        // 38.770 ton untuk kapal berkapasitas 9.000 ton.
        $this->assertSame(38_770.0, (float) BulkLoadingLog::sum('cob'));
        $this->assertSame(8_350.0, (float) BulkLoadingLog::sum('cob_delta'));
    }

    /**
     * Jumlah tonase tiap shift harus sama dengan total pelayarannya, supaya
     * angka per regu dan per shift tetap bisa dipotong dari kolom yang sama.
     */
    public function test_selisih_terbagi_ke_shift_yang_menyebabkannya(): void
    {
        $this->voyage('KM. Contoh', 9000, [
            ['2026-07-10', 'Pagi', [1000]],
            ['2026-07-10', 'Sore', [2500]],
            ['2026-07-10', 'Malam', [4000]],
        ]);

        app(BulkTonnageService::class)->recalculate();

        $perShift = DailyReport::query()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (DailyReport $report): array => [
                $report->shift => (float) BulkLoadingLog::query()
                    ->whereIn(
                        'bulk_loading_activity_id',
                        BulkLoadingActivity::where('daily_report_id', $report->id)->pluck('id')
                    )
                    ->sum('cob_delta'),
            ]);

        $this->assertSame(['Pagi' => 1_000.0, 'Sore' => 1_500.0, 'Malam' => 1_500.0], $perShift->all());
        $this->assertSame(4_000.0, $perShift->sum());
    }

    public function test_pembacaan_kosong_dan_nol_diabaikan_bukan_dianggap_muatan_turun(): void
    {
        $this->voyage('KM. Contoh', 9000, [
            ['2026-07-10', 'Pagi', [1000, null, 0]],
            ['2026-07-10', 'Sore', [0, 2500]],
        ]);

        app(BulkTonnageService::class)->recalculate();

        $this->assertSame(2_500.0, (float) BulkLoadingLog::sum('cob_delta'));
        $this->assertSame(0, BulkLoadingLog::where('cob_delta', '<', 0)->count());
    }

    /**
     * Draft survey ulang sering menurunkan angka sedikit. Itu koreksi, bukan
     * muatan yang dibongkar, dan tidak boleh menambah maupun mengurangi tonase.
     */
    public function test_koreksi_kecil_ke_bawah_tidak_menambah_tonase(): void
    {
        $this->voyage('KM. Contoh', 40000, [
            ['2026-07-10', 'Pagi', [4863]],
            ['2026-07-10', 'Sore', [4280, 8630]],
        ]);

        app(BulkTonnageService::class)->recalculate();

        $this->assertSame(8_630.0, (float) BulkLoadingLog::sum('cob_delta'));
    }

    /**
     * Sebagian shift menulis COB dalam ribuan ton ("16.75" untuk 16.750).
     * Nilai itu dikembalikan ke ton penuh selama hasilnya melanjutkan
     * pembacaan sebelumnya dan masih masuk kapasitas kapal.
     */
    public function test_pembacaan_dalam_ribuan_ton_dikembalikan_ke_ton_penuh(): void
    {
        $this->voyage('KM. Contoh', 40000, [
            ['2026-07-10', 'Pagi', [15000]],
            ['2026-07-10', 'Sore', [16.75]],
            ['2026-07-10', 'Malam', [19.4, 20280]],
        ]);

        app(BulkTonnageService::class)->recalculate();

        $this->assertSame(
            [15000.0, 16750.0, 19400.0, 20280.0],
            BulkLoadingLog::orderBy('id')->pluck('cob_normalized')->map(fn ($v): float => (float) $v)->all(),
        );
        $this->assertSame(20_280.0, (float) BulkLoadingLog::sum('cob_delta'));
    }

    /**
     * Kapal kecil yang memang bermuatan ratusan ton tidak boleh ikut dikali
     * seribu hanya karena angkanya kecil.
     */
    public function test_angka_kecil_pada_kapal_kecil_tidak_dikali_seribu(): void
    {
        $this->voyage('KM. Kecil', 600, [
            ['2026-07-10', 'Pagi', [120, 240]],
            ['2026-07-10', 'Sore', [480, 575]],
        ]);

        app(BulkTonnageService::class)->recalculate();

        $this->assertSame(575.0, (float) BulkLoadingLog::sum('cob_delta'));
    }

    /**
     * Kapal yang sama datang lagi bulan berikutnya: COB mulai dari nol, dan
     * pelayaran keduanya dihitung terpisah, bukan diabaikan karena angkanya
     * lebih kecil daripada pelayaran sebelumnya.
     */
    public function test_kunjungan_berikutnya_dihitung_sebagai_pelayaran_baru(): void
    {
        $this->voyage('KM. Contoh', 9000, [
            ['2026-07-01', 'Pagi', [4000, 8500]],
            ['2026-07-20', 'Pagi', [1500, 6000]],
        ]);

        app(BulkTonnageService::class)->recalculate();

        $this->assertSame(14_500.0, (float) BulkLoadingLog::sum('cob_delta'));
    }

    /**
     * Pembacaan yang jauh melampaui kapasitas kapal adalah salah ketik, bukan
     * tonase. Dicatat pada kolom normalisasi supaya bisa ditelusuri, tetapi
     * tidak boleh masuk hitungan.
     */
    public function test_pembacaan_di_luar_kewajaran_tidak_menambah_tonase(): void
    {
        $this->voyage('KM. Contoh', 9000, [
            ['2026-07-10', 'Pagi', [3000, 900000, 4000]],
        ]);

        app(BulkTonnageService::class)->recalculate();

        $this->assertSame(4_000.0, (float) BulkLoadingLog::sum('cob_delta'));
    }

    /**
     * Laporan yang masih draft belum boleh ikut merangkai pelayaran, karena
     * angkanya masih bisa berubah dan statistik manajer pun tidak menghitungnya.
     */
    public function test_draft_tidak_ikut_dirangkai(): void
    {
        $this->voyage('KM. Contoh', 9000, [
            ['2026-07-10', 'Pagi', [1000]],
            ['2026-07-10', 'Sore', [5000], 'draft'],
            ['2026-07-10', 'Malam', [2000]],
        ]);

        app(BulkTonnageService::class)->recalculate();

        $this->assertSame(2_000.0, (float) BulkLoadingLog::sum('cob_delta'));
    }

    public function test_hitung_ulang_bersifat_idempoten(): void
    {
        $this->voyage('KM. Contoh', 9000, [
            ['2026-07-10', 'Pagi', [1000, 2000]],
            ['2026-07-10', 'Sore', [3000]],
        ]);

        $service = app(BulkTonnageService::class);
        $service->recalculate();
        $first = BulkLoadingLog::orderBy('id')->pluck('cob_delta')->all();

        $service->recalculate();

        $this->assertSame($first, BulkLoadingLog::orderBy('id')->pluck('cob_delta')->all());
    }

    /**
     * Satu pelayaran yang namanya diketik berbeda tiap shift tetap terangkai,
     * asalkan barisnya menunjuk operasi kapal yang sama.
     *
     * @param  array<int, array{0: string, 1: string, 2: array<int, float|null>, 3?: string}>  $shifts
     */
    private function voyage(string $shipName, float $capacity, array $shifts): void
    {
        $shipOperationId = ShipOperation::create([
            'type' => ShipOperation::TYPE_BULK_LOADING,
            'status' => ShipOperation::STATUS_ACTIVE,
            'ship_name' => $shipName,
            'ship_name_key' => ShipNameNormalizer::key($shipName),
            'capacity' => $capacity,
        ])->id;

        foreach ($shifts as $shift) {
            [$date, $shiftName, $readings] = $shift;
            $status = $shift[3] ?? 'approved';

            $report = DailyReport::create([
                'report_date' => $date,
                'shift' => $shiftName,
                'group_name' => 'A',
                'status' => $status,
            ]);

            $activity = BulkLoadingActivity::create([
                'daily_report_id' => $report->id,
                'ship_operation_id' => $shipOperationId,
                'activity_type' => BulkLoadingActivity::TYPE_BULK_LOADING,
                'sequence' => 1,
                'ship_name' => $shipName,
                'ship_name_key' => ShipNameNormalizer::key($shipName),
                'capacity' => $capacity,
                'berthing_time' => $date.' 06:00:00',
            ]);

            foreach ($readings as $reading) {
                $activity->logs()->create([
                    'datetime' => $date.' 09:00:00',
                    'activity' => 'Pemuatan',
                    'cob' => $reading,
                ]);
            }
        }
    }
}
