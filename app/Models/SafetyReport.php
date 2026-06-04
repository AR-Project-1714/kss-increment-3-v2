<?php

namespace App\Models;

use App\Enums\SafetyStatus;
use Illuminate\Database\Eloquent\Model;

class SafetyReport extends Model
{
    public const DRAFT_TTL_DAYS = 3;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'report_date'  => 'date',
            'submitted_at' => 'datetime',
            'approved_at'  => 'datetime',
            'status'       => SafetyStatus::class,
        ];
    }

    /**
     * Hapus draft yang sudah melewati masa simpan tanpa dilanjutkan.
     * Selaras dengan perilaku draft pada modul operasional & pemeliharaan.
     */
    public static function pruneStaleDrafts(): int
    {
        $cutoff = now()->subDays(self::DRAFT_TTL_DAYS);

        return static::query()
            ->where('status', SafetyStatus::Draft)
            ->where(function ($query) use ($cutoff): void {
                $query->where('updated_at', '<', $cutoff)
                    ->orWhere(function ($fallback) use ($cutoff): void {
                        $fallback->whereNull('updated_at')
                            ->where('created_at', '<', $cutoff);
                    });
            })
            ->delete();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function inspections()
    {
        return $this->hasMany(SafetyInspection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function operationLogs()
    {
        return $this->hasMany(SafetyOperationLog::class)->orderBy('sort_order')->orderBy('id');
    }

    public function incidentLogs()
    {
        return $this->hasMany(SafetyIncidentLog::class)->orderBy('sort_order')->orderBy('id');
    }
}
