<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris keadaan sistem pada satu tanggal. Diisi sekali sehari oleh
 * perintah system:snapshot dan dibaca dashboard admin untuk menghitung
 * perubahan antar periode.
 */
class SystemMetricSnapshot extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'captured_on' => 'date',
            'storage_used_bytes' => 'integer',
            'active_users' => 'integer',
            'total_users' => 'integer',
            'security_events' => 'integer',
            'activity_events' => 'integer',
            'reports_created' => 'integer',
        ];
    }
}
