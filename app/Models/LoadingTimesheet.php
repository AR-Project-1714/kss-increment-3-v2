<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoadingTimesheet extends Model
{
    protected $guarded = ['id'];

    public function loadingActivity()
    {
        return $this->belongsTo(LoadingActivity::class);
    }
}
