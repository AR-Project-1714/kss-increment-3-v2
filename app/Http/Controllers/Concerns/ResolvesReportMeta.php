<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\ReportStatus;
use App\Models\DailyReport;
use Carbon\Carbon;
use Throwable;

/**
 * Presentasi & metadata laporan yang dipakai bersama oleh dashboard operasional,
 * manajer, dan admin (label shift/status, nomor dokumen, nama file export, relasi
 * eager-load standar). Sebelumnya helper-helper ini diduplikasi di tiap controller.
 */
trait ResolvesReportMeta
{
    protected function shiftMeta(mixed $shift): array
    {
        $normalized = strtolower(trim((string) $shift));

        return match (true) {
            in_array($normalized, ['1', 'pagi', 'shift 1', 'shift pagi'], true) => ['label' => 'Shift Pagi', 'short' => 'Pagi', 'class' => 'pagi', 'icon' => 'fi fi-rr-sunrise'],
            in_array($normalized, ['2', 'sore', 'siang', 'shift 2', 'shift sore', 'shift siang'], true) => ['label' => 'Shift Sore', 'short' => 'Sore', 'class' => 'sore', 'icon' => 'fi fi-rr-sun'],
            in_array($normalized, ['3', 'malam', 'shift 3', 'shift malam'], true) => ['label' => 'Shift Malam', 'short' => 'Malam', 'class' => 'malam', 'icon' => 'fi fi-rr-moon-stars'],
            default => ['label' => $shift ? 'Shift '.$shift : 'Shift -', 'short' => $shift ? trim((string) $shift) : '-', 'class' => 'pagi', 'icon' => 'fi fi-rr-clock'],
        };
    }

    protected function statusMeta(mixed $status): array
    {
        $value = $status instanceof ReportStatus ? $status->value : (string) $status;

        return match ($value) {
            ReportStatus::Draft->value => ['label' => 'Draft', 'class' => 'draft'],
            ReportStatus::Submitted->value => ['label' => 'Diserahkan', 'class' => 'submit'],
            ReportStatus::Acknowledged->value => ['label' => 'Diterima', 'class' => 'confirm'],
            ReportStatus::Approved->value => ['label' => 'Diarsipkan', 'class' => 'archive'],
            default => ['label' => ucfirst($value), 'class' => 'submit'],
        };
    }

    protected function shiftSearchValues(string $shift): array
    {
        return match ($shift) {
            'pagi' => ['1', 'pagi', 'shift 1', 'shift pagi'],
            'sore' => ['2', 'sore', 'siang', 'shift 2', 'shift sore', 'shift siang'],
            'malam' => ['3', 'malam', 'shift 3', 'shift malam'],
            default => [],
        };
    }

    protected function documentId(DailyReport $report): string
    {
        try {
            $date = $report->report_date ?: $report->created_at;
            $year = $date ? Carbon::parse($date)->format('Y') : now()->format('Y');
        } catch (Throwable) {
            $year = now()->format('Y');
        }

        return '#OPS-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
    }

    protected function reportFileName(DailyReport $report, string $extension): string
    {
        try {
            $date = $report->report_date ?: $report->created_at;
            $year = $date ? Carbon::parse($date)->format('Y') : now()->format('Y');
        } catch (Throwable) {
            $year = now()->format('Y');
        }

        $id = str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
        $group = strtoupper(trim((string) $report->group_name)) ?: '-';
        $extension = ltrim(strtolower($extension), '.');

        // Format: Laporan_Ops_[id]_[tahun]_[regu] -> mis. Laporan_Ops_001_2026_A.pdf
        return "Laporan_Ops_{$id}_{$year}_{$group}.{$extension}";
    }

    protected function archiveStatuses(): array
    {
        return [ReportStatus::Submitted, ReportStatus::Acknowledged, ReportStatus::Approved];
    }

    protected function reportRelations(): array
    {
        return [
            'creator',
            'receiver',
            'approver',
            'loadingActivities.timesheets',
            'bulkLoadingActivities.logs',
            'materialActivity.items',
            'containerActivity.items',
            'turbaActivity.deliveries',
            'unitCheckLogs',
            'employeeLogs',
        ];
    }

    protected function loadReport(DailyReport $report): DailyReport
    {
        return $report->load($this->reportRelations());
    }

    protected function applyArchiveDivisionFilter($query, string $division): void
    {
        if ($division === '' || $division === 'all' || $division === 'operasional') {
            return;
        }

        $query->whereRaw('1 = 0');
    }
}
