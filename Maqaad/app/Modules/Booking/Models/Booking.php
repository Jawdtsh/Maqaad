<?php

namespace App\Modules\Booking\Models;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Session\Models\TrainingSession;
use App\Modules\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $table = 'bookings';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'charged_amount' => 'decimal:2',
            'commission_rate_snapshot' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'created_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(
            TrainingSession::class,
            'session_id'
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}