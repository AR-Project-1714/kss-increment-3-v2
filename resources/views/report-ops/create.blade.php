@extends('report-ops.layouts.app')

@php
    // Draft sudah direservasi controller saat form dibuka, jadi laporan baru pun
    // langsung punya ID dan menembak endpoint update — tidak ada baris duplikat
    // walau autosave berjalan berkali-kali.
    $isEdit = false;
    $formMethod = 'PUT';
    $formAction = route('report-ops.update', $reservedReport);
    $discardBlankUrl = route('report-ops.discard-blank', $reservedReport);
    $headerTitle = 'Form Laporan Operasi Harian';
    try { $year = ($reservedReport->report_date ?: $reservedReport->created_at)?->format('Y') ?? now()->format('Y'); } catch (\Throwable) { $year = now()->format('Y'); }
    $headerDocumentLabel = '#OPS-'.$year.'-'.str_pad((string) $reservedReport->id, 3, '0', STR_PAD_LEFT);
    $draftButtonLabel = 'Simpan Sebagai Draft';
@endphp

@include('report-ops.partials.report-form')
