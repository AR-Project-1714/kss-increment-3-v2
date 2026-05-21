<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
