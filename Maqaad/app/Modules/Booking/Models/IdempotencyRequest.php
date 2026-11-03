<?php

namespace App\Modules\Booking\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyRequest extends Model
{
    protected $table = 'idempotency_requests';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}