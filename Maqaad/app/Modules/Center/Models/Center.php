<?php

namespace App\Modules\Center\Models;

use Illuminate\Database\Eloquent\Model;

class Center extends Model
{
    protected $table = 'centers';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'created_at' => 'immutable_datetime',
        ];
    }
}