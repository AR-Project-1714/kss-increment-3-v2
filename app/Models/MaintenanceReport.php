<?php

namespace App\Models;

use App\Enums\MaintenanceStatus;
use Illuminate\Database\Eloquent\Model;

class MaintenanceReport extends Model
{
    public const DRAFT_TTL_DAYS = 3;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'report_date'  => 'date',
            'submitted_at' => 'datetime',
            'approved_at'  => 'datetime',
            'status'       => MaintenanceStatus::class,
        ];
    }

    /**
     * Hapus draft yang sudah melewati masa simpan tanpa dilanjutkan.
     * Selaras dengan perilaku draft pada modul operasional.
     */
    public static function pruneStaleDrafts(): int
    {
        $cutoff = now()->subDays(self::DRAFT_TTL_DAYS);

        return static::query()
            ->where('status', MaintenanceStatus::Draft)
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

    public function workItems()
    {
        return $this->hasMany(MaintenanceWorkItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function mainWorkItems()
    {
        return $this->workItems()->where('work_type', 'utama');
    }

    public function priorityWorkItems()
    {
        return $this->workItems()->where('work_type', 'prioritas');
    }

    public function unitConditions()
    {
        return $this->hasMany(MaintenanceUnitCondition::class);
    }

    public function attendances()
    {
        return $this->hasMany(MaintenanceAttendance::class)->orderBy('sort_order')->orderBy('id');
    }
}
