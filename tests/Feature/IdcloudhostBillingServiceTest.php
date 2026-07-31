<?php

namespace Tests\Feature;

use App\Models\IdcloudhostCreditSnapshot;
use App\Services\IdcloudhostBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IdcloudhostBillingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.idcloudhost.api_key' => 'test-token',
            'services.idcloudhost.billing_account_id' => '12345',
            'services.idcloudhost.base_url' => 'https://api.idcloudhost.test/v1',
            'services.idcloudhost.currency' => 'IDR',
            'services.idcloudhost.low_credit_threshold' => 100000,
            'services.idcloudhost.warning_days' => 7,
            'services.idcloudhost.critical_days' => 3,
            'services.idcloudhost.estimated_monthly_cost' => null,
        ]);
    }

    public function test_saldo_dan_estimasi_diambil_tanpa_membocorkan_token(): void
    {
        Http::fake([
            'https://api.idcloudhost.test/v1/payment/billing_account*' => Http::response([
                'id' => 12345,
                'title' => 'Produksi KSS',
                'credit_amount' => 900000,
                'running_totals' => [
                    'credit_available' => 900000,
                    'ongoing' => 850000,
                ],
                'is_active' => true,
            ]),
            'https://api.idcloudhost.test/v1/payment/credit/list*' => Http::response([
                ['amount' => -300000, 'description' => 'Invoice payment', 'created' => 300],
                ['amount' => -330000, 'description' => 'Invoice payment', 'created' => 200],
                ['amount' => 1000000, 'description' => 'Credit top up', 'created' => 100],
            ]),
        ]);

        $status = app(IdcloudhostBillingService::class)->dashboard(forceRefresh: true);

        $this->assertTrue($status['available']);
        $this->assertSame('Rp 850.000', $status['credit_formatted']);
        $this->assertSame('invoice_history', $status['estimate_source']);
        $this->assertGreaterThan(80, $status['remaining_days']);
        $this->assertSame('healthy', $status['level']);
        $this->assertArrayNotHasKey('api_key', $status);
        $this->assertDatabaseCount('idcloudhost_credit_snapshots', 1);

        Http::assertSent(fn ($request): bool => $request->hasHeader('apikey', 'test-token'));
    }

    public function test_peringatan_kritis_menggunakan_estimasi_biaya_bulanan_terkonfigurasi(): void
    {
        config(['services.idcloudhost.estimated_monthly_cost' => 300000]);

        Http::fake([
            '*' => Http::response([
                'running_totals' => ['credit_available' => 20000],
                'is_active' => true,
            ]),
        ]);

        $status = app(IdcloudhostBillingService::class)->dashboard(forceRefresh: true);

        $this->assertSame('critical', $status['level']);
        $this->assertSame('configured', $status['estimate_source']);
        $this->assertStringContainsString('Segera lakukan top up', $status['message']);
    }

    public function test_snapshot_terakhir_dipakai_saat_api_gagal(): void
    {
        IdcloudhostCreditSnapshot::create([
            'billing_account_id' => '12345',
            'credit_available' => 250000,
            'estimated_daily_cost' => 10000,
            'estimate_source' => 'invoice_history',
            'account_title' => 'Produksi KSS',
            'is_active' => true,
            'captured_at' => now()->subHour(),
        ]);

        Http::fake(['*' => Http::response(['message' => 'Unavailable'], 503)]);

        $status = app(IdcloudhostBillingService::class)->dashboard(forceRefresh: true);

        $this->assertTrue($status['available']);
        $this->assertTrue($status['is_stale']);
        $this->assertSame('Rp 250.000', $status['credit_formatted']);
    }

    public function test_halaman_billing_memisahkan_laporan_topup_dan_riwayat_saldo(): void
    {
        config(['services.idcloudhost.estimated_monthly_cost' => 300000]);

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/payment/billing_account')) {
                return Http::response([
                    'running_totals' => ['ongoing' => 500000],
                    'is_active' => true,
                ]);
            }

            if (str_contains($request->url(), '/payment/invoice/list')) {
                return Http::response([
                    [
                        'id' => 1205001337,
                        'status' => 10,
                        'period_start' => 1780272000,
                        'period_end' => 1782863999,
                        'totals' => ['total' => 0],
                        'records_list' => [['amount' => 95000]],
                    ],
                    [
                        'id' => 1204866770,
                        'status' => 10,
                        'period_start' => 1772878567,
                        'period_end' => 1772878567,
                        'totals' => ['total' => 954600],
                    ],
                ]);
            }

            if (str_contains($request->url(), '/payment/credit/list')) {
                return Http::response([
                    ['id' => 1, 'amount' => -95000, 'description' => 'Invoice payment', 'created' => 1782887381],
                    ['id' => 2, 'amount' => 860000, 'description' => 'Top up for account', 'created' => 1772878565],
                ]);
            }

            return Http::response([['description' => 'server', 'cost' => 3200, 'hours' => 24]]);
        });

        $page = app(IdcloudhostBillingService::class)->billingPage(forceRefresh: true);

        $this->assertCount(2, $page['reports']);
        $this->assertCount(1, $page['topup_invoices']);
        $this->assertCount(2, $page['balance_history']);
        $this->assertSame('Rp 3.200', $page['usage_total_formatted']);
        $this->assertSame('Pembayaran invoice', $page['balance_history'][0]['description']);
        $this->assertGreaterThan(0, $page['runway_percent']);
    }
}
