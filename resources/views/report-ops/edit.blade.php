@extends('report-ops.layouts.app')

@php
    try {
        $documentDate = $report->report_date ?: $report->created_at;
        $documentYear = $documentDate
            ? \Carbon\Carbon::parse($documentDate)->format('Y')
            : now()->format('Y');
    } catch (\Throwable) {
        $documentYear = now()->format('Y');
    }

    $documentId = '#OPS-'.$documentYear.'-'.str_pad((string) $report->id, 3, '0', STR_PAD_LEFT);

    $isEdit = true;
    $formMethod = 'PUT';
    $formAction = route('report-ops.update', $report);
    $discardBlankUrl = null;
    $headerTitle = 'Edit Laporan Operasi Harian';
    $headerDocumentLabel = $documentId;
    $draftButtonLabel = 'Simpan Pembaruan';
@endphp

@include('report-ops.partials.report-form')
