@extends('report-safety.layouts.app')

@php
    use App\Enums\SafetyStatus;
    $isEdit = true;
    $formAction = route('safety.update', $report);
    $headerTitle = $report->status === SafetyStatus::Draft ? 'Lanjutkan Draft K3' : 'Edit Laporan K3';
    try { $year = ($report->report_date ?: $report->created_at)?->format('Y') ?? now()->format('Y'); } catch (\Throwable) { $year = now()->format('Y'); }
    $headerDocumentLabel = '#K3-'.$year.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);
@endphp

@include('report-safety.partials.report-form')
