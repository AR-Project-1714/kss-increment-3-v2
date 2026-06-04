@extends('pemeliharaan.layouts.app')

@php
    $isEdit = false;
    $formAction = route('pemeliharaan.store');
    $headerTitle = 'Form Laporan Harian Pemeliharaan';
    $headerDocumentLabel = 'Draft Baru';
@endphp

@include('pemeliharaan.partials.report-form')
