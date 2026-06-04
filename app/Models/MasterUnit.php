<?php

namespace App\Models;

use App\Models\Concerns\InvalidatesMasterDataCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterUnit extends Model
{
    use HasFactory;
    use InvalidatesMasterDataCache;

    public const MASTER_DATA_CACHE_KEY = 'master_data.vehicles';
    public const MAINTENANCE_DATA_CACHE_KEY = 'maintenance.master.units';
    public const MASTER_DATA_CACHE_KEYS = [
        self::MASTER_DATA_CACHE_KEY,
        self::MAINTENANCE_DATA_CACHE_KEY,
    ];

    public const MACRO_TRUCK = 'truck';
    public const MACRO_HEAVY = 'heavy';

    protected $table = 'master_units';

    protected $fillable = [
        'name',
        'type',
        'unit_code',
        'brand',
        'unit_number',
        'macro_category',
        'status',
    ];

    public function checkLogs()
    {
        return $this->hasMany(UnitCheckLog::class, 'master_id', 'id')->where('category', 'vehicle');
    }

    public function maintenanceConditions()
    {
        return $this->hasMany(MaintenanceUnitCondition::class, 'master_unit_id');
    }

    public function maintenanceWorkItems()
    {
        return $this->hasMany(MaintenanceWorkItem::class, 'master_unit_id');
    }

    /**
     * Label pemeliharaan: kode + merk + nomor.
     * Tampilan operasional tetap memakai kolom name.
     */
    public function getDisplayNameAttribute(): string
    {
        if (filled($this->unit_code) && filled($this->unit_number)) {
            return trim(implode(' ', array_filter([
                $this->unit_code,
                $this->brand,
                $this->unit_number,
            ])));
        }

        return (string) $this->name;
    }

    public function getShortDisplayNameAttribute(): string
    {
        $unitCode = $this->unit_code ?: $this->unitCodeFromTypeOrName();
        $unitNumber = $this->unit_number ?: $this->unitNumberFromName();

        if (filled($unitCode) && filled($unitNumber)) {
            return trim($unitCode.' '.$unitNumber);
        }

        return (string) $this->name;
    }

    private function unitCodeFromTypeOrName(): ?string
    {
        foreach ([$this->type, $this->name] as $candidate) {
            $value = strtolower(trim((string) $candidate));
            $value = preg_replace('/[\s._-]+/', ' ', $value) ?: '';

            $unitCode = match (true) {
                str_starts_with($value, 'trailer'),
                str_starts_with($value, 'trailler') => 'TRL',
                str_starts_with($value, 'tronton') => 'TRT',
                str_starts_with($value, 'dump truck'),
                str_starts_with($value, 'dt ') => 'DT',
                str_starts_with($value, 'forklift'),
                str_starts_with($value, 'fl ') => 'FL',
                str_starts_with($value, 'wheel loader'),
                str_starts_with($value, 'wl ') => 'WL',
                str_starts_with($value, 'excavator'),
                str_starts_with($value, 'exc ') => 'EXC',
                str_starts_with($value, 'pick up'),
                str_starts_with($value, 'pu ') => 'PU',
                str_starts_with($value, 'bus') => 'BUS',
                default => null,
            };

            if ($unitCode !== null) {
                return $unitCode;
            }
        }

        return null;
    }

    private function unitNumberFromName(): ?string
    {
        if (! preg_match('/\b(KSS|KAD)[\s.-]*(\d+)\b/i', (string) $this->name, $matches)) {
            return null;
        }

        return strtoupper($matches[1]).'-'.str_pad($matches[2], 2, '0', STR_PAD_LEFT);
    }
}
