<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurbaDelivery extends Model
{
    protected $guarded = ['id'];

    public function turbaActivity()
    {
        return $this->belongsTo(TurbaActivity::class);
    }
}
