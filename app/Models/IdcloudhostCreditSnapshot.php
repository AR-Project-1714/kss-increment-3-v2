<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdcloudhostCreditSnapshot extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'credit_available' => 'float',
            'estimated_daily_cost' => 'float',
            'is_active' => 'boolean',
            'captured_at' => 'datetime',
        ];
    }
}
