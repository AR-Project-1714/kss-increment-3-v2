@extends('report-safety.layouts.app')

@php
    $isEdit = false;
    $formAction = route('safety.store');
    $headerTitle = 'Form Laporan Harian K3';
    $headerDocumentLabel = 'Draft Baru';
@endphp

@include('report-safety.partials.report-form')
