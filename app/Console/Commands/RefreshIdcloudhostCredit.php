<?php

namespace App\Console\Commands;

use App\Services\IdcloudhostBillingService;
use Illuminate\Console\Command;

class RefreshIdcloudhostCredit extends Command
{
    protected $signature = 'idcloudhost:refresh-credit
                            {--inspect : Tampilkan hanya field saldo aman dari response API}
                            {--inspect-data : Tampilkan struktur aman laporan, invoice, dan riwayat saldo}
                            {--warm-details : Perbarui cache laporan, invoice top up, dan riwayat saldo}';

    protected $description = 'Perbarui snapshot saldo dan estimasi masa aktif IDCloudHost';

    public function handle(IdcloudhostBillingService $billing): int
    {
        if (! $billing->isConfigured()) {
            $this->warn('Integrasi IDCloudHost belum dikonfigurasi.');

            return self::SUCCESS;
        }

        if ($this->option('inspect')) {
            $this->table(
                ['Field', 'Nilai'],
                collect($billing->inspectBalanceFields())
                    ->map(fn (?float $value, string $field): array => [
                        $field,
                        $value === null ? 'null' : number_format($value, 4, '.', ''),
                    ])
                    ->values()
                    ->all()
            );

            return self::SUCCESS;
        }

        if ($this->option('inspect-data')) {
            $this->line(json_encode(
                $billing->inspectBillingCollections(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ));

            return self::SUCCESS;
        }

        if ($this->option('warm-details')) {
            $page = $billing->billingPage(forceRefresh: true);

            if (! $page['available']) {
                $this->error($page['message']);

                return self::FAILURE;
            }

            $this->info(sprintf(
                'Cache billing diperbarui: %d laporan, %d invoice top up, %d transaksi saldo.',
                count($page['reports']),
                count($page['topup_invoices']),
                count($page['balance_history'])
            ));

            foreach ($page['partial_errors'] as $error) {
                $this->warn($error);
            }

            return self::SUCCESS;
        }

        $status = $billing->dashboard(forceRefresh: true);

        if (! $status['available']) {
            $this->error($status['message']);

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Saldo IDCloudHost %s; estimasi masa aktif %s.',
            $status['credit_formatted'],
            $status['remaining_label']
        ));

        return self::SUCCESS;
    }
}
