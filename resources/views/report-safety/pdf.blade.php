<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>@page { size: 21.59cm 33.02cm; margin: 20px 22px; }</style>
</head>
<body>
    @include('report-safety.partials.report-paper', ['report' => $report, 'isPdf' => true])
</body>
</html>
