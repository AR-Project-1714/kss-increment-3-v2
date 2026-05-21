<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterEmployee extends Model
{
    use HasFactory;

    protected $table = 'master_employees';

    protected $fillable = [
        'npk',
        'name',
        'group_name',
        'position',
        'status',
    ];
}
