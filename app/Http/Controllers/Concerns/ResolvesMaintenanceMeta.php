<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceReport;
use Carbon\Carbon;
use Throwable;

/**
 * Presentasi & metadata laporan pemeliharaan yang dipakai bersama oleh dashboard
 * petugas (Kasi) maupun dashboard manajer (nomor dokumen, badge status, nama file).
 */
trait ResolvesMaintenanceMeta
{
    protected function maintenanceStatusMeta(mixed $status): array
    {
        $value = $status instanceof MaintenanceStatus ? $status->value : (string) $status;
        $case = MaintenanceStatus::tryFrom($value) ?? MaintenanceStatus::Draft;

        return [
            'label' => $case->label(),
            'class' => $case->badgeClass(),
            'icon'  => $case->icon(),
        ];
    }

    protected function maintenanceDocumentId(MaintenanceReport $report): string
    {
        try {
            $date = $report->report_date ?: $report->created_at;
            $year = $date ? Carbon::parse($date)->format('Y') : now()->format('Y');
        } catch (Throwable) {
            $year = now()->format('Y');
        }

        return '#MNT-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
    }

    protected function maintenanceFileName(MaintenanceReport $report, string $extension): string
    {
        try {
            $date = $report->report_date ?: $report->created_at;
            $year = $date ? Carbon::parse($date)->format('Y') : now()->format('Y');
        } catch (Throwable) {
            $year = now()->format('Y');
        }

        $id = str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
        $extension = ltrim(strtolower($extension), '.');

        return "Laporan_Pemeliharaan_{$id}_{$year}.{$extension}";
    }

    protected function maintenanceReportRelations(): array
    {
        return [
            'creator',
            'approver',
            'workItems.unit',
            'unitConditions.unit',
            'attendances',
        ];
    }

    protected function loadMaintenanceReport(MaintenanceReport $report): MaintenanceReport
    {
        return $report->load($this->maintenanceReportRelations());
    }
}
