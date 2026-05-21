<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContainerItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function containerActivity()
    {
        return $this->belongsTo(ContainerActivity::class);
    }
}
