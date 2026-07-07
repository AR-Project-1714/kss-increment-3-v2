<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Operasi Harian</title>
    <style>
        @page { size: 21.59cm 33.02cm; margin: 20px 22px; }
        body { margin: 0; }
    </style>
</head>
<body>
    @include('report-ops.partials.report-paper', ['report' => $report, 'isPdf' => true])
</body>
</html>
