@extends('pemeliharaan.layouts.app')

@php
    use App\Enums\MaintenanceStatus;
    $isEdit = true;
    $formAction = route('pemeliharaan.update', $report);
    $headerTitle = $report->status === MaintenanceStatus::Draft ? 'Lanjutkan Draft Pemeliharaan' : 'Edit Laporan Pemeliharaan';
    try { $year = ($report->report_date ?: $report->created_at)?->format('Y') ?? now()->format('Y'); } catch (\Throwable) { $year = now()->format('Y'); }
    $headerDocumentLabel = '#MNT-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
@endphp

@include('pemeliharaan.partials.report-form')
