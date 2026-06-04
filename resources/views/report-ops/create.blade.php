@extends('report-ops.layouts.app')

@php
    $isEdit = false;
    $formAction = route('report-ops.store');
    $headerTitle = 'Form Laporan Operasi Harian';
    $headerDocumentLabel = 'Draft Baru';
    $draftButtonLabel = 'Simpan Sebagai Draft';
@endphp

@include('report-ops.partials.report-form')
