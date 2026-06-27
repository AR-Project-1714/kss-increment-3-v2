<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sumber kebenaran TUNGGAL untuk master karyawan KSS.
 *
 * Seluruh divisi digabung di sini (Safety, Office, Workshop/Pemeliharaan,
 * Operasi Group A-D, Relief, dan OP.7) sesuai daftar resmi. Idempotent:
 * - karyawan ber-NPK dicocokkan via NPK (upsert).
 * - karyawan tanpa NPK dicocokkan via nama (updateOrInsert).
 *
 * Konvensi kolom:
 * - group_name : 'Group A'..'Group D', 'OP.7 Group A'..'D', 'Bengkel',
 *                'Relief 1'/'Relief 2', atau null (Kantor/Office).
 * - division   : 'operasional' | 'pemeliharaan' | 'safety'.
 * - work_time  : 'Shift' | 'Non Shift' | 'Relief'.
 */
class MasterEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // [npk, name, group_name, position, division, work_time]
        $rows = array_merge(
            $this->safety(),
            $this->officeOperasi(),
            $this->workshop(),
            $this->relief(),
            $this->operationalGroups(),
            $this->op7Groups(),
            $this->office(),
        );

        $withNpk = [];
        $withoutNpk = [];

        foreach ($rows as [$npk, $name, $group, $position, $division, $workTime]) {
            $payload = [
                'npk' => $npk,
                'name' => $name,
                'group_name' => $group,
                'position' => $position,
                'division' => $division,
                'work_time' => $workTime,
                'status' => 'active',
                'updated_at' => $now,
            ];

            if ($npk !== null) {
                // Adopsi baris lama tanpa NPK bernama sama (mis. sisa migrasi
                // transisi pemeliharaan) supaya upsert-by-NPK memperbaruinya,
                // bukan menyisakan duplikat. Aman: hanya menyentuh baris ber-NPK
                // null, tidak pernah memindahkan baris yang sudah punya NPK lain.
                DB::table('master_employees')
                    ->where('name', $name)
                    ->whereNull('npk')
                    ->update(['npk' => $npk, 'updated_at' => $now]);

                $withNpk[] = $payload + ['created_at' => $now];
            } else {
                $withoutNpk[] = $payload;
            }
        }

        if ($withNpk !== []) {
            DB::table('master_employees')->upsert(
                $withNpk,
                ['npk'],
                ['name', 'group_name', 'position', 'division', 'work_time', 'status', 'updated_at']
            );
        }

        // Karyawan tanpa NPK: cocokkan via nama agar tetap idempotent.
        foreach ($withoutNpk as $payload) {
            DB::table('master_employees')->updateOrInsert(
                ['name' => $payload['name']],
                $payload + ['created_at' => $now]
            );
        }
    }

    /** Divisi Safety (K3). */
    private function safety(): array
    {
        return [
            ['2000.1.010', 'Asmuni Syukur', null, 'Karu', 'safety', 'Non Shift'],
            ['2000.1.016', 'Usman', null, 'Karu', 'safety', 'Non Shift'],
        ];
    }

    /** Office Operasi (struktural, divisi office). */
    private function officeOperasi(): array
    {
        return [
            ['2000.1.005', 'Mustari, ST', null, 'Manager', 'office', 'Non Shift'],
            ['2000.1.012', 'Susianto', null, 'Kabag', 'office', 'Non Shift'],
            ['2000.1.008', 'Sabaruddin', null, 'Kasi', 'office', 'Non Shift'],
            ['2020.1.070', 'Frestiani Soeharsono P', null, 'Staf Ahli', 'office', 'Non Shift'],
            ['2024.K.056', 'Sukirman', null, 'Staf', 'office', 'Non Shift'],
        ];
    }

    /** Divisi Workshop / Pemeliharaan (Bengkel). */
    private function workshop(): array
    {
        return [
            ['2000.1.007', 'Sungkono', 'Bengkel', 'Kasi Pemeliharaan & Peralatan', 'pemeliharaan', 'Non Shift'],
            ['2008.1.058', 'Achmad Saiful Anwari', 'Bengkel', 'Karu Pemeliharaan', 'pemeliharaan', 'Non Shift'],
            ['2023.K.017', 'Usman', 'Bengkel', 'Mekanik', 'pemeliharaan', 'Non Shift'],
            ['2023.K.035', 'Arman', 'Bengkel', 'Mekanik', 'pemeliharaan', 'Non Shift'],
            [null, 'Usriadi', 'Bengkel', 'Helper', 'pemeliharaan', 'Non Shift'],
            ['2024.K.058', 'Muhammad Suaiban', 'Bengkel', 'Helper', 'pemeliharaan', 'Non Shift'],
            ['2024.K.059', 'Rahul', 'Bengkel', 'Helper', 'pemeliharaan', 'Non Shift'],
            [null, 'Fakhruddin', 'Bengkel', 'Helper', 'pemeliharaan', 'Non Shift'],
            ['2003.1.031', 'Akhmad Yani Siregar', 'Bengkel', 'Karu Peralatan', 'pemeliharaan', 'Non Shift'],
            ['2006.1.049', 'Amiruddin', 'Bengkel', 'Checker', 'pemeliharaan', 'Non Shift'],
            ['2003.1.038', 'Rizal Paselleri', 'Bengkel', 'Driver', 'pemeliharaan', 'Non Shift'],
            ['2004.1.044', 'Nasrayuddin', 'Bengkel', 'Driver', 'pemeliharaan', 'Non Shift'],
            ['2023.K.020', 'Supriadi Budianto', 'Bengkel', 'Operator WL/ Exca', 'pemeliharaan', 'Non Shift'],
            ['2023.K.019', 'Irfan Teguh Andriyanto', 'Bengkel', 'Operator Exca/ WL', 'pemeliharaan', 'Non Shift'],
        ];
    }

    /** Relief 1 & Relief 2 (operasional, jam kerja Relief). */
    private function relief(): array
    {
        return [
            ['2025.K.064', 'Rahardian Efendi', 'Relief 1', 'Driver', 'operasional', 'Relief'],
            ['2023.K.021', 'Hermanto Susanto', 'Relief 1', null, 'operasional', 'Relief'],
            ['2023.K.027', 'Muhammad Ardi', 'Relief 1', 'Operator FL', 'operasional', 'Relief'],

            ['2025.K.063', 'Awaluddin Fitroh', 'Relief 2', 'Driver', 'operasional', 'Relief'],
            ['2023.K.033', 'Usaid Nur Rachman', 'Relief 2', 'Operator FL', 'operasional', 'Relief'],
            ['2024.K.057', 'Abdul Khair', 'Relief 2', 'Operator FL', 'operasional', 'Relief'],
        ];
    }

    /** Divisi Operasi Shift Regular (Group A-D). */
    private function operationalGroups(): array
    {
        return [
            // Group A
            ['2002.1.024', 'Jhon Maradona Mailoor', 'Group A', 'Kepala Regu ( KARU )', 'operasional', 'Shift'],
            ['2003.1.030', 'Zainuddin', 'Group A', 'Wakil Kepala Regu', 'operasional', 'Shift'],
            ['2006.1.050', 'Mustafa', 'Group A', 'Checker', 'operasional', 'Shift'],
            ['2008.1.055', 'Asri Sahibu', 'Group A', 'Checker', 'operasional', 'Shift'],
            ['2025.K.065', 'Boyska Albian', 'Group A', 'Operator FL', 'operasional', 'Shift'],
            ['2023.K.029', 'Muhammad Zein Al-Fiqri', 'Group A', 'Operator FL', 'operasional', 'Shift'],
            ['2023.K.034', 'Zulkifli A', 'Group A', 'Operator FL', 'operasional', 'Shift'],
            ['2025.K.066', 'Jenri Tangruru', 'Group A', 'Driver', 'operasional', 'Shift'],
            ['2023.K.011', 'Samsul Zainuddin', 'Group A', 'Driver', 'operasional', 'Shift'],
            ['2025.K.062', 'Arlis', 'Group A', 'Driver', 'operasional', 'Shift'],
            ['2023.K.041', 'Musliady', 'Group A', 'Operator FL', 'operasional', 'Shift'],
            ['2023.K.043', 'Rifky Rana Juliansyah', 'Group A', 'Operator FL', 'operasional', 'Shift'],

            // Group B
            ['2002.1.028', 'Nurul Huda', 'Group B', 'Kepala Regu ( KARU )', 'operasional', 'Shift'],
            ['2004.1.043', 'Ryman Oloan Manurung', 'Group B', 'Wakil Kepala Regu', 'operasional', 'Shift'],
            ['2006.1.051', 'Mulyadi', 'Group B', 'Checker', 'operasional', 'Shift'],
            ['2006.1.052', 'Ruben Marbun', 'Group B', 'Checker', 'operasional', 'Shift'],
            ['2023.K.026', 'Muh. Agung Hidayat Sinaga', 'Group B', 'Operator FL', 'operasional', 'Shift'],
            ['2023.K.032', 'Ronaldes Romba Pratama', 'Group B', 'Operator FL', 'operasional', 'Shift'],
            ['2005.1.047', 'Ahmad Nur', 'Group B', 'Operator FL', 'operasional', 'Shift'],
            ['2023.K.001', 'Freddy Widiarto', 'Group B', 'Driver', 'operasional', 'Shift'],
            ['2025.1.072', 'Agus Ibnu Thufail', 'Group B', 'Driver', 'operasional', 'Shift'],
            [null, 'Agus Hendra Jaya', 'Group B', 'Driver', 'operasional', 'Shift'],
            [null, 'Andre Oktavianus Damanik', 'Group B', 'Operator FL', 'operasional', 'Shift'],
            ['2023.K.037', 'Habibi', 'Group B', 'Operator FL', 'operasional', 'Shift'],

            // Group C
            ['2001.1.020', 'Jawawi', 'Group C', 'Kepala Regu ( KARU )', 'operasional', 'Shift'],
            [null, 'Ahmad Bisri', 'Group C', 'Wakil Kepala Regu', 'operasional', 'Shift'],
            ['2023.K.030', 'Mus Fajry', 'Group C', 'Checker', 'operasional', 'Shift'],
            ['2023.K.004', 'Hamsyah', 'Group C', 'Checker', 'operasional', 'Shift'],
            ['2008.1.056', 'Edi Irawan', 'Group C', 'Operator FL', 'operasional', 'Shift'],
            ['2023.K.031', 'Prasetya Perdana', 'Group C', 'Operator FL', 'operasional', 'Shift'],
            ['2023.K.028', 'M. Ikram Jaya Pratama', 'Group C', 'Operator FL', 'operasional', 'Shift'],
            ['2023.K.013', 'Usman DT', 'Group C', 'Driver', 'operasional', 'Shift'],
            ['2023.K.012', 'Sudirman', 'Group C', 'Driver', 'operasional', 'Shift'],
            ['2023.K.025', 'H. Usman Hasan', 'Group C', 'Driver', 'operasional', 'Shift'],
            ['2023.K.036', 'Agil Akbar', 'Group C', 'Operator FL', 'operasional', 'Shift'],
            ['2023.K.039', 'Muhammad Ichsanul Yakin', 'Group C', 'Operator FL', 'operasional', 'Shift'],

            // Group D
            ['2001.1.021', 'Sugianto', 'Group D', 'Kepala Regu ( KARU )', 'operasional', 'Shift'],
            ['2004.1.045', 'Syamsuddin R', 'Group D', 'Wakil Kepala Regu', 'operasional', 'Shift'],
            ['2008.1.057', 'Yakop Bendon', 'Group D', 'Checker', 'operasional', 'Shift'],
            ['2023.K.006', 'Saddam Hassanuddin', 'Group D', 'Checker', 'operasional', 'Shift'],
            ['2005.1.048', 'Wirawan', 'Group D', 'Operator FL', 'operasional', 'Shift'],
            ['2025.1.071', 'Jefri Parianto', 'Group D', 'Operator FL', 'operasional', 'Shift'],
            ['2023.K.042', 'Syamrisal', 'Group D', 'Operator FL', 'operasional', 'Shift'],
            ['2023.K.024', 'Dony Amping', 'Group D', 'Driver', 'operasional', 'Shift'],
            ['2024.K.055', 'Abd Rahim', 'Group D', 'Driver', 'operasional', 'Shift'],
            ['2025.K.061', 'Abdul Azis', 'Group D', 'Driver', 'operasional', 'Shift'],
            ['2023.K.038', 'Muhammad Fadli', 'Group D', 'Operator FL', 'operasional', 'Shift'],
            ['2023.K.040', 'Muhammad Reza Al Habsyi', 'Group D', 'Operator FL', 'operasional', 'Shift'],
        ];
    }

    /** Divisi OP.7 (Group A-D, semua Operator FL). */
    private function op7Groups(): array
    {
        $groups = [
            'A' => [
                ['2025.K.004', 'Aziz Bukhari Surya'],
                ['2025.K.007', 'Juprianto'],
                ['2025.K.020', 'Ahmad Faitzal'],
                ['2025.K.003', 'Ashar'],
                ['2025.K.022', 'Ediansyah'],
                ['2025.K.002', 'Artanto Adhiguna'],
                ['2025.K.024', 'Kiki Arifin Saputra'],
                ['2025.K.005', 'Firman'],
                ['2025.K.021', 'Aji Faitsal'],
                ['2025.K.023', 'Edi Sutomo'],
            ],
            'B' => [
                ['2025.K.017', 'Sutrisno Sikombong'],
                ['2025.K.009', 'Julyo Gabriel H'],
                ['2025.K.028', 'Muhammad Azhar Fadli Sinaga'],
                ['2025.K.040', 'Yohanes Woge'],
                ['2025.K.038', 'Wahyu Karim'],
                ['2025.K.010', 'Junaedi'],
                ['2025.K.027', 'Muhammad Abdul Salim'],
                ['2025.K.018', 'Wahyudi Eko Saputro'],
                ['2025.K.008', 'Imam Buchori'],
                ['2023.K.018', 'Ilham'],
            ],
            'C' => [
                ['2025.K.001', 'Ali Murdani'],
                ['2025.K.013', 'Muhammad Dandi'],
                ['2025.K.016', 'Sholaiman'],
                ['2025.K.026', 'Muhammad Bakri'],
                ['2025.K.029', 'Muhammad Dwian Jaya Grahita'],
                ['2025.K.030', 'Muhammad Fikirianur'],
                ['2025.K.019', 'Yaser Daniel'],
                ['2025.K.011', 'Mochamad Agita'],
                ['2025.K.012', 'Muhammad Amar M'],
                [null, 'Abd. Aziz'],
            ],
            'D' => [
                ['2025.K.014', 'Muhammad Ridwan'],
                ['2025.K.037', 'Samsir'],
                ['2025.K.039', 'Yodi Fathir Ahmad Nasir'],
                ['2025.K.033', 'Randi Satrio Wijaksana'],
                ['2025.K.034', 'Rusbandi'],
                ['2025.K.015', 'Muhammad Rizki'],
                ['2025.K.036', 'Salama'],
                ['2025.K.032', 'Muhammad Nurdin'],
                ['2025.K.006', 'Herwin Saputra'],
                ['2025.K.035', 'Rustam'],
            ],
        ];

        $rows = [];
        foreach ($groups as $code => $members) {
            foreach ($members as [$npk, $name]) {
                $rows[] = [$npk, $name, 'OP.7 Group '.$code, 'Operator FL', 'operasional', 'Shift'];
            }
        }

        return $rows;
    }

    /** Divisi Office umum (administrasi, divisi office). */
    private function office(): array
    {
        return [
            ['2001.1.022', 'Sulfa Salombe SR', null, null, 'office', 'Non Shift'],
            ['2024.K.023', 'Arby Surya Kus\'indianto', null, null, 'office', 'Non Shift'],
            ['2024.K.060', 'Sultan', null, null, 'office', 'Non Shift'],
            ['2014.1.069', 'Sherley Diah Anggraeni', null, null, 'office', 'Non Shift'],
            ['2024.K.044', 'Naufal Noor Fauzi', null, null, 'office', 'Non Shift'],
            ['2004.1.041', 'Andriastuti', null, null, 'office', 'Non Shift'],
            ['2002.1.023', 'Murni Kristin', null, null, 'office', 'Non Shift'],
        ];
    }
}
