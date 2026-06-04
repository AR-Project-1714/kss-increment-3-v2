<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SafetyOperationLog extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function report()
    {
        return $this->belongsTo(SafetyReport::class, 'safety_report_id');
    }
}
