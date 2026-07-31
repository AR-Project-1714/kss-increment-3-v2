<?php

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Http\Controllers\ReportOpsController;
use App\Models\DailyReport;
use App\Models\ShipOperation;
use App\Models\User;
use App\Services\BulkTonnageService;
use Carbon\Carbon;
use Database\Seeders\Concerns\GuardsSampleData;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Laporan operasi nyata hasil backup produksi (8-29 Juli 2026).
 *
 * Berbeda dengan OperationalReportSeeder yang membuat data contoh sintetis,
 * seeder ini MEMUTAR ULANG payload form asli apa adanya: setiap laporan pada
 * berkas backup dikirim kembali lewat ReportOpsController::store(), yaitu jalur
 * simpan yang sama persis dengan yang dipakai petugas. Karena itu seluruh
 * kebiasaan pengisian di lapangan ikut terbawa — termasuk yang keliru.
 *
 * Yang sengaja TIDAK dibersihkan (justru inilah gunanya seeder ini):
 *
 *   1. Nama kapal tidak konsisten. Satu kapal yang sama ditulis berbeda-beda
 *      antar shift — "KM. Golden Rejeki" / "KM. GOLDEN REJEKI",
 *      "KM. Malacca Strait" / "Km.Malacca Strait", "KM. Noah Asyera" /
 *      "KM.NOAH ASYERA". Enam kapal fisik menghasilkan sembilan nama.
 *   2. Fitur simpan operasi kapal sering dilewati, sehingga
 *      ship_operation_urea_id kosong dan satu pelayaran terpecah menjadi
 *      beberapa baris ship_operations.
 *   3. Angka COB (Cargo On Board) yang satuannya campur aduk — sebagian ditulis
 *      penuh (16750) dan sebagian dalam ribuan ton (16.75).
 *   4. Pembacaan COB yang dikosongkan/diisi 0 karena tidak ada penimbangan.
 *
 * Dipakai sebagai data uji untuk perbaikan perhitungan tonase muat curah;
 * lihat PERBAIKAN_TONASE_MUAT_CURAH.md.
 *
 * Idempotent: kombinasi tanggal + shift + regu yang ada di berkas dihapus lebih
 * dulu, lalu ditulis ulang dari nol.
 *
 * TIDAK didaftarkan di DatabaseSeeder. Periodenya bertabrakan dengan data
 * contoh OperationalReportSeeder, jadi dipanggil sendiri saat dibutuhkan:
 *
 *   php artisan db:seed --class=BackupOperationalReportSeeder
 */
class BackupOperationalReportSeeder extends Seeder
{
    use GuardsSampleData;

    private const DATA_FILE = __DIR__.'/data/backup-operasi-2026-07.json';

    public function run(): void
    {
        if ($this->shouldSkipSampleData()) {
            return;
        }

        $document = $this->document();
        $reports = $document['reports'] ?? [];

        if ($reports === []) {
            $this->command?->warn('Berkas backup tidak berisi laporan.');

            return;
        }

        $users = User::query()->pluck('id', 'username');
        $controller = app(ReportOpsController::class);

        $this->forget($reports);

        $written = 0;
        $skipped = [];

        foreach ($reports as $report) {
            $user = $this->resolveUser($users, $report['created_by'] ?? null);

            if (! $user) {
                $skipped[] = ($report['fields']['report_date'] ?? '?').' '.($report['fields']['shift'] ?? '?')
                    .' — pengguna "'.($report['created_by'] ?? '?').'" tidak ada';

                continue;
            }

            // Waktu dimajukan ke tanggal dinas laporan, bukan ke created_at
            // aslinya. Dua alasan: sebagian laporan produksi dibuat sehari lebih
            // awal sehingga ditolak aturan before_or_equal:today, dan tanggal
            // pada payload sudah berupa tanggal dinas hasil normalisasi — jam
            // 23.30 membuat resolveShiftStartDate() tidak menggesernya lagi
            // (batas geser shift Malam ada di jam 12.00). Efek sampingnya
            // diinginkan: umur operasi kapal ikut berjalan seperti di lapangan,
            // sehingga saran kapal yang menganggur tetap terarsip otomatis.
            Carbon::setTestNow(
                Carbon::parse($report['fields']['report_date'])->setTime(23, 30)
            );

            try {
                $controller->store($this->request($report, $user));
                $written++;
            } catch (ValidationException $exception) {
                $skipped[] = ($report['fields']['report_date'] ?? '?').' '.($report['fields']['shift'] ?? '?')
                    .' — '.implode(' ', $exception->validator->errors()->all());
            } finally {
                Carbon::setTestNow();
            }
        }

        $this->approve($document);

        // Dihitung ulang paling akhir: status laporan baru final setelah
        // approve(), sedangkan rangkaian COB hanya merangkai laporan yang
        // berstatus terkirim ke atas.
        app(BulkTonnageService::class)->recalculate();

        $this->command?->info(sprintf(
            'Backup operasi: %d laporan ditulis, %d operasi kapal terbentuk.',
            $written,
            ShipOperation::count(),
        ));

        foreach ($skipped as $line) {
            $this->command?->warn('  dilewati: '.$line);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function document(): array
    {
        $raw = @file_get_contents(self::DATA_FILE);

        if ($raw === false) {
            $this->command?->error('Berkas '.self::DATA_FILE.' tidak ditemukan.');

            return [];
        }

        return json_decode($raw, true) ?: [];
    }

    /**
     * Bersihkan hasil seeder sebelumnya supaya bisa dijalankan berulang.
     *
     * Yang dihapus hanya kombinasi tanggal + shift + regu yang memang ada di
     * berkas, bukan seluruh rentang tanggalnya, agar laporan lain pada periode
     * yang sama tidak ikut terbawa. Meski begitu seeder ini tetap tidak bisa
     * hidup berdampingan dengan OperationalReportSeeder untuk Juli 2026:
     * keduanya mengisi tanggal, shift, dan regu yang sama.
     *
     * @param  array<int, array<string, mixed>>  $reports
     */
    private function forget(array $reports): void
    {
        $triples = [];

        foreach ($reports as $report) {
            $date = $report['fields']['report_date'] ?? null;

            if ($date === null) {
                continue;
            }

            $triples[] = [
                $date,
                $report['fields']['shift'] ?? null,
                strtoupper((string) ($report['fields']['group_name'] ?? '')),
            ];
        }

        if ($triples === []) {
            return;
        }

        DB::transaction(function () use ($triples): void {
            DailyReport::query()
                ->where(function ($query) use ($triples): void {
                    foreach ($triples as [$date, $shift, $group]) {
                        $query->orWhere(function ($match) use ($date, $shift, $group): void {
                            $match->whereDate('report_date', $date)
                                ->where('shift', $shift)
                                ->where('group_name', $group);
                        });
                    }
                })
                ->get()
                ->each(static fn (DailyReport $report) => $report->delete());

            // Operasi kapal tidak ikut terhapus lewat relasi laporan: yang
            // tersisa tanpa aktivitas apa pun dibuang di sini.
            ShipOperation::query()
                ->whereDoesntHave('loadingActivities')
                ->whereDoesntHave('bulkLoadingActivities')
                ->delete();
        });
    }

    /**
     * @param  Collection<string, int>  $users
     */
    private function resolveUser(mixed $users, ?string $username): ?User
    {
        $id = $users[$username] ?? null;

        return $id ? User::find($id) : null;
    }

    /**
     * Bangun Request tiruan dari payload form yang datar.
     *
     * @param  array<string, mixed>  $report
     */
    private function request(array $report, User $user): Request
    {
        $fields = $report['fields'] ?? [];
        $input = $this->expand($fields);

        // Laporan asli memang sudah terkirim, dan beberapa regu punya dua
        // laporan sah pada tanggal dinas yang sama (mis. laporan koreksi).
        // Konfirmasi duplikat dinyalakan agar keduanya tetap masuk.
        $input['status'] = ReportStatus::Submitted->value;
        $input['confirm_duplicate'] = '1';

        // Payload adalah rekaman isian form apa adanya. Form asli selalu
        // mengirimnya, dan perintah perbaikan memakainya untuk memulihkan nilai
        // COB yang titik desimalnya pernah terbuang — jadi seeder pun harus
        // menyimpannya agar keadaan produksi tergambar utuh.
        $input['form_payload'] = json_encode([
            'fields' => array_map(
                static fn (string $key, mixed $value): array => ['key' => $key, 'value' => (string) $value],
                array_keys($fields),
                array_values($fields),
            ),
        ]);

        $request = Request::create('/report-ops', 'POST', $input);
        $request->setUserResolver(static fn (): User => $user);

        return $request;
    }

    /**
     * Ubah kunci datar bergaya form ("bulk_logs[1][0][cob]") menjadi array
     * bersarang seperti yang diterima controller dari HTTP.
     *
     * parse_str() sengaja tidak dipakai karena ia mengubah titik dan spasi pada
     * nama kunci menjadi garis bawah.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function expand(array $fields): array
    {
        $input = [];

        foreach ($fields as $key => $value) {
            if (! preg_match('/^([^\[]+)((?:\[[^\]]*\])*)$/', (string) $key, $matches)) {
                continue;
            }

            $path = [$matches[1]];

            if ($matches[2] !== '' && preg_match_all('/\[([^\]]*)\]/', $matches[2], $segments)) {
                $path = array_merge($path, $segments[1]);
            }

            $cursor = &$input;

            foreach ($path as $segment) {
                if ($segment === '') {
                    $cursor[] = [];
                    $cursor = &$cursor[array_key_last($cursor)];

                    continue;
                }

                if (! isset($cursor[$segment]) || ! is_array($cursor[$segment])) {
                    $cursor[$segment] = [];
                }

                $cursor = &$cursor[$segment];
            }

            $cursor = $value;
            unset($cursor);
        }

        return $input;
    }

    /**
     * Kembalikan status laporan seperti pada backup. Controller selalu menulis
     * "submitted"; laporan yang di produksi sudah ditandatangani dinaikkan lagi
     * ke status aslinya supaya statistik manajer menghitungnya sama.
     *
     * @param  array<string, mixed>  $document
     */
    private function approve(array $document): void
    {
        $approver = User::query()->where('username', 'manajer')->value('id');

        foreach ($document['reports'] ?? [] as $report) {
            $status = $report['status'] ?? null;

            if ($status === ReportStatus::Submitted->value || $status === null) {
                continue;
            }

            DailyReport::query()
                ->whereDate('report_date', $report['fields']['report_date'] ?? null)
                ->where('shift', $report['fields']['shift'] ?? null)
                ->where('group_name', strtoupper((string) ($report['fields']['group_name'] ?? '')))
                ->update([
                    'status' => $status,
                    'approved_by' => $status === ReportStatus::Approved->value ? $approver : null,
                    'approved_at' => $status === ReportStatus::Approved->value ? now() : null,
                ]);
        }
    }
}
