{{-- Tonase bulanan yang dipecah menjadi tiga shift.

     Warna dipilih menurut waktu kerja — oranye untuk pagi, biru untuk sore,
     ungu untuk malam. Penggambarannya diserahkan ke partial area bertumpuk
     yang juga dipakai dashboard admin.

     Parameter:
       $shiftTrend  deret bulanan dari OperationalPerformanceService
--}}
@include('charts.area-stacked', [
    'rows' => $shiftTrend ?? [],
    'series' => [
        ['key' => 'Pagi',  'label' => 'Shift Pagi',  'color' => 'var(--chart-3)'],
        ['key' => 'Sore',  'label' => 'Shift Sore',  'color' => 'var(--chart-1)'],
        ['key' => 'Malam', 'label' => 'Shift Malam', 'color' => 'var(--chart-5)'],
    ],
    'suffix' => ' Ton',
    'decimals' => 1,
    'labelStep' => 1,
    'emptyText' => 'Belum ada tonase yang tercatat pada enam bulan terakhir.',
    'ariaLabel' => 'Grafik area tonase bulanan menurut shift',
])
