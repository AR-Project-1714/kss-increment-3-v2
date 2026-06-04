<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\SafetyStatus;
use App\Models\SafetyReport;
use Carbon\Carbon;
use Throwable;

/**
 * Presentasi & metadata laporan K3 yang dipakai bersama oleh dashboard petugas
 * (Karu Safety) maupun dashboard manajer (nomor dokumen, badge status, nama file).
 * Mengikuti pola ResolvesMaintenanceMeta.
 */
trait ResolvesSafetyMeta
{
    protected function safetyStatusMeta(mixed $status): array
    {
        $value = $status instanceof SafetyStatus ? $status->value : (string) $status;
        $case = SafetyStatus::tryFrom($value) ?? SafetyStatus::Draft;

        return [
            'label' => $case->label(),
            'class' => $case->badgeClass(),
            'icon'  => $case->icon(),
        ];
    }

    protected function safetyDocumentId(SafetyReport $report): string
    {
        try {
            $date = $report->report_date ?: $report->created_at;
            $year = $date ? Carbon::parse($date)->format('Y') : now()->format('Y');
        } catch (Throwable) {
            $year = now()->format('Y');
        }

        return '#K3-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
    }

    protected function safetyFileName(SafetyReport $report, string $extension): string
    {
        try {
            $date = $report->report_date ?: $report->created_at;
            $year = $date ? Carbon::parse($date)->format('Y') : now()->format('Y');
        } catch (Throwable) {
            $year = now()->format('Y');
        }

        $id = str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
        $extension = ltrim(strtolower($extension), '.');

        return "Laporan_K3_{$id}_{$year}.{$extension}";
    }

    protected function safetyReportRelations(): array
    {
        return [
            'creator',
            'approver',
            'inspections.location',
            'inspections.item',
            'operationLogs',
            'incidentLogs',
        ];
    }

    protected function loadSafetyReport(SafetyReport $report): SafetyReport
    {
        return $report->load($this->safetyReportRelations());
    }

    protected function safetyDayName(mixed $date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date)->locale('id')->translatedFormat('l');
        } catch (Throwable) {
            return null;
        }
    }
}
