<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\MaintenanceReport;
use App\Models\Role;
use App\Models\SafetyReport;
use App\Models\SystemNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class SystemNotificationService
{
    /** Notifikasi yang masih menunggu tindakan bertahan paling lama dua minggu. */
    private const ACTION_TTL_DAYS = 14;

    /** Pemberitahuan hasil atau keberhasilan cukup berada di kotak selama seminggu. */
    private const INFO_TTL_DAYS = 7;

    public function operationalSubmitted(DailyReport $report): void
    {
        $this->safely('operational_submitted', function () use ($report): void {
            $recipients = $this->activeUsersForRole(Role::OPERATIONAL)
                ->filter(fn (User $user): bool => strtoupper((string) $user->group) === strtoupper((string) $report->received_by_group)
                    && (int) $user->id !== (int) $report->created_by);

            $this->notify($recipients, [
                'event_key' => $this->eventKey('operational', $report->id, 'awaiting-acknowledgement'),
                'category' => 'report',
                'severity' => 'warning',
                'title' => 'Laporan masuk perlu ditandatangani',
                'message' => sprintf(
                    '%s dari Regu %s untuk Regu %s menunggu tanda tangan serah terima.',
                    $this->operationalDocumentId($report),
                    strtoupper((string) $report->group_name),
                    strtoupper((string) $report->received_by_group),
                ),
                'action_url' => route('report-ops.show', $report),
                'action_label' => 'Periksa laporan',
                'expires_at' => now()->addDays(self::ACTION_TTL_DAYS),
            ]);
        });
    }

    public function operationalAcknowledged(DailyReport $report): void
    {
        $this->safely('operational_acknowledged', function () use ($report): void {
            $this->resolve($this->eventKey('operational', $report->id, 'awaiting-acknowledgement'));

            $this->notify($this->activeUsersForRole(Role::MANAGER), [
                'event_key' => $this->eventKey('operational', $report->id, 'awaiting-approval'),
                'category' => 'approval',
                'severity' => 'warning',
                'title' => 'Laporan Operasional menunggu persetujuan',
                'message' => $this->operationalDocumentId($report).' telah ditandatangani regu penerima dan siap disetujui.',
                'action_url' => route('manajer.reports.show', $report),
                'action_label' => 'Tinjau laporan',
                'expires_at' => now()->addDays(self::ACTION_TTL_DAYS),
            ]);
        });
    }

    public function operationalApproved(DailyReport $report): void
    {
        $this->safely('operational_approved', function () use ($report): void {
            $this->resolve($this->eventKey('operational', $report->id, 'awaiting-approval'));

            $this->notify($this->activeUserById($report->created_by), [
                'event_key' => $this->eventKey('operational', $report->id, 'approved'),
                'category' => 'approval',
                'severity' => 'success',
                'title' => 'Laporan Operasional disetujui',
                'message' => $this->operationalDocumentId($report).' telah ditandatangani manajer dan masuk ke arsip.',
                'action_url' => route('report-ops.show', $report),
                'action_label' => 'Lihat laporan',
                'expires_at' => now()->addDays(self::INFO_TTL_DAYS),
            ]);
        });
    }

    public function maintenanceSubmitted(MaintenanceReport $report): void
    {
        $this->safely('maintenance_submitted', function () use ($report): void {
            $this->notify($this->activeUsersForRole(Role::MANAGER), [
                'event_key' => $this->eventKey('maintenance', $report->id, 'awaiting-approval'),
                'category' => 'approval',
                'severity' => 'warning',
                'title' => 'Laporan Pemeliharaan masuk',
                'message' => $this->maintenanceDocumentId($report).' menunggu pemeriksaan dan tanda tangan manajer.',
                'action_url' => route('manajer.pemeliharaan.show', $report),
                'action_label' => 'Tinjau laporan',
                'expires_at' => now()->addDays(self::ACTION_TTL_DAYS),
            ]);
        });
    }

    public function maintenanceApproved(MaintenanceReport $report): void
    {
        $this->safely('maintenance_approved', function () use ($report): void {
            $this->resolve($this->eventKey('maintenance', $report->id, 'awaiting-approval'));

            $this->notify($this->activeUserById($report->created_by), [
                'event_key' => $this->eventKey('maintenance', $report->id, 'approved'),
                'category' => 'approval',
                'severity' => 'success',
                'title' => 'Laporan Pemeliharaan disetujui',
                'message' => $this->maintenanceDocumentId($report).' telah ditandatangani manajer dan masuk ke arsip.',
                'action_url' => route('pemeliharaan.show', $report),
                'action_label' => 'Lihat laporan',
                'expires_at' => now()->addDays(self::INFO_TTL_DAYS),
            ]);
        });
    }

    public function safetySubmitted(SafetyReport $report): void
    {
        $this->safely('safety_submitted', function () use ($report): void {
            $this->notify($this->activeUsersForRole(Role::MANAGER), [
                'event_key' => $this->eventKey('safety', $report->id, 'awaiting-approval'),
                'category' => 'safety',
                'severity' => 'warning',
                'title' => 'Laporan K3 masuk',
                'message' => $this->safetyDocumentId($report).' menunggu pemeriksaan dan tanda tangan manajer.',
                'action_url' => route('manajer.safety.show', $report),
                'action_label' => 'Tinjau laporan',
                'expires_at' => now()->addDays(self::ACTION_TTL_DAYS),
            ]);
        });
    }

    public function safetyApproved(SafetyReport $report): void
    {
        $this->safely('safety_approved', function () use ($report): void {
            $this->resolve($this->eventKey('safety', $report->id, 'awaiting-approval'));

            $this->notify($this->activeUserById($report->created_by), [
                'event_key' => $this->eventKey('safety', $report->id, 'approved'),
                'category' => 'safety',
                'severity' => 'success',
                'title' => 'Laporan K3 disetujui',
                'message' => $this->safetyDocumentId($report).' telah ditandatangani manajer dan masuk ke arsip.',
                'action_url' => route('safety.show', $report),
                'action_label' => 'Lihat laporan',
                'expires_at' => now()->addDays(self::INFO_TTL_DAYS),
            ]);
        });
    }

    public function backupSucceeded(string $filename, bool $automatic): void
    {
        $this->safely('backup_succeeded', function () use ($filename, $automatic): void {
            $this->notify($this->activeUsersForRole(Role::ADMIN), [
                'event_key' => 'system:backup:latest',
                'category' => 'backup',
                'severity' => 'success',
                'title' => 'Backup '.($automatic ? 'otomatis' : 'manual').' berhasil',
                'message' => 'Cadangan terbaru '.$filename.' telah tersimpan dan siap diperiksa.',
                'action_url' => route('admin.backup'),
                'action_label' => 'Buka backup',
                'expires_at' => now()->addDays(self::INFO_TTL_DAYS),
            ]);
        });
    }

    public function backupFailed(bool $automatic): void
    {
        $this->safely('backup_failed', function () use ($automatic): void {
            $this->notify($this->activeUsersForRole(Role::ADMIN), [
                'event_key' => 'system:backup:latest',
                'category' => 'backup',
                'severity' => 'danger',
                'title' => 'Backup '.($automatic ? 'otomatis' : 'manual').' gagal',
                'message' => 'Cadangan sistem belum berhasil dibuat. Periksa kapasitas penyimpanan dan log sistem.',
                'action_url' => route('admin.backup'),
                'action_label' => 'Periksa backup',
                'expires_at' => now()->addDays(self::ACTION_TTL_DAYS),
            ]);
        });
    }

    private function notify(iterable $users, array $data): void
    {
        foreach ($users as $user) {
            SystemNotification::query()->updateOrCreate(
                ['user_id' => $user->id, 'event_key' => $data['event_key']],
                array_merge($data, [
                    'read_at' => null,
                    'resolved_at' => null,
                    // Event dengan key yang sama (contohnya backup terbaru)
                    // menjadi kejadian baru, sehingga waktu dan urutannya ikut direset.
                    'created_at' => now(),
                ])
            );
        }
    }

    private function resolve(string $eventKey): void
    {
        SystemNotification::query()
            ->where('event_key', $eventKey)
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now(), 'updated_at' => now()]);
    }

    /** @return Collection<int, User> */
    private function activeUsersForRole(string $role): Collection
    {
        $names = $role === Role::OPERATIONAL ? [Role::OPERATIONAL, 'petugas'] : [$role];

        return User::query()
            ->where('status', 'aktif')
            ->whereHas('role', fn ($query) => $query->whereIn('name', $names))
            ->get();
    }

    /** @return Collection<int, User> */
    private function activeUserById(?int $userId): Collection
    {
        if (! $userId) {
            return collect();
        }

        return User::query()->whereKey($userId)->where('status', 'aktif')->get();
    }

    private function eventKey(string $division, int $reportId, string $event): string
    {
        return "report:{$division}:{$reportId}:{$event}";
    }

    private function operationalDocumentId(DailyReport $report): string
    {
        return '#OPS-'.$this->year($report->report_date).'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
    }

    private function maintenanceDocumentId(MaintenanceReport $report): string
    {
        return '#MNT-'.$this->year($report->report_date).'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
    }

    private function safetyDocumentId(SafetyReport $report): string
    {
        if ($report->document_number) {
            return '#'.ltrim((string) $report->document_number, '#');
        }

        return '#K3-'.$this->year($report->report_date).'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
    }

    private function year(mixed $date): string
    {
        return $date ? Carbon::parse($date)->format('Y') : now()->format('Y');
    }

    private function safely(string $event, callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            // Kegagalan notifikasi tidak boleh menggagalkan transaksi laporan/backup
            // yang sudah berhasil. Catat agar dapat ditindaklanjuti oleh admin.
            Log::error('Gagal memproses notifikasi sistem.', [
                'event' => $event,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
