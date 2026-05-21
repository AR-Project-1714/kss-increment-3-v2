<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterTruck extends Model
{
    protected $fillable = [
        'name',
        'plate_number',
        'description',
    ];
}
