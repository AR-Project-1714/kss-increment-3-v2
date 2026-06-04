<?php

namespace App\Http\Controllers\Concerns;

/**
 * Pencarian laporan lintas kolom & relasi (kapal, bongkar, turba, unit, karyawan)
 * plus parser kata kunci tanggal berbahasa Indonesia. Dipakai bersama oleh
 * dashboard operasional (riwayat) dan manajer (arsip), yang sebelumnya menyalin
 * blok pencarian yang sama persis.
 */
trait SearchesReports
{
    /**
     * Terapkan pencarian kata kunci ke query laporan.
     *
     * @param  bool  $includeApprover  Sertakan pencocokan nama penyetuju (manajer/arsip).
     */
    protected function applyReportSearch($query, string $keyword, bool $includeApprover = false): void
    {
        if ($keyword === '') {
            return;
        }

        $like = '%'.$keyword.'%';
        $datePatterns = $this->buildDateSearchPatterns($keyword);

        if (! empty($datePatterns)) {
            $query->where(function ($dateQuery) use ($datePatterns): void {
                foreach ($datePatterns as $pattern) {
                    $dateQuery->orWhere('report_date', 'like', $pattern);
                }
            });

            return;
        }

        $query->where(function ($searchQuery) use ($keyword, $like, $includeApprover): void {
            $this->whereColumnsLike($searchQuery, [
                'shift',
                'group_name',
                'received_by_group',
                'time_range',
                'status',
                'payload',
            ], $like);

            if (preg_match('/ops[-\s]?\d{4}[-\s]?(\d+)/i', $keyword, $match)) {
                $searchQuery->orWhere('id', (int) $match[1]);
            } elseif (ctype_digit($keyword)) {
                $searchQuery->orWhere('id', (int) $keyword);
            }

            $searchQuery
                ->orWhere('report_date', 'like', $like)
                ->orWhereHas('creator', fn ($relation) => $this->whereColumnsLike($relation, ['name', 'username', 'email', 'group'], $like))
                ->orWhereHas('receiver', fn ($relation) => $this->whereColumnsLike($relation, ['name', 'username', 'email', 'group'], $like));

            if ($includeApprover) {
                $searchQuery->orWhereHas('approver', fn ($relation) => $this->whereColumnsLike($relation, ['name', 'username', 'email', 'group'], $like));
            }

            $searchQuery
                ->orWhereHas('loadingActivities', function ($relation) use ($like): void {
                    $relation->where(function ($activity) use ($like): void {
                        $this->whereColumnsLike($activity, [
                            'ship_name',
                            'agent',
                            'jetty',
                            'destination',
                            'capacity',
                            'wo_number',
                            'cargo_type',
                            'marking',
                            'operating_gang',
                            'foreman',
                            'tally_warehouse',
                            'driver_name',
                            'truck_number',
                            'tally_ship',
                            'operator_ship',
                            'forklift_ship',
                            'operator_warehouse',
                            'forklift_warehouse',
                        ], $like);

                        $activity->orWhereHas('timesheets', fn ($timesheet) => $this->whereColumnsLike($timesheet, ['category', 'time', 'activity'], $like));
                    });
                })
                ->orWhereHas('bulkLoadingActivities', function ($relation) use ($like): void {
                    $relation->where(function ($activity) use ($like): void {
                        $this->whereColumnsLike($activity, [
                            'ship_name',
                            'jetty',
                            'destination',
                            'agent',
                            'stevedoring',
                            'commodity',
                            'capacity',
                            'berthing_time',
                            'start_loading_time',
                        ], $like);

                        $activity->orWhereHas('logs', fn ($log) => $this->whereColumnsLike($log, ['datetime', 'activity', 'cob'], $like));
                    });
                })
                ->orWhereHas('materialActivity', fn ($relation) => $this->whereColumnsLike($relation, [
                    'ship_name',
                    'agent',
                    'capacity',
                    'ship_tally_names',
                    'forklift_operator_names',
                    'delivery_tally_names',
                    'driver_names',
                    'working_hours',
                ], $like))
                ->orWhereHas('materialActivity.items', fn ($relation) => $this->whereColumnsLike($relation, ['raw_material_type', 'qty_current', 'qty_prev', 'qty_total'], $like))
                ->orWhereHas('containerActivity', fn ($relation) => $this->whereColumnsLike($relation, [
                    'ship_name',
                    'agent',
                    'capacity',
                    'ship_tally_names',
                    'gudang_tally_names',
                    'driver_names',
                ], $like))
                ->orWhereHas('containerActivity.items', fn ($relation) => $this->whereColumnsLike($relation, ['time', 'qty_current', 'qty_prev', 'qty_total', 'status'], $like))
                ->orWhereHas('turbaActivity', fn ($relation) => $this->whereColumnsLike($relation, [
                    'tally_gudang_names',
                    'forklift_operator_names',
                    'driver_names',
                    'working_hours',
                ], $like))
                ->orWhereHas('turbaActivity.deliveries', fn ($relation) => $this->whereColumnsLike($relation, [
                    'truck_name',
                    'do_so_number',
                    'capacity',
                    'marking_type',
                    'qty_current',
                    'qty_prev',
                    'qty_accumulated',
                ], $like))
                ->orWhereHas('unitCheckLogs', fn ($relation) => $this->whereColumnsLike($relation, [
                    'category',
                    'item_name',
                    'master_id',
                    'fuel_level',
                    'condition_received',
                    'condition_handed_over',
                    'quantity',
                ], $like))
                ->orWhereHas('employeeLogs', fn ($relation) => $this->whereColumnsLike($relation, [
                    'category',
                    'name',
                    'no_forklift_',
                    'work_area',
                    'personil_count',
                    'time_in',
                    'time_out',
                    'work_time',
                    'description',
                ], $like));
        });
    }

    protected function whereColumnsLike($query, array $columns, string $like): void
    {
        $query->where(function ($columnQuery) use ($columns, $like): void {
            foreach ($columns as $column) {
                $columnQuery->orWhere($column, 'like', $like);
            }
        });
    }

    protected function buildDateSearchPatterns(string $keyword): array
    {
        $months = [
            'januari' => '01', 'jan' => '01', 'january' => '01',
            'februari' => '02', 'feb' => '02', 'february' => '02', 'pebruari' => '02',
            'maret' => '03', 'mar' => '03', 'march' => '03',
            'april' => '04', 'apr' => '04',
            'mei' => '05', 'may' => '05',
            'juni' => '06', 'jun' => '06', 'june' => '06',
            'juli' => '07', 'jul' => '07', 'july' => '07',
            'agustus' => '08', 'agu' => '08', 'agus' => '08', 'ags' => '08', 'august' => '08', 'aug' => '08',
            'september' => '09', 'sep' => '09', 'sept' => '09',
            'oktober' => '10', 'okt' => '10', 'october' => '10', 'oct' => '10',
            'november' => '11', 'nov' => '11', 'nop' => '11', 'nopember' => '11',
            'desember' => '12', 'des' => '12', 'december' => '12', 'dec' => '12',
        ];

        $normalized = mb_strtolower(trim($keyword));

        if ($normalized === '') {
            return [];
        }

        $tokens = array_values(array_filter(
            preg_split('/[\s,\/\-\.]+/', $normalized) ?: [],
            fn ($token) => $token !== ''
        ));

        if (empty($tokens)) {
            return [];
        }

        $resolveMonth = function (string $token) use ($months): ?string {
            if (isset($months[$token])) {
                return $months[$token];
            }

            if (strlen($token) < 2) {
                return null;
            }

            $matches = [];

            foreach ($months as $monthName => $monthNumber) {
                if (str_starts_with($monthName, $token)) {
                    $matches[$monthNumber] = true;
                }
            }

            return count($matches) === 1 ? array_key_first($matches) : null;
        };

        foreach ($tokens as $token) {
            if ($resolveMonth($token) === null && ! ctype_digit($token)) {
                return [];
            }
        }

        $monthFromName = null;
        $year = null;
        $numerics = [];

        foreach ($tokens as $token) {
            $resolvedMonth = $resolveMonth($token);

            if ($resolvedMonth !== null) {
                $monthFromName = $resolvedMonth;
            } else {
                $value = (int) $token;

                if (strlen($token) === 4 && $value >= 1900 && $value <= 2100) {
                    $year = $token;
                } else {
                    $numerics[] = $value;
                }
            }
        }

        if ($monthFromName === null && $year === null && count($numerics) === 1) {
            return [];
        }

        $candidates = [];
        $yearPart = $year ?? '%';
        $pad = fn (int $value) => str_pad((string) $value, 2, '0', STR_PAD_LEFT);

        if ($monthFromName !== null) {
            if (empty($numerics)) {
                $candidates[] = $yearPart.'-'.$monthFromName.'-%';
            } else {
                foreach ($numerics as $value) {
                    if ($value >= 1 && $value <= 31) {
                        $candidates[] = $yearPart.'-'.$monthFromName.'-'.$pad($value).'%';
                    }
                }
            }
        } elseif (count($numerics) === 1) {
            $value = $numerics[0];

            if ($year !== null && $value >= 1 && $value <= 12) {
                $candidates[] = $yearPart.'-'.$pad($value).'-%';
            }
            if ($value >= 1 && $value <= 31) {
                $candidates[] = $yearPart.'-%-'.$pad($value).'%';
            }
        } elseif (count($numerics) >= 2) {
            [$first, $second] = $numerics;

            if ($first >= 1 && $first <= 31 && $second >= 1 && $second <= 12) {
                $candidates[] = $yearPart.'-'.$pad($second).'-'.$pad($first).'%';
            }
            if ($first >= 1 && $first <= 12 && $second >= 1 && $second <= 31 && $first !== $second) {
                $candidates[] = $yearPart.'-'.$pad($first).'-'.$pad($second).'%';
            }
        } elseif ($year !== null) {
            $candidates[] = $yearPart.'-%-%';
        }

        return array_values(array_unique($candidates));
    }
}
