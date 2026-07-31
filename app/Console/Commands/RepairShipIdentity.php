<?php

namespace App\Console\Commands;

use App\Models\ShipOperation;
use App\Services\BulkTonnageService;
use App\Support\ShipNameNormalizer;
use App\Support\TonnageNumber;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Penyembuhan data lama: satukan identitas kapal, lalu hitung ulang tonase.
 *
 * Perbaikan pada form dan pada penyimpanan hanya berlaku untuk laporan baru.
 * Riwayat yang sudah tersimpan tetap memuat satu pelayaran yang terpecah
 * menjadi beberapa operasi kapal hanya karena ejaan namanya berbeda tiap
 * shift. Perintah ini merapikannya dalam empat langkah, semuanya bisa diulang
 * kapan saja tanpa efek ganda:
 *
 *   1. Isi ship_name_key (nama kanonik) di semua tabel yang menyimpan nama kapal.
 *   2. Gabungkan operasi kapal kembar — nama kanonik sama, atau namanya mirip
 *      dan rentang tanggalnya bersinggungan.
 *   3. Sambungkan aktivitas yang belum punya operasi kapal (petugas melewati
 *      fitur simpan operasi kapal) ke operasi yang sesuai.
 *   4. Hitung ulang cob_delta untuk seluruh pelayaran.
 *
 * Jalankan dengan --dry-run lebih dulu untuk melihat rencananya tanpa menulis.
 */
class RepairShipIdentity extends Command
{
    protected $signature = 'ops:repair-ship-identity
                            {--dry-run : Tampilkan rencana perubahan tanpa menyimpan apa pun}
                            {--recalculate-only : Hanya hitung ulang tonase, tanpa menggabungkan operasi kapal}';

    protected $description = 'Satukan penamaan kapal yang tidak konsisten lalu hitung ulang tonase muat curah';

    /**
     * Tabel aktivitas yang menunjuk operasi kapal, beserta jenis operasinya.
     * null berarti jenisnya ditentukan per baris lewat kolom activity_type.
     */
    private const ACTIVITY_TYPES = [
        'loading_activities' => ShipOperation::TYPE_BAG_LOADING,
        'bulk_loading_activities' => null,
        'material_activities' => ShipOperation::TYPE_MATERIAL_UNLOADING,
        'container_activities' => ShipOperation::TYPE_CONTAINER,
    ];

    /** @var array<int, string> */
    private const ACTIVITY_TABLES = [
        'loading_activities',
        'bulk_loading_activities',
        'material_activities',
        'container_activities',
    ];

    /**
     * Rentang tanggal laporan per operasi kapal. Dihitung malas dan dilupakan
     * setiap kali operasi digabung, karena penggabungan mengubah rentangnya.
     *
     * @var array<int, array{0: ?Carbon, 1: ?Carbon}>|null
     */
    private ?array $spans = null;

    public function handle(BulkTonnageService $tonnage): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Mode pratinjau: tidak ada perubahan yang disimpan.');
        }

        // Penggabungan operasi kapal mengubah struktur data dan sebaiknya
        // dijalankan sadar; hitung ulang tonase tidak, karena hasilnya hanya
        // bergantung pada pembacaan yang sudah ada. Karena itu penjadwal
        // memakai mode ini.
        if ($this->option('recalculate-only')) {
            $this->info(sprintf('%d baris log dihitung ulang.', $tonnage->recalculate()));

            return self::SUCCESS;
        }

        $this->components->task('Isi nama kanonik', fn () => $this->backfillKeys($dryRun) !== null);

        // Dijalankan sebelum tonase dihitung: selama nilai COB-nya masih rusak,
        // hasil hitungannya ikut salah berapa kali pun diulang.
        [$restored, $unmatched] = $this->restoreCobFromPayload($dryRun);

        if ($restored > 0 || $unmatched > 0) {
            $this->line(sprintf(
                '  COB dipulihkan dari payload laporan: %d nilai%s',
                $restored,
                $unmatched > 0 ? sprintf(' (%d kegiatan dilewati karena jumlah barisnya tidak cocok)', $unmatched) : '',
            ));
        }

        $merged = $this->mergeDuplicateOperations($dryRun);

        // Penggabungan mengubah rentang tanggal tiap operasi kapal, jadi
        // hitungannya dilupakan sebelum aktivitas yatim dicarikan induk.
        $this->spans = null;

        $attached = $this->attachOrphanActivities($dryRun);

        // Kegiatan bongkar baru punya operasi kapal, jadi riwayat lamanya belum
        // punya induk yang bisa dicocokkan — induknya dibentuk di sini.
        [$created, $linked] = $this->createOperationsForUnlinkedUnloading($dryRun);

        if ($dryRun) {
            $this->newLine();
            $this->line(sprintf(
                'Rencana: %d operasi kapal digabung, %d aktivitas disambungkan, %d operasi bongkar dibentuk untuk %d baris.',
                $merged,
                $attached,
                $created,
                $linked,
            ));

            return self::SUCCESS;
        }

        $written = $tonnage->recalculate();

        $this->newLine();
        $this->info(sprintf(
            'Selesai: %d operasi kapal digabung, %d aktivitas disambungkan, %d operasi bongkar dibentuk untuk %d baris, %d baris log dihitung ulang.',
            $merged,
            $attached,
            $created,
            $linked,
            $written,
        ));

        $this->summarise();

        return self::SUCCESS;
    }

    /**
     * Isi ship_name_key untuk semua baris yang menyimpan nama kapal.
     */
    private function backfillKeys(bool $dryRun): ?int
    {
        $total = 0;

        // Semua tabel yang menyimpan nama kapal, bukan hanya yang punya operasi
        // kapal. Bongkar bahan baku dan container juga mencatat nama yang
        // diketik bebas, dan rekap keduanya ikut memakai nama kanonik.
        foreach ([
            'ship_operations',
            'bulk_loading_activities',
            'loading_activities',
            'material_activities',
            'container_activities',
        ] as $table) {
            DB::table($table)
                ->whereNotNull('ship_name')
                ->where('ship_name', '!=', '')
                ->orderBy('id')
                ->chunkById(500, function (Collection $rows) use ($table, $dryRun, &$total): void {
                    foreach ($rows as $row) {
                        $key = ShipNameNormalizer::key($row->ship_name) ?: null;

                        if ($key === ($row->ship_name_key ?? null)) {
                            continue;
                        }

                        $total++;

                        if (! $dryRun) {
                            DB::table($table)->where('id', $row->id)->update(['ship_name_key' => $key]);
                        }
                    }
                });
        }

        return $total;
    }

    /**
     * Gabungkan operasi kapal yang sebenarnya satu pelayaran.
     *
     * Dua operasi dianggap satu bila jenisnya sama, namanya kanonik sama atau
     * mirip, DAN rentang tanggal laporannya bersinggungan (berjarak kurang dari
     * ambang jeda pelayaran). Syarat tanggal itu yang menjaga agar kunjungan
     * kapal yang sama pada bulan berbeda tetap terhitung sebagai dua pelayaran.
     */
    private function mergeDuplicateOperations(bool $dryRun): int
    {
        $operations = ShipOperation::query()->orderBy('id')->get();
        $spans = $this->activitySpans();
        $merged = 0;

        foreach ($operations->groupBy('type') as $type => $group) {
            /** @var array<int, array{keeper: ShipOperation, key: string, start: ?Carbon, end: ?Carbon}> $clusters */
            $clusters = [];

            foreach ($group as $operation) {
                $key = ShipNameNormalizer::key($operation->ship_name);

                if ($key === '') {
                    continue;
                }

                [$start, $end] = $spans[$operation->id] ?? [null, null];
                $target = null;

                foreach ($clusters as $index => $cluster) {
                    if (! ShipNameNormalizer::isSameShip($cluster['key'], $key)) {
                        continue;
                    }

                    if (! $this->overlaps($cluster['start'], $cluster['end'], $start, $end)) {
                        continue;
                    }

                    if (! $this->workOrdersCompatible($cluster['keeper'], $operation)) {
                        continue;
                    }

                    $target = $index;

                    break;
                }

                if ($target === null) {
                    $clusters[] = ['keeper' => $operation, 'key' => $key, 'start' => $start, 'end' => $end];

                    continue;
                }

                $keeper = $clusters[$target]['keeper'];

                // Rentang tanggal ikut dicetak supaya penggabungan bisa dinilai:
                // dua baris yang tanggalnya bersambung memang satu kunjungan,
                // sedangkan yang berjauhan patut dicurigai.
                $this->line(sprintf(
                    '  gabung [%s] #%d "%s" %s → #%d "%s" %s',
                    $type,
                    $operation->id,
                    $operation->ship_name,
                    $this->spanText($start, $end),
                    $keeper->id,
                    $keeper->ship_name,
                    $this->spanText($clusters[$target]['start'], $clusters[$target]['end']),
                ));

                $merged++;

                if (! $dryRun) {
                    $this->repoint($operation->id, $keeper->id);
                }

                $clusters[$target]['start'] = $this->earliest($clusters[$target]['start'], $start);
                $clusters[$target]['end'] = $this->latest($clusters[$target]['end'], $end);
            }
        }

        return $merged;
    }

    /**
     * Rentang tanggal laporan sebuah operasi kapal, untuk dibaca manusia.
     */
    private function spanText(?Carbon $start, ?Carbon $end): string
    {
        if ($start === null || $end === null) {
            return '(tanpa laporan)';
        }

        return $start->isSameDay($end)
            ? '('.$start->format('d/m').')'
            : '('.$start->format('d/m').'–'.$end->format('d/m').')';
    }

    /**
     * Nomor WO menahan penggabungan yang terlalu jauh.
     *
     * Pada pemuatan kantong, nomor WO menandai pekerjaan yang berbeda. Kapal
     * yang sama dengan WO yang sama sekali berlainan berarti dua pekerjaan,
     * bukan satu pelayaran yang namanya salah ketik. Sebaliknya, WO yang saling
     * memuat ("3460429762" dan "3460429762, 3460431016" — WO kedua ditambahkan
     * di tengah pemuatan) tetap boleh disatukan.
     */
    private function workOrdersCompatible(ShipOperation $keeper, ShipOperation $candidate): bool
    {
        if ($keeper->type !== ShipOperation::TYPE_BAG_LOADING) {
            return true;
        }

        $left = $this->workOrderNumbers($keeper->wo_number);
        $right = $this->workOrderNumbers($candidate->wo_number);

        if ($left === [] || $right === []) {
            return true;
        }

        return array_intersect($left, $right) !== [];
    }

    /**
     * @return array<int, string>
     */
    private function workOrderNumbers(?string $value): array
    {
        preg_match_all('/\d{6,}/', (string) $value, $matches);

        return array_values(array_unique($matches[0]));
    }

    /**
     * Pindahkan seluruh aktivitas ke operasi yang dipertahankan, lalu buang
     * operasi kembar tersebut.
     */
    private function repoint(int $from, int $to): void
    {
        DB::transaction(function () use ($from, $to): void {
            foreach (self::ACTIVITY_TABLES as $table) {
                DB::table($table)->where('ship_operation_id', $from)->update(['ship_operation_id' => $to]);
            }

            $keeper = ShipOperation::find($to);
            $loser = ShipOperation::find($from);

            // Operasi yang dipertahankan mewarisi keterangan terbaru: nama yang
            // terakhir diketik dan waktu laporan terakhir. Data yang kosong di
            // salah satunya diisi dari pasangannya.
            if ($keeper && $loser) {
                foreach (['agent', 'jetty', 'destination', 'wo_number', 'cargo_type', 'marking', 'stevedoring', 'commodity', 'arrival_time', 'berthing_time', 'start_loading_time'] as $column) {
                    if (blank($keeper->{$column}) && filled($loser->{$column})) {
                        $keeper->{$column} = $loser->{$column};
                    }
                }

                if ((float) $loser->capacity > (float) $keeper->capacity) {
                    $keeper->capacity = $loser->capacity;
                }

                if ($loser->last_report_date && (! $keeper->last_report_date || $loser->last_report_date > $keeper->last_report_date)) {
                    $keeper->last_report_date = $loser->last_report_date;
                    $keeper->last_report_id = $loser->last_report_id;
                    $keeper->ship_name = $loser->ship_name;
                }

                if ($loser->status === ShipOperation::STATUS_COMPLETED) {
                    $keeper->status = ShipOperation::STATUS_COMPLETED;
                    $keeper->completed_at ??= $loser->completed_at;
                    $keeper->completed_by ??= $loser->completed_by;
                }

                $keeper->ship_name_key = ShipNameNormalizer::key($keeper->ship_name) ?: null;
                $keeper->save();
            }

            ShipOperation::whereKey($from)->delete();
        });
    }

    /**
     * Pulihkan nilai COB yang titik desimalnya terbuang saat disimpan.
     *
     * Pembantu integer() yang lama membuang titik desimal, bukan angka di
     * belakangnya, sehingga "4420.25" tersimpan sebagai 442025 dan "16.75"
     * menjadi 1675. Faktornya berbeda-beda menurut jumlah angka di belakang
     * koma, jadi tidak bisa ditebak dari nilainya sendiri.
     *
     * Untungnya tidak perlu menebak: isian form aslinya masih tersimpan utuh
     * pada daily_reports.payload. Nilai itulah yang dibaca ulang di sini.
     *
     * Baris log dipasangkan dengan baris payload memakai penyaring yang sama
     * dengan controller (baris dianggap ada bila salah satu dari jam, aktivitas,
     * atau COB terisi), lalu diurutkan menurut id. Bila jumlahnya tidak cocok,
     * aktivitas itu dilewati — lebih baik membiarkan satu angka apa adanya
     * daripada memasangkannya ke baris yang keliru.
     *
     * @return array{0: int, 1: int} jumlah nilai dipulihkan dan aktivitas dilewati
     */
    private function restoreCobFromPayload(bool $dryRun): array
    {
        $restored = 0;
        $skipped = 0;

        DB::table('bulk_loading_activities')
            ->join('daily_reports', 'daily_reports.id', '=', 'bulk_loading_activities.daily_report_id')
            ->whereNotNull('daily_reports.payload')
            ->orderBy('bulk_loading_activities.id')
            ->select([
                'bulk_loading_activities.id',
                'bulk_loading_activities.sequence',
                'bulk_loading_activities.activity_type',
                'daily_reports.payload',
            ])
            ->chunk(200, function (Collection $activities) use ($dryRun, &$restored, &$skipped): void {
                foreach ($activities as $activity) {
                    $typed = $this->typedCobValues($activity);

                    if ($typed === null) {
                        continue;
                    }

                    $logs = DB::table('bulk_loading_logs')
                        ->where('bulk_loading_activity_id', $activity->id)
                        ->orderBy('id')
                        ->get(['id', 'cob']);

                    if ($logs->count() !== count($typed)) {
                        $skipped++;

                        continue;
                    }

                    foreach ($logs as $index => $log) {
                        $value = $typed[$index];
                        $stored = $log->cob === null ? null : (float) $log->cob;

                        if ($value === $stored) {
                            continue;
                        }

                        $restored++;

                        if (! $dryRun) {
                            DB::table('bulk_loading_logs')
                                ->where('id', $log->id)
                                ->update(['cob' => $value]);
                        }
                    }
                }
            });

        return [$restored, $skipped];
    }

    /**
     * Nilai COB apa adanya sesuai yang diketik petugas, urut seperti baris log
     * yang dibuat controller. null bila payload-nya tidak memuat kegiatan ini.
     *
     * @return array<int, float|null>|null
     */
    private function typedCobValues(object $activity): ?array
    {
        $fields = $this->payloadFields($activity->payload);

        if ($fields === []) {
            return null;
        }

        $prefix = $activity->activity_type === ShipOperation::TYPE_AMMONIA_LOADING
            ? 'ammonia_logs'
            : 'bulk_logs';

        $rows = [];

        foreach ($fields as $key => $value) {
            if (! preg_match('/^'.$prefix.'\['.((int) $activity->sequence).'\]\[(\d+)\]\[(time|activity|cob)\]$/', $key, $match)) {
                continue;
            }

            $rows[(int) $match[1]][$match[2]] = $value;
        }

        if ($rows === []) {
            return null;
        }

        ksort($rows);

        $values = [];

        foreach ($rows as $row) {
            // Penyaring yang sama dengan storeBulkLoadingActivities().
            $filled = array_filter(
                [$row['time'] ?? null, $row['activity'] ?? null, $row['cob'] ?? null],
                static fn (mixed $item): bool => $item !== null && trim((string) $item) !== '',
            );

            if ($filled === []) {
                continue;
            }

            $values[] = TonnageNumber::reading($row['cob'] ?? null);
        }

        return $values;
    }

    /**
     * Payload laporan sebagai peta kunci → nilai. Bentuk simpanannya adalah
     * daftar pasangan {key, value}, sama seperti isian form yang dikirim.
     *
     * @return array<string, string>
     */
    private function payloadFields(mixed $payload): array
    {
        $decoded = is_string($payload) ? json_decode($payload, true) : $payload;

        if (! is_array($decoded) || ! is_array($decoded['fields'] ?? null)) {
            return [];
        }

        $fields = [];

        foreach ($decoded['fields'] as $field) {
            if (is_array($field) && isset($field['key'])) {
                $fields[(string) $field['key']] = (string) ($field['value'] ?? '');
            }
        }

        return $fields;
    }

    /**
     * Bentuk operasi kapal untuk kegiatan bongkar yang belum punya induk.
     *
     * Laporan bongkar yang ditulis sebelum kegiatan ini punya operasi kapal
     * tidak akan pernah tersambung oleh attachOrphanActivities(), karena memang
     * tidak ada operasi yang bisa dicocokkan. Di sini riwayatnya dibentuk ulang:
     * baris dikelompokkan menurut nama kanonik, lalu dipotong menjadi kunjungan
     * terpisah setiap kali jedanya melewati ambang pelayaran.
     *
     * @return array{0: int, 1: int} jumlah operasi dibuat dan baris tersambung
     */
    private function createOperationsForUnlinkedUnloading(bool $dryRun): array
    {
        $created = 0;
        $linked = 0;

        foreach ([
            'material_activities' => ShipOperation::TYPE_MATERIAL_UNLOADING,
            'container_activities' => ShipOperation::TYPE_CONTAINER,
        ] as $table => $type) {
            $rows = DB::table($table)
                ->join('daily_reports', 'daily_reports.id', '=', $table.'.daily_report_id')
                ->whereNull($table.'.ship_operation_id')
                ->whereNotNull($table.'.ship_name')
                ->where($table.'.ship_name', '!=', '')
                ->orderBy('daily_reports.report_date')
                ->orderBy($table.'.id')
                ->get([
                    $table.'.id',
                    $table.'.ship_name',
                    $table.'.ship_name_key',
                    $table.'.agent',
                    $table.'.jetty',
                    $table.'.capacity',
                    'daily_reports.id as report_id',
                    'daily_reports.report_date',
                ]);

            /** @var array<string, array{operation: ?ShipOperation, last: ?Carbon}> $open */
            $open = [];

            foreach ($rows as $row) {
                $key = (string) ($row->ship_name_key ?: ShipNameNormalizer::key($row->ship_name));

                if ($key === '') {
                    continue;
                }

                $at = $row->report_date ? Carbon::parse($row->report_date) : null;
                $current = $open[$key] ?? null;
                $expired = $current === null
                    || ($current['last'] && $at && abs($current['last']->diffInDays($at)) >= self::gapDays());

                if ($expired) {
                    $created++;

                    $operation = $dryRun ? null : ShipOperation::create([
                        'type' => $type,
                        'status' => ShipOperation::STATUS_INACTIVE,
                        'ship_name' => $row->ship_name,
                        'ship_name_key' => $key,
                        'agent' => $row->agent,
                        'jetty' => $row->jetty,
                        'capacity' => $row->capacity,
                        'started_at' => $at,
                        'last_report_id' => $row->report_id,
                        'last_report_date' => $at?->toDateString(),
                    ]);

                    $open[$key] = ['operation' => $operation, 'last' => $at];
                    $current = $open[$key];
                }

                $linked++;
                $open[$key]['last'] = $at ?? $open[$key]['last'];

                if ($dryRun || ! $current['operation']) {
                    continue;
                }

                DB::table($table)->where('id', $row->id)->update([
                    'ship_operation_id' => $current['operation']->id,
                ]);

                // Keterangan terbaru menang, dan kapasitas terbesar dipertahankan
                // — kapal yang bongkarnya bertambah di tengah kunjungan tidak
                // boleh kehilangan angka acuannya.
                $current['operation']->forceFill([
                    'ship_name' => $row->ship_name,
                    'ship_name_key' => $key,
                    'agent' => $row->agent ?: $current['operation']->agent,
                    'jetty' => $row->jetty ?: $current['operation']->jetty,
                    'capacity' => max((float) $row->capacity, (float) $current['operation']->capacity),
                    'last_report_id' => $row->report_id,
                    'last_report_date' => $at?->toDateString(),
                ])->save();
            }
        }

        return [$created, $linked];
    }

    /**
     * Sambungkan aktivitas yang tersimpan tanpa operasi kapal.
     *
     * Inilah jejak "petugas tidak menekan simpan operasi kapal": barisnya ada,
     * tonasenya ada, tetapi tidak terikat pelayaran mana pun.
     */
    private function attachOrphanActivities(bool $dryRun): int
    {
        $attached = 0;

        foreach (self::ACTIVITY_TABLES as $table) {
            // Hanya muat curah/amoniak yang jenisnya ditentukan per baris
            // (activity_type); tabel lain sudah pasti satu jenis.
            $type = self::ACTIVITY_TYPES[$table];

            $rows = DB::table($table)
                ->join('daily_reports', 'daily_reports.id', '=', $table.'.daily_report_id')
                ->whereNull($table.'.ship_operation_id')
                ->whereNotNull($table.'.ship_name')
                ->where($table.'.ship_name', '!=', '')
                ->orderBy($table.'.id')
                ->get([
                    $table.'.id',
                    $table.'.ship_name',
                    $table.'.ship_name_key',
                    'daily_reports.report_date',
                    ...($type === null ? [$table.'.activity_type'] : []),
                ]);

            foreach ($rows as $row) {
                $operation = $this->findOperationFor(
                    $type ?? (string) ($row->activity_type ?? ShipOperation::TYPE_BULK_LOADING),
                    (string) $row->ship_name,
                    $row->report_date ? Carbon::parse($row->report_date) : null,
                );

                if (! $operation) {
                    continue;
                }

                $attached++;

                if (! $dryRun) {
                    DB::table($table)->where('id', $row->id)->update(['ship_operation_id' => $operation]);
                }
            }
        }

        return $attached;
    }

    /**
     * Operasi kapal mana yang sedang berjalan untuk nama ini pada tanggal itu.
     */
    private function findOperationFor(string $type, string $shipName, ?Carbon $at): ?int
    {
        $key = ShipNameNormalizer::key($shipName);

        if ($key === '') {
            return null;
        }

        $spans = $this->activitySpans();

        $best = null;

        foreach (ShipOperation::query()->where('type', $type)->get() as $operation) {
            if (! ShipNameNormalizer::isSameShip($operation->ship_name, $shipName)) {
                continue;
            }

            [$start, $end] = $spans[$operation->id] ?? [null, null];

            if ($at !== null && $start !== null && $end !== null) {
                $window = self::gapDays();

                if ($at->lt($start->copy()->subDays($window)) || $at->gt($end->copy()->addDays($window))) {
                    continue;
                }
            }

            $best = $operation->id;

            break;
        }

        return $best;
    }

    /**
     * Rentang tanggal laporan tiap operasi kapal, dihitung sekali lalu dipakai
     * ulang — jumlah operasi bisa ratusan dan pemeriksaannya berpasangan.
     *
     * @return array<int, array{0: ?Carbon, 1: ?Carbon}>
     */
    private function activitySpans(): array
    {
        if ($this->spans !== null) {
            return $this->spans;
        }

        $cache = [];

        foreach (self::ACTIVITY_TABLES as $table) {
            $rows = DB::table($table)
                ->join('daily_reports', 'daily_reports.id', '=', $table.'.daily_report_id')
                ->whereNotNull($table.'.ship_operation_id')
                ->groupBy($table.'.ship_operation_id')
                ->get([
                    DB::raw($table.'.ship_operation_id as operation_id'),
                    DB::raw('MIN(daily_reports.report_date) as first_date'),
                    DB::raw('MAX(daily_reports.report_date) as last_date'),
                ]);

            foreach ($rows as $row) {
                $id = (int) $row->operation_id;
                $start = $row->first_date ? Carbon::parse($row->first_date) : null;
                $end = $row->last_date ? Carbon::parse($row->last_date) : null;

                $cache[$id] = [
                    $this->earliest($cache[$id][0] ?? null, $start),
                    $this->latest($cache[$id][1] ?? null, $end),
                ];
            }
        }

        return $this->spans = $cache;
    }

    private function overlaps(?Carbon $aStart, ?Carbon $aEnd, ?Carbon $bStart, ?Carbon $bEnd): bool
    {
        // Operasi tanpa aktivitas apa pun (mis. saran yang tak pernah dipakai)
        // tidak punya rentang; biarkan nama yang menentukan.
        if ($aStart === null || $aEnd === null || $bStart === null || $bEnd === null) {
            return true;
        }

        $window = self::gapDays();

        return $bStart->lte($aEnd->copy()->addDays($window))
            && $aStart->lte($bEnd->copy()->addDays($window));
    }

    private function earliest(?Carbon $a, ?Carbon $b): ?Carbon
    {
        if ($a === null || $b === null) {
            return $a ?? $b;
        }

        return $a->lte($b) ? $a : $b;
    }

    private function latest(?Carbon $a, ?Carbon $b): ?Carbon
    {
        if ($a === null || $b === null) {
            return $a ?? $b;
        }

        return $a->gte($b) ? $a : $b;
    }

    private static function gapDays(): int
    {
        return BulkTonnageService::VOYAGE_GAP_DAYS;
    }

    /**
     * Ringkasan tonase per pelayaran sesudah perbaikan, sebagai bukti angka.
     */
    private function summarise(): void
    {
        $rows = DB::table('bulk_loading_logs')
            ->join('bulk_loading_activities', 'bulk_loading_logs.bulk_loading_activity_id', '=', 'bulk_loading_activities.id')
            ->join('daily_reports', 'daily_reports.id', '=', 'bulk_loading_activities.daily_report_id')
            ->leftJoin('ship_operations', 'ship_operations.id', '=', 'bulk_loading_activities.ship_operation_id')
            ->groupBy('bulk_loading_activities.ship_operation_id', 'bulk_loading_activities.ship_name_key')
            ->orderByRaw('MIN(daily_reports.report_date)')
            ->get([
                DB::raw('COALESCE(MAX(ship_operations.ship_name), MAX(bulk_loading_activities.ship_name)) as ship_name'),
                DB::raw('MAX(bulk_loading_activities.capacity) as capacity'),
                DB::raw('SUM(bulk_loading_logs.cob) as sum_cob'),
                DB::raw('SUM(bulk_loading_logs.cob_delta) as tonnage'),
                DB::raw('MIN(daily_reports.report_date) as first_date'),
                DB::raw('MAX(daily_reports.report_date) as last_date'),
            ]);

        if ($rows->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->table(
            ['Kapal', 'Kapasitas', 'SUM(cob) — cara lama', 'Tonase — cara baru', 'Periode'],
            $rows->map(fn ($row): array => [
                $row->ship_name,
                number_format((float) $row->capacity, 0, ',', '.'),
                number_format((float) $row->sum_cob, 0, ',', '.'),
                number_format((float) $row->tonnage, 2, ',', '.'),
                Carbon::parse($row->first_date)->format('d/m').' – '.Carbon::parse($row->last_date)->format('d/m'),
            ])->all(),
        );

        $this->line(sprintf(
            'Total: %s ton (cara lama %s ton).',
            number_format((float) $rows->sum(fn ($row): float => (float) $row->tonnage), 2, ',', '.'),
            number_format((float) $rows->sum(fn ($row): float => (float) $row->sum_cob), 0, ',', '.'),
        ));
    }
}
