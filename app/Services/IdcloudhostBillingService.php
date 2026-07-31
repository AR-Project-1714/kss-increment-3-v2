<?php

namespace App\Services;

use App\Models\IdcloudhostCreditSnapshot;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class IdcloudhostBillingService
{
    private const CACHE_KEY_PREFIX = 'idcloudhost.billing.';
    private const DETAIL_CACHE_KEY_PREFIX = 'idcloudhost.billing.detail.';

    /**
     * Data siap-presentasi untuk dashboard. Kegagalan API tidak boleh membuat
     * dashboard admin gagal dimuat; snapshot terakhir dipakai sebagai fallback.
     */
    public function dashboard(bool $forceRefresh = false): array
    {
        if (! $this->isConfigured()) {
            return $this->unavailable(
                'not_configured',
                'Tambahkan API key dan Billing Account ID IDCloudHost pada konfigurasi server.'
            );
        }

        $accountId = (string) config('services.idcloudhost.billing_account_id');
        $cacheKey = self::CACHE_KEY_PREFIX.$accountId;

        try {
            if ($forceRefresh) {
                Cache::forget($cacheKey);
            }

            $seconds = max(60, (int) config('services.idcloudhost.cache_seconds', 900));
            $data = Cache::remember(
                $cacheKey,
                now()->addSeconds($seconds),
                fn (): array => $this->fetchAndStore()
            );

            return $this->present($data);
        } catch (Throwable $exception) {
            Log::warning('Gagal mengambil saldo IDCloudHost.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'billing_account_id' => $accountId,
            ]);

            $snapshot = $this->latestSnapshot($accountId);

            if ($snapshot) {
                return $this->present([
                    'billing_account_id' => $snapshot->billing_account_id,
                    'account_title' => $snapshot->account_title,
                    'credit_available' => $snapshot->credit_available,
                    'estimated_daily_cost' => $snapshot->estimated_daily_cost,
                    'estimate_source' => $snapshot->estimate_source,
                    'is_active' => $snapshot->is_active,
                    'captured_at' => $snapshot->captured_at,
                    'is_stale' => true,
                ]);
            }

            return $this->unavailable(
                'api_error',
                'Saldo belum dapat dimuat. Periksa token, Billing Account ID, dan koneksi server.'
            );
        }
    }

    public function isConfigured(): bool
    {
        return filled(config('services.idcloudhost.api_key'))
            && filled(config('services.idcloudhost.billing_account_id'));
    }

    /**
     * Data read-only untuk halaman Billing Cloud. Setiap koleksi diambil
     * terpisah agar kegagalan satu endpoint tidak mengosongkan seluruh halaman.
     *
     * @return array<string, mixed>
     */
    public function billingPage(bool $forceRefresh = false): array
    {
        $status = $this->dashboard($forceRefresh);

        if (! $this->isConfigured()) {
            return array_merge($status, [
                'reports' => [],
                'topup_invoices' => [],
                'balance_history' => [],
                'usage_total_formatted' => null,
                'partial_errors' => [],
            ]);
        }

        $accountId = (string) config('services.idcloudhost.billing_account_id');
        $cacheKey = self::DETAIL_CACHE_KEY_PREFIX.$accountId;

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $seconds = max(60, (int) config('services.idcloudhost.cache_seconds', 900));
        $details = Cache::remember($cacheKey, now()->addSeconds($seconds), function () use ($accountId): array {
            $errors = [];
            $invoices = $this->safeRequest('/payment/invoice/list', $accountId, 'invoice', $errors);
            $credits = $this->safeRequest('/payment/credit/list', $accountId, 'riwayat saldo', $errors);
            $usage = $this->safeRequest('/charging/usage', $accountId, 'pemakaian berjalan', $errors);

            return $this->presentBillingCollections($invoices, $credits, $usage, $errors);
        });

        return array_merge($status, $details);
    }

    /**
     * Dua KPI khusus dashboard untuk menggantikan pengguna aktif dan storage.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dashboardCards(array $status): array
    {
        $note = $status['available']
            ? 'Diperbarui '.$status['captured_label']
            : ($status['message'] ?? 'Data belum tersedia');

        return [
            [
                'key' => 'cloud-credit',
                'label' => 'Remaining Credit',
                'value' => $status['credit_formatted'] ?? '—',
                'icon' => 'fi fi-sr-wallet',
                'tint' => $status['level'] === 'critical' ? 'orange' : 'blue',
                'unit' => '',
                'delta' => ['available' => false, 'text' => $note, 'direction' => 'flat', 'tone' => 'flat'],
                'note' => $note,
            ],
            [
                'key' => 'cloud-runway',
                'label' => 'Estimasi Masa Aktif',
                'value' => $status['remaining_label'] ?? '—',
                'icon' => 'fi fi-sr-hourglass-end',
                'tint' => $status['level'] === 'critical' ? 'orange' : 'cyan',
                'unit' => '',
                'delta' => ['available' => false, 'text' => $status['level_label'] ?? 'Tidak tersedia', 'direction' => 'flat', 'tone' => 'flat'],
                'note' => $status['daily_cost_formatted'] ?? $note,
            ],
        ];
    }

    /**
     * Field finansial aman untuk diagnosis CLI. API key dan data identitas
     * billing account sengaja tidak pernah dikembalikan.
     *
     * @return array<string, float|null>
     */
    public function inspectBalanceFields(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $account = $this->request('/payment/billing_account', [
            'billing_account_id' => (string) config('services.idcloudhost.billing_account_id'),
        ]);

        return [
            'credit_amount' => $this->numericOrNull(data_get($account, 'credit_amount')),
            'credit_available' => $this->numericOrNull(data_get($account, 'running_totals.credit_available')),
            'ongoing' => $this->numericOrNull(data_get($account, 'running_totals.ongoing')),
            'running_total' => $this->numericOrNull(data_get($account, 'running_totals.total')),
            'unpaid_amount' => $this->numericOrNull(data_get($account, 'unpaid_amount')),
        ];
    }

    /**
     * Ringkasan struktur endpoint billing untuk diagnosis CLI. Hanya field
     * transaksi non-sensitif yang ditampilkan; kredensial dan profil akun
     * tidak pernah ikut keluar.
     *
     * @return array<string, mixed>
     */
    public function inspectBillingCollections(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $accountId = (string) config('services.idcloudhost.billing_account_id');
        $invoices = $this->request('/payment/invoice/list', ['billing_account_id' => $accountId]);
        $credits = $this->request('/payment/credit/list', ['billing_account_id' => $accountId]);
        $usage = $this->request('/charging/usage', ['billing_account_id' => $accountId]);

        return [
            'invoice_count' => count($invoices),
            'invoice_samples' => collect($invoices)->take(5)->map(fn ($row): array => [
                'id' => data_get($row, 'id'),
                'padded_id' => data_get($row, 'padded_id'),
                'status' => data_get($row, 'status'),
                'period_start' => data_get($row, 'period_start'),
                'period_end' => data_get($row, 'period_end'),
                'total' => data_get($row, 'totals.total'),
                'records' => collect(data_get($row, 'records_list', []))
                    ->take(3)
                    ->map(fn ($record) => [
                        'description' => data_get($record, 'description'),
                        'amount' => data_get($record, 'amount'),
                        'total' => data_get($record, 'total'),
                    ])->values()->all(),
            ])->values()->all(),
            'credit_count' => count($credits),
            'credit_samples' => collect($credits)->take(8)->map(fn ($row): array => [
                'id' => data_get($row, 'id'),
                'created' => data_get($row, 'created'),
                'amount' => data_get($row, 'amount'),
                'description' => data_get($row, 'description'),
            ])->values()->all(),
            'usage_count' => count($usage),
            'usage_samples' => collect($usage)->take(5)->map(fn ($row): array => [
                'description' => data_get($row, 'description'),
                'cost' => data_get($row, 'cost'),
                'hours' => data_get($row, 'hours'),
            ])->values()->all(),
        ];
    }

    /**
     * Memanggil API resmi. Detail akun dan histori kredit dipisahkan supaya
     * saldo tetap dapat ditampilkan ketika endpoint histori sedang bermasalah.
     */
    private function fetchAndStore(): array
    {
        $accountId = (string) config('services.idcloudhost.billing_account_id');
        $account = $this->request('/payment/billing_account', [
            'billing_account_id' => $accountId,
        ]);

        // Console IDCloudHost menampilkan `ongoing`: saldo efektif setelah
        // pemakaian berjalan. `credit_available` adalah saldo bruto sebelum
        // biaya tersebut, sehingga dapat terlihat lebih tinggi di dashboard.
        $credit = data_get(
            $account,
            'running_totals.ongoing',
            data_get($account, 'running_totals.credit_available', data_get($account, 'credit_amount'))
        );

        if (! is_numeric($credit)) {
            throw new RuntimeException('Response IDCloudHost tidak memuat nilai credit_available.');
        }

        $estimate = $this->configuredCostEstimate();

        if ($estimate === null) {
            try {
                $history = $this->request('/payment/credit/list', [
                    'billing_account_id' => $accountId,
                ]);
                $estimate = $this->estimateFromCreditHistory($history);
            } catch (Throwable $exception) {
                Log::notice('Histori billing IDCloudHost tidak dapat dimuat; saldo utama tetap digunakan.', [
                    'exception' => $exception::class,
                    'billing_account_id' => $accountId,
                ]);
            }
        }

        if ($estimate === null) {
            $estimate = $this->estimateFromSnapshots($accountId, (float) $credit);
        }

        $data = [
            'billing_account_id' => $accountId,
            'account_title' => is_string(data_get($account, 'title')) ? data_get($account, 'title') : null,
            'credit_available' => max(0, (float) $credit),
            'estimated_daily_cost' => $estimate['daily_cost'] ?? null,
            'estimate_source' => $estimate['source'] ?? null,
            'is_active' => (bool) data_get($account, 'is_active', true),
            'captured_at' => now(),
            'is_stale' => false,
        ];

        $this->storeSnapshot($data);

        return $data;
    }

    private function request(string $path, array $query): array
    {
        $baseUrl = rtrim((string) config('services.idcloudhost.base_url'), '/');
        $timeout = max(2, (int) config('services.idcloudhost.timeout_seconds', 8));

        $response = Http::acceptJson()
            ->withHeaders(['apikey' => (string) config('services.idcloudhost.api_key')])
            ->connectTimeout(min(4, $timeout))
            ->timeout($timeout)
            ->retry(2, 250, throw: false)
            ->get($baseUrl.$path, $query);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $decoded = $response->json();

        if (! is_array($decoded) || isset($decoded['errors'])) {
            throw new RuntimeException('Response IDCloudHost tidak valid.');
        }

        return $decoded;
    }

    private function safeRequest(string $path, string $accountId, string $label, array &$errors): array
    {
        try {
            return $this->request($path, ['billing_account_id' => $accountId]);
        } catch (Throwable $exception) {
            $errors[] = 'Data '.$label.' sementara tidak tersedia.';
            Log::notice('Endpoint billing IDCloudHost tidak dapat dimuat.', [
                'endpoint' => $path,
                'exception' => $exception::class,
                'billing_account_id' => $accountId,
            ]);

            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function presentBillingCollections(array $invoices, array $credits, array $usage, array $errors): array
    {
        $currentUsage = collect($usage)->sum(fn ($row): float => (float) data_get($row, 'cost', 0));
        $invoiceRows = collect($invoices)
            ->filter(fn ($row): bool => is_array($row))
            ->sortByDesc(fn (array $row): int => (int) data_get($row, 'period_end', 0));

        $reports = collect();

        if ($usage !== []) {
            $reports->push([
                'id' => 'Pemakaian berjalan',
                'status' => 'Berjalan',
                'status_tone' => 'ongoing',
                'period' => 'Bulan berjalan',
                'amount_formatted' => $this->formatMoney($currentUsage),
            ]);
        }

        $invoiceRows
            ->filter(fn (array $row): bool => (float) data_get($row, 'totals.total', 0) <= 0)
            ->each(function (array $row) use ($reports): void {
                $amount = collect(data_get($row, 'records_list', []))
                    ->sum(fn ($record): float => (float) data_get($record, 'amount', 0));

                $reports->push([
                    'id' => 'RP-'.str_pad((string) data_get($row, 'id', '—'), 10, '0', STR_PAD_LEFT),
                    'status' => (int) data_get($row, 'status') === 10 ? 'Lunas' : 'Belum lunas',
                    'status_tone' => (int) data_get($row, 'status') === 10 ? 'paid' : 'unpaid',
                    'period' => $this->formatPeriod(data_get($row, 'period_start'), data_get($row, 'period_end')),
                    'amount_formatted' => $this->formatMoney($amount),
                ]);
            });

        $topups = $invoiceRows
            ->filter(fn (array $row): bool => (float) data_get($row, 'totals.total', 0) > 0)
            ->map(fn (array $row): array => [
                'id' => '#'.str_pad((string) data_get($row, 'id', '—'), 12, '0', STR_PAD_LEFT),
                'status' => (int) data_get($row, 'status') === 10 ? 'Lunas' : 'Belum lunas',
                'status_tone' => (int) data_get($row, 'status') === 10 ? 'paid' : 'unpaid',
                'issued' => $this->formatDate(data_get($row, 'period_start')),
                'total_formatted' => $this->formatMoney((float) data_get($row, 'totals.total', 0)),
            ])->values()->all();

        $history = collect($credits)
            ->filter(fn ($row): bool => is_array($row) && is_numeric(data_get($row, 'amount')))
            ->sortByDesc(fn (array $row): int => (int) data_get($row, 'created', 0))
            ->map(function (array $row): array {
                $amount = (float) data_get($row, 'amount');

                return [
                    'id' => (string) data_get($row, 'id', ''),
                    'date' => $this->formatDateTime(data_get($row, 'created')),
                    'amount_formatted' => ($amount < 0 ? '−' : '+').$this->formatMoney(abs($amount)),
                    'tone' => $amount < 0 ? 'debit' : 'credit',
                    'description' => $this->translateDescription((string) data_get($row, 'description', 'Transaksi saldo')),
                ];
            })->values()->all();

        return [
            'reports' => $reports->values()->all(),
            'topup_invoices' => $topups,
            'balance_history' => $history,
            'usage_total_formatted' => $usage !== [] ? $this->formatMoney($currentUsage) : null,
            'partial_errors' => array_values(array_unique($errors)),
        ];
    }

    private function configuredCostEstimate(): ?array
    {
        $monthly = config('services.idcloudhost.estimated_monthly_cost');

        if (! is_numeric($monthly) || (float) $monthly <= 0) {
            return null;
        }

        return [
            'daily_cost' => (float) $monthly / 30.4375,
            'source' => 'configured',
        ];
    }

    private function numericOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Pembayaran invoice bernilai negatif. Rata-rata tiga invoice terbaru
     * lebih stabil daripada satu transaksi dan langsung berguna sebelum
     * snapshot saldo lokal terkumpul.
     */
    private function estimateFromCreditHistory(array $records): ?array
    {
        $invoicePayments = collect($records)
            ->filter(function ($record): bool {
                if (! is_array($record) || ! is_numeric($record['amount'] ?? null)) {
                    return false;
                }

                $description = mb_strtolower((string) ($record['description'] ?? ''));

                return (float) $record['amount'] < 0 && str_contains($description, 'invoice');
            })
            ->sortByDesc(fn (array $record): int => (int) ($record['created'] ?? 0))
            ->take(3)
            ->pluck('amount')
            ->map(fn ($amount): float => abs((float) $amount))
            ->filter(fn (float $amount): bool => $amount > 0);

        if ($invoicePayments->isEmpty()) {
            return null;
        }

        return [
            'daily_cost' => $invoicePayments->avg() / 30.4375,
            'source' => 'invoice_history',
        ];
    }

    private function estimateFromSnapshots(string $accountId, float $currentCredit): ?array
    {
        if (! Schema::hasTable('idcloudhost_credit_snapshots')) {
            return null;
        }

        $oldest = IdcloudhostCreditSnapshot::query()
            ->where('billing_account_id', $accountId)
            ->where('captured_at', '<=', now()->subHours(12))
            ->where('captured_at', '>=', now()->subDays(30))
            ->oldest('captured_at')
            ->first();

        if (! $oldest) {
            $previous = $this->latestSnapshot($accountId);

            return $previous?->estimated_daily_cost > 0
                ? ['daily_cost' => $previous->estimated_daily_cost, 'source' => $previous->estimate_source]
                : null;
        }

        $elapsedDays = max($oldest->captured_at->diffInMinutes(now()) / 1440, 0.5);
        $spent = (float) $oldest->credit_available - $currentCredit;

        // Saldo naik berarti top-up terjadi dalam jendela observasi.
        if ($spent <= 0) {
            return null;
        }

        return [
            'daily_cost' => $spent / $elapsedDays,
            'source' => 'balance_trend',
        ];
    }

    private function storeSnapshot(array $data): void
    {
        if (! Schema::hasTable('idcloudhost_credit_snapshots')) {
            return;
        }

        $latest = $this->latestSnapshot($data['billing_account_id']);

        // Cache biasanya membuat ini tidak terjadi, tetapi interval minimum
        // mencegah tabel membengkak bila refresh paksa dipanggil berulang.
        if ($latest && $latest->captured_at->greaterThan(now()->subMinutes(10))) {
            $latest->update([
                'credit_available' => $data['credit_available'],
                'estimated_daily_cost' => $data['estimated_daily_cost'],
                'estimate_source' => $data['estimate_source'],
                'account_title' => $data['account_title'],
                'is_active' => $data['is_active'],
                'captured_at' => $data['captured_at'],
            ]);

            return;
        }

        IdcloudhostCreditSnapshot::create([
            'billing_account_id' => $data['billing_account_id'],
            'credit_available' => $data['credit_available'],
            'estimated_daily_cost' => $data['estimated_daily_cost'],
            'estimate_source' => $data['estimate_source'],
            'account_title' => $data['account_title'],
            'is_active' => $data['is_active'],
            'captured_at' => $data['captured_at'],
        ]);

        IdcloudhostCreditSnapshot::query()
            ->where('captured_at', '<', now()->subDays(90))
            ->delete();
    }

    private function latestSnapshot(string $accountId): ?IdcloudhostCreditSnapshot
    {
        if (! Schema::hasTable('idcloudhost_credit_snapshots')) {
            return null;
        }

        return IdcloudhostCreditSnapshot::query()
            ->where('billing_account_id', $accountId)
            ->latest('captured_at')
            ->first();
    }

    private function present(array $data): array
    {
        $credit = (float) $data['credit_available'];
        $dailyCost = is_numeric($data['estimated_daily_cost'] ?? null)
            ? (float) $data['estimated_daily_cost']
            : null;
        $remainingDays = $dailyCost > 0 ? $credit / $dailyCost : null;
        $level = $this->warningLevel($credit, $remainingDays);
        $capturedAt = $data['captured_at'] instanceof Carbon
            ? $data['captured_at']
            : Carbon::parse($data['captured_at']);

        return array_merge($data, [
            'available' => true,
            'configured' => true,
            'currency' => (string) config('services.idcloudhost.currency', 'IDR'),
            'credit_formatted' => $this->formatMoney($credit),
            'daily_cost_formatted' => $dailyCost ? $this->formatMoney($dailyCost) : null,
            'remaining_days' => $remainingDays,
            'remaining_label' => $this->formatDuration($remainingDays),
            'runway_percent' => $remainingDays === null
                ? 0
                : min(100, max(0, (int) round(
                    $remainingDays / max(1, (float) config('services.idcloudhost.runway_target_days', 180)) * 100
                ))),
            'level' => $level,
            'level_label' => match ($level) {
                'critical' => 'Kritis',
                'warning' => 'Hampir habis',
                default => 'Aman',
            },
            'message' => $this->warningMessage($level, $remainingDays),
            'captured_label' => $capturedAt->locale('id')->diffForHumans(),
            'captured_iso' => $capturedAt->toIso8601String(),
        ]);
    }

    private function warningLevel(float $credit, ?float $remainingDays): string
    {
        $lowThreshold = max(0, (float) config('services.idcloudhost.low_credit_threshold', 100000));
        $criticalDays = max(0, (float) config('services.idcloudhost.critical_days', 3));
        $warningDays = max($criticalDays, (float) config('services.idcloudhost.warning_days', 7));

        if ($credit <= 0
            || ($remainingDays !== null && $remainingDays <= $criticalDays)
            || ($lowThreshold > 0 && $credit <= $lowThreshold / 2)) {
            return 'critical';
        }

        if (($remainingDays !== null && $remainingDays <= $warningDays)
            || ($lowThreshold > 0 && $credit <= $lowThreshold)) {
            return 'warning';
        }

        return 'healthy';
    }

    private function warningMessage(string $level, ?float $remainingDays): ?string
    {
        if ($level === 'healthy') {
            return null;
        }

        if ($level === 'critical') {
            return $remainingDays !== null
                ? 'Saldo diperkirakan hanya cukup '.$this->formatDuration($remainingDays).'. Segera lakukan top up.'
                : 'Saldo berada di bawah ambang kritis. Segera lakukan top up.';
        }

        return $remainingDays !== null
            ? 'Saldo mendekati batas aman dan diperkirakan cukup '.$this->formatDuration($remainingDays).'.'
            : 'Saldo IDCloudHost sudah berada di bawah ambang peringatan.';
    }

    private function formatMoney(float $amount): string
    {
        $currency = strtoupper((string) config('services.idcloudhost.currency', 'IDR'));
        $decimals = $currency === 'IDR' ? 0 : 2;
        $prefix = $currency === 'IDR' ? 'Rp ' : $currency.' ';

        return $prefix.number_format($amount, $decimals, ',', '.');
    }

    private function formatDuration(?float $days): string
    {
        if ($days === null) {
            return 'Mengumpulkan data';
        }

        if ($days < 1) {
            return '± '.max(1, (int) ceil($days * 24)).' jam';
        }

        if ($days < 60) {
            return '± '.max(1, (int) floor($days)).' hari';
        }

        $months = $days / 30.4375;

        return '± '.number_format($months, $months >= 10 ? 0 : 1, ',', '.').' bulan';
    }

    private function formatPeriod(mixed $start, mixed $end): string
    {
        if (! is_numeric($start) || ! is_numeric($end)) {
            return '—';
        }

        return Carbon::createFromTimestamp((int) $start)
            ->setTimezone(config('app.timezone'))
            ->locale('id')
            ->translatedFormat('d M Y')
            .' – '.
            Carbon::createFromTimestamp((int) $end)
                ->setTimezone(config('app.timezone'))
                ->locale('id')
                ->translatedFormat('d M Y');
    }

    private function formatDate(mixed $timestamp): string
    {
        return is_numeric($timestamp)
            ? Carbon::createFromTimestamp((int) $timestamp)
                ->setTimezone(config('app.timezone'))
                ->locale('id')
                ->translatedFormat('d M Y')
            : '—';
    }

    private function formatDateTime(mixed $timestamp): string
    {
        return is_numeric($timestamp)
            ? Carbon::createFromTimestamp((int) $timestamp)
                ->setTimezone(config('app.timezone'))
                ->locale('id')
                ->translatedFormat('d M Y, H.i').' WITA'
            : '—';
    }

    private function translateDescription(string $description): string
    {
        $lower = mb_strtolower($description);

        if (str_contains($lower, 'invoice payment')) {
            return 'Pembayaran invoice';
        }

        if (str_contains($lower, 'top up')) {
            return 'Top up saldo IDCloudHost';
        }

        if (str_contains($lower, 'campaign')) {
            return 'Kredit promosi';
        }

        return $description !== '' ? $description : 'Transaksi saldo';
    }

    private function unavailable(string $reason, string $message): array
    {
        return [
            'available' => false,
            'configured' => $reason !== 'not_configured',
            'reason' => $reason,
            'level' => 'unavailable',
            'level_label' => 'Tidak tersedia',
            'message' => $message,
            'credit_formatted' => '—',
            'remaining_label' => '—',
            'runway_percent' => 0,
        ];
    }
}
