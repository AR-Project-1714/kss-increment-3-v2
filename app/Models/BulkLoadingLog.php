<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkLoadingLog extends Model
{
    protected $guarded = ['id'];

    public function bulkLoadingActivity()
    {
        return $this->belongsTo(BulkLoadingActivity::class);
    }
}
