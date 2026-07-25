@extends('pemeliharaan.layouts.app')

@php
    // Draft sudah direservasi controller saat form dibuka, jadi laporan baru pun
    // langsung punya ID dan menembak endpoint update — tidak ada baris duplikat
    // walau autosave berjalan berkali-kali.
    $isEdit = false;
    $formMethod = 'PUT';
    $formAction = route('pemeliharaan.update', $reservedReport);
    $discardBlankUrl = route('pemeliharaan.discard-blank', $reservedReport);
    $headerTitle = 'Form Laporan Harian Pemeliharaan';
    try { $year = ($reservedReport->report_date ?: $reservedReport->created_at)?->format('Y') ?? now()->format('Y'); } catch (\Throwable) { $year = now()->format('Y'); }
    $headerDocumentLabel = '#MNT-'.$year.'-'.str_pad((string) $reservedReport->id, 3, '0', STR_PAD_LEFT);
@endphp

@include('pemeliharaan.partials.report-form')
