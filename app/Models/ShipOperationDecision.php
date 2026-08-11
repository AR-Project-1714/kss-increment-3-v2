<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipOperationDecision extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function shipOperation()
    {
        return $this->belongsTo(ShipOperation::class);
    }

    public function dailyReport()
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
